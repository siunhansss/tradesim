<?php

namespace App\Models;

use App\Enums\OrderSide;
use App\Enums\OrderStatus;
use App\Enums\OrderType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $fillable = [
        'user_id', 'stock_id', 'side', 'type', 'quantity', 'limit_price',
        'filled_quantity', 'avg_fill_price', 'reserved_amount', 'reserved_quantity',
        'status', 'reject_reason', 'request_key',
    ];

    protected function casts(): array
    {
        return [
            'side' => OrderSide::class,
            'type' => OrderType::class,
            'status' => OrderStatus::class,
            'quantity' => 'integer',
            'limit_price' => 'integer',
            'filled_quantity' => 'integer',
            'avg_fill_price' => 'decimal:4',
            'reserved_amount' => 'integer',
            'reserved_quantity' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function stock(): BelongsTo
    {
        return $this->belongsTo(Stock::class);
    }

    public function trades(): HasMany
    {
        return $this->hasMany(Trade::class);
    }

    public function remainingQuantity(): int
    {
        return $this->quantity - $this->filled_quantity;
    }

    public function isOpen(): bool
    {
        return $this->status->isOpen();
    }
}
