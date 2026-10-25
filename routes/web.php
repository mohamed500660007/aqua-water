<?php

use App\Domain\Settings\Settings;
use App\Http\Controllers\Complaints\ComplaintController;
use App\Http\Controllers\Customers\CustomerController;
use App\Http\Controllers\Customers\CustomerImportController;
use App\Http\Controllers\Gifts\GiftController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Inventory\CustodySettlementController;
use App\Http\Controllers\Inventory\EmployeeStockController;
use App\Http\Controllers\Inventory\PurchaseOrderController;
use App\Http\Controllers\Inventory\StockMovementController;
use App\Http\Controllers\Maintenance\MaintenanceController;
use App\Http\Controllers\Maintenance\MaintenancePlanController;
use App\Http\Controllers\Payroll\PayrollController;
use App\Http\Controllers\Products\ProductController;
use App\Http\Controllers\Reports\ReportController;
use App\Http\Controllers\Sales\ContractImportController;
use App\Http\Controllers\Sales\InstallmentController;
use App\Http\Controllers\Sales\SalesContractController;
use App\Http\Controllers\Settings\SettingsController;
use App\Http\Controllers\Users\UserController;
use Illuminate\Support\Facades\Route;

/*
| لوحة تحكم أكوا ووتر — كل الصفحات تعمل على قاعدة البيانات.
*/

Route::redirect('/', '/dashboard');

