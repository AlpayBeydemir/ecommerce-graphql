<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class OrderCreated
{
    use Dispatchable, SerializesModels;
    public Order $order;
    public function __construct(Order $order)
    {
        $this->order = $order;
        Log::info('OrderCreated event fired', [
            'order_id' => $order->id
        ]);
    }
}
