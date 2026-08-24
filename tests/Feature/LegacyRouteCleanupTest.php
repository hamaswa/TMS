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
use Illuminate\Support\Facades\URL;
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

    public function test_order_receipt_qr_opens_signed_public_status_and_payment_page(): void
    {
        [$owner, $order] = $this->orderWithoutActiveSetting();
        $order->update(['status' => 'ready', 'returnDate' => '2026-08-30']);
        Transaction::create([
            'customerId' => $order->customerId,
            'userId' => $owner->id,
            'Order_type' => 'Sale',
            'remainingBalance' => 200,
        ]);
        Transaction::create([
            'customerId' => $order->customerId,
            'orderId' => $order->id,
            'userId' => $owner->id,
            'Order_type' => 'Tailor',
            'remainingBalance' => 450,
        ]);

        $trackingUrl = URL::signedRoute('orders.track', ['order' => $order->id]);

        $this->get(route('orders.track', $order))->assertForbidden();

        $this->get($trackingUrl)
            ->assertOk()
            ->assertViewIs('order.track')
            ->assertSeeText('تیار ہے')
            ->assertSeeText('Test customer')
            ->assertSeeText('ادائیگی موصول نہیں ہوئی')
            ->assertSeeText('پچھلا بقایا باقی ہے')
            ->assertSeeText('200.00')
            ->assertDontSeeText('موجودہ باقی بقایا')
            ->assertDontSeeText('آرڈر کی پیش رفت');

        Transaction::create([
            'customerId' => $order->customerId,
            'orderId' => $order->id,
            'userId' => $owner->id,
            'Order_type' => 'Payment',
            'recivedPayment' => 450,
            'remainingBalance' => -450,
        ]);

        $this->get($trackingUrl)
            ->assertOk()
            ->assertSeeText('ادائیگی موصول ہو گئی ہے')
            ->assertSeeText('پچھلا بقایا باقی ہے')
            ->assertSeeText('200.00')
            ->assertDontSeeText('ادائیگی موصول نہیں ہوئی');

        $this->actingAs($owner)
            ->get(route('admin.order-print', $order))
            ->assertOk()
            ->assertSee('class="order-tracking-qr"', false)
            ->assertSee(e($trackingUrl), false)
            ->assertSee('<svg', false);

        $this->actingAs($owner)
            ->get(route('admin.order-prints', $order))
            ->assertOk()
            ->assertSee('class="order-tracking-qr"', false);
    }

    public function test_shared_payment_is_applied_to_oldest_order_on_receipts(): void
    {
        [$owner, $firstOrder] = $this->orderWithoutActiveSetting();
        $firstOrder->update(['totalPayment' => 1500]);
        $secondOrder = Order::create([
            'customerId' => $firstOrder->customerId,
            'sub_customer' => $firstOrder->sub_customer,
            'suitQuantity' => 1,
            'totalPayment' => 1500,
            'userId' => $owner->id,
            'tailorId' => $firstOrder->tailorId,
        ]);

        foreach ([$firstOrder, $secondOrder] as $order) {
            Transaction::create([
                'customerId' => $order->customerId,
                'orderId' => $order->id,
                'userId' => $owner->id,
                'Order_type' => 'Tailor',
                'recivedPayment' => 0,
                'remainingBalance' => 1500,
            ]);
        }

        Transaction::create([
            'customerId' => $firstOrder->customerId,
            'userId' => $owner->id,
            'Order_type' => 'Payment',
            'recivedPayment' => 1500,
            'remainingBalance' => -1500,
        ]);

        foreach (['admin.order-print', 'admin.order-prints'] as $route) {
            $this->actingAs($owner)
                ->get(route($route, $secondOrder))
                ->assertOk()
                ->assertViewHas('previousBalance', 0.0)
                ->assertViewHas('orderBalance', 1500.0)
                ->assertViewHas('latestBalance', 1500.0)
                ->assertDontSeeText('3000');
        }

        $this->actingAs($owner)
            ->get(route('admin.order-print', $firstOrder))
            ->assertOk()
            ->assertViewHas('orderBalance', 0.0)
            ->assertViewHas('latestBalance', 0.0);
    }

    public function test_customer_order_payment_is_linked_and_reported_for_that_order(): void
    {
        [$owner, $firstOrder] = $this->orderWithoutActiveSetting();
        $firstOrder->update(['totalPayment' => 1500]);
        $secondOrder = Order::create([
            'customerId' => $firstOrder->customerId,
            'sub_customer' => $firstOrder->sub_customer,
            'suitQuantity' => 1,
            'totalPayment' => 1500,
            'userId' => $owner->id,
            'tailorId' => $firstOrder->tailorId,
        ]);

        foreach ([$firstOrder, $secondOrder] as $order) {
            Transaction::create([
                'customerId' => $order->customerId,
                'orderId' => $order->id,
                'userId' => $owner->id,
                'Order_type' => 'Tailor',
                'recivedPayment' => 0,
                'remainingBalance' => 1500,
            ]);
        }

        $this->actingAs($owner)->post(route('admin.DirectPayment'), [
            'customer_id' => $secondOrder->customerId,
            'order_id' => $secondOrder->id,
            'DirectPayment' => 500,
            'comment' => 'Order-specific payment',
        ])->assertRedirect('/admin/Customers');

        $this->assertDatabaseHas('transactions', [
            'customerId' => $secondOrder->customerId,
            'orderId' => $secondOrder->id,
            'Order_type' => 'Payment',
            'recivedPayment' => 500,
            'remainingBalance' => -500,
        ]);

        $orders = $this->actingAs($owner)
            ->getJson(route('admin.getCustomer', ['id' => $secondOrder->customerId]))
            ->assertOk()
            ->json();
        $firstRow = collect($orders)->firstWhere('orderId', $firstOrder->id);
        $secondRow = collect($orders)->firstWhere('orderId', $secondOrder->id);

        $this->assertSame(1500.0, (float) $firstRow['remainingAmount']);
        $this->assertSame('unpaid', $firstRow['paymentStatus']['key']);
        $this->assertSame(500.0, (float) $secondRow['paidAmount']);
        $this->assertSame(1000.0, (float) $secondRow['remainingAmount']);
        $this->assertSame('partial', $secondRow['paymentStatus']['key']);

        $this->actingAs($owner)
            ->get(route('admin.order-print', $secondOrder))
            ->assertOk()
            ->assertViewHas('previousBalance', 1500.0)
            ->assertViewHas('orderBalance', 1000.0)
            ->assertViewHas('latestBalance', 2500.0);
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
