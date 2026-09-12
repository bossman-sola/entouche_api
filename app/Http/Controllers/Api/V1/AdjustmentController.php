<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Adjustment;
use App\Models\AdjustmentItem;
use App\Models\Item;
use App\Models\Setting;
use App\Models\StockBalance;
use App\Services\BaseService;
use App\Services\NotificationService;
use App\Services\StockMovementService;
use Illuminate\Http\Request;

class AdjustmentController extends BaseApiController
{
    public function __construct(
        private readonly StockMovementService $stock,
        private readonly NotificationService $notifications,
    ) {}

    public function index(Request $request)
    {
        return $this->paginated(
            Adjustment::with([
                'items.item',
                'items.location',
                'warehouse',
                'location',
                'adjustedBy',
                'approvedBy',
            ])
                ->latest()
                ->paginate(
                    $request->integer(
                        'per_page',
                        15
                    )
                )
        );
    }

    public function show(Adjustment $adjustment)
    {
        return $this->success(
            $adjustment->load([
                'items.item.category',
                'items.item.unit',
                'items.location',
                'warehouse',
                'location',
                'adjustedBy.roles',
                'approvedBy.roles',
            ])
        );
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'warehouse_id' => [
                'required',
                'exists:warehouses,id',
            ],

            'warehouse_location_id' => [
                'nullable',
                'exists:warehouse_locations,id',
            ],

            'adjustment_type' => [
                'required',
                'in:increase,decrease,set_stock',
            ],

            'reason' => [
                'nullable',
                'string',
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

            'items.*.adjustment_quantity' => [
                'required',
                'numeric',
                'min:0',
            ],

            'items.*.unit_cost' => [
                'sometimes',
                'nullable',
                'numeric',
                'min:0',
            ],
        ]);

        $items = $data['items'];

        unset($data['items']);

        $number = (
            new class extends BaseService {}
        )->generateNumber(
            'adjustments',
            'adjustment_number',
            Setting::get(
                'numbering.adjustment_prefix',
                'ADJ'
            ),
            6
        );

        $adjustment = Adjustment::create(
            $data + [
                'adjustment_number' => $number,
                'adjusted_by' => auth()->id(),
                'created_by' => auth()->id(),
                'status' => 'draft',
                'adjustment_date' => now()->toDateString(),
            ]
        );

        foreach ($items as $row) {
            $item = Item::findOrFail(
                $row['item_id']
            );

            $unitCost = (float) (
                $row['unit_cost']
                ?? $item->unit_cost
                ?? 0
            );

            $locationId =
                $row['warehouse_location_id']
                ?? $data['warehouse_location_id']
                ?? null;

            /*
             * Always get current stock from the database.
             * Do not trust quantity_before from the frontend.
             */
            $stockBalance = StockBalance::where(
                'item_id',
                $row['item_id']
            )
                ->where(
                    'warehouse_id',
                    $data['warehouse_id']
                )
                ->where(
                    'warehouse_location_id',
                    $locationId
                )
                ->first();

            $quantityBefore = (float) (
                $stockBalance?->quantity_on_hand
                ?? 0
            );

            $adjustmentQuantity = (float) (
                $row['adjustment_quantity']
                ?? 0
            );

            switch ($adjustment->adjustment_type) {
                case 'increase':
                    $quantityAfter =
                        $quantityBefore
                        + $adjustmentQuantity;
                    break;

                case 'decrease':
                    if (
                        $adjustmentQuantity
                        > $quantityBefore
                    ) {
                        return $this->error(
                            "Cannot decrease {$item->name} by {$adjustmentQuantity}. Current stock is {$quantityBefore}.",
                            null,
                            422
                        );
                    }

                    $quantityAfter =
                        $quantityBefore
                        - $adjustmentQuantity;
                    break;

                case 'set_stock':
                    $quantityAfter =
                        $adjustmentQuantity;
                    break;

                default:
                    $quantityAfter =
                        $quantityBefore;
                    break;
            }

            AdjustmentItem::create([
                'adjustment_id' =>
                    $adjustment->id,

                'item_id' =>
                    $row['item_id'],

                'warehouse_location_id' =>
                    $locationId,

                'quantity_before' =>
                    $quantityBefore,

                'adjustment_quantity' =>
                    $adjustmentQuantity,

                'quantity_after' =>
                    $quantityAfter,

                'unit_cost' =>
                    $unitCost,
            ]);
        }

