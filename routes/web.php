<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PackageController;
use App\Http\Controllers\OrderController;

/*
|--------------------------------------------------------------------------
| Web Routes - Sistem Laundry Serkom BNSP
|--------------------------------------------------------------------------
*/

// 1. Landing Page -> Halaman utama sistem laundry otomatis mengalihkan pengunjung yang belum login ke formulir login via redirect('login')
Route::get('/', function () {
    if (Auth::check()) {
        return Auth::user()->role === 'admin'
            ? redirect()->route('admin.packages.index')
            : redirect()->route('customer.catalog');
    }
    return redirect('login');
});

// 2. Rute Autentikasi Publik (Guest)
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'register'])->name('register.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// 3. Rute Terproteksi (Wajib Login via Middleware Bawaan 'auth')
Route::middleware(['auth'])->group(function () {
    // --- Sisi Pelanggan (Customer) ---
    Route::get('/catalog', [OrderController::class, 'catalog'])->name('customer.catalog');
    Route::get('/order/create/{package_id}', [OrderController::class, 'createOrder'])->name('customer.order.create');
    Route::post('/order/store', [OrderController::class, 'storeOrder'])->name('customer.order.store');

    // --- Sisi Administrator (Admin) ---
    // Master Paket Laundry (Resource CRUD 7 Rute Standar)
    Route::resource('/admin/packages', PackageController::class)->names('admin.packages');

    // Rekapitulasi Pesanan & Update Status Transaksi
    Route::get('/admin/orders', [OrderController::class, 'adminOrders'])->name('admin.orders.index');
    Route::patch('/admin/orders/{id}/status', [OrderController::class, 'updateStatus'])->name('admin.orders.updateStatus');
});