<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReceiptItem extends Model
{
    protected $fillable = ['receipt_id', 'item_id', 'warehouse_location_id', 'quantity', 'unit_cost', 'total_cost'];

    public function item()
    {
        return $this->belongsTo(Item::class);
    }
}
