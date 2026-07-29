<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Item extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'category_id',
        'unit_of_measure_id',
        'supplier_id',
        'sku',
        'barcode',
        'name',
        'manufacturer',
        'model_number',
        'item_type',
        'brand',
        'description',
        'unit_cost',
        'selling_price',
        'reorder_level',
        'image_path',
        'status',
        'created_by',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class, 'unit_of_measure_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function stockBalances()
    {
        return $this->hasMany(StockBalance::class);
    }

    public function transactions()
    {
        return $this->hasMany(InventoryTransaction::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
