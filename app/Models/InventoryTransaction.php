<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryTransaction extends Model
{
    protected $fillable = [
        'transaction_number',
        'item_id',
        'warehouse_id',
        'warehouse_location_id',
        'transaction_type',
        'direction',
        'quantity',
        'balance_before',
        'balance_after',
        'unit_cost',
        'total_value',
        'reference_type',
        'reference_id',
        'remarks',
        'performed_by',
        'transaction_date',
    ];

    protected $appends = [
        'signed_quantity',
        'signed_total_value',
    ];

    protected function casts(): array
    {
        return [
            'transaction_date' => 'datetime',
        ];
    }

    public function getSignedQuantityAttribute(): float
    {
        $quantity = (float) $this->quantity;

        return $this->direction === 'out'
            ? -abs($quantity)
            : abs($quantity);
    }

    public function getSignedTotalValueAttribute(): float
    {
        $total = (float) $this->total_value;

        return $this->direction === 'out'
            ? -abs($total)
            : abs($total);
    }

    protected static function booted(): void
    {
        static::updating(function (): void {
            throw new \RuntimeException(
                'Inventory transactions are immutable.'
            );
        });

        static::deleting(function (): void {
            throw new \RuntimeException(
                'Inventory transactions are immutable.'
            );
        });
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
        return $this->belongsTo(
            Location::class,
            'warehouse_location_id'
        );
    }

    public function performer()
    {
        return $this->belongsTo(
            User::class,
            'performed_by'
        );
    }
}
