<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Cấu hình thị trường dạng khóa – giá trị (mục 6.2).
 * Khóa dùng trong hệ thống: market_open, fee_rate, sell_tax_rate, initial_capital,
 * price_interval_seconds, snapshot_time, price_volatility, max_fill_per_tick.
 */
class MarketSetting extends Model
{
    public const DEFAULTS = [
        'market_open' => ['1', 'Phiên mô phỏng đang mở (1) hay đóng (0)'],
        'fee_rate' => ['0.0015', 'Tỷ lệ phí giao dịch cho cả mua và bán'],
        'sell_tax_rate' => ['0.001', 'Tỷ lệ thuế bán mô phỏng'],
        'initial_capital' => ['100000000', 'Vốn khởi tạo cho tài khoản mới (VND)'],
        'price_interval_seconds' => ['30', 'Chu kỳ cập nhật giá trong phiên'],
        'snapshot_time' => ['15:05', 'Giờ chụp ảnh danh mục cuối ngày (giờ Việt Nam)'],
        'price_volatility' => ['0.011', 'Biên dao động giá tối đa mỗi lần cập nhật (1,1%)'],
        'max_fill_per_tick' => ['1000', 'Khối lượng khớp tối đa cho một lệnh LO mỗi lần cập nhật giá'],
    ];

    protected $fillable = ['key', 'value', 'description'];

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget('market_settings'));
        static::deleted(fn () => Cache::forget('market_settings'));
    }

    public static function allValues(): array
    {
        return Cache::rememberForever('market_settings', fn () => static::query()->pluck('value', 'key')->all());
    }

    public static function value(string $key, mixed $default = null): mixed
    {
        return static::allValues()[$key] ?? $default ?? (self::DEFAULTS[$key][0] ?? null);
    }

    public static function put(string $key, string $value): void
    {
        static::query()->updateOrCreate(['key' => $key], ['value' => $value]);
    }

    public static function isMarketOpen(): bool
    {
        return (bool) (int) static::value('market_open');
    }

    public static function initialCapital(): int
    {
        return (int) static::value('initial_capital');
    }
}
