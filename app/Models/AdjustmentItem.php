<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdjustmentItem extends Model
{
    protected $fillable = [
        'adjustment_id',
        'item_id',
        'warehouse_location_id',
        'quantity_before',
        'adjustment_quantity',
        'quantity_after',
        'unit_cost',
    ];

    protected function casts(): array
    {
        return [
            'quantity_before' => 'decimal:3',
            'adjustment_quantity' => 'decimal:3',
            'quantity_after' => 'decimal:3',
            'unit_cost' => 'decimal:2',
        ];
    }

    public function adjustment()
    {
        return $this->belongsTo(Adjustment::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function location()
    {
        return $this->belongsTo(
            Location::class,
            'warehouse_location_id'
        );
    }
}
