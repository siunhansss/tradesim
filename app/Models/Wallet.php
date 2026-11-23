<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Wallet extends Model
{
    protected $fillable = ['user_id', 'balance', 'reserved'];

    protected function casts(): array
    {
        return [
            'balance' => 'integer',
            'reserved' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class);
    }

    /** Số dư khả dụng = tổng số dư - tiền đang giữ. */
    public function available(): int
    {
        return $this->balance - $this->reserved;
    }
}
