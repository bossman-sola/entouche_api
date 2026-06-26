<?php

namespace App\Http\Requests\Items;

class UpdateItemRequest extends StoreItemRequest
{
    public function rules(): array
    {
        $rules = parent::rules();
        $rules['unit_id'] = ['sometimes', 'exists:units,id'];
        $rules['name'] = ['sometimes', 'string', 'max:255'];

        return $rules;
    }
}
