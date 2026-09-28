<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BusinessRole;
use App\Models\Cloth;
use App\Models\Customers;
use App\Models\SaleSession;
use App\Models\User;
use App\Services\SaleSessionService;
use App\Services\ShopHubService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class SalesAgentController extends Controller
{
    public function login(Request $request, ShopHubService $hub): JsonResponse
    {
        $credentials = $request->validate([
            'login' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:100'],
        ]);

        $user = User::with(['business', 'businessRole', 'roles'])
            ->where('email', $credentials['login'])
            ->orWhere('username', $credentials['login'])
            ->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages(['login' => 'The username/email or password is incorrect.']);
        }
        if (! $user->isBusinessMember() || ! $user->hasModule(User::MODULE_CLOTHING)
            || ! $user->hasBusinessPermission(BusinessRole::CLOTHING_SALES)
            || ! $user->hasAnyRole(['business_employee', 'stock_seller', 'shop_owner'])) {
            abort(403, 'This account does not have clothing sales access.');
        }
        if ((! $user->isBusinessOwner() && ! $user->employee_active) || ! $user->business?->isActive()) {
            abort(403, 'This business account is not active.');
        }
        if ($user->must_change_password || $user->employeePasswordExpired()) {
            abort(423, 'Change this employee password in the dashboard before using the app.');
        }

        $token = $user->createToken($credentials['device_name'] ?? 'sales-agent-app', ['sales-agent'])->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $user->only(['id', 'name', 'username', 'email']),
            'permission' => BusinessRole::CLOTHING_SALES,
            'connection' => $hub->status($user),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json(['message' => 'Signed out.']);
    }

    public function customers(Request $request): JsonResponse
    {
        $query = trim((string) $request->query('q'));
        $customers = Customers::where('user_id', $request->user()->businessOwnerId())
            ->whereNull('parent_id')
            ->selectableForSales()
            ->when($query, fn ($builder) => $builder->where(function ($nested) use ($query) {
                $nested->where('name', 'like', "%{$query}%")->orWhere('phone_number1', 'like', "%{$query}%");
            }))
            ->latest('id')->limit(20)->get(['id', 'name', 'phone_number1']);

        return response()->json(['data' => $customers]);
    }

    public function inventory(Request $request): JsonResponse
    {
        $cloths = Cloth::where('user_id', $request->user()->businessOwnerId())
            ->with(['brand:id,name', 'type:id,name', 'colors:id,cloth_id,color,length'])
            ->get()->map(fn (Cloth $cloth) => [
                'id' => $cloth->id,
                'setCode' => $cloth->set_code,
                'brandId' => $cloth->cloth_brand_id,
                'brand' => $cloth->brand?->name,
                'clothTypeId' => $cloth->cloth_type_id,
                'clothType' => $cloth->type?->name,
                'salePrice' => (string) ($cloth->sale_price ?? ''),
                'colorTrackingMode' => $cloth->color_tracking_mode,
                'colors' => $cloth->colors->map->only(['color', 'length'])->values(),
            ]);

        return response()->json(['data' => $cloths]);
    }

    public function inventorySet(Request $request, string $code): JsonResponse
    {
        $normalized = str_starts_with($code, 'BNS-SET:') ? substr($code, 8) : $code;
        $cloth = Cloth::where('user_id', $request->user()->businessOwnerId())
            ->where('set_code', $normalized)
            ->with(['brand:id,name', 'type:id,name', 'colors:id,cloth_id,color,length'])
            ->firstOrFail();

        return response()->json(['data' => [
            'setCode' => $cloth->set_code,
            'brandId' => $cloth->cloth_brand_id,
            'brand' => $cloth->brand?->name,
            'clothTypeId' => $cloth->cloth_type_id,
            'clothType' => $cloth->type?->name,
            'salePrice' => (string) ($cloth->sale_price ?? ''),
            'colorTrackingMode' => $cloth->color_tracking_mode,
            'colors' => $cloth->colors->map(fn ($color) => [
                'name' => $color->color,
                'availableLength' => (string) $color->length,
            ])->values(),
        ]]);
    }

    public function show(Request $request, string $uuid, SaleSessionService $sessions): JsonResponse
    {
        $session = $this->agentSession($request, $uuid);

        return response()->json(['data' => $sessions->serialize($session)]);
    }

    public function sync(
        Request $request,
        string $uuid,
        SaleSessionService $sessions,
        ShopHubService $hub,
    ): JsonResponse
    {
        $payload = $this->validateSessionPayload($request);
        $baseRevision = (int) $payload['revision'];

        try {
            $session = $sessions->sync($request->user(), $uuid, $payload);
        } catch (ValidationException $exception) {
            $current = SaleSession::where('uuid', $uuid)->first();

            return response()->json([
                'message' => $exception->getMessage(),
                'errors' => $exception->errors(),
                'data' => $current ? $sessions->serialize($current) : null,
            ], 409);
        }

        $serialized = $sessions->serialize($session);
        $hub->recordSaleEvent(
            $request->user(),
            $session,
            'sale_session.saved',
            $payload['operationId'] ?? null,
            $request->header('X-Shop-Device-Id'),
            $baseRevision,
            $serialized,
        );

        return response()->json(['data' => $serialized, 'connection' => $hub->status($request->user())]);
    }

    private function validateSessionPayload(Request $request): array
    {
        return $request->validate([
            'revision' => ['required', 'integer', 'min:0'],
            'operationId' => ['nullable', 'uuid'],
            'customerMode' => ['required', 'in:existing,new,walk-in'],
            'customerId' => ['nullable', 'integer'],
            'customer' => ['nullable', 'array'],
            'customer.name' => ['nullable', 'string', 'max:255'],
            'customer.phone' => ['nullable', 'string', 'max:30'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.localId' => ['required', 'string', 'max:100'],
            'items.*.setCode' => ['nullable', 'string', 'max:255'],
            'items.*.brandId' => ['nullable', 'integer'],
            'items.*.brand' => ['nullable', 'string', 'max:255'],
            'items.*.clothTypeId' => ['nullable', 'integer'],
            'items.*.clothType' => ['nullable', 'string', 'max:255'],
            'items.*.color' => ['nullable', 'string', 'max:100'],
            'items.*.availableColors' => ['nullable', 'array'],
            'items.*.availableColors.*' => ['string', 'max:100'],
            'items.*.requiresColor' => ['nullable', 'boolean'],
            'items.*.quantity' => ['nullable'],
            'items.*.length' => ['nullable'],
            'items.*.unitPrice' => ['nullable'],
            'items.*.rack' => ['nullable', 'string', 'max:100'],
            'payment' => ['required', 'array'],
            'payment.method' => ['required', 'string', 'max:50'],
            'payment.receivedAmount' => ['nullable'],
            'payment.reference' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);
    }

    public function requestAttention(
        Request $request,
        string $uuid,
        SaleSessionService $sessions,
        ShopHubService $hub,
    ): JsonResponse
    {
        $baseRevision = (int) $request->input('revision', 0);
        $session = $request->has('items')
            ? DB::transaction(function () use ($request, $uuid, $sessions) {
                $payload = $this->validateSessionPayload($request);
                $current = SaleSession::where('uuid', $uuid)->where('agent_user_id', $request->user()->id)->first();
                $payload['revision'] = $current?->revision ?? 0;

                return $sessions->requestAttention(
                    $sessions->sync($request->user(), $uuid, $payload),
                    $request->user(),
                );
            })
            : $sessions->requestAttention($this->agentSession($request, $uuid), $request->user());

        $serialized = $sessions->serialize($session);
        $hub->recordSaleEvent(
            $request->user(),
            $session,
            'sale_session.forwarded',
            $request->input('operationId'),
            $request->header('X-Shop-Device-Id'),
            $baseRevision,
            $serialized,
        );

        return response()->json(['data' => $serialized, 'connection' => $hub->status($request->user())]);
    }

    public function complete(
        Request $request,
        string $uuid,
        SaleSessionService $sessions,
        ShopHubService $hub,
    ): JsonResponse
    {
        $baseRevision = (int) $request->input('revision', 0);
        $session = $request->has('items')
            ? DB::transaction(function () use ($request, $uuid, $sessions) {
                $payload = $this->validateSessionPayload($request);
                $current = SaleSession::where('uuid', $uuid)->where('agent_user_id', $request->user()->id)->first();
                $payload['revision'] = $current?->revision ?? 0;

                return $sessions->complete(
                    $sessions->sync($request->user(), $uuid, $payload),
                    $request->user(),
                );
            })
            : $sessions->complete($this->agentSession($request, $uuid), $request->user());

        $serialized = $sessions->serialize($session);
        $hub->recordSaleEvent(
            $request->user(),
            $session,
            'sale_session.completed',
            $request->input('operationId'),
            $request->header('X-Shop-Device-Id'),
            $baseRevision,
            $serialized,
        );

        return response()->json(['data' => $serialized, 'connection' => $hub->status($request->user())]);
    }

    private function agentSession(Request $request, string $uuid): SaleSession
    {
        return SaleSession::where('uuid', $uuid)
            ->where('agent_user_id', $request->user()->id)
            ->where('user_id', $request->user()->businessOwnerId())
            ->firstOrFail();
    }
}
