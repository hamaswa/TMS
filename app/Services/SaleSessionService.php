<?php

namespace App\Services;

use App\Models\Business;
use App\Models\BusinessRole;
use App\Models\Cloth;
use App\Models\SaleSession;
use App\Models\User;
use App\Notifications\SaleSessionAttentionNotification;
use App\Notifications\SaleSessionCompletedNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaleSessionService
{
    public function __construct(private readonly CounterSaleService $counterSales) {}

    public function serialize(SaleSession $session): array
    {
        $session->loadMissing(['agent:id,name', 'claimedBy:id,name', 'completedBy:id,name', 'receipt:id,receipt_number']);

        return [
            'uuid' => $session->uuid,
            'status' => $session->status,
            'revision' => $session->revision,
            'customerMode' => $session->customer_mode,
            'customerId' => $session->customer_id,
            'customer' => $session->customer_data,
            'items' => $session->items ?? [],
            'payment' => $session->payment_data ?? [],
            'note' => $session->note,
            'agent' => $session->agent?->only(['id', 'name']),
            'claimedBy' => $session->claimedBy?->only(['id', 'name']),
            'completedBy' => $session->completedBy?->only(['id', 'name']),
            'receipt' => $session->receipt?->only(['id', 'receipt_number']),
            'lastSyncedAt' => $session->last_synced_at?->toIso8601String(),
            'updatedAt' => $session->updated_at?->toIso8601String(),
        ];
    }

    public function sync(User $agent, string $uuid, array $payload): SaleSession
    {
        return DB::transaction(function () use ($agent, $uuid, $payload) {
            $ownerId = $agent->businessOwnerId();
            $session = SaleSession::where('uuid', $uuid)->lockForUpdate()->first();

            if (! $session) {
                $session = new SaleSession([
                    'uuid' => $uuid,
                    'user_id' => $ownerId,
                    'agent_user_id' => $agent->id,
                    'status' => SaleSession::STATUS_ACTIVE,
                    'revision' => 0,
                ]);
            } elseif ($session->user_id !== $ownerId || $session->agent_user_id !== $agent->id) {
                abort(404);
            }

            if ($session->isTerminal()) {
                return $session;
            }
            if ($session->claimed_by_user_id && $session->claimed_by_user_id !== $agent->id) {
                abort(423, 'This sale is being handled on the dashboard.');
            }

            $clientRevision = (int) ($payload['revision'] ?? 0);
            if ($session->exists && $clientRevision !== $session->revision) {
                throw ValidationException::withMessages([
                    'revision' => 'This sale changed elsewhere. Refresh before editing.',
                ]);
            }

            $session->fill([
                'customer_mode' => $payload['customerMode'],
                'customer_id' => $payload['customerId'] ?? null,
                'customer_data' => $payload['customer'] ?? [],
                'items' => $payload['items'],
                'payment_data' => $payload['payment'],
                'note' => $payload['note'] ?? null,
                'last_synced_at' => now(),
                'revision' => $session->revision + 1,
            ])->save();

            return $session->fresh();
        });
    }

    public function requestAttention(SaleSession $session, User $agent): SaleSession
    {
        abort_unless($session->agent_user_id === $agent->id && $session->user_id === $agent->businessOwnerId(), 404);
        abort_if($session->isTerminal(), 409, 'This sale is already closed.');

        $session->forceFill([
            'status' => SaleSession::STATUS_NEEDS_ATTENTION,
            'attention_requested_at' => now(),
        ])->save();

        foreach ($this->attentionRecipients($session) as $recipient) {
            if ($recipient->id !== $agent->id) {
                $recipient->notify(new SaleSessionAttentionNotification($session->loadMissing('agent')));
            }
        }

        return $session->fresh();
    }

    public function claim(SaleSession $session, User $actor): SaleSession
    {
        abort_unless($session->user_id === $actor->businessOwnerId(), 404);
        abort_if($session->isTerminal(), 409, 'This sale is already closed.');

        return DB::transaction(function () use ($session, $actor) {
            $locked = SaleSession::whereKey($session->id)->lockForUpdate()->firstOrFail();
            if ($locked->claimed_by_user_id && $locked->claimed_by_user_id !== $actor->id) {
                abort(409, 'Another user already claimed this sale.');
            }
            $locked->forceFill([
                'status' => SaleSession::STATUS_CLAIMED,
                'claimed_by_user_id' => $actor->id,
                'claimed_at' => now(),
            ])->save();

            return $locked->fresh();
        });
    }

    public function complete(SaleSession $session, User $actor): SaleSession
    {
        return $this->completeWithValidated(
            $session,
            $actor,
            $this->counterSales->validate($this->counterSalePayload($session)),
        );
    }

    public function completeWithValidated(SaleSession $session, User $actor, array $validated): SaleSession
    {
        abort_unless($session->user_id === $actor->businessOwnerId(), 404);

        $completed = DB::transaction(function () use ($session, $actor, $validated) {
            $locked = SaleSession::whereKey($session->id)->lockForUpdate()->firstOrFail();
            if ($locked->status === SaleSession::STATUS_COMPLETED) {
                return $locked;
            }
            abort_if($locked->status === SaleSession::STATUS_CANCELLED, 409, 'This sale was cancelled.');
            if ($locked->claimed_by_user_id && $locked->claimed_by_user_id !== $actor->id) {
                abort(409, 'Another user already claimed this sale.');
            }

            $result = $this->counterSales->complete($actor, $validated);
            $locked->forceFill([
                'status' => SaleSession::STATUS_COMPLETED,
                'completed_by_user_id' => $actor->id,
                'counter_sale_receipt_id' => $result['receipt']->id,
                'completed_at' => now(),
                'revision' => $locked->revision + 1,
            ])->save();

            return $locked->fresh(['completedBy', 'receipt']);
        });

        if ($completed->agent_user_id !== $actor->id && ! $completed->agent->notifications()
            ->where('type', SaleSessionCompletedNotification::class)
            ->where('data->sale_session_uuid', $completed->uuid)->exists()) {
            $completed->agent->notify(new SaleSessionCompletedNotification($completed));
        }

        return $completed;
    }

    private function counterSalePayload(SaleSession $session): array
    {
        $items = collect($session->items ?? [])->map(function (array $item) use ($session) {
            if (! empty($item['brandId']) && ! empty($item['clothTypeId'])) {
                return $item;
            }

            $cloth = Cloth::where('user_id', $session->user_id)
                ->when($item['brand'] ?? null, fn ($query, $brand) => $query->whereHas('brand', fn ($brandQuery) => $brandQuery->where('name', $brand)))
                ->when($item['clothType'] ?? null, fn ($query, $type) => $query->whereHas('type', fn ($typeQuery) => $typeQuery->where('name', $type)))
                ->first();

            $item['brandId'] = $cloth?->cloth_brand_id;
            $item['clothTypeId'] = $cloth?->cloth_type_id;

            return $item;
        });
        $customer = $session->customer_data ?? [];
        $payment = $session->payment_data ?? [];
        $mode = $session->customer_mode === 'existing' ? 'regular' : 'random';

        return [
            'brand_name' => $items->pluck('brandId')->all(),
            'cloth_type' => $items->pluck('clothTypeId')->all(),
            'color' => $items->pluck('color')->all(),
            'clothes_rack' => $items->pluck('rack')->all(),
            'length' => $items->map(fn ($item) => $item['length'] ?: ($item['quantity'] ?? null))->all(),
            'item_total' => $items->map(fn ($item) => (float) ($item['quantity'] ?? 1) * (float) ($item['unitPrice'] ?? 0))->all(),
            'customer_mode' => $mode,
            'existing_customer_id' => $session->customer_id,
            'random_customer_name' => $customer['name'] ?? ($session->customer_mode === 'walk-in' ? 'Walk-in Customer' : null),
            'random_customer_phone' => $customer['phone'] ?? null,
            'payment' => (float) ($payment['receivedAmount'] ?? 0),
            'payment_method' => strtolower(str_replace(' ', '_', $payment['method'] ?? 'cash')),
            'payment_reference' => $payment['reference'] ?? null,
            'paid_on' => now()->toDateString(),
        ];
    }

    private function attentionRecipients(SaleSession $session): Collection
    {
        $business = Business::where('owner_user_id', $session->user_id)->with(['owner', 'members.businessRole'])->first();
        if (! $business) {
            return User::whereKey($session->user_id)->get();
        }

        return collect([$business->owner])->merge($business->members)
            ->filter(fn (?User $user) => $user && $user->hasBusinessPermission(BusinessRole::CLOTHING_SALES))
            ->unique('id')->values();
    }
}
