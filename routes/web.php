<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MarketController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PortfolioController;
use App\Http\Controllers\ProfileController;
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

    // M01 – Hồ sơ cá nhân.
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

    // M04 – Đặt lệnh: chỉ Trader đang hoạt động, giới hạn 30 lệnh/phút (mục 8.2).
    Route::post('/orders', [OrderController::class, 'store'])
        ->middleware(['can:trade', 'throttle:orders'])->name('orders.store');

    // M05 – Danh mục.
    Route::get('/portfolio', [PortfolioController::class, 'index'])->middleware('can:trade')->name('portfolio.index');

    // M03, M06, M09 – thêm ở các sprint sau (xem docs/design/routes.md).
});

// M10 – Quản trị: chỉ Admin (FR-02).
Route::middleware(['auth', 'can:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::redirect('/', '/admin/stocks')->name('index');
    Route::resource('stocks', Admin\StockController::class)->except('show');
    Route::get('/settings', [Admin\SettingController::class, 'edit'])->name('settings.edit');
    Route::put('/settings', [Admin\SettingController::class, 'update'])->name('settings.update');
    Route::post('/prices/run', [Admin\SettingController::class, 'runPrices'])->name('prices.run');
});
