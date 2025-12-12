'use client';

import { useEffect, useState } from 'react';
import { ShoppingBag, MapPin, Clock, X } from 'lucide-react';
import Image from 'next/image';
import { useRealtimeSocialProof } from '@/hooks/useRealtime';

interface Purchase {
  id: string;
  userName: string;
  location: string;
  productName: string;
  productImage?: string;
  timestamp: number;
}

interface SocialProofNotificationsProps {
  productId?: string;
  maxNotifications?: number;
  displayDuration?: number; // ms
  position?: 'top-left' | 'top-right' | 'bottom-left' | 'bottom-right';
  className?: string;
}

export function SocialProofNotifications({
  productId,
  maxNotifications = 3,
  displayDuration = 5000,
  position = 'bottom-left',
  className = '',
}: SocialProofNotificationsProps) {
  const { recentPurchases } = useRealtimeSocialProof();
  const [visibleNotifications, setVisibleNotifications] = useState<Purchase[]>([]);
  const [dismissedIds, setDismissedIds] = useState<Set<string>>(new Set());

  useEffect(() => {
    if (recentPurchases.length === 0) return;

    // Filter purchases and limit display
    const newPurchases = recentPurchases
      .filter((p) => !dismissedIds.has(p.id))
      .filter((p) => !productId || p.productName.includes(productId))
      .slice(0, maxNotifications);

    setVisibleNotifications(newPurchases);

    // Auto-dismiss after duration
    const timers = newPurchases.map((purchase) =>
      setTimeout(() => {
        handleDismiss(purchase.id);
      }, displayDuration)
    );

    return () => timers.forEach(clearTimeout);
  }, [recentPurchases, productId, maxNotifications, displayDuration, dismissedIds]);

  const handleDismiss = (id: string) => {
    setDismissedIds((prev) => new Set(prev).add(id));
    setVisibleNotifications((prev) => prev.filter((p) => p.id !== id));
  };

  const getTimeAgo = (timestamp: number) => {
    const seconds = Math.floor((Date.now() - timestamp) / 1000);
    if (seconds < 60) return 'just now';
    if (seconds < 3600) return `${Math.floor(seconds / 60)}m ago`;
    if (seconds < 86400) return `${Math.floor(seconds / 3600)}h ago`;
    return `${Math.floor(seconds / 86400)}d ago`;
  };

  const positionClasses = {
    'top-left': 'top-4 left-4',
    'top-right': 'top-4 right-4',
    'bottom-left': 'bottom-4 left-4',
    'bottom-right': 'bottom-4 right-4',
  };

  if (visibleNotifications.length === 0) return null;

  return (
    <div
      className={`fixed ${positionClasses[position]} z-50 space-y-2 w-80 max-w-[calc(100vw-2rem)] ${className}`}
    >
      {visibleNotifications.map((purchase, index) => (
        <div
          key={purchase.id}
          className="bg-white rounded-lg shadow-lg border border-gray-200 p-4 animate-slide-in-left"
          style={{ animationDelay: `${index * 100}ms` }}
        >
          <div className="flex items-start gap-3">
            {/* Product Image */}
            {purchase.productImage && (
              <div className="relative w-12 h-12 flex-shrink-0">
                <Image
                  src={purchase.productImage}
                  alt={purchase.productName}
                  fill
                  className="object-cover rounded"
                />
              </div>
            )}

            {/* Content */}
            <div className="flex-1 min-w-0">
              <div className="flex items-start justify-between gap-2">
                <div className="flex items-center gap-2 text-green-600 mb-1">
                  <ShoppingBag className="w-4 h-4" />
                  <span className="text-xs font-semibold">Recent Purchase</span>
                </div>
                <button
                  onClick={() => handleDismiss(purchase.id)}
                  className="text-gray-400 hover:text-gray-600"
                  aria-label="Dismiss"
                >
                  <X className="w-4 h-4" />
                </button>
              </div>

              <p className="text-sm text-gray-900 font-medium mb-1 line-clamp-1">
                {purchase.userName} purchased
              </p>
              <p className="text-sm text-gray-600 mb-2 line-clamp-1">
                {purchase.productName}
              </p>

              <div className="flex items-center gap-3 text-xs text-gray-500">
                <span className="flex items-center gap-1">
                  <MapPin className="w-3 h-3" />
                  {purchase.location}
                </span>
                <span className="flex items-center gap-1">
                  <Clock className="w-3 h-3" />
                  {getTimeAgo(purchase.timestamp)}
                </span>
              </div>
            </div>
          </div>
        </div>
      ))}
    </div>
  );
}

