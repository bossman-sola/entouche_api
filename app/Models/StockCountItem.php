<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockCountItem extends Model
{
    protected $fillable = [
        'stock_count_id',
        'item_id',
        'warehouse_location_id',
        'system_quantity',
        'counted_quantity',
        'adjustment_created',
        'remarks',
    ];

    public function item()
    {
        return $this->belongsTo(Item::class);
    }
}
