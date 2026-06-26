<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends BaseApiController
{
    public function index()
    {
        return $this->success(Role::with('permissions')->get());
    }

    public function show(Role $role)
    {
        return $this->success($role->load('permissions'));
    }

    public function permissions()
    {
        return $this->success(Permission::orderBy('name')->get());
    }
}
