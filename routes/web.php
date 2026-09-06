<?php

use App\Http\Controllers\Customers\CustomerController;
use App\Http\Controllers\Inventory\EmployeeStockController;
use App\Http\Controllers\Inventory\StockMovementController;
use App\Http\Controllers\Maintenance\MaintenancePlanController;
use App\Http\Controllers\Products\ProductController;
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

    Route::resource('customers', CustomerController::class);
    Route::resource('products', ProductController::class);
    Route::resource('users', UserController::class);

    Route::resource('maintenance-plans', MaintenancePlanController::class)
        ->parameters(['maintenance-plans' => 'plan']);

    Route::get('inventory/add-stock', [StockMovementController::class, 'create'])->name('inventory.stock.create');
    Route::post('inventory/add-stock', [StockMovementController::class, 'store'])->name('inventory.stock.store');

    Route::get('inventory/employee-stocks', [EmployeeStockController::class, 'index'])->name('inventory.employee-stocks.index');
    Route::get('inventory/employee-stocks/create', [EmployeeStockController::class, 'create'])->name('inventory.employee-stocks.create');
    Route::post('inventory/employee-stocks', [EmployeeStockController::class, 'store'])->name('inventory.employee-stocks.store');
});
