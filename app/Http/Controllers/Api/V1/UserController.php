<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends BaseApiController
{
    public function index(Request $request)
    {
        $users = User::with('roles')
            ->when($request->search, fn ($q, $s) => $q->where(fn ($q) => $q->where('name', 'like', "%{$s}%")->orWhere('email', 'like', "%{$s}%")))
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return $this->paginated($users);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:50'],
            'password' => ['required', 'string', 'min:8'],
            'status' => ['sometimes', 'in:active,inactive'],
            'roles' => ['sometimes', 'array'],
        ]);

        $roles = $data['roles'] ?? [];
        unset($data['roles']);
        $user = User::create($data);
        if ($roles) {
            $user->syncRoles($roles);
        }

        return $this->created($user->load('roles'), 'User created');
    }

    public function show(User $user)
    {
        return $this->success($user->load('roles.permissions'));
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'unique:users,email,' . $user->id],
            'phone' => ['nullable', 'string', 'max:50'],
            'password' => ['sometimes', 'nullable', 'string', 'min:8'],
            'status' => ['sometimes', 'in:active,inactive'],
        ]);

        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        $user->update($data);

        return $this->success($user->fresh('roles'), 'User updated');
    }

    public function destroy(User $user)
    {
        $user->delete();

        return $this->success(null, 'User deleted');
    }

    public function assignRole(Request $request, User $user)
    {
        $data = $request->validate(['roles' => ['required', 'array']]);
        $user->syncRoles($data['roles']);

        return $this->success($user->fresh('roles'), 'Role assigned');
    }

    public function removeRole(Request $request, User $user)
    {
        $data = $request->validate(['role' => ['required', 'string']]);
        $user->removeRole($data['role']);

        return $this->success($user->fresh('roles'), 'Role removed');
    }

    public function toggleStatus(User $user)
    {
        $user->update(['status' => $user->status === 'active' ? 'inactive' : 'active']);

        return $this->success($user->fresh(), 'User status updated');
    }
}
