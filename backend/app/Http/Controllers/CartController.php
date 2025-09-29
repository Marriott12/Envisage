<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\CartItem;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function index($userId)
    {
        $cart = Cart::with('items.product')->where('user_id', $userId)->first();
        if (!$cart) {
            return response()->json(['message' => 'Cart not found'], 404);
        }
        return response()->json($cart);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);
        $cart = Cart::firstOrCreate(['user_id' => $validated['user_id']]);
        return response()->json($cart, 201);
    }

    public function addItem(Request $request, $cartId)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
        ]);
        $cart = Cart::find($cartId);
        if (!$cart) {
            return response()->json(['message' => 'Cart not found'], 404);
        }
        $item = $cart->items()->updateOrCreate(
            ['product_id' => $validated['product_id']],
            ['quantity' => $validated['quantity']]
        );
        return response()->json($item, 201);
    }

    public function removeItem($cartId, $itemId)
    {
        $cart = Cart::find($cartId);
        if (!$cart) {
            return response()->json(['message' => 'Cart not found'], 404);
        }
        $item = $cart->items()->find($itemId);
        if (!$item) {
            return response()->json(['message' => 'Item not found'], 404);
        }
        $item->delete();
        return response()->json(['message' => 'Item removed']);
    }

    public function clear($cartId)
    {
        $cart = Cart::find($cartId);
        if (!$cart) {
            return response()->json(['message' => 'Cart not found'], 404);
        }
        $cart->items()->delete();
        return response()->json(['message' => 'Cart cleared']);
    }
}
