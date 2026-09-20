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
use Illuminate\Support\Facades\DB;

class TransferController extends BaseApiController
{
    public function __construct(
        private readonly StockMovementService $stock,
        private readonly NotificationService $notifications,
    ) {}

    public function index(Request $request)
    {
        return $this->paginated(
            Transfer::with([
                'items.item',
                'fromWarehouse',
                'fromLocation',
                'toWarehouse',
                'toLocation',
                'requester',
                'approver',
                'completer',
            ])
                ->latest()
                ->paginate(
                    $request->integer('per_page', 15)
                )
        );
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'from_warehouse_id' => [
                'required',
                'exists:warehouses,id',
            ],

            'from_location_id' => [
                'nullable',
                'exists:warehouse_locations,id',
            ],

            'to_warehouse_id' => [
                'required',
                'exists:warehouses,id',
            ],

            'to_location_id' => [
                'nullable',
                'exists:warehouse_locations,id',
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

            'items.*.quantity' => [
                'required',
                'numeric',
                'min:0.001',
            ],
        ]);

        $items = $data['items'];

        unset($data['items']);

        $number = (
            new class extends BaseService {}
        )->generateNumber(
            'transfers',
            'transfer_number',
            Setting::get(
                'numbering.transfer_prefix',
                'TRF'
            ),
            6
        );

        $transfer = Transfer::create(
            $data + [
                'transfer_number' => $number,

                'transfer_date' => $data['transfer_date']
                    ?? now()->toDateString(),

                'requested_by' => auth()->id(),

                'created_by' => auth()->id(),

                'status' => 'draft',
            ]
        );

        foreach ($items as $row) {
            TransferItem::create(
                $row + [
                    'transfer_id' => $transfer->id,
                ]
            );
        }

