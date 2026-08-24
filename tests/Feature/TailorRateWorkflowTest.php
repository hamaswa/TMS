<?php

namespace Tests\Feature;

use App\Models\Options;
use App\Models\Customers;
use App\Models\Order;
use App\Models\MeasurementTemplate;
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
        $rateId = DB::table('tailorsalaries')->insertGetId([
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
            ->assertSee('900 -- Mens suit', false)
            ->assertSee('value="'.$rateId.'-900" selected', false);
    }

    public function test_legacy_rate_without_an_option_still_renders_on_the_rate_page(): void
    {
        $role = Role::firstOrCreate(['name' => 'shop_owner', 'guard_name' => 'web']);
        $owner = User::factory()->create(['tailoring_access' => true]);
        $owner->assignRole($role);
        $tailor = Tailor::create([
            'name' => 'Legacy Rate Tailor',
            'phone_number1' => '03001230012',
            'password' => bcrypt('QaTailor@2026'),
            'user_id' => $owner->id,
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
            ->get(route('admin.tailor-rates', $tailor))
            ->assertOk()
            ->assertSeeText('Mens suit')
            ->assertSeeText('900.00');
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

        $this->actingAs($owner)
            ->get(route('admin.order.create', $customer))
            ->assertOk()
            ->assertSeeText('گاہک کا پچھلا مشترکہ بقایا')
            ->assertSee('<h1', false)
            ->assertSeeText('اس آرڈر کی کل قیمت')
            ->assertSeeText('ابھی وصول شدہ رقم')
            ->assertSeeText('اس آرڈر کی باقی رقم')
            ->assertSee('for="totalPayment"', false)
            ->assertSee('for="recivedPayment"', false)
            ->assertSee('id="totalPayment"', false)
            ->assertSee('id="recivedPayment"', false)
            ->assertSee('aria-label="محفوظ ناپ تلاش کریں"', false);

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

    public function test_order_is_blocked_until_selected_template_measurements_are_complete(): void
    {
        Notification::fake();
        $role = Role::firstOrCreate(['name' => 'shop_owner', 'guard_name' => 'web']);
        $owner = User::factory()->create(['tailoring_access' => true]);
        $owner->assignRole($role);
        $template = MeasurementTemplate::create([
            'user_id' => $owner->id,
            'name' => 'Required suit measurements',
            'system_fields' => ['length', 'arms'],
            'custom_field_ids' => [],
            'is_default' => true,
            'is_active' => true,
        ]);
        $customer = Customers::create([
            'name' => 'Incomplete Measurement Customer',
            'phone_number1' => '03005550001',
            'user_id' => $owner->id,
            'measurement_template_id' => $template->id,
            'length' => 42,
        ]);
        $tailor = Tailor::create([
            'name' => 'Measurement QA Tailor',
            'phone_number1' => '03001230009',
            'password' => bcrypt('QaTailor@2026'),
            'user_id' => $owner->id,
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
            'measurement_template_id' => $template->id,
            'suitQuantity' => 1,
            'totalPayment' => 3200,
            'recivedPayment' => 1000,
            'returnDate' => now()->addWeek()->toDateString(),
            'tailorId' => $tailor->id,
            'tailor_price' => $rateId.'-900',
        ];

        $this->actingAs($owner)
            ->from(route('admin.order.create', $customer))
            ->post(route('admin.order.insert'), $payload)
            ->assertRedirect(route('admin.order.create', $customer))
            ->assertSessionHasErrors('measurement_template_id');
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('transactions', 0);

        $customer->update(['arms' => 24]);
        $this->actingAs($owner)->post(route('admin.order.insert'), $payload)->assertRedirect();
        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseHas('order_measurement_values', [
            'source_key' => 'system.arms',
            'value' => '24',
        ]);
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

    public function test_weekly_print_groups_orders_by_sewing_type_with_cost_total_and_serials(): void
    {
        $role = Role::firstOrCreate(['name' => 'shop_owner', 'guard_name' => 'web']);
        $owner = User::factory()->create(['tailoring_access' => true]);
        $owner->assignRole($role);
        $customer = Customers::create([
            'name' => 'Print Customer', 'phone_number1' => '03005550010', 'user_id' => $owner->id,
        ]);
        $tailor = Tailor::create([
            'name' => 'Print Tailor', 'phone_number1' => '03001230010',
            'password' => bcrypt('QaTailor@2026'), 'user_id' => $owner->id,
        ]);
        $rateId = DB::table('tailorsalaries')->insertGetId([
            'tailor_id' => $tailor->id, 'options_id' => null, 'type' => 'سادہ سلائی', 'price' => 500,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        foreach ([[2, json_encode(['SN-101', 'SN-102'])], [1, 'SN-103']] as [$quantity, $serials]) {
            Order::create([
                'customerId' => $customer->id, 'sub_customer' => $customer->id,
                'suitNum' => $serials, 'suitQuantity' => $quantity, 'totalPayment' => 3200,
                'tailorId' => $tailor->id, 'rateId' => $rateId, 'tailor_price' => 500,
                'returnDate' => now()->addWeek()->toDateString(), 'userId' => $owner->id,
                'status' => 'assigned',
            ]);
        }

        $response = $this->actingAs($owner)->get(route('admin.report-print', $tailor));

        $response->assertOk()
            ->assertSee('<span class="rate-breakdown">3 × 500</span>', false)
            ->assertSee('<span class="work-total">Rs. 1,500</span>', false)
            ->assertSee('SN-101, SN-102, SN-103')
            ->assertSee('سوٹ × اجرت')
            ->assertSee('کل اجرت');
        $this->assertSame(1, substr_count($response->getContent(), 'سادہ سلائی'));

        $monthlyOnlyOrder = Order::create([
            'customerId' => $customer->id, 'sub_customer' => $customer->id,
            'suitNum' => 'SN-104', 'suitQuantity' => 1, 'totalPayment' => 3200,
            'tailorId' => $tailor->id, 'rateId' => $rateId, 'tailor_price' => 500,
            'returnDate' => now()->addWeek()->toDateString(), 'userId' => $owner->id,
            'status' => 'assigned',
        ]);
        $monthlyOnlyOrder->forceFill(['created_at' => now()->startOfMonth()->addDay()])->save();

        $monthlyReport = $this->actingAs($owner)->get(route('admin.tailor-report', [
            'id' => $tailor->id,
            'filterType' => 'monthly',
        ]));
        $monthlyReport->assertOk()->assertSee(route('admin.report-print', [
            'id' => $tailor->id,
            'filterType' => 'monthly',
        ]), false);

        $this->actingAs($owner)->get(route('admin.report-print', [
            'id' => $tailor->id,
            'filterType' => 'monthly',
        ]))->assertOk()
            ->assertSeeText('درزی کا ماہانہ حساب')
            ->assertSee('<span class="rate-breakdown">4 × 500</span>', false)
            ->assertSee('<span class="work-total">Rs. 2,000</span>', false)
            ->assertSee('SN-101, SN-102, SN-103, SN-104');
    }

    public function test_tailor_history_formats_serials_dates_and_table_controls_for_urdu_users(): void
    {
        $role = Role::firstOrCreate(['name' => 'shop_owner', 'guard_name' => 'web']);
        $owner = User::factory()->create(['tailoring_access' => true]);
        $owner->assignRole($role);
        $customer = Customers::create([
            'name' => 'فیصل محمود', 'phone_number1' => '03005551234', 'user_id' => $owner->id,
        ]);
        $tailor = Tailor::create([
            'name' => 'رشید محمود', 'phone_number1' => '03001230005',
            'password' => bcrypt('QaTailor@2026'), 'user_id' => $owner->id,
        ]);
        $order = Order::create([
            'customerId' => $customer->id, 'sub_customer' => $customer->id,
            'suitNum' => json_encode(['سوٹ نمبر 1'], JSON_UNESCAPED_UNICODE),
            'suitQuantity' => 1, 'totalPayment' => 3200, 'tailorId' => $tailor->id,
            'tailor_price' => 900, 'returnDate' => '2026-08-02',
            'userId' => $owner->id, 'status' => 'assigned',
        ]);
        $order->forceFill(['created_at' => '2026-07-22 10:00:00'])->save();

        $this->actingAs($owner)
            ->get(route('admin.tailor-orders', $tailor))
            ->assertOk()
            ->assertSeeText('سوٹ نمبر 1')
            ->assertDontSee('[&quot;سوٹ نمبر 1&quot;]', false)
            ->assertSeeText('بدھ')
            ->assertSeeText('22-07-2026')
            ->assertSee('"search": "تلاش:"', false)
            ->assertSee('"next": "اگلا"', false);
    }
}
