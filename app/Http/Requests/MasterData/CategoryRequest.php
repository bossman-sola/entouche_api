<?php

namespace App\Http\Requests\MasterData;

use App\Http\Requests\ApiRequest;
use Illuminate\Validation\Rule;

class CategoryRequest extends ApiRequest
{
    public function rules(): array
    {
        $id = $this->route('category')?->id;

        return [
            'parent_id' => ['nullable', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', Rule::unique('categories', 'code')->ignore($id)],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
