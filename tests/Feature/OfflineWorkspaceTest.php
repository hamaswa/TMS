<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Customers;
use App\Models\OfflineOperation;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\Tailor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class OfflineWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_tailor_job_workspace_exposes_the_offline_shell_and_queue_metadata(): void
    {
        [$owner, $tailor, $order] = $this->tailorOrder();

        $this->withSession($this->tailorSession($tailor))
            ->get(route('tailor.jobs.index'))
            ->assertOk()
            ->assertSee('manifest.webmanifest', false)
            ->assertSee('tms-offline-sync-url', false)
            ->assertSee('content="tailor:'.$tailor->id.'"', false)
            ->assertSee('offline-workspace.js', false)
            ->assertSee('data-offline-command="order.status.change"', false)
            ->assertSee('data-order-id="'.$order->id.'"', false)
            ->assertSee('data-base-status="assigned"', false);
    }

    public function test_business_offline_manifest_contains_permitted_workspace_pages(): void
    {
        [$owner] = $this->tailorOrder();

        $response = $this->actingAs($owner)->getJson(route('admin.offline.manifest'));

        $response->assertOk()
            ->assertJsonPath('actor', 'user:'.$owner->id)
            ->assertJsonStructure([
                'version',
                'actor',
                'pages' => [['label', 'url', 'route']],
                'inventory' => ['permitted_get_routes', 'fixed_pages', 'record_route_patterns'],
            ]);

        $urls = collect($response->json('pages'))->pluck('url');
        $this->assertTrue($urls->contains(route('admin.dashboard.tailoring')));
        $this->assertTrue($urls->contains(route('admin.Customers.create')));
        $this->assertTrue($urls->contains(route('admin.tailor-jobs.index')));
        $this->assertTrue($urls->contains(route('admin.design.create')));
        $this->assertFalse($urls->contains(route('admin.notifications.index')));
        $this->assertFalse($urls->contains(route('admin.OptionType.create')));
        $this->assertFalse($urls->contains(route('admin.Options.index')));
        $this->assertFalse($urls->contains(route('admin.dashboard.clothing')));
    }

    public function test_tailor_offline_manifest_is_limited_to_tailor_pages(): void
    {
        [, $tailor] = $this->tailorOrder();

        $this->withSession($this->tailorSession($tailor))
            ->getJson(route('tailor.offline.manifest'))
            ->assertOk()
            ->assertJsonPath('actor', 'tailor:'.$tailor->id)
            ->assertJsonCount(2, 'pages')
            ->assertJsonFragment(['url' => route('tailor.jobs.index')]);
    }

    public function test_tailor_offline_status_command_is_applied_only_once(): void
    {
        [$owner, $tailor, $order] = $this->tailorOrder();
        $operationId = (string) Str::uuid();
        $command = $this->statusCommand($operationId, $order, 'assigned', 'cutting');

        $this->withSession($this->tailorSession($tailor))
            ->postJson(route('tailor.offline.sync'), ['commands' => [$command]])
            ->assertOk()
            ->assertJsonPath('results.0.status', OfflineOperation::STATUS_APPLIED)
            ->assertJsonPath('results.0.current_status', 'cutting');

        $this->assertSame('cutting', $order->fresh()->status);
        $this->assertSame(1, OrderStatusHistory::where('order_id', $order->id)->count());
        $this->assertSame(1, OfflineOperation::where('operation_uuid', $operationId)->count());

        $this->withSession($this->tailorSession($tailor))
            ->postJson(route('tailor.offline.sync'), ['commands' => [$command]])
            ->assertOk()
            ->assertJsonPath('results.0.status', OfflineOperation::STATUS_APPLIED);

        $this->assertSame(1, OrderStatusHistory::where('order_id', $order->id)->count());
        $this->assertSame(1, OfflineOperation::where('operation_uuid', $operationId)->count());
    }

    public function test_compatible_offline_status_is_rebased_and_incompatible_change_becomes_a_conflict(): void
    {
        [$owner, $tailor, $order] = $this->tailorOrder();
        $owner->business->update(['tailoring_status_mode' => Business::TAILORING_STATUS_DETAILED]);
        $order->update(['status' => 'cutting']);

        $rebase = $this->statusCommand((string) Str::uuid(), $order, 'assigned', 'stitching');
        $this->withSession($this->tailorSession($tailor))
            ->postJson(route('tailor.offline.sync'), ['commands' => [$rebase]])
            ->assertOk()
            ->assertJsonPath('results.0.status', OfflineOperation::STATUS_APPLIED)
            ->assertJsonPath('results.0.rebased', true)
            ->assertJsonPath('results.0.current_status', 'stitching');

        $conflict = $this->statusCommand((string) Str::uuid(), $order, 'assigned', 'delivered');
        $this->withSession($this->tailorSession($tailor))
            ->postJson(route('tailor.offline.sync'), ['commands' => [$conflict]])
            ->assertOk()
            ->assertJsonPath('results.0.status', OfflineOperation::STATUS_CONFLICT)
            ->assertJsonPath('results.0.reason', 'tailor_cannot_deliver');

        $this->assertSame('stitching', $order->fresh()->status);
    }

    public function test_tailor_cannot_sync_another_tailors_order(): void
    {
        [$owner, $tailor, $order] = $this->tailorOrder();
        $otherTailor = Tailor::create([
            'name' => 'دوسرا درزی',
            'phone_number1' => '03009998888',
            'password' => bcrypt('Tailor@2026'),
            'user_id' => $owner->id,
        ]);
        $command = $this->statusCommand((string) Str::uuid(), $order, 'assigned', 'cutting');

        $this->withSession($this->tailorSession($otherTailor))
            ->postJson(route('tailor.offline.sync'), ['commands' => [$command]])
            ->assertOk()
            ->assertJsonPath('results.0.status', OfflineOperation::STATUS_CONFLICT)
            ->assertJsonPath('results.0.reason', 'order_unavailable');

        $this->assertSame('assigned', $order->fresh()->status);
    }

    public function test_shop_owner_can_sync_a_valid_offline_delivery(): void
    {
        [$owner, $tailor, $order] = $this->tailorOrder();
        $order->update(['status' => 'ready', 'ready_at' => now()]);
        $command = $this->statusCommand((string) Str::uuid(), $order, 'ready', 'delivered');

        $this->actingAs($owner)
            ->postJson(route('admin.offline.sync'), ['commands' => [$command]])
            ->assertOk()
            ->assertJsonPath('results.0.status', OfflineOperation::STATUS_APPLIED)
            ->assertJsonPath('results.0.current_status', 'delivered');

        $this->assertSame('delivered', $order->fresh()->status);
        $this->assertNotNull($order->fresh()->delivered_at);
    }

    public function test_offline_client_captures_status_from_the_clicked_delivery_button(): void
    {
        $script = file_get_contents(public_path('assets/js/offline-workspace.js'));

        $this->assertStringContainsString('const submitter = event.submitter;', $script);
        $this->assertStringContainsString("['status', 'order_status'].includes(submitter.name)", $script);
        $this->assertStringContainsString('data.get(\'status\') || data.get(\'order_status\') || submittedButtonStatus', $script);
    }

    public function test_simple_workflow_can_sync_directly_from_assigned_to_ready(): void
    {
        [$owner, , $order] = $this->tailorOrder();
        $command = $this->statusCommand((string) Str::uuid(), $order, 'assigned', 'ready');

        $this->actingAs($owner)
            ->postJson(route('admin.offline.sync'), ['commands' => [$command]])
            ->assertOk()
            ->assertJsonPath('results.0.status', OfflineOperation::STATUS_APPLIED)
            ->assertJsonPath('results.0.current_status', 'ready');

        $this->assertSame('ready', $order->fresh()->status);
        $this->assertNotNull($order->fresh()->ready_at);
        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $order->id,
            'from_status' => 'assigned',
            'to_status' => 'ready',
        ]);
    }

    public function test_detailed_workflow_still_rejects_skipping_from_assigned_to_ready(): void
    {
        [$owner, , $order] = $this->tailorOrder();
        $owner->business->update(['tailoring_status_mode' => Business::TAILORING_STATUS_DETAILED]);
        $command = $this->statusCommand((string) Str::uuid(), $order, 'assigned', 'ready');

        $this->actingAs($owner)
            ->postJson(route('admin.offline.sync'), ['commands' => [$command]])
            ->assertOk()
            ->assertJsonPath('results.0.status', OfflineOperation::STATUS_CONFLICT)
            ->assertJsonPath('results.0.reason', 'invalid_transition');

        $this->assertSame('assigned', $order->fresh()->status);
    }

    public function test_offline_fallback_and_worker_assets_are_publicly_available(): void
    {
        $this->get(route('offline.fallback'))->assertOk()->assertSeeText('انٹرنیٹ دستیاب نہیں ہے');
        $this->assertFileExists(public_path('service-worker.js'));
        $this->assertFileExists(public_path('manifest.webmanifest'));
        $worker = file_get_contents(public_path('service-worker.js'));
        $this->assertStringContainsString("addEventListener('fetch'", $worker);
        $this->assertStringContainsString("addEventListener('push'", $worker);
        $this->assertStringContainsString('CLEAR_PRIVATE_DATA', $worker);
        $this->assertStringContainsString('PREPARE_OFFLINE_WORKSPACE', $worker);
        $this->assertStringContainsString('OFFLINE_PREPARE_PROGRESS', $worker);
        $this->assertStringContainsString('discoverRecordLinks', $worker);
        $this->assertStringContainsString('/assets/js/jquery.dataTables.min.js', $worker);
        $this->assertStringContainsString('/assets/js/form-accessibility.js', $worker);
        $this->assertStringContainsString('NotoNastaliqUrdu-VariableFont_wght.woff2', $worker);
        $this->assertStringContainsString('https://cdnjs.cloudflare.com', $worker);
        $this->assertStringContainsString('https://cdn.jsdelivr.net', $worker);
        $this->assertStringContainsString('isPrivatePageAsset', $worker);
        $this->assertStringContainsString("url.pathname.startsWith('/storage/')", $worker);

        $client = file_get_contents(public_path('assets/js/offline-workspace.js'));
        $this->assertStringContainsString('tms-offline-manifest-url', $client);
        $this->assertStringContainsString('آف لائن ورک اسپیس تیار کریں', $client);
    }

    public function test_each_visited_page_is_cached_without_full_workspace_opt_in(): void
    {
        $client = file_get_contents(public_path('assets/js/offline-workspace.js'));

        $this->assertStringContainsString(
            "localStorage.setItem(ACTIVE_ACTOR_KEY, actorKey);\n                worker?.postMessage({ type: 'CACHE_CURRENT_PAGE', url: window.location.href });\n                if (readReadiness()?.enabled) {",
            $client,
        );
    }

    private function tailorOrder(): array
    {
        $owner = User::factory()->create();
        $owner->forceFill(['tailoring_access' => true, 'is_business_owner' => true])->save();
        $owner->assignRole(Role::firstOrCreate(['name' => 'shop_owner', 'guard_name' => 'web']));
        $business = Business::firstOrCreate(
            ['owner_user_id' => $owner->id],
            [
                'name' => $owner->name,
                'tailoring_enabled' => true,
                'clothing_enabled' => false,
                'status' => Business::STATUS_ACTIVE,
            ],
        );
        $owner->forceFill(['business_id' => $business->id])->save();

        $tailor = Tailor::create([
            'name' => 'رشید محمود',
            'phone_number1' => '03001234567',
            'password' => bcrypt('Tailor@2026'),
            'user_id' => $owner->id,
        ]);
        $customer = Customers::create([
            'name' => 'فیصل محمود',
            'phone_number1' => '03005551234',
            'user_id' => $owner->id,
        ]);
        $order = Order::create([
            'customerId' => $customer->id,
            'sub_customer' => $customer->id,
            'suitQuantity' => 1,
            'totalPayment' => 2000,
            'tailorId' => $tailor->id,
            'tailor_price' => 500,
            'returnDate' => now()->addDays(2)->toDateString(),
            'userId' => $owner->id,
            'status' => 'assigned',
        ]);

        return [$owner, $tailor, $order];
    }

    private function tailorSession(Tailor $tailor): array
    {
        return [
            'tailor-login-success' => $tailor->name,
            'tailor' => 'tailor',
            'tailor_id' => $tailor->id,
        ];
    }

    private function statusCommand(string $operationId, Order $order, string $base, string $target): array
    {
        return [
            'id' => $operationId,
            'type' => 'order.status.change',
            'aggregate_id' => $order->id,
            'base_version' => $base,
            'payload' => ['status' => $target, 'note' => 'آف لائن تبدیلی'],
            'created_at' => now()->toIso8601String(),
        ];
    }
}
