<?php

namespace App\GraphQL\Queries;

use Illuminate\Support\Facades\Auth;
use GraphQL\Error\Error;

class MyOrders
{
    public function __invoke($rootValue, array $args)
    {
        $user = Auth::user();

        if (!$user) {
            throw new Error('Unauthenticated');
        }

        $query = $user->orders()->with(['items.product', 'address', 'payment']);

        // Filter by status if provided
        if (isset($args['status'])) {
            $query->where('status', strtolower($args['status']));
        }

        return $query->orderBy('created_at', 'desc')->get();
    }
}
