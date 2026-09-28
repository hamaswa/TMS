<?php

namespace App\Console\Commands;

use App\Models\ShopHubEvent;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class SyncShopHubEvents extends Command
{
    protected $signature = 'shop-hub:sync';

    protected $description = 'Relay pending Shop Hub events to the BuyNStitch cloud';

    public function handle(): int
    {
        if (config('shop_hub.mode') !== 'hub') {
            $this->components->info('This installation is not configured as a Shop Hub.');

            return self::SUCCESS;
        }

        $cloudUrl = rtrim((string) config('shop_hub.cloud_url'), '/');
        $syncKey = (string) config('shop_hub.sync_key');
        if ($cloudUrl === '' || $syncKey === '') {
            $this->components->error('SHOP_HUB_CLOUD_URL and SHOP_HUB_SYNC_KEY are required.');

            return self::FAILURE;
        }

        $events = ShopHubEvent::where('status', ShopHubEvent::STATUS_PENDING)
            ->orderBy('id')
            ->limit(max(1, min(100, (int) config('shop_hub.batch_size', 50))))
            ->get();
        if ($events->isEmpty()) {
            $this->components->info('No pending Shop Hub events.');

            return self::SUCCESS;
        }

        $payload = ['events' => $events->map(fn (ShopHubEvent $event) => [
            'eventUuid' => $event->event_uuid,
            'businessOwnerUserId' => $event->business_owner_user_id,
            'actorUserId' => $event->actor_user_id,
            'deviceId' => $event->device_id,
            'eventType' => $event->event_type,
            'aggregateUuid' => $event->aggregate_uuid,
            'baseRevision' => $event->base_revision,
            'resultingRevision' => $event->resulting_revision,
            'payload' => $event->payload,
            'occurredAt' => $event->occurred_at->toIso8601String(),
        ])->values()->all()];
        $body = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        $timestamp = (string) now()->timestamp;
        $signature = hash_hmac('sha256', $timestamp.'.'.$body, $syncKey);

        try {
            $response = Http::timeout(15)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'X-Shop-Hub-Timestamp' => $timestamp,
                    'X-Shop-Hub-Signature' => $signature,
                ])
                ->withBody($body, 'application/json')
                ->post($cloudUrl.'/api/shop-hub/relay')
                ->throw();
        } catch (\Throwable $exception) {
            $this->components->error('Cloud relay unavailable: '.$exception->getMessage());

            return self::FAILURE;
        }

        $results = collect($response->json('results', []))->keyBy('eventUuid');
        foreach ($events as $event) {
            $result = $results->get($event->event_uuid);
            if (! $result) {
                continue;
            }
            $event->forceFill([
                'status' => $result['status'],
                'synced_at' => $result['status'] === ShopHubEvent::STATUS_SYNCED ? now() : null,
            ])->save();
        }

        $this->components->info(sprintf(
            'Processed %d event(s): %d synced, %d conflict(s).',
            $events->count(),
            $results->where('status', ShopHubEvent::STATUS_SYNCED)->count(),
            $results->where('status', ShopHubEvent::STATUS_CONFLICT)->count(),
        ));

        return self::SUCCESS;
    }
}
