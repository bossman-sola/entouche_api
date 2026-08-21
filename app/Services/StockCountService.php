<?php

namespace App\Services;

use App\Exceptions\StockCountValidationException;
use App\Models\Adjustment;
use App\Models\AdjustmentItem;
use App\Models\Item;
use App\Models\Location;
use App\Models\Setting;
use App\Models\StockBalance;
use App\Models\StockCount;
use App\Models\StockCountItem;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Models\Activity;

class StockCountService extends BaseService
{
    public function list(array $filters = [])
    {
        return StockCount::with(['items.item', 'warehouse', 'location', 'assignedCounter', 'submittedBy'])
            ->when($filters['search'] ?? null, fn ($q, $v) => $q->where('count_number', 'like', "%{$v}%"))
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($filters['warehouse_id'] ?? null, fn ($q, $v) => $q->where('warehouse_id', $v))
            ->when($filters['warehouse_location_id'] ?? null, fn ($q, $v) => $q->where('warehouse_location_id', $v))
            ->when($filters['count_type'] ?? null, fn ($q, $v) => $q->where('count_type', $v))
            ->when($filters['date_from'] ?? null, fn ($q, $v) => $q->whereDate('count_date', '>=', $v))
            ->when($filters['date_to'] ?? null, fn ($q, $v) => $q->whereDate('count_date', '<=', $v))
            ->when(($filters['variance'] ?? null) === 'variance_only', fn ($q) => $q->whereHas('items', fn ($i) => $i->where('variance_quantity', '!=', 0)))
            ->when(($filters['variance'] ?? null) === 'matches_only', fn ($q) => $q->whereDoesntHave('items', fn ($i) => $i->where('variance_quantity', '!=', 0)))
            ->latest()
            ->paginate($filters['per_page'] ?? 15);
    }

    public function lookups(): array
    {
        return [
            'warehouses' => Warehouse::where('status', 'active')->orderBy('name')->get(['id', 'name', 'code']),
            'locations' => Location::where('status', 'active')->orderBy('name')->get(['id', 'warehouse_id', 'name', 'code']),
            'users' => User::where('status', 'active')
                ->role(['inventory_officer', 'warehouse_manager', 'system_administrator'])
                ->orderBy('name')
                ->get(['id', 'name', 'email']),
        ];
    }

    public function overview(): array
    {
        $total = StockCount::count();
        $totalLastMonth = StockCount::where('created_at', '<', now()->startOfMonth())->count();

        $completed = StockCount::where('status', 'completed')->count();
        $completedLastMonth = StockCount::where('status', 'completed')->where('completed_at', '<', now()->startOfMonth())->count();

        $statusCounts = StockCount::select('status', DB::raw('count(*) as total'))->groupBy('status')->pluck('total', 'status');
        $statusColors = [
            'draft' => '#9CA3AF',
            'pending_review' => '#F59E0B',
            'completed' => '#10B981',
            'in_progress' => '#6366F1',
            'cancelled' => '#EF4444',
        ];
        $breakdown = $statusCounts->map(function ($count, $status) use ($total, $statusColors) {
            return [
                'label' => ucwords(str_replace('_', ' ', $status)),
                'value' => $count,
                'pct' => $total > 0 ? round($count / $total * 100) : 0,
                'color' => $statusColors[$status] ?? '#9CA3AF',
            ];
        })->values();

        $countTypes = StockCount::select('count_type', DB::raw('count(*) as total'))
            ->whereNotNull('count_type')
            ->groupBy('count_type')
            ->pluck('total', 'count_type')
            ->map(fn ($count, $type) => ['label' => $type, 'value' => $count])
            ->values();

        return [
            'total_stock_counts' => $total,
            'total_delta' => $total - $totalLastMonth,
            'pending_review' => StockCount::where('status', 'pending_review')->count(),
            'variances_found' => StockCount::whereHas('items', fn ($q) => $q->where('variance_quantity', '!=', 0))->count(),
            'completed_counts' => $completed,
            'completed_delta' => $completed - $completedLastMonth,
            'breakdown' => $breakdown,
            'count_types' => $countTypes,
            'recent_activity' => $this->recentActivity(),
        ];
    }

