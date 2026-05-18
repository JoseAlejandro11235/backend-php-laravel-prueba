<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\Auth\LoginResource;
use App\Http\Resources\MessageResource;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\Request;

#[Group('Authentication')]
class AuthController extends Controller
{
    public function __construct(
        protected AuthService $authService
    ) {}

    public function login(LoginRequest $request): LoginResource
    {
        $result = $this->authService->login(
            $request->validated('email'),
            $request->validated('password')
        );

        return new LoginResource($result);
    }

    public function me(Request $request): UserResource
    {
        return new UserResource($request->user());
    }

    public function logout(Request $request): MessageResource
    {
        $this->authService->logout($request->user());

        return new MessageResource(['ok' => true]);
    }
}
