<?php

namespace App\GraphQL\Mutations;

use App\Models\Order;
use App\Services\CheckoutService;
use Illuminate\Support\Facades\Auth;
use GraphQL\Error\Error;

class CancelOrder
{
    protected $checkoutService;

    public function __construct(CheckoutService $checkoutService)
    {
        $this->checkoutService = $checkoutService;
    }

    public function __invoke($rootValue, array $args)
    {
        $user = Auth::user();

        if (!$user) {
            throw new Error('Unauthenticated');
        }

        $order = Order::where('order_number', $args['orderId'])
            ->where('user_id', $user->id)
            ->first();

        if (!$order) {
            throw new Error('Order not found or does not belong to you');
        }

        return $this->checkoutService->cancelOrder($order);
    }
}
