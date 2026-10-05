<?php

namespace App\Http\Controllers;

use App\Models\MeasurementField;
use App\Models\MeasurementTemplate;
use App\Models\Storefront;
use App\Models\StorefrontTailoringService;
use App\Models\StandardMeasurementProfile;
use App\Services\MeasurementService;
use App\Services\StorefrontCartService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PublicStorefrontTailoringCartController extends Controller
{
    public function store(
        Request $request,
        Storefront $storefront,
        StorefrontTailoringService $service,
        StorefrontCartService $carts,
    ) {
        $this->ensureVisible($storefront);
        abort_unless(
            $service->storefront_id === $storefront->id
            && $service->is_published && $service->is_available && $service->accepts_inquiries,
            404
        );
        if ($service->price_from === null) {
            throw ValidationException::withMessages([
                'tailoring' => __('storefront.messages.tailoring_price_required'),
            ]);
        }

        $validated = $request->validate([
            'measurement_method' => ['required', Rule::in($service->availableMeasurementMethods())],
            'standard_measurement_profile_id' => ['nullable', 'integer'],
            'quantity' => ['required', 'integer', 'min:1', 'max:20'],
            'clothing_cart_item_id' => ['nullable', 'integer'],
            'measurements' => ['nullable', 'array'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'preferred_date' => ['nullable', 'date', 'after_or_equal:today'],
        ]);

        $method = $validated['measurement_method'];
        $template = $this->template($storefront, $service);
        $standardProfile = null;
        $measurementValues = [];
        if ($method === StorefrontTailoringService::MEASUREMENT_CUSTOM) {
            $measurementValues = $this->customMeasurementRows($request, $storefront, $template);
        } elseif ($method === StorefrontTailoringService::MEASUREMENT_STANDARD_SIZE) {
            $standardProfile = StandardMeasurementProfile::query()
                ->where('id', $validated['standard_measurement_profile_id'] ?? 0)
                ->where('user_id', $storefront->business->owner_user_id)
                ->where('measurement_template_id', $template?->id)
                ->where('is_active', true)
                ->first();
            if (! $standardProfile) {
                throw ValidationException::withMessages([
                    'standard_measurement_profile_id' => __('storefront.messages.select_standard_size'),
                ]);
            }
            $measurementValues = $standardProfile->measurement_values;
        }

        [$cart, $plainToken] = $carts->getOrCreate(
            $storefront,
            $request->session()->get($this->sessionKey($storefront))
        );
        $clothingItemId = $validated['clothing_cart_item_id'] ?? null;
        if ($clothingItemId && ! $cart->items()->whereKey($clothingItemId)->exists()) {
            throw ValidationException::withMessages([
                'clothing_cart_item_id' => __('storefront.messages.cloth_item_unavailable'),
            ]);
        }

        $cart->tailoringItems()->create([
            'tailoring_service_id' => $service->id,
            'clothing_cart_item_id' => $clothingItemId,
            'measurement_template_id' => $template?->id,
            'standard_measurement_profile_id' => $standardProfile?->id,
            'measurement_method' => $method,
            'standard_size' => $method === StorefrontTailoringService::MEASUREMENT_STANDARD_SIZE
                ? $standardProfile?->name : null,
            'quantity' => $validated['quantity'],
            'unit_price_snapshot' => $service->price_from,
            'measurement_values' => $measurementValues,
            'notes' => $validated['notes'] ?? null,
            'preferred_date' => $validated['preferred_date'] ?? null,
        ]);
        $cart->update(['expires_at' => now()->addDay(), 'last_activity_at' => now()]);
        $request->session()->put($this->sessionKey($storefront), $plainToken);

        return redirect()->route('storefront.cart.show', $storefront)
            ->with('success', __('storefront.messages.tailoring_added'));
    }

    public function destroy(
        Request $request,
        Storefront $storefront,
        int $item,
        StorefrontCartService $carts,
    ) {
        $this->ensureVisible($storefront);
        $cart = $carts->find($storefront, $request->session()->get($this->sessionKey($storefront)));
        abort_unless($cart && $cart->tailoringItems()->whereKey($item)->exists(), 404);
        $cart->tailoringItems()->whereKey($item)->delete();
        $cart->update(['last_activity_at' => now()]);

        return redirect()->route('storefront.cart.show', $storefront)
            ->with('success', __('storefront.messages.cart_removed'));
    }

    private function customMeasurementRows(Request $request, Storefront $storefront, ?MeasurementTemplate $template): array
    {
        if (! $template) {
            throw ValidationException::withMessages(['measurement_method' => __('storefront.messages.measurement_template_required')]);
        }
        $values = $request->input('measurements', []);
        $rows = [];
        foreach ($template->system_fields ?? [] as $key) {
            $meta = MeasurementService::SYSTEM_FIELDS[$key] ?? null;
            if (! $meta) continue;
            $value = trim((string) data_get($values, 'system.'.$key, ''));
            if ($value === '') {
                throw ValidationException::withMessages(['measurements.system.'.$key => __('storefront.messages.measurement_required', ['field' => $meta['label']])]);
            }
            $rows[] = ['source_key' => 'system.'.$key, 'label' => $meta['label'], 'value' => $value, 'unit' => $meta['unit']];
        }
        $fields = MeasurementField::query()
            ->where('user_id', $storefront->business->owner_user_id)
            ->where('is_active', true)
            ->whereIn('id', array_map('intval', $template->custom_field_ids ?? []))
            ->orderBy('sort_order')->get();
        foreach ($fields as $field) {
            $value = trim((string) data_get($values, 'custom.'.$field->id, ''));
            if ($field->is_required && $value === '') {
                throw ValidationException::withMessages(['measurements.custom.'.$field->id => __('storefront.messages.measurement_required', ['field' => $field->label])]);
            }
            if ($value === '') continue;
            if ($field->field_type === 'number' && (! is_numeric($value) || (float) $value < 0)) {
                throw ValidationException::withMessages(['measurements.custom.'.$field->id => __('storefront.messages.measurement_invalid', ['field' => $field->label])]);
            }
            if ($field->field_type === 'select' && ! in_array($value, $field->options ?? [], true)) {
                throw ValidationException::withMessages(['measurements.custom.'.$field->id => __('storefront.messages.measurement_invalid', ['field' => $field->label])]);
            }
            $rows[] = ['source_key' => 'custom.'.$field->id, 'label' => $field->label, 'value' => $value, 'unit' => $field->unit === 'none' ? '' : $field->unit];
        }

        return $rows;
    }

    private function template(Storefront $storefront, StorefrontTailoringService $service): ?MeasurementTemplate
    {
        return $service->measurement_template_id
            ? MeasurementTemplate::where('user_id', $storefront->business->owner_user_id)->where('is_active', true)->find($service->measurement_template_id)
            : null;
    }

    private function ensureVisible(Storefront $storefront): void
    {
        abort_unless($storefront->is_published && $storefront->isModerationActive()
            && $storefront->show_tailoring && $storefront->tailoringInquiriesEnabled()
            && $storefront->business?->isActive() && $storefront->business->tailoring_enabled, 404);
    }

    private function sessionKey(Storefront $storefront): string { return 'storefront_cart_token_'.$storefront->id; }
}
