'use client';

import React, { useState, useEffect } from 'react';
import api from '@/lib/api';
import Link from 'next/link';

interface PriceAlert {
  id: number;
  product_id: number;
  target_price: number;
  is_active: boolean;
  is_triggered: boolean;
  triggered_at?: string;
  product: {
    id: number;
    name: string;
    price: number;
    images_urls?: string[];
  };
}

export default function PriceAlertBanner() {
  const [alerts, setAlerts] = useState<PriceAlert[]>([]);
  const [showBanner, setShowBanner] = useState(false);

  useEffect(() => {
    fetchPriceAlerts();
  }, []);

  const fetchPriceAlerts = async () => {
    try {
      const response = await api.get('/price-alerts');
      const triggeredAlerts = response.data.data.filter(
        (alert: PriceAlert) => alert.is_triggered && alert.is_active
      );
      setAlerts(triggeredAlerts);
      setShowBanner(triggeredAlerts.length > 0);
    } catch (error) {
      console.error('Failed to fetch price alerts:', error);
    }
  };

  const dismissAlert = async (alertId: number) => {
    try {
      await api.delete(`/price-alerts/${alertId}`);
      setAlerts(alerts.filter((alert) => alert.id !== alertId));
      if (alerts.length <= 1) {
        setShowBanner(false);
      }
    } catch (error) {
      console.error('Failed to dismiss alert:', error);
    }
  };

  if (!showBanner || alerts.length === 0) {
    return null;
  }

  return (
    <div className="bg-gradient-to-r from-green-500 to-green-600 text-white shadow-lg">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3">
        <div className="flex items-center justify-between">
          <div className="flex items-center gap-3">
            <svg
              className="w-6 h-6"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                strokeLinecap="round"
                strokeLinejoin="round"
                strokeWidth={2}
                d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"
              />
            </svg>
            <div>
              <p className="font-semibold">
                Price Drop Alert! {alerts.length} {alerts.length === 1 ? 'item' : 'items'} on your wishlist {alerts.length === 1 ? 'has' : 'have'} dropped in price
              </p>
              <div className="flex flex-wrap gap-4 mt-1">
                {alerts.slice(0, 3).map((alert) => (
                  <div key={alert.id} className="flex items-center gap-2 text-sm">
                    <Link
                      href={`/marketplace/${alert.product_id}`}
                      className="underline hover:no-underline"
                    >
                      {alert.product.name}
                    </Link>
                    <span className="bg-white text-green-600 px-2 py-0.5 rounded font-bold">
                      ${alert.product.price.toFixed(2)}
                    </span>
                    <button
                      onClick={() => dismissAlert(alert.id)}
                      className="text-white hover:text-gray-200 ml-1"
                      aria-label="Dismiss"
                    >
                      ✕
                    </button>
                  </div>
                ))}
                {alerts.length > 3 && (
                  <Link href="/wishlists" className="text-sm underline hover:no-underline">
                    +{alerts.length - 3} more
                  </Link>
                )}
              </div>
            </div>
          </div>
          <button
            onClick={() => setShowBanner(false)}
            className="text-white hover:text-gray-200 text-2xl font-bold"
            aria-label="Close banner"
          >
            ✕
          </button>
        </div>
      </div>
    </div>
  );
}
