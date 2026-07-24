<?php

namespace App\Http\Controllers;

use App\Models\StorefrontInquiry;
use App\Models\StorefrontTailoringService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AdminStorefrontTailoringController extends Controller
{
    public function services()
    {
        $storefront = $this->storefront();
        $services = $storefront->tailoringServices()
            ->orderBy('sort_order')
            ->latest('id')
            ->get();

        return view('storefront.admin.tailoring-services', compact('storefront', 'services'));
    }

    public function storeService(Request $request)
    {
        $storefront = $this->storefront();
        $validated = $this->validateService($request);

        $storefront->tailoringServices()->create([
            ...$validated,
            'is_featured' => $request->boolean('is_featured'),
            'is_published' => $request->boolean('is_published'),
        ]);

        return redirect()->route('admin.storefront.tailoring.services')
            ->with('success', 'ٹیلرنگ خدمت شامل ہو گئی ہے۔');
    }

    public function updateService(Request $request, StorefrontTailoringService $service)
    {
        $storefront = $this->storefront();
        abort_unless($service->storefront_id === $storefront->id, 404);
        $validated = $this->validateService($request);
        $service->update([
            ...$validated,
            'is_featured' => $request->boolean('is_featured'),
            'is_published' => $request->boolean('is_published'),
        ]);

        return redirect()->route('admin.storefront.tailoring.services')
            ->with('success', 'ٹیلرنگ خدمت محفوظ ہو گئی ہے۔');
    }

    public function inquiries(Request $request)
    {
        $storefront = $this->storefront(false);
        $filters = $request->validate([
            'status' => ['nullable', Rule::in(array_keys(StorefrontInquiry::statuses()))],
            'q' => ['nullable', 'string', 'max:100'],
        ]);
        $inquiries = $storefront->inquiries()
            ->with(['service:id,name', 'paymentVerifier:id,name,username'])
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['q'] ?? null, function ($query, $term) {
                $query->where(function ($query) use ($term) {
                    $query->where('customer_name', 'like', '%'.$term.'%')
                        ->orWhere('phone', 'like', '%'.$term.'%')
                        ->orWhere('email', 'like', '%'.$term.'%');
                });
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('storefront.admin.inquiries', [
            'storefront' => $storefront,
            'inquiries' => $inquiries,
            'filters' => $filters,
            'statuses' => StorefrontInquiry::statuses(),
        ]);
    }

    public function updateInquiry(Request $request, StorefrontInquiry $inquiry)
    {
        $storefront = $this->storefront(false);
        abort_unless($inquiry->storefront_id === $storefront->id, 404);
        $validated = $request->validate([
            'status' => ['required', Rule::in(array_keys(StorefrontInquiry::statuses()))],
            'admin_notes' => ['nullable', 'string', 'max:3000'],
        ]);
        $inquiry->update([
            ...$validated,
            'contacted_at' => $validated['status'] === StorefrontInquiry::STATUS_CONTACTED
                ? ($inquiry->contacted_at ?? now()) : $inquiry->contacted_at,
            'closed_at' => $validated['status'] === StorefrontInquiry::STATUS_CLOSED
                ? ($inquiry->closed_at ?? now()) : null,
        ]);

        return redirect()->route('admin.storefront.inquiries.index')
            ->with('success', 'درخواست کی حالت محفوظ ہو گئی ہے۔');
    }

    public function verifyInquiryPayment(Request $request, StorefrontInquiry $inquiry)
    {
        $storefront = $this->storefront(false);
        abort_unless($inquiry->storefront_id === $storefront->id, 404);
        $validated = $request->validate([
            'decision' => ['required', Rule::in([
                StorefrontInquiry::VERIFICATION_VERIFIED,
                StorefrontInquiry::VERIFICATION_REJECTED,
            ])],
            'payment_verification_notes' => [
                Rule::requiredIf($request->input('decision') === StorefrontInquiry::VERIFICATION_REJECTED),
                'nullable',
                'string',
                'max:1000',
            ],
        ]);

        DB::transaction(function () use ($inquiry, $validated) {
            $lockedInquiry = StorefrontInquiry::query()->lockForUpdate()->findOrFail($inquiry->id);
            if ($lockedInquiry->payment_method !== StorefrontInquiry::PAYMENT_EASYPAISA) {
                throw ValidationException::withMessages([
                    'payment_verification' => 'اس ادائیگی کے طریقے کے لیے دستی تصدیق درکار نہیں۔',
                ]);
            }
            if ($lockedInquiry->payment_verification_status === StorefrontInquiry::VERIFICATION_VERIFIED) {
                throw ValidationException::withMessages([
                    'payment_verification' => 'یہ ادائیگی پہلے ہی تصدیق ہو چکی ہے۔',
                ]);
            }

            $verified = $validated['decision'] === StorefrontInquiry::VERIFICATION_VERIFIED;
            $lockedInquiry->update([
                'payment_verification_status' => $validated['decision'],
                'payment_verification_notes' => $validated['payment_verification_notes'] ?? null,
                'payment_verified_by_user_id' => Auth::id(),
                'payment_verified_at' => $verified ? now() : null,
                'payment_rejected_at' => $verified ? null : now(),
            ]);
        }, 3);

        return redirect()->route('admin.storefront.inquiries.index')
            ->with('success', $validated['decision'] === StorefrontInquiry::VERIFICATION_VERIFIED
                ? 'ایزی پیسہ حوالہ تصدیق کر دیا گیا ہے۔'
                : 'ایزی پیسہ حوالہ مسترد کر دیا گیا ہے۔');
    }

    private function storefront(bool $requireTailoring = true)
    {
        $business = Auth::user()->business;
        abort_unless($business && (! $requireTailoring || $business->tailoring_enabled), 404);
        $storefront = $business->storefront()->first();
        abort_unless($storefront, 404, 'پہلے آن لائن دکان کی بنیادی معلومات محفوظ کریں۔');

        return $storefront;
    }

    private function validateService(Request $request): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:2000'],
            'price_from' => ['nullable', 'numeric', 'min:0', 'max:9999999999'],
            'price_unit' => ['required', Rule::in(['فی سوٹ', 'فی لباس', 'فی کام'])],
            'estimated_days' => ['nullable', 'integer', 'min:1', 'max:365'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_featured' => ['nullable', 'boolean'],
            'is_published' => ['nullable', 'boolean'],
        ]);
        if ($request->boolean('is_published') && ! Auth::user()->business->storefront->show_tailoring) {
            throw ValidationException::withMessages([
                'is_published' => 'پہلے آن لائن دکان کی ترتیب میں ٹیلرنگ شعبہ فعال کریں۔',
            ]);
        }

        return $validated;
    }
}
