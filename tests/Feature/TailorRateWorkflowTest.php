<?php

namespace Tests\Feature;

use App\Models\Options;
use App\Models\Customers;
use App\Models\Order;
use App\Models\Tailor;
use App\Models\Transaction;
use App\Models\User;
use Database\Seeders\OptionTypesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TailorRateWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_can_create_a_rate_for_their_own_sewing_option(): void
    {
        $this->seed(OptionTypesSeeder::class);
        $role = Role::firstOrCreate(['name' => 'shop_owner', 'guard_name' => 'web']);
        $owner = User::factory()->create(['tailoring_access' => true]);
        $owner->assignRole($role);
        $tailor = Tailor::create([
            'name' => 'QA Tailor', 'phone_number1' => '03001230000',
            'password' => bcrypt('QaTailor@2026'), 'user_id' => $owner->id,
        ]);
        $option = Options::create([
            'user_id' => $owner->id, 'option_id' => 1,
            'Name' => 'سادہ سلائی', 'slug' => 'simple',
        ]);

        $this->actingAs($owner)->post(route('admin.tailor-rates.store', $tailor), [
            'options_id' => $option->id,
            'price' => 500,
        ])->assertRedirect();

        $this->assertDatabaseHas('tailorsalaries', [
            'tailor_id' => $tailor->id,
            'options_id' => $option->id,
            'price' => 500,
        ]);
    }

    public function test_client_cannot_use_another_clients_sewing_option(): void
    {
        $this->seed(OptionTypesSeeder::class);
        $role = Role::firstOrCreate(['name' => 'shop_owner', 'guard_name' => 'web']);
        $owner = User::factory()->create(['tailoring_access' => true]);
        $other = User::factory()->create(['tailoring_access' => true]);
        $owner->assignRole($role);
        $other->assignRole($role);
        $tailor = Tailor::create([
            'name' => 'Own Tailor', 'phone_number1' => '03001230001',
            'password' => bcrypt('QaTailor@2026'), 'user_id' => $owner->id,
        ]);
        $otherOption = Options::create([
            'user_id' => $other->id, 'option_id' => 1,
            'Name' => 'Other Sewing', 'slug' => 'other-sewing',
        ]);

        $this->actingAs($owner)->post(route('admin.tailor-rates.store', $tailor), [
            'options_id' => $otherOption->id,
            'price' => 500,
        ])->assertNotFound();

        $this->assertDatabaseMissing('tailorsalaries', ['tailor_id' => $tailor->id]);
    }

    public function test_legacy_text_rate_is_available_when_creating_an_order(): void
    {
        $role = Role::firstOrCreate(['name' => 'shop_owner', 'guard_name' => 'web']);
        $owner = User::factory()->create(['tailoring_access' => true]);
        $owner->assignRole($role);
        $tailor = Tailor::create([
            'name' => 'Rashid Mahmood', 'phone_number1' => '03001230002',
            'password' => bcrypt('QaTailor@2026'), 'user_id' => $owner->id,
        ]);
        DB::table('tailorsalaries')->insert([
            'tailor_id' => $tailor->id,
            'options_id' => null,
            'type' => 'Mens suit',
            'price' => 900,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($owner)
            ->get(route('admin.tailor.salary', $tailor))
            ->assertOk()
            ->assertSee('900 -- Mens suit', false);
    }

    public function test_order_balance_is_calculated_on_the_server_and_overpayment_is_rejected(): void
    {
        Notification::fake();
        $role = Role::firstOrCreate(['name' => 'shop_owner', 'guard_name' => 'web']);
        $owner = User::factory()->create(['tailoring_access' => true]);
        $owner->assignRole($role);
        $customer = Customers::create([
            'name' => 'Faisal Mahmood',
            'phone_number1' => '03005551234',
            'user_id' => $owner->id,
        ]);
        $tailor = Tailor::create([
            'name' => 'Rashid Mahmood', 'phone_number1' => '03001230003',
            'password' => bcrypt('QaTailor@2026'), 'user_id' => $owner->id,
        ]);
        $rateId = DB::table('tailorsalaries')->insertGetId([
            'tailor_id' => $tailor->id,
            'options_id' => null,
            'type' => 'Mens suit',
            'price' => 900,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $payload = [
            'customerId' => $customer->id,
            'suitQuantity' => 1,
            'totalPayment' => 3200,
            'recivedPayment' => 1500,
            'balance' => 9999,
            'returnDate' => now()->addWeek()->toDateString(),
            'tailorId' => $tailor->id,
            'tailor_price' => $rateId.'-900',
            'serail' => 'QA-001',
        ];

        $this->actingAs($owner)->post(route('admin.order.insert'), $payload)->assertRedirect();

        $this->assertDatabaseHas('transactions', [
            'customerId' => (string) $customer->id,
            'recivedPayment' => '1500',
            'remainingBalance' => '1700',
        ]);

        $this->actingAs($owner)
            ->from(route('admin.order.create', $customer))
            ->post(route('admin.order.insert'), array_merge($payload, [
                'recivedPayment' => 3300,
                'balance' => 0,
            ]))
            ->assertRedirect(route('admin.order.create', $customer))
            ->assertSessionHasErrors('recivedPayment');

        $this->assertDatabaseCount('orders', 1);
    }

    public function test_order_edit_has_one_tailor_selector_and_separates_order_and_customer_balances(): void
    {
        $role = Role::firstOrCreate(['name' => 'shop_owner', 'guard_name' => 'web']);
        $owner = User::factory()->create(['tailoring_access' => true]);
        $owner->assignRole($role);
        $customer = Customers::create([
            'name' => 'Faisal Mahmood', 'phone_number1' => '03005551234', 'user_id' => $owner->id,
        ]);
        $tailor = Tailor::create([
            'name' => 'Rashid Mahmood', 'phone_number1' => '03001230004',
            'password' => bcrypt('QaTailor@2026'), 'user_id' => $owner->id,
        ]);
        $rateId = DB::table('tailorsalaries')->insertGetId([
            'tailor_id' => $tailor->id, 'options_id' => null, 'type' => 'Mens suit', 'price' => 900,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $order = Order::create([
            'customerId' => $customer->id, 'sub_customer' => $customer->id,
            'suitQuantity' => 1, 'totalPayment' => 3200, 'tailorId' => $tailor->id,
            'rateId' => $rateId, 'tailor_price' => 900, 'returnDate' => now()->addWeek()->toDateString(),
            'userId' => $owner->id, 'status' => 'assigned',
        ]);
        Transaction::create([
            'customerId' => $customer->id, 'orderId' => $order->id, 'userId' => $owner->id,
            'Order_type' => 'Tailor', 'recivedPayment' => 1500, 'remainingBalance' => 1700,
        ]);
        Transaction::create([
            'customerId' => $customer->id, 'userId' => $owner->id,
            'Order_type' => 'Payment', 'recivedPayment' => 1000, 'remainingBalance' => -1000,
        ]);

        $response = $this->actingAs($owner)->get(route('admin.order.edit', $order));
        $response->assertOk()
            ->assertSeeText('اس آرڈر کا بقایا')
            ->assertSeeText('گاہک کا مشترکہ بقایا')
            ->assertSee('value="1700"', false)
            ->assertSee('value="700"', false)
            ->assertSee('900 -- Mens suit');
        $this->assertSame(1, substr_count($response->getContent(), 'name="tailorId"'));
        $this->assertSame(1, substr_count($response->getContent(), 'name="tailor_price"'));

        $payload = [
            'sub_id' => $customer->id, 'customerId' => $customer->id, 'suitQuantity' => 1,
            'totalPayment' => 3200, 'recivedPayment' => 3300, 'tailorId' => $tailor->id,
            'tailor_price' => $rateId.'-900', 'returnDate' => now()->addWeek()->toDateString(),
            'remarks' => 'Slim fit kurta',
        ];
        $this->actingAs($owner)
            ->from(route('admin.order.edit', $order))
            ->put(route('admin.order.update', $order), $payload)
            ->assertRedirect(route('admin.order.edit', $order))
            ->assertSessionHasErrors('recivedPayment');

        $this->assertEquals(1700, (float) $order->transactions()->value('remainingBalance'));
    }
}
