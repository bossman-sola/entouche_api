<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Supplier;

class SupplierController extends MasterCrudController
{
    protected string $model = Supplier::class;
    protected array $rules = ['name' => ['required', 'string'], 'code' => ['required', 'string'], 'email' => ['nullable', 'email'], 'phone' => ['nullable', 'string'], 'address' => ['nullable', 'string'], 'status' => ['sometimes', 'in:active,inactive']];
}
