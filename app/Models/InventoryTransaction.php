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

    protected function casts(): array
    {
        return ['transaction_date' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::updating(function (): void {
            throw new \RuntimeException('Inventory transactions are immutable.');
        });

        static::deleting(function (): void {
            throw new \RuntimeException('Inventory transactions are immutable.');
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
        return $this->belongsTo(Location::class, 'warehouse_location_id');
    }

    public function performer()
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}
