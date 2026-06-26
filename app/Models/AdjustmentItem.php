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
}
