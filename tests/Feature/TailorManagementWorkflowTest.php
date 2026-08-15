<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Tailor;
use App\Models\TailorRecord;
use App\Models\TailorSecurityDepositTransaction;
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
            ->assertSeeText('فون نمبر')
            ->assertSeeText('ابتدائی سیکیورٹی ڈپازٹ')
            ->assertSeeText('یہ درزی کو دیا گیا ایڈوانس نہیں');

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
            ->assertRedirect()->assertSessionHas('insert', 'درزی کا مرکزی ایڈوانس محفوظ کر دیا گیا ہے۔');
        $this->actingAs($owner)->post(route('admin.tailor.addAdvanceRecord', $tailor), ['amount' => 200])
            ->assertRedirect();

        $this->assertEquals(700, (float) $tailor->fresh()->advance);
        $this->assertEquals(700, (float) TailorRecord::where('tailor_id', $tailor->id)->where('comment', 'main_advance')->sum('amount'));
        $this->assertDatabaseCount('tailor_records', 2);

        $this->actingAs($otherOwner)->post(route('admin.tailor.addAdvanceRecord', $tailor), ['amount' => 100])
            ->assertNotFound();
        $this->assertEquals(700, (float) $tailor->fresh()->advance);
    }

    public function test_security_deposit_received_from_tailor_is_separate_from_later_advance_paid_to_tailor(): void
    {
        $owner = $this->owner();

        $this->actingAs($owner)->post(route('admin.Tailor.store'), [
            'name' => 'محمد وقاص',
            'contact' => '03005550123',
            'password' => 'Waqas@2026',
            'security_deposit' => 3000,
            'security_deposit_note' => 'رسید نمبر 12',
            'initial_rate_label' => 'معیاری سلائی',
            'initial_rate_price' => 500,
        ])->assertRedirect('admin/Tailor');

        $tailor = Tailor::where('user_id', $owner->id)->firstOrFail();
        $this->assertEquals(3000, (float) $tailor->security_deposit);
        $this->assertEquals(0, (float) $tailor->advance);
        $this->assertDatabaseHas('tailor_security_deposit_transactions', [
            'tailor_id' => $tailor->id,
            'user_id' => $owner->id,
            'transaction_type' => TailorSecurityDepositTransaction::TYPE_RECEIVED,
            'amount' => 3000,
            'note' => 'رسید نمبر 12',
        ]);
        $this->assertDatabaseMissing('tailor_records', [
            'tailor_id' => $tailor->id,
            'comment' => 'advance',
        ]);

        $this->actingAs($owner)->post(route('admin.tailor.addAdvanceRecord', $tailor), [
            'amount' => 800,
        ])->assertRedirect();

        $tailor->refresh();
        $this->assertEquals(3000, (float) $tailor->security_deposit);
        $this->assertEquals(800, (float) $tailor->advance);
        $this->assertDatabaseHas('tailor_records', [
            'tailor_id' => $tailor->id,
            'comment' => 'main_advance',
            'amount' => 800,
        ]);

        $this->actingAs($owner)->post(route('admin.tailor.securityDeposit', $tailor), [
            'transaction_type' => TailorSecurityDepositTransaction::TYPE_REFUNDED,
            'amount' => 1000,
            'note' => 'جزوی واپسی',
        ])->assertRedirect();

        $tailor->refresh();
        $this->assertEquals(2000, (float) $tailor->security_deposit);
        $this->assertEquals(800, (float) $tailor->advance);

        $this->actingAs($owner)->post(route('admin.tailor.securityDeposit', $tailor), [
            'transaction_type' => TailorSecurityDepositTransaction::TYPE_REFUNDED,
            'amount' => 2500,
        ])->assertSessionHasErrors('amount');
        $this->assertEquals(2000, (float) $tailor->fresh()->security_deposit);

        $this->actingAs($owner)->get(route('admin.Tailor.index'))
            ->assertOk()
            ->assertSeeText('سیکیورٹی ڈپازٹ')
            ->assertSeeText('درزی کو دیا گیا ایڈوانس');

        $this->actingAs($owner)->get(route('admin.tailor-report', $tailor))
            ->assertOk()
            ->assertSeeText('دکان کے پاس سیکیورٹی ڈپازٹ')
            ->assertSeeText('مرکزی قابلِ وصول ایڈوانس')
            ->assertSeeText('جزوی واپسی');
    }

    public function test_weekly_advance_is_separate_from_main_advance_and_is_deducted_from_weekly_payments(): void
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

        $this->assertEquals(0, (float) $tailor->fresh()->advance);
        $this->assertDatabaseHas('tailor_records', ['tailor_id' => $tailor->id, 'comment' => 'advance', 'amount' => 300]);
        $this->assertDatabaseHas('tailor_records', ['tailor_id' => $tailor->id, 'comment' => 'salary', 'amount' => 150]);
        $this->actingAs($owner)->get(route('admin.tailor-report', $tailor))
            ->assertOk()
            ->assertSeeText('موجودہ ہفتہ')
            ->assertSeeText('ہفتہ وار ایڈوانس')
            ->assertSeeText('ادائیگیوں سے منہا ایڈوانس')
            ->assertSeeText('حتمی ہفتہ وار رقم')
            ->assertSeeText('اجرت کی ادائیگی')
            ->assertDontSee('weekFilter=undefined')
            ->assertDontSee('ajax.googleapis.com');

        $this->actingAs($owner)->post(route('admin.tailor.addAdvanceRecord', $tailor), ['amount' => 500])
            ->assertRedirect();
        $this->actingAs($owner)->post(route('admin.tailor.cutAdvanceRecord', $tailor), [
            'amount' => 300,
            'total' => 150,
        ])->assertRedirect();

        $this->assertEquals(200, (float) $tailor->fresh()->advance);
        $this->actingAs($owner)->get(route('admin.tailor-report', $tailor))
            ->assertOk()
            ->assertSeeText('مرکزی ایڈوانس سے کٹوتی')
            ->assertDontSeeText('ادائیگیوں سے منہا ایڈوانس');
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
