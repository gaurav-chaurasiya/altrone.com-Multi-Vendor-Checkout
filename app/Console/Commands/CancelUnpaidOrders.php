<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CancelUnpaidOrders extends Command
{
    protected $signature = 'orders:cancel-unpaid {minutes=30}';
    protected $description = 'Cancel unpaid orders and restore stock levels';

    public function handle()
    {
        $minutes = $this->argument('minutes');
        $expiryTime = now()->subMinutes($minutes);

        $orders = Order::where('status', 'pending')
            ->where('created_at', '<', $expiryTime)
            ->get();

        if ($orders->isEmpty()) {
            $this->info("No unpaid orders to cancel.");
            return;
        }

        foreach ($orders as $order) {
            DB::transaction(function () use ($order) {
                // Restore stock
                foreach ($order->items as $item) {
                    $product = Product::find($item->product_id);
                    if ($product) {
                        $product->increment('stock', $item->quantity);
                    }
                }

                $order->update(['status' => 'cancelled']);
                $this->info("Cancelled order #{$order->id}");
            });
        }

        $this->info("Cancelled {$orders->count()} orders.");
    }
}
