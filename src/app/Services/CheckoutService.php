<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Events\OrderCreated;
use App\Models\Product;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use GraphQL\Error\Error;
use Illuminate\Support\Str;

class CheckoutService
{
    /**
     * Process a "Buy Now" checkout
     *
     * @param int $userId
     * @param int $productId
     * @param int $quantity
     * @param int $addressId
     * @param string|null $notes
     * @return Order
     * @throws Error
     */
    public function processBuyNow(
        int $userId,
        int $productId,
        int $quantity,
        int $addressId,
        ?string $notes
    ): Order {

        return DB::transaction(function () use (
            $userId,
            $productId,
            $quantity,
            $addressId,
            $notes
        ) {

            // Lock product and check stock
            $product = Product::where('id', $productId)
                ->lockForUpdate()
                ->first();

            if (!$product) {
                throw new Error('Product not found');
            }

            if ($product->stock_quantity < $quantity) {
                throw new Error(sprintf(
                    'Insufficient stock. Available: %d, Requested: %d',
                    $product->stock_quantity,
                    $quantity
                ));
            }

            $product->decrement('stock_quantity', $quantity);

            $subtotal = $product->price * $quantity;
            $tax = $subtotal * 0.18;
            $total = $subtotal + $tax;

            $order = Order::create([
                'order_number' => Str::uuid(),
                'user_id' => $userId,
                'address_id' => $addressId,
                'status' => OrderStatus::PROCESSING->value,
                'subtotal' => $subtotal,
                'tax' => $tax,
                'total' => $total,
                'notes' => $notes,
            ]);

            $order->items()->create([
                'product_id' => $product->id,
                'product_name' => $product->name,
                'price' => $product->price,
                'quantity' => $quantity,
                'subtotal' => $subtotal,
            ]);

            $order->update(['status' => OrderStatus::COMPLETED->value]);
            $order->load('items');
            OrderCreated::dispatch($order);

            return $order->fresh(['items', 'address']);
        });
    }

    /**
     * Cancel an order and restore stock
     *
     * @param Order $order
     * @return Order
     * @throws Error
     */
    public function cancelOrder(Order $order): Order
    {
        if (!in_array($order->status, ['pending', 'processing'])) {
            throw new Error('Cannot cancel order with status: ' . $order->status);
        }

        return DB::transaction(function () use ($order) {
            foreach ($order->items as $item) {
                $product = Product::find($item->product_id);
                if ($product) {
                    $product->increment('stock_quantity', $item->quantity);
                }
            }

            $order->update(['status' => 'cancelled']);

            if ($order->payment && $order->payment->status === 'completed') {
                $order->payment->update(['status' => 'refunded']);
            }

            return $order->fresh(['items', 'address', 'payment']);
        });
    }
}
