<?php

namespace App\GraphQL\Mutations;

use App\Services\TokenService;
use GraphQL\Error\Error;

class RefreshToken
{
    public function __construct(
        protected TokenService $tokenService
    ) {
    }

    public function __invoke($rootValue, array $args)
    {
        try {
            // Refresh the token using TokenService
            $tokenData = $this->tokenService->refreshToken($args['refresh_token']);

            return [
                'access_token' => $tokenData['access_token'],
                'token_type' => $tokenData['token_type'],
                'expires_in' => $tokenData['expires_in'],
                'refresh_token' => $tokenData['refresh_token'],
            ];
        } catch (\Exception $e) {
            // Rethrow GraphQL errors, wrap other exceptions
            if ($e instanceof Error) {
                throw $e;
            }
            throw new Error('Failed to refresh token: ' . $e->getMessage());
        }
    }
}
