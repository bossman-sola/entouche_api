<?php

namespace App\Http\Requests\MasterData;

use App\Http\Requests\ApiRequest;
use Illuminate\Validation\Rule;

class UnitRequest extends ApiRequest
{
    public function rules(): array
    {
        $id = $this->route('unit')?->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'symbol' => ['required', 'string', 'max:20', Rule::unique('units', 'symbol')->ignore($id)],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
