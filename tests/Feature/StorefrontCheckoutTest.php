<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Cloth;
use App\Models\ClothBrand;
use App\Models\ClothColor;
use App\Models\ClothType;
use App\Models\Customers;
use App\Models\Storefront;
use App\Models\StorefrontCart;
use App\Models\StorefrontClothingListing;
use App\Models\StorefrontOrder;
use App\Models\StorefrontOrderRefund;
use App\Models\StorefrontOrderReturn;
use App\Models\Transaction;
use App\Models\User;
use App\Notifications\NewStorefrontOrderNotification;
use App\Services\FinancialReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StorefrontCheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkout_atomically_deducts_stock_and_posts_one_unified_sale_charge(): void
    {
        Notification::fake();
        [$owner, $storefront, $listing, $color, $customer] = $this->catalog();
        $this->reservedLinkedCart($storefront, $listing, $color, $customer, 2);

        $response = $this->post(route('storefront.checkout.store', $storefront), [
            'fulfillment_method' => 'pickup',
            'customer_note' => 'شام پانچ بجے وصول کروں گا',
        ]);
        $order = StorefrontOrder::firstOrFail();

        $response->assertRedirect(route('storefront.orders.show', [$storefront, $order->reference]));
        $this->assertSame(8.0, (float) $color->fresh()->length);
        $this->assertSame('2900.00', $order->subtotal);
        $this->assertSame($customer->id, $order->customer_id);
        $this->assertSame('pickup', $order->fulfillment_method);
        $this->assertDatabaseHas('storefront_order_items', [
            'storefront_order_id' => $order->id,
            'quantity' => 2,
            'line_total' => 2900,
            'cost_total' => 2000,
        ]);
        $this->assertDatabaseHas('inventory_movements', [
            'movement_type' => 'storefront_order',
            'quantity' => -2,
        ]);
        $this->assertDatabaseHas('transactions', [
            'id' => $order->transaction_id,
            'customerId' => $customer->id,
            'userId' => $owner->id,
            'Order_type' => 'Sale',
            'remainingBalance' => 2900,
            'recivedPayment' => 0,
        ]);
        $this->assertNotNull(StorefrontCart::firstOrFail()->checked_out_at);
        $this->assertDatabaseCount('storefront_cart_items', 0);
        $this->assertDatabaseCount('online_orders', 0);
        $this->get(route('storefront.orders.show', [$storefront, $order->reference]))
            ->assertSee('<meta name="robots" content="noindex,nofollow">', false)
            ->assertDontSee('application/ld+json', false)
            ->assertOk()
            ->assertSeeText($customer->name)
            ->assertSeeText('2,900.00 روپے')
            ->assertSeeText('شام پانچ بجے وصول کروں گا');
        Notification::assertSentTo($owner, NewStorefrontOrderNotification::class);
    }

    public function test_checkout_requires_a_linked_customer_and_active_reservation(): void
    {
        [, $storefront, $listing, $color] = $this->catalog();
        $this->post(route('storefront.cart.store', [$storefront, $listing]), [
            'cloth_color_id' => $color->id,
            'quantity' => 2,
        ]);

        $this->post(route('storefront.checkout.store', $storefront), [
            'fulfillment_method' => 'pickup',
        ])->assertSessionHasErrors('checkout');

        $this->assertSame(10.0, (float) $color->fresh()->length);
        $this->assertDatabaseCount('storefront_orders', 0);
        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_checkout_rolls_back_when_physical_stock_changed_after_reservation(): void
    {
        [, $storefront, $listing, $color, $customer] = $this->catalog();
        $this->reservedLinkedCart($storefront, $listing, $color, $customer, 8);
        $color->update(['length' => 7]);

        $this->post(route('storefront.checkout.store', $storefront), [
            'fulfillment_method' => 'pickup',
        ])->assertSessionHasErrors('checkout');

        $this->assertSame(7.0, (float) $color->fresh()->length);
        $this->assertDatabaseCount('storefront_orders', 0);
        $this->assertDatabaseCount('storefront_order_items', 0);
        $this->assertDatabaseCount('inventory_movements', 0);
        $this->assertDatabaseCount('transactions', 0);
        $this->assertNull(StorefrontCart::firstOrFail()->checked_out_at);
    }

    public function test_checked_out_cart_cannot_create_a_duplicate_order(): void
    {
        [, $storefront, $listing, $color, $customer] = $this->catalog();
        $this->reservedLinkedCart($storefront, $listing, $color, $customer, 1);
        $this->post(route('storefront.checkout.store', $storefront), ['fulfillment_method' => 'pickup'])
            ->assertRedirect();

        $this->post(route('storefront.checkout.store', $storefront), ['fulfillment_method' => 'pickup'])
            ->assertSessionHasErrors('checkout');

        $this->assertDatabaseCount('storefront_orders', 1);
        $this->assertDatabaseCount('transactions', 1);
        $this->assertSame(9.0, (float) $color->fresh()->length);
    }

    public function test_tracking_details_require_same_session_or_customer_pin(): void
    {
        [, $storefront, $listing, $color, $customer] = $this->catalog();
        $this->reservedLinkedCart($storefront, $listing, $color, $customer, 1);
        $this->post(route('storefront.checkout.store', $storefront), ['fulfillment_method' => 'pickup']);
        $order = StorefrontOrder::firstOrFail();
        $this->app['session']->flush();

        $unauthorizedResponse = $this->get(route('storefront.orders.show', [$storefront, $order->reference]))
            ->assertSee('<meta name="robots" content="noindex,nofollow">', false)
            ->assertDontSee('application/ld+json', false)
            ->assertSeeText('آرڈر کی محفوظ تفصیل')
            ->assertDontSeeText('نیلا پریمیم کپڑا')
            ->assertDontSeeText('ادائیگی کا خلاصہ');
        preg_match('/<head>(.*?)<\/head>/s', $unauthorizedResponse->getContent(), $head);
        $this->assertStringNotContainsString($order->reference, $head[1]);
        $this->assertStringNotContainsString($customer->name, $head[1]);
        $this->post(route('storefront.orders.authenticate', [$storefront, $order->reference]), [
            'phone' => $customer->phone_number1,
            'pin' => '111111',
        ])->assertSessionHasErrors('phone');
        $this->post(route('storefront.orders.authenticate', [$storefront, $order->reference]), [
            'phone' => $customer->phone_number1,
            'pin' => '482913',
        ])->assertRedirect(route('storefront.orders.show', [$storefront, $order->reference]));
        $this->get(route('storefront.orders.show', [$storefront, $order->reference]))
            ->assertSeeText('نیلا پریمیم کپڑا')
            ->assertSeeText($customer->name);
    }

    public function test_client_cancellation_restores_stock_and_reverses_customer_charge_once(): void
    {
        [$owner, $storefront, $listing, $color, $customer] = $this->catalog();
        $this->reservedLinkedCart($storefront, $listing, $color, $customer, 2);
        $this->post(route('storefront.checkout.store', $storefront), ['fulfillment_method' => 'pickup']);
        $order = StorefrontOrder::firstOrFail();

        $this->actingAs($owner)->patch(route('admin.storefront.orders.update', $order), [
            'status' => 'cancelled',
        ])->assertRedirect(route('admin.storefront.orders.index'));

        $this->assertSame(StorefrontOrder::STATUS_CANCELLED, $order->fresh()->status);
        $this->assertSame(10.0, (float) $color->fresh()->length);
        $this->assertSame(0.0, (float) Transaction::where('customerId', $customer->id)->sum('remainingBalance'));
        $this->assertDatabaseHas('inventory_movements', [
            'movement_type' => 'storefront_cancellation',
            'quantity' => 2,
        ]);

        $this->actingAs($owner)->patch(route('admin.storefront.orders.update', $order), [
            'status' => 'cancelled',
        ])->assertSessionHasErrors('status');
        $this->assertSame(10.0, (float) $color->fresh()->length);
        $this->assertDatabaseCount('transactions', 2);
    }

    public function test_client_completion_changes_status_without_moving_stock_or_balance_again(): void
    {
        [$owner, $storefront, $listing, $color, $customer] = $this->catalog();
        $this->reservedLinkedCart($storefront, $listing, $color, $customer, 1);
        $this->post(route('storefront.checkout.store', $storefront), ['fulfillment_method' => 'pickup']);
        $order = StorefrontOrder::firstOrFail();

        $this->actingAs($owner)->patch(route('admin.storefront.orders.update', $order), [
            'status' => 'complete',
        ])->assertRedirect(route('admin.storefront.orders.index'));

        $this->assertSame(StorefrontOrder::STATUS_COMPLETE, $order->fresh()->status);
        $this->assertNotNull($order->fresh()->completed_at);
        $this->assertSame(9.0, (float) $color->fresh()->length);
        $this->assertSame(1450.0, (float) Transaction::where('customerId', $customer->id)->sum('remainingBalance'));
        $this->assertDatabaseCount('inventory_movements', 1);
        $this->assertDatabaseCount('transactions', 1);

        $this->actingAs($owner)->patch(route('admin.storefront.orders.update', $order), [
            'status' => 'complete',
        ])->assertSessionHasErrors('status');
        $this->assertDatabaseCount('inventory_movements', 1);
    }

    public function test_delivery_requires_address_and_financial_report_includes_storefront_margin(): void
    {
        [$owner, $storefront, $listing, $color, $customer] = $this->catalog();
        $this->reservedLinkedCart($storefront, $listing, $color, $customer, 2);
        $this->post(route('storefront.checkout.store', $storefront), [
            'fulfillment_method' => 'delivery',
        ])->assertSessionHasErrors('delivery_address');
        $this->post(route('storefront.checkout.store', $storefront), [
            'fulfillment_method' => 'delivery',
            'delivery_address' => 'مکان 12، گلی 4، سیٹلائٹ ٹاؤن، راولپنڈی',
        ])->assertRedirect();

        $report = app(FinancialReportService::class)->build($owner->id, now()->startOfDay(), now()->endOfDay());
        $this->assertSame(2900.0, (float) $report['summary']['total_revenue']);
        $this->assertSame(900.0, (float) $report['summary']['gross_profit']);
        $this->assertSame(2900.0, (float) $report['summary']['receivables']);
    }

    public function test_payment_preferences_are_recorded_without_falsely_marking_money_as_received(): void
    {
        [, $storefront, $listing, $color, $customer] = $this->catalog();
        $this->reservedLinkedCart($storefront, $listing, $color, $customer, 1);

        $this->post(route('storefront.checkout.store', $storefront), [
            'fulfillment_method' => 'pickup',
            'payment_method' => StorefrontOrder::PAYMENT_EASYPAISA,
        ])->assertSessionHasErrors(['payment_sender_phone', 'payment_reference']);

        $this->post(route('storefront.checkout.store', $storefront), [
            'fulfillment_method' => 'pickup',
            'payment_method' => StorefrontOrder::PAYMENT_EASYPAISA,
            'payment_sender_phone' => '03009998888',
            'payment_reference' => 'EP-QA-1001',
        ])->assertRedirect();

        $order = StorefrontOrder::firstOrFail();
        $this->assertSame(StorefrontOrder::PAYMENT_EASYPAISA, $order->payment_method);
        $this->assertSame('03009998888', $order->payment_sender_phone);
        $this->assertSame('EP-QA-1001', $order->payment_reference);
        $this->assertSame(StorefrontOrder::VERIFICATION_PENDING, $order->payment_verification_status);
        $this->assertSame('0.00', $order->paid_amount);
        $this->assertSame($order->subtotal, $order->balance_amount);
        $this->assertDatabaseHas('transactions', [
            'id' => $order->transaction_id,
            'recivedPayment' => 0,
            'remainingBalance' => 1450,
        ]);
        $this->get(route('storefront.orders.show', [$storefront, $order->reference]))
            ->assertOk()
            ->assertSeeText('ادائیگی کا خلاصہ')
            ->assertSeeText('دستی تصدیق زیرِ انتظار')
            ->assertSeeText('اس آرڈر کے ساتھ تصدیق شدہ رقم')
            ->assertSeeText('0.00 روپے')
            ->assertSeeText('اس آرڈر پر باقی رقم')
            ->assertSeeText('1,450.00 روپے')
            ->assertSeeText('EP-QA-1001')
            ->assertSeeText('دکان کی طرف سے ادائیگی کے حوالے کی تصدیق کا انتظار ہے۔');
    }

    public function test_client_verifies_easypaisa_once_before_completing_order(): void
    {
        [$owner, $storefront, $listing, $color, $customer] = $this->catalog();
        $this->reservedLinkedCart($storefront, $listing, $color, $customer, 1);
        $this->post(route('storefront.checkout.store', $storefront), [
            'fulfillment_method' => 'pickup',
            'payment_method' => StorefrontOrder::PAYMENT_EASYPAISA,
            'payment_sender_phone' => '03009998888',
            'payment_reference' => 'EP-VERIFY-1001',
        ])->assertRedirect();
        $order = StorefrontOrder::firstOrFail();

        $this->actingAs($owner)->patch(route('admin.storefront.orders.update', $order), [
            'status' => StorefrontOrder::STATUS_COMPLETE,
        ])->assertSessionHasErrors('status');

        $this->actingAs($owner)->patch(route('admin.storefront.orders.payment-verification', $order), [
            'decision' => StorefrontOrder::VERIFICATION_VERIFIED,
            'payment_verification_notes' => 'Matched merchant statement',
        ])->assertRedirect(route('admin.storefront.orders.index'));

        $order->refresh();
        $this->assertSame(StorefrontOrder::VERIFICATION_VERIFIED, $order->payment_verification_status);
        $this->assertSame($owner->id, $order->payment_verified_by_user_id);
        $this->assertSame('1450.00', $order->paid_amount);
        $this->assertSame('0.00', $order->balance_amount);
        $this->assertNotNull($order->payment_verified_at);
        $this->assertDatabaseHas('transactions', [
            'id' => $order->transaction_id,
            'recivedPayment' => 1450,
            'remainingBalance' => 0,
        ]);
        $this->get(route('storefront.orders.show', [$storefront, $order->reference]))
            ->assertOk()
            ->assertSeeText('ادائیگی تصدیق شدہ')
            ->assertSeeText('1,450.00 روپے')
            ->assertSeeText('دکان نے اس آرڈر کے ساتھ ادائیگی کی تصدیق کر دی۔')
            ->assertDontSeeText('Matched merchant statement');

        $this->actingAs($owner)->patch(route('admin.storefront.orders.payment-verification', $order), [
            'decision' => StorefrontOrder::VERIFICATION_VERIFIED,
        ])->assertSessionHasErrors('payment_verification');
        $this->actingAs($owner)->patch(route('admin.storefront.orders.update', $order), [
            'status' => StorefrontOrder::STATUS_COMPLETE,
        ])->assertRedirect(route('admin.storefront.orders.index'));
        $this->assertSame(StorefrontOrder::STATUS_COMPLETE, $order->fresh()->status);
    }

    public function test_paid_order_is_refunded_and_cancelled_once_with_inventory_and_cash_audit(): void
    {
        [$owner, $storefront, $listing, $color, $customer] = $this->catalog();
        $this->reservedLinkedCart($storefront, $listing, $color, $customer, 1);
        $this->post(route('storefront.checkout.store', $storefront), [
            'fulfillment_method' => 'pickup',
            'payment_method' => StorefrontOrder::PAYMENT_EASYPAISA,
            'payment_sender_phone' => '03009998888',
            'payment_reference' => 'EP-PAID-1001',
        ])->assertRedirect();
        $order = StorefrontOrder::firstOrFail();

        $this->actingAs($owner)->patch(route('admin.storefront.orders.payment-verification', $order), [
            'decision' => StorefrontOrder::VERIFICATION_VERIFIED,
        ])->assertRedirect(route('admin.storefront.orders.index'));
        $this->actingAs($owner)->get(route('admin.storefront.orders.index'))
            ->assertOk()
            ->assertSeeText('مکمل رقم واپس کر کے آرڈر منسوخ کریں')
            ->assertSeeText('واپس کی جانے والی رقم: 1,450.00 روپے')
            ->assertSeeText('واپسی کا بیرونی حوالہ');

        $this->actingAs($owner)->patch(route('admin.storefront.orders.update', $order), [
            'status' => StorefrontOrder::STATUS_CANCELLED,
        ])->assertSessionHasErrors('refund_method');
        $this->actingAs($owner)->patch(route('admin.storefront.orders.update', $order), [
            'status' => StorefrontOrder::STATUS_CANCELLED,
            'refund_method' => StorefrontOrderRefund::METHOD_EASYPAISA,
        ])->assertSessionHasErrors('refund_reference');

        $this->actingAs($owner)->patch(route('admin.storefront.orders.update', $order), [
            'status' => StorefrontOrder::STATUS_CANCELLED,
            'refund_method' => StorefrontOrderRefund::METHOD_EASYPAISA,
            'refund_reference' => 'EP-REFUND-1001',
            'refund_notes' => 'Internal refund note',
        ])->assertRedirect(route('admin.storefront.orders.index'));

        $order->refresh();
        $refund = $order->refunds()->firstOrFail();
        $this->assertSame(StorefrontOrder::STATUS_CANCELLED, $order->status);
        $this->assertSame('1450.00', $order->paid_amount);
        $this->assertSame('0.00', $order->balance_amount);
        $this->assertSame('1450.00', $refund->amount);
        $this->assertSame(StorefrontOrderRefund::METHOD_EASYPAISA, $refund->method);
        $this->assertSame('EP-REFUND-1001', $refund->external_reference);
        $this->assertSame($owner->id, $refund->processed_by_user_id);
        $this->assertSame(10.0, (float) $color->fresh()->length);
        $this->assertSame(1, DB::table('inventory_movements')
            ->where('movement_type', 'storefront_cancellation')->count());
        $this->assertDatabaseHas('transactions', [
            'customerId' => $customer->id,
            'recivedPayment' => -1450,
            'remainingBalance' => 0,
        ]);
        $this->assertSame(0.0, (float) Transaction::where('customerId', $customer->id)->sum('recivedPayment'));
        $this->actingAs($owner)->get(route('admin.storefront.orders.index'))
            ->assertOk()
            ->assertSeeText('رقم واپس کی گئی:')
            ->assertSeeText($refund->reference)
            ->assertSeeText('EP-REFUND-1001')
            ->assertDontSeeText('Internal refund note');

        $this->get(route('storefront.orders.show', [$storefront, $order->reference]))
            ->assertOk()
            ->assertSeeText('ادائیگی واپس کر دی گئی')
            ->assertSeeText('گاہک کو واپس کی گئی رقم')
            ->assertSeeText('1,450.00 روپے')
            ->assertSeeText($refund->reference)
            ->assertSeeText('EP-REFUND-1001')
            ->assertDontSeeText('Internal refund note');

        $this->actingAs($owner)->patch(route('admin.storefront.orders.update', $order), [
            'status' => StorefrontOrder::STATUS_CANCELLED,
            'refund_method' => StorefrontOrderRefund::METHOD_CASH,
        ])->assertSessionHasErrors('status');
        $this->assertDatabaseCount('storefront_order_refunds', 1);
        $this->assertSame(10.0, (float) $color->fresh()->length);
        $this->assertSame(2, Transaction::where('customerId', $customer->id)->count());
    }

    public function test_rejecting_easypaisa_requires_notes_and_does_not_post_payment(): void
    {
        [$owner, $storefront, $listing, $color, $customer] = $this->catalog();
        $this->reservedLinkedCart($storefront, $listing, $color, $customer, 1);
        $this->post(route('storefront.checkout.store', $storefront), [
            'fulfillment_method' => 'pickup',
            'payment_method' => StorefrontOrder::PAYMENT_EASYPAISA,
            'payment_sender_phone' => '03009998888',
            'payment_reference' => 'EP-REJECT-1001',
        ]);
        $order = StorefrontOrder::firstOrFail();

        $this->actingAs($owner)->patch(route('admin.storefront.orders.payment-verification', $order), [
            'decision' => StorefrontOrder::VERIFICATION_REJECTED,
        ])->assertSessionHasErrors('payment_verification_notes');
        $this->actingAs($owner)->patch(route('admin.storefront.orders.payment-verification', $order), [
            'decision' => StorefrontOrder::VERIFICATION_REJECTED,
            'payment_verification_notes' => 'Reference not found in statement',
        ])->assertRedirect(route('admin.storefront.orders.index'));

        $order->refresh();
        $this->assertSame(StorefrontOrder::VERIFICATION_REJECTED, $order->payment_verification_status);
        $this->assertSame('0.00', $order->paid_amount);
        $this->assertDatabaseHas('transactions', [
            'id' => $order->transaction_id,
            'recivedPayment' => 0,
            'remainingBalance' => 1450,
        ]);
        $this->get(route('storefront.orders.show', [$storefront, $order->reference]))
            ->assertOk()
            ->assertSeeText('ادائیگی کا حوالہ مسترد')
            ->assertSeeText('دکان نے جمع کیا گیا ادائیگی کا حوالہ مسترد کر دیا۔')
            ->assertDontSeeText('Reference not found in statement');

        $this->actingAs($owner)->patch(route('admin.storefront.orders.update', $order), [
            'status' => StorefrontOrder::STATUS_CANCELLED,
        ])->assertRedirect(route('admin.storefront.orders.index'));
        $this->actingAs($owner)->patch(route('admin.storefront.orders.payment-verification', $order), [
            'decision' => StorefrontOrder::VERIFICATION_VERIFIED,
        ])->assertSessionHasErrors('payment_verification');
        $this->assertSame(StorefrontOrder::STATUS_CANCELLED, $order->fresh()->status);
    }

    public function test_unpaid_partial_return_restores_resellable_stock_and_reduces_unified_balance(): void
    {
        [$owner, $storefront, $listing, $color, $customer] = $this->catalog();
        $this->reservedLinkedCart($storefront, $listing, $color, $customer, 2);
        $this->post(route('storefront.checkout.store', $storefront), [
            'fulfillment_method' => 'pickup',
        ])->assertRedirect();
        $order = StorefrontOrder::firstOrFail();
        $item = $order->items()->firstOrFail();

        $this->actingAs($owner)->post(route('admin.storefront.orders.returns.store', $order), [
            'order_item_id' => $item->id,
            'return_type' => StorefrontOrderReturn::TYPE_REFUND,
            'quantity' => 0.5,
            'restock' => 1,
            'return_notes' => 'Customer returned sealed fabric',
        ])->assertRedirect(route('admin.storefront.orders.index'));

        $return = $order->returns()->firstOrFail();
        $this->assertSame('725.00', $return->refund_amount);
        $this->assertNull($return->refund_method);
        $this->assertSame('2175.00', $order->fresh()->balance_amount);
        $this->assertSame(8.5, (float) $color->fresh()->length);
        $this->assertSame(2175.0, (float) Transaction::where('customerId', $customer->id)->sum('remainingBalance'));
        $this->assertDatabaseHas('inventory_movements', [
            'movement_type' => 'storefront_return',
            'quantity' => 0.5,
        ]);
        $report = app(FinancialReportService::class)->build($owner->id, now()->startOfDay(), now()->endOfDay());
        $this->assertSame(2175.0, (float) $report['summary']['total_revenue']);
        $this->assertSame(675.0, (float) $report['summary']['gross_profit']);

        $this->actingAs($owner)->post(route('admin.storefront.orders.returns.store', $order), [
            'order_item_id' => $item->id,
            'return_type' => StorefrontOrderReturn::TYPE_REFUND,
            'quantity' => 1.51,
        ])->assertSessionHasErrors('quantity');
        $this->actingAs($owner)->patch(route('admin.storefront.orders.update', $order), [
            'status' => StorefrontOrder::STATUS_CANCELLED,
        ])->assertSessionHasErrors('status');
        $this->assertDatabaseCount('storefront_order_returns', 1);
        $this->assertSame(8.5, (float) $color->fresh()->length);

        $this->get(route('storefront.orders.show', [$storefront, $order->reference]))
            ->assertOk()
            ->assertSeeText('آرڈر کا بقایا ایڈجسٹ کر دیا گیا')
            ->assertSeeText('725.00 روپے')
            ->assertSeeText($return->reference)
            ->assertSeeText('گاہک کے بقایا میں کمی')
            ->assertDontSeeText('Customer returned sealed fabric');
    }

    public function test_paid_partial_return_requires_refund_proof_and_reverses_received_cash(): void
    {
        [$owner, $storefront, $listing, $color, $customer] = $this->catalog();
        $this->reservedLinkedCart($storefront, $listing, $color, $customer, 2);
        $this->post(route('storefront.checkout.store', $storefront), [
            'fulfillment_method' => 'pickup',
            'payment_method' => StorefrontOrder::PAYMENT_EASYPAISA,
            'payment_sender_phone' => '03009998888',
            'payment_reference' => 'EP-PARTIAL-1001',
        ]);
        $order = StorefrontOrder::firstOrFail();
        $item = $order->items()->firstOrFail();
        $this->actingAs($owner)->post(route('admin.storefront.orders.returns.store', $order), [
            'order_item_id' => $item->id,
            'return_type' => StorefrontOrderReturn::TYPE_REFUND,
            'quantity' => 0.5,
            'refund_method' => StorefrontOrderRefund::METHOD_CASH,
        ])->assertSessionHasErrors('return_type');
        $this->actingAs($owner)->patch(route('admin.storefront.orders.payment-verification', $order), [
            'decision' => StorefrontOrder::VERIFICATION_VERIFIED,
        ])->assertRedirect();

        $this->actingAs($owner)->post(route('admin.storefront.orders.returns.store', $order), [
            'order_item_id' => $item->id,
            'return_type' => StorefrontOrderReturn::TYPE_REFUND,
            'quantity' => 0.5,
        ])->assertSessionHasErrors('refund_method');
        $this->actingAs($owner)->post(route('admin.storefront.orders.returns.store', $order), [
            'order_item_id' => $item->id,
            'return_type' => StorefrontOrderReturn::TYPE_REFUND,
            'quantity' => 0.5,
            'refund_method' => StorefrontOrderRefund::METHOD_EASYPAISA,
        ])->assertSessionHasErrors('refund_reference');
        $this->actingAs($owner)->post(route('admin.storefront.orders.returns.store', $order), [
            'order_item_id' => $item->id,
            'return_type' => StorefrontOrderReturn::TYPE_REFUND,
            'quantity' => 0.5,
            'restock' => 0,
            'refund_method' => StorefrontOrderRefund::METHOD_EASYPAISA,
            'refund_reference' => 'EP-PARTIAL-REFUND-1001',
        ])->assertRedirect();

        $return = $order->returns()->firstOrFail();
        $this->assertSame('725.00', $return->refund_amount);
        $this->assertSame(StorefrontOrderRefund::METHOD_EASYPAISA, $return->refund_method);
        $this->assertSame('0.00', $order->fresh()->balance_amount);
        $this->assertSame(8.0, (float) $color->fresh()->length);
        $this->assertSame(2175.0, (float) Transaction::where('customerId', $customer->id)->sum('recivedPayment'));
        $this->assertDatabaseMissing('inventory_movements', ['movement_type' => 'storefront_return']);
        $this->get(route('storefront.orders.show', [$storefront, $order->reference]))
            ->assertOk()
            ->assertSeeText('ادائیگی واپس کر دی گئی')
            ->assertSeeText('EP-PARTIAL-REFUND-1001');
    }

    public function test_same_fabric_colour_exchange_is_tenant_scoped_and_moves_stock_once(): void
    {
        [$owner, $storefront, $listing, $color, $customer, $replacementColor] = $this->catalog();
        $this->reservedLinkedCart($storefront, $listing, $color, $customer, 2);
        $this->post(route('storefront.checkout.store', $storefront), ['fulfillment_method' => 'pickup']);
        $order = StorefrontOrder::firstOrFail();
        $item = $order->items()->firstOrFail();
        [$otherOwner, , , , , $otherColor] = $this->catalog();

        $this->actingAs($otherOwner)->post(route('admin.storefront.orders.returns.store', $order), [
            'order_item_id' => $item->id,
            'return_type' => StorefrontOrderReturn::TYPE_EXCHANGE,
            'quantity' => 1,
            'replacement_cloth_color_id' => $otherColor->id,
        ])->assertNotFound();
        $this->actingAs($owner)->post(route('admin.storefront.orders.returns.store', $order), [
            'order_item_id' => $item->id,
            'return_type' => StorefrontOrderReturn::TYPE_EXCHANGE,
            'quantity' => 1,
            'restock' => 1,
            'replacement_cloth_color_id' => $otherColor->id,
        ])->assertSessionHasErrors('replacement_cloth_color_id');
        $this->actingAs($owner)->post(route('admin.storefront.orders.returns.store', $order), [
            'order_item_id' => $item->id,
            'return_type' => StorefrontOrderReturn::TYPE_EXCHANGE,
            'quantity' => 1,
            'restock' => 1,
            'replacement_cloth_color_id' => $replacementColor->id,
        ])->assertRedirect();

        $return = $order->returns()->firstOrFail();
        $this->assertSame(StorefrontOrderReturn::TYPE_EXCHANGE, $return->type);
        $this->assertSame(9.0, (float) $color->fresh()->length);
        $this->assertSame(7.0, (float) $replacementColor->fresh()->length);
        $this->assertDatabaseHas('inventory_movements', ['movement_type' => 'storefront_return', 'quantity' => 1]);
        $this->assertDatabaseHas('inventory_movements', ['movement_type' => 'storefront_exchange_issue', 'quantity' => -1]);
        $this->assertDatabaseCount('transactions', 1);
        $this->actingAs($owner)->get(route('admin.storefront.orders.index', ['search' => $order->reference]))
            ->assertOk()
            ->assertSeeText('جزوی واپسی اور تبدیلی کی تاریخ')
            ->assertSeeText('اس آرڈر پر جزوی واپسی یا تبدیلی موجود ہے، اس لیے مکمل منسوخی دستیاب نہیں۔');

        $this->actingAs($owner)->post(route('admin.storefront.orders.returns.store', $order), [
            'order_item_id' => $item->id,
            'return_type' => StorefrontOrderReturn::TYPE_EXCHANGE,
            'quantity' => 1.01,
            'replacement_cloth_color_id' => $replacementColor->id,
        ])->assertSessionHasErrors('quantity');
        $this->assertDatabaseCount('storefront_order_returns', 1);
        $this->assertSame(9.0, (float) $color->fresh()->length);
        $this->assertSame(7.0, (float) $replacementColor->fresh()->length);
        $this->get(route('storefront.orders.show', [$storefront, $order->reference]))
            ->assertOk()
            ->assertSeeText('سرمئی')
            ->assertSeeText($return->reference);
    }

    public function test_cash_on_delivery_requires_delivery_fulfillment(): void
    {
        [, $storefront, $listing, $color, $customer] = $this->catalog();
        $this->reservedLinkedCart($storefront, $listing, $color, $customer, 1);

        $this->post(route('storefront.checkout.store', $storefront), [
            'fulfillment_method' => 'pickup',
            'payment_method' => StorefrontOrder::PAYMENT_COD,
        ])->assertSessionHasErrors('payment_method');
        $this->assertDatabaseCount('storefront_orders', 0);

        $this->post(route('storefront.checkout.store', $storefront), [
            'fulfillment_method' => 'delivery',
            'delivery_address' => 'مکان 12، سیٹلائٹ ٹاؤن، راولپنڈی',
            'payment_method' => StorefrontOrder::PAYMENT_COD,
        ])->assertRedirect();

        $this->assertSame(StorefrontOrder::PAYMENT_COD, StorefrontOrder::firstOrFail()->payment_method);
    }

    public function test_client_can_keep_public_catalogue_without_accepting_online_orders(): void
    {
        [, $storefront, $listing, $color] = $this->catalog();
        $storefront->update(['online_ordering_enabled' => false]);

        $this->get(route('storefront.clothing.show', [$storefront, $listing]))
            ->assertOk()
            ->assertSeeText('یہ دکان فی الحال اپنی عوامی ویب سائٹ کو صرف فہرست کے طور پر استعمال کر رہی ہے۔')
            ->assertDontSee(route('storefront.cart.store', [$storefront, $listing]), false);
        $this->get(route('storefront.cart.show', $storefront))->assertNotFound();
        $this->post(route('storefront.cart.store', [$storefront, $listing]), [
            'cloth_color_id' => $color->id,
            'quantity' => 1,
        ])->assertNotFound();

        $this->assertDatabaseCount('storefront_carts', 0);
        $this->assertDatabaseCount('storefront_orders', 0);
    }

    public function test_client_selected_payment_methods_are_shown_and_enforced_server_side(): void
    {
        [, $storefront, $listing, $color, $customer] = $this->catalog();
        $storefront->update([
            'unpaid_orders_enabled' => false,
            'cod_enabled' => false,
            'easypaisa_enabled' => true,
        ]);
        $this->reservedLinkedCart($storefront, $listing, $color, $customer, 1);

        $this->get(route('storefront.cart.show', $storefront))
            ->assertOk()
            ->assertSeeText('ایزی پیسہ')
            ->assertDontSeeText('کیش آن ڈیلیوری')
            ->assertDontSeeText('ابھی ادائیگی نہیں');
        $this->post(route('storefront.checkout.store', $storefront), [
            'fulfillment_method' => 'pickup',
            'payment_method' => StorefrontOrder::PAYMENT_UNPAID,
        ])->assertSessionHasErrors('payment_method');
        $this->assertDatabaseCount('storefront_orders', 0);

        $this->post(route('storefront.checkout.store', $storefront), [
            'fulfillment_method' => 'pickup',
            'payment_method' => StorefrontOrder::PAYMENT_EASYPAISA,
            'payment_sender_phone' => '03007771111',
            'payment_reference' => 'EP-CONTROL-1001',
        ])->assertRedirect();
        $this->assertSame(
            StorefrontOrder::PAYMENT_EASYPAISA,
            StorefrontOrder::firstOrFail()->payment_method
        );
    }

    public function test_jazzcash_checkout_is_pending_until_client_verifies_it(): void
    {
        [$owner, $storefront, $listing, $color, $customer] = $this->catalog();
        $storefront->update([
            'unpaid_orders_enabled' => false,
            'cod_enabled' => false,
            'easypaisa_enabled' => false,
            'jazzcash_enabled' => true,
            'jazzcash_account_title' => 'Checkout Business',
            'jazzcash_account_number' => '03001234567',
        ]);
        $this->reservedLinkedCart($storefront, $listing, $color, $customer, 1);

        $this->get(route('storefront.cart.show', $storefront))
            ->assertOk()
            ->assertSeeText('جاز کیش')
            ->assertSeeText('Checkout Business')
            ->assertSeeText('03001234567');
        $this->post(route('storefront.checkout.store', $storefront), [
            'fulfillment_method' => 'pickup',
            'payment_method' => StorefrontOrder::PAYMENT_JAZZCASH,
            'payment_sender_phone' => '03009998888',
            'payment_reference' => 'JC-20260724-1001',
        ])->assertRedirect();

        $order = StorefrontOrder::firstOrFail();
        $this->assertSame(StorefrontOrder::PAYMENT_JAZZCASH, $order->payment_method);
        $this->assertSame(StorefrontOrder::VERIFICATION_PENDING, $order->payment_verification_status);
        $this->assertSame('0.00', $order->paid_amount);

        $this->actingAs($owner)->patch(route('admin.storefront.orders.payment-verification', $order), [
            'decision' => StorefrontOrder::VERIFICATION_VERIFIED,
            'payment_verification_notes' => 'JazzCash merchant wallet matched',
        ])->assertRedirect(route('admin.storefront.orders.index'));

        $order->refresh();
        $this->assertSame(StorefrontOrder::VERIFICATION_VERIFIED, $order->payment_verification_status);
        $this->assertSame($order->subtotal, $order->paid_amount);
        $this->assertSame($owner->id, $order->payment_verified_by_user_id);
        $this->assertDatabaseHas('transactions', [
            'id' => $order->transaction_id,
            'customerId' => $customer->id,
            'Order_type' => 'Sale',
            'recivedPayment' => 1450,
            'remainingBalance' => 0,
        ]);
    }

    public function test_bank_transfer_checkout_requires_reference_but_not_sender_mobile(): void
    {
        [, $storefront, $listing, $color, $customer] = $this->catalog();
        $storefront->update([
            'unpaid_orders_enabled' => false,
            'cod_enabled' => false,
            'easypaisa_enabled' => false,
            'bank_transfer_enabled' => true,
            'bank_name' => 'Meezan Bank',
            'bank_account_title' => 'Checkout Business',
            'bank_iban' => 'PK36MEZN0001234567890123',
        ]);
        $this->reservedLinkedCart($storefront, $listing, $color, $customer, 1);

        $this->post(route('storefront.checkout.store', $storefront), [
            'fulfillment_method' => 'pickup',
            'payment_method' => StorefrontOrder::PAYMENT_BANK_TRANSFER,
        ])->assertSessionHasErrors('payment_reference');
        $this->assertDatabaseCount('storefront_orders', 0);

        $this->post(route('storefront.checkout.store', $storefront), [
            'fulfillment_method' => 'pickup',
            'payment_method' => StorefrontOrder::PAYMENT_BANK_TRANSFER,
            'payment_reference' => 'MBL-20260724-8842',
        ])->assertRedirect();

        $order = StorefrontOrder::firstOrFail();
        $this->assertSame(StorefrontOrder::PAYMENT_BANK_TRANSFER, $order->payment_method);
        $this->assertNull($order->payment_sender_phone);
        $this->assertSame(StorefrontOrder::VERIFICATION_PENDING, $order->payment_verification_status);
    }

    public function test_payment_evidence_is_private_and_tenant_scoped(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        [$owner, $storefront, $listing, $color, $customer] = $this->catalog();
        $this->reservedLinkedCart($storefront, $listing, $color, $customer, 1);

        $this->post(route('storefront.checkout.store', $storefront), [
            'fulfillment_method' => 'pickup',
            'payment_method' => StorefrontOrder::PAYMENT_EASYPAISA,
            'payment_sender_phone' => '03009998888',
            'payment_reference' => 'EP-PRIVATE-1001',
            'payment_evidence' => UploadedFile::fake()->image('receipt.jpg', 800, 1200),
        ])->assertRedirect();

        $order = StorefrontOrder::firstOrFail();
        $this->assertNotNull($order->payment_evidence_path);
        $this->assertSame('receipt.jpg', $order->payment_evidence_original_name);
        $this->assertSame('image/jpeg', $order->payment_evidence_mime_type);
        $this->assertNotNull($order->payment_evidence_submitted_at);
        Storage::disk('local')->assertExists($order->payment_evidence_path);
        Storage::disk('public')->assertMissing($order->payment_evidence_path);

        $this->get(route('storefront.orders.show', [$storefront, $order->reference]))
            ->assertOk()
            ->assertDontSeeText('receipt.jpg')
            ->assertDontSee(route('admin.storefront.orders.payment-evidence', $order), false);
        $this->get(route('admin.storefront.orders.payment-evidence', $order))
            ->assertRedirect(route('login'));
        $this->actingAs($owner)
            ->get(route('admin.storefront.orders.payment-evidence', $order))
            ->assertOk()
            ->assertHeader('content-type', 'image/jpeg')
            ->assertHeader('cache-control', 'max-age=0, no-store, private');

        [$otherOwner] = $this->catalog();
        $this->actingAs($otherOwner)
            ->get(route('admin.storefront.orders.payment-evidence', $order))
            ->assertNotFound();
    }

    public function test_payment_evidence_is_rejected_for_non_manual_payment(): void
    {
        Storage::fake('local');
        [, $storefront, $listing, $color, $customer] = $this->catalog();
        $this->reservedLinkedCart($storefront, $listing, $color, $customer, 1);

        $this->post(route('storefront.checkout.store', $storefront), [
            'fulfillment_method' => 'pickup',
            'payment_method' => StorefrontOrder::PAYMENT_UNPAID,
            'payment_evidence' => UploadedFile::fake()->image('unrelated.jpg'),
        ])->assertSessionHasErrors('payment_evidence');

        $this->assertDatabaseCount('storefront_orders', 0);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_failed_checkout_removes_staged_private_payment_evidence(): void
    {
        Storage::fake('local');
        [, $storefront, $listing, $color, $customer] = $this->catalog();
        $this->reservedLinkedCart($storefront, $listing, $color, $customer, 2);
        $color->update(['length' => 1]);

        $this->post(route('storefront.checkout.store', $storefront), [
            'fulfillment_method' => 'pickup',
            'payment_method' => StorefrontOrder::PAYMENT_EASYPAISA,
            'payment_sender_phone' => '03009998888',
            'payment_reference' => 'EP-ROLLBACK-1',
            'payment_evidence' => UploadedFile::fake()->image('failed-receipt.jpg'),
        ])->assertSessionHasErrors('checkout');

        $this->assertDatabaseCount('storefront_orders', 0);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_payment_evidence_rejects_unsafe_file_types(): void
    {
        Storage::fake('local');
        [, $storefront, $listing, $color, $customer] = $this->catalog();
        $this->reservedLinkedCart($storefront, $listing, $color, $customer, 1);

        $this->post(route('storefront.checkout.store', $storefront), [
            'fulfillment_method' => 'pickup',
            'payment_method' => StorefrontOrder::PAYMENT_EASYPAISA,
            'payment_sender_phone' => '03009998888',
            'payment_reference' => 'EP-UNSAFE-1',
            'payment_evidence' => UploadedFile::fake()->create('receipt.svg', 10, 'image/svg+xml'),
        ])->assertSessionHasErrors('payment_evidence');

        $this->assertDatabaseCount('storefront_orders', 0);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_another_client_cannot_view_or_change_the_order(): void
    {
        [$owner, $storefront, $listing, $color, $customer] = $this->catalog();
        $this->reservedLinkedCart($storefront, $listing, $color, $customer, 1);
        $this->post(route('storefront.checkout.store', $storefront), ['fulfillment_method' => 'pickup']);
        $order = StorefrontOrder::firstOrFail();
        [$otherOwner] = $this->catalog();

        $this->actingAs($otherOwner)->get(route('admin.storefront.orders.index'))
            ->assertOk()
            ->assertDontSeeText($order->reference);
        $this->actingAs($otherOwner)->patch(route('admin.storefront.orders.update', $order), [
            'status' => 'complete',
        ])->assertNotFound();
        $this->assertSame(StorefrontOrder::STATUS_PENDING, $order->fresh()->status);
        $this->assertSame(9.0, (float) $color->fresh()->length);
        $this->assertSame($owner->id, $order->storefront->business->owner_user_id);
    }

    private function reservedLinkedCart(
        Storefront $storefront,
        StorefrontClothingListing $listing,
        ClothColor $color,
        Customers $customer,
        float $quantity
    ): void {
        $this->post(route('storefront.cart.store', [$storefront, $listing]), [
            'cloth_color_id' => $color->id,
            'quantity' => $quantity,
        ])->assertSessionHasNoErrors();
        $this->post(route('storefront.cart.customer.link', $storefront), [
            'phone' => $customer->phone_number1,
            'pin' => '482913',
        ])->assertSessionHasNoErrors();
    }

    private function catalog(): array
    {
        $role = Role::firstOrCreate(['name' => 'shop_owner', 'guard_name' => 'web']);
        $owner = User::factory()->create([
            'is_business_owner' => true,
            'tailoring_access' => true,
            'clothing_access' => true,
        ]);
        $owner->assignRole($role);
        $business = Business::create([
            'name' => 'Checkout Business',
            'owner_user_id' => $owner->id,
            'tailoring_enabled' => true,
            'clothing_enabled' => true,
            'status' => Business::STATUS_ACTIVE,
            'approved_at' => now(),
        ]);
        $owner->update(['business_id' => $business->id]);
        $storefront = Storefront::create([
            'business_id' => $business->id,
            'slug' => 'checkout-shop-'.$business->id,
            'display_name' => 'چیک آؤٹ دکان',
            'show_clothing' => true,
            'pickup_enabled' => true,
            'delivery_enabled' => true,
            'is_published' => true,
            'published_at' => now(),
        ]);
        $brand = ClothBrand::create(['name' => 'صدیقی فیبرکس', 'user_id' => $owner->id]);
        $type = ClothType::create(['name' => 'واش اینڈ ویئر', 'user_id' => $owner->id]);
        $cloth = Cloth::create([
            'cloth_brand_id' => $brand->id,
            'cloth_type_id' => $type->id,
            'user_id' => $owner->id,
            'price' => 1000,
            'sale_price' => 1450,
        ]);
        $color = ClothColor::create([
            'cloth_id' => $cloth->id,
            'user_id' => $owner->id,
            'color' => 'نیلا',
            'length' => 10,
            'average_unit_cost' => 1000,
        ]);
        $replacementColor = ClothColor::create([
            'cloth_id' => $cloth->id,
            'user_id' => $owner->id,
            'color' => 'سرمئی',
            'length' => 8,
            'average_unit_cost' => 1000,
        ]);
        $listing = StorefrontClothingListing::create([
            'storefront_id' => $storefront->id,
            'cloth_id' => $cloth->id,
            'public_name' => 'نیلا پریمیم کپڑا',
            'is_published' => true,
        ]);
        $customer = Customers::create([
            'user_id' => $owner->id,
            'name' => 'محمد اسلم',
            'phone_number1' => '03007771111',
            'mobile_pin' => Hash::make('482913'),
        ]);

        return [$owner->fresh(), $storefront, $listing, $color, $customer, $replacementColor];
    }
}
