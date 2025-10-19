<?php

namespace Modules\Auth\Services;

use App\Http\Traits\ResponsesTrait;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Modules\Auth\Models\User;

class AuthService
{
    use ResponsesTrait;
    private User $user;
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function login($request)
    {
        $user = User::where(function ($query) use ($request) {
            if ($request->filled('email')) {
                $query->where('email', $request->input('email'));
            } elseif ($request->filled('phone')) {
                $query->where('phone', $request->input('phone'));
            }
        })->first();

        if(!$user ) {
            return null;
        }

        if (!Hash::check($request->input('password'), $user->password)) {
            return null;
        }

        $token = $user->createToken('Access Token')->plainTextToken;

        return [
            'token' => $token,
            'user' => $user,
            'role' => $user->getRoleNames()[0],
        ];
    }

    public function logout($user)
    {
        $user->currentAccessToken()->delete();
        $user->tokens()->delete();
    }
}
