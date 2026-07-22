<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Workers;
use App\Models\Expenses;
use Nette\Schema\Expect;
use Illuminate\Http\Request;
use Illuminate\Queue\Worker;
use App\Models\DaliyExpenses;
use Illuminate\Support\Facades\DB;

class ExpensesController extends Controller
{
    public function index()
{
    // Get the current date
    $currentDate = Carbon::now()->toDateString();

    // Retrieve expenses for the current day
    $expenses = Expenses::where('user_id',auth()->user()->id)->get();

    $workers = Workers::where('user_id',auth()->user()->id)->get();

    // Calculate total expenses
    $totalExpenses = $expenses->sum('Monthly_Rent') + $expenses->sum('Monthly_Bill');

    $totalSalaries = $workers->sum('Worker_salary');

    return view('Expenses.index', compact('expenses', 'workers', 'totalExpenses', 'totalSalaries'));
}

    public function create()
    {
        return view('Expenses.create');
    }

    public function insert(Request $request)
    {
        $monthly_bill = $request->input('bill');
        $monthly_rent = $request->input('rent');
        $user_id = auth()->user()->id;

        Expenses::create([
            'Monthly_Rent' => $monthly_rent ?? 0,
            'Monthly_Bill' => $monthly_bill ?? 0,
            'expense_day' => Carbon::now()->format('l'),
            'expense_date' => now(),
            'user_id' => $user_id,
        ]);

        $numWorkers = $request->input('num_workers');

        $worker_names = [];
        $worker_salaries = [];

        for ($i = 1; $i <= $numWorkers; $i++) {
            // Use the loop index to differentiate between worker inputs
            $worker_name = $request->input('worker_name_' . $i);
            $worker_salary = $request->input('worker_salary_' . $i);

            // Add the values to arrays
            $worker_names[] = $worker_name;
            $worker_salaries[] = $worker_salary;
        }

        // Create records for each worker
        for ($i = 0; $i < $numWorkers; $i++) {
            Workers::create([
                'Worker_Name' => $worker_names[$i] ?? null,
                'Worker_salary' => $worker_salaries[$i] ?? 0,
                'dateentered' => now(),
                'user_id' => $user_id,
            ]);
        }


        // dd($monthly_bill,$monthly_rent,$monthly_extra,$worker_names,$worker_salaries);
        return redirect()->route('admin.expense.index')->with('success', 'Expense records added successfully');
    }

    public function edit($id)
    {
        $data = Expenses::find($id);
        return view('Expenses.expense_edit',compact('data'));
    }

    public function update(Request $request, $id)
    {
        $data = Expenses::find($id);
        $monthly_bill = $request->input('bill');
        $monthly_rent = $request->input('rent');
        $monthly_extra = $request->input('extra');

        $data->update([
            'Monthly_Rent' => $monthly_rent ?? 0,
            'Monthly_Bill' => $monthly_bill ?? 0,
        ]);

        return redirect()->route('admin.expense.index')->with('success','ریکارڈ تبدیل کر دیا گیا ہے۔');
    }
    public function delete($id)
    {
        $data = Expenses::find($id);

        if($data)
        {
            $data->delete();
            return redirect()->route('admin.expense.index')->with('error','ریکارڈ حذف کر دیا گیا ہے۔');
        }
    }

    public function workersedit($id)
    {
        $data = Workers::find($id);
        return view('Expenses.workers_edit',compact('data'));
    }

    public function workersupdate(Request $request,$id)
    {
        $data = Workers::find($id);
        $employee_name = $request->input('name');
        $employee_salary = $request->input('salary');

        $data->update([
            'Worker_Name' => $employee_name ?? 0,
            'Worker_salary' => $employee_salary ?? 0,
        ]);

        return redirect()->route('admin.expense.index')->with('success','ریکارڈ تبدیل کر دیا گیا ہے۔');
    }

    public function workersdelete($id)
    {
        $data = Workers::find($id);

        if($data)
        {
            $data->delete();
            return redirect()->route('admin.expense.index')->with('error','ریکارڈ حذف کر دیا گیا ہے۔');
        }
    }

    public function showSpecificExpense(Request $request)
    {
        $data_range = $request->input('date_range');
        $date_parts = explode('to ',$data_range);

        $start_date = $date_parts[0];
        $end_date = $date_parts[1];
        // dd($start_date,$end_date);

        // Fetching expenses details
        $expenses_detail = Expenses::select('Monthly_Rent', 'Monthly_Bill', 'expense_date')
            ->whereBetween('expense_date', [date($start_date), date($end_date)])
            ->where('user_id', auth()->user()->id)
            ->get();

        // Calculating total expenses
        $expenses = Expenses::whereBetween('expense_date', [date($start_date), date($end_date)])
            ->where('user_id', auth()->user()->id)
            ->sum(DB::raw('Monthly_Rent + Monthly_Bill'));

        // Fetching salaries details
        $salaries_detail = Workers::select('Worker_Name', 'Worker_salary')
            ->whereBetween('dateentered', [date($start_date), date($end_date)])
            ->where('user_id', auth()->user()->id)
            ->get();

        // dd($salaries_detail);

        $salaries = Workers::whereBetween('dateentered', [date($start_date), date($end_date)])
        ->where('user_id', auth()->user()->id)
        ->sum('Worker_salary');

        // dd($salaries);

        // Return the data as JSON
        return response()->json([
            'expenses_detail' => $expenses_detail,
            'total_expenses' => $expenses,
            'salaries_detail' => $salaries_detail,
            'salaries' => $salaries
        ]);

    }

    public function Dailyindex()
    {
        $currentDate = Carbon::now()->toDateString();
        $expense = DaliyExpenses::where('user_id', auth()->user()->id)
                             ->get();
        return view('DailyExpenses.index',compact('expense'));
    }

    public function Dailycreate()
    {
        return view('DailyExpenses.create');
    }

    public function Dailyinsert(Request $request)
    {
        $name = $request->input('name');
        $rupee = $request->input('rupee');
        $user_id = auth()->user()->id;

        foreach (array_map(null, $name, $rupee) as [$name, $rupee]) {
            DaliyExpenses::create([
                'Expense_name' => $name,
                'Expense_payment' => $rupee,
                'user_id' => $user_id,
            ]);
        }

        return redirect()->route('admin.dailyexpense.index');
    }

    public function Dailyedit($id)
    {
        $data = DaliyExpenses::find($id);
        return view('DailyExpenses.edit',compact('data'));
    }

    public function Dailyupdate(Request $request,$id)
    {
        $data = DaliyExpenses::find($id);
        $name = $request->input('name');
        $rupee = $request->input('rupee');

        foreach (array_map(null, $name, $rupee) as [$name, $rupee]) {
            $data->update([
                'Expense_name' => $name,
                'Expense_payment' => $rupee,
            ]);
        }

        return redirect()->route('admin.dailyexpense.index');
    }

    public function Dailydelete($id)
    {
        $data = DaliyExpenses::find($id);
        if($data){
            $data->delete();
        }
        return redirect()->back();
    }

    public function showdailySpecificExpense(Request $request)
    {
        $data_range = $request->input('date_range');
        $date_parts = explode('to ',$data_range);

        $start_date = $date_parts[0];
        $end_date = $date_parts[1];
        // dd($start_date,$end_date);

        $daily_expense = DaliyExpenses::whereBetween('created_at',[date($start_date),date($end_date)])->where('user_id',auth()->user()->id)->get();

        return response()->json(['expense' => $daily_expense]);

    }
}
