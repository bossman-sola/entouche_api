<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Location extends Model
{
    use SoftDeletes;

    protected $table = 'warehouse_locations';

    protected $fillable = ['warehouse_id', 'name', 'code', 'type', 'status'];

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }
}
