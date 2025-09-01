<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\Order;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class NewOrderPlaced implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    public $order;

    public function __construct(Order $order)
    {
        $this->order = $order;

    }

    public function broadcastOn()
    {
        return new Channel('orders');
    }

    public function broadcastAs()
    {
        return 'NewOrderPlaced';
    }

    public function broadcastWith()
    {
        return [
            'order' => [
                'id' => $this->order->id,
                'customer_name' => $this->order->customer_name,
                'total_amount' => $this->order->total_amount,
                'status' => $this->order->status,
                'branch_id' => $this->order->branch_id,
                'branch_name' => $this->order->branch_name,
                'order_type' => $this->order->order_type
            ],
        ];
    }
    
}
