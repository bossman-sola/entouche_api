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
        'count_type',
        'priority',
        'counted_by',
        'submitted_by',
        'approved_by',
        'rejected_by',
        'recount_requested_by',
        'created_by',
        'count_date',
        'start_time',
        'started_at',
        'submitted_at',
        'completed_at',
        'approved_at',
        'rejected_at',
        'recount_requested_at',
        'status',
        'notes',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'count_date' => 'date',
            'started_at' => 'datetime',
            'submitted_at' => 'datetime',
            'completed_at' => 'datetime',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'recount_requested_at' => 'datetime',
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

    public function assignedCounter()
    {
        return $this->belongsTo(User::class, 'counted_by');
    }

    public function submittedBy()
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejectedBy()
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isInProgress(): bool
    {
        return $this->status === 'in_progress';
    }

    public function isPendingReview(): bool
    {
        return $this->status === 'pending_review';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function isEditable(): bool
    {
        return in_array($this->status, ['draft', 'in_progress'], true);
    }
}
