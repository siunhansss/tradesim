<?php

namespace App\Models;

use App\Enums\OrderSide;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Một lần khớp lệnh. Không sửa, không xóa (BR-06). */
class Trade extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'order_id', 'user_id', 'stock_id', 'side', 'quantity', 'price', 'value',
        'fee', 'tax', 'fee_rate', 'tax_rate', 'realized_pnl', 'executed_at',
    ];

    protected function casts(): array
    {
        return [
            'side' => OrderSide::class,
            'quantity' => 'integer',
            'price' => 'integer',
            'value' => 'integer',
            'fee' => 'integer',
            'tax' => 'integer',
            'fee_rate' => 'decimal:6',
            'tax_rate' => 'decimal:6',
            'realized_pnl' => 'integer',
            'executed_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function stock(): BelongsTo
    {
        return $this->belongsTo(Stock::class);
    }
}
