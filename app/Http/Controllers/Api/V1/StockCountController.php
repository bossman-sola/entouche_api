<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\StockCountValidationException;
use App\Exports\StockCountsExport;
use App\Http\Controllers\Api\BaseApiController;
use App\Models\Setting;
use App\Models\StockCount;
use App\Models\StockCountItem;
use App\Services\BaseService;
use App\Services\NotificationService;
use App\Services\StockCountService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Activitylog\Models\Activity;

class StockCountController extends BaseApiController
{
    public function __construct(
        private readonly StockCountService $stockCounts,
        private readonly NotificationService $notifications,
    ) {}

    public function index(Request $request)
    {
        return $this->paginated($this->stockCounts->list($request->all()));
    }

    public function show(StockCount $stockCount)
    {
        $stockCount->load(['items.item', 'warehouse', 'location', 'assignedCounter', 'submittedBy', 'approvedBy', 'rejectedBy']);

        return $this->success(array_merge($stockCount->toArray(), [
            'progress' => $this->stockCounts->countingProgress($stockCount),
            'activity' => $this->activity($stockCount),
        ]));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'warehouse_location_id' => ['nullable', 'exists:warehouse_locations,id'],
            'count_type' => ['nullable', 'in:Cycle Count,Spot Check,Full Physical,Recount'],
            'priority' => ['nullable', 'in:low,medium,high'],
            'count_date' => ['nullable', 'date'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'notes' => ['nullable', 'string', 'max:500'],
            'items' => ['sometimes', 'array'],
            'items.*.item_id' => ['required', 'exists:items,id'],
            'items.*.warehouse_location_id' => ['nullable', 'exists:warehouse_locations,id'],
        ]);

        $items = $data['items'] ?? [];
        unset($data['items']);
        $assignedTo = $data['assigned_to'] ?? auth()->id();
        unset($data['assigned_to']);

        $number = (new class extends BaseService {})->generateNumber('stock_counts', 'count_number', Setting::get('numbering.stock_count_prefix', 'SC'), 6);

        $count = StockCount::create($data + [
            'count_number' => $number,
            'counted_by' => $assignedTo,
            'created_by' => auth()->id(),
            'status' => 'draft',
            'count_date' => $data['count_date'] ?? now()->toDateString(),
        ]);

        if (! empty($items)) {
            $this->stockCounts->addItems($count, $items);
        }

        activity()->causedBy(auth()->user())->performedOn($count)->withProperties([
            'title' => 'Stock Count Created',
            'description' => 'Created by '.(auth()->user()->name ?? 'a user'),
        ])->log('stock_count.created');

        $count = $count->fresh('items');
        $response = $count->toArray();

        $response['items'] = $count->items->map(function ($item) {
            return [
                'id' => $item->item_id,
                'stock_count_id' => $item->stock_count_id,
                'warehouse_location_id' => $item->warehouse_location_id,
                'system_quantity' => $item->system_quantity,
                'counted_quantity' => $item->counted_quantity,
                'counted_at' => $item->counted_at,
                'counted_by' => $item->counted_by,
                'variance_quantity' => $item->variance_quantity,
                'adjustment_created' => $item->adjustment_created,
                'remarks' => $item->remarks,
                'created_at' => $item->created_at,
                'updated_at' => $item->updated_at,
            ];
        })->values()->all();

