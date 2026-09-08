<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\InventoryTransaction;
use App\Models\Transfer;
use Illuminate\Http\Request;

class TransactionController extends BaseApiController
{
    public function index(Request $request)
    {
        $transactions = InventoryTransaction::with([
            'item.category',
            'item.unit',
            'warehouse',
            'location',
            'performer',
        ])
            ->when(
                $request->item_id,
                fn ($q, $v) => $q->where('item_id', $v)
            )
            ->when(
                $request->warehouse_id,
                fn ($q, $v) => $q->where('warehouse_id', $v)
            )
            ->when(
                $request->transaction_type,
                fn ($q, $v) => $q->where('transaction_type', $v)
            )
            ->when(
                $request->direction,
                fn ($q, $v) => $q->where('direction', $v)
            )
            ->when(
                $request->date_from,
                fn ($q, $v) => $q->whereDate('transaction_date', '>=', $v)
            )
            ->when(
                $request->date_to,
                fn ($q, $v) => $q->whereDate('transaction_date', '<=', $v)
            )
            ->latest('transaction_date')
            ->paginate(
                $request->integer('per_page', 20)
            );

        /*
         * Attach transfer route information to every transfer ledger row.
         *
         * InventoryTransaction itself only knows the warehouse/location
         * involved in that individual movement. The full From -> To route
         * belongs to the parent Transfer record.
         */
        $transferIds = $transactions->getCollection()
            ->filter(
                fn ($transaction) =>
                    $transaction->reference_type === Transfer::class &&
                    $transaction->reference_id
            )
            ->pluck('reference_id')
            ->unique()
            ->values();

        $transfers = Transfer::with([
            'fromWarehouse',
            'fromLocation',
            'toWarehouse',
            'toLocation',
        ])
            ->whereIn('id', $transferIds)
            ->get()
            ->keyBy('id');

        $transactions->getCollection()->transform(
            function ($transaction) use ($transfers) {
                $data = $transaction->toArray();

                if (
                    $transaction->reference_type === Transfer::class &&
                    $transaction->reference_id
                ) {
                    $transfer = $transfers->get(
                        $transaction->reference_id
                    );

                    $data['transfer'] = $transfer
                        ? $transfer->toArray()
                        : null;
                }

                return $data;
            }
        );

        return $this->paginated($transactions);
    }

    public function show(InventoryTransaction $txn)
    {
        $txn->load([
            'item.category',
            'item.unit',
            'warehouse',
            'location',
            'performer',
        ]);

        $data = $txn->toArray();

        if (
            $txn->reference_type === Transfer::class &&
            $txn->reference_id
        ) {
            $transfer = Transfer::with([
                'fromWarehouse',
                'fromLocation',
                'toWarehouse',
                'toLocation',
            ])->find($txn->reference_id);

            $data['transfer'] = $transfer
                ? $transfer->toArray()
                : null;
        }

        return $this->success($data);
    }
}