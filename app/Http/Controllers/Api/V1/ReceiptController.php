<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Mail\SystemNotificationMail;
use App\Models\Receipt;
use App\Models\ReceiptItem;
use App\Models\Setting;
use App\Models\User;
use App\Services\BaseService;
use App\Services\NotificationService;
use App\Services\StockMovementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class ReceiptController extends BaseApiController
{
    public function __construct(
        private readonly StockMovementService $stock,
        private readonly NotificationService $notifications,
    ) {}

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
            'receipt_number' => $helper->generateNumber(
                'receipts',
                'receipt_number',
                (string) Setting::get('numbering.receipt_prefix', 'RCPT'),
                6
            ),
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

        return $this->created(
            $receipt->load(
                $this->receiptRelations()
            ),
            'Receipt created'
        );
    }

    public function show(Receipt $receipt)
    {
        return $this->success(
            $receipt->load(
                $this->receiptRelations()
            )
        );
    }

    public function update(Request $request, Receipt $receipt)
    {
        if ($receipt->status !== 'draft') {
            return $this->error('Only draft receipts can be updated.', null, 422);
        }

        $data = $request->validate([
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'warehouse_id' => ['nullable', 'exists:warehouses,id'],
            'receiving_location_id' => ['nullable', 'exists:warehouse_locations,id'],
            'receipt_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'items' => ['nullable', 'array', 'min:1'],
            'items.*.item_id' => ['required', 'exists:items,id'],
            'items.*.warehouse_location_id' => ['nullable', 'exists:warehouse_locations,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.001'],
            'items.*.unit_cost' => ['sometimes', 'numeric', 'min:0'],
        ]);

        $receipt->update(collect($data)->except('items')->toArray());
        if (! empty($data['items'])) {

            $receipt->items()->delete();

            foreach ($data['items'] as $row) {
                ReceiptItem::create([
                    'receipt_id' => $receipt->id,
                    'item_id' => $row['item_id'],
                    'warehouse_location_id' => $row['warehouse_location_id'] ?? $receipt->receiving_location_id,
                    'quantity' => $row['quantity'],
                    'unit_cost' => $row['unit_cost'] ?? 0,
                    'total_cost' => $row['quantity'] * ($row['unit_cost'] ?? 0),
                ]);
            }
        }

        return $this->success(
            $receipt->fresh()->load(
                $this->receiptRelations()
            ),
            'Receipt updated'
        );
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
    if ($receipt->status !== 'approved') {
        return $this->error(
            'Only approved receipts can be received.',
            null,
            422
        );
    }

    foreach ($receipt->items as $item) {
        $this->stock->move([
            'item_id' => $item->item_id,

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
        'status' => 'received',
        'received_by' => auth()->id(),
        'received_at' => now(),
    ]);

    $recipient = $receipt->creator;

    $title = 'Receipt Completed';

    $message =
        "Receipt {$receipt->receipt_number} has been completed and added to inventory.";

    if ($recipient) {
        // In-app
        $this->notifications->notifyUser(
            $recipient,
            'receipt_completed',
            $title,
            $message,
            [
                'receipt_id' => $receipt->id,
            ],
        );

        // Email
        if (
            $recipient->status === 'active' &&
            ! empty($recipient->email)
        ) {
            Mail::to($recipient->email)->send(
                new SystemNotificationMail(
                    $title,
                    $message
                )
            );
        }
    }

    return $this->success(
        $receipt->fresh()->load(
            $this->receiptRelations()
        ),
        'Receipt received'
    );
}
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
            'status' => 'submitted',
        ]);

        $title = 'New Receipt Awaiting Approval';

        $message =
            "Receipt {$receipt->receipt_number} has been submitted and is awaiting approval.";

        /*
         * Users allowed to approve receipts:
         * System Administrator + Warehouse Manager
         */
        $approvers = User::role([
            'system_administrator',
            'warehouse_manager',
        ])
            ->where('status', 'active')
            ->get();

        foreach ($approvers as $user) {
            // In-app
            $this->notifications->notifyUser(
                $user,
                'new_receipt_awaiting_approval',
                $title,
                $message,
                [
                    'receipt_id' => $receipt->id,
                ],
            );

            // Email
            if (! empty($user->email)) {
                Mail::to($user->email)->send(
                    new SystemNotificationMail(
                        $title,
                        $message
                    )
                );
            }
        }

        return $this->success(
            $receipt->fresh()->load(
                $this->receiptRelations()
            ),
            'Receipt submitted for approval'
        );
    }

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
            'status' => 'approved',
            'approved_at' => now(),
        ]);

        $recipient = $receipt->creator;

        $title = 'Receipt Approved';

        $message =
            "Receipt {$receipt->receipt_number} has been approved and is ready to be received.";

        if ($recipient) {
            // In-app
            $this->notifications->notifyUser(
                $recipient,
                'receipt_approved',
                $title,
                $message,
                [
                    'receipt_id' => $receipt->id,
                ],
            );

            // Email
            if (
                $recipient->status === 'active' &&
                ! empty($recipient->email)
            ) {
                Mail::to($recipient->email)->send(
                    new SystemNotificationMail(
                        $title,
                        $message
                    )
                );
            }
        }

        return $this->success(
            $receipt->fresh()->load(
                $this->receiptRelations()
            ),
            'Receipt approved'
        );
    }

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
        'status' => 'cancelled',
    ]);

    /*
     * Draft cancellation remains silent.
     */
    if ($previousStatus !== 'draft') {
        $recipient =
            $receipt->creator;

        $title =
            'Receipt Cancelled';

        $message =
            "Receipt {$receipt->receipt_number} has been cancelled.";

        if ($recipient) {
            // In-app
            $this->notifications->notifyUser(
                $recipient,
                'receipt_cancelled',
                $title,
                $message,
                [
                    'receipt_id' =>
                        $receipt->id,

                    'previous_status' =>
                        $previousStatus,
                ],
            );

            // Email
            if (
                $recipient->status === 'active' &&
                ! empty($recipient->email)
            ) {
                Mail::to(
                    $recipient->email
                )->send(
                    new SystemNotificationMail(
                        $title,
                        $message
                    )
                );
            }
        }
    }

    return $this->success(
        $receipt->fresh()->load(
            $this->receiptRelations()
        ),
        'Receipt cancelled'
    );
}

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
