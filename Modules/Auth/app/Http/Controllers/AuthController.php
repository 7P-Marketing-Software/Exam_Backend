<?php

namespace Modules\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\Auth\Http\Requests\LoginRequest;
use Modules\Auth\Services\AuthService;
use App\Services\WhatsAppService;

class AuthController extends Controller
{
    public function __construct(protected AuthService $authService,protected WhatsAppService $whatsAppService)
    {
        $this->authService = $authService;
        $this->whatsAppService = $whatsAppService;
    }

    public function login(LoginRequest $request)
    {
        $result = $this->authService->login($request);

        if (!$result) {
            return $this->respondNotFound(null,'Invalid credentials');
        }

        return $this->respondOk($result, 'Logged in successfully');
    }

    public function logout()
    {
        $user = auth('sanctum')->user();

        if ($user->currentAccessToken()) {
            $this->authService->logout($user);
        } else {
            return $this->respondNotFound(null, 'No active session found.');
        }
        return $this->respondOk(null, 'Logged out successfully from the device.');
    }

}
