<?php

namespace App\Services;

use App\Models\InventoryTransaction;
use App\Models\Item;
use App\Models\Location;
use App\Models\Setting;
use App\Models\StockBalance;
use App\Models\Warehouse;
use App\Mail\SystemNotificationMail;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

class StockMovementService extends BaseService
{
    public function __construct(private readonly NotificationService $notifications) {}

    public function move(array $data): InventoryTransaction
    {
        return $this->transaction(function () use ($data): InventoryTransaction {
            $balance = StockBalance::firstOrNew([
                'item_id' => $data['item_id'],
                'warehouse_id' => $data['warehouse_id'],
                'warehouse_location_id' => $data['warehouse_location_id'] ?? null,
            ]);

            $before = (float) ($balance->quantity_on_hand ?? 0);
            $qty = (float) $data['quantity'];
            $after = $data['direction'] === 'out' ? $before - $qty : $before + $qty;

            if ($after < 0 && ! Setting::get('inventory.allow_negative_stock', false)) {
                throw new \RuntimeException('Insufficient stock for this movement.');
            }

            $balance->quantity_on_hand = $after;
            $balance->quantity_reserved = $balance->quantity_reserved ?? 0;
            $balance->quantity_available = $after - (float) $balance->quantity_reserved;
            $balance->last_transaction_at = now();
            $balance->save();

            $this->maybeNotifyStockThreshold($before, $after, $data);

            return InventoryTransaction::create([
                'transaction_number' => $this->generateNumber(
                    'inventory_transactions',
                    'transaction_number',
                    Setting::get('numbering.transaction_prefix', 'TXN'),
                    6
                ),
                'item_id' => $data['item_id'],
                'warehouse_id' => $data['warehouse_id'],
                'warehouse_location_id' => $data['warehouse_location_id'] ?? null,
                'transaction_type' => $data['transaction_type'],
                'direction' => $data['direction'],
                'quantity' => $qty,
                'balance_before' => $before,
                'balance_after' => $after,
                'unit_cost' => $data['unit_cost'] ?? 0,
                'total_value' => $data['total_value'] ?? 0,
                'reference_type' => $data['reference_type'] ?? null,
                'reference_id' => $data['reference_id'] ?? null,
                'remarks' => $data['remarks'] ?? null,
                'performed_by' => auth()->id(),
                'transaction_date' => now(),
            ]);
        });
    }

 private function maybeNotifyStockThreshold(
    float $before,
    float $after,
    array $data
): void {
    if (! Setting::get(
        'inventory.low_stock_alerts',
        true
    )) {
        return;
    }

    $item = Item::find(
        $data['item_id']
    );

    if (! $item) {
        return;
    }

    $reorderLevel =
        (float) (
            $item->reorder_level ?? 0
        );

    if ($reorderLevel <= 0) {
        return;
    }

    $wasAboveThreshold =
        $before > $reorderLevel;

    $isAtOrBelowNow =
        $after <= $reorderLevel;

    /*
     * Only notify when stock crosses
     * the threshold.
     */
    if (
        ! $wasAboveThreshold ||
        ! $isAtOrBelowNow
    ) {
        return;
    }

    $warehouse = Warehouse::find(
        $data['warehouse_id']
    );

    $location =
        ! empty(
            $data['warehouse_location_id']
        )
            ? Location::find(
                $data[
                    'warehouse_location_id'
                ]
            )
            : null;

    $place = $location
        ? "{$warehouse?->name} ({$location->name})"
        : ($warehouse?->name ?? 'a warehouse');

    $notificationData = [
        'item_id' =>
            $item->id,

        'warehouse_id' =>
            $data['warehouse_id'],

        'warehouse_location_id' =>
            $data[
                'warehouse_location_id'
            ] ?? null,

        'current_stock' =>
            $after,

        'reorder_level' =>
            $reorderLevel,
    ];

    if ($after == $reorderLevel) {
        $type =
            'reorder_level_reached';

        $title =
            'Reorder Level Reached';

        $message =
            "{$item->name} has reached reorder level at {$place}.";
    } else {
        $type =
            'low_stock_alert';

        $title =
            'Low Stock Alert';

        $message =
            "{$item->name} is low on stock at {$place}.";
    }

    /*
     * Operational recipients:
     * - System Administrator
     * - Warehouse Manager
     * - Inventory Officer
     */
    $recipients = User::role([
        'system_administrator',
        'warehouse_manager',
        'inventory_officer',
    ])
        ->where(
            'status',
            'active'
        )
        ->get();

    foreach ($recipients as $user) {
        /*
         * In-app
         */
        $this->notifications->notifyUser(
            $user,
            $type,
            $title,
            $message,
            $notificationData,
        );

        /*
         * Email
         */
        if (! empty($user->email)) {
            Mail::to(
                $user->email
            )->send(
                new SystemNotificationMail(
                    $title,
                    $message
                )
            );
        }
    }
}
}
