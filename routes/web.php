<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FinanceController;
use App\Http\Controllers\MarketController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PortfolioController;
use App\Http\Controllers\PriceAlertController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TradeController;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\WatchlistController;
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
    Route::get('/orders', [OrderController::class, 'index'])->middleware('can:trade')->name('orders.index');
    // Hủy lệnh: chỉ chủ lệnh, lệnh còn mở (OrderPolicy::cancel).
    Route::delete('/orders/{order}', [OrderController::class, 'destroy'])->middleware('can:cancel,order')->name('orders.destroy');
    Route::get('/trades', [TradeController::class, 'index'])->middleware('can:trade')->name('trades.index');

    // M03 – Ví ảo và sổ dòng tiền.
    Route::get('/wallet', [WalletController::class, 'index'])->middleware('can:trade')->name('wallet.index');

    // M06 – Quản lý tài chính: tổng quan, báo cáo tháng, CSV.
    Route::middleware('can:trade')->group(function () {
        Route::get('/finance', [FinanceController::class, 'index'])->name('finance.index');
        Route::get('/finance/reports/{month}', [FinanceController::class, 'report'])->name('finance.report');
        Route::get('/finance/reports/{month}/csv', [FinanceController::class, 'csv'])->name('finance.report.csv');
    });

    // M05 – Danh mục.
    Route::get('/portfolio', [PortfolioController::class, 'index'])->middleware('can:trade')->name('portfolio.index');

    // M09 – Theo dõi, cảnh báo giá, thông báo.
    Route::middleware('can:trade')->group(function () {
        Route::get('/watchlist', [WatchlistController::class, 'index'])->name('watchlist.index');
        Route::post('/watchlist', [WatchlistController::class, 'store'])->name('watchlist.store');
        Route::delete('/watchlist/{stock}', [WatchlistController::class, 'destroy'])->name('watchlist.destroy');
        Route::resource('alerts', PriceAlertController::class)->except(['create', 'show']);
    });
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
});

// M10 – Quản trị: chỉ Admin (FR-02).
Route::middleware(['auth', 'can:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::redirect('/', '/admin/stocks')->name('index');
    Route::resource('stocks', Admin\StockController::class)->except('show');
    Route::resource('users', Admin\UserController::class)->except('show');
    Route::get('/reconcile', [Admin\ReconcileController::class, 'index'])->name('reconcile.index');
    Route::post('/reconcile', [Admin\ReconcileController::class, 'store'])->name('reconcile.store');
    Route::get('/audit-logs', [Admin\AuditLogController::class, 'index'])->name('audit.index');
    Route::get('/settings', [Admin\SettingController::class, 'edit'])->name('settings.edit');
    Route::put('/settings', [Admin\SettingController::class, 'update'])->name('settings.update');
    Route::post('/prices/run', [Admin\SettingController::class, 'runPrices'])->name('prices.run');
    Route::post('/session/close', [Admin\SettingController::class, 'closeSession'])->name('session.close');
});
