<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\InventoryTransaction;
use App\Models\Item;
use App\Models\StockBalance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends BaseApiController
{
    public function stockSummary(Request $request)
    {
        $balances = StockBalance::with(['item.category', 'warehouse', 'location'])
            ->when($request->warehouse_id, fn ($q, $v) => $q->where('warehouse_id', $v))
            ->when($request->item_id, fn ($q, $v) => $q->where('item_id', $v))
            ->get();

        return $this->success([
            'total_items' => $balances->pluck('item_id')->unique()->count(),
            'total_quantity_on_hand' => $balances->sum('quantity_on_hand'),
            'low_stock_count' => $balances->filter(fn ($b) => $b->item && $b->quantity_on_hand <= $b->item->reorder_level)->count(),
            'balances' => $balances,
        ]);
    }

    public function movementReport(Request $request)
    {
        $query = InventoryTransaction::with(['item', 'warehouse', 'location'])
            ->when($request->item_id, fn ($q, $v) => $q->where('item_id', $v))
            ->when($request->warehouse_id, fn ($q, $v) => $q->where('warehouse_id', $v))
            ->when($request->transaction_type, fn ($q, $v) => $q->where('transaction_type', $v))
            ->when($request->date_from, fn ($q, $v) => $q->whereDate('transaction_date', '>=', $v))
            ->when($request->date_to, fn ($q, $v) => $q->whereDate('transaction_date', '<=', $v));

        $totals = [
            'total_in' => (clone $query)->where('direction', 'in')->sum('quantity'),
            'total_out' => (clone $query)->where('direction', 'out')->sum('quantity'),
            'total_value_in' => (clone $query)->where('direction', 'in')->sum('total_value'),
            'total_value_out' => (clone $query)->where('direction', 'out')->sum('total_value'),
        ];

        return $this->success(['totals' => $totals, 'transactions' => $query->latest('transaction_date')->paginate($request->integer('per_page', 20))]);
    }

    public function lowStock(Request $request)
    {
        $items = Item::active()
            ->with(['category', 'unit'])
            ->withSum('stockBalances as total_stock', 'quantity_on_hand')
            ->whereHas('stockBalances', fn ($q) => $q->whereColumn('quantity_on_hand', '<=', 'items.reorder_level'))
            ->paginate($request->integer('per_page', 20));

        return $this->paginated($items);
    }

    public function dashboardStats()
    {
        return $this->success([
            'summary' => [
                'total_items' => Item::count(),
                'total_stock_value' => DB::table('stock_balances as sb')->join('items as i', 'i.id', '=', 'sb.item_id')->sum(DB::raw('sb.quantity_on_hand * i.unit_cost')),
                'low_stock_count' => Item::whereHas('stockBalances', fn ($q) => $q->whereColumn('quantity_on_hand', '<=', 'items.reorder_level'))->count(),
                'transactions_7d' => InventoryTransaction::where('transaction_date', '>=', now()->subDays(7))->count(),
            ],
            'recent_transactions' => InventoryTransaction::with(['item', 'warehouse'])->latest('transaction_date')->limit(10)->get(),
        ]);
    }
}