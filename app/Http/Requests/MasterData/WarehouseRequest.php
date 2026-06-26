<?php

namespace App\Http\Requests\MasterData;

use App\Http\Requests\ApiRequest;
use Illuminate\Validation\Rule;

class WarehouseRequest extends ApiRequest
{
    public function rules(): array
    {
        $id = $this->route('warehouse')?->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', Rule::unique('warehouses', 'code')->ignore($id)],
            'address' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
