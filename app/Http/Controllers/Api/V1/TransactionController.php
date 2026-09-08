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
        $transactions = InventoryTransaction::with(['item', 'warehouse', 'location', 'performer'])
            ->when($request->item_id, fn ($q, $v) => $q->where('item_id', $v))
            ->when($request->warehouse_id, fn ($q, $v) => $q->where('warehouse_id', $v))
            ->when($request->transaction_type, fn ($q, $v) => $q->where('transaction_type', $v))
            ->latest('transaction_date')
            ->paginate($request->integer('per_page', 20));

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

        $data['transfer'] = $transfer;
    }

    return $this->success($data);
}
}
