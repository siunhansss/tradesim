<?php

namespace App\Models;

use App\Enums\StockStatus;
use Database\Factories\StockFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Stock extends Model
{
    /** @use HasFactory<StockFactory> */
    use HasFactory;

    /** Biên độ dao động HOSE: ±7% so với giá tham chiếu. */
    public const PRICE_BAND = 0.07;

    protected $fillable = [
        'symbol', 'name', 'exchange', 'industry', 'description',
        'reference_price', 'ceiling_price', 'floor_price', 'last_price',
        'volume', 'last_price_at', 'price_source', 'status',
    ];

    protected function casts(): array
    {
        return [
            'reference_price' => 'integer',
            'ceiling_price' => 'integer',
            'floor_price' => 'integer',
            'last_price' => 'integer',
            'volume' => 'integer',
            'last_price_at' => 'datetime',
            'status' => StockStatus::class,
        ];
    }

    /** Bước giá HOSE theo mức giá. */
    public static function tickSize(int $price): int
    {
        return match (true) {
            $price < 10_000 => 10,
            $price < 50_000 => 50,
            default => 100,
        };
    }

    /** Tính trần/sàn từ giá tham chiếu, làm tròn vào trong theo bước giá. */
    public static function priceBand(int $reference): array
    {
        $ceiling = (int) floor($reference * (1 + self::PRICE_BAND));
        $floor = (int) ceil($reference * (1 - self::PRICE_BAND));

        return [
            'ceiling_price' => intdiv($ceiling, self::tickSize($ceiling)) * self::tickSize($ceiling),
            'floor_price' => (int) (ceil($floor / self::tickSize($floor)) * self::tickSize($floor)),
        ];
    }

    public function prices(): HasMany
    {
        return $this->hasMany(StockPrice::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function trades(): HasMany
    {
        return $this->hasMany(Trade::class);
    }

    public function holdings(): HasMany
    {
        return $this->hasMany(Holding::class);
    }

    public function priceAlerts(): HasMany
    {
        return $this->hasMany(PriceAlert::class);
    }

    /** Mã hiển thị trên bảng giá (đang giao dịch hoặc tạm ngừng). */
    public function scopeVisible(Builder $query): void
    {
        $query->where('status', '!=', StockStatus::Hidden->value);
    }

    public function scopeSearch(Builder $query, ?string $term): void
    {
        $term = trim((string) $term);
        if ($term === '') {
            return;
        }
        $query->where(function (Builder $q) use ($term) {
            $q->where('symbol', 'like', '%'.strtoupper($term).'%')
                ->orWhere('name', 'like', '%'.$term.'%');
        });
    }

    public function isTradable(): bool
    {
        return $this->status === StockStatus::Active;
    }

    public function change(): int
    {
        return $this->last_price - $this->reference_price;
    }

    public function changePercent(): float
    {
        return $this->reference_price > 0 ? $this->change() / $this->reference_price * 100 : 0.0;
    }

    /** Lớp màu theo quy ước bảng giá: trần, tăng, tham chiếu, giảm, sàn. */
    public function priceClass(?int $price = null): string
    {
        $price ??= $this->last_price;

        return match (true) {
            $price >= $this->ceiling_price => 'px-ceil',
            $price <= $this->floor_price => 'px-floor',
            $price === $this->reference_price => 'px-ref',
            $price > $this->reference_price => 'px-up',
            default => 'px-down',
        };
    }

    /** Có lệnh, giao dịch hoặc nắm giữ thì không được xóa, chỉ được ngừng hiển thị. */
    public function hasTradingHistory(): bool
    {
        return $this->orders()->exists() || $this->trades()->exists() || $this->holdings()->exists();
    }
}
