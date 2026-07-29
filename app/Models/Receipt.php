<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Receipt extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'receipt_number',
        'invoice_number',
        'vendor_po_number',
        'api_po_number',
        'supplier_id',
        'warehouse_id',
        'receiving_location_id',
        'received_by',
        'created_by',
        'receipt_date',
        'payment_date',
        'received_at',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return ['receipt_date' => 'date', 'payment_date' => 'date', 'received_at' => 'datetime'];
    }

    public function items()
    {
        return $this->hasMany(ReceiptItem::class);
    }

    public function scopeReceived($query)
    {
        return $query->where('status', 'received');
    }
}