// Live counter variant
export function LivePurchaseCounter({
  productId,
  timeframe = '24h',
  className = '',
}: {
  productId: string;
  timeframe?: '1h' | '24h' | '7d';
  className?: string;
}) {
  const [count, setCount] = useState(0);
  const [isAnimating, setIsAnimating] = useState(false);

  useEffect(() => {
    // Fetch initial count
    const fetchCount = async () => {
      try {
        const response = await fetch(
          `/api/products/${productId}/purchase-count?timeframe=${timeframe}`
        );
        const data = await response.json();
        setCount(data.count || 0);
      } catch (error) {
        console.error('Failed to fetch purchase count:', error);
      }
    };

    fetchCount();

    // Simulate real-time updates (in production, use WebSocket/Pusher)
    const interval = setInterval(() => {
      setIsAnimating(true);
      setCount((prev) => prev + Math.floor(Math.random() * 3));
      setTimeout(() => setIsAnimating(false), 500);
    }, 30000); // Every 30 seconds

    return () => clearInterval(interval);
  }, [productId, timeframe]);

  const timeframeLabels = {
    '1h': 'in the last hour',
    '24h': 'in the last 24 hours',
    '7d': 'this week',
  };

  return (
    <div className={`inline-flex items-center gap-2 px-3 py-2 bg-green-50 border border-green-200 rounded-lg ${className}`}>
      <ShoppingBag className="w-4 h-4 text-green-600" />
      <p className="text-sm text-green-900">
        <span className={`font-bold ${isAnimating ? 'animate-pulse' : ''}`}>
          {count}
        </span>{' '}
        purchased {timeframeLabels[timeframe]}
      </p>
    </div>
  );
}

// Viewing counter
export function LiveViewingCounter({
  productId,
  className = '',
}: {
  productId: string;
  className?: string;
}) {
  const [viewers, setViewers] = useState(0);

  useEffect(() => {
    // In production, use Pusher presence channel
    const simulateViewers = () => {
      setViewers(Math.floor(Math.random() * 20) + 5);
    };

    simulateViewers();
    const interval = setInterval(simulateViewers, 10000);

    return () => clearInterval(interval);
  }, [productId]);

  if (viewers === 0) return null;

  return (
    <div className={`inline-flex items-center gap-2 px-3 py-1.5 bg-blue-50 text-blue-700 rounded-full text-sm ${className}`}>
      <span className="relative flex h-2 w-2">
        <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-400 opacity-75"></span>
        <span className="relative inline-flex rounded-full h-2 w-2 bg-blue-500"></span>
      </span>
      <span className="font-medium">{viewers} people viewing</span>
    </div>
  );
}

// Stock urgency indicator
export function StockUrgencyBadge({
  stockLevel,
  threshold = 10,
  className = '',
}: {
  stockLevel: number;
  threshold?: number;
  className?: string;
}) {
  if (stockLevel > threshold) return null;

  const getUrgencyLevel = () => {
    if (stockLevel <= 3) return { color: 'red', text: 'Only a few left!' };
    if (stockLevel <= threshold / 2) return { color: 'orange', text: `Only ${stockLevel} left!` };
    return { color: 'yellow', text: `Only ${stockLevel} left in stock` };
  };

  const urgency = getUrgencyLevel();
  const colorClasses: Record<string, string> = {
    red: 'bg-red-50 border-red-200 text-red-700',
    orange: 'bg-orange-50 border-orange-200 text-orange-700',
    yellow: 'bg-yellow-50 border-yellow-200 text-yellow-700',
  };

  return (
    <div className={`inline-flex items-center gap-2 px-3 py-1.5 border rounded-lg text-sm font-medium ${colorClasses[urgency.color]} ${className}`}>
      <span className="relative flex h-2 w-2">
        <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-current opacity-75"></span>
        <span className="relative inline-flex rounded-full h-2 w-2 bg-current"></span>
      </span>
      {urgency.text}
    </div>
  );
}
