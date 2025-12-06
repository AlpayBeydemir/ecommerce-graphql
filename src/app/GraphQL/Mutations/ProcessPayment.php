<?php

namespace App\GraphQL\Mutations;

use App\Models\Order;
use App\Services\PaymentService;
use Illuminate\Support\Facades\Auth;
use GraphQL\Error\Error;

class ProcessPayment
{
    protected $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    public function __invoke($rootValue, array $args)
    {
        $user = Auth::user();

        if (!$user) {
            throw new Error('Unauthenticated');
        }

        // Find order and verify ownership
        $order = Order::where('id', $args['order_id'])
            ->where('user_id', $user->id)
            ->first();

        if (!$order) {
            throw new Error('Order not found or does not belong to you');
        }

        // Process payment
        $payment = $this->paymentService->processPayment(
            order: $order,
            paymentMethod: $args['payment_method']
        );

        return $payment;
    }
}
