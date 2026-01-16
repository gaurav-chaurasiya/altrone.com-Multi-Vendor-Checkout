<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CartService
{
    public function getCartForUser(User $user)
    {
        return Cart::firstOrCreate(['user_id' => $user->id])->load('items.product.vendor');
    }

    public function addItem(User $user, int $productId, int $quantity)
    {
        $product = Product::findOrFail($productId);

        if ($product->stock < $quantity) {
            throw new \Exception("Insufficient stock for product: {$product->name}");
        }

        $cart = $this->getCartForUser($user);

        $cartItem = $cart->items()->where('product_id', $productId)->first();

        if ($cartItem) {
            $newQuantity = $cartItem->quantity + $quantity;
            if ($product->stock < $newQuantity) {
                throw new \Exception("Insufficient stock for product: {$product->name}");
            }
            $cartItem->update(['quantity' => $newQuantity]);
        } else {
            $cart->items()->create([
                'product_id' => $productId,
                'quantity' => $quantity
            ]);
        }

        return $cart->load('items.product.vendor');
    }

    public function removeItem(User $user, int $cartItemId)
    {
        $cart = $this->getCartForUser($user);
        $cart->items()->where('id', $cartItemId)->delete();
        return $cart->load('items.product.vendor');
    }

    public function getGroupedCart(User $user)
    {
        $cart = $this->getCartForUser($user);
        
        return $cart->items->groupBy(function($item) {
            return $item->product->vendor_id;
        })->map(function($items, $vendorId) {
            $vendor = $items->first()->product->vendor;
            return [
                'vendor_name' => $vendor->name,
                'items' => $items,
                'vendor_total' => $items->sum(function($item) {
                    return $item->quantity * $item->product->price;
                })
            ];
        });
    }

    public function clearCart(User $user)
    {
        $cart = $this->getCartForUser($user);
        $cart->items()->delete();
    }
}
