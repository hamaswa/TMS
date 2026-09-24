<?php

namespace App\Http\Controllers;

use App\Models\ClothBrand;
use App\Models\ClothType;
use App\Models\Customers;
use App\Models\Storefront;
use App\Models\StorefrontClothingListing;
use App\Models\StorefrontInquiry;
use App\Models\StorefrontTailoringService;
use App\Notifications\NewStorefrontTailoringBookingNotification;
use App\Services\StorefrontPaymentEvidenceService;
use App\Support\PakistanPhoneNumber;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\ValidationException;

class PublicStorefrontController extends Controller
{
    public function index(Request $request)
    {
        $publicQuery = Storefront::query()->publiclyVisible();
        $cities = (clone $publicQuery)
            ->whereNotNull('city')
            ->where('city', '!=', '')
            ->orderBy('city')
            ->distinct()
            ->pluck('city');
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'city' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', Rule::in(['clothing', 'tailoring', 'both'])],
            'delivery' => ['nullable', Rule::in(['1'])],
        ]);
        $storefronts = $publicQuery
            ->when($filters['q'] ?? null, function ($query, $term) {
                $query->where(function ($query) use ($term) {
                    $query->where('display_name', 'like', '%'.$term.'%')
                        ->orWhere('tagline', 'like', '%'.$term.'%')
                        ->orWhere('description', 'like', '%'.$term.'%')
                        ->orWhere('city', 'like', '%'.$term.'%');
                });
            })
            ->when($filters['city'] ?? null, fn ($query, $city) => $query->where('city', $city))
            ->when($filters['delivery'] ?? null, function ($query) {
                $query->where(function ($delivery) {
                    $delivery->where(function ($tailoring) {
                        $tailoring->where('show_tailoring', true)
                            ->where(function ($enabled) {
                                $enabled->where('tailoring_delivery_enabled', true)
                                    ->orWhere(function ($legacy) {
                                        $legacy->whereNull('tailoring_delivery_enabled')
                                            ->where('delivery_enabled', true);
                                    });
                            });
                    })->orWhere(function ($clothing) {
                        $clothing->where('show_clothing', true)
                            ->where(function ($enabled) {
                                $enabled->where('clothing_delivery_enabled', true)
                                    ->orWhere(function ($legacy) {
                                        $legacy->whereNull('clothing_delivery_enabled')
                                            ->where('delivery_enabled', true);
                                    });
                            });
                    });
                });
            })
            ->when($filters['category'] ?? null, function ($query, $category) {
                if (in_array($category, ['clothing', 'both'], true)) {
                    $query->where('show_clothing', true)
                        ->whereHas('business', fn ($business) => $business->where('clothing_enabled', true));
                }
                if (in_array($category, ['tailoring', 'both'], true)) {
                    $query->where('show_tailoring', true)
                        ->whereHas('business', fn ($business) => $business->where('tailoring_enabled', true));
                }
            })
            ->with('business:id,status,tailoring_enabled,clothing_enabled')
            ->latest('published_at')
            ->paginate(12)
            ->withQueryString();

        return view('storefront.public.index', compact('storefronts', 'cities', 'filters'));
    }

    public function show(Storefront $storefront)
    {
        abort_unless(
            $storefront->is_published
            && $storefront->isModerationActive()
            && $storefront->business?->isActive(),
            404
        );

        $storefront->load([
            'business',
            'clothingListings' => fn ($query) => $query
                ->where('is_published', true)
                ->whereHas('cloth', fn ($cloth) => $cloth->where('user_id', $storefront->business->owner_user_id))
                ->with(['cloth.brand', 'cloth.type', 'cloth.images', 'cloth.colors'])
                ->orderByDesc('is_featured')->orderBy('sort_order')->latest('id')->limit(8),
            'tailoringServices' => fn ($query) => $query
                ->where('is_published', true)->where('is_available', true)
                ->orderByDesc('is_featured')->orderBy('sort_order')->latest('id')->limit(4),
        ]);
        $categoryRows = $storefront->clothingListings()
            ->where('is_published', true)
            ->join('cloths', 'cloths.id', '=', 'storefront_clothing_listings.cloth_id')
            ->where('cloths.user_id', $storefront->business->owner_user_id)
            ->whereNull('cloths.deleted_at')
            ->get(['cloths.cloth_type_id', 'cloths.cloth_brand_id']);
        $clothTypes = ClothType::query()->where('user_id', $storefront->business->owner_user_id)
            ->whereIn('id', $categoryRows->pluck('cloth_type_id')->filter()->unique())->orderBy('name')->get();
        $clothBrands = ClothBrand::query()->where('user_id', $storefront->business->owner_user_id)
            ->whereIn('id', $categoryRows->pluck('cloth_brand_id')->filter()->unique())->orderBy('name')->get();

        return view('storefront.public.show', [
            'storefront' => $storefront,
            'preview' => false,
            'clothTypes' => $clothTypes,
            'clothBrands' => $clothBrands,
        ]);
    }

    public function clothing(Request $request, Storefront $storefront)
    {
        $this->ensureClothingVisible($storefront);
        $typeIds = $storefront->clothingListings()
            ->where('is_published', true)
            ->join('cloths', 'cloths.id', '=', 'storefront_clothing_listings.cloth_id')
            ->where('cloths.user_id', $storefront->business->owner_user_id)
            ->whereNull('cloths.deleted_at')
            ->whereNotNull('cloths.cloth_type_id')
            ->distinct()
            ->pluck('cloths.cloth_type_id');
        $types = ClothType::query()
            ->whereIn('id', $typeIds)
            ->orderBy('name')
            ->get(['id', 'name']);
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'color' => ['nullable', 'string', 'max:100'],
            'type' => ['nullable', 'integer', Rule::in($types->pluck('id')->all())],
            'min_price' => ['nullable', 'numeric', 'min:0', 'max:10000000'],
            'max_price' => [
                'nullable',
                'numeric',
                'min:0',
                'max:10000000',
                Rule::when($request->filled('min_price'), ['gte:min_price']),
            ],
            'availability' => ['nullable', Rule::in(['in_stock'])],
        ]);
        $listings = $storefront->clothingListings()
            ->where('is_published', true)
            ->whereHas('cloth', function ($query) use ($storefront) {
                $query->where('user_id', $storefront->business->owner_user_id);
            })
            ->when($filters['q'] ?? null, function ($query, $term) {
                $query->where(function ($query) use ($term) {
                    $query->where('public_name', 'like', '%'.$term.'%')
                        ->orWhere('description', 'like', '%'.$term.'%')
                        ->orWhereHas('cloth.brand', fn ($brand) => $brand->where('name', 'like', '%'.$term.'%'))
                        ->orWhereHas('cloth.type', fn ($type) => $type->where('name', 'like', '%'.$term.'%'));
                });
            })
            ->when($filters['color'] ?? null, fn ($query, $color) => $query->whereHas(
                'cloth.colors',
                fn ($colors) => $colors->where('color', $color)
            ))
            ->when($filters['type'] ?? null, fn ($query, $type) => $query->whereHas(
                'cloth',
                fn ($cloth) => $cloth->where('cloth_type_id', $type)
            ))
            ->when(
                array_key_exists('min_price', $filters) && $filters['min_price'] !== null,
                fn ($query) => $query->whereHas(
                    'cloth',
                    fn ($cloth) => $cloth->whereRaw(
                        "CAST(COALESCE(NULLIF(sale_price, ''), price) AS DECIMAL(12,2)) >= ?",
                        [$filters['min_price']]
                    )
                ))
            ->when(
                array_key_exists('max_price', $filters) && $filters['max_price'] !== null,
                fn ($query) => $query->whereHas(
                    'cloth',
                    fn ($cloth) => $cloth->whereRaw(
                        "CAST(COALESCE(NULLIF(sale_price, ''), price) AS DECIMAL(12,2)) <= ?",
                        [$filters['max_price']]
                    )
                ))
            ->when($filters['availability'] ?? null, fn ($query) => $query->withReservableStock())
            ->with(['cloth.brand', 'cloth.type', 'cloth.colors', 'cloth.images'])
            ->orderByDesc('is_featured')
            ->orderBy('sort_order')
            ->latest('id')
            ->paginate(12)
            ->withQueryString();
        $colors = $storefront->clothingListings()
            ->where('is_published', true)
            ->join('cloths', 'cloths.id', '=', 'storefront_clothing_listings.cloth_id')
            ->join('cloth_colors', 'cloth_colors.cloth_id', '=', 'cloths.id')
            ->where('cloths.user_id', $storefront->business->owner_user_id)
            ->whereNull('cloths.deleted_at')
            ->orderBy('cloth_colors.color')
            ->distinct()
            ->pluck('cloth_colors.color');

        return view('storefront.public.clothing.index', compact('storefront', 'listings', 'colors', 'types', 'filters'));
    }

    public function clothingShow(Storefront $storefront, StorefrontClothingListing $listing)
    {
        $this->ensureClothingVisible($storefront);
        abort_unless(
            $listing->storefront_id === $storefront->id
            && $listing->is_published
            && (int) $listing->cloth?->user_id === (int) $storefront->business->owner_user_id,
            404
        );

        return view('storefront.public.clothing.show', [
            'storefront' => $storefront,
            'listing' => $listing->load(['cloth.brand', 'cloth.type', 'cloth.colors', 'cloth.images']),
        ]);
    }

    private function ensureClothingVisible(Storefront $storefront): void
    {
        abort_unless(
            $storefront->is_published
            && $storefront->isModerationActive()
            && $storefront->show_clothing
            && $storefront->business?->isActive()
            && $storefront->business->clothing_enabled,
            404
        );
    }

    public function tailoring(Storefront $storefront)
    {
        $this->ensureTailoringVisible($storefront);
        $services = $storefront->tailoringServices()
            ->where('is_published', true)
            ->orderByDesc('is_featured')
            ->orderBy('sort_order')
            ->latest('id')
            ->get();

        return view('storefront.public.tailoring.index', compact('storefront', 'services'));
    }

    public function tailoringShow(Storefront $storefront, StorefrontTailoringService $service)
    {
        $this->ensureTailoringVisible($storefront);
        abort_unless($service->storefront_id === $storefront->id && $service->is_published, 404);

        return view('storefront.public.tailoring.show', compact('storefront', 'service'));
    }

    public function submitInquiry(
        Request $request,
        Storefront $storefront,
        StorefrontPaymentEvidenceService $evidenceService
    ) {
        $this->ensureTailoringVisible($storefront);
        abort_unless($storefront->tailoringInquiriesEnabled(), 404);
        $isBooking = $request->routeIs('storefront.tailoring.bookings.store');
        $request->mergeIfMissing(['payment_method' => StorefrontInquiry::PAYMENT_UNPAID]);
        $paymentMethod = $request->input('payment_method');
        $manualPayment = StorefrontInquiry::requiresManualVerification($paymentMethod);
        $mobileWallet = in_array($paymentMethod, [
            StorefrontInquiry::PAYMENT_EASYPAISA,
            StorefrontInquiry::PAYMENT_JAZZCASH,
        ], true);
        $validated = $request->validate([
            'tailoring_service_id' => [$isBooking ? 'required' : 'nullable', 'integer'],
            'customer_name' => ['required', 'string', 'max:150'],
            'phone' => ['required', 'string', 'min:7', 'max:50'],
            'email' => ['nullable', 'email', 'max:150'],
            'city' => ['nullable', 'string', 'max:100'],
            'preferred_date' => ['nullable', 'date', 'after_or_equal:today'],
            'measurement_method' => [
                'nullable',
                Rule::in(array_keys(StorefrontTailoringService::measurementMethodLabels())),
            ],
            'suit_quantity' => [$isBooking ? 'required' : 'nullable', 'integer', 'min:1', 'max:20'],
            'booking_pin' => [$isBooking ? 'required' : 'nullable', 'digits:6', 'confirmed'],
            'payment_claimed_amount' => [$isBooking && $manualPayment ? 'required' : 'nullable', 'numeric', 'min:0', 'max:9999999999'],
            'message' => ['nullable', 'string', 'max:3000'],
            'payment_method' => ['required', Rule::in(array_keys($storefront->acceptedInquiryPaymentMethods()))],
            'payment_sender_phone' => [
                Rule::requiredIf($mobileWallet),
                'nullable',
                'string',
                'min:7',
                'max:50',
            ],
            'payment_reference' => [
                Rule::requiredIf($manualPayment),
                'nullable',
                'string',
                'max:100',
            ],
            'payment_evidence' => [
                Rule::prohibitedIf(! $manualPayment),
                'nullable',
                File::types(['jpg', 'jpeg', 'png', 'webp', 'pdf'])->max(5 * 1024),
            ],
            'website' => ['prohibited'],
        ], [
            'preferred_date.after_or_equal' => __('storefront.messages.preferred_date'),
        ]);
        $service = null;
        if (! empty($validated['tailoring_service_id'])) {
            $service = $storefront->tailoringServices()
                ->where('is_published', true)
                ->findOrFail($validated['tailoring_service_id']);
            if (! $service->is_available || ! $service->accepts_inquiries) {
                throw ValidationException::withMessages([
                    'tailoring_service_id' => __('storefront.messages.service_not_accepting'),
                ]);
            }
            if (blank($validated['measurement_method'] ?? null)) {
                throw ValidationException::withMessages([
                    'measurement_method' => __('storefront.messages.measurement_method_required'),
                ]);
            }
            if (! in_array($validated['measurement_method'], $service->availableMeasurementMethods(), true)) {
                throw ValidationException::withMessages([
                    'measurement_method' => __('storefront.messages.measurement_method_unavailable'),
                ]);
            }
            if ($service->deposit_type !== StorefrontTailoringService::DEPOSIT_NONE
                && $validated['payment_method'] === StorefrontInquiry::PAYMENT_UNPAID) {
                throw ValidationException::withMessages([
                    'payment_method' => __('storefront.messages.deposit_payment_required'),
                ]);
            }
            if ($isBooking && $manualPayment
                && (float) ($validated['payment_claimed_amount'] ?? 0) < (float) $service->depositAmount()) {
                throw ValidationException::withMessages([
                    'payment_claimed_amount' => __('storefront.messages.deposit_amount_required', [
                        'amount' => number_format((float) $service->depositAmount(), 2),
                    ]),
                ]);
            }
            if ($service->weekly_booking_limit) {
                if (blank($validated['preferred_date'] ?? null)) {
                    throw ValidationException::withMessages([
                        'preferred_date' => __('storefront.messages.preferred_date_for_capacity'),
                    ]);
                }
            }
        }
        $customer = null;
        if ($isBooking) {
            $customer = Customers::findByPhoneForOwner(
                (int) $storefront->business->owner_user_id,
                $validated['phone']
            );
            if ($customer && (! $customer->mobile_pin || ! Hash::check($validated['booking_pin'], $customer->mobile_pin))) {
                throw ValidationException::withMessages([
                    'booking_pin' => __('storefront.messages.existing_customer_pin_invalid'),
                ]);
            }
        }
        $evidence = $request->hasFile('payment_evidence')
            ? $evidenceService->store($request->file('payment_evidence'), $storefront)
            : [];
        try {
            $inquiry = DB::transaction(function () use ($storefront, $service, $validated, $evidence, $isBooking, $customer) {
                $lockedService = $service
                    ? $storefront->tailoringServices()->lockForUpdate()->findOrFail($service->id)
                    : null;
                if ($lockedService && (! $lockedService->is_published
                    || ! $lockedService->is_available || ! $lockedService->accepts_inquiries)) {
                    throw ValidationException::withMessages([
                        'tailoring_service_id' => __('storefront.messages.service_not_accepting'),
                    ]);
                }
                if ($lockedService?->weekly_booking_limit) {
                    $preferredDate = Carbon::parse($validated['preferred_date']);
                    $booked = $lockedService->inquiries()
                        ->where('status', '!=', StorefrontInquiry::STATUS_CLOSED)
                        ->whereBetween('preferred_date', [
                            $preferredDate->copy()->startOfWeek(),
                            $preferredDate->copy()->endOfWeek(),
                        ])
                        ->count();
                    if ($booked >= $lockedService->weekly_booking_limit) {
                        throw ValidationException::withMessages([
                            'preferred_date' => __('storefront.messages.week_capacity_reached'),
                        ]);
                    }
                }

                return $storefront->inquiries()->create([
                    ...collect($validated)->except(['website', 'tailoring_service_id', 'payment_evidence', 'booking_pin', 'booking_pin_confirmation'])->all(),
                    ...$evidence,
                    'tailoring_service_id' => $lockedService?->id,
                    'customer_id' => $customer?->id,
                    'booking_pin_hash' => $isBooking ? Hash::make($validated['booking_pin']) : null,
                    'payment_claimed_amount' => (float) ($validated['payment_claimed_amount'] ?? 0),
                    'estimated_price' => $isBooking && $lockedService?->price_from !== null
                        ? round((float) $lockedService->price_from * (int) $validated['suit_quantity'], 2)
                        : null,
                    'service_deposit_type' => $lockedService?->deposit_type,
                    'service_deposit_value' => $lockedService?->deposit_value,
                    'service_deposit_amount' => $lockedService?->depositAmount(),
                    'status' => StorefrontInquiry::STATUS_NEW,
                    'payment_verification_status' => StorefrontInquiry::requiresManualVerification($validated['payment_method'])
                        ? StorefrontInquiry::VERIFICATION_PENDING
                        : StorefrontInquiry::VERIFICATION_NOT_REQUIRED,
                ]);
            });
        } catch (\Throwable $exception) {
            $evidenceService->delete($evidence);
            throw $exception;
        }

        if ($isBooking) {
            $request->session()->put($this->bookingSessionKey($inquiry), true);
            try {
                Notification::send(
                    $storefront->business->owner,
                    new NewStorefrontTailoringBookingNotification($inquiry->load('service'))
                );
            } catch (\Throwable $exception) {
                report($exception);
            }

            return redirect()->route('storefront.tailoring.bookings.show', [$storefront, $inquiry->reference])
                ->with('success', __('storefront.messages.booking_saved'));
        }

        return redirect()->route('storefront.tailoring.index', $storefront)
            ->with('inquiry_success', __('storefront.messages.inquiry_saved', [
                'reference' => $inquiry->reference,
            ]));
    }

    public function showBooking(Request $request, Storefront $storefront, string $reference)
    {
        $this->ensureTailoringVisible($storefront);
        $booking = $this->findBooking($storefront, $reference);
        $authorized = $request->session()->get($this->bookingSessionKey($booking), false);

        return view('storefront.public.tailoring.booking', compact('storefront', 'booking', 'authorized'));
    }

    public function authenticateBooking(Request $request, Storefront $storefront, string $reference)
    {
        $this->ensureTailoringVisible($storefront);
        $validated = $request->validate([
            'phone' => ['required', 'string', 'max:50'],
            'pin' => ['required', 'digits:6'],
        ]);
        $booking = $this->findBooking($storefront, $reference);
        if (PakistanPhoneNumber::normalize($validated['phone']) !== PakistanPhoneNumber::normalize($booking->phone)
            || ! Hash::check($validated['pin'], $booking->booking_pin_hash)) {
            throw ValidationException::withMessages(['phone' => __('storefront.messages.identity_invalid')]);
        }
        $request->session()->put($this->bookingSessionKey($booking), true);

        return redirect()->route('storefront.tailoring.bookings.show', [$storefront, $booking->reference]);
    }

    private function findBooking(Storefront $storefront, string $reference): StorefrontInquiry
    {
        abort_unless(preg_match('/^TMSB-(\d{6})$/', strtoupper($reference), $matches), 404);
        $booking = $storefront->inquiries()
            ->with(['service', 'order', 'customer'])
            ->findOrFail((int) $matches[1]);
        abort_unless($booking->isBooking(), 404);

        return $booking;
    }

    private function bookingSessionKey(StorefrontInquiry $booking): string
    {
        return 'storefront_tailoring_booking_access_'.$booking->id;
    }

    private function ensureTailoringVisible(Storefront $storefront): void
    {
        abort_unless(
            $storefront->is_published
            && $storefront->isModerationActive()
            && $storefront->show_tailoring
            && $storefront->business?->isActive()
            && $storefront->business->tailoring_enabled,
            404
        );
    }
}
