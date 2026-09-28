<?php

namespace App\Services;

use App\Models\SaleSession;
use App\Models\ShopHubEvent;
use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ShopHubService
{
    public function isHub(): bool
    {
        return config('shop_hub.mode') === 'hub';
    }

    public function status(?User $user = null): array
    {
        $pending = null;
        if ($this->isHub() && $user && Schema::hasTable('shop_hub_events')) {
            $pending = ShopHubEvent::where('business_owner_user_id', $user->businessOwnerId())
                ->where('status', ShopHubEvent::STATUS_PENDING)
                ->count();
        }

        return [
            'mode' => $this->isHub() ? 'shop' : 'cloud',
            'hubId' => $this->isHub() ? config('shop_hub.id') : null,
            'hubName' => $this->isHub() ? config('shop_hub.name') : null,
            'shopOwnerId' => $user?->businessOwnerId(),
            'withinShopSync' => $this->isHub(),
            'cloudConfigured' => filled(config('shop_hub.cloud_url')),
            'pendingCloudEvents' => $pending,
            'serverTime' => now()->toIso8601String(),
        ];
    }

    public function recordSaleEvent(
        User $actor,
        SaleSession $session,
        string $eventType,
        ?string $eventUuid,
        ?string $deviceId,
        ?int $baseRevision,
        array $payload,
    ): ?ShopHubEvent {
        if (! $this->isHub() || ! Schema::hasTable('shop_hub_events')) {
            return null;
        }

        return ShopHubEvent::firstOrCreate(
            ['event_uuid' => $eventUuid ?: (string) Str::uuid()],
            [
                'business_owner_user_id' => $actor->businessOwnerId(),
                'actor_user_id' => $actor->id,
                'device_id' => $deviceId,
                'event_type' => $eventType,
                'aggregate_type' => 'sale_session',
                'aggregate_uuid' => $session->uuid,
                'base_revision' => $baseRevision,
                'resulting_revision' => $session->revision,
                'payload' => $payload,
                'status' => ShopHubEvent::STATUS_PENDING,
                'occurred_at' => now(),
            ],
        );
    }
}
