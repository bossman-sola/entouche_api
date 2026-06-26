<?php

namespace App\Services;

use App\Models\Adjustment;
use App\Models\AdjustmentItem;
use App\Models\Setting;
use App\Models\StockBalance;
use App\Models\StockCount;
use App\Models\StockCountItem;

class StockCountService extends BaseService
{
    public function list(array $filters = [])
    {
        return StockCount::with(['items.item', 'warehouse', 'location'])
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($filters['warehouse_id'] ?? null, fn ($q, $v) => $q->where('warehouse_id', $v))
            ->latest()
            ->paginate($filters['per_page'] ?? 15);
    }

    public function approve(StockCount $stockCount): StockCount
    {
        if (! $stockCount->isCompleted()) {
            throw new \RuntimeException('Only completed stock counts can be approved.');
        }

        return $this->transaction(function () use ($stockCount): StockCount {
            $stock = app(StockMovementService::class);

            foreach ($stockCount->items as $countItem) {
                $variance = (float) $countItem->counted_quantity - (float) $countItem->system_quantity;

                if ($variance == 0.0) {
                    continue;
                }

                $adjustment = Adjustment::create([
                    'adjustment_number' => $this->generateNumber('adjustments', 'adjustment_number', Setting::get('numbering.adjustment_prefix', 'ADJ'), 6),
                    'warehouse_id' => $stockCount->warehouse_id,
                    'warehouse_location_id' => $countItem->warehouse_location_id ?? $stockCount->warehouse_location_id,
                    'adjustment_type' => $variance > 0 ? 'increase' : 'decrease',
                    'reason' => 'Stock count variance - ' . $stockCount->count_number,
                    'adjusted_by' => auth()->id(),
                    'approved_by' => auth()->id(),
                    'created_by' => auth()->id(),
                    'adjustment_date' => now()->toDateString(),
                    'approved_at' => now(),
                    'status' => 'approved',
                ]);

                AdjustmentItem::create([
                    'adjustment_id' => $adjustment->id,
                    'item_id' => $countItem->item_id,
                    'warehouse_location_id' => $countItem->warehouse_location_id ?? $stockCount->warehouse_location_id,
                    'quantity_before' => $countItem->system_quantity,
                    'adjustment_quantity' => abs($variance),
                    'quantity_after' => $countItem->counted_quantity,
                    'unit_cost' => 0,
                ]);

                $stock->move([
                    'item_id' => $countItem->item_id,
                    'warehouse_id' => $stockCount->warehouse_id,
                    'warehouse_location_id' => $countItem->warehouse_location_id ?? $stockCount->warehouse_location_id,
                    'transaction_type' => $variance > 0 ? 'adjustment_in' : 'adjustment_out',
                    'direction' => $variance > 0 ? 'in' : 'out',
                    'quantity' => abs($variance),
                    'reference_type' => StockCount::class,
                    'reference_id' => $stockCount->id,
                    'remarks' => 'Auto-adjustment from stock count ' . $stockCount->count_number,
                ]);

                $countItem->update(['adjustment_created' => true]);
            }

            $stockCount->update([
                'status' => 'approved',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);

            return $stockCount->fresh(['items.item', 'warehouse', 'location']);
        });
    }

    public function populateSystemQuantities(StockCount $stockCount): void
    {
        foreach ($stockCount->items as $item) {
            $item->update([
                'system_quantity' => StockBalance::where([
                    'item_id' => $item->item_id,
                    'warehouse_id' => $stockCount->warehouse_id,
                    'warehouse_location_id' => $item->warehouse_location_id ?? $stockCount->warehouse_location_id,
                ])->value('quantity_on_hand') ?? 0,
            ]);
        }
    }
}
