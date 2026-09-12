<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Adjustment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'adjustment_number',
        'warehouse_id',
        'warehouse_location_id',
        'adjustment_type',
        'reason',
        'adjusted_by',
        'approved_by',
        'created_by',
        'adjustment_date',
        'approved_at',
        'status',
        'notes',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'adjustment_date' => 'date',
            'approved_at' => 'datetime',
        ];
    }

    public function items()
    {
        return $this->hasMany(AdjustmentItem::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function location()
    {
        return $this->belongsTo(
            Location::class,
            'warehouse_location_id'
        );
    }

    public function adjustedBy()
    {
        return $this->belongsTo(
            User::class,
            'adjusted_by'
        );
    }

    public function approvedBy()
    {
        return $this->belongsTo(
            User::class,
            'approved_by'
        );
    }

    public function createdBy()
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }
}
