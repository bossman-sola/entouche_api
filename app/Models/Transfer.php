<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transfer extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'transfer_number',
        'from_warehouse_id',
        'from_location_id',
        'to_warehouse_id',
        'to_location_id',
        'requested_by',
        'approved_by',
        'completed_by',
        'created_by',
        'transfer_date',
        'approved_at',
        'completed_at',
        'status',
        'notes',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'transfer_date' => 'date',
            'approved_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function items()
    {
        return $this->hasMany(TransferItem::class);
    }

    public function requester()
    {
        return $this->belongsTo(
            User::class,
            'requested_by'
        );
    }

    public function approver()
    {
        return $this->belongsTo(
            User::class,
            'approved_by'
        );
    }

    public function completer()
    {
        return $this->belongsTo(
            User::class,
            'completed_by'
        );
    }

    public function creator()
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }
}
