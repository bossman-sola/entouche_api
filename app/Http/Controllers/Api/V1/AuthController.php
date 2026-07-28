<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends BaseApiController
{
    public function login(LoginRequest $request)
    {
        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password) || $user->status !== 'active') {
            return $this->error('Invalid credentials', null, 401);
        }

        $data = (object) [
            'user' => $user->load('roles.permissions'),
            'access_token' => $user->createToken('api')->accessToken,
            'token_type' => 'Bearer',
        ];

        return $this->success($data, 'Login successful');
    }

    public function logout(Request $request)
    {
        $user = $request->user();

        if ($user && $token = $user->token()) {
            $token->revoke();
        }

        return $this->success(null, 'Logout successful');
    }

    public function me(Request $request)
    {
        return $this->success($request->user()->load('roles.permissions'));
    }

    public function refresh(Request $request)
    {
        return $this->success((object) [
            'access_token' => $request->user()->createToken('api')->accessToken,
            'token_type' => 'Bearer',
        ], 'Token refreshed');
    }
}
