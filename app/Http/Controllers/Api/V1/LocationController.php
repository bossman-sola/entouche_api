<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Location;

class LocationController extends MasterCrudController
{
    protected string $model = Location::class;

    protected array $relations = ['warehouse'];

    protected array $rules = ['warehouse_id' => ['required', 'exists:warehouses,id'], 'name' => ['required', 'string'], 'code' => ['required', 'string'], 'type' => ['sometimes', 'string'], 'status' => ['sometimes', 'in:active,inactive']];
}
