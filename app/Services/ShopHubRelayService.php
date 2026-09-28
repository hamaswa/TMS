<?php

namespace App\Services;

use App\Models\BusinessRole;
use App\Models\SaleSession;
use App\Models\ShopHubEvent;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class ShopHubRelayService
{
    public function __construct(private readonly SaleSessionService $sessions) {}

    public function apply(array $event): array
    {
        $existingEvent = ShopHubEvent::where('event_uuid', $event['eventUuid'])->first();
        if ($existingEvent) {
            return [
                'eventUuid' => $event['eventUuid'],
                'status' => $existingEvent->status,
                'alreadyProcessed' => true,
            ];
        }

        try {
            return DB::transaction(function () use ($event) {
                $actor = User::find($event['actorUserId']);
                if (! $actor
                    || $actor->businessOwnerId() !== (int) $event['businessOwnerUserId']
                    || ! $actor->hasBusinessPermission(BusinessRole::CLOTHING_SALES)) {
                    throw new RuntimeException('actor_unavailable');
                }

                $session = SaleSession::where('uuid', $event['aggregateUuid'])->lockForUpdate()->first();
                $baseRevision = $event['baseRevision'];
                if ($session && $baseRevision !== null && $session->revision !== (int) $baseRevision) {
                    throw new RuntimeException('revision_conflict');
                }

                $payload = $this->sessionPayload($event['payload'], $session?->revision ?? 0);
                $session = match ($event['eventType']) {
                    'sale_session.saved' => $this->sessions->sync($actor, $event['aggregateUuid'], $payload),
                    'sale_session.forwarded' => $this->sessions->requestAttention(
                        $this->sessions->sync($actor, $event['aggregateUuid'], $payload),
                        $actor,
                    ),
                    'sale_session.completed' => $this->sessions->complete(
                        $session && $session->agent_user_id !== $actor->id
                            ? $session
                            : $this->sessions->sync($actor, $event['aggregateUuid'], $payload),
                        $actor,
                    ),
                    'sale_session.claimed' => $this->sessions->claim(
                        $session ?: throw new RuntimeException('session_unavailable'),
                        $actor,
                    ),
                    default => throw new RuntimeException('unsupported_event'),
                };

                if ($event['resultingRevision'] !== null
                    && $session->revision !== (int) $event['resultingRevision']) {
                    throw new RuntimeException('resulting_revision_mismatch');
                }

                ShopHubEvent::create([
                    'event_uuid' => $event['eventUuid'],
                    'business_owner_user_id' => $event['businessOwnerUserId'],
                    'actor_user_id' => $event['actorUserId'],
                    'device_id' => $event['deviceId'] ?? null,
                    'event_type' => $event['eventType'],
                    'aggregate_type' => 'sale_session',
                    'aggregate_uuid' => $event['aggregateUuid'],
                    'base_revision' => $event['baseRevision'],
                    'resulting_revision' => $session->revision,
                    'payload' => $event['payload'],
                    'status' => ShopHubEvent::STATUS_SYNCED,
                    'occurred_at' => $event['occurredAt'],
                    'synced_at' => now(),
                ]);

                return [
                    'eventUuid' => $event['eventUuid'],
                    'status' => ShopHubEvent::STATUS_SYNCED,
                    'resultingRevision' => $session->revision,
                ];
            });
        } catch (Throwable $exception) {
            ShopHubEvent::create([
                'event_uuid' => $event['eventUuid'],
                'business_owner_user_id' => $event['businessOwnerUserId'],
                'actor_user_id' => $event['actorUserId'],
                'device_id' => $event['deviceId'] ?? null,
                'event_type' => $event['eventType'],
                'aggregate_type' => 'sale_session',
                'aggregate_uuid' => $event['aggregateUuid'],
                'base_revision' => $event['baseRevision'],
                'resulting_revision' => $event['resultingRevision'],
                'payload' => $event['payload'],
                'status' => ShopHubEvent::STATUS_CONFLICT,
                'occurred_at' => $event['occurredAt'],
            ]);

            return [
                'eventUuid' => $event['eventUuid'],
                'status' => ShopHubEvent::STATUS_CONFLICT,
                'reason' => $exception->getMessage(),
            ];
        }
    }

    private function sessionPayload(array $serialized, int $revision): array
    {
        return [
            'revision' => $revision,
            'customerMode' => $serialized['customerMode'],
            'customerId' => $serialized['customerId'] ?? null,
            'customer' => $serialized['customer'] ?? [],
            'items' => $serialized['items'] ?? [],
            'payment' => $serialized['payment'] ?? [],
            'note' => $serialized['note'] ?? null,
        ];
    }
}
