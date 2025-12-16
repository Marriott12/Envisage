import React, { useState, useEffect } from 'react';
import { Gavel, TrendingUp, Eye, Users, Loader2 } from 'lucide-react';
import CountdownTimer from '@/components/CountdownTimer';
import api from '@/lib/api';
import Image from 'next/image';

interface Auction {
  id: number;
  product: {
    id: number;
    name: string;
    image: string;
  };
  starting_bid: number;
  current_bid: number;
  buy_now_price?: number;
  bid_increment: number;
  minimum_bid: number;
  highest_bidder_id?: number;
  bid_count: number;
  ends_at: string;
  views_count: number;
  watchers_count: number;
}

interface AuctionCardProps {
  auction: Auction;
  onBidPlaced?: () => void;
}

export default function AuctionCard({ auction, onBidPlaced }: AuctionCardProps) {
  const [bidAmount, setBidAmount] = useState(auction.minimum_bid.toString());
  const [loading, setLoading] = useState(false);
  const [isWatching, setIsWatching] = useState(false);

  const handlePlaceBid = async () => {
    setLoading(true);
    try {
      await api.post(`/auctions/${auction.id}/bid`, {
        bid_amount: parseFloat(bidAmount),
      });
      if (onBidPlaced) onBidPlaced();
    } catch (error) {
      console.error('Bid failed:', error);
    } finally {
      setLoading(false);
    }
  };

  const handleWatch = async () => {
    try {
      if (isWatching) {
        await api.delete(`/auctions/${auction.id}/watch`);
      } else {
        await api.post(`/auctions/${auction.id}/watch`);
      }
      setIsWatching(!isWatching);
    } catch (error) {
      console.error('Watch toggle failed:', error);
    }
  };

  return (
    <div className="bg-white rounded-lg shadow-lg overflow-hidden border-2 border-yellow-400">
      {/* Auction Badge */}
      <div className="bg-gradient-to-r from-yellow-400 to-orange-500 text-white px-4 py-2 flex items-center gap-2">
        <Gavel className="w-5 h-5" />
        <span className="font-bold">Live Auction</span>
      </div>

      {/* Product Image */}
      <div className="relative aspect-square">
        <Image
          src={auction.product.image || '/placeholder.jpg'}
          alt={auction.product.name}
          fill
          className="object-cover"
        />
      </div>

      <div className="p-4">
        {/* Product Name */}
        <h3 className="font-bold text-lg mb-3 line-clamp-2">
          {auction.product.name}
        </h3>

        {/* Current Bid */}
        <div className="mb-4">
          <div className="flex items-baseline justify-between mb-1">
            <span className="text-sm text-gray-600">Current Bid:</span>
            <div className="flex items-center gap-1">
              <TrendingUp className="w-4 h-4 text-green-500" />
              <span className="text-2xl font-bold text-green-600">
                ${auction.current_bid.toFixed(2)}
              </span>
            </div>
          </div>
          <p className="text-xs text-gray-500">
            {auction.bid_count} bid{auction.bid_count !== 1 ? 's' : ''}
          </p>
        </div>

        {/* Stats */}
        <div className="flex items-center justify-between text-sm text-gray-600 mb-4 pb-4 border-b">
          <div className="flex items-center gap-1">
            <Eye className="w-4 h-4" />
            <span>{auction.views_count} views</span>
          </div>
          <div className="flex items-center gap-1">
            <Users className="w-4 h-4" />
            <span>{auction.watchers_count} watching</span>
          </div>
        </div>

        {/* Countdown */}
        <div className="mb-4">
          <p className="text-sm text-gray-600 mb-2">Time Remaining:</p>
          <CountdownTimer 
            endDate={auction.ends_at}
            showDays={true}
            className="justify-center"
          />
        </div>

        {/* Bidding */}
        <div className="space-y-2">
          <div className="flex gap-2">
            <input
              type="number"
              value={bidAmount}
              onChange={(e) => setBidAmount(e.target.value)}
              min={auction.minimum_bid}
              step={auction.bid_increment}
              className="flex-1 px-3 py-2 border rounded-lg"
              placeholder={`Min: $${auction.minimum_bid}`}
            />
            <button
              onClick={handlePlaceBid}
              disabled={loading}
              className="px-6 py-2 bg-green-600 text-white rounded-lg font-bold hover:bg-green-700 disabled:opacity-50 flex items-center gap-2"
            >
              {loading ? (
                <Loader2 className="w-5 h-5 animate-spin" />
              ) : (
                <Gavel className="w-5 h-5" />
              )}
              Bid
            </button>
          </div>

          {auction.buy_now_price && (
            <button className="w-full py-2 bg-primary-600 text-white rounded-lg font-bold hover:bg-primary-700">
              Buy Now - ${auction.buy_now_price.toFixed(2)}
            </button>
          )}

          <button
            onClick={handleWatch}
            className={`w-full py-2 rounded-lg font-semibold transition-colors ${
              isWatching
                ? 'bg-yellow-100 text-yellow-700 border-2 border-yellow-400'
                : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
            }`}
          >
            {isWatching ? 'Watching' : 'Watch Auction'}
          </button>
        </div>
      </div>
    </div>
  );
}
