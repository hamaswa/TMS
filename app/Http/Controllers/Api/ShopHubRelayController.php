<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ShopHubRelayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShopHubRelayController extends Controller
{
    public function store(Request $request, ShopHubRelayService $relay): JsonResponse
    {
        abort_if(config('shop_hub.mode') === 'hub', 409, 'A Shop Hub cannot act as its own cloud relay.');

        $validated = $request->validate([
            'events' => ['required', 'array', 'max:100'],
            'events.*.eventUuid' => ['required', 'uuid'],
            'events.*.businessOwnerUserId' => ['required', 'integer', 'min:1'],
            'events.*.actorUserId' => ['required', 'integer', 'min:1'],
            'events.*.deviceId' => ['nullable', 'string', 'max:100'],
            'events.*.eventType' => ['required', 'in:sale_session.saved,sale_session.forwarded,sale_session.claimed,sale_session.completed'],
            'events.*.aggregateUuid' => ['required', 'uuid'],
            'events.*.baseRevision' => ['nullable', 'integer', 'min:0'],
            'events.*.resultingRevision' => ['nullable', 'integer', 'min:0'],
            'events.*.payload' => ['required', 'array'],
            'events.*.occurredAt' => ['required', 'date'],
        ]);

        return response()->json([
            'results' => collect($validated['events'])
                ->map(fn (array $event) => $relay->apply($event))
                ->values(),
            'serverTime' => now()->toIso8601String(),
        ]);
    }
}