Route::prefix('dashboard')->group(function () {
    Route::get('/', HomeController::class)->name('home');

    // ملف التقارير: فهرس كل تقارير الشركة + تقارير الإدارة العابرة للوحدات
    Route::prefix('reports')->name('reports.')->controller(ReportController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('executive', 'executive')->name('executive');
        Route::get('finance', 'finance')->name('finance');
        Route::get('custody', 'custody')->name('custody');
        Route::get('maintenance', 'maintenance')->name('maintenance');
        Route::get('after-sales', 'afterSales')->name('after-sales');
    });

    // ملف الإعدادات: قسم لكل مجموعة، يُحفظ ويُستعاد لقيمه الافتراضية مستقلًا
    Route::get('settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::put('settings/{section}', [SettingsController::class, 'update'])->name('settings.update')
        ->whereIn('section', array_keys(Settings::sections()));
    Route::delete('settings/{section}', [SettingsController::class, 'reset'])->name('settings.reset')
        ->whereIn('section', array_keys(Settings::sections()));

    // قبل الـ resource حتى لا يُفهم «report» كمعرّف عقد
    Route::get('contracts/report', [SalesContractController::class, 'report'])->name('contracts.report');
    Route::get('contracts/import', [ContractImportController::class, 'create'])->name('contracts.import.create');
    Route::get('contracts/import/template', [ContractImportController::class, 'template'])->name('contracts.import.template');
    Route::post('contracts/import', [ContractImportController::class, 'store'])->name('contracts.import.store');
    Route::get('contracts/{contract}/panel', [SalesContractController::class, 'panel'])->name('contracts.panel');
    Route::resource('contracts', SalesContractController::class)
        ->except('destroy')
        ->parameters(['contracts' => 'contract']);
    Route::post('contracts/{contract}/legal', [SalesContractController::class, 'referToLegal'])->name('contracts.legal.store');
    Route::delete('contracts/{contract}/legal', [SalesContractController::class, 'withdrawFromLegal'])->name('contracts.legal.destroy');

    Route::get('installments', [InstallmentController::class, 'index'])->name('installments.index');
    Route::get('installments/report', [InstallmentController::class, 'report'])->name('installments.report');
    Route::get('installments/print', [InstallmentController::class, 'printBatch'])->name('installments.print');
    Route::post('installments/{installment}/collect', [InstallmentController::class, 'collect'])->name('installments.collect');
    Route::delete('installments/{installment}/collect', [InstallmentController::class, 'uncollect'])->name('installments.uncollect');
    Route::get('installments/{installment}/receipt', [InstallmentController::class, 'receipt'])->name('installments.receipt');

    // قبل الـ resource حتى لا يُفهم «report» كمعرّف عميل
    Route::get('customers/report', [CustomerController::class, 'report'])->name('customers.report');
    Route::get('customers/import', [CustomerImportController::class, 'create'])->name('customers.import.create');
    Route::get('customers/import/template', [CustomerImportController::class, 'template'])->name('customers.import.template');
    Route::post('customers/import', [CustomerImportController::class, 'store'])->name('customers.import.store');
    Route::get('customers/{customer}/panel', [CustomerController::class, 'panel'])->name('customers.panel');
    Route::resource('customers', CustomerController::class)->except('destroy');
    // قبل الـ resource حتى لا يُفهم «report» كمعرّف منتج
    Route::get('products/report', [ProductController::class, 'report'])->name('products.report');
    Route::resource('products', ProductController::class);
    // قبل الـ resource حتى لا يُفهم «report» كمعرّف موظف
    Route::get('users/report', [UserController::class, 'report'])->name('users.report');
    Route::resource('users', UserController::class);
    Route::get('payroll', [PayrollController::class, 'index'])->name('payroll.index');
    Route::get('payroll/report', [PayrollController::class, 'report'])->name('payroll.report');
    Route::post('payroll/{user}', [PayrollController::class, 'store'])->name('payroll.store');
    Route::get('payroll/payslips/{payroll}', [PayrollController::class, 'payslip'])->name('payroll.payslip');
    Route::post('payroll/payslips/{payroll}/payments', [PayrollController::class, 'storePayment'])->name('payroll.payments.store');
    Route::delete('payroll/payments/{payment}', [PayrollController::class, 'destroyPayment'])->name('payroll.payments.destroy');

    // خطة الصيانة الوحيدة: سبع شمعات بمواعيد ثابتة
    Route::get('maintenance/plan', [MaintenancePlanController::class, 'show'])->name('maintenance.plan');

    // ملف الصيانة: سطر لكل شمعة لم تتم — «إضافة» تسجّل الصيانة و«تعديل» يغيّر الفني والتاريخ
    Route::get('maintenance', [MaintenanceController::class, 'index'])->name('maintenance.file');
    Route::get('maintenance/candles/{candle}/create', [MaintenanceController::class, 'create'])->name('maintenance.create');
    Route::post('maintenance/candles/{candle}', [MaintenanceController::class, 'store'])->name('maintenance.store');
    Route::patch('maintenance/candles/{candle}', [MaintenanceController::class, 'update'])->name('maintenance.update');

    Route::get('complaints', [ComplaintController::class, 'index'])->name('complaints.index');
    Route::get('complaints/create', [ComplaintController::class, 'create'])->name('complaints.create');
    Route::post('complaints', [ComplaintController::class, 'store'])->name('complaints.store');
    Route::patch('complaints/{complaint}/resolve', [ComplaintController::class, 'resolve'])->name('complaints.resolve');
    Route::delete('complaints/{complaint}', [ComplaintController::class, 'destroy'])->name('complaints.destroy');

    Route::get('gifts', [GiftController::class, 'index'])->name('gifts.index');
    Route::get('gifts/create', [GiftController::class, 'create'])->name('gifts.create');
    Route::post('gifts', [GiftController::class, 'store'])->name('gifts.store');
    Route::delete('gifts/{gift}', [GiftController::class, 'destroy'])->name('gifts.destroy');

    Route::get('inventory/add-stock', [StockMovementController::class, 'create'])->name('inventory.stock.create');
    Route::post('inventory/add-stock', [StockMovementController::class, 'store'])->name('inventory.stock.store');

    Route::get('inventory/purchase-orders', [PurchaseOrderController::class, 'index'])->name('inventory.purchase-orders.index');
    Route::get('inventory/purchase-orders/create', [PurchaseOrderController::class, 'create'])->name('inventory.purchase-orders.create');
    Route::get('inventory/purchase-orders/report', [PurchaseOrderController::class, 'report'])->name('inventory.purchase-orders.report');
    Route::get('inventory/purchase-orders/{purchaseOrder}/panel', [PurchaseOrderController::class, 'panel'])->name('inventory.purchase-orders.panel');
    Route::post('inventory/purchase-orders', [PurchaseOrderController::class, 'store'])->name('inventory.purchase-orders.store');
    Route::delete('inventory/purchase-orders/{purchaseOrder}', [PurchaseOrderController::class, 'destroy'])->name('inventory.purchase-orders.destroy');

    Route::get('inventory/employee-stocks', [EmployeeStockController::class, 'index'])->name('inventory.employee-stocks.index');
    Route::get('inventory/employee-stocks/create', [EmployeeStockController::class, 'create'])->name('inventory.employee-stocks.create');
    Route::post('inventory/employee-stocks', [EmployeeStockController::class, 'store'])->name('inventory.employee-stocks.store');
    Route::get('inventory/employee-stocks/{user}', [EmployeeStockController::class, 'show'])->name('inventory.employee-stocks.show');

    Route::get('inventory/custody-settlements', [CustodySettlementController::class, 'index'])->name('inventory.custody-settlements.index');
    Route::get('inventory/custody-settlements/create', [CustodySettlementController::class, 'create'])->name('inventory.custody-settlements.create');
    Route::post('inventory/custody-settlements', [CustodySettlementController::class, 'store'])->name('inventory.custody-settlements.store');
    Route::get('inventory/custody-settlements/{settlement}/panel', [CustodySettlementController::class, 'panel'])->name('inventory.custody-settlements.panel');
    Route::get('inventory/custody-settlements/{settlement}/edit', [CustodySettlementController::class, 'edit'])->name('inventory.custody-settlements.edit');
    Route::put('inventory/custody-settlements/{settlement}', [CustodySettlementController::class, 'update'])->name('inventory.custody-settlements.update');
    Route::post('inventory/custody-settlements/{settlement}/post', [CustodySettlementController::class, 'post'])->name('inventory.custody-settlements.post');
    Route::delete('inventory/custody-settlements/{settlement}', [CustodySettlementController::class, 'destroy'])->name('inventory.custody-settlements.destroy');
});
