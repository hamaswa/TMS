<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    // public function __construct()
    // {
    //     $this->middleware('auth');
    // }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        logout_tailor();
       $current_week = current_week();
       $pre_week = pre_week();
       $current_month = current_month();
       $pre_month = pre_month();
      return view('dashboard.index',compact('current_week','pre_week','current_month','pre_month'));
    }
}
function logout_tailor()
{
        session()->forget('tailor-login-success');
        session()->forget('tailor');
        session()->forget('tailor_id');
}
function current_week(){
        $sat = strtotime('next Saturday -1 week');
        $sat = date('w', $sat)==date('w') ? strtotime(date("Y-m-d",$sat)." +7 days") : $sat;
        $friday = strtotime(date("Y-m-d",$sat)." +6 days");
        $start = date("Y-m-d",$sat);
        $end = date("Y-m-d",$friday);
        $cur_week = common_date_btw($start, $end);
        return $cur_week;
};
function pre_week(){
        $previous_week = strtotime("-1 week +1 day");
        $start_week = strtotime("last saturday midnight",$previous_week);
        $end_week = strtotime("next friday",$start_week);
        $start = date("Y-m-d",$start_week);
        $end = date("Y-m-d",$end_week);
        return common_date_btw($start, $end);
};
function current_month(){
     $start = date('Y-m-01');
     $end = date('Y-m-t');
     return common_date_btw($start, $end);
};
function pre_month(){
    $start = date('Y-m-01',strtotime('last month'));
    $end = date('Y-m-t',strtotime('last month'));
    return common_date_btw($start, $end);
};


// comman function
function common_date_btw($start, $end)
{
    $u_id = Auth::user()->id;
    return DB::table('orders')
           ->select('orders.suitQuantity as suit')
           ->where('orders.userId',$u_id)
           ->whereBetween('orders.created_at',[$start, $end])
           ->sum('suitQuantity');
}
