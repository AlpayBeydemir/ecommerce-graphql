<?php

namespace App\GraphQL\Queries;

use Illuminate\Support\Facades\Auth;
use GraphQL\Error\Error;

class MyAddresses
{
    public function __invoke($rootValue, array $args)
    {
        $user = Auth::user();

        if (!$user) {
            throw new Error('Unauthenticated');
        }

        return $user->addresses()->orderBy('is_default', 'desc')->orderBy('created_at', 'desc')->get();
    }
}
