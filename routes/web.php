<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\OrderAdminController;
use App\Http\Controllers\Admin\ServiceController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\OrderController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes - Customer Portal (Milestone 2)
|--------------------------------------------------------------------------
*/

// Customer Homepage (Catalog, Order Form & Tracking Search)
Route::get('/', [OrderController::class, 'index'])->name('home');

// Customer Order Submission (Database Transaction & Recalculation)
Route::post('/pesan', [OrderController::class, 'store'])->name('order.store');

// Customer Receipt View (Nota Breakdown, Print Action, WhatsApp Dispatch)
Route::get('/nota/{order}', [OrderController::class, 'show'])->name('order.show')->whereNumber('order');

/*
|--------------------------------------------------------------------------
| Admin Authentication Routes (Milestone 3 - Part 1)
|--------------------------------------------------------------------------
*/

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

/*
|--------------------------------------------------------------------------
| Admin Portal Protected Routes (Milestone 3 - Parts 2 & 3)
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->prefix('admin')->name('admin.')->group(function () {
    // Admin Dashboard (Overview Metrics & Recent Orders)
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Service Package Management (CRUD)
    Route::get('/layanan', [ServiceController::class, 'index'])->name('services.index');
    Route::post('/layanan', [ServiceController::class, 'store'])->name('services.store');
    Route::match(['put', 'patch'], '/layanan/{service}', [ServiceController::class, 'update'])->name('services.update');
    Route::delete('/layanan/{service}', [ServiceController::class, 'destroy'])->name('services.destroy');

    // Order Workflow & Status Management
    Route::get('/pesanan', [OrderAdminController::class, 'index'])->name('orders.index');
    Route::patch('/pesanan/{order}/status', [OrderAdminController::class, 'updateStatus'])->name('orders.update-status');
    Route::delete('/pesanan/{order}', [OrderAdminController::class, 'destroy'])->name('orders.destroy');
});
