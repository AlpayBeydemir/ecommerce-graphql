<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Address;
use Illuminate\Support\Facades\DB;
use GraphQL\Error\Error;

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
    public function processBuyNow(int $userId, int $productId, int $quantity, int $addressId, ?string $notes = null): Order
    {
        return DB::transaction(function () use ($userId, $productId, $quantity, $addressId, $notes) {
            // 1. Validate product exists and is active
            $product = Product::where('id', $productId)
                ->where('is_active', true)
                ->lockForUpdate() // Lock for race condition prevention
                ->first();

            if (!$product) {
                throw new Error('Product not found or not available');
            }

            // 2. Check stock availability
            if ($product->stock_quantity < $quantity) {
                throw new Error('Insufficient stock. Available: ' . $product->stock_quantity);
            }

            // 3. Validate address belongs to user
            $address = Address::where('id', $addressId)
                ->where('user_id', $userId)
                ->first();

            if (!$address) {
                throw new Error('Address not found or does not belong to you');
            }

            // 4. Calculate pricing
            $price = $product->price;
            $subtotal = $price * $quantity;
            $tax = $subtotal * 0.18; // 18% KDV
            $total = $subtotal + $tax;

            // 5. Create order
            $order = Order::create([
                'user_id' => $userId,
                'address_id' => $addressId,
                'status' => 'pending',
                'subtotal' => $subtotal,
                'tax' => $tax,
                'total' => $total,
                'notes' => $notes,
            ]);

            // 6. Create order item (snapshot of product at purchase time)
            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'product_name' => $product->name,
                'price' => $price,
                'quantity' => $quantity,
                'subtotal' => $subtotal,
            ]);

            // 7. Reserve stock (decrement)
            $product->decrement('stock_quantity', $quantity);

            return $order->load(['items', 'address', 'user']);
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
            // Restore stock for all items
            foreach ($order->items as $item) {
                $product = Product::find($item->product_id);
                if ($product) {
                    $product->increment('stock_quantity', $item->quantity);
                }
            }

            // Update order status
            $order->update(['status' => 'cancelled']);

            // Cancel payment if exists
            if ($order->payment && $order->payment->status === 'completed') {
                $order->payment->update(['status' => 'refunded']);
            }

            return $order->fresh(['items', 'address', 'payment']);
        });
    }
}
