<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sku' => $this->sku,
            'barcode' => $this->barcode,
            'name' => $this->name,
            'item_type' => $this->item_type,
            'brand' => $this->brand,
            'description' => $this->description,
            'unit_cost' => $this->unit_cost,
            'selling_price' => $this->selling_price,
            'reorder_level' => $this->reorder_level,
            'image_path' => $this->image_path,
            'status' => $this->status,
            'category' => $this->whenLoaded('category'),
            'unit' => $this->whenLoaded('unit'),
            'supplier' => $this->whenLoaded('supplier'),
            'stock_balances' => $this->whenLoaded('stockBalances'),
        ];
    }
}
