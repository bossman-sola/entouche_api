<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Inventory extends Model
{
    protected $table = 'inventory';

    protected $fillable = ['item_id', 'warehouse_id', 'location_id', 'quantity_on_hand', 'quantity_reserved'];
}
