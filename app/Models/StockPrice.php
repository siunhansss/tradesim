<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockPrice extends Model
{
    public $timestamps = false;

    protected $fillable = ['stock_id', 'price', 'volume', 'source', 'recorded_at'];

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'volume' => 'integer',
            'recorded_at' => 'datetime',
        ];
    }

    public function stock(): BelongsTo
    {
        return $this->belongsTo(Stock::class);
    }
}
