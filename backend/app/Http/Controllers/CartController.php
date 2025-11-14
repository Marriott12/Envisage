<?php

namespace App\Http\Controllers;

use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function index()
    {
        $cartItems = CartItem::where('user_id', auth()->id())
            ->with('product.seller:id,name,email')
            ->get();

        $total = $cartItems->sum(function ($item) {
            return $item->product->price * $item->quantity;
        });

        return response()->json([
            'items' => $cartItems,
            'total' => $total,
            'item_count' => $cartItems->sum('quantity')
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1'
        ]);

        $product = Product::findOrFail($validated['product_id']);

        if ($product->stock < $validated['quantity']) {
            return response()->json(['error' => 'Insufficient stock available'], 400);
        }

        $cartItem = CartItem::updateOrCreate(
            ['user_id' => auth()->id(), 'product_id' => $validated['product_id']],
            ['quantity' => $validated['quantity']]
        );

        return response()->json($cartItem->load('product'), 201);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate(['quantity' => 'required|integer|min:1']);
        $cartItem = CartItem::where('user_id', auth()->id())->findOrFail($id);

        if ($cartItem->product->stock < $validated['quantity']) {
            return response()->json(['error' => 'Insufficient stock available'], 400);
        }

        $cartItem->update($validated);
        return response()->json($cartItem->load('product'));
    }

    public function destroy($id)
    {
        CartItem::where('user_id', auth()->id())->findOrFail($id)->delete();
        return response()->json(['message' => 'Item removed from cart']);
    }

    public function clear()
    {
        CartItem::where('user_id', auth()->id())->delete();
        return response()->json(['message' => 'Cart cleared']);
    }
}

