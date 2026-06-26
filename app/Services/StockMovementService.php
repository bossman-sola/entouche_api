<?php

namespace App\Services;

use App\Models\InventoryTransaction;
use App\Models\Setting;
use App\Models\StockBalance;

class StockMovementService extends BaseService
{
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

            return InventoryTransaction::create([
                'transaction_number' => $this->generateNumber('inventory_transactions', 'transaction_number', Setting::get('numbering.transaction_prefix', 'TXN'), 6),
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
}
