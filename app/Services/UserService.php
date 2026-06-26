<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Arr;

class UserService
{
    public function create(array $data): User
    {
        $roles = Arr::pull($data, 'roles', []);
        $user = User::create($data);

        if ($roles !== []) {
            $user->syncRoles($roles);
        }

        return $user->load('roles');
    }

    public function update(User $user, array $data): User
    {
        if (array_key_exists('password', $data) && blank($data['password'])) {
            unset($data['password']);
        }

        $user->update($data);

        return $user->fresh('roles');
    }

    public function assignRoles(User $user, array $roles): User
    {
        $user->syncRoles($roles);

        return $user->fresh('roles');
    }
}
