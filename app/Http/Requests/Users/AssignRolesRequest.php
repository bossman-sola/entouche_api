<?php

namespace App\Http\Requests\Users;

use App\Http\Requests\ApiRequest;

class AssignRolesRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'roles' => ['required', 'array'],
            'roles.*' => ['string', 'exists:roles,name'],
        ];
    }
}
