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
        'counted_at',
        'counted_by',
        'adjustment_created',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'counted_at' => 'datetime',
        ];
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class, 'warehouse_location_id');
    }

    public function countedByUser()
    {
        return $this->belongsTo(User::class, 'counted_by');
    }

    public function getIsCountedAttribute(): bool
    {
        return $this->counted_at !== null;
    }

    public function getVarianceAttribute(): float
    {

        return (float) $this->variance_quantity;
    }
}
