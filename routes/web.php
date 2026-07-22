<?php

use App\Events\NotificationEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CsvController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Redirect;
use App\Http\Controllers\ClothController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\DesignController;
use App\Http\Controllers\TailorController;
use App\Http\Controllers\OptionsController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\ExpensesController;
use App\Http\Controllers\ClothTypeController;
use App\Http\Controllers\ClothBrandController;
use App\Http\Controllers\ClothStockController;
use App\Http\Controllers\OptionTypeController;
use App\Http\Controllers\TailorRateController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\SaleCustomerController;
use App\Http\Controllers\AdministratorController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Auth::routes();

Route::get('/new-tab', function () {
    return Redirect::away('http://heera.it');
});

// Common route for both roles
Route::get('/', function () {
    if (auth()->check()) {
        // Check if the user has the 'shop_owner' role
        if (auth()->user()->hasRole('shop_owner')) {
            return redirect()->route('admin.home'); // Adjust this route name as needed
        }
        // Check if the user has the 'user' role
        elseif (auth()->user()->hasRole('user')) {
            return redirect()->route('user.shops'); // Adjust this route name as needed
        } elseif (auth()->user()->hasRole('administrative')) {
            return redirect()->route('administrator.index');
        }
    }

    // If not authenticated or no specific role, redirect to login or another page
    return redirect('/login');
});
//Administrator routes
Route::group(['prefix' => 'administrator', 'middleware' => ['auth', 'role:administrative'], 'as' => 'administrator.'], function () {

    Route::get('/', [AdministratorController::class, 'showData'])->name('index');
    Route::get('/create', [AdministratorController::class, 'index'])->name('create');
    Route::post('/create', [AdministratorController::class, 'insert'])->name('insert');
    Route::get('/edit{id}', [AdministratorController::class, 'edit'])->name('edit');
    Route::post('/update{id}', [AdministratorController::class, 'update'])->name('update');
    Route::get('/delete{id}', [AdministratorController::class, 'delete'])->name('delete');
    Route::get('/roles', [AdministratorController::class, 'show'])->name('roles');
    Route::get('/editUserRoles/{id}', [AdministratorController::class, 'editRole'])->name('editUserRoles');
    Route::put('/updateUserRoles/{id}', [AdministratorController::class, 'updateRole'])->name('updateUserRoles');
    Route::get('/new-role', [AdministratorController::class, 'newRole'])->name('role.new');
    Route::post('create-role', [AdministratorController::class, 'createRole'])->name('role.create');
    Route::get('/roles-permis', [AdministratorController::class, 'showRolePermi'])->name('roles-permi');

    Route::get('/roles-edit/{id}', [AdministratorController::class, 'editRoles'])->name('role.edit');
    Route::get('/permi-edit/{id}', [AdministratorController::class, 'editPermissions'])->name('perm.edit');

    Route::post('/roles-update/{id}', [AdministratorController::class, 'updateRoles'])->name('role.update');
    Route::post('/permi-update/{id}', [AdministratorController::class, 'updatePermissions'])->name('perm.update');

    Route::get('/roles-delete/{id}', [AdministratorController::class, 'deleteRoles'])->name('role.delete');
    Route::get('/permi-delete/{id}', [AdministratorController::class, 'deletePermissions'])->name('perm.delete');

    Route::get('/notify/{id}',[AdministratorController::class,'send'])->name('noti');
    Route::post('/send',[AdministratorController::class,'store'])->name('send');
    // Route::get('/sse-update', [AdministratorController::class, 'SSEupdates']);
});

