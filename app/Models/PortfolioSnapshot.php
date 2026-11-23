<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PortfolioSnapshot extends Model
{
    protected $fillable = ['user_id', 'snapshot_date', 'cash', 'stock_value', 'total_assets', 'realized_pnl', 'unrealized_pnl'];

    protected function casts(): array
    {
        return [
            'snapshot_date' => 'date',
            'cash' => 'integer',
            'stock_value' => 'integer',
            'total_assets' => 'integer',
            'realized_pnl' => 'integer',
            'unrealized_pnl' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PortfolioSnapshotItem::class);
    }
}
