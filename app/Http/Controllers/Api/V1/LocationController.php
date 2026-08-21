<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Location;
use Illuminate\Http\Request;

class LocationController extends MasterCrudController
{
    protected string $model = Location::class;

    protected array $relations = ['warehouse'];

    protected array $rules = [
        'warehouse_id' => ['required', 'exists:warehouses,id'],
        'name' => ['required', 'string'],
        'code' => ['required', 'string'],
        'type' => ['sometimes', 'string'],
        'description' => ['nullable', 'string'],
        'capacity' => ['nullable', 'integer', 'min:0'],
        'status' => ['sometimes', 'in:active,inactive'],
    ];

    public function index(Request $request)
    {
        $warehouseId = $request->route('warehouse');
        $warehouseId = is_object($warehouseId) ? $warehouseId->id : $warehouseId;

        $query = Location::query()->with(['stockBalances.item']);

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%"));
        }

        $locations = $query->latest()->paginate($request->integer('per_page', 15));

        $locations->getCollection()->transform(function (Location $location) {
            $quantityOnHand = (int) $location->stockBalances->sum('quantity_on_hand');
            $stockValue = $location->stockBalances->sum(
                fn ($b) => $b->quantity_on_hand * ($b->item->unit_cost ?? 0)
            );

            $location->quantity_on_hand = $quantityOnHand;
            $location->stock_value = round($stockValue, 2);
            $location->utilization_pct = $location->capacity
                ? round(($quantityOnHand / $location->capacity) * 100, 1)
                : null;

            return $location->makeHidden('stockBalances');
        });

        return $this->paginated($locations);
    }
}
