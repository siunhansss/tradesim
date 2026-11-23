<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MarketController;
use Illuminate\Support\Facades\Route;

/*
| Danh sách route đầy đủ theo kế hoạch: docs/design/routes.md
| Mỗi nhóm route ghi phân hệ (M01–M10) và người phụ trách.
*/

Route::redirect('/', '/market')->name('home');

// M02 – Thị trường: khách cũng xem được (tác nhân Guest, mục 2).
Route::get('/market', [MarketController::class, 'index'])->name('market.index');
Route::get('/market/{stock:symbol}', [MarketController::class, 'show'])->name('market.show');

// M01 – Xác thực.
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store'])->middleware('throttle:6,1');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    // M03, M04, M05, M06, M09 – thêm ở Sprint 2–4 (xem docs/design/routes.md).
});

// M10 – Quản trị: chỉ Admin (FR-02).
Route::middleware(['auth', 'can:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::redirect('/', '/admin/stocks')->name('index');
    Route::resource('stocks', Admin\StockController::class)->except('show');
});
