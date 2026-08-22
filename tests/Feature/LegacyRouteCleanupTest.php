<?php

namespace Tests\Feature;

use App\Models\Customers;
use App\Models\Order;
use App\Models\Sale;
use App\Models\Setting;
use App\Models\Tailor;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LegacyRouteCleanupTest extends TestCase
{
    use RefreshDatabase;

    public function test_debug_and_blank_legacy_routes_are_not_registered(): void
    {
        $routes = app('router')->getRoutes();

        $this->assertNull($routes->getByName('admin.payment-received'));
        $this->assertNull($routes->getByName('admin.tailor-rates.edit'));
        $this->get('/new-tab')->assertNotFound();
    }

    public function test_legacy_public_registration_cannot_create_an_unscoped_user(): void
    {
        $this->get('/register')->assertRedirect('/', 301);

        $this->post('/register', [
            'name' => 'Unscoped customer',
            'email' => 'unscoped@example.test',
            'password' => 'Password@2026',
            'password_confirmation' => 'Password@2026',
        ])->assertRedirect('/', 301);

        $this->assertDatabaseMissing('users', ['email' => 'unscoped@example.test']);
    }

    public function test_order_print_uses_a_safe_default_when_shop_setup_is_missing(): void
    {
        [$owner, $order] = $this->orderWithoutActiveSetting();

        $this->actingAs($owner)
            ->get(route('admin.order-print', $order))
            ->assertOk()
            ->assertViewIs('order.print')
            ->assertSeeText($owner->name);

        $this->assertDatabaseHas('settings', [
            'user_id' => $owner->id,
            'status' => 1,
        ]);
    }

    public function test_sale_print_uses_a_safe_default_when_shop_setup_is_missing(): void
    {
        [$owner] = $this->orderWithoutActiveSetting();
        $sale = Sale::create(['user_id' => $owner->id, 'customer_name' => 'Walk-in customer']);

        $this->actingAs($owner)
            ->get(route('admin.sale-print', $sale))
            ->assertOk()
            ->assertViewIs('sale.print')
            ->assertSeeText($owner->name);
    }

    public function test_active_order_print_routes_render_decoded_serials_and_their_intended_views(): void
    {
        [$owner, $order] = $this->orderWithoutActiveSetting();
        $order->update([
            'suitNum' => json_encode(['Suit 1']),
            'remarks' => 'Bring matching buttons - کالر نرم رکھیں',
        ]);
        Setting::forceCreate([
            'user_id' => $owner->id,
            'name' => 'QA Tailors',
            'status' => 1,
            'note' => '',
            'address' => '',
            'logo' => '',
            'contact_no' => '',
        ]);
        Transaction::create([
            'customerId' => $order->customerId,
            'userId' => $owner->id,
            'Order_type' => 'Sale',
            'remainingBalance' => 300,
        ]);
        Transaction::create([
            'customerId' => $order->customerId,
            'orderId' => $order->id,
            'userId' => $owner->id,
            'Order_type' => 'Tailor',
            'recivedPayment' => 500,
            'remainingBalance' => 500,
        ]);
        Transaction::create([
            'customerId' => $order->customerId,
            'userId' => $owner->id,
            'Order_type' => 'Tailor',
            'remainingBalance' => 700,
        ]);

        $this->actingAs($owner)->get(route('admin.order-print', $order))
            ->assertOk()
            ->assertViewIs('order.print')
            ->assertSeeText('Suit 1')
            ->assertSeeText('اس آرڈر کا بقایا:')
            ->assertSeeText('گزشتہ واجبات:')
            ->assertSeeText('کل واجب الادا:')
            ->assertSeeText('800.00')
            ->assertDontSeeText('1,500.00');

        $this->actingAs($owner)->get(route('admin.order-prints', $order))
            ->assertOk()
            ->assertViewIs('order.prints')
            ->assertSeeText('Suit 1');
    }

    public function test_print_documents_support_safe_paper_overrides_and_qr_references(): void
    {
        [$owner, $order] = $this->orderWithoutActiveSetting();
        $order->update(['suitNum' => json_encode(['Suit 1'])]);
        $setting = Setting::forceCreate([
            'user_id' => $owner->id,
            'name' => 'QA Tailors',
            'shop_slug' => 'qa-tailors',
            'status' => 1,
            'note' => '',
            'address' => '',
            'logo' => '',
            'contact_no' => '',
            'print_paper_size' => Setting::PRINT_PAPER_RECEIPT_80,
            'print_show_qr' => true,
        ]);
        Transaction::create([
            'customerId' => $order->customerId,
            'orderId' => $order->id,
            'userId' => $owner->id,
            'Order_type' => 'Tailor',
            'recivedPayment' => 500,
            'remainingBalance' => 500,
        ]);

        $this->actingAs($owner)
            ->get(route('admin.order-print', $order).'?paper=a4')
            ->assertOk()
            ->assertSee('tms-paper-a4', false)
            ->assertSee('class="receipt-date"', false)
            ->assertSee('class="order-summary-row"', false)
            ->assertSee('order-note', false)
            ->assertSee('dir="auto"', false)
            ->assertSee('unicode-bidi: plaintext', false)
            ->assertSee('TMS REF: '.$order->id)
            ->assertSee('<svg', false);

        $this->actingAs($owner)
            ->get(route('admin.order-print', $order).'?paper=unsupported')
            ->assertOk()
            ->assertSee('tms-paper-receipt_80', false);

        $setting->update(['print_show_qr' => false]);

        $this->actingAs($owner)
            ->get(route('admin.order-print', $order))
            ->assertOk()
            ->assertDontSee('TMS REF: '.$order->id);
    }

    public function test_order_notes_field_has_an_explicit_accessible_label_and_direction(): void
    {
        [$owner, $order] = $this->orderWithoutActiveSetting();

        $this->actingAs($owner)
            ->get(route('admin.order.create', $order->customerId))
            ->assertOk()
            ->assertSee('for="order_remarks"', false)
            ->assertSee('id="order_remarks"', false)
            ->assertSee('dir="auto"', false);
    }

    private function orderWithoutActiveSetting(): array
    {
        $role = Role::firstOrCreate(['name' => 'shop_owner', 'guard_name' => 'web']);
        $owner = User::factory()->create(['tailoring_access' => true]);
        $owner->assignRole($role);
        $customer = Customers::create([
            'name' => 'Test customer',
            'phone_number1' => '03001234567',
            'user_id' => $owner->id,
        ]);
        $tailor = Tailor::create([
            'name' => 'Test tailor',
            'phone_number1' => '03007654321',
            'user_id' => $owner->id,
        ]);
        $order = Order::create([
            'customerId' => $customer->id,
            'sub_customer' => $customer->id,
            'suitQuantity' => 1,
            'totalPayment' => 1000,
            'userId' => $owner->id,
            'tailorId' => $tailor->id,
        ]);

        return [$owner, $order];
    }
}
