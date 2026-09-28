<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\BusinessRole;
use App\Models\ShopHubEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ShopHubSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_hub_status_distinguishes_shop_lan_from_cloud(): void
    {
        config()->set('shop_hub.mode', 'hub');
        config()->set('shop_hub.id', 'shop-qa-01');
        config()->set('shop_hub.name', 'QA Shop Hub');

        $this->getJson('/api/shop-hub/status')
            ->assertOk()
            ->assertJsonPath('data.mode', 'shop')
            ->assertJsonPath('data.hubId', 'shop-qa-01')
            ->assertJsonPath('data.hubName', 'QA Shop Hub')
            ->assertJsonPath('data.withinShopSync', true);
    }

    public function test_mobile_sale_saved_on_hub_is_visible_to_shop_dashboard_and_journaled(): void
    {
        config()->set('shop_hub.mode', 'hub');
        config()->set('shop_hub.id', 'shop-qa-01');
        [$owner, $agent] = $this->businessUsers();

        $login = $this->postJson('/api/sales-agent/login', [
            'login' => $agent->email,
            'password' => 'secret-password',
            'device_name' => 'QA phone',
        ])->assertOk()
            ->assertJsonPath('connection.mode', 'shop')
            ->assertJsonPath('connection.hubId', 'shop-qa-01');

        $token = $login->json('token');
        $sessionUuid = (string) Str::uuid();
        $operationUuid = (string) Str::uuid();

        $this->withToken($token)
            ->withHeader('X-Shop-Device-Id', 'qa-phone-01')
            ->putJson("/api/sales-agent/sessions/{$sessionUuid}", [
                'operationId' => $operationUuid,
                'revision' => 0,
                'customerMode' => 'walk-in',
                'customerId' => null,
                'customer' => ['name' => '', 'phone' => ''],
                'items' => [[
                    'localId' => 'line-1',
                    'setCode' => null,
                    'brandId' => null,
                    'brand' => 'Local brand',
                    'clothTypeId' => null,
                    'clothType' => 'Cotton',
                    'color' => null,
                    'availableColors' => [],
                    'quantity' => '1',
                    'length' => '2',
                    'unitPrice' => '500',
                ]],
                'payment' => ['method' => 'Cash', 'receivedAmount' => '', 'reference' => null],
                'note' => 'Saved on shop Wi-Fi',
            ])
            ->assertOk()
            ->assertJsonPath('connection.mode', 'shop')
            ->assertJsonPath('data.uuid', $sessionUuid);

        $this->assertDatabaseHas('shop_hub_events', [
            'event_uuid' => $operationUuid,
            'business_owner_user_id' => $owner->id,
            'actor_user_id' => $agent->id,
            'device_id' => 'qa-phone-01',
            'event_type' => 'sale_session.saved',
            'aggregate_uuid' => $sessionUuid,
            'status' => ShopHubEvent::STATUS_PENDING,
        ]);

        auth('web')->logout();
        $this->app['auth']->forgetGuards();
        $this->actingAs($owner)->getJson(route('admin.sales-sessions.feed'))
            ->assertOk()
            ->assertJsonPath('openCount', 1)
            ->assertJsonPath('data.0.uuid', $sessionUuid)
            ->assertJsonPath('data.0.items.0.brand', 'Local brand');

        auth('web')->logout();
        $this->app['auth']->forgetGuards();
        $this->withToken($token)->getJson('/api/sales-agent/shop-context')
            ->assertOk()
            ->assertJsonPath('data.mode', 'shop')
            ->assertJsonPath('data.pendingCloudEvents', 1);
    }

    public function test_cloud_relay_accepts_signed_event_once_and_rejects_stale_revision(): void
    {
        config()->set('shop_hub.mode', 'cloud');
        config()->set('shop_hub.sync_key', 'qa-shared-relay-key');
        [$owner, $agent] = $this->businessUsers();
        $sessionUuid = (string) Str::uuid();

        $first = $this->relayEvent($owner, $agent, $sessionUuid, 0, 1, 'sale_session.saved');
        $this->postSignedRelay([$first])
            ->assertOk()
            ->assertJsonPath('results.0.status', ShopHubEvent::STATUS_SYNCED)
            ->assertJsonPath('results.0.resultingRevision', 1);

        $this->postSignedRelay([$first])
            ->assertOk()
            ->assertJsonPath('results.0.status', ShopHubEvent::STATUS_SYNCED)
            ->assertJsonPath('results.0.alreadyProcessed', true);

        $stale = $this->relayEvent($owner, $agent, $sessionUuid, 0, 1, 'sale_session.saved');
        $this->postSignedRelay([$stale])
            ->assertOk()
            ->assertJsonPath('results.0.status', ShopHubEvent::STATUS_CONFLICT)
            ->assertJsonPath('results.0.reason', 'revision_conflict');

        $this->assertDatabaseHas('sale_sessions', ['uuid' => $sessionUuid, 'revision' => 1]);
        $this->assertDatabaseCount('sale_sessions', 1);
    }

    private function postSignedRelay(array $events)
    {
        $body = json_encode(['events' => $events], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        $timestamp = (string) now()->timestamp;
        $signature = hash_hmac('sha256', $timestamp.'.'.$body, 'qa-shared-relay-key');

        return $this->call('POST', '/api/shop-hub/relay', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_SHOP_HUB_TIMESTAMP' => $timestamp,
            'HTTP_X_SHOP_HUB_SIGNATURE' => $signature,
        ], $body);
    }

    private function relayEvent(
        User $owner,
        User $agent,
        string $sessionUuid,
        int $baseRevision,
        int $resultingRevision,
        string $eventType,
    ): array {
        return [
            'eventUuid' => (string) Str::uuid(),
            'businessOwnerUserId' => $owner->id,
            'actorUserId' => $agent->id,
            'deviceId' => 'qa-phone-01',
            'eventType' => $eventType,
            'aggregateUuid' => $sessionUuid,
            'baseRevision' => $baseRevision,
            'resultingRevision' => $resultingRevision,
            'payload' => [
                'customerMode' => 'walk-in',
                'customerId' => null,
                'customer' => ['name' => '', 'phone' => ''],
                'items' => [[
                    'localId' => 'line-1',
                    'setCode' => null,
                    'brandId' => null,
                    'brand' => 'Relay brand',
                    'clothTypeId' => null,
                    'clothType' => 'Cotton',
                    'color' => null,
                    'availableColors' => [],
                    'quantity' => '1',
                    'length' => '2',
                    'unitPrice' => '500',
                ]],
                'payment' => ['method' => 'Cash', 'receivedAmount' => '', 'reference' => null],
                'note' => 'Relayed from shop',
            ],
            'occurredAt' => now()->toIso8601String(),
        ];
    }

    private function businessUsers(): array
    {
        $ownerRole = Role::firstOrCreate(['name' => 'shop_owner', 'guard_name' => 'web']);
        $employeeRole = Role::firstOrCreate(['name' => 'business_employee', 'guard_name' => 'web']);
        $owner = User::factory()->create([
            'password' => 'secret-password',
            'is_business_owner' => true,
            'clothing_access' => true,
            'must_change_password' => false,
        ]);
        $owner->assignRole($ownerRole);
        $business = Business::create([
            'name' => 'LAN Sync Shop',
            'owner_user_id' => $owner->id,
            'clothing_enabled' => true,
            'tailoring_enabled' => false,
            'status' => Business::STATUS_ACTIVE,
        ]);
        $owner->update(['business_id' => $business->id]);
        $salesRole = BusinessRole::create([
            'business_id' => $business->id,
            'name' => 'Sales Agent',
            'permissions' => [BusinessRole::CLOTHING_ACCESS, BusinessRole::CLOTHING_SALES],
        ]);
        $agent = User::factory()->create([
            'password' => 'secret-password',
            'business_id' => $business->id,
            'business_role_id' => $salesRole->id,
            'employee_active' => true,
            'is_business_owner' => false,
            'must_change_password' => false,
        ]);
        $agent->assignRole($employeeRole);

        return [$owner->fresh(), $agent->fresh()];
    }
}
