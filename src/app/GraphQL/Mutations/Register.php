<?php

namespace App\GraphQL\Mutations;

use App\Models\User;
use App\Services\TokenService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class Register
{
    public function __construct(
        protected TokenService $tokenService
    ) {
    }

    public function __invoke($rootValue, array $args)
    {
        $user = User::create([
            'name' => $args['name'],
            'email' => $args['email'],
            'password' => Hash::make($args['password']),
        ]);

        // Create tokens using TokenService
        $tokenData = $this->tokenService->createTokenForUser($user);

        return [
            'access_token' => $tokenData['access_token'],
            'token_type' => $tokenData['token_type'],
            'expires_in' => $tokenData['expires_in'],
            'refresh_token' => $tokenData['refresh_token'],
            'user' => $user,
        ];
    }
}
