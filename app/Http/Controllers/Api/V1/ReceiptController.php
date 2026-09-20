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
    ) {}

    /**
     * List receipts.
     */
    public function index(Request $request)
    {
        return $this->paginated(
            Receipt::with(
                $this->receiptRelations()
            )
                ->latest()
                ->paginate(
                    $request->integer(
                        'per_page',
                        15
                    )
                )
        );
    }

    /**
     * Create a new receipt.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'supplier_id' => [
                'nullable',
                'exists:suppliers,id',
            ],

            'warehouse_id' => [
                'required',
                'exists:warehouses,id',
            ],

            'receiving_location_id' => [
                'nullable',
                'exists:warehouse_locations,id',
            ],

            'receipt_date' => [
                'nullable',
                'date',
            ],

            'notes' => [
                'nullable',
                'string',
            ],

            'items' => [
                'required',
                'array',
                'min:1',
            ],

            'items.*.item_id' => [
                'required',
                'exists:items,id',
            ],

            'items.*.warehouse_location_id' => [
                'nullable',
                'exists:warehouse_locations,id',
            ],

            'items.*.quantity' => [
                'required',
                'numeric',
                'min:0.001',
            ],

            'items.*.unit_cost' => [
                'sometimes',
                'numeric',
                'min:0',
            ],
        ]);

        $helper = new class extends BaseService {};

        $items = $data['items'];

        unset($data['items']);

        $receipt = Receipt::create(
            $data + [
                'receipt_number' =>
                    $helper->generateNumber(
                        'receipts',
                        'receipt_number',
                        (string) Setting::get(
                            'numbering.receipt_prefix',
                            'RCPT'
                        ),
                        6
                    ),

                'receipt_date' =>
                    $data['receipt_date']
                    ?? now()->toDateString(),

                'created_by' =>
                    auth()->id(),
            ]
        );

        foreach ($items as $row) {
            ReceiptItem::create([
                'receipt_id' =>
                    $receipt->id,

                'item_id' =>
                    $row['item_id'],

                'warehouse_location_id' =>
                    $row['warehouse_location_id']
                    ?? $receipt->receiving_location_id,

                'quantity' =>
                    $row['quantity'],

                'unit_cost' =>
                    $row['unit_cost']
                    ?? 0,

                'total_cost' =>
                    $row['quantity']
                    * ($row['unit_cost'] ?? 0),
            ]);
        }

        return $this->created(
            $receipt->load(
                $this->receiptRelations()
            ),
            'Receipt created'
        );
    }

    /**
     * Show a receipt.
     */
    public function show(Receipt $receipt)
    {
        return $this->success(
            $receipt->load(
                $this->receiptRelations()
            )
        );
    }

    /**
     * Update a draft receipt.
     */
    public function update(
        Request $request,
        Receipt $receipt
    ) {
        if ($receipt->status !== 'draft') {
            return $this->error(
                'Only draft receipts can be updated.',
                null,
                422
            );
        }

        $data = $request->validate([
            'supplier_id' => [
                'nullable',
                'exists:suppliers,id',
            ],

            'warehouse_id' => [
                'nullable',
                'exists:warehouses,id',
            ],

            'receiving_location_id' => [
                'nullable',
                'exists:warehouse_locations,id',
            ],

            'receipt_date' => [
                'nullable',
                'date',
            ],

            'notes' => [
                'nullable',
                'string',
            ],

            'items' => [
                'nullable',
                'array',
                'min:1',
            ],

            'items.*.item_id' => [
                'required',
                'exists:items,id',
            ],

            'items.*.warehouse_location_id' => [
                'nullable',
                'exists:warehouse_locations,id',
            ],

            'items.*.quantity' => [
                'required',
                'numeric',
                'min:0.001',
            ],

            'items.*.unit_cost' => [
                'sometimes',
                'numeric',
                'min:0',
            ],
        ]);

        $receipt->update(
            collect($data)
                ->except('items')
                ->toArray()
        );

        if (! empty($data['items'])) {
            $receipt->items()->delete();

            foreach ($data['items'] as $row) {
                ReceiptItem::create([
                    'receipt_id' =>
                        $receipt->id,

                    'item_id' =>
                        $row['item_id'],

                    'warehouse_location_id' =>
                        $row['warehouse_location_id']
                        ?? $receipt->receiving_location_id,

                    'quantity' =>
                        $row['quantity'],

                    'unit_cost' =>
                        $row['unit_cost']
                        ?? 0,

                    'total_cost' =>
                        $row['quantity']
                        * ($row['unit_cost'] ?? 0),
                ]);
            }
        }

        return $this->success(
            $receipt
                ->fresh()
                ->load(
                    $this->receiptRelations()
                ),
            'Receipt updated'
        );
    }

    /**
     * Delete a draft receipt.
     */
    public function destroy(Receipt $receipt)
    {
        if ($receipt->status !== 'draft') {
            return $this->error(
                'Only draft receipts can be deleted.',
                null,
                422
            );
        }

        $receipt->delete();

        return $this->success(
            null,
            'Receipt deleted'
        );
    }

    /**
     * Receive an approved receipt.
     *
     * This adds the received items to inventory.
     * The receipt creator is notified when completed.
     */
    public function receive(Receipt $receipt)
    {
        if ($receipt->status !== 'approved') {
            return $this->error(
                'Only approved receipts can be received.',
                null,
                422
            );
        }

        $receipt->loadMissing([
            'items',
            'creator',
        ]);

        foreach ($receipt->items as $item) {
            $this->stock->move([
                'item_id' =>
                    $item->item_id,

                'warehouse_id' =>
                    $receipt->warehouse_id,

                'warehouse_location_id' =>
                    $item->warehouse_location_id
                    ?? $receipt->receiving_location_id,

                'transaction_type' =>
                    'receipt',

                'direction' =>
                    'in',

                'quantity' =>
                    $item->quantity,

                'unit_cost' =>
                    $item->unit_cost,

                'total_value' =>
                    $item->total_cost,

                'reference_type' =>
                    Receipt::class,

                'reference_id' =>
                    $receipt->id,
            ]);
        }

        $receipt->update([
            'status' =>
                'received',

            'received_by' =>
                auth()->id(),

            'received_at' =>
                now(),
        ]);

        /*
         * Notify the person who originally
         * created the receipt.
         *
         * NotificationService handles:
         * - In-app notification
         * - Email
         */
        $recipient =
            $receipt->creator;

        if ($recipient) {
            $this->notifications->notifyUser(
                $recipient,
                'receipt_completed',
                'Receipt Completed',
                "Receipt {$receipt->receipt_number} has been completed and added to inventory.",
                [
                    'receipt_id' =>
                        $receipt->id,
                ]
            );
        }

        return $this->success(
            $receipt
                ->fresh()
                ->load(
                    $this->receiptRelations()
                ),
            'Receipt received'
        );
    }

    /**
     * Submit a receipt for approval.
     *
     * System Administrators and Warehouse Managers
     * have receipts.approve permission, so both
     * roles are notified.
     */
    public function submit(Receipt $receipt)
    {
        if ($receipt->status !== 'draft') {
            return $this->error(
                'Only draft receipts can be submitted for approval.',
                null,
                422
            );
        }

        if ($receipt->items()->count() === 0) {
            return $this->error(
                'Receipt must contain at least one item before submission.',
                null,
                422
            );
        }

        $receipt->update([
            'status' =>
                'submitted',
        ]);

        /*
         * Notify everyone who can approve receipts.
         *
         * NotificationService removes duplicate
         * users if somebody has multiple roles.
         */
        $this->notifications->notifyRoles(
            [
                'system_administrator',
                'warehouse_manager',
            ],
            'new_receipt_awaiting_approval',
            'New Receipt Awaiting Approval',
            "Receipt {$receipt->receipt_number} has been submitted and is awaiting approval.",
            [
                'receipt_id' =>
                    $receipt->id,
            ]
        );

        return $this->success(
            $receipt
                ->fresh()
                ->load(
                    $this->receiptRelations()
                ),
            'Receipt submitted for approval'
        );
    }

    /**
     * Approve a submitted receipt.
     *
     * The person who created the receipt
     * is notified after approval.
     */
    public function approve(Receipt $receipt)
    {
        if ($receipt->status !== 'submitted') {
            return $this->error(
                'Only submitted receipts can be approved.',
                null,
                422
            );
        }

        $receipt->update([
            'status' =>
                'approved',

            'approved_at' =>
                now(),
        ]);

        $receipt->loadMissing(
            'creator'
        );

        $recipient =
            $receipt->creator;

        if ($recipient) {
            $this->notifications->notifyUser(
                $recipient,
                'receipt_approved',
                'Receipt Approved',
                "Receipt {$receipt->receipt_number} has been approved and is ready to be received.",
                [
                    'receipt_id' =>
                        $receipt->id,
                ]
            );
        }

        return $this->success(
            $receipt
                ->fresh()
                ->load(
                    $this->receiptRelations()
                ),
            'Receipt approved'
        );
    }

    /**
     * Cancel a receipt.
     *
     * Draft cancellations do not generate
     * notifications because the receipt has
     * not entered the approval workflow.
     */
    public function cancel(Receipt $receipt)
    {
        $previousStatus =
            $receipt->status;

        if (! in_array(
            $previousStatus,
            [
                'draft',
                'submitted',
                'approved',
            ],
            true
        )) {
            return $this->error(
                'Receipt cannot be cancelled.',
                null,
                422
            );
        }

        $receipt->update([
            'status' =>
                'cancelled',
        ]);

        /*
         * Do not notify anyone when a
         * draft receipt is cancelled.
         */
        if ($previousStatus !== 'draft') {
            $receipt->loadMissing(
                'creator'
            );

            $recipient =
                $receipt->creator;

            if ($recipient) {
                $this->notifications->notifyUser(
                    $recipient,
                    'receipt_cancelled',
                    'Receipt Cancelled',
                    "Receipt {$receipt->receipt_number} has been cancelled.",
                    [
                        'receipt_id' =>
                            $receipt->id,

                        'previous_status' =>
                            $previousStatus,
                    ]
                );
            }
        }

        return $this->success(
            $receipt
                ->fresh()
                ->load(
                    $this->receiptRelations()
                ),
            'Receipt cancelled'
        );
    }

    /**
     * Common relationships returned with receipts.
     */
    private function receiptRelations(): array
    {
        return [
            'supplier',
            'warehouse',
            'receivingLocation',

            'receiver.roles',
            'creator.roles',

            'items.location',
            'items.item.category',
            'items.item.unit',
        ];
    }
}