    private function recentActivity(int $limit = 8): array
    {
        return Activity::where('subject_type', StockCount::class)
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn ($log) => [
                'id' => $log->id,
                'type' => $log->description,
                'title' => $log->properties['title'] ?? $log->description,
                'description' => $log->properties['description'] ?? '',
                'time' => $log->created_at->diffForHumans(),
            ])
            ->all();
    }

    public function calendar(int $month, int $year): array
    {
        return StockCount::with(['warehouse', 'location'])
            ->whereYear('count_date', $year)
            ->whereMonth('count_date', $month)
            ->get()
            ->map(fn (StockCount $sc) => [
                'id' => $sc->id,
                'count_number' => $sc->count_number,
                'type' => $sc->count_type,
                'date' => optional($sc->count_date)->toDateString(),
                'time' => $sc->start_time,
                'location' => $sc->location->name ?? $sc->warehouse->name ?? null,
                'status' => $sc->status,
            ])
            ->all();
    }

    public function addItems(StockCount $stockCount, array $items): StockCount
    {
        if (! $stockCount->isEditable()) {
            throw new \RuntimeException('Items can only be added while the stock count is a draft.');
        }

        foreach ($items as $row) {
            $systemQty = StockBalance::where([
                'item_id' => $row['item_id'],
                'warehouse_id' => $stockCount->warehouse_id,
                'warehouse_location_id' => $row['warehouse_location_id'] ?? $stockCount->warehouse_location_id,
            ])->value('quantity_on_hand') ?? 0;

            StockCountItem::updateOrCreate(
                [
                    'stock_count_id' => $stockCount->id,
                    'item_id' => $row['item_id'],
                    'warehouse_location_id' => $row['warehouse_location_id'] ?? $stockCount->warehouse_location_id,
                ],
                ['system_quantity' => $systemQty],
            );
        }

        activity()->causedBy(auth()->user())->performedOn($stockCount)->withProperties([
            'title' => 'Items Added',
            'description' => count($items).' item(s) added to the count.',
        ])->log('stock_count.items_added');

        return $stockCount->fresh('items.item');
    }

    public function removeItem(StockCount $stockCount, StockCountItem $item): void
    {
        if (! $stockCount->isEditable()) {
            throw new \RuntimeException('Items can only be removed while the stock count is a draft.');
        }

        $item->delete();
    }

    public function updateItemCount(StockCount $stockCount, StockCountItem $item, float $countedQuantity, ?string $reason = null): StockCountItem
    {
        if (! $stockCount->isEditable()) {
            throw new \RuntimeException('Counted quantities can only be edited while the stock count is a draft.');
        }

        $item->update([
            'counted_quantity' => $countedQuantity,
            'counted_at' => now(),
            'counted_by' => auth()->id(),
            'remarks' => $reason ?? $item->remarks,
        ]);

        return $item->fresh();
    }

    public function countingProgress(StockCount $stockCount): array
    {
        $items = $stockCount->items;
        $total = $items->count();
        $counted = $items->whereNotNull('counted_at')->count();

        return [
            'total_items' => $total,
            'counted_items' => $counted,
            'remaining_items' => $total - $counted,
        ];
    }

    public function submit(StockCount $stockCount, NotificationService $notifications): StockCount
    {
        if (! $stockCount->isEditable()) {
            throw new \RuntimeException('Only a draft stock count can be submitted.');
        }

        $progress = $this->countingProgress($stockCount);

        if ($progress['total_items'] === 0) {
            throw new \RuntimeException('Add at least one item before submitting this stock count.');
        }

        if ($progress['remaining_items'] > 0) {
            throw new StockCountValidationException(
                "{$progress['remaining_items']} items have not been counted. Complete the remaining items before submitting.",
                $progress,
            );
        }

        $stockCount->update([
            'status' => 'pending_review',
            'submitted_by' => auth()->id(),
            'submitted_at' => now(),
        ]);

        activity()->causedBy(auth()->user())->performedOn($stockCount)->withProperties([
            'title' => 'Stock Count Submitted',
            'description' => 'Submitted by '.(auth()->user()->name ?? 'a user'),
        ])->log('stock_count.submitted');

        $notifications->notifyRole(
            'warehouse_manager',
            'stock_count_submitted',
            'Stock Count Pending Review',
            "Stock Count {$stockCount->count_number} has been submitted and is awaiting your approval.",
            ['stock_count_id' => $stockCount->id],
        );
        $notifications->notifyAdmins(
            'stock_count_submitted',
            'Stock Count Pending Review',
            "Stock Count {$stockCount->count_number} has been submitted and is awaiting approval.",
            ['stock_count_id' => $stockCount->id],
        );

        return $stockCount->fresh(['items.item', 'submittedBy']);
    }

    public function approveAndComplete(StockCount $stockCount, NotificationService $notifications): StockCount
    {
        if (! $stockCount->isPendingReview()) {
            throw new \RuntimeException('Only a stock count that is pending review can be approved.');
        }

        return $this->transaction(function () use ($stockCount, $notifications): StockCount {
            $stock = app(StockMovementService::class);

            foreach ($stockCount->items()->with('item')->get() as $countItem) {
                $variance = (float) $countItem->counted_quantity - (float) $countItem->system_quantity;

                if ($variance == 0.0) {
                    continue;
                }

                $adjustment = Adjustment::create([
                    'adjustment_number' => $this->generateNumber('adjustments', 'adjustment_number', Setting::get('numbering.adjustment_prefix', 'ADJ'), 6),
                    'warehouse_id' => $stockCount->warehouse_id,
                    'warehouse_location_id' => $countItem->warehouse_location_id ?? $stockCount->warehouse_location_id,
                    'adjustment_type' => $variance > 0 ? 'increase' : 'decrease',
                    'reason' => 'Stock count variance - '.$stockCount->count_number,
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
                    'remarks' => 'Auto-adjustment from stock count '.$stockCount->count_number,
                ]);

                $countItem->update(['adjustment_created' => true]);

                $itemName = $countItem->item->name ?? 'an item';
                $notifications->notifyAdmins(
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

            $stockCount->update([
                'status' => 'completed',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
                'completed_at' => now(),
            ]);

            activity()->causedBy(auth()->user())->performedOn($stockCount)->withProperties([
                'title' => 'Stock Count Approved & Completed',
                'description' => 'Approved by '.(auth()->user()->name ?? 'a user'),
            ])->log('stock_count.completed');

            $notifications->notifyAdmins(
                'stock_count_completed',
                'Stock Count Completed',
                "Stock Count {$stockCount->count_number} has been approved and completed.",
                ['stock_count_id' => $stockCount->id],
            );

            return $stockCount->fresh(['items.item', 'warehouse', 'location']);
        });
    }

    public function reject(StockCount $stockCount, string $reason, NotificationService $notifications): StockCount
    {
        if (! $stockCount->isPendingReview()) {
            throw new \RuntimeException('Only a stock count that is pending review can be rejected.');
        }

        $stockCount->update([
            'status' => 'draft',
            'rejection_reason' => $reason,
            'rejected_by' => auth()->id(),
            'rejected_at' => now(),
        ]);

        activity()->causedBy(auth()->user())->performedOn($stockCount)->withProperties([
            'title' => 'Stock Count Rejected',
            'description' => $reason,
        ])->log('stock_count.rejected');

        if ($stockCount->assignedCounter) {
            $notifications->notifyUser(
                $stockCount->assignedCounter,
                'stock_count_rejected',
                'Stock Count Rejected',
                "Stock Count {$stockCount->count_number} was rejected: {$reason}",
                ['stock_count_id' => $stockCount->id],
            );
        }

        return $stockCount->fresh(['items.item']);
    }

    public function requestRecount(StockCount $stockCount, ?string $reason, NotificationService $notifications): StockCount
    {
        if (! $stockCount->isPendingReview()) {
            throw new \RuntimeException('Only a stock count that is pending review can have a recount requested.');
        }

        $this->transaction(function () use ($stockCount, $reason): void {
            $stockCount->items()->update(['counted_quantity' => 0, 'counted_at' => null, 'counted_by' => null]);

            $stockCount->update([
                'status' => 'draft',
                'recount_requested_by' => auth()->id(),
                'recount_requested_at' => now(),
                'rejection_reason' => $reason,
            ]);
        });

        activity()->causedBy(auth()->user())->performedOn($stockCount)->withProperties([
            'title' => 'Recount Requested',
            'description' => $reason ?? 'A recount was requested for this stock count.',
        ])->log('stock_count.recount_requested');

        if ($stockCount->assignedCounter) {
            $notifications->notifyUser(
                $stockCount->assignedCounter,
                'stock_count_recount_requested',
                'Recount Requested',
                "A recount has been requested for Stock Count {$stockCount->count_number}.",
                ['stock_count_id' => $stockCount->id],
            );
        }

        return $stockCount->fresh(['items.item']);
    }

    public function varianceBreakdown(StockCount $stockCount): array
    {
        return $stockCount->items()->with('item')->get()
            ->filter(fn (StockCountItem $item) => (float) $item->variance_quantity != 0.0)
            ->map(function (StockCountItem $item) {
                $variance = (float) $item->variance_quantity;

                return [
                    'item_id' => $item->item_id,
                    'item_name' => $item->item->name ?? null,
                    'sku' => $item->item->sku ?? null,
                    'system_quantity' => (float) $item->system_quantity,
                    'counted_quantity' => (float) $item->counted_quantity,
                    'difference' => $variance,
                    'reason' => $item->remarks,
                    'adjustment_recommendation' => $variance > 0
                        ? 'Increase system quantity by '.abs($variance).' to match the physical count.'
                        : 'Decrease system quantity by '.abs($variance).' to match the physical count.',
                ];
            })
            ->values()
            ->all();
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

    public function searchItems(string $search = '', int $limit = 20)
    {
        return Item::active()
            ->when($search !== '', fn ($q) => $q->where(fn ($qq) => $qq
                ->where('name', 'like', "%{$search}%")
                ->orWhere('sku', 'like', "%{$search}%")
                ->orWhere('barcode', 'like', "%{$search}%")))
            ->orderBy('name')
            ->limit($limit)
            ->get(['id', 'name', 'sku', 'barcode']);
    }
}
