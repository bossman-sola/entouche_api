<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Item;
use App\Services\ItemService;
use Illuminate\Http\Request;

class ItemController extends BaseApiController
{
    public function __construct(private readonly ItemService $items)
    {
    }

    public function index(Request $request)
    {
        return $this->paginated($this->items->list($request->all()));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'category_id' => ['nullable', 'exists:categories,id'],
            'unit_of_measure_id' => ['required', 'exists:units_of_measure,id'],
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'barcode' => ['nullable', 'string', 'unique:items,barcode'],
            'name' => ['required', 'string'],
            'item_type' => ['sometimes', 'string'],
            'brand' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'unit_cost' => ['sometimes', 'numeric', 'min:0'],
            'selling_price' => ['sometimes', 'numeric', 'min:0'],
            'reorder_level' => ['sometimes', 'numeric', 'min:0'],
            'image' => ['nullable', 'image', 'max:2048'],
            'status' => ['sometimes', 'in:active,inactive'],
        ]);

        return $this->created($this->items->create($data, $request->file('image')), 'Item created');
    }

    public function show(Item $item)
    {
        return $this->success($item->load(['category', 'unit', 'supplier', 'stockBalances']));
    }

    public function update(Request $request, Item $item)
    {
        $data = $request->validate([
            'category_id' => ['nullable', 'exists:categories,id'],
            'unit_of_measure_id' => ['sometimes', 'exists:units_of_measure,id'],
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'barcode' => ['nullable', 'string', 'unique:items,barcode,' . $item->id],
            'name' => ['sometimes', 'string'],
            'item_type' => ['sometimes', 'string'],
            'brand' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'unit_cost' => ['sometimes', 'numeric', 'min:0'],
            'selling_price' => ['sometimes', 'numeric', 'min:0'],
            'reorder_level' => ['sometimes', 'numeric', 'min:0'],
            'image' => ['nullable', 'image', 'max:2048'],
            'status' => ['sometimes', 'in:active,inactive'],
        ]);

        return $this->success($this->items->update($item, $data, $request->file('image')), 'Item updated');
    }

    public function destroy(Item $item)
    {
        if ($item->stockBalances()->sum('quantity_on_hand') > 0) {
            return $this->error('Cannot delete item with stock on hand.', null, 422);
        }

        $item->delete();

        return $this->success(null, 'Item deleted');
    }

    public function uploadImage(Request $request, Item $item)
    {
        $request->validate(['image' => ['required', 'image', 'max:2048']]);

        return $this->success($this->items->uploadImage($item, $request->file('image')), 'Image uploaded');
    }

    public function removeImage(Item $item)
    {
        return $this->success($this->items->removeImage($item), 'Image removed');
    }

    public function transactions(Item $item)
    {
        return $this->success($item->transactions()->latest('transaction_date')->paginate(20));
    }

    public function stockBalance(Item $item)
    {
        return $this->success($this->items->getStockBalance($item));
    }

    public function toggleStatus(Item $item)
    {
        $item->update(['status' => $item->status === 'active' ? 'inactive' : 'active']);

        return $this->success($item->fresh(), 'Status updated');
    }
}
