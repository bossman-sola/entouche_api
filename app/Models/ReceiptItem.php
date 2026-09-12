<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReceiptItem extends Model
{
    protected $fillable = [
        'receipt_id',
        'item_id',
        'warehouse_location_id',
        'quantity',
        'unit_cost',
        'total_cost',
    ];

    public function receipt()
    {
        return $this->belongsTo(
            Receipt::class
        );
    }

    public function item()
    {
        return $this->belongsTo(
            Item::class
        );
    }

    public function location()
    {
        return $this->belongsTo(
            Location::class,
            'warehouse_location_id'
        );
    }
}
