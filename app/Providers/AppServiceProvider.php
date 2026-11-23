<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Phát hiện lỗi N+1 khi phát triển và kiểm thử (mục 8.4).
        Model::preventLazyLoading(! $this->app->isProduction());

        Paginator::useBootstrapFive();

        // Phân quyền (FR-02): khu vực quản trị chỉ dành cho Admin.
        Gate::define('admin', fn (User $user) => $user->isAdmin() && $user->isActive());
        // Chỉ Trader đang hoạt động mới được đặt lệnh (BR-04).
        Gate::define('trade', fn (User $user) => $user->isTrader() && $user->isActive());

        // Giới hạn tần suất (mục 8.2).
        RateLimiter::for('orders', fn (Request $request) => Limit::perMinute(30)->by($request->user()?->id ?: $request->ip()));

        Blade::directive('money', fn (string $expr) => "<?php echo e(\\App\\Support\\Format::money($expr)); ?>");
        Blade::directive('datetime', fn (string $expr) => "<?php echo e(\\App\\Support\\Format::datetime($expr)); ?>");
    }
}
