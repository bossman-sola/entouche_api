<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Receipt;
use App\Models\ReceiptItem;
use App\Models\Setting;
use App\Services\BaseService;
use App\Services\NotificationService;
use App\Services\StockMovementService;
use Illuminate\Http\Request;

class ReceiptController extends BaseApiController
{
    public function __construct(
        private readonly StockMovementService $stock,
        private readonly NotificationService $notifications,
    ) {
    }

    public function index(Request $request)
    {
        return $this->paginated(Receipt::with(['items.item'])->latest()->paginate($request->integer('per_page', 15)));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'receiving_location_id' => ['nullable', 'exists:warehouse_locations,id'],
            'receipt_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.item_id' => ['required', 'exists:items,id'],
            'items.*.warehouse_location_id' => ['nullable', 'exists:warehouse_locations,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.001'],
            'items.*.unit_cost' => ['sometimes', 'numeric', 'min:0'],
        ]);
    
        $helper = new class extends BaseService {};
        $items = $data['items'];
        unset($data['items']);
        $receipt = Receipt::create($data + [
            'receipt_number' => $helper->generateNumber('receipts', 'receipt_number', Setting::get('numbering.receipt_prefix', 'RCPT'), 6),
            'receipt_date' => $data['receipt_date'] ?? now()->toDateString(),
            'created_by' => auth()->id(),
        ]);
    
        foreach ($items as $row) {
            ReceiptItem::create($row + [
                'receipt_id' => $receipt->id,
                'warehouse_location_id' => $row['warehouse_location_id'] ?? $receipt->receiving_location_id,
                'total_cost' => ($row['quantity'] * ($row['unit_cost'] ?? 0)),
            ]);
        }
    
        return $this->created($receipt->load('items'), 'Receipt created');
    }

    public function show(Receipt $receipt)
    {
        return $this->success($receipt->load('items.item'));
    }

    public function update(Request $request, Receipt $receipt)
    {
        if ($receipt->status !== 'draft') {
            return $this->error('Only draft receipts can be updated.', null, 422);
        }

        $data = $request->validate([
            'supplier_id'           => ['nullable', 'exists:suppliers,id'],
            'warehouse_id'          => ['nullable', 'exists:warehouses,id'],
            'receiving_location_id' => ['nullable', 'exists:warehouse_locations,id'],
            'receipt_date'          => ['nullable', 'date'],
            'notes'                 => ['nullable', 'string'],
            'items'                 => ['nullable', 'array', 'min:1'],
            'items.*.item_id'       => ['required', 'exists:items,id'],
            'items.*.warehouse_location_id' => ['nullable', 'exists:warehouse_locations,id'],
            'items.*.quantity'      => ['required', 'numeric', 'min:0.001'],
            'items.*.unit_cost'     => ['sometimes', 'numeric', 'min:0'],
        ]);

        
        $receipt->update(collect($data)->except('items')->toArray());
        if (!empty($data['items'])) {
            
            $receipt->items()->delete();

            foreach ($data['items'] as $row) {
                ReceiptItem::create([
                    'receipt_id'            => $receipt->id,
                    'item_id'               => $row['item_id'],
                    'warehouse_location_id' => $row['warehouse_location_id'] ?? $receipt->receiving_location_id,
                    'quantity'              => $row['quantity'],
                    'unit_cost'             => $row['unit_cost'] ?? 0,
                    'total_cost'            => $row['quantity'] * ($row['unit_cost'] ?? 0),
                ]);
            }
        }

        return $this->success($receipt->fresh('items.item'), 'Receipt updated');
    }

    public function destroy(Receipt $receipt)
    {
        if ($receipt->status !== 'draft') {
            return $this->error('Only draft receipts can be deleted.', null, 422);
        }
        $receipt->delete();
        return $this->success(null, 'Receipt deleted');
    }

    public function receive(Receipt $receipt)
    {
        if ($receipt->status !== 'draft') {
            return $this->error('Only draft receipts can be received.', null, 422);
        }
        foreach ($receipt->items as $item) {
            $this->stock->move([
                'item_id' => $item->item_id,
                'warehouse_id' => $receipt->warehouse_id,
                'warehouse_location_id' => $item->warehouse_location_id ?? $receipt->receiving_location_id,
                'transaction_type' => 'receipt',
                'direction' => 'in',
                'quantity' => $item->quantity,
                'unit_cost' => $item->unit_cost,
                'total_value' => $item->total_cost,
                'reference_type' => Receipt::class,
                'reference_id' => $receipt->id,
            ]);
        }
        $receipt->update(['status' => 'received', 'received_by' => auth()->id(), 'received_at' => now()]);

        $this->notifications->notifyAdmins(
            'receipt_completed',
            'Receipt Completed',
            "Receipt {$receipt->receipt_number} has been completed and added to inventory.",
            ['receipt_id' => $receipt->id],
        );

        return $this->success($receipt->fresh('items'), 'Receipt received');
    }

    public function approve(Receipt $receipt)
    {
        return $this->receive($receipt);
    }

    public function cancel(Receipt $receipt)
    {
        if (! in_array($receipt->status, ['draft', 'pending_approval'], true)) {
            return $this->error('Receipt cannot be cancelled.', null, 422);
        }
        $receipt->update(['status' => 'cancelled']);
        return $this->success($receipt->fresh(), 'Receipt cancelled');
    }
}
