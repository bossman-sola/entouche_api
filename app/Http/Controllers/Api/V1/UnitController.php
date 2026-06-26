<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Unit;

class UnitController extends MasterCrudController
{
    protected string $model = Unit::class;
    protected array $rules = ['name' => ['required', 'string'], 'abbreviation' => ['required', 'string'], 'status' => ['sometimes', 'in:active,inactive']];
}
