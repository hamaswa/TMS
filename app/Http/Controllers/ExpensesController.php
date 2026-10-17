<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Workers;
use App\Models\Expenses;
use Illuminate\Http\Request;
use App\Models\DaliyExpenses;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ExpensesController extends Controller
{
    /**
     * Monthly expenses: rent/bills and staff salaries, with an optional date filter.
     */
    public function index(Request $request)
    {
        $ownerId = auth()->user()->businessOwnerId();
        $filters = $this->dateFilters($request);

        $expenses = Expenses::where('user_id', $ownerId)
            ->when($filters['from'] ?? null, fn ($query, $date) => $query->whereDate('expense_date', '>=', $date))
            ->when($filters['to'] ?? null, fn ($query, $date) => $query->whereDate('expense_date', '<=', $date))
            ->orderByDesc('expense_date')->orderByDesc('id')
            ->get();

        $workers = Workers::where('user_id', $ownerId)
            ->when($filters['from'] ?? null, fn ($query, $date) => $query->whereDate('dateentered', '>=', $date))
            ->when($filters['to'] ?? null, fn ($query, $date) => $query->whereDate('dateentered', '<=', $date))
            ->orderByDesc('dateentered')->orderByDesc('id')
            ->get();

        $totalExpenses = $expenses->sum('Monthly_Rent') + $expenses->sum('Monthly_Bill');
        $totalSalaries = $workers->sum('Worker_salary');

        return view('Expenses.index', compact('expenses', 'workers', 'totalExpenses', 'totalSalaries', 'filters'));
    }

    public function create()
    {
        // The forms live in modals on the index page.
        return redirect()->route('admin.expense.index', ['modal' => 'bill']);
    }

    /**
     * Stores rent/bill and/or staff salaries, posted from the index page modals.
     */
    public function insert(Request $request)
    {
        $validated = $request->validate([
            'rent' => ['nullable', 'numeric', 'min:0'],
            'bill' => ['nullable', 'numeric', 'min:0'],
            'expense_date' => ['nullable', 'date'],
            'workers' => ['nullable', 'array'],
            'workers.*.name' => ['required', 'string', 'max:255'],
            'workers.*.salary' => ['required', 'numeric', 'min:0'],
        ], [], [
            'rent' => 'ماہانہ کرایہ',
            'bill' => 'ماہانہ بل',
            'workers.*.name' => 'ملازم کا نام',
            'workers.*.salary' => 'ملازم تنخواہ',
        ]);

        $rent = (float) ($validated['rent'] ?? 0);
        $bill = (float) ($validated['bill'] ?? 0);
        $workers = array_values($validated['workers'] ?? []);

        if ($rent <= 0 && $bill <= 0 && $workers === []) {
            throw ValidationException::withMessages(['rent' => 'کرایہ، بل یا کم از کم ایک ملازم درج کریں۔']);
        }

        $userId = auth()->user()->businessOwnerId();

        DB::transaction(function () use ($rent, $bill, $workers, $validated, $userId) {
            if ($rent > 0 || $bill > 0) {
                $date = Carbon::parse($validated['expense_date'] ?? now());
                Expenses::create([
                    'Monthly_Rent' => $rent,
                    'Monthly_Bill' => $bill,
                    'expense_day' => $date->format('l'),
                    'expense_date' => $date,
                    'user_id' => $userId,
                ]);
            }

            foreach ($workers as $worker) {
                Workers::create([
                    'Worker_Name' => trim($worker['name']),
                    'Worker_salary' => $worker['salary'],
                    'dateentered' => now(),
                    'user_id' => $userId,
                ]);
            }
        });

        return redirect()->route('admin.expense.index')->with('insert', 'اخراجات کامیابی سے شامل کر دیے گئے۔');
    }

    public function edit($id)
    {
        $data = Expenses::where('user_id', auth()->user()->businessOwnerId())->findOrFail($id);

        return redirect()->route('admin.expense.index', ['edit_bill' => $data->id]);
    }

    public function update(Request $request, $id)
    {
        $data = Expenses::where('user_id', auth()->user()->businessOwnerId())->findOrFail($id);
        $validated = $request->validate([
            'rent' => ['nullable', 'numeric', 'min:0'],
            'bill' => ['nullable', 'numeric', 'min:0'],
            'expense_date' => ['nullable', 'date'],
        ], [], ['rent' => 'ماہانہ کرایہ', 'bill' => 'ماہانہ بل']);

        $attributes = [
            'Monthly_Rent' => $validated['rent'] ?? 0,
            'Monthly_Bill' => $validated['bill'] ?? 0,
        ];
        if (! empty($validated['expense_date'])) {
            $date = Carbon::parse($validated['expense_date']);
            $attributes['expense_date'] = $date;
            $attributes['expense_day'] = $date->format('l');
        }
        $data->update($attributes);

        return redirect()->route('admin.expense.index')->with('update', 'ریکارڈ تبدیل کر دیا گیا ہے۔');
    }

    public function delete($id)
    {
        Expenses::where('user_id', auth()->user()->businessOwnerId())->findOrFail($id)->delete();

        return redirect()->route('admin.expense.index')->with('delete', 'ریکارڈ حذف کر دیا گیا ہے۔');
    }

    public function workersedit($id)
    {
        $data = Workers::where('user_id', auth()->user()->businessOwnerId())->findOrFail($id);

        return redirect()->route('admin.expense.index', ['edit_staff' => $data->id]);
    }

    public function workersupdate(Request $request, $id)
    {
        $data = Workers::where('user_id', auth()->user()->businessOwnerId())->findOrFail($id);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'salary' => ['required', 'numeric', 'min:0'],
        ], [], ['name' => 'ملازم کا نام', 'salary' => 'ملازم تنخواہ']);

        $data->update([
            'Worker_Name' => trim($validated['name']),
            'Worker_salary' => $validated['salary'],
        ]);

        return redirect()->route('admin.expense.index')->with('update', 'ریکارڈ تبدیل کر دیا گیا ہے۔');
    }

    public function workersdelete($id)
    {
        Workers::where('user_id', auth()->user()->businessOwnerId())->findOrFail($id)->delete();

        return redirect()->route('admin.expense.index')->with('delete', 'ریکارڈ حذف کر دیا گیا ہے۔');
    }

    public function showSpecificExpense(Request $request)
    {
        $data_range = $request->input('date_range');
        $date_parts = explode('to ',$data_range);

        $start_date = $date_parts[0];
        $end_date = $date_parts[1];

        // Fetching expenses details
        $expenses_detail = Expenses::select('Monthly_Rent', 'Monthly_Bill', 'expense_date')
            ->whereBetween('expense_date', [date($start_date), date($end_date)])
            ->where('user_id', auth()->user()->businessOwnerId())
            ->get();

        // Calculating total expenses
        $expenses = Expenses::whereBetween('expense_date', [date($start_date), date($end_date)])
            ->where('user_id', auth()->user()->businessOwnerId())
            ->sum(DB::raw('Monthly_Rent + Monthly_Bill'));

        // Fetching salaries details
        $salaries_detail = Workers::select('Worker_Name', 'Worker_salary')
            ->whereBetween('dateentered', [date($start_date), date($end_date)])
            ->where('user_id', auth()->user()->businessOwnerId())
            ->get();

        $salaries = Workers::whereBetween('dateentered', [date($start_date), date($end_date)])
        ->where('user_id', auth()->user()->businessOwnerId())
        ->sum('Worker_salary');

        // Return the data as JSON
        return response()->json([
            'expenses_detail' => $expenses_detail,
            'total_expenses' => $expenses,
            'salaries_detail' => $salaries_detail,
            'salaries' => $salaries
        ]);

    }

    /**
     * Daily expenses with today/month totals and an optional date filter.
     */
    public function Dailyindex(Request $request)
    {
        $ownerId = auth()->user()->businessOwnerId();
        $filters = $this->dateFilters($request);

        $query = DaliyExpenses::where('user_id', $ownerId)
            ->when($filters['from'] ?? null, fn ($query, $date) => $query->whereDate('created_at', '>=', $date))
            ->when($filters['to'] ?? null, fn ($query, $date) => $query->whereDate('created_at', '<=', $date));

        // Total of the whole filtered list, not just the current page.
        $listTotal = (float) (clone $query)->sum('Expense_payment');
        $expense = $query->latest()->orderByDesc('id')->paginate(10)->withQueryString();

        // The record to edit may not be on the current page (old edit URL or a failed edit).
        $editId = old('_form') === 'daily-edit' ? old('_edit_id') : $request->query('edit');
        $editRecord = $editId ? DaliyExpenses::where('user_id', $ownerId)->find($editId) : null;
        $editTarget = $editRecord ? [
            'id' => $editRecord->id,
            'name' => $editRecord->Expense_name,
            'amount' => $editRecord->Expense_payment,
            'action' => route('admin.dailyexpense.update', ['id' => $editRecord->id]),
        ] : null;

        $todayTotal = (float) DaliyExpenses::where('user_id', $ownerId)->whereDate('created_at', today())->sum('Expense_payment');
        $monthTotal = (float) DaliyExpenses::where('user_id', $ownerId)
            ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->sum('Expense_payment');

        return view('DailyExpenses.index', compact('expense', 'filters', 'todayTotal', 'monthTotal', 'listTotal', 'editTarget'));
    }

    public function Dailycreate()
    {
        // The form lives in a modal on the index page.
        return redirect()->route('admin.dailyexpense.index', ['modal' => 'create']);
    }

    public function Dailyinsert(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'array', 'min:1'],
            'name.*' => ['required', 'string', 'max:255'],
            'rupee' => ['required', 'array', 'size:'.count((array) $request->input('name', []))],
            'rupee.*' => ['required', 'numeric', 'min:0'],
        ], [], ['name.*' => 'اخراجات کا عنوان', 'rupee.*' => 'اخراجات کی رقم']);

        $userId = auth()->user()->businessOwnerId();

        DB::transaction(function () use ($validated, $userId) {
            foreach (array_values($validated['name']) as $index => $name) {
                DaliyExpenses::create([
                    'Expense_name' => trim($name),
                    'Expense_payment' => array_values($validated['rupee'])[$index],
                    'user_id' => $userId,
                ]);
            }
        });

        return redirect()->route('admin.dailyexpense.index')->with('insert', 'روزانہ اخراجات کامیابی سے شامل کر دیے گئے۔');
    }

    public function Dailyedit($id)
    {
        $data = DaliyExpenses::where('user_id', auth()->user()->businessOwnerId())->findOrFail($id);

        return redirect()->route('admin.dailyexpense.index', ['edit' => $data->id]);
    }

    public function Dailyupdate(Request $request,$id)
    {
        $data = DaliyExpenses::where('user_id', auth()->user()->businessOwnerId())->findOrFail($id);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'rupee' => ['required', 'numeric', 'min:0'],
        ], [], ['name' => 'اخراجات کا عنوان', 'rupee' => 'اخراجات کی رقم']);

        $data->update([
            'Expense_name' => trim($validated['name']),
            'Expense_payment' => $validated['rupee'],
        ]);

        return redirect()->route('admin.dailyexpense.index')->with('update', 'ریکارڈ تبدیل کر دیا گیا ہے۔');
    }

    public function Dailydelete($id)
    {
        DaliyExpenses::where('user_id', auth()->user()->businessOwnerId())->findOrFail($id)->delete();

        return redirect()->back()->with('delete', 'ریکارڈ حذف کر دیا گیا ہے۔');
    }

    public function showdailySpecificExpense(Request $request)
    {
        $data_range = $request->input('date_range');
        $date_parts = explode('to ',$data_range);

        $start_date = $date_parts[0];
        $end_date = $date_parts[1];

        $daily_expense = DaliyExpenses::whereBetween('created_at',[date($start_date),date($end_date)])->where('user_id',auth()->user()->businessOwnerId())->get();

        return response()->json(['expense' => $daily_expense]);

    }

    private function dateFilters(Request $request): array
    {
        return $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);
    }
}
