<?php

namespace App\GraphQL\Queries;

use Illuminate\Support\Facades\Auth;
use GraphQL\Error\Error;

class Profile
{
    public function __invoke($rootValue, array $args)
    {
        $user = Auth::guard('api')->user();

        if (!$user) {
            throw new Error('Unauthenticated');
        }

        return $user;
    }
}
