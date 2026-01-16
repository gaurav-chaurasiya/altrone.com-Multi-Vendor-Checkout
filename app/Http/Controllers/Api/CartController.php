<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AddToCartRequest;
use App\Services\CartService;
use Illuminate\Http\Request;

class CartController extends Controller
{
    protected $cartService;

    public function __construct(CartService $cartService)
    {
        $this->cartService = $cartService;
    }

    public function index(Request $request)
    {
        $groupedCart = $this->cartService->getGroupedCart($request->user());
        return response()->json($groupedCart);
    }

    public function store(AddToCartRequest $request)
    {
        try {
            $cart = $this->cartService->addItem(
                $request->user(),
                $request->product_id,
                $request->quantity
            );
            return response()->json([
                'message' => 'Product added to cart.',
                'cart' => $cart
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function destroy(Request $request, $itemId)
    {
        $cart = $this->cartService->removeItem($request->user(), $itemId);
        return response()->json([
            'message' => 'Item removed from cart.',
            'cart' => $cart
        ]);
    }
}
