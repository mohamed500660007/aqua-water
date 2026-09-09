<?php

use App\Http\Controllers\Customers\CustomerController;
use App\Http\Controllers\Inventory\EmployeeStockController;
use App\Http\Controllers\Inventory\StockMovementController;
use App\Http\Controllers\Maintenance\MaintenancePlanController;
use App\Http\Controllers\Maintenance\MaintenanceVisitController;
use App\Http\Controllers\Products\ProductController;
use App\Http\Controllers\Sales\InstallmentController;
use App\Http\Controllers\Sales\SalesContractController;
use App\Http\Controllers\Users\UserController;
use Illuminate\Support\Facades\Route;

/*
| لوحة تحكم أكوا ووتر — كل الصفحات تعمل على قاعدة البيانات.
*/

Route::redirect('/', '/dashboard');

Route::prefix('dashboard')->group(function () {
    Route::redirect('/', '/dashboard/contracts');

    Route::resource('contracts', SalesContractController::class)
        ->parameters(['contracts' => 'contract']);

    Route::get('installments', [InstallmentController::class, 'index'])->name('installments.index');
    Route::post('installments/{installment}/collect', [InstallmentController::class, 'collect'])->name('installments.collect');
    Route::delete('installments/{installment}/collect', [InstallmentController::class, 'uncollect'])->name('installments.uncollect');
    Route::get('installments/{installment}/receipt', [InstallmentController::class, 'receipt'])->name('installments.receipt');

    Route::resource('customers', CustomerController::class);
    Route::resource('products', ProductController::class);
    Route::resource('users', UserController::class);

    Route::resource('maintenance-plans', MaintenancePlanController::class)
        ->parameters(['maintenance-plans' => 'plan']);

    Route::get('maintenance-visits', [MaintenanceVisitController::class, 'index'])->name('maintenance-visits.index');
    Route::get('maintenance-visits/create', [MaintenanceVisitController::class, 'create'])->name('maintenance-visits.create');
    Route::post('maintenance-visits', [MaintenanceVisitController::class, 'store'])->name('maintenance-visits.store');
    Route::delete('maintenance-visits/{visit}', [MaintenanceVisitController::class, 'destroy'])->name('maintenance-visits.destroy');

    Route::get('inventory/add-stock', [StockMovementController::class, 'create'])->name('inventory.stock.create');
    Route::post('inventory/add-stock', [StockMovementController::class, 'store'])->name('inventory.stock.store');

    Route::get('inventory/employee-stocks', [EmployeeStockController::class, 'index'])->name('inventory.employee-stocks.index');
    Route::get('inventory/employee-stocks/create', [EmployeeStockController::class, 'create'])->name('inventory.employee-stocks.create');
    Route::post('inventory/employee-stocks', [EmployeeStockController::class, 'store'])->name('inventory.employee-stocks.store');
});
