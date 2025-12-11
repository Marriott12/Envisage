<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Offer;
use App\Models\Product;
use Illuminate\Http\Request;
use Carbon\Carbon;

class OfferController extends Controller
{
    /**
     * Create a new offer
     */
    public function create(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'offered_price' => 'required|numeric|min:0.01',
            'message' => 'nullable|string|max:500',
        ]);

        $product = Product::findOrFail($request->product_id);

        // Check if offer is below minimum (e.g., 50% of price)
        $minimumOffer = $product->price * 0.5;
        if ($request->offered_price < $minimumOffer) {
            return response()->json([
                'success' => false,
                'message' => 'Offer must be at least 50% of the listing price',
            ], 400);
        }

        $offer = Offer::create([
            'product_id' => $request->product_id,
            'buyer_id' => auth()->id(),
            'seller_id' => $product->seller_id,
            'offered_price' => $request->offered_price,
            'buyer_message' => $request->message,
            'status' => 'pending',
            'expires_at' => Carbon::now()->addDays(3),
        ]);

        // TODO: Send notification to seller

        return response()->json([
            'success' => true,
            'message' => 'Offer sent successfully',
            'data' => $offer->load('product', 'seller'),
        ], 201);
    }

    /**
     * Get sent offers
     */
    public function sentOffers()
    {
        $offers = Offer::with(['product', 'seller'])
                      ->where('buyer_id', auth()->id())
                      ->orderBy('created_at', 'desc')
                      ->get();

        return response()->json([
            'success' => true,
            'data' => $offers,
        ]);
    }

    /**
     * Get received offers (seller)
     */
    public function receivedOffers()
    {
        $offers = Offer::with(['product', 'buyer'])
                      ->where('seller_id', auth()->id())
                      ->orderBy('created_at', 'desc')
                      ->get();

        return response()->json([
            'success' => true,
            'data' => $offers,
        ]);
    }

    /**
     * Counter an offer (seller)
     */
    public function counter(Request $request, $id)
    {
        $request->validate([
            'counter_price' => 'required|numeric|min:0.01',
            'message' => 'nullable|string|max:500',
        ]);

        $offer = Offer::where('seller_id', auth()->id())
                     ->findOrFail($id);

        if (!$offer->isPending()) {
            return response()->json([
                'success' => false,
                'message' => 'This offer cannot be countered',
            ], 400);
        }

        $offer->update([
            'counter_price' => $request->counter_price,
            'seller_message' => $request->message,
            'status' => 'countered',
            'expires_at' => Carbon::now()->addDays(2),
        ]);

        // TODO: Send notification to buyer

        return response()->json([
            'success' => true,
            'message' => 'Counter offer sent',
            'data' => $offer,
        ]);
    }

    /**
     * Accept an offer
     */
    public function accept($id)
    {
        $offer = Offer::findOrFail($id);

        // Verify user is the seller
        if ($offer->seller_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        if (!$offer->isPending()) {
            return response()->json([
                'success' => false,
                'message' => 'This offer cannot be accepted',
            ], 400);
        }

        $offer->update(['status' => 'accepted']);

        // TODO: Create order with negotiated price
        // TODO: Send notifications

        return response()->json([
            'success' => true,
            'message' => 'Offer accepted',
            'data' => $offer,
        ]);
    }

    /**
     * Reject an offer
     */
    public function reject(Request $request, $id)
    {
        $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        $offer = Offer::findOrFail($id);

        // Verify user is the seller
        if ($offer->seller_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $offer->update([
            'status' => 'rejected',
            'seller_message' => $request->reason,
        ]);

        // TODO: Send notification to buyer

        return response()->json([
            'success' => true,
            'message' => 'Offer rejected',
        ]);
    }
}