        return $this->created(
            $adjustment->load([
                'items.item',
                'items.location',
                'warehouse',
                'location',
                'adjustedBy',
            ]),
            'Adjustment created'
        );
    }

    public function update(
        Request $request,
        Adjustment $adjustment
    ) {
        if (
            $adjustment->status !==
            'draft'
        ) {
            return $this->error(
                'Only draft adjustments can be updated.',
                null,
                422
            );
        }

        $adjustment->update(
            $request->only([
                'warehouse_id',
                'warehouse_location_id',
                'adjustment_type',
                'reason',
                'notes',
            ])
        );

        return $this->success(
            $adjustment->fresh([
                'items.item',
                'items.location',
                'warehouse',
                'location',
                'adjustedBy',
            ]),
            'Adjustment updated'
        );
    }

    public function destroy(
        Adjustment $adjustment
    ) {
        if (
            $adjustment->status !==
            'draft'
        ) {
            return $this->error(
                'Only draft adjustments can be deleted.',
                null,
                422
            );
        }

        $adjustment->delete();

        return $this->success(
            null,
            'Adjustment deleted'
        );
    }

    public function submit(
        Adjustment $adjustment
    ) {
        if (
            $adjustment->status !==
            'draft'
        ) {
            return $this->error(
                'Only draft adjustments can be submitted.',
                null,
                422
            );
        }

        $adjustment->update([
            'status' =>
                'pending_approval',
        ]);

        $this->notifications->notifyAdmins(
            'adjustment_pending',
            'Adjustment Pending',
            "Adjustment {$adjustment->adjustment_number} has been submitted and is awaiting approval.",
            [
                'adjustment_id' =>
                    $adjustment->id,
            ],
        );

        return $this->success(
            $adjustment->fresh([
                'items.item',
                'items.location',
                'warehouse',
                'location',
                'adjustedBy',
            ]),
            'Adjustment submitted'
        );
    }

    public function reject(
        Request $request,
        Adjustment $adjustment
    ) {
        if (
            $adjustment->status !==
            'pending_approval'
        ) {
            return $this->error(
                'Only adjustments pending approval can be rejected.',
                null,
                422
            );
        }

        $data = $request->validate([
            'reason' => [
                'required',
                'string',
            ],
        ]);

        $adjustment->update([
            'status' =>
                'rejected',

            'rejection_reason' =>
                $data['reason'],
        ]);

        $this->notifications->notifyAdmins(
            'adjustment_rejected',
            'Adjustment Rejected',
            "Adjustment {$adjustment->adjustment_number} has been rejected.",
            [
                'adjustment_id' =>
                    $adjustment->id,

                'reason' =>
                    $data['reason'],
            ],
        );

        return $this->success(
            $adjustment->fresh([
                'items.item',
                'items.location',
                'warehouse',
                'location',
                'adjustedBy',
                'approvedBy',
            ]),
            'Adjustment rejected'
        );
    }

    public function cancel(
        Adjustment $adjustment
    ) {
        if (! in_array(
            $adjustment->status,
            [
                'draft',
                'pending_approval',
                'approved',
            ],
            true
        )) {
            return $this->error(
                'Adjustment cannot be cancelled.',
                null,
                422
            );
        }

        $previousStatus =
            $adjustment->status;

        $adjustment->update([
            'status' =>
                'cancelled',
        ]);

        if (
            $previousStatus !==
            'draft'
        ) {
            $this->notifications->notifyAdmins(
                'adjustment_cancelled',
                'Adjustment Cancelled',
                "Adjustment {$adjustment->adjustment_number} has been cancelled.",
                [
                    'adjustment_id' =>
                        $adjustment->id,

                    'previous_status' =>
                        $previousStatus,
                ],
            );
        }

        return $this->success(
            $adjustment->fresh([
                'items.item',
                'items.location',
                'warehouse',
                'location',
                'adjustedBy',
                'approvedBy',
            ]),
            'Adjustment cancelled'
        );
    }

    public function approve(
        Adjustment $adjustment
    ) {
        if (
            $adjustment->status !==
            'pending_approval'
        ) {
            return $this->error(
                'Only adjustments pending approval can be approved.',
                null,
                422
            );
        }

        foreach (
            $adjustment->items
            as $item
        ) {
            if (
                $adjustment->adjustment_type
                === 'set_stock'
            ) {
                $difference =
                    (float) $item->quantity_after
                    - (float) $item->quantity_before;

                if ($difference === 0.0) {
                    continue;
                }

                $direction =
                    $difference > 0
                        ? 'in'
                        : 'out';

                $movementQuantity =
                    abs($difference);
            } else {
                $direction =
                    $adjustment->adjustment_type
                    === 'decrease'
                        ? 'out'
                        : 'in';

                $movementQuantity =
                    (float)
                    $item->adjustment_quantity;
            }

            $this->stock->move([
                'item_id' =>
                    $item->item_id,

                'warehouse_id' =>
                    $adjustment->warehouse_id,

                'warehouse_location_id' =>
                    $item->warehouse_location_id
                    ?? $adjustment->warehouse_location_id,

                'transaction_type' =>
                    $direction === 'in'
                        ? 'adjustment_in'
                        : 'adjustment_out',

                'direction' =>
                    $direction,

                'quantity' =>
                    $movementQuantity,

                'unit_cost' =>
                    $item->unit_cost
                    ?? 0,

                'reference_type' =>
                    Adjustment::class,

                'reference_id' =>
                    $adjustment->id,

                'remarks' =>
                    $adjustment->reason,
            ]);
        }

        $adjustment->update([
            'status' =>
                'approved',

            'approved_by' =>
                auth()->id(),

            'approved_at' =>
                now(),
        ]);

        $this->notifications->notifyAdmins(
            'adjustment_approved',
            'Adjustment Approved',
            "Adjustment {$adjustment->adjustment_number} has been approved and inventory has been updated.",
            [
                'adjustment_id' =>
                    $adjustment->id,
            ],
        );

        return $this->success(
            $adjustment->fresh([
                'items.item',
                'items.location',
                'warehouse',
                'location',
                'adjustedBy',
                'approvedBy',
            ]),
            'Adjustment approved'
        );
    }
}
