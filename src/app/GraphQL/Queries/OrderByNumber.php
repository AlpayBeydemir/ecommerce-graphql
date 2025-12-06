<?php

namespace App\GraphQL\Queries;

use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use GraphQL\Error\Error;

class OrderByNumber
{
    public function __invoke($rootValue, array $args)
    {
        $user = Auth::user();

        if (!$user) {
            throw new Error('Unauthenticated');
        }

        $order = Order::where('order_number', $args['orderNumber'])
            ->where('user_id', $user->id)
            ->with(['items.product', 'address', 'payment'])
            ->first();

        if (!$order) {
            throw new Error('Order not found');
        }

        return $order;
    }
}
