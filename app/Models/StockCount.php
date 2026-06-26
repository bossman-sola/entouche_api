<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StockCount extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'count_number',
        'warehouse_id',
        'warehouse_location_id',
        'counted_by',
        'approved_by',
        'created_by',
        'count_date',
        'started_at',
        'completed_at',
        'approved_at',
        'status',
        'notes',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'count_date' => 'date',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    public function items()
    {
        return $this->hasMany(StockCountItem::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class, 'warehouse_location_id');
    }

    public function isDraft(): bool { return $this->status === 'draft'; }
    public function isInProgress(): bool { return $this->status === 'in_progress'; }
    public function isCompleted(): bool { return $this->status === 'completed'; }
    public function isApproved(): bool { return $this->status === 'approved'; }
}