Route::group(['prefix' => 'admin', 'middleware' => ['auth', 'role:shop_owner'], 'as' => 'admin.'], function () {

    Route::get('/', [HomeController::class, 'index'])->name('home');
    // Route::get('/test', function(){
    //     event(new NotificationEvent("Testing Web Socket"));
    //     return response()->json('Event Dispacthed');
    // });


    // Route::get('/notifications-stream', [UserController::class, 'notificationsStream'])->name('notifications-stream');

    Route::resource('/Customers', CustomerController::class);
    Route::post('DirectPayment', [CustomerController::class, 'DirectPayment'])->name('DirectPayment');
    Route::post('RackNo', [CustomerController::class, 'RackNo'])->name('RackNo');

    // Sale
    Route::resource('/sale', SaleController::class);
    Route::get('sale/print/{id}', [SaleController::class, 'print'])->name('sale-print');
    Route::get('sale/delete/{id}', [SaleController::class, 'destroy'])->name('sale.destroy');

    // OptionTypes
    Route::resource('/OptionType', OptionTypeController::class);
    Route::resource('/Options', OptionsController::class);
    Route::get('Options/add/{id}', [OptionsController::class, 'add']);

    //design
    Route::get('/design', [DesignController::class, 'index'])->name('design.index');
    Route::get('/design/create', [DesignController::class, 'create'])->name('design.create');
    Route::post('/design/store', [DesignController::class, 'store'])->name('design.store');
    Route::get('/design/edit/{id}', [DesignController::class, 'edit'])->name('design.edit');
    Route::post('/design/update/{id}', [DesignController::class, 'update'])->name('design.update');
    Route::get('/design/delete/{id}', [DesignController::class, 'delete'])->name('design.delete');
    Route::get('/design/price/{id}', [DesignController::class, 'price'])->name('design.price');

    // Tailor
    Route::resource('/Tailor', TailorController::class);
    Route::get('Tailor/delete/{id}', [TailorController::class, 'destroy']);
    Route::get('payment-received{id}', [TailorController::class, 'paymentReceived'])->name('payment-received');
    Route::get('tailor-orders/{id}', [TailorController::class, 'tailorRecord'])->name('tailor-orders');
    Route::match(['get', 'post'], 'tailor-report/{id}', [TailorController::class, 'tailorReport'])->name('tailor-report');
    Route::get('tailor-weekly-report-print/{id}', [TailorController::class, 'tailorReportPrint'])->name('report-print');
    Route::get('tailor-rates/{id}', [TailorController::class, 'tailorRates'])->name('tailor-rates');
    Route::post('tailor/addRecord/{id}', [TailorController::class, 'addRecord'])->name('tailor.addRecord');
    Route::post('tailor/addAdvanceRecord/{id}', [TailorController::class, 'addAdnvanceRecord'])->name('tailor.addAdvanceRecord');

    //new route
    Route::post('tailor/cutAdvanceRecord/{id}', [TailorController::class, 'cutAdvanceRecord'])->name('tailor.cutAdvanceRecord');
    Route::post('specific-record/{id}', [TailorController::class, 'showSpecificRecord'])->name('record.specific');

    // Tailor Salary or Rates
    Route::get('salary/{user_id}', [TailorController::class, 'tailorSalary']);
    Route::get('tailors-rates/create/{id}', [TailorRateController::class, 'create']);
    Route::post('tailors-rates/store/{id}', [TailorRateController::class, 'store']);
    Route::get('tailors-rates/delete/{id}', [TailorRateController::class, 'destroy']);
    Route::get('tailors-rates/edit/{id}', [TailorRateController::class, 'edit']);

    // order
    Route::get('/order/edit/{id}', [OrderController::class, 'edit']);
    Route::put('/order/update/{id}', [OrderController::class, 'update']);
    Route::get('/order/{id}', [OrderController::class, 'createOrder']);
    Route::post('/order/insert', [OrderController::class, 'insert']);
    Route::get('/getCustomer', [OrderController::class, 'getCustomer'])->name('getCustomer');
    Route::get('order/print/{id}', [OrderController::class, 'print'])->name('order-print');
    Route::get('order/prints/{id}', [OrderController::class, 'two_prints'])->name('order-prints');
    Route::get('search', [OrderController::class, 'search'])->name('search');
    Route::post('/order/update-rack-no/{orderId}', [OrderController::class, 'updateRackNo'])->name('order.updateRackNo');
    Route::post('/order/order-complete', [OrderController::class, 'orderCompleteNotify'])->name('order.notify');


    // csv import and export route
    Route::get('/export-csv-customers',[CsvController::class,'exportCsv'])->name('customercsv');
    Route::post('/import-csv-customers',[CsvController::class,'importCsv'])->name('customerscsv');
    Route::get('/export-csv-clothes',[CsvController::class,'exportCsvCloths'])->name('clothscsv');
    Route::post('/import-csv-clothes',[CsvController::class,'importCsvCloths'])->name('clothescsv');

    Route::post('/save-subscription', 'NotificationController@saveSubscription')->name('save-subscription');
    Route::post('/save-push-noti',[PushNotificationController::class,'saveSubscription'])->name('save-push');


    // order-status
    Route::post('order-status', [OrderController::class, 'order_status']);

    // admin get tailor records print
    Route::post('tailor-weakly-print/{id}', [TailorController::class, 'tailor_weekly']);
    // language
    Route::get('Lang-change', [SettingController::class, 'langChange']);

    Route::get('/total_orde', [OrderController::class, 'totalOrder'])->name('order.total');
});


