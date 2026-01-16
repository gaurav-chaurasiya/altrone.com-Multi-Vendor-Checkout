<?php

namespace App\Listeners;

use App\Events\OrderPlaced;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendOrderEmail
{
    public function handle(OrderPlaced $event): void
    {
        $order = $event->order;
        $user = $order->user;
        $vendor = $order->vendor;

        Log::info("MOCK EMAIL: Order Placement Notification", [
            'to' => $user->email,
            'order_id' => $order->id,
            'vendor' => $vendor->name,
            'amount' => $order->total_amount,
            'message' => "Hi {$user->name}, your order #{$order->id} from {$vendor->name} has been placed successfully."
        ]);
    }
}
