<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockBalance extends Model
{
    protected $fillable = [
        'item_id',
        'warehouse_id',
        'warehouse_location_id',
        'quantity_on_hand',
        'quantity_reserved',
        'quantity_available',
        'last_transaction_at',
    ];

    protected function casts(): array
    {
        return ['last_transaction_at' => 'datetime'];
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class, 'warehouse_location_id');
    }
}