Route::group(['prefix' => 'admin', 'middleware' => ['auth', 'role:stock_seller|shop_owner'], 'as' => 'admin.'], function () {
    // Route::get('/', [HomeController::class, 'index'])->name('admin');
    // ClothType
    Route::resource('/clothtype', ClothTypeController::class);

    //sale customers routes
    Route::get('/cloths-index', [SaleCustomerController::class, 'index']);

    // setting
    Route::get('setting', [SettingController::class, 'list']);
    Route::get('setting/add', [SettingController::class, 'add'])->name('add-setting');
    Route::post('setting/insert', [SettingController::class, 'insert'])->name('insert-setting');
    Route::get('setting/edit/{id}', [SettingController::class, 'edit'])->name('edit-setting');
    Route::post('setting/update/{id}', [SettingController::class, 'update'])->name('update-setting');
    Route::get('setting/delete/{id}', [SettingController::class, 'delete'])->name('delete-setting');
    Route::get('setting/active/{id}', [SettingController::class, 'active'])->name('active-setting');
    Route::get('setting/deactive/{id}', [SettingController::class, 'deactive'])->name('deactive-setting');

    //acount details
    Route::get('/user-details', [UserController::class, 'index'])->name('users');
    Route::get('/user-edit/{id}', [UserController::class, 'edit'])->name('user.edit');
    Route::put('/user-update/{id}', [UserController::class, 'update'])->name('user.update');

    // ClothBrand
    Route::resource('/clothbrand', ClothBrandController::class);

    // Cloth
    Route::resource('/cloth', ClothController::class);
    Route::get('/edit-cloth/{id}/{color}', [ClothController::class, 'editCloth'])->name('edit-cloths');
    Route::post('/delete-cloth', [ClothController::class, 'deleteCloth'])->name('delete-cloths');

    // Cloth Stock
    Route::resource('/stock', ClothStockController::class);

    //ajax for get cloth Type
    Route::get('/getType', [ClothStockController::class, 'getType']);

    Route::get('sellcloth', [ClothStockController::class, 'sellCloth'])->name('sellCloth');

    //stock details
    Route::get('/getSale', [ClothStockController::class, 'getSale'])->name('getSale');

    //monthly expenses
    Route::get('/expense', [ExpensesController::class, 'index'])->name('expense.index');
    Route::get('/create-expense', [ExpensesController::class, 'create'])->name('expense.create');
    Route::post('/create-expense', [ExpensesController::class, 'insert'])->name('expense.insert');
    Route::get('/expense-edit/{id}', [ExpensesController::class, 'edit'])->name('expense.edit');
    Route::post('/expense-update/{id}', [ExpensesController::class, 'update'])->name('expense.update');
    Route::get('/expense-delete/{id}', [ExpensesController::class, 'delete'])->name('expense.delete');
    //workers
    Route::get('/workers-edit/{id}', [ExpensesController::class, 'workersedit'])->name('worker.edit');
    Route::post('/workers-update/{id}', [ExpensesController::class, 'workersupdate'])->name('worker.update');
    Route::get('/workers-delete/{id}', [ExpensesController::class, 'workersdelete'])->name('worker.delete');
    Route::post('specific-expense', [ExpensesController::class, 'showSpecificExpense'])->name('expense.specific');

    //daily expenses
    Route::get('/daily-expense', [ExpensesController::class, 'Dailyindex'])->name('dailyexpense.index');
    Route::get('/create-daily-expense', [ExpensesController::class, 'Dailycreate'])->name('dailyexpense.create');
    Route::post('/create-daily-expense', [ExpensesController::class, 'Dailyinsert'])->name('dailyexpense.insert');
    Route::get('/dailyexpense-edit/{id}', [ExpensesController::class, 'Dailyedit'])->name('dailyexpense.edit');
    Route::post('/dailyexpense-update/{id}', [ExpensesController::class, 'Dailyupdate'])->name('dailyexpense.update');
    Route::get('/dailyexpense-delete/{id}', [ExpensesController::class, 'Dailydelete'])->name('dailyexpense.delete');

    Route::post('daily-specific-expense', [ExpensesController::class, 'showdailySpecificExpense'])->name('dailyexpense.specific');


    // Define the route for processing the sell form
    Route::post('stock/sell', [ClothStockController::class, 'sellStock'])->name('sellStock');
    Route::get('/print/{id}/{customerId}', [ClothStockController::class, 'printStock'])->name('printStock');

    Route::get('/prints/{id}/{customerId}', [ClothStockController::class, 'printStocks'])->name('printStocks');

    Route::get('/getNmbr', [ClothStockController::class, 'getNmbr'])->name('nmbr');
    Route::get('/getId', [ClothStockController::class, 'getId'])->name('Id');

    Route::post('specific-sales', [ClothStockController::class, 'showSpecificSales'])->name('sales.specific');

    Route::get('/customer-add', [CustomerController::class, 'saleCustomer'])->name('customers.sale');
    Route::post('/customer-add', [CustomerController::class, 'AddsaleCustomer'])->name('addcustomers.sale');
    Route::get('/customers-record', [ClothStockController::class, 'showList'])->name('record');
    Route::get('/customers-detail/{id}', [ClothStockController::class, 'customersDetail'])->name('customers.details');
    Route::get('/dlt/{id}', [ClothStockController::class, 'dlt'])->name('dlt');
    Route::post('SaleDirectPayment', [CustomerController::class, 'SaleDirectPayment'])->name('DirectPayment');


    Route::get('/total_sale', [ClothStockController::class, 'totalSales'])->name('sales.total');
    Route::get('/total_earning', [ClothStockController::class, 'totalEarning'])->name('earning.total');


    // notifications route
    Route::get('/notifications', [NotificationController::class, 'showNotifications'])->name('notifications.index');
    Route::get('/notify-admin',[NotificationController::class,'AdminNotifications'])->name('notify');
    Route::get('/notify-user',[NotificationController::class,'UserNotifications'])->name('user');
    Route::post('/mark-as-read/{id}', [NotificationController::class, 'readNotifications'])->name('notification.read');
    Route::post('/order-complete/{id}', [NotificationController::class, 'orderComplete'])->name('order.complete');

    // Online Orders Route
    Route::get('/Online-Orders', [NotificationController::class, 'showOnlineOrders'])->name('orders.online');
});

