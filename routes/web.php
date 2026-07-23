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
use App\Http\Controllers\TailorJobController;
use App\Http\Controllers\OptionsController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\ExpensesController;
use App\Http\Controllers\ClothTypeController;
use App\Http\Controllers\ClothBrandController;
use App\Http\Controllers\ClothStockController;
use App\Http\Controllers\OptionTypeController;
use App\Http\Controllers\MeasurementFieldController;
use App\Http\Controllers\MeasurementTemplateController;
use App\Http\Controllers\TailorRateController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PushNotificationController;
use App\Http\Controllers\SaleCustomerController;
use App\Http\Controllers\AdministratorController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\InventoryLedgerController;
use App\Http\Controllers\FinancialReportController;
use App\Http\Controllers\BusinessTeamController;
use App\Http\Controllers\BusinessActivityController;
use App\Http\Controllers\EmployeePasswordController;
use App\Http\Controllers\ProductionWorkerController;
use App\Http\Controllers\OrderWorkAssignmentController;
use App\Http\Controllers\AdminStorefrontController;
use App\Http\Controllers\AdminStorefrontClothingController;
use App\Http\Controllers\AdminStorefrontTailoringController;
use App\Http\Controllers\PublicStorefrontController;
use App\Http\Controllers\PublicStorefrontCartController;
use App\Http\Controllers\PublicStorefrontCheckoutController;
use App\Http\Controllers\AdminStorefrontOrderController;

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

Route::get('/shops', [PublicStorefrontController::class, 'index'])->name('storefront.index');
Route::get('/shops/{storefront:slug}', [PublicStorefrontController::class, 'show'])->name('storefront.show');
Route::get('/shops/{storefront:slug}/clothes', [PublicStorefrontController::class, 'clothing'])->name('storefront.clothing.index');
Route::get('/shops/{storefront:slug}/clothes/{listing}', [PublicStorefrontController::class, 'clothingShow'])->name('storefront.clothing.show');
Route::get('/shops/{storefront:slug}/cart', [PublicStorefrontCartController::class, 'show'])->name('storefront.cart.show');
Route::post('/shops/{storefront:slug}/clothes/{listing}/cart', [PublicStorefrontCartController::class, 'store'])
    ->middleware('throttle:30,1')->name('storefront.cart.store');
Route::patch('/shops/{storefront:slug}/cart/items/{item}', [PublicStorefrontCartController::class, 'update'])->name('storefront.cart.update');
Route::delete('/shops/{storefront:slug}/cart/items/{item}', [PublicStorefrontCartController::class, 'destroy'])->name('storefront.cart.destroy');
Route::post('/shops/{storefront:slug}/cart/customer', [PublicStorefrontCartController::class, 'linkCustomer'])
    ->middleware('throttle:5,1')->name('storefront.cart.customer.link');
Route::delete('/shops/{storefront:slug}/cart/customer', [PublicStorefrontCartController::class, 'unlinkCustomer'])->name('storefront.cart.customer.unlink');
Route::post('/shops/{storefront:slug}/checkout', [PublicStorefrontCheckoutController::class, 'store'])
    ->middleware('throttle:10,1')->name('storefront.checkout.store');
Route::get('/shops/{storefront:slug}/orders/{reference}', [PublicStorefrontCheckoutController::class, 'show'])
    ->name('storefront.orders.show');
Route::post('/shops/{storefront:slug}/orders/{reference}/access', [PublicStorefrontCheckoutController::class, 'authenticate'])
    ->middleware('throttle:5,1')->name('storefront.orders.authenticate');
Route::get('/shops/{storefront:slug}/tailoring', [PublicStorefrontController::class, 'tailoring'])->name('storefront.tailoring.index');
Route::get('/shops/{storefront:slug}/tailoring/{service}', [PublicStorefrontController::class, 'tailoringShow'])->name('storefront.tailoring.show');
Route::post('/shops/{storefront:slug}/inquiries', [PublicStorefrontController::class, 'submitInquiry'])
    ->middleware('throttle:10,1')
    ->name('storefront.inquiries.store');

