<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Item;
use App\Models\Setting;
use App\Models\Transfer;
use App\Models\TransferItem;
use App\Services\BaseService;
use App\Services\NotificationService;
use App\Services\StockMovementService;
use Illuminate\Http\Request;

class TransferController extends BaseApiController
{
    public function __construct(
        private readonly StockMovementService $stock,
        private readonly NotificationService $notifications,
    ) {}

    public function index(Request $request)
    {
        return $this->paginated(Transfer::with('items')->latest()->paginate($request->integer('per_page', 15)));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'from_warehouse_id' => ['required', 'exists:warehouses,id'],
            'from_location_id' => ['nullable', 'exists:warehouse_locations,id'],
            'to_warehouse_id' => ['required', 'exists:warehouses,id'],
            'to_location_id' => ['nullable', 'exists:warehouse_locations,id'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.item_id' => ['required', 'exists:items,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.001'],
        ]);

        $items = $data['items'];
        unset($data['items']);
        $number = (new class extends BaseService {})->generateNumber('transfers', 'transfer_number', Setting::get('numbering.transfer_prefix', 'TRF'), 6);
        $transfer = Transfer::create(
            $data +
            [
                'transfer_number' => $number,
                'transfer_date' => $data['transfer_date'] ?? now()->toDateString(),
                'requested_by' => auth()->id(),
                'created_by' => auth()->id(),
                'status' => 'draft',
            ]);

        foreach ($items as $row) {
            TransferItem::create($row + ['transfer_id' => $transfer->id]);
        }

        return $this->created($transfer->load('items'), 'Transfer created');
    }

    public function show(Transfer $transfer)
    {
        return $this->success(
            $transfer->load([
                'items.item.category',
                'items.item.unit',

                'fromWarehouse',
                'fromLocation',
                'toWarehouse',
                'toLocation',

                'requester.roles',
                'approver.roles',
                'completer.roles',
            ])
        );
    }

    public function update(Request $request, Transfer $transfer)
    {
        if ($transfer->status !== 'draft') {
            return $this->error('Only draft transfers can be updated.', null, 422);
        }

        $data = $request->validate([
            'from_warehouse_id' => ['nullable', 'exists:warehouses,id'],
            'from_location_id' => ['nullable', 'exists:warehouse_locations,id'],
            'to_warehouse_id' => ['nullable', 'exists:warehouses,id'],
            'to_location_id' => ['nullable', 'exists:warehouse_locations,id'],
            'notes' => ['nullable', 'string'],
            'items' => ['nullable', 'array', 'min:1'],
            'items.*.item_id' => ['required', 'exists:items,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.001'],
        ]);

        $transfer->update(collect($data)->except('items')->toArray());

        if (! empty($data['items'])) {
            foreach ($data['items'] as $row) {
                TransferItem::updateOrCreate(
                    [
                        'transfer_id' => $transfer->id,
                        'item_id' => $row['item_id'],
                    ],
                    [
                        'quantity' => $row['quantity'],
                    ]
                );
            }
        }

        return $this->success($transfer->fresh('items'), 'Transfer updated');
    }

    public function destroy(Transfer $transfer)
    {
        if ($transfer->status !== 'draft') {
            return $this->error('Only draft transfers can be deleted.', null, 422);
        }
        $transfer->delete();

        return $this->success(null, 'Transfer deleted');
    }

    public function submit(Transfer $transfer)
    {
        $transfer->update(['status' => 'pending_approval']);

        return $this->success($transfer, 'Transfer submitted');
    }

    public function reject(Request $request, Transfer $transfer)
    {
        $transfer->update(['status' => 'rejected', 'rejection_reason' => $request->reason]);

        return $this->success($transfer, 'Transfer rejected');
    }

    public function cancel(Transfer $transfer)
    {
        $transfer->update(['status' => 'cancelled']);

        return $this->success($transfer, 'Transfer cancelled');
    }

    public function approve(Transfer $transfer)
    {
        if (! in_array($transfer->status, ['draft', 'pending_approval'], true)) {
            return $this->error('Transfer cannot be approved.', null, 422);
        }
        $transfer->update(['status' => 'approved', 'approved_by' => auth()->id(), 'approved_at' => now()]);
        $this->notifications->notifyAdmins(
            'transfer_approved',
            'Transfer Approved',
            "Transfer {$transfer->transfer_number} has been approved and is ready for completion.",
            ['transfer_id' => $transfer->id],
        );

        return $this->success($transfer->fresh('items'), 'Transfer approved');
    }

    public function complete(Transfer $transfer)
    {
        if ($transfer->status !== 'approved') {
            return $this->error(
                'Only approved transfers can be completed.',
                null,
                422
            );
        }

        foreach ($transfer->items as $transferItem) {
            $item = Item::findOrFail($transferItem->item_id);

            $quantity = (float) $transferItem->quantity;
            $unitCost = (float) ($item->unit_cost ?? 0);
            $totalValue = $quantity * $unitCost;

            $this->stock->move([
                'item_id' => $transferItem->item_id,
                'warehouse_id' => $transfer->from_warehouse_id,
                'warehouse_location_id' => $transfer->from_location_id,
                'transaction_type' => 'transfer_out',
                'direction' => 'out',
                'quantity' => $quantity,
                'unit_cost' => $unitCost,
                'total_value' => $totalValue,
                'reference_type' => Transfer::class,
                'reference_id' => $transfer->id,
                'remarks' => $transfer->notes,
            ]);

            $this->stock->move([
                'item_id' => $transferItem->item_id,
                'warehouse_id' => $transfer->to_warehouse_id,
                'warehouse_location_id' => $transfer->to_location_id,
                'transaction_type' => 'transfer_in',
                'direction' => 'in',
                'quantity' => $quantity,
                'unit_cost' => $unitCost,
                'total_value' => $totalValue,
                'reference_type' => Transfer::class,
                'reference_id' => $transfer->id,
                'remarks' => $transfer->notes,
            ]);
        }

        $transfer->update([
            'status' => 'completed',
            'completed_by' => auth()->id(),
            'completed_at' => now(),
        ]);

        $this->notifications->notifyAdmins(
            'transfer_completed',
            'Transfer Completed',
            "Transfer {$transfer->transfer_number} has been completed successfully.",
            ['transfer_id' => $transfer->id],
        );

        return $this->success(
            $transfer,
            'Transfer completed'
        );
    }
}
