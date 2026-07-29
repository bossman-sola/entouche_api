<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssetLocationHistory extends Model
{
    protected $table = 'asset_location_history';

    protected $fillable = [
        'asset_id',
        'warehouse_id',
        'location_id',
        'moved_at',
        'moved_by',
        'reference_type',
        'reference_id',
        'notes',
    ];

    protected function casts(): array
    {
        return ['moved_at' => 'datetime'];
    }

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class, 'location_id');
    }

    public function movedBy()
    {
        return $this->belongsTo(User::class, 'moved_by');
    }
}
