<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Holding extends Model
{
    protected $fillable = ['user_id', 'stock_id', 'quantity', 'reserved_quantity', 'total_cost', 'realized_pnl'];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'reserved_quantity' => 'integer',
            'total_cost' => 'integer',
            'realized_pnl' => 'integer',
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

    /** Cổ phiếu khả dụng = nắm giữ - đang giữ cho lệnh bán (BR-05). */
    public function availableQuantity(): int
    {
        return $this->quantity - $this->reserved_quantity;
    }

    /** Giá vốn bình quân gia quyền, đã gồm phí mua (mục 6.2). */
    public function averageCost(): float
    {
        return $this->quantity > 0 ? $this->total_cost / $this->quantity : 0.0;
    }
}
