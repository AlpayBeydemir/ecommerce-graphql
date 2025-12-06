<?php

namespace App\GraphQL\Mutations;

use App\Services\TokenService;
use Illuminate\Support\Facades\Auth;

class Logout
{
    public function __construct(
        protected TokenService $tokenService
    ) {
    }

    public function __invoke($rootValue, array $args)
    {
        $user = Auth::user();

        if ($user && $user->token()) {
            // Revoke both access token and its refresh token
            $this->tokenService->revokeToken($user->token());
        }

        return [
            'message' => 'Successfully logged out',
            'status' => 'success',
        ];
    }
}
