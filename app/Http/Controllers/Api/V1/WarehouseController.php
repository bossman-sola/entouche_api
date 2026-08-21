<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\StockBalance;
use App\Models\Warehouse;

class WarehouseController extends MasterCrudController
{
    protected string $model = Warehouse::class;

    protected array $relations = ['locations'];

    protected array $rules = ['name' => ['required', 'string'], 'code' => ['required', 'string'], 'address' => ['nullable', 'string'], 'status' => ['sometimes', 'in:active,inactive']];

    public function locations(Warehouse $warehouse)
    {
        return $this->success($warehouse->locations);
    }

    public function stockSummary(Warehouse $warehouse)
    {
        return $this->success(StockBalance::with(['item', 'location'])->where('warehouse_id', $warehouse->id)->get());
    }
}
