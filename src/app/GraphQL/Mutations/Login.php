<?php

namespace App\GraphQL\Mutations;

use App\Models\User;
use App\Services\TokenService;
use Illuminate\Support\Facades\Hash;
use GraphQL\Error\Error;

class Login
{
    public function __construct(
        protected TokenService $tokenService
    ) {}

    public function __invoke($rootValue, array $args)
    {
        $user = User::where('email', $args['email'])->first();

        if (!$user || !Hash::check($args['password'], $user->password)) {
            return response()->json(['status' => false, 'error' => 'Invalid credentials'], 401);
        }

        $tokenData = $this->tokenService->createTokenForUser($user);

        if (!$tokenData) {
            return response()->json(['status' => false, 'error' => 'Invalid credentials'], 401);
        }

        return [
            'status' => true,
            'access_token' => $tokenData['access_token'],
            'token_type' => $tokenData['token_type'],
            'expires_in' => $tokenData['expires_in'],
            'refresh_token' => $tokenData['refresh_token'],
            'user' => $user,
        ];
    }
}
