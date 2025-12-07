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
        $order = $event->order->fresh();

        Log::info('ReduceStock listener triggered', [
            'order_id' => $order->id,
            'order_status' => $order->status,
            'items_count' => $order->items->count()
        ]);

        if (in_array($order->status, [
            OrderStatus::COMPLETED->value,
            OrderStatus::FAILED->value,
            OrderStatus::CANCELLED->value
        ], true)) {
            Log::info('Order already processed, skipping stock reduction', [
                'order_id' => $order->id,
                'status' => $order->status
            ]);
            return;
        }

        Log::info('Order stock already handled by CheckoutService', [
            'order_id' => $order->id,
            'status' => $order->status
        ]);
    }
}
