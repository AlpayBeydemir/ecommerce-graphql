<?php

namespace App\GraphQL\Mutations;

use App\Services\CheckoutService;
use Illuminate\Support\Facades\Auth;
use GraphQL\Error\Error;

class BuyNow
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

        $order = $this->checkoutService->processBuyNow(
            userId: $user->id,
            productId: $args['product_id'],
            quantity: $args['quantity'],
            addressId: $args['address_id'],
            notes: $args['notes'] ?? null
        );

        // TODO: Dispatch job to send order confirmation email
        // dispatch(new SendOrderConfirmation($order));

        return $order;
    }
}
