<?php

namespace App\Listeners;

use App\Enums\OrderStatus;
use App\Events\OrderCreated;
use App\Models\Product;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReduceStock implements ShouldQueue
{
    public function handle(OrderCreated $event): void
    {
        Log::info('ReduceStock listener triggered', [
            'order_id' => $event->order->id
        ]);
        $order = $event->order;

        DB::transaction(function () use ($order) {
            foreach ($order->items as $item) {

                Log::info('Checking stock...', [
                    'product_id' => $item->product_id,
                    'quantity_needed' => $item->quantity
                ]);

                $product = Product::where('id', $item->product_id)->lockForUpdate()->first();

                if (!$product) {
                    Log::error('Product not found for stock update', ['product_id' => $item->product_id]);

                    $order->update(['status' => OrderStatus::FAILED->value]);
                    return;
                }

                if ($product->stock < $item->quantity) {
                    Log::error('Insufficient stock', [
                        'product_id' => $item->product_id,
                        'stock_available' => $product->stock,
                        'quantity_needed' => $item->quantity
                    ]);

                    $order->update(['status' => OrderStatus::FAILED->value]);
                    return;
                }

                Log::info('Stock deducted');
            }

            $order->update(['status' => OrderStatus::COMPLETED->value]);
            Log::info('Order completed successfully', ['order_id' => $order->id]);
        });
    }
}