Route::group(['prefix' => 'employee/security', 'middleware' => ['auth', 'business.status', 'business.activity'], 'as' => 'employee.password.'], function () {
    Route::get('/password', [EmployeePasswordController::class, 'edit'])->name('edit');
    Route::put('/password', [EmployeePasswordController::class, 'update'])->middleware('throttle:6,1')->name('update');
});

Route::get('/new-tab', function () {
    return Redirect::away('http://heera.it');
});

// Common route for both roles
Route::get('/', function () {
    if (auth()->check()) {
        // Check if the user has the 'shop_owner' role
        if (auth()->user()->isBusinessMember()) {
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
    Route::get('/marketplace', [AdministratorController::class, 'marketplace'])->name('marketplace.index');
    Route::patch('/marketplace/{storefront}/moderation', [AdministratorController::class, 'updateMarketplaceModeration'])
        ->name('marketplace.moderation');
    Route::get('/create', [AdministratorController::class, 'index'])->name('create');
    Route::post('/create', [AdministratorController::class, 'insert'])->name('insert');
    Route::get('/clients/{id}', [AdministratorController::class, 'clientDetails'])->name('clients.show');
    Route::patch('/clients/{id}/status', [AdministratorController::class, 'updateStatus'])->name('clients.status');
    Route::get('/edit/{id}', [AdministratorController::class, 'edit'])->name('edit');
    Route::post('/update/{id}', [AdministratorController::class, 'update'])->name('update');
    Route::delete('/delete/{id}', [AdministratorController::class, 'delete'])->name('delete');
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

    Route::delete('/roles-delete/{id}', [AdministratorController::class, 'deleteRoles'])->name('role.delete');
    Route::delete('/permi-delete/{id}', [AdministratorController::class, 'deletePermissions'])->name('perm.delete');

    Route::get('/notify/{id}',[AdministratorController::class,'send'])->name('noti');
    Route::post('/send',[AdministratorController::class,'store'])->name('send');
    // Route::get('/sse-update', [AdministratorController::class, 'SSEupdates']);
});

Route::group(['prefix' => 'admin', 'middleware' => ['auth', 'business.status', 'password.changed', 'role:shop_owner', 'business.activity'], 'as' => 'admin.'], function () {
    Route::get('/customers/{id}/statement', [CustomerController::class, 'statement'])
        ->middleware('business.permission:tailoring.customers|clothing.sales|customers.balances')
        ->name('customers.statement');
    Route::get('/financial-reports', [FinancialReportController::class, 'index'])->middleware('business.permission:finance.view')->name('financial-reports.index');
    Route::get('/financial-reports/export/{section}', [FinancialReportController::class, 'export'])->middleware('business.permission:finance.view')->name('financial-reports.export');
    Route::get('/', [HomeController::class, 'index'])->name('home');
    Route::get('/workspace/current', [HomeController::class, 'current'])->name('workspace.current');
    Route::get('/workspace/{workspace}', [HomeController::class, 'switch'])->whereIn('workspace', ['tailoring', 'clothing'])->name('workspace.switch');
    Route::get('/activity-log', [BusinessActivityController::class, 'index'])->middleware('business.permission:activity.view')->name('activity.index');
    Route::get('/activity-log/export', [BusinessActivityController::class, 'export'])->middleware('business.permission:activity.view')->name('activity.export');

    Route::middleware('business.permission:storefront.manage')->group(function () {
        Route::get('/storefront', [AdminStorefrontController::class, 'edit'])->name('storefront.edit');
        Route::put('/storefront', [AdminStorefrontController::class, 'update'])->name('storefront.update');
        Route::patch('/storefront/publication', [AdminStorefrontController::class, 'publish'])->name('storefront.publish');
        Route::get('/storefront/preview', [AdminStorefrontController::class, 'preview'])->name('storefront.preview');
        Route::get('/storefront/clothing', [AdminStorefrontClothingController::class, 'index'])->name('storefront.clothing.index');
        Route::put('/storefront/clothing/{cloth}', [AdminStorefrontClothingController::class, 'update'])->name('storefront.clothing.update');
        Route::get('/storefront/tailoring', [AdminStorefrontTailoringController::class, 'services'])->name('storefront.tailoring.services');
        Route::post('/storefront/tailoring', [AdminStorefrontTailoringController::class, 'storeService'])->name('storefront.tailoring.store');
        Route::put('/storefront/tailoring/{service}', [AdminStorefrontTailoringController::class, 'updateService'])->name('storefront.tailoring.update');
        Route::get('/storefront/inquiries', [AdminStorefrontTailoringController::class, 'inquiries'])->name('storefront.inquiries.index');
        Route::patch('/storefront/inquiries/{inquiry}', [AdminStorefrontTailoringController::class, 'updateInquiry'])->name('storefront.inquiries.update');
    });
    Route::middleware(['business.permission:storefront.manage', 'business.permission:clothing.sales'])->group(function () {
        Route::get('/storefront/orders', [AdminStorefrontOrderController::class, 'index'])->name('storefront.orders.index');
        Route::patch('/storefront/orders/{order}', [AdminStorefrontOrderController::class, 'update'])->name('storefront.orders.update');
    });

    Route::middleware('business.permission:settings.manage')->group(function () {
    Route::get('setting', [SettingController::class, 'list'])->name('setting.index');
    Route::get('setting/add', [SettingController::class, 'add'])->name('add-setting');
    Route::post('setting/insert', [SettingController::class, 'insert'])->name('insert-setting');
    Route::get('setting/edit/{id}', [SettingController::class, 'edit'])->name('edit-setting');
    Route::post('setting/update/{id}', [SettingController::class, 'update'])->name('update-setting');
    Route::delete('setting/delete/{id}', [SettingController::class, 'delete'])->name('delete-setting');
    Route::patch('setting/active/{id}', [SettingController::class, 'active'])->name('active-setting');
    Route::patch('setting/deactive/{id}', [SettingController::class, 'deactive'])->name('deactive-setting');
    });
    Route::get('/user-details', [UserController::class, 'index'])->name('users');
    Route::get('/user-edit/{id}', [UserController::class, 'edit'])->name('user.edit');
    Route::put('/user-update/{id}', [UserController::class, 'update'])->name('user.update');

    Route::middleware('business.permission:team.manage')->group(function () {
        Route::get('/team', [BusinessTeamController::class, 'index'])->name('team.index');
        Route::get('/team/employees', [BusinessTeamController::class, 'employees'])->name('team.employees.index');
        Route::get('/team/roles', [BusinessTeamController::class, 'roles'])->name('team.roles.index');
        Route::get('/team/security', [BusinessTeamController::class, 'security'])->name('team.security');
        Route::post('/team/roles', [BusinessTeamController::class, 'storeRole'])->name('team.roles.store');
        Route::get('/team/roles/{role}/edit', [BusinessTeamController::class, 'editRole'])->name('team.roles.edit');
        Route::put('/team/roles/{role}', [BusinessTeamController::class, 'updateRole'])->name('team.roles.update');
        Route::delete('/team/roles/{role}', [BusinessTeamController::class, 'destroyRole'])->name('team.roles.destroy');
        Route::post('/team/employees', [BusinessTeamController::class, 'storeEmployee'])->name('team.employees.store');
        Route::get('/team/employees/{employee}/edit', [BusinessTeamController::class, 'editEmployee'])->name('team.employees.edit');
        Route::put('/team/employees/{employee}', [BusinessTeamController::class, 'updateEmployee'])->name('team.employees.update');
        Route::patch('/team/employees/{employee}/temporary-password', [BusinessTeamController::class, 'resetPassword'])->name('team.employees.password');
        Route::put('/team/password-policy', [BusinessTeamController::class, 'updatePasswordPolicy'])->name('team.password-policy.update');
    });

    Route::middleware('business.permission:expenses.manage')->group(function () {
    Route::get('/expense', [ExpensesController::class, 'index'])->name('expense.index');
    Route::get('/create-expense', [ExpensesController::class, 'create'])->name('expense.create');
    Route::post('/create-expense', [ExpensesController::class, 'insert'])->name('expense.insert');
    Route::get('/expense-edit/{id}', [ExpensesController::class, 'edit'])->name('expense.edit');
    Route::post('/expense-update/{id}', [ExpensesController::class, 'update'])->name('expense.update');
    Route::delete('/expense-delete/{id}', [ExpensesController::class, 'delete'])->name('expense.delete');
    Route::get('/workers-edit/{id}', [ExpensesController::class, 'workersedit'])->name('worker.edit');
    Route::post('/workers-update/{id}', [ExpensesController::class, 'workersupdate'])->name('worker.update');
    Route::delete('/workers-delete/{id}', [ExpensesController::class, 'workersdelete'])->name('worker.delete');
    Route::post('specific-expense', [ExpensesController::class, 'showSpecificExpense'])->name('expense.specific');
    Route::get('/daily-expense', [ExpensesController::class, 'Dailyindex'])->name('dailyexpense.index');
    Route::get('/create-daily-expense', [ExpensesController::class, 'Dailycreate'])->name('dailyexpense.create');
    Route::post('/create-daily-expense', [ExpensesController::class, 'Dailyinsert'])->name('dailyexpense.insert');
    Route::get('/dailyexpense-edit/{id}', [ExpensesController::class, 'Dailyedit'])->name('dailyexpense.edit');
    Route::post('/dailyexpense-update/{id}', [ExpensesController::class, 'Dailyupdate'])->name('dailyexpense.update');
    Route::delete('/dailyexpense-delete/{id}', [ExpensesController::class, 'Dailydelete'])->name('dailyexpense.delete');
    Route::post('daily-specific-expense', [ExpensesController::class, 'showdailySpecificExpense'])->name('dailyexpense.specific');
    });
});

Route::group(['prefix' => 'admin', 'middleware' => ['auth', 'business.status', 'password.changed', 'role:shop_owner', 'module:tailoring', 'business.activity'], 'as' => 'admin.'], function () {
    Route::get('/tailoring-dashboard', [HomeController::class, 'tailoring'])->name('dashboard.tailoring');
    // Route::get('/test', function(){
    //     event(new NotificationEvent("Testing Web Socket"));
    //     return response()->json('Event Dispacthed');
    // });


    // Route::get('/notifications-stream', [UserController::class, 'notificationsStream'])->name('notifications-stream');

    Route::middleware('business.permission:tailoring.customers')->group(function () {
    Route::resource('/Customers', CustomerController::class);
    Route::post('DirectPayment', [CustomerController::class, 'DirectPayment'])->middleware('business.permission:customers.balances')->name('DirectPayment');
    Route::post('RackNo', [CustomerController::class, 'RackNo'])->name('RackNo');
    Route::get('/export-csv-customers',[CsvController::class,'exportCsv'])->name('customercsv');
    Route::post('/import-csv-customers',[CsvController::class,'importCsv'])->name('customerscsv');
    });

    // Sale
    Route::middleware('business.permission:tailoring.orders')->group(function () {
    Route::resource('/sale', SaleController::class);
    Route::get('sale/print/{id}', [SaleController::class, 'print'])->name('sale-print');
    });

    // OptionTypes
    Route::middleware('business.permission:tailoring.configuration')->group(function () {
    Route::resource('/OptionType', OptionTypeController::class);
    Route::resource('/Options', OptionsController::class);
    Route::resource('/measurement-fields', MeasurementFieldController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::resource('/measurement-templates', MeasurementTemplateController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::get('Options/add/{id}', [OptionsController::class, 'add'])->name('options.add');

    //design
    Route::get('/design', [DesignController::class, 'index'])->name('design.index');
    Route::get('/design/create', [DesignController::class, 'create'])->name('design.create');
    Route::post('/design/store', [DesignController::class, 'store'])->name('design.store');
    Route::get('/design/edit/{id}', [DesignController::class, 'edit'])->name('design.edit');
    Route::post('/design/update/{id}', [DesignController::class, 'update'])->name('design.update');
    Route::delete('/design/delete/{id}', [DesignController::class, 'delete'])->name('design.delete');
    Route::get('/design/price/{id}', [DesignController::class, 'price'])->name('design.price');
    Route::get('Lang-change', [SettingController::class, 'langChange'])->name('language.change');
    });

    // Tailor
    Route::resource('/Tailor', TailorController::class)->middleware('business.permission:tailoring.tailors');
    Route::middleware('business.permission:tailoring.workshop')->group(function () {
    Route::get('tailor-jobs', [TailorJobController::class, 'adminIndex'])->name('tailor-jobs.index');
    Route::get('orders/{order}/workforce', [OrderWorkAssignmentController::class, 'index'])->name('orders.workforce.index');
    Route::post('orders/{order}/workforce', [OrderWorkAssignmentController::class, 'store'])->name('orders.workforce.store');
    Route::patch('orders/{order}/workforce/{assignment}/status', [OrderWorkAssignmentController::class, 'updateStatus'])->name('orders.workforce.status');
    Route::patch('tailor-jobs/{order}/status', [TailorJobController::class, 'updateStatus'])->name('tailor-jobs.status');
    Route::patch('tailor-jobs/{order}/payment', [TailorJobController::class, 'updatePayment'])->name('tailor-jobs.payment');
    Route::post('tailor-jobs/{order}/notifications/{delivery}/retry', [TailorJobController::class, 'retryNotification'])->name('tailor-jobs.notifications.retry');
    Route::post('order-status', [TailorJobController::class, 'updateLegacyStatus'])->name('order.status');
    });
    Route::middleware('business.permission:tailoring.tailors')->group(function () {
    Route::get('production-workers', [ProductionWorkerController::class, 'index'])->name('production-workers.index');
    Route::get('production-workers/create', [ProductionWorkerController::class, 'create'])->name('production-workers.create');
    Route::post('production-workers', [ProductionWorkerController::class, 'store'])->name('production-workers.store');
    Route::get('production-workers/{worker}', [ProductionWorkerController::class, 'show'])->name('production-workers.show');
    Route::get('production-workers/{worker}/edit', [ProductionWorkerController::class, 'edit'])->name('production-workers.edit');
    Route::put('production-workers/{worker}', [ProductionWorkerController::class, 'update'])->name('production-workers.update');
    Route::post('production-work-types', [ProductionWorkerController::class, 'storeWorkType'])->name('production-work-types.store');
    Route::post('production-workers/{worker}/compensation', [ProductionWorkerController::class, 'storeCompensation'])->name('production-workers.compensation.store');
    Route::post('production-workers/{worker}/payments', [ProductionWorkerController::class, 'payment'])->name('production-workers.payments.store');
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
    Route::get('salary/{user_id}', [TailorController::class, 'tailorSalary'])->name('tailor.salary');
    Route::get('tailors-rates/create/{id}', [TailorRateController::class, 'create'])->name('tailor-rates.create');
    Route::post('tailors-rates/store/{id}', [TailorRateController::class, 'store'])->name('tailor-rates.store');
    Route::delete('tailors-rates/delete/{id}', [TailorRateController::class, 'destroy'])->name('tailor-rates.delete');
    Route::post('tailor-weakly-print/{id}', [TailorController::class, 'tailor_weekly'])->name('tailor.weekly-print');
    });

    // order
    Route::middleware('business.permission:tailoring.orders')->group(function () {
    Route::get('/order/edit/{id}', [OrderController::class, 'edit'])->name('order.edit');
    Route::put('/order/update/{id}', [OrderController::class, 'update'])->name('order.update');
    Route::get('/order/{id}', [OrderController::class, 'createOrder'])->name('order.create');
    Route::post('/order/insert', [OrderController::class, 'insert'])->name('order.insert');
    Route::get('/getCustomer', [OrderController::class, 'getCustomer'])->name('getCustomer');
    Route::get('order/print/{id}', [OrderController::class, 'print'])->name('order-print');
    Route::get('order/prints/{id}', [OrderController::class, 'two_prints'])->name('order-prints');
    Route::get('search', [OrderController::class, 'search'])->name('search');
    Route::post('/order/update-rack-no/{orderId}', [OrderController::class, 'updateRackNo'])->name('order.updateRackNo');
    Route::post('/order/order-complete', [OrderController::class, 'orderCompleteNotify'])->name('order.notify');
    Route::post('/save-subscription', [NotificationController::class, 'saveSubscription'])->name('save-subscription');
    Route::post('/save-push-noti',[PushNotificationController::class,'saveSubscription'])->name('save-push');
    Route::get('/total_orde', [OrderController::class, 'totalOrder'])->name('order.total');
    });


    // csv import and export route
});


Route::group(['prefix' => 'admin', 'middleware' => ['auth', 'business.status', 'password.changed', 'role:stock_seller|shop_owner', 'module:clothing', 'business.activity'], 'as' => 'admin.'], function () {
    Route::get('/shop-dashboard', [HomeController::class, 'clothing'])->name('dashboard.clothing');
    Route::middleware('business.permission:clothing.suppliers')->group(function () {
    Route::get('/suppliers', [SupplierController::class, 'index'])->name('suppliers.index');
    Route::post('/suppliers', [SupplierController::class, 'store'])->name('suppliers.store');
    Route::get('/suppliers/{supplier}/edit', [SupplierController::class, 'edit'])->name('suppliers.edit');
    Route::put('/suppliers/{supplier}', [SupplierController::class, 'update'])->name('suppliers.update');
    Route::delete('/suppliers/{supplier}', [SupplierController::class, 'destroy'])->name('suppliers.destroy');
    Route::post('/suppliers/{supplier}/payments', [SupplierController::class, 'payment'])->name('suppliers.payment');
    });
    Route::middleware('business.permission:clothing.purchases')->group(function () {
    Route::get('/purchases', [PurchaseController::class, 'index'])->name('purchases.index');
    Route::get('/purchases/create', [PurchaseController::class, 'create'])->name('purchases.create');
    Route::post('/purchases', [PurchaseController::class, 'store'])->name('purchases.store');
    Route::get('/purchases/{purchase}', [PurchaseController::class, 'show'])->name('purchases.show');
    Route::patch('/purchases/{purchase}/receive', [PurchaseController::class, 'receive'])->name('purchases.receive');
    Route::patch('/purchases/{purchase}/cancel', [PurchaseController::class, 'cancel'])->name('purchases.cancel');
    Route::post('/purchases/{purchase}/payments', [PurchaseController::class, 'payment'])->name('purchases.payment');
    Route::post('/purchases/{purchase}/returns', [PurchaseController::class, 'returnItem'])->name('purchases.return');
    });
    Route::middleware('business.permission:clothing.inventory')->group(function () {
    Route::get('/inventory-ledger', [InventoryLedgerController::class, 'index'])->name('inventory-ledger.index');
    Route::post('/inventory-ledger/adjustments', [InventoryLedgerController::class, 'adjust'])->name('inventory-ledger.adjust');
    Route::get('/inventory-valuation', [InventoryLedgerController::class, 'valuation'])->name('inventory-valuation.index');
    // Route::get('/', [HomeController::class, 'index'])->name('admin');
    // ClothType
    Route::resource('/clothtype', ClothTypeController::class);

    // ClothBrand
    Route::resource('/clothbrand', ClothBrandController::class);

    // Cloth
    Route::resource('/cloth', ClothController::class);
    Route::get('/edit-cloth/{id}/{color}', [ClothController::class, 'editCloth'])->name('edit-cloths');
    Route::post('/delete-cloth', [ClothController::class, 'deleteCloth'])->name('delete-cloths');

    // Cloth Stock
    Route::resource('/stock', ClothStockController::class);

    Route::get('/export-csv-clothes',[CsvController::class,'exportCsvCloths'])->name('clothscsv');
    Route::post('/import-csv-clothes',[CsvController::class,'importCsvCloths'])->name('clothescsv');
    });

    Route::get('/getType', [ClothStockController::class, 'getType'])->middleware('business.permission:clothing.sales|clothing.inventory')->name('cloth-type.lookup');

    Route::middleware('business.permission:clothing.sales')->group(function () {
    Route::get('/cloths-index', [ClothStockController::class, 'index'])->name('cloths.index');
    Route::get('sellcloth', [ClothStockController::class, 'sellCloth'])->name('sellCloth');
    Route::get('/getSale', [ClothStockController::class, 'getSale'])->name('getSale');

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
    Route::delete('/dlt/{id}', [ClothStockController::class, 'dlt'])->name('dlt');
    Route::post('SaleDirectPayment', [CustomerController::class, 'SaleDirectPayment'])->middleware('business.permission:customers.balances')->name('sale-direct-payment');


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
});

Route::group(['middleware' => 'Tailor', 'prefix' => 'tailor'], function () {
    Route::get('tailor-dashboard', [TailorController::class, 'tailor_dashboard']);
    Route::get('tailor-order-list', [TailorJobController::class, 'tailorIndex'])->name('tailor.jobs.index');
    Route::patch('jobs/{order}/status', [TailorJobController::class, 'updateStatus'])->name('tailor.jobs.status');
    Route::get('logout', [TailorController::class, 'logout']);
    Route::post('tailor-weakly-print/{id}', [TailorController::class, 'tailor_weekly']);
    // order-status
    Route::post('order-status', [TailorJobController::class, 'updateLegacyStatus']);
});

Route::get('tailor-login', [TailorController::class, 'tailor_login']);
Route::post('tailor-login', [TailorController::class, 'login'])->middleware('throttle:5,1');


// for user come for shopping
Route::group(['prefix' => 'user', 'middleware' => ['auth', 'role:user'], 'as' => 'user.'], function () {
    Route::get('/home', [SaleCustomerController::class, 'shops'])->name('shops');
    Route::get('/{slug}', [SaleCustomerController::class, 'saleCustomer'])->name('selling');
    Route::get('/{slug}/stock/{id}', [SaleCustomerController::class, 'Stock'])->name('customer.stock');
    Route::post('/{slug}/stock', [SaleCustomerController::class, 'stockSearch'])->name('stock.search');
    Route::get('/{slug}/stock_check/{brand_id}/{type_id}/{color}', [SaleCustomerController::class, 'ShowStock'])->name('customer.stock.show');


    Route::post('/{slug}/add_cart', [CartController::class, 'AddCart'])->name('stock.cart');

    Route::get('/{slug}/show_cart', [CartController::class, 'ShowCart'])->name('cart.show');
    Route::delete('/{slug}/delete_cart/{id}', [CartController::class, 'DeleteCart'])->name('cart.delete');
    Route::post('/{slug}/buy_cart/{id}', [CartController::class, 'BuyCart'])->name('cart.buy');

    Route::post('/{slug}/add_order', [CartController::class, 'AddOrder'])->name('stock.order');
    Route::get('/{slug}/thank_you', [CartController::class, 'ThankYou'])->name('thank_you');


    Route::get('/{slug}/customer-details', [SaleCustomerController::class, 'AccountDetails'])->name('customers.details');
    Route::get('edit/{id}', [SaleCustomerController::class, 'edit'])->name('customers.edit');
    Route::post('update/{id}', [SaleCustomerController::class, 'update'])->name('customers.update');
    Route::delete('/{slug}/delete/{id}', [SaleCustomerController::class, 'delete'])->name('customers.delete');

    Route::get('/{slug}/order-history', [CartController::class, 'ShowOrderHistory'])->name('history');

    Route::patch('/{slug}/cancel-order/{id}', [CartController::class, 'CancelOrder'])->name('order.cancel');

    Route::post('/{slug}/again-order/{id}', [CartController::class, 'AgainOrder'])->name('order.again');
});
