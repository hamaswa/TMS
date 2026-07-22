<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Tailor;
use App\Models\TailorRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TailorManagementWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_can_create_tailor_and_duplicate_phone_is_rejected_with_urdu_ui(): void
    {
        $owner = $this->owner();

        $this->actingAs($owner)->get(route('admin.Tailor.create'))
            ->assertOk()
            ->assertSeeText('پورٹل پاس ورڈ')
            ->assertSeeText('فون نمبر');

        $payload = [
            'name' => 'محمد وقاص',
            'contact' => '03005550123',
            'password' => 'Waqas@2026',
        ];
        $this->actingAs($owner)->post(route('admin.Tailor.store'), $payload)
            ->assertRedirect('admin/Tailor')
            ->assertSessionHas('insert', 'نیا درزی کامیابی سے شامل کر دیا گیا ہے۔');

        $this->actingAs($owner)->post(route('admin.Tailor.store'), $payload)
            ->assertSessionHasErrors('contact');
        $this->assertDatabaseCount('tailors', 1);

        $this->actingAs($owner)->get(route('admin.Tailor.index'))
            ->assertOk()
            ->assertSeeText('حساب اور لین دین')
            ->assertSeeText('ایڈوانس دیں')
            ->assertDontSeeText('Tailor Record');
    }

    public function test_advance_is_additive_audited_and_tenant_scoped(): void
    {
        $owner = $this->owner();
        $otherOwner = $this->owner();
        $tailor = Tailor::create([
            'user_id' => $owner->id,
            'name' => 'محمد وقاص',
            'phone_number1' => '03005550123',
            'password' => bcrypt('Waqas@2026'),
        ]);

        $this->assertTrue(Schema::hasColumn('tailors', 'advance'));
        $this->actingAs($owner)->post(route('admin.tailor.addAdvanceRecord', $tailor), ['amount' => 500])
            ->assertRedirect()->assertSessionHas('insert', 'درزی کا ایڈوانس محفوظ کر دیا گیا ہے۔');
        $this->actingAs($owner)->post(route('admin.tailor.addAdvanceRecord', $tailor), ['amount' => 200])
            ->assertRedirect();

        $this->assertEquals(700, (float) $tailor->fresh()->advance);
        $this->assertEquals(700, (float) TailorRecord::where('tailor_id', $tailor->id)->where('comment', 'advance')->sum('amount'));
        $this->assertDatabaseCount('tailor_records', 2);

        $this->actingAs($otherOwner)->post(route('admin.tailor.addAdvanceRecord', $tailor), ['amount' => 100])
            ->assertNotFound();
        $this->assertEquals(700, (float) $tailor->fresh()->advance);
    }

    public function test_report_transactions_update_advance_only_for_advance_type(): void
    {
        $owner = $this->owner();
        $tailor = Tailor::create([
            'user_id' => $owner->id,
            'name' => 'محمد وقاص',
            'phone_number1' => '03005550123',
            'password' => bcrypt('Waqas@2026'),
        ]);

        $this->actingAs($owner)->post(route('admin.tailor.addRecord', $tailor), [
            'amount' => 300,
            'comment' => 'advance',
        ])->assertRedirect()->assertSessionHas('success', 'درزی کا لین دین محفوظ کر دیا گیا ہے۔');
        $this->actingAs($owner)->post(route('admin.tailor.addRecord', $tailor), [
            'amount' => 150,
            'comment' => 'salary',
        ])->assertRedirect();

        $this->assertEquals(300, (float) $tailor->fresh()->advance);
        $this->assertDatabaseHas('tailor_records', ['tailor_id' => $tailor->id, 'comment' => 'salary', 'amount' => 150]);
        $this->actingAs($owner)->get(route('admin.tailor-report', $tailor))
            ->assertOk()
            ->assertSeeText('موجودہ ہفتہ')
            ->assertSeeText('ایڈوانس')
            ->assertSeeText('اجرت کی ادائیگی')
            ->assertDontSee('weekFilter=undefined')
            ->assertDontSee('ajax.googleapis.com');
    }

    private function owner(): User
    {
        $role = Role::firstOrCreate(['name' => 'shop_owner', 'guard_name' => 'web']);
        $owner = User::factory()->create([
            'tailoring_access' => true,
            'is_business_owner' => true,
        ]);
        $owner->assignRole($role);
        $business = Business::create([
            'name' => $owner->name,
            'owner_user_id' => $owner->id,
            'tailoring_enabled' => true,
            'clothing_enabled' => false,
            'status' => Business::STATUS_ACTIVE,
        ]);
        $owner->forceFill(['business_id' => $business->id])->save();

        return $owner;
    }
}
