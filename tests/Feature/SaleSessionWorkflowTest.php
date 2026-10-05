<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\BusinessRole;
use App\Models\Cloth;
use App\Models\ClothBrand;
use App\Models\ClothColor;
use App\Models\ClothType;
use App\Models\Customers;
use App\Models\SaleSession;
use App\Models\User;
use App\Notifications\SaleSessionAttentionNotification;
use App\Notifications\SaleSessionCompletedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SaleSessionWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_existing_sales_employee_can_login_and_sync_action_changes_with_revision_protection(): void
    {
        [, $agent] = $this->businessUsers();
        $token = $this->login($agent);
        $uuid = 'mobile-session-1';

        $first = $this->withToken($token)->putJson("/api/sales-agent/sessions/{$uuid}", $this->payload(0));
        $first->assertOk()->assertJsonPath('data.revision', 1)->assertJsonPath('data.status', 'active');

        $this->withToken($token)->putJson("/api/sales-agent/sessions/{$uuid}", $this->payload(0))
            ->assertStatus(409)->assertJsonPath('data.revision', 1);

        $this->assertDatabaseHas('sale_sessions', [
            'uuid' => $uuid,
            'agent_user_id' => $agent->id,
            'revision' => 1,
        ]);
    }

    public function test_permission_is_rechecked_after_a_token_has_been_issued(): void
    {
        [, $agent] = $this->businessUsers();
        $token = $this->login($agent);
        $agent->businessRole->update(['permissions' => [BusinessRole::CLOTHING_ACCESS]]);

        $this->withToken($token)->getJson('/api/sales-agent/customers')->assertForbidden();
    }

    public function test_local_mobile_draft_reaches_dashboard_only_when_agent_forwards_it(): void
    {
        Notification::fake();
        [$owner, $agent] = $this->businessUsers();
        $token = $this->login($agent);
        $uuid = 'forward-only-session';

        $this->assertDatabaseMissing('sale_sessions', ['uuid' => $uuid]);
        $this->withToken($token)->postJson("/api/sales-agent/sessions/{$uuid}/attention", $this->payload(0))
            ->assertOk()
            ->assertJsonPath('data.status', SaleSession::STATUS_NEEDS_ATTENTION);

        $this->assertDatabaseHas('sale_sessions', [
            'uuid' => $uuid,
            'agent_user_id' => $agent->id,
            'status' => SaleSession::STATUS_NEEDS_ATTENTION,
        ]);
        Notification::assertSentTo($owner, SaleSessionAttentionNotification::class);

        $this->actingAs($owner)->get(route('admin.sales-sessions.index'))
            ->assertOk()
            ->assertSee('id="salesAttentionModal"', false)
            ->assertSee('id="sales-session-attention-open"', false);

        $this->actingAs($owner)->getJson(route('admin.sales-sessions.feed'))
            ->assertOk()
            ->assertJsonPath('attentionCount', 1)
            ->assertJsonPath('data.0.uuid', $uuid)
            ->assertJsonPath('data.0.status', SaleSession::STATUS_NEEDS_ATTENTION)
            ->assertJsonPath('data.0.agent.name', $agent->name);
    }

    public function test_agent_can_complete_local_draft_in_one_action_with_optional_color(): void
    {
        [$owner, $agent] = $this->businessUsers();
        [$cloth, $color] = $this->stock($owner);
        $cloth->update(['color_tracking_mode' => Cloth::COLOR_TRACKING_NONE]);
        $customer = Customers::create(['name' => 'Direct Buyer', 'phone_number1' => '', 'user_id' => $owner->id]);
        $token = $this->login($agent);
        $uuid = 'direct-mobile-completion';
        $payload = $this->payload(0, [
            'customerMode' => 'existing',
            'customerId' => $customer->id,
            'customer' => ['name' => $customer->name, 'phone' => ''],
            'items' => [[
                'localId' => 'direct-line', 'setCode' => $cloth->set_code,
                'brandId' => $cloth->cloth_brand_id, 'brand' => $cloth->brand->name,
                'clothTypeId' => $cloth->cloth_type_id, 'clothType' => $cloth->type->name,
                'color' => '', 'quantity' => '1', 'length' => '2', 'unitPrice' => '300',
            ]],
            'payment' => ['method' => 'Cash', 'receivedAmount' => '300', 'reference' => null],
        ]);

        $this->assertDatabaseMissing('sale_sessions', ['uuid' => $uuid]);
        $this->withToken($token)->postJson("/api/sales-agent/sessions/{$uuid}/complete", $payload)
            ->assertOk()
            ->assertJsonPath('data.status', SaleSession::STATUS_COMPLETED);

        $this->assertDatabaseHas('sale_sessions', ['uuid' => $uuid, 'status' => SaleSession::STATUS_COMPLETED]);
        $this->assertDatabaseCount('counter_sale_receipts', 1);
        $this->assertEquals(8, (float) $color->fresh()->length);
    }

    public function test_set_qr_lookup_is_tenant_scoped_and_labels_are_visible_on_dashboard(): void
    {
        [$owner, $agent] = $this->businessUsers();
        [$cloth, $color] = $this->stock($owner);
        $cloth->update([
            'default_sale_length' => 4.5,
            'suit_sale_price' => 900,
            'sale_price_basis' => Cloth::SALE_PRICE_PER_SUIT,
        ]);
        $token = $this->login($agent);

        $this->withToken($token)->getJson('/api/sales-agent/inventory/sets/'.urlencode($cloth->set_code))
            ->assertOk()
            ->assertJsonPath('data.setCode', $cloth->set_code)
            ->assertJsonPath('data.brandId', $cloth->cloth_brand_id)
            ->assertJsonPath('data.defaultSaleLength', '4.50')
            ->assertJsonPath('data.salePriceBasis', Cloth::SALE_PRICE_PER_SUIT)
            ->assertJsonPath('data.salePrice', '900.00')
            ->assertJsonPath('data.colors.0.name', $color->color);

        // Already-printed legacy labels remain usable while newly generated
        // labels use the canonical opaque set code.
        $this->withToken($token)->getJson('/api/sales-agent/inventory/sets/'.urlencode($cloth->stock_code))
            ->assertOk()
            ->assertJsonPath('data.setCode', $cloth->set_code);

        $this->actingAs($owner)->get(route('admin.cloth.qr-labels'))
            ->assertOk()
            ->assertSeeText($cloth->set_code)
            ->assertSee('<svg', false);

        $this->actingAs($owner)->get(route('admin.cloth.qr-label', $cloth))
            ->assertOk()
            ->assertSeeText($cloth->set_code)
            ->assertDontSeeText($cloth->stock_code);
    }

    public function test_mobile_inventory_exposes_display_colors_with_one_shared_stock_total(): void
    {
        [$owner, $agent] = $this->businessUsers();
        [$cloth, $stock] = $this->stock($owner);
        $cloth->update([
            'color_tracking_mode' => Cloth::COLOR_TRACKING_DISPLAY_ONLY,
            'display_colors' => ['Navy', 'Maroon'],
        ]);
        $stock->update(['color' => 'عام']);
        $token = $this->login($agent);

        $this->withToken($token)->getJson('/api/sales-agent/inventory/sets/'.urlencode($cloth->set_code))
            ->assertOk()
            ->assertJsonPath('data.colorTrackingMode', Cloth::COLOR_TRACKING_DISPLAY_ONLY)
            ->assertJsonPath('data.availableLength', '10')
            ->assertJsonPath('data.colors.0.name', 'Navy')
            ->assertJsonPath('data.colors.0.availableLength', null)
            ->assertJsonPath('data.colors.1.name', 'Maroon')
            ->assertJsonPath('data.colors.1.availableLength', null);

        $this->withToken($token)->getJson('/api/sales-agent/inventory')
            ->assertOk()
            ->assertJsonPath('data.0.colorTrackingMode', Cloth::COLOR_TRACKING_DISPLAY_ONLY)
            ->assertJsonPath('data.0.availableLength', '10')
            ->assertJsonPath('data.0.colors.0.color', 'Navy')
            ->assertJsonPath('data.0.colors.0.length', null);
    }

    public function test_per_suit_mobile_sale_uses_default_cut_length_and_fixed_suit_price(): void
    {
        [$owner, $agent] = $this->businessUsers();
        [$cloth, $color] = $this->stock($owner);
        $cloth->update([
            'sale_price' => 1200,
            'suit_sale_price' => 1200,
            'default_sale_length' => 4.5,
            'sale_price_basis' => Cloth::SALE_PRICE_PER_SUIT,
        ]);
        $customer = Customers::create(['name' => 'Suit Buyer', 'phone_number1' => '', 'user_id' => $owner->id]);
        $token = $this->login($agent);

        $payload = $this->payload(0, [
            'customerMode' => 'existing',
            'customerId' => $customer->id,
            'customer' => ['name' => $customer->name, 'phone' => ''],
            'items' => [[
                'localId' => 'suit-line', 'setCode' => $cloth->set_code,
                'brandId' => $cloth->cloth_brand_id, 'brand' => $cloth->brand->name,
                'clothTypeId' => $cloth->cloth_type_id, 'clothType' => $cloth->type->name,
                'color' => $color->color, 'quantity' => '1', 'length' => '4.5',
                'unitPrice' => '1200', 'salePriceBasis' => Cloth::SALE_PRICE_PER_SUIT,
            ]],
            'payment' => ['method' => 'Cash', 'receivedAmount' => '1200', 'reference' => null],
        ]);

        $this->withToken($token)->postJson('/api/sales-agent/sessions/per-suit-completion/complete', $payload)
            ->assertOk()
            ->assertJsonPath('data.status', SaleSession::STATUS_COMPLETED);

        $this->assertEquals(5.5, (float) $color->fresh()->length);
        $this->assertDatabaseHas('transactions', ['recivedPayment' => 1200, 'remainingBalance' => 0]);
    }

    public function test_manual_sale_selectors_only_return_current_shop_customers_and_stock(): void
    {
        [$owner, $agent] = $this->businessUsers();
        [$cloth, $color] = $this->stock($owner);
        $customer = Customers::create([
            'name' => 'Current Shop Buyer',
            'phone_number1' => '03001110001',
            'user_id' => $owner->id,
        ]);
        Customers::create([
            'name' => 'Walk-in Customer',
            'phone_number1' => '',
            'user_id' => $owner->id,
        ]);
        $namedWithoutPhone = Customers::create([
            'name' => 'Named Counter Buyer',
            'phone_number1' => '',
            'user_id' => $owner->id,
        ]);

        [$otherOwner] = $this->businessUsers();
        $this->stock($otherOwner);
        Customers::create([
            'name' => 'Other Shop Buyer',
            'phone_number1' => '03001110002',
            'user_id' => $otherOwner->id,
        ]);

        $this->actingAs($owner)->get(route('admin.sellCloth'))
            ->assertOk()
            ->assertSeeText('Current Shop Buyer')
            ->assertSeeText('Named Counter Buyer')
            ->assertDontSeeText('Walk-in Customer')
            ->assertDontSeeText('Other Shop Buyer');

        $token = $this->login($agent);

        $this->withToken($token)->getJson('/api/sales-agent/inventory')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.setCode', $cloth->set_code)
            ->assertJsonPath('data.0.brandId', $cloth->cloth_brand_id)
            ->assertJsonPath('data.0.clothTypeId', $cloth->cloth_type_id)
            ->assertJsonPath('data.0.colors.0.color', $color->color);

        $this->withToken($token)->getJson('/api/sales-agent/customers')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment(['id' => $customer->id, 'name' => 'Current Shop Buyer'])
            ->assertJsonFragment(['id' => $namedWithoutPhone->id, 'name' => 'Named Counter Buyer'])
            ->assertJsonMissing(['name' => 'Walk-in Customer']);
    }

    public function test_live_draft_is_visible_on_dashboard_and_can_be_completed_by_admin(): void
    {
        Notification::fake();
        [$owner, $agent] = $this->businessUsers();
        [$cloth, $color] = $this->stock($owner);
        $customer = Customers::create(['name' => 'Mobile Buyer', 'phone_number1' => '03001112222', 'user_id' => $owner->id]);
        $token = $this->login($agent);
        $uuid = 'mobile-session-completion';
        $payload = $this->payload(0, [
            'customerMode' => 'existing',
            'customerId' => $customer->id,
            'customer' => ['name' => $customer->name, 'phone' => $customer->phone_number1],
            'items' => [[
                'localId' => 'line-1', 'setCode' => null,
                'brandId' => $cloth->cloth_brand_id, 'brand' => $cloth->brand->name,
                'clothTypeId' => $cloth->cloth_type_id, 'clothType' => $cloth->type->name,
                'color' => $color->color, 'quantity' => '1', 'length' => '2',
                'unitPrice' => '300', 'rack' => 'A-1',
            ], [
                'localId' => 'line-2', 'setCode' => $cloth->set_code,
                'brandId' => $cloth->cloth_brand_id, 'brand' => $cloth->brand->name,
                'clothTypeId' => $cloth->cloth_type_id, 'clothType' => $cloth->type->name,
                'color' => $color->color, 'quantity' => '1', 'length' => '1',
                'unitPrice' => '300', 'rack' => 'A-2',
            ]],
            'payment' => ['method' => 'Cash', 'receivedAmount' => '600', 'reference' => null],
        ]);

        $this->withToken($token)->putJson("/api/sales-agent/sessions/{$uuid}", $payload)->assertOk();
        $this->actingAs($owner)->get(route('admin.dashboard.clothing'))
            ->assertOk()
            ->assertSeeText('سیلز ایجنٹ کی لائیو فروخت')
            ->assertSeeText($agent->name);
        $this->actingAs($owner)->getJson(route('admin.sales-sessions.feed'))
            ->assertOk()->assertJsonPath('openCount', 1);
        auth('web')->logout();
        $this->app['auth']->forgetGuards();
        $this->withToken($token)->postJson("/api/sales-agent/sessions/{$uuid}/attention")
            ->assertOk()->assertJsonPath('data.status', 'needs_attention');
        Notification::assertSentTo($owner, SaleSessionAttentionNotification::class);

        $session = SaleSession::where('uuid', $uuid)->firstOrFail();
        $this->actingAs($owner)->get(route('admin.sales-sessions.show', $session))
            ->assertRedirect(route('admin.sellCloth', ['sale_session' => $uuid]));
        $saleFormResponse = $this->actingAs($owner)->get(route('admin.sellCloth', ['sale_session' => $uuid]));
        $saleFormResponse
            ->assertOk()
            ->assertSeeText('سیلز ایجنٹ کی لائیو فروخت')
            ->assertSeeText('2 آئٹمز')
            ->assertSee('name="sale_session_uuid" value="'.$uuid.'"', false)
            ->assertDontSeeText('فیبرک رول / ریک');
        $this->assertStringContainsString('name="item_total[]" value="600"', $saleFormResponse->getContent());
        $this->assertStringContainsString('name="per_meter[]" value="300"', $saleFormResponse->getContent());
        $this->actingAs($owner)->post(route('admin.sales-sessions.claim', $session))->assertRedirect();
        $this->actingAs($owner)->post(route('admin.sellStock'), [
            'sale_session_uuid' => $uuid,
            'brand_name' => [$cloth->cloth_brand_id, $cloth->cloth_brand_id],
            'cloth_type' => [$cloth->cloth_type_id, $cloth->cloth_type_id],
            'color' => [$color->color, $color->color],
            'length' => [2, 1],
            'item_total' => [300, 300],
            'per_meter' => [150, 300],
            'clothes_rack' => ['A-1', 'A-2'],
            'customer_mode' => 'regular',
            'existing_customer_id' => $customer->id,
            'payment' => 600,
            'payment_method' => 'cash',
            'paid_on' => now()->toDateString(),
        ])->assertRedirect();

        $completedSession = $session->fresh('receipt');
        $this->assertSame(SaleSession::STATUS_COMPLETED, $completedSession->status);
        $this->assertEquals(7, (float) $color->fresh()->length);
        $this->assertDatabaseCount('counter_sale_receipts', 1);
        Notification::assertSentTo($agent, SaleSessionCompletedNotification::class);

        $this->actingAs($owner)->get(route('admin.sales-sessions.index'))
            ->assertOk()
            ->assertSeeText('مکمل')
            ->assertSeeText('رسید دیکھیں')
            ->assertSee('data-label="آخری تبدیلی"', false);
        $this->actingAs($owner)->get(route('admin.sales-sessions.show', $completedSession))
            ->assertRedirect(route('admin.printStock', [
                'id' => $completedSession->receipt->first_sale_stock_id,
                'customerId' => $completedSession->receipt->customer_id,
            ]));

        auth('web')->logout();
        $this->app['auth']->forgetGuards();
        $this->withToken($token)->getJson("/api/sales-agent/sessions/{$uuid}")
            ->assertOk()->assertJsonPath('data.status', 'completed')->assertJsonStructure(['data' => ['receipt']]);
    }

    private function businessUsers(): array
    {
        $ownerRole = Role::firstOrCreate(['name' => 'shop_owner', 'guard_name' => 'web']);
        $sellerRole = Role::firstOrCreate(['name' => 'business_employee', 'guard_name' => 'web']);
        $owner = User::factory()->create([
            'password' => 'secret-password', 'is_business_owner' => true,
            'clothing_access' => true, 'must_change_password' => false,
        ]);
        $owner->assignRole($ownerRole);
        $business = Business::create([
            'name' => 'Mobile Shop', 'owner_user_id' => $owner->id,
            'clothing_enabled' => true, 'tailoring_enabled' => false,
            'status' => Business::STATUS_ACTIVE,
        ]);
        $owner->update(['business_id' => $business->id]);
        $businessRole = BusinessRole::create([
            'business_id' => $business->id, 'name' => 'Sales Agent',
            'permissions' => [BusinessRole::CLOTHING_ACCESS, BusinessRole::CLOTHING_SALES],
        ]);
        $agent = User::factory()->create([
            'password' => 'secret-password', 'business_id' => $business->id,
            'business_role_id' => $businessRole->id, 'employee_active' => true,
            'is_business_owner' => false, 'must_change_password' => false,
        ]);
        $agent->assignRole($sellerRole);

        return [$owner->fresh(), $agent->fresh()];
    }

    private function login(User $agent): string
    {
        return $this->postJson('/api/sales-agent/login', [
            'login' => $agent->email, 'password' => 'secret-password', 'device_name' => 'Test phone',
        ])->assertOk()->json('token');
    }

    private function payload(int $revision, array $overrides = []): array
    {
        return array_replace_recursive([
            'revision' => $revision,
            'customerMode' => 'walk-in', 'customerId' => null,
            'customer' => ['name' => '', 'phone' => ''],
            'items' => [[
                'localId' => 'line-1', 'setCode' => null, 'brandId' => null, 'brand' => '',
                'clothTypeId' => null, 'clothType' => '', 'color' => '', 'quantity' => '1',
                'length' => '', 'unitPrice' => '', 'rack' => '',
            ]],
            'payment' => ['method' => 'Cash', 'receivedAmount' => '', 'reference' => null],
            'note' => '',
        ], $overrides);
    }

    private function stock(User $owner): array
    {
        $type = ClothType::create(['name' => 'Lawn', 'user_id' => $owner->id]);
        $brand = ClothBrand::create(['name' => 'Summer', 'user_id' => $owner->id]);
        $cloth = Cloth::create([
            'cloth_type_id' => $type->id, 'cloth_brand_id' => $brand->id,
            'price' => 100, 'sale_price' => 150,
            'color_tracking_mode' => Cloth::COLOR_TRACKING_PER_COLOR,
            'user_id' => $owner->id,
        ]);
        $color = ClothColor::create([
            'cloth_id' => $cloth->id, 'color' => 'Blue', 'length' => 10,
            'average_unit_cost' => 100, 'user_id' => $owner->id,
        ]);

        return [$cloth->load(['brand', 'type']), $color];
    }
}
