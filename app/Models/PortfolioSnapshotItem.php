<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PortfolioSnapshotItem extends Model
{
    public $timestamps = false;

    protected $fillable = ['portfolio_snapshot_id', 'stock_id', 'quantity', 'price', 'value', 'weight'];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'price' => 'integer',
            'value' => 'integer',
            'weight' => 'decimal:4',
        ];
    }

    public function snapshot(): BelongsTo
    {
        return $this->belongsTo(PortfolioSnapshot::class, 'portfolio_snapshot_id');
    }

    public function stock(): BelongsTo
    {
        return $this->belongsTo(Stock::class);
    }
}