        return $this->created(
            $transfer->load([
                'items.item',
                'fromWarehouse',
                'fromLocation',
                'toWarehouse',
                'toLocation',
                'requester',
            ]),
            'Transfer created'
        );
    }

    public function show(Transfer $transfer)
    {
        return $this->success(
            $transfer->load(
                $this->transferRelations()
            )
        );
    }

    public function update(
        Request $request,
        Transfer $transfer
    ) {
        if ($transfer->status !== 'draft') {
            return $this->error(
                'Only draft transfers can be updated.',
                null,
                422
            );
        }

        $data = $request->validate([
            'from_warehouse_id' => [
                'nullable',
                'exists:warehouses,id',
            ],

            'from_location_id' => [
                'nullable',
                'exists:warehouse_locations,id',
            ],

            'to_warehouse_id' => [
                'nullable',
                'exists:warehouses,id',
            ],

            'to_location_id' => [
                'nullable',
                'exists:warehouse_locations,id',
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

            'items.*.quantity' => [
                'required',
                'numeric',
                'min:0.001',
            ],
        ]);

        $transfer->update(
            collect($data)
                ->except('items')
                ->toArray()
        );

        if (! empty($data['items'])) {
            /*
             * Replace the transfer item list so that
             * removed items do not remain attached.
             */
            $transfer->items()->delete();

            foreach ($data['items'] as $row) {
                TransferItem::create([
                    'transfer_id' => $transfer->id,

                    'item_id' => $row['item_id'],

                    'quantity' => $row['quantity'],
                ]);
            }
        }

        return $this->success(
            $transfer->fresh()->load(
                $this->transferRelations()
            ),
            'Transfer updated'
        );
    }

    public function destroy(Transfer $transfer)
    {
        if ($transfer->status !== 'draft') {
            return $this->error(
                'Only draft transfers can be deleted.',
                null,
                422
            );
        }

        $transfer->delete();

        return $this->success(
            null,
            'Transfer deleted'
        );
    }

    public function submit(Transfer $transfer)
    {
        if ($transfer->status !== 'draft') {
            return $this->error(
                'Only draft transfers can be submitted.',
                null,
                422
            );
        }

        if ($transfer->items()->count() === 0) {
            return $this->error(
                'Transfer must contain at least one item before submission.',
                null,
                422
            );
        }

        $transfer->update([
            'status' => 'pending_approval',
        ]);

        /*
         * Only users who can approve transfers
         * need the pending approval notification.
         *
         * NotificationService sends:
         * - in-app
         * - email
         */
        $this->notifications->notifyRoles(
            [
                'system_administrator',
                'warehouse_manager',
            ],
            'transfer_pending',
            'Transfer Pending Approval',
            "Transfer {$transfer->transfer_number} has been submitted and is awaiting approval.",
            [
                'transfer_id' => $transfer->id,
            ]
        );

        return $this->success(
            $transfer->fresh()->load(
                $this->transferRelations()
            ),
            'Transfer submitted'
        );
    }

    public function reject(
        Request $request,
        Transfer $transfer
    ) {
        if (
            $transfer->status !==
            'pending_approval'
        ) {
            return $this->error(
                'Only transfers pending approval can be rejected.',
                null,
                422
            );
        }

        $data = $request->validate([
            'reason' => [
                'required',
                'string',
                'max:1000',
            ],
        ]);

        $transfer->update([
            'status' => 'rejected',

            'rejection_reason' => $data['reason'],
        ]);

        /*
         * Notify the person who requested
         * the transfer.
         */
        $transfer->loadMissing(
            'requester'
        );

        if ($transfer->requester) {
            $this->notifications->notifyUser(
                $transfer->requester,
                'transfer_rejected',
                'Transfer Rejected',
                "Transfer {$transfer->transfer_number} has been rejected.",
                [
                    'transfer_id' => $transfer->id,

                    'reason' => $data['reason'],
                ]
            );
        }

        return $this->success(
            $transfer->fresh()->load(
                $this->transferRelations()
            ),
            'Transfer rejected'
        );
    }

    public function cancel(Transfer $transfer)
    {
        $previousStatus =
            $transfer->status;

        if (! in_array(
            $previousStatus,
            [
                'draft',
                'pending_approval',
                'approved',
            ],
            true
        )) {
            return $this->error(
                'Transfer cannot be cancelled.',
                null,
                422
            );
        }

        $transfer->update([
            'status' => 'cancelled',
        ]);

        /*
         * Cancelling a draft remains silent because
         * the transfer had not entered the workflow.
         */
        if ($previousStatus !== 'draft') {
            $transfer->loadMissing(
                'requester'
            );

            if ($transfer->requester) {
                $this->notifications->notifyUser(
                    $transfer->requester,
                    'transfer_cancelled',
                    'Transfer Cancelled',
                    "Transfer {$transfer->transfer_number} has been cancelled.",
                    [
                        'transfer_id' => $transfer->id,

                        'previous_status' => $previousStatus,
                    ]
                );
            }
        }

        return $this->success(
            $transfer->fresh()->load(
                $this->transferRelations()
            ),
            'Transfer cancelled'
        );
    }

    public function approve(Transfer $transfer)
    {
        /*
         * Approval should only happen after
         * submission.
         *
         * Do not allow direct draft -> approved.
         */
        if (
            $transfer->status !==
            'pending_approval'
        ) {
            return $this->error(
                'Only transfers pending approval can be approved.',
                null,
                422
            );
        }

        $transfer->update([
            'status' => 'approved',

            'approved_by' => auth()->id(),

            'approved_at' => now(),
        ]);

        /*
         * Tell the requester that their transfer
         * has been approved.
         */
        $transfer->loadMissing(
            'requester'
        );

        if ($transfer->requester) {
            $this->notifications->notifyUser(
                $transfer->requester,
                'transfer_approved',
                'Transfer Approved',
                "Transfer {$transfer->transfer_number} has been approved and is ready for completion.",
                [
                    'transfer_id' => $transfer->id,
                ]
            );
        }

        return $this->success(
            $transfer->fresh()->load(
                $this->transferRelations()
            ),
            'Transfer approved'
        );
    }

    public function complete(Transfer $transfer)
    {
        if (
            $transfer->status !==
            'approved'
        ) {
            return $this->error(
                'Only approved transfers can be completed.',
                null,
                422
            );
        }

        /*
         * Load everything needed before performing
         * inventory movements.
         */
        $transfer->loadMissing([
            'items.item',
            'requester',
        ]);

        /*
         * Perform the entire transfer atomically.
         *
         * If any stock movement fails, all previous
         * movements made during this transfer are
         * automatically rolled back.
         */
        try {
            DB::transaction(function () use ($transfer) {

                foreach (
                    $transfer->items as $transferItem
                ) {
                    $item =
                        $transferItem->item
                        ?? Item::findOrFail(
                            $transferItem->item_id
                        );

                    $quantity =
                        (float)
                        $transferItem->quantity;

                    $unitCost =
                        (float) (
                            $item->unit_cost ?? 0
                        );

                    $totalValue =
                        $quantity * $unitCost;

                    /*
                     * Remove inventory from the
                     * source location.
                     */
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

                    /*
                     * Add inventory to the
                     * destination location.
                     */
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

                /*
                 * Only mark the transfer as completed
                 * after every inventory movement succeeds.
                 */
                $transfer->update([
                    'status' => 'completed',

                    'completed_by' => auth()->id(),

                    'completed_at' => now(),
                ]);
            });
        } catch (\RuntimeException $e) {

            /*
             * StockMovementService throws a RuntimeException
             * when inventory is insufficient.
             *
             * Return a validation-style response rather
             * than exposing it as a 500 server error.
             */
            return $this->error(
                $e->getMessage(),
                null,
                422
            );
        } catch (\Throwable $e) {

            /*
             * Unexpected failures are logged by Laravel,
             * while the user receives a safe response.
             */
            report($e);

            return $this->error(
                'The transfer could not be completed. No inventory changes were made.',
                null,
                500
            );
        }

        /*
         * Send the notification only AFTER the database
         * transaction has successfully committed.
         *
         * NotificationService handles:
         * - in-app notification
         * - email notification
         */
        if ($transfer->requester) {
            $this->notifications->notifyUser(
                $transfer->requester,
                'transfer_completed',
                'Transfer Completed',
                "Transfer {$transfer->transfer_number} has been completed successfully.",
                [
                    'transfer_id' => $transfer->id,
                ]
            );
        }

        return $this->success(
            $transfer->fresh()->load(
                $this->transferRelations()
            ),
            'Transfer completed'
        );
    }

    private function transferRelations(): array
    {
        return [
            'items.item.category',
            'items.item.unit',

            'fromWarehouse',
            'fromLocation',

            'toWarehouse',
            'toLocation',

            'requester.roles',
            'approver.roles',
            'completer.roles',
        ];
    }
}
