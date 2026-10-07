<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\BusinessRole;
use App\Models\Cloth;
use App\Models\ClothBrand;
use App\Models\ClothColor;
use App\Models\ClothType;
use App\Models\CounterOrder;
use App\Models\CounterOrderItem;
use App\Models\Customers;
use App\Models\MeasurementTemplate;
use App\Models\Order;
use App\Models\Options;
use App\Models\OptionType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CounterOrderWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_one_counter_order_can_confirm_cloth_and_tailoring_then_accept_more_items(): void
    {
        [$owner, $customer, $cloth, $stock, $template] = $this->fixture();
        $neckType = OptionType::create([
            'user_id' => $owner->id,
            'Name' => 'گلہ',
            'slug' => 'add_neck_type',
            'type' => 'necktype',
        ]);
        Options::create([
            'user_id' => $owner->id,
            'measurement_template_id' => $template->id,
            'option_id' => $neckType->id,
            'slug' => 'band-collar',
            'Name' => 'Band collar',
        ]);
        $template->update(['system_fields' => ['length', 'necktype']]);

        $this->actingAs($owner)->get(route('admin.counter-orders.create', ['customer' => $customer]))
            ->assertOk()->assertSeeText('ایک آرڈر شروع کریں');
        $this->actingAs($owner)->post(route('admin.counter-orders.store'), [
            'customer_id' => $customer->id,
            'profile_id' => $customer->id,
            'note' => 'Wedding order',
        ])->assertRedirectToRoute('admin.counter-orders.edit', [
            'counterOrder' => 1,
            'profile' => $customer->id,
        ]);
        $counterOrder = CounterOrder::firstOrFail();
        $this->actingAs($owner)->get(route('admin.counter-orders.create', ['customer' => $customer, 'profile' => $customer]))
            ->assertOk()->assertSeeText('اس گاہک کے کھلے آرڈرز')->assertSeeText($counterOrder->reference);

        $this->actingAs($owner)->put(route('admin.Customers.update', $customer), [
            'name' => $customer->name,
            'contact' => $customer->phone_number1,
            'measurement_template_id' => $template->id,
            'length' => 40,
            'add_neck_type' => 'Band collar',
            'return_counter_order' => $counterOrder->id,
            'return_profile' => $customer->id,
        ])->assertRedirect(route('admin.counter-orders.edit', [
            'counterOrder' => $counterOrder,
            'profile' => $customer->id,
        ]));

        $this->actingAs($owner)->post(route('admin.counter-orders.cloth-items.store', $counterOrder), [
            'cloth_id' => $cloth->id,
            'color' => $stock->color,
            'length' => 2,
            'total_price' => 600,
        ])->assertRedirect()->assertSessionHasNoErrors();
        $clothItem = $counterOrder->items()->where('type', CounterOrderItem::TYPE_CLOTH)->firstOrFail();
        $this->assertSame('300.00', $clothItem->unit_price);
        $this->assertSame('600.00', $clothItem->line_total);

        $this->actingAs($owner)->post(route('admin.counter-orders.tailoring-items.store', $counterOrder), [
            'measurement_profile_id' => $customer->id,
            'measurement_template_id' => $template->id,
            'quantity' => 1,
            'unit_price' => 1200,
            'due_date' => now()->addWeek()->toDateString(),
        ])->assertRedirect()->assertSessionHasNoErrors();

        $tailoringItem = $counterOrder->items()->where('type', CounterOrderItem::TYPE_TAILORING)->firstOrFail();
        $snapshotLength = collect(data_get($tailoringItem->details, 'measurement_snapshot'))
            ->firstWhere('source_key', 'system.length')['value'];
        $this->assertSame('40', (string) $snapshotLength);
        $snapshotNeck = collect(data_get($tailoringItem->details, 'measurement_snapshot'))
            ->firstWhere('source_key', 'system.necktype')['value'];
        $this->assertSame('Band collar', $snapshotNeck);
        $customer->update(['length' => 99]);

        $editor = $this->actingAs($owner)->get(route('admin.counter-orders.edit', $counterOrder));
        $editor->assertOk()->assertSeeText('کپڑا اور سلائی ایک ہی آرڈر میں')
            ->assertSeeText('کپڑا اسی فہرست میں شامل یا تبدیل کریں')
            ->assertSeeText('ناپ دیکھیں / تبدیل کریں')
            ->assertSeeText('کل سلائی رقم')
            ->assertSee('return_counter_order='.$counterOrder->id, false)
            ->assertDontSee('name="linked_item_id"', false)
            ->assertDontSee('name="tailor_id"', false)
            ->assertDontSee('name="rate_id"', false)
            ->assertSee('name="total_price"', false)
            ->assertDontSee('name="unit_price" id="co-cloth-price"', false)
            ->assertDontSee('name="rack"', false);
        $editor->assertSee('name="quantity"', false)
            ->assertSee('id="co-new-cloth-row"', false)
            ->assertSee('data-length="4.5"', false)
            ->assertSee('data-inline-edit="co-edit-cloth-'.$clothItem->id.'"', false)
            ->assertDontSee('id="clothItemModal"', false)
            ->assertSee(route('admin.counter-orders.items.update', [$counterOrder, $clothItem]), false)
            ->assertSee(route('admin.counter-orders.items.update', [$counterOrder, $tailoringItem]), false);
        $this->assertStringContainsString('co-modal-form', $editor->getContent());

        $this->actingAs($owner)->post(route('admin.counter-orders.confirm', $counterOrder), [
            'payment' => 1800,
            'payment_method' => 'cash',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertDatabaseCount('counter_sale_receipts', 1);
        $this->assertDatabaseCount('orders', 1);
        $this->assertEquals(8, (float) $stock->fresh()->length);
        $this->assertSame(2, $counterOrder->items()->where('status', CounterOrderItem::STATUS_CONFIRMED)->count());
        $legacyOrder = Order::firstOrFail();
        $this->assertSame($customer->id, (int) $legacyOrder->customerId);
        $this->assertSame($template->id, $legacyOrder->measurement_template_id);
        $this->assertDatabaseHas('order_measurement_values', [
            'order_id' => $legacyOrder->id,
            'source_key' => 'system.length',
            'value' => '40',
        ]);
        $this->assertDatabaseHas('order_measurement_values', [
            'order_id' => $legacyOrder->id,
            'source_key' => 'system.necktype',
            'value' => 'Band collar',
        ]);
        $this->assertDatabaseHas('transactions', ['orderId' => $legacyOrder->id, 'recivedPayment' => 1200]);
        $legacyOrder->measurementValues()->where('source_key', 'system.necktype')->update(['value' => '0']);
        $this->actingAs($owner)->get(route('admin.order-print', $legacyOrder))
            ->assertOk()
            ->assertSeeText('Band collar')
            ->assertDontSee('images/setting/', false);
        $this->actingAs($owner)->get(route('admin.counter-orders.print', $counterOrder))
            ->assertOk()
            ->assertSeeText($counterOrder->reference)
            ->assertSeeText('Summer')
            ->assertSeeText('Simple Suit')
            ->assertSeeText('Rs. 1,800.00');

        $this->actingAs($owner)->post(route('admin.counter-orders.cloth-items.store', $counterOrder), [
            'cloth_id' => $cloth->id,
            'color' => $stock->color,
            'length' => 1,
            'total_price' => 300,
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame(CounterOrder::STATUS_CONFIRMED, $counterOrder->fresh()->status);
        $this->assertSame(1, $counterOrder->items()->where('status', CounterOrderItem::STATUS_DRAFT)->count());

        $this->actingAs($owner)->post(route('admin.counter-orders.confirm', $counterOrder), [
            'payment' => 0,
            'payment_method' => 'cash',
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertEquals(7, (float) $stock->fresh()->length);
        $this->assertSame('2100.00', $counterOrder->fresh()->subtotal);
        $this->assertSame('1800.00', $counterOrder->fresh()->paid_amount);
        $this->assertSame('300.00', $counterOrder->fresh()->balance_amount);
    }

    public function test_confirmed_order_must_have_no_draft_items_before_it_can_close(): void
    {
        [$owner, $customer, $cloth, $stock] = $this->fixture();
        $counterOrder = CounterOrder::create([
            'reference' => 'ORD-TEST-CLOSE', 'user_id' => $owner->id, 'customer_id' => $customer->id,
            'created_by_user_id' => $owner->id, 'status' => CounterOrder::STATUS_DRAFT,
        ]);
        $counterOrder->items()->create([
            'type' => CounterOrderItem::TYPE_CLOTH, 'cloth_id' => $cloth->id, 'quantity' => 1,
            'length' => 1, 'unit_price' => 300, 'line_total' => 300,
            'details' => ['color' => $stock->color],
        ]);

        $this->actingAs($owner)->post(route('admin.counter-orders.close', $counterOrder))->assertStatus(409);
        $this->actingAs($owner)->post(route('admin.counter-orders.confirm', $counterOrder), [
            'payment' => 300, 'payment_method' => 'cash',
        ])->assertRedirect();
        $this->actingAs($owner)->post(route('admin.counter-orders.close', $counterOrder))->assertRedirect();
        $this->assertSame(CounterOrder::STATUS_CLOSED, $counterOrder->fresh()->status);
    }

    public function test_draft_cloth_and_tailoring_items_can_be_edited_before_confirmation(): void
    {
        [$owner, $customer, $cloth, $stock, $template] = $this->fixture();
        $cloth->update([
            'sale_price_basis' => Cloth::SALE_PRICE_PER_SUIT,
            'suit_sale_price' => 1500,
            'default_sale_length' => 4.5,
        ]);
        $stock->update(['length' => 30]);
        $customer->update(['length' => 40]);
        $counterOrder = CounterOrder::create([
            'reference' => 'ORD-EDIT-DRAFTS', 'user_id' => $owner->id, 'customer_id' => $customer->id,
            'created_by_user_id' => $owner->id, 'status' => CounterOrder::STATUS_DRAFT,
        ]);
        $clothItem = $counterOrder->items()->create([
            'type' => CounterOrderItem::TYPE_CLOTH, 'status' => CounterOrderItem::STATUS_DRAFT,
            'cloth_id' => $cloth->id, 'quantity' => 1, 'length' => 4.5,
            'unit_price' => 1500, 'line_total' => 1500,
            'details' => ['color' => $stock->color, 'sale_price_basis' => Cloth::SALE_PRICE_PER_SUIT],
        ]);
        $tailoringItem = $counterOrder->items()->create([
            'type' => CounterOrderItem::TYPE_TAILORING, 'status' => CounterOrderItem::STATUS_DRAFT,
            'measurement_profile_id' => $customer->id, 'measurement_template_id' => $template->id,
            'quantity' => 1, 'unit_price' => 1200, 'line_total' => 1200,
            'due_date' => now()->addWeek(),
        ]);
        $counterOrder->recalculate();

        $this->actingAs($owner)->patch(route('admin.counter-orders.items.update', [$counterOrder, $clothItem]), [
            'color' => $stock->color,
            'quantity' => 3,
            'length' => 4,
            'total_price' => 4500,
            'note' => 'Changed by admin',
        ])->assertRedirect()->assertSessionHasNoErrors();
        $clothItem->refresh();
        $this->assertSame('3.00', $clothItem->quantity);
        $this->assertSame('4.00', $clothItem->length);
        $this->assertSame('1500.00', $clothItem->unit_price);
        $this->assertSame('4500.00', $clothItem->line_total);
        $this->assertSame('Changed by admin', $clothItem->note);

        $this->actingAs($owner)->patch(route('admin.counter-orders.items.update', [$counterOrder, $tailoringItem]), [
            'measurement_profile_id' => $customer->id,
            'measurement_template_id' => $template->id,
            'quantity' => 2,
            'unit_price' => 1400,
            'due_date' => now()->addDays(10)->toDateString(),
            'note' => 'Two suits',
        ])->assertRedirect()->assertSessionHasNoErrors();
        $tailoringItem->refresh();
        $this->assertSame('2.00', $tailoringItem->quantity);
        $this->assertSame('2800.00', $tailoringItem->line_total);
        $this->assertNotEmpty(data_get($tailoringItem->details, 'measurement_snapshot'));
        $this->assertSame('7300.00', $counterOrder->fresh()->subtotal);

        $this->actingAs($owner)->post(route('admin.counter-orders.confirm', $counterOrder), [
            'payment' => 0,
            'payment_method' => 'cash',
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertEquals(18, (float) $stock->fresh()->length);

        $this->actingAs($owner)->patch(route('admin.counter-orders.items.update', [$counterOrder, $clothItem]), [
            'color' => $stock->color, 'quantity' => 1, 'length' => 1, 'total_price' => 1,
        ])->assertStatus(409);
    }

    public function test_cloth_rows_can_be_added_and_updated_without_page_navigation(): void
    {
        [$owner, $customer, $cloth, $stock] = $this->fixture();
        $order = CounterOrder::create([
            'reference' => 'ORD-AJAX-ROWS',
            'user_id' => $owner->id,
            'customer_id' => $customer->id,
            'created_by_user_id' => $owner->id,
            'status' => CounterOrder::STATUS_DRAFT,
        ]);

        $created = $this->actingAs($owner)->postJson(
            route('admin.counter-orders.cloth-items.store', $order),
            [
                'cloth_id' => $cloth->id,
                'color' => $stock->color,
                'length' => 4.5,
                'total_price' => 1350,
            ]
        );
        $created->assertOk()
            ->assertJsonPath('summary.draft_count', 1)
            ->assertJsonPath('summary.subtotal', 1350)
            ->assertJsonStructure(['item_id', 'row_html', 'summary']);
        $this->assertStringContainsString('data-order-item=', $created->json('row_html'));

        $item = $order->items()->firstOrFail();
        $updated = $this->actingAs($owner)->patchJson(
            route('admin.counter-orders.items.update', [$order, $item]),
            [
                'color' => $stock->color,
                'length' => 4,
                'total_price' => 1280,
            ]
        );
        $updated->assertOk()
            ->assertJsonPath('item_id', $item->id)
            ->assertJsonPath('summary.draft_count', 1)
            ->assertJsonPath('summary.subtotal', 1280);
        $this->assertStringContainsString('value="1280.00"', $updated->json('row_html'));
    }

    public function test_laptop_order_desk_enforces_shop_and_tailoring_boundaries_and_retires_legacy_flows(): void
    {
        [$owner, $customer, $cloth] = $this->fixture();
        $business = $owner->business;
        $shopRole = BusinessRole::create([
            'business_id' => $business->id,
            'name' => 'Shop counter only',
            'permissions' => [BusinessRole::CLOTHING_ACCESS, BusinessRole::CLOTHING_SALES],
        ]);
        $tailoringRole = BusinessRole::create([
            'business_id' => $business->id,
            'name' => 'Tailoring counter only',
            'permissions' => [BusinessRole::TAILORING_ACCESS, BusinessRole::TAILORING_ORDERS],
        ]);
        $shopEmployee = User::factory()->create([
            'business_id' => $business->id,
            'business_role_id' => $shopRole->id,
            'is_business_owner' => false,
            'employee_active' => true,
            'must_change_password' => false,
        ]);
        $tailoringEmployee = User::factory()->create([
            'business_id' => $business->id,
            'business_role_id' => $tailoringRole->id,
            'is_business_owner' => false,
            'employee_active' => true,
            'must_change_password' => false,
        ]);
        $counterOrder = CounterOrder::create([
            'reference' => 'ORD-PERMISSIONS',
            'user_id' => $owner->id,
            'customer_id' => $customer->id,
            'created_by_user_id' => $owner->id,
            'status' => CounterOrder::STATUS_DRAFT,
        ]);
        $clothItem = $counterOrder->items()->create([
            'type' => CounterOrderItem::TYPE_CLOTH,
            'status' => CounterOrderItem::STATUS_DRAFT,
            'cloth_id' => $cloth->id,
            'quantity' => 1,
            'length' => 1,
            'unit_price' => 300,
            'line_total' => 300,
        ]);
        $tailoringItem = $counterOrder->items()->create([
            'type' => CounterOrderItem::TYPE_TAILORING,
            'status' => CounterOrderItem::STATUS_DRAFT,
            'measurement_profile_id' => $customer->id,
            'quantity' => 1,
            'unit_price' => 1000,
            'line_total' => 1000,
            'due_date' => now()->addWeek(),
        ]);

        $this->actingAs($shopEmployee)->get(route('admin.counter-orders.edit', $counterOrder))
            ->assertOk()
            ->assertSee('id="co-add-cloth-button"', false)
            ->assertSee('id="co-new-cloth-row"', false)
            ->assertDontSee('data-target="#tailoringItemModal"', false)
            ->assertDontSee(route('admin.counter-orders.confirm', $counterOrder), false);
        $this->actingAs($shopEmployee)->post(route('admin.counter-orders.tailoring-items.store', $counterOrder), [])
            ->assertForbidden();
        $this->actingAs($shopEmployee)->delete(route('admin.counter-orders.items.destroy', [$counterOrder, $tailoringItem]))
            ->assertForbidden();
        $this->actingAs($shopEmployee)->post(route('admin.counter-orders.confirm', $counterOrder), [
            'payment' => 0,
            'payment_method' => 'cash',
        ])->assertForbidden();

        $this->actingAs($tailoringEmployee)->get(route('admin.counter-orders.edit', $counterOrder))
            ->assertOk()
            ->assertDontSee('id="co-add-cloth-button"', false)
            ->assertDontSee('id="co-new-cloth-row"', false)
            ->assertSee('data-target="#tailoringItemModal"', false)
            ->assertDontSee(route('admin.counter-orders.confirm', $counterOrder), false);
        $this->actingAs($tailoringEmployee)->post(route('admin.counter-orders.cloth-items.store', $counterOrder), [])
            ->assertForbidden();
        $this->actingAs($tailoringEmployee)->delete(route('admin.counter-orders.items.destroy', [$counterOrder, $clothItem]))
            ->assertForbidden();

        $this->actingAs($shopEmployee)->get(route('admin.sellCloth'))
            ->assertRedirect(route('admin.counter-orders.create'));
        $this->actingAs($tailoringEmployee)->get(route('admin.order.create', $customer))
            ->assertRedirect(route('admin.counter-orders.create', [
                'customer' => $customer->id,
                'profile' => $customer->id,
            ]));
        $this->actingAs($tailoringEmployee)->get(route('admin.sale.create'))
            ->assertRedirect(route('admin.counter-orders.create'));
    }

    public function test_clothing_only_owner_cannot_see_or_submit_stitching_items(): void
    {
        [$owner, $customer, $cloth] = $this->fixture();
        $owner->business->update(['tailoring_enabled' => false, 'clothing_enabled' => true]);
        $order = CounterOrder::create([
            'reference' => 'ORD-CLOTH-ONLY',
            'user_id' => $owner->id,
            'customer_id' => $customer->id,
            'created_by_user_id' => $owner->id,
            'status' => CounterOrder::STATUS_DRAFT,
        ]);

        $this->actingAs($owner)->get(route('admin.counter-orders.create'))
            ->assertOk()
            ->assertSeeText('فروخت ہونے والا کپڑا شامل کریں')
            ->assertDontSeeText('کپڑا اور سلائی');

        $this->actingAs($owner)->get(route('admin.counter-orders.edit', $order))
            ->assertOk()
            ->assertSee('id="co-add-cloth-button"', false)
            ->assertSee('id="co-new-cloth-row"', false)
            ->assertDontSee('data-target="#tailoringItemModal"', false)
            ->assertSeeText('کپڑے کی فروخت کا آرڈر')
            ->assertDontSeeText('سلائی شامل کریں');

        $this->actingAs($owner)->post(route('admin.counter-orders.tailoring-items.store', $order), [])
            ->assertForbidden();
    }

    public function test_tailoring_only_owner_cannot_see_or_submit_cloth_items(): void
    {
        [$owner, $customer] = $this->fixture();
        $owner->business->update(['tailoring_enabled' => true, 'clothing_enabled' => false]);
        $order = CounterOrder::create([
            'reference' => 'ORD-TAILORING-ONLY',
            'user_id' => $owner->id,
            'customer_id' => $customer->id,
            'created_by_user_id' => $owner->id,
            'status' => CounterOrder::STATUS_DRAFT,
        ]);

        $this->actingAs($owner)->get(route('admin.counter-orders.create'))
            ->assertOk()
            ->assertSeeText('گاہک کا سلائی آرڈر شامل کریں')
            ->assertDontSeeText('فروخت ہونے والا کپڑا');

        $this->actingAs($owner)->get(route('admin.counter-orders.edit', $order))
            ->assertOk()
            ->assertDontSee('id="co-add-cloth-button"', false)
            ->assertDontSee('id="co-new-cloth-row"', false)
            ->assertSee('data-target="#tailoringItemModal"', false)
            ->assertSeeText('سلائی کا آرڈر')
            ->assertDontSeeText('کپڑا شامل کریں');

        $this->actingAs($owner)->post(route('admin.counter-orders.cloth-items.store', $order), [])
            ->assertForbidden();
    }

    private function fixture(): array
    {
        $role = Role::firstOrCreate(['name' => 'shop_owner', 'guard_name' => 'web']);
        $owner = User::factory()->create([
            'is_business_owner' => true, 'tailoring_access' => true, 'clothing_access' => true,
            'must_change_password' => false,
        ]);
        $owner->assignRole($role);
        $business = Business::create([
            'name' => 'Combined Shop', 'owner_user_id' => $owner->id,
            'tailoring_enabled' => true, 'clothing_enabled' => true, 'status' => Business::STATUS_ACTIVE,
        ]);
        $owner->update(['business_id' => $business->id]);
        $customer = Customers::create([
            'name' => 'Combined Customer', 'phone_number1' => '03007770000', 'user_id' => $owner->id,
        ]);
        $template = MeasurementTemplate::create([
            'user_id' => $owner->id, 'name' => 'Simple Suit', 'system_fields' => ['length'],
            'custom_field_ids' => [], 'is_default' => true, 'is_active' => true,
        ]);
        $type = ClothType::create(['name' => 'Lawn', 'user_id' => $owner->id]);
        $brand = ClothBrand::create(['name' => 'Summer', 'user_id' => $owner->id]);
        $cloth = Cloth::create([
            'cloth_type_id' => $type->id, 'cloth_brand_id' => $brand->id,
            'price' => 100, 'sale_price' => 300, 'color_tracking_mode' => Cloth::COLOR_TRACKING_PER_COLOR,
            'user_id' => $owner->id,
        ]);
        $stock = ClothColor::create([
            'cloth_id' => $cloth->id, 'color' => 'Blue', 'length' => 10,
            'average_unit_cost' => 100, 'user_id' => $owner->id,
        ]);

        return [$owner->fresh(), $customer, $cloth->load(['brand', 'type']), $stock, $template];
    }
}
