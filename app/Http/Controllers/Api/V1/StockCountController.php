<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Setting;
use App\Models\StockCount;
use App\Models\StockCountItem;
use App\Services\BaseService;
use App\Services\NotificationService;
use App\Services\StockCountService;
use Illuminate\Http\Request;

class StockCountController extends BaseApiController
{
    public function __construct(
        private readonly StockCountService $stockCounts,
        private readonly NotificationService $notifications,
    ) {
    }

    public function index(Request $request) { return $this->paginated($this->stockCounts->list($request->all())); }
    public function show(StockCount $stockCount) { return $this->success($stockCount->load('items.item')); }

    public function store(Request $request)
    {
        $data = $request->validate([
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'warehouse_location_id' => ['nullable', 'exists:warehouse_locations,id'],
            'count_date' => ['nullable', 'date'],
            'items' => ['sometimes', 'array'],
            'items.*.item_id' => ['required', 'exists:items,id'],
            'items.*.system_quantity' => ['sometimes', 'numeric'],
            'items.*.counted_quantity' => ['sometimes', 'numeric'],
        ]);
        $items = $data['items'] ?? []; unset($data['items']);
        $number = (new class extends BaseService {})->generateNumber('stock_counts', 'count_number', Setting::get('numbering.stock_count_prefix', 'SC'), 6);
        $count = StockCount::create($data + ['count_number' => $number, 'counted_by' => auth()->id(), 'created_by' => auth()->id(), 'status' => 'draft', 'count_date' => $data['count_date'] ?? now()->toDateString()]);
        foreach ($items as $row) StockCountItem::create($row + ['stock_count_id' => $count->id, 'warehouse_location_id' => $data['warehouse_location_id'] ?? null]);
        return $this->created($count->load('items'), 'Stock count created');
    }

    public function update(Request $request, StockCount $stockCount)
    {
        if (! $stockCount->isDraft()) return $this->error('Only draft stock counts can be updated.', null, 422);
        $stockCount->update($request->only(['warehouse_id', 'warehouse_location_id', 'count_date', 'notes']));
        return $this->success($stockCount->fresh('items'), 'Stock count updated');
    }

    public function destroy(StockCount $stockCount)
    {
        if (! $stockCount->isDraft()) return $this->error('Only draft stock counts can be deleted.', null, 422);
        $stockCount->delete();
        return $this->success(null, 'Stock count deleted');
    }

    public function start(StockCount $stockCount) { $this->stockCounts->populateSystemQuantities($stockCount); $stockCount->update(['status' => 'in_progress', 'started_at' => now()]); return $this->success($stockCount->fresh('items'), 'Stock count started'); }
    public function complete(StockCount $stockCount)
    {
        $stockCount->update(['status' => 'completed', 'completed_at' => now()]);

        
        foreach ($stockCount->items()->with('item')->get() as $countItem) {
            $variance = (float) $countItem->counted_quantity - (float) $countItem->system_quantity;

            if ($variance == 0.0) {
                continue;
            }

            $itemName = $countItem->item->name ?? 'an item';

            $this->notifications->notifyAdmins(
                'stock_count_variance',
                'Stock Count Variance',
                "Stock Count {$stockCount->count_number} has a variance of {$variance} for {$itemName}.",
                [
                    'stock_count_id' => $stockCount->id,
                    'item_id' => $countItem->item_id,
                    'system_quantity' => $countItem->system_quantity,
                    'counted_quantity' => $countItem->counted_quantity,
                    'variance' => $variance,
                ],
            );
        }

        return $this->success($stockCount, 'Stock count completed');
    }
    public function approve(StockCount $stockCount) { return $this->success($this->stockCounts->approve($stockCount), 'Stock count approved'); }
    public function cancel(StockCount $stockCount) { $stockCount->update(['status' => 'cancelled']); return $this->success($stockCount, 'Stock count cancelled'); }
}
