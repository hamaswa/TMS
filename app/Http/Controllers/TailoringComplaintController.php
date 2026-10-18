<?php

namespace App\Http\Controllers;

use App\Models\BusinessRole;
use App\Models\CounterOrderComplaint;
use App\Models\Order;
use App\Models\Tailor;
use App\Models\TailoringComplaintEvidence;
use App\Models\TailoringRecovery;
use App\Services\TailoringComplaintService;
use App\Support\PaymentMethods;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class TailoringComplaintController extends Controller
{
    public function acknowledge(CounterOrderComplaint $complaint)
    {
        $complaint->update(['customer_acknowledged_at' => $complaint->customer_acknowledged_at ?: now()]);
        return response()->view('tailoring-complaints.acknowledged', compact('complaint'));
    }
    public function decide(Request $request, CounterOrderComplaint $complaint, TailoringComplaintService $service)
    {
        $claim = $this->owned($request, $complaint);
        $data = $request->validate([
            'category' => ['required', Rule::in(['stitching_defect', 'measurement_error', 'cloth_damage', 'fabric_defect', 'shop_error', 'customer_change', 'other'])],
            'inspection_notes' => ['required', 'string', 'min:10', 'max:5000'],
            'responsible_party' => ['required', Rule::in(['tailor', 'cutter', 'shop', 'customer', 'fabric', 'undetermined'])],
            'responsible_tailor_id' => ['nullable', 'integer'],
            'resolution' => ['required', Rule::in(['rework', 'replacement', 'refund', 'compensation', 'refund_and_compensation', 'rejected', 'withdrawn'])],
            'decision_notes' => ['required', 'string', 'min:5', 'max:5000'],
            'rework_due_on' => ['nullable', 'date', 'after_or_equal:today'],
        ]);
        if (! empty($data['responsible_tailor_id'])) {
            Tailor::where('user_id', $request->user()->businessOwnerId())->findOrFail($data['responsible_tailor_id']);
        }
        $service->decide($claim, $request->user(), $data);
        return back()->with('success', 'شکایت کی جانچ، ذمہ داری اور فیصلہ محفوظ ہو گیا ہے۔');
    }

    public function recover(Request $request, CounterOrderComplaint $complaint, TailoringComplaintService $service)
    {
        $this->authorizeFinancialApproval($request);
        $claim = $this->owned($request, $complaint);
        $data = $request->validate([
            'method' => ['required', Rule::in(TailoringRecovery::METHODS)],
            'tailor_id' => ['nullable', 'integer'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);
        if ($data['method'] !== 'shop_absorbed' && empty($data['tailor_id'])) {
            return back()->withErrors(['tailor_id' => 'ریکوری کے لیے درزی منتخب کریں۔'])->withInput();
        }
        $service->recover($claim, $request->user(), $data);
        return back()->with('success', 'نقصان کی ریکوری الگ کھاتے میں محفوظ ہو گئی ہے۔');
    }

    public function evidence(Request $request, CounterOrderComplaint $complaint)
    {
        $claim = $this->owned($request, $complaint);
        abort_if($claim->closed_at, 409, 'Closed complaint evidence is immutable.');
        $data = $request->validate(['evidence' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120']]);
        $file = $data['evidence'];
        $path = $file->store('tailoring-complaints/'.$claim->id, 'public');
        $claim->evidence()->create(['uploaded_by_user_id' => $request->user()->id, 'disk' => 'public', 'path' => $path,
            'original_name' => $file->getClientOriginalName(), 'mime_type' => $file->getMimeType(), 'size' => $file->getSize()]);
        return back()->with('success', 'ثبوت محفوظ ہو گیا ہے۔');
    }

    public function download(Request $request, CounterOrderComplaint $complaint, TailoringComplaintEvidence $evidence)
    {
        $claim = $this->owned($request, $complaint);
        abort_unless($evidence->complaint_id === $claim->id, 404);
        return Storage::disk($evidence->disk)->download($evidence->path, $evidence->original_name);
    }

    public function completeRework(Request $request, CounterOrderComplaint $complaint, TailoringComplaintService $service)
    {
        $data = $request->validate(['completion_note' => ['required', 'string', 'min:5', 'max:3000']]);
        $service->completeRework($this->owned($request, $complaint), $request->user(), $data['completion_note']);
        return back()->with('success', 'دوبارہ کام مکمل اور شکایت بند کر دی گئی ہے۔');
    }

    public function cancelOrder(Request $request, Order $order, TailoringComplaintService $service)
    {
        $this->authorizeFinancialApproval($request);
        abort_unless((int) $order->userId === $request->user()->businessOwnerId(), 404);
        $data = $request->validate([
            'reason' => ['required', 'string', 'min:10', 'max:3000'],
            'cancellation_charge' => ['nullable', 'numeric', 'min:0'],
            'compensation' => ['nullable', 'numeric', 'min:0'],
            'applied_to_other_balance' => ['nullable', 'numeric', 'min:0'],
            'cash_refund' => ['nullable', 'numeric', 'min:0'],
            'retained_credit' => ['nullable', 'numeric', 'min:0'],
            'payment_method' => ['nullable', Rule::in(array_keys(PaymentMethods::LABELS))],
            'payment_reference' => ['nullable', 'string', 'max:255'],
        ]);
        $service->cancelOrder($order, $request->user(), $data);
        return back()->with('success', 'آرڈر منسوخ، تاریخ محفوظ اور ضروری کھاتا واپس کر دیا گیا ہے۔');
    }

    private function owned(Request $request, CounterOrderComplaint $complaint): CounterOrderComplaint
    {
        abort_unless($request->user()?->hasBusinessPermission(BusinessRole::TAILORING_ORDERS), 403);
        return CounterOrderComplaint::where('user_id', $request->user()->businessOwnerId())->findOrFail($complaint->id);
    }

    private function authorizeFinancialApproval(Request $request): void
    {
        abort_unless($request->user()?->isBusinessOwner(), 403, 'Only the business owner can approve refunds, cancellations, or recoveries.');
    }
}
