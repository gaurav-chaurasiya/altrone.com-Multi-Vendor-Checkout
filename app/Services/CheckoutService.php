<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use App\Events\OrderPlaced;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CheckoutService
{
    protected $cartService;

    public function __construct(CartService $cartService)
    {
        $this->cartService = $cartService;
    }

    public function processCheckout(User $user)
    {
        $cart = $this->cartService->getCartForUser($user);
        
        if ($cart->items->isEmpty()) {
            throw new \Exception("Cart is empty.");
        }

        return DB::transaction(function () use ($user, $cart) {
            $groupedItems = $cart->items->groupBy(function($item) {
                return $item->product->vendor_id;
            });

            $orders = [];

            foreach ($groupedItems as $vendorId => $items) {
                $totalAmount = 0;
                
                // Create Order
                $order = Order::create([
                    'user_id' => $user->id,
                    'vendor_id' => $vendorId,
                    'status' => 'paid',
                    'total_amount' => 0 // Will update after calculating items
                ]);

                foreach ($items as $item) {
                    // Row locking for stock update
                    $product = Product::where('id', $item->product_id)->lockForUpdate()->first();

                    if ($product->stock < $item->quantity) {
                        throw new \Exception("Insufficient stock for product: {$product->name}");
                    }

                    // Deduct stock
                    $product->decrement('stock', $item->quantity);

                    // Create Order Item
                    $order->items()->create([
                        'product_id' => $product->id,
                        'price' => $product->price,
                        'quantity' => $item->quantity
                    ]);

                    $totalAmount += ($product->price * $item->quantity);
                }

                $order->update(['total_amount' => $totalAmount]);

                // Create Payment
                Payment::create([
                    'order_id' => $order->id,
                    'status' => 'paid',
                    'payment_reference' => 'PAY-' . strtoupper(Str::random(10))
                ]);

                event(new OrderPlaced($order));
                
                $orders[] = $order->load('items.product');
            }

            // Clear Cart
            $this->cartService->clearCart($user);

            return $orders;
        });
    }
}
