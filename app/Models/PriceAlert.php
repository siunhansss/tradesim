<?php

namespace App\Models;

use App\Enums\AlertCondition;
use App\Enums\AlertStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PriceAlert extends Model
{
    /** Tối đa số cảnh báo đang bật cho mỗi người dùng (BR-19). */
    public const MAX_ACTIVE_PER_USER = 20;

    protected $fillable = ['user_id', 'stock_id', 'condition', 'target_price', 'status', 'triggered_at', 'triggered_price'];

    protected function casts(): array
    {
        return [
            'condition' => AlertCondition::class,
            'status' => AlertStatus::class,
            'target_price' => 'integer',
            'triggered_price' => 'integer',
            'triggered_at' => 'datetime',
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
}
