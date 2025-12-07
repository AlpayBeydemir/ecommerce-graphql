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

            $product = Product::findOrFail($productId);

            $subtotal = $product->price * $quantity;
            $tax = $subtotal * 0.20; // örnek KDV
            $total = $subtotal + $tax;

            $order = Order::create([
                'order_number' => Str::uuid(),
                'user_id' => $userId,
                'address_id' => $addressId,
                'status' => OrderStatus::PENDING->value,
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

            OrderCreated::dispatch($order);
            return $order;
        });
    }

    /**
     * Payment success callback
     * Uygulamada webhook / event consumer çalıştırır
     */
    public function finalizePayment(Order $order): void
    {
        DB::transaction(function () use ($order) {
            $order->update([
                'status' => 'paid'
            ]);

            event(new \App\Events\OrderPaid($order));
        });
    }

    /**
     * Payment failure compensation
     */
    public function rollbackStockAndFail(Order $order): void
    {
        DB::transaction(function () use ($order) {

            foreach ($order->items as $item) {
                Product::where('id', $item->product_id)
                    ->increment('stock_quantity', $item->quantity);
            }

            $order->update([
                'status' => 'payment_failed'
            ]);

            event(new \App\Events\OrderPaymentFailed($order));
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
