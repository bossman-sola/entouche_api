<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\InventoryTransaction;
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
        return $this->success($txn->load(['item', 'warehouse', 'location', 'performer']));
    }
}
