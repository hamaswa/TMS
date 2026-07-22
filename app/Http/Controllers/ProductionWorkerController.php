<?php

namespace App\Http\Controllers;

use App\Models\ProductionWorker;
use App\Models\WorkType;
use App\Models\WorkerCompensationPlan;
use App\Models\WorkerLedgerEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ProductionWorkerController extends Controller
{
    private const DEFAULT_WORK_TYPES = [
        'stitching' => 'سلائی',
        'cutting' => 'کٹائی',
        'embroidery' => 'کڑھائی',
        'finishing' => 'فنشنگ اور بٹن',
        'ironing' => 'استری',
        'quality_check' => 'معیار کی جانچ',
    ];

    public function index(Request $request)
    {
        $this->ensureDefaultWorkTypes();
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'relationship' => ['nullable', Rule::in(['employee', 'contractor'])],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
        ]);
        $ownerId = Auth::user()->businessOwnerId();
        $workers = ProductionWorker::where('user_id', $ownerId)
            ->with('skills')
            ->withSum('ledgerEntries as ledger_balance', 'amount')
            ->when($filters['q'] ?? null, fn ($query, $search) => $query->where(fn ($nested) => $nested
                ->where('name', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%")))
            ->when($filters['relationship'] ?? null, fn ($query, $type) => $query->where('relationship_type', $type))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('active', $status === 'active'))
            ->orderByDesc('active')->orderBy('name')->paginate(25)->withQueryString();

        return view('production-workers.index', compact('workers', 'filters'));
    }

    public function create()
    {
        $this->ensureDefaultWorkTypes();
        $workTypes = WorkType::where('user_id', Auth::user()->businessOwnerId())->where('active', true)->orderBy('name')->get();

        return view('production-workers.create', compact('workTypes'));
    }

    public function store(Request $request)
    {
        $validated = $this->validateWorker($request);
        $worker = ProductionWorker::create([
            'user_id' => Auth::user()->businessOwnerId(),
            'name' => $validated['name'],
            'phone' => $validated['phone'] ?? null,
            'email' => $validated['email'] ?? null,
            'relationship_type' => $validated['relationship_type'],
            'active' => true,
            'notes' => $validated['notes'] ?? null,
        ]);
        $worker->skills()->sync($validated['work_type_ids']);

        return redirect()->route('admin.production-workers.show', $worker)
            ->with('success', 'پروڈکشن ورکر شامل کر دیا گیا ہے۔ اب اس کی اجرت طے کریں۔');
    }

    public function show(int $worker)
    {
        $worker = $this->ownedWorker($worker)->load(['skills', 'compensationPlans.workType']);
        $entries = $worker->ledgerEntries()->with('assignment.order')->latest('entry_date')->latest('id')->paginate(30);
        $workTypes = $worker->skills()->where('active', true)->orderBy('name')->get();
        $balance = (float) $worker->ledgerEntries()->sum('amount');

        return view('production-workers.show', compact('worker', 'entries', 'workTypes', 'balance'));
    }

    public function edit(int $worker)
    {
        $worker = $this->ownedWorker($worker)->load('skills');
        $workTypes = WorkType::where('user_id', Auth::user()->businessOwnerId())->where('active', true)->orderBy('name')->get();

        return view('production-workers.edit', compact('worker', 'workTypes'));
    }

    public function update(Request $request, int $worker)
    {
        $worker = $this->ownedWorker($worker);
        $validated = $this->validateWorker($request);
        $worker->update([
            'name' => $validated['name'],
            'phone' => $validated['phone'] ?? null,
            'email' => $validated['email'] ?? null,
            'relationship_type' => $validated['relationship_type'],
            'active' => $request->boolean('active'),
            'notes' => $validated['notes'] ?? null,
        ]);
        $worker->skills()->sync($validated['work_type_ids']);

        return redirect()->route('admin.production-workers.show', $worker)->with('success', 'ورکر کی معلومات محفوظ کر دی گئی ہیں۔');
    }

    public function storeWorkType(Request $request)
    {
        $validated = $request->validate(['name' => ['required', 'string', 'max:100']]);
        $ownerId = Auth::user()->businessOwnerId();
        $base = Str::slug($validated['name']) ?: 'custom-work';
        $code = $base;
        $suffix = 1;
        while (WorkType::where('user_id', $ownerId)->where('code', $code)->exists()) {
            $code = $base.'-'.$suffix++;
        }
        WorkType::create([
            'user_id' => $ownerId, 'code' => $code, 'name' => $validated['name'],
            'category' => 'production', 'is_system' => false, 'active' => true,
        ]);

        return back()->with('success', 'نیا کام شامل کر دیا گیا ہے۔');
    }

    public function storeCompensation(Request $request, int $worker)
    {
        $worker = $this->ownedWorker($worker);
        $ownerId = Auth::user()->businessOwnerId();
        $validated = $request->validate([
            'work_type_id' => ['required', Rule::exists('work_types', 'id')->where('user_id', $ownerId)],
            'method' => ['required', Rule::in(['fixed_salary', 'per_piece', 'commission', 'hybrid'])],
            'rate' => ['nullable', 'numeric', 'min:0'],
            'fixed_salary' => ['nullable', 'numeric', 'min:0'],
            'commission_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'effective_from' => ['nullable', 'date'],
        ]);
        $rate = (float) ($validated['rate'] ?? 0);
        $salary = (float) ($validated['fixed_salary'] ?? 0);
        $commission = (float) ($validated['commission_percent'] ?? 0);
        if (! $worker->skills()->whereKey($validated['work_type_id'])->exists()) {
            throw ValidationException::withMessages(['work_type_id' => 'یہ کام اس ورکر کی مہارت میں شامل نہیں ہے۔']);
        }
        if (($validated['method'] === 'per_piece' && $rate <= 0)
            || ($validated['method'] === 'fixed_salary' && $salary <= 0)
            || ($validated['method'] === 'commission' && $commission <= 0)
            || ($validated['method'] === 'hybrid' && $salary <= 0 && $rate <= 0 && $commission <= 0)) {
            throw ValidationException::withMessages(['method' => 'منتخب طریقۂ اجرت کے لیے رقم یا فیصد درج کریں۔']);
        }

        WorkerCompensationPlan::where('production_worker_id', $worker->id)
            ->where('work_type_id', $validated['work_type_id'])->where('active', true)
            ->update(['active' => false, 'effective_to' => now()->subDay()->toDateString()]);
        WorkerCompensationPlan::create([
            'user_id' => $ownerId,
            'production_worker_id' => $worker->id,
            'work_type_id' => $validated['work_type_id'],
            'method' => $validated['method'],
            'rate' => $rate,
            'fixed_salary' => $salary,
            'commission_percent' => $commission,
            'effective_from' => $validated['effective_from'] ?? now()->toDateString(),
            'active' => true,
        ]);

        return back()->with('success', 'اجرت کا نیا اصول محفوظ کر دیا گیا ہے۔');
    }

    public function payment(Request $request, int $worker)
    {
        $worker = $this->ownedWorker($worker);
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'entry_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);
        $balance = max(0, (float) $worker->ledgerEntries()->sum('amount'));
        if ((float) $validated['amount'] > $balance) {
            throw ValidationException::withMessages(['amount' => 'ادائیگی واجب الادا رقم سے زیادہ نہیں ہو سکتی۔']);
        }
        WorkerLedgerEntry::create([
            'user_id' => Auth::user()->businessOwnerId(),
            'production_worker_id' => $worker->id,
            'entry_type' => 'payment',
            'amount' => -abs((float) $validated['amount']),
            'entry_date' => $validated['entry_date'],
            'notes' => $validated['notes'] ?? 'ورکر کو ادائیگی',
        ]);

        return back()->with('success', 'ورکر کی ادائیگی درج کر دی گئی ہے۔');
    }

    private function validateWorker(Request $request): array
    {
        $ownerId = Auth::user()->businessOwnerId();

        return $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'relationship_type' => ['required', Rule::in(['employee', 'contractor'])],
            'work_type_ids' => ['required', 'array', 'min:1'],
            'work_type_ids.*' => ['integer', Rule::exists('work_types', 'id')->where('user_id', $ownerId)],
            'notes' => ['nullable', 'string', 'max:1000'],
            'active' => ['nullable', 'boolean'],
        ]);
    }

    private function ownedWorker(int $id): ProductionWorker
    {
        return ProductionWorker::where('user_id', Auth::user()->businessOwnerId())->findOrFail($id);
    }

    private function ensureDefaultWorkTypes(): void
    {
        $ownerId = Auth::user()->businessOwnerId();
        foreach (self::DEFAULT_WORK_TYPES as $code => $name) {
            WorkType::firstOrCreate(
                ['user_id' => $ownerId, 'code' => $code],
                ['name' => $name, 'category' => 'production', 'is_system' => true, 'active' => true],
            );
        }
    }
}
