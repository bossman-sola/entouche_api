<?php

namespace App\Http\Requests\MasterData;

use App\Http\Requests\ApiRequest;
use Illuminate\Validation\Rule;

class LocationRequest extends ApiRequest
{
    public function rules(): array
    {
        $id = $this->route('location')?->id;

        return [
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('locations', 'code')->where('warehouse_id', $this->input('warehouse_id'))->ignore($id),
            ],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
