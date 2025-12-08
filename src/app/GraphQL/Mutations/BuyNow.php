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

    /**
     * @throws Error
     */
    public function __invoke($rootValue, array $args)
    {
        $user = Auth::user();

        if (!$user) {
            throw new Error('Unauthenticated');
        }

        return $this->checkoutService->processBuyNow(
            userId: $user->id,
            productId: $args['product_id'],
            quantity: $args['quantity'],
            addressId: $args['address_id'],
            paymentMethod: $args['payment_method'],
            notes: $args['notes'] ?? null,
        );
    }
}
