<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Asset extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'asset_tag',
        'serial_number',
        'api_number',
        'acquisition_cost',
        'asset_life',
        'date_acquired',
        'status',
        'notes',
        'item_id',
        'warehouse_id',
        'location_id',
        'supplier_id',
        'import_id',
    ];

    protected function casts(): array
    {
        return [
            'date_acquired' => 'date',
            'acquisition_cost' => 'decimal:2',
        ];
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
        return $this->belongsTo(Location::class, 'location_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function import()
    {
        return $this->belongsTo(Import::class);
    }

    public function locationHistory()
    {
        return $this->hasMany(AssetLocationHistory::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
