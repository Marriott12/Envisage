<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Auction;
use App\Models\AuctionBid;
use App\Models\AutoBid;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AuctionController extends Controller
{
    public function index(Request $request)
    {
        $auctions = Auction::with(['product', 'seller', 'highestBidder'])
            ->when($request->status, function ($q) use ($request) {
                $q->where('status', $request->status);
            })
            ->when($request->status === 'active', function ($q) {
                $q->active();
            })
            ->orderBy('ends_at', 'asc')
            ->paginate(20);

        return response()->json($auctions);
    }

    public function show($id)
    {
        $auction = Auction::with(['product', 'seller', 'bids.user'])
            ->findOrFail($id);

        // Increment view count
        $auction->increment('views_count');

        return response()->json($auction);
    }

    public function placeBid(Request $request, $id)
    {
        $request->validate([
            'bid_amount' => 'required|numeric|min:0',
        ]);

        $auction = Auction::findOrFail($id);

        if (!$auction->isActive()) {
            return response()->json(['message' => 'Auction is not active'], 400);
        }

        if ($auction->seller_id == Auth::id()) {
            return response()->json(['message' => 'You cannot bid on your own auction'], 400);
        }

        $minimumBid = $auction->minimum_bid;
        if ($request->bid_amount < $minimumBid) {
            return response()->json([
                'message' => "Bid must be at least $minimumBid",
            ], 400);
        }

        DB::beginTransaction();
        try {
            // Create bid
            $bid = AuctionBid::create([
                'auction_id' => $auction->id,
                'user_id' => Auth::id(),
                'bid_amount' => $request->bid_amount,
                'is_winning' => true,
            ]);

            // Update previous winning bid
            AuctionBid::where('auction_id', $auction->id)
                ->where('id', '!=', $bid->id)
                ->where('is_winning', true)
                ->update(['is_winning' => false, 'outbid' => true]);

            // Update auction
            $auction->update([
                'current_bid' => $request->bid_amount,
                'highest_bidder_id' => Auth::id(),
                'bid_count' => $auction->bid_count + 1,
            ]);

            // Process auto-bids
            $this->processAutoBids($auction);

            DB::commit();

            return response()->json([
                'message' => 'Bid placed successfully',
                'bid' => $bid,
                'auction' => $auction->fresh(),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to place bid'], 500);
        }
    }

    public function setAutoBid(Request $request, $id)
    {
        $request->validate([
            'max_bid' => 'required|numeric|min:0',
        ]);

        $auction = Auction::findOrFail($id);

        AutoBid::updateOrCreate(
            [
                'auction_id' => $auction->id,
                'user_id' => Auth::id(),
            ],
            [
                'max_bid' => $request->max_bid,
                'active' => true,
            ]
        );

        return response()->json(['message' => 'Auto-bid configured successfully']);
    }

    public function watch($id)
    {
        $auction = Auction::findOrFail($id);

        $auction->watchers()->syncWithoutDetaching([
            Auth::id() => [
                'notify_outbid' => true,
                'notify_ending_soon' => true,
            ]
        ]);

        $auction->increment('watchers_count');

        return response()->json(['message' => 'Now watching auction']);
    }

    public function unwatch($id)
    {
        $auction = Auction::findOrFail($id);
        $auction->watchers()->detach(Auth::id());
        $auction->decrement('watchers_count');

        return response()->json(['message' => 'Stopped watching auction']);
    }

    public function myBids()
    {
        $bids = AuctionBid::with(['auction.product'])
            ->where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($bids);
    }

    protected function processAutoBids($auction)
    {
        $autoBids = AutoBid::where('auction_id', $auction->id)
            ->where('active', true)
            ->where('user_id', '!=', $auction->highest_bidder_id)
            ->where('max_bid', '>', $auction->current_bid)
            ->orderBy('max_bid', 'desc')
            ->get();

        foreach ($autoBids as $autoBid) {
            $newBid = min($autoBid->max_bid, $auction->current_bid + $auction->bid_increment);
            
            if ($newBid > $auction->current_bid) {
                AuctionBid::create([
                    'auction_id' => $auction->id,
                    'user_id' => $autoBid->user_id,
                    'bid_amount' => $newBid,
                    'is_auto_bid' => true,
                    'max_auto_bid' => $autoBid->max_bid,
                    'is_winning' => true,
                ]);

                $auction->update([
                    'current_bid' => $newBid,
                    'highest_bidder_id' => $autoBid->user_id,
                ]);

                break;
            }
        }
    }
}
