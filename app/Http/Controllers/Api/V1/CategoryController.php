<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Category;

class CategoryController extends MasterCrudController
{
    protected string $model = Category::class;

    protected array $rules =
        [
            'parent_id' => [
                'nullable',
                'exists:categories,id',
            ],
            'name' => [
                'required',
                'string',
            ],
            'code' => [
                'required',
                'string',
            ],
            'status' => [
                'sometimes',
                'in:active,inactive',
            ]];
}
