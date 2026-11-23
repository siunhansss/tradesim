<?php

namespace App\Models;

use App\Enums\LedgerType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/** Bút toán sổ tiền: chỉ tạo mới, không cập nhật hay xóa. */
class WalletTransaction extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'wallet_id', 'type', 'amount', 'balance_after', 'reserved_after',
        'reference_type', 'reference_id', 'idempotency_key', 'note',
    ];

    protected function casts(): array
    {
        return [
            'type' => LedgerType::class,
            'amount' => 'integer',
            'balance_after' => 'integer',
            'reserved_after' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }
}
