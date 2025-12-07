<?php

namespace App\GraphQL\Mutations;

use App\Models\User;
use App\Services\TokenService;
use Illuminate\Support\Facades\Hash;

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

        return response()->json([
            'status' => true,
            'data' => $user,
            'message' => 'The user was created successfully'
        ]);
    }
}