        return $this->created($count->fresh('items'), 'Stock count created');
    }

    public function update(Request $request, StockCount $stockCount)
    {
        if (! $stockCount->isEditable()) {
            return $this->error('Only draft stock counts can be updated.', null, 422);
        }

        $data = $request->validate([
            'warehouse_id' => ['sometimes', 'exists:warehouses,id'],
            'warehouse_location_id' => ['nullable', 'exists:warehouse_locations,id'],
            'count_type' => ['nullable', 'in:Cycle Count,Spot Check,Full Physical,Recount'],
            'priority' => ['nullable', 'in:low,medium,high'],
            'count_date' => ['nullable', 'date'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        if (isset($data['assigned_to'])) {
            $data['counted_by'] = $data['assigned_to'];
            unset($data['assigned_to']);
        }

        $stockCount->update($data);

        return $this->success($stockCount->fresh('items'), 'Stock count updated');
    }

    public function destroy(StockCount $stockCount)
    {
        if (! $stockCount->isEditable()) {
            return $this->error('Only draft stock counts can be deleted.', null, 422);
        }
        $stockCount->delete();

        return $this->success(null, 'Stock count deleted');
    }

    public function cancel(StockCount $stockCount)
    {
        if (in_array($stockCount->status, ['completed', 'cancelled'], true)) {
            return $this->error(
                'This stock count cannot be cancelled.',
                null,
                422
            );
        }

        $previousStatus = $stockCount->status;

        $stockCount->update([
            'status' => 'cancelled',
        ]);

        activity()
            ->causedBy(auth()->user())
            ->performedOn($stockCount)
            ->withProperties([
                'title' => 'Stock Count Cancelled',
                'description' => 'Cancelled by '.(auth()->user()->name ?? 'a user'),
                'previous_status' => $previousStatus,
            ])
            ->log('stock_count.cancelled');

        // Drafts have not entered the workflow yet,
        // so cancelling one should not notify other users.
        if ($previousStatus !== 'draft') {
            $this->notifications->notifyAdmins(
                'stock_count_cancelled',
                'Stock Count Cancelled',
                "Stock Count {$stockCount->count_number} has been cancelled.",
                [
                    'stock_count_id' => $stockCount->id,
                ]
            );
        }

        return $this->success(
            $stockCount->fresh(),
            'Stock count cancelled'
        );
    }

    public function addItems(Request $request, StockCount $stockCount)
    {
        $data = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.item_id' => ['required', 'exists:items,id'],
            'items.*.warehouse_location_id' => ['nullable', 'exists:warehouse_locations,id'],
        ]);

        try {
            $stockCount = $this->stockCounts->addItems($stockCount, $data['items']);
        } catch (\RuntimeException $e) {
            return $this->error($e->getMessage(), null, 422);
        }

        return $this->success($stockCount, 'Items added to stock count');
    }

    public function updateItem(Request $request, StockCount $stockCount, StockCountItem $item)
    {
        if ($item->stock_count_id !== $stockCount->id) {
            return $this->error('Item does not belong to this stock count.', null, 404);
        }

        $data = $request->validate([
            'counted_quantity' => ['required', 'numeric'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $item = $this->stockCounts->updateItemCount($stockCount, $item, (float) $data['counted_quantity'], $data['reason'] ?? null);
        } catch (\RuntimeException $e) {
            return $this->error($e->getMessage(), null, 422);
        }

        return $this->success($item, 'Counted quantity saved');
    }

    public function removeItem(StockCount $stockCount, StockCountItem $item)
    {
        if ($item->stock_count_id !== $stockCount->id) {
            return $this->error('Item does not belong to this stock count.', null, 404);
        }

        try {
            $this->stockCounts->removeItem($stockCount, $item);
        } catch (\RuntimeException $e) {
            return $this->error($e->getMessage(), null, 422);
        }

        return $this->success(null, 'Item removed from stock count');
    }

    public function searchItems(Request $request)
    {
        return $this->success($this->stockCounts->searchItems($request->string('search', '')->toString()));
    }

    public function progress(StockCount $stockCount)
    {
        return $this->success($this->stockCounts->countingProgress($stockCount));
    }

    public function submit(StockCount $stockCount)
    {
        try {
            $stockCount = $this->stockCounts->submit($stockCount, $this->notifications);
        } catch (StockCountValidationException $e) {
            return $this->error($e->getMessage(), $e->progress, 422);
        } catch (\RuntimeException $e) {
            return $this->error($e->getMessage(), null, 422);
        }

        return $this->success($stockCount, 'Stock count submitted for review');
    }

    public function approve(StockCount $stockCount)
    {
        try {
            $stockCount = $this->stockCounts->approveAndComplete($stockCount, $this->notifications);
        } catch (\RuntimeException $e) {
            return $this->error($e->getMessage(), null, 422);
        }

        return $this->success($stockCount, 'Stock count approved and completed');
    }

    public function reject(Request $request, StockCount $stockCount)
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:500']]);

        try {
            $stockCount = $this->stockCounts->reject($stockCount, $data['reason'], $this->notifications);
        } catch (\RuntimeException $e) {
            return $this->error($e->getMessage(), null, 422);
        }

        return $this->success($stockCount, 'Stock count rejected');
    }

    public function requestRecount(Request $request, StockCount $stockCount)
    {
        $data = $request->validate(['reason' => ['nullable', 'string', 'max:500']]);

        try {
            $stockCount = $this->stockCounts->requestRecount($stockCount, $data['reason'] ?? null, $this->notifications);
        } catch (\RuntimeException $e) {
            return $this->error($e->getMessage(), null, 422);
        }

        return $this->success($stockCount, 'Recount requested');
    }

    public function varianceBreakdown(StockCount $stockCount)
    {
        return $this->success([
            'count_number' => $stockCount->count_number,
            'items' => $this->stockCounts->varianceBreakdown($stockCount),
        ]);
    }

    public function overview()
    {
        return $this->success($this->stockCounts->overview());
    }

    public function calendar(Request $request)
    {
        $data = $request->validate(['month' => ['required', 'integer', 'min:1', 'max:12'], 'year' => ['required', 'integer', 'min:2000', 'max:2100']]);

        return $this->success($this->stockCounts->calendar((int) $data['month'], (int) $data['year']));
    }

    public function lookups()
    {
        return $this->success($this->stockCounts->lookups());
    }

    public function export(Request $request)
    {
        $data = $request->validate([
            'format' => ['required', 'in:xlsx,csv'],
            'ids' => ['sometimes', 'array'],
        ]);

        $filename = 'stock-counts-'.now()->format('Y-m-d-His').'.'.$data['format'];
        $writerType = $data['format'] === 'csv' ? \Maatwebsite\Excel\Excel::CSV : \Maatwebsite\Excel\Excel::XLSX;

        return Excel::download(new StockCountsExport($request->all()), $filename, $writerType);
    }

    private function activity(StockCount $stockCount): array
    {
        return Activity::where('subject_type', StockCount::class)
            ->where('subject_id', $stockCount->id)
            ->oldest()
            ->get()
            ->map(fn ($log) => [
                'title' => $log->properties['title'] ?? $log->description,
                'detail' => $log->properties['description'] ?? '',
                'time' => $log->created_at->format('M j, Y \a\t g:i A'),
                'done' => true,
            ])
            ->all();
    }
}