Route::group(['middleware' => 'Tailor', 'prefix' => 'tailor'], function () {
    Route::get('tailor-dashboard', [TailorController::class, 'tailor_dashboard']);
    Route::get('tailor-order-list', [TailorController::class, 'tailor_order_list']);
    Route::get('logout', [TailorController::class, 'logout']);
    Route::post('tailor-weakly-print/{id}', [TailorController::class, 'tailor_weekly']);
    // order-status
    Route::post('order-status', [OrderController::class, 'order_status']);
});

Route::get('tailor-login', [TailorController::class, 'tailor_login']);
Route::post('tailor-login', [TailorController::class, 'login']);


// for user come for shopping
Route::group(['prefix' => 'user', 'middleware' => ['auth', 'role:user'], 'as' => 'user.'], function () {
    Route::get('/home', [SaleCustomerController::class, 'shops'])->name('shops');
    Route::get('/{slug}', [SaleCustomerController::class, 'saleCustomer'])->name('selling');
    Route::get('/{slug}/stock/{id}', [SaleCustomerController::class, 'Stock'])->name('customer.stock');
    Route::post('/{slug}/stock', [SaleCustomerController::class, 'stockSearch'])->name('stock.search');
    Route::get('/{slug}/stock_check/{brand_id}/{type_id}/{color}', [SaleCustomerController::class, 'ShowStock'])->name('customer.stock.show');


    Route::post('/{slug}/add_cart', [CartController::class, 'AddCart'])->name('stock.cart');

    Route::get('/{slug}/show_cart', [CartController::class, 'ShowCart'])->name('cart.show');
    Route::get('/{slug}/delete_cart/{id}', [CartController::class, 'DeleteCart'])->name('cart.delete');
    Route::post('/{slug}/buy_cart/{id}', [CartController::class, 'BuyCart'])->name('cart.buy');

    Route::post('/{slug}/add_order', [CartController::class, 'AddOrder'])->name('stock.order');
    Route::get('/{slug}/thank_you', [CartController::class, 'ThankYou'])->name('thank_you');


    Route::get('/{slug}/customer-details', [SaleCustomerController::class, 'AccountDetails'])->name('customers.details');
    Route::get('edit/{id}', [SaleCustomerController::class, 'edit'])->name('customers.edit');
    Route::post('update/{id}', [SaleCustomerController::class, 'update'])->name('customers.update');
    Route::get('/{slug}/delete/{id}', [SaleCustomerController::class, 'delete'])->name('customers.delete');

    Route::get('/{slug}/order-history', [CartController::class, 'ShowOrderHistory'])->name('history');

    Route::get('/{slug}/cancel-order/{id}', [CartController::class, 'CancelOrder'])->name('order.cancel');

    Route::get('/{slug}/again-order/{id}', [CartController::class, 'AgainOrder'])->name('order.again');
});
