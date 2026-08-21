<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Location;
use App\Models\StockBalance;
use App\Models\Warehouse;

class WarehouseController extends MasterCrudController
{
    protected string $model = Warehouse::class;

    protected array $relations = ['locations'];

    protected array $rules = ['name' => ['required', 'string'], 'code' => ['required', 'string'], 'address' => ['nullable', 'string'], 'status' => ['sometimes', 'in:active,inactive']];

    public function locations(Warehouse $warehouse)
    {
        $locations = $warehouse->locations()
            ->with('stockBalances.item')
            ->get()
            ->map(function (Location $location) {
                $quantityOnHand = (int) $location->stockBalances->sum('quantity_on_hand');
                $stockValue = $location->stockBalances->sum(
                    fn($balance) => $balance->quantity_on_hand * ($balance->item->unit_cost ?? 0)
                );

                return array_merge(
                    $location->makeHidden('stockBalances')->toArray(),
                    [
                        'quantity_on_hand' => $quantityOnHand,
                        'stock_value' => round($stockValue, 2),
                        'utilization_pct' => $location->capacity
                            ? round(($quantityOnHand / $location->capacity) * 100, 1)
                            : null,
                    ]
                );
            })
            ->values();

        return $this->success($locations);
    }

    public function stockSummary(Warehouse $warehouse)
    {
        return $this->success(StockBalance::with(['item', 'location'])->where('warehouse_id', $warehouse->id)->get());
    }
}