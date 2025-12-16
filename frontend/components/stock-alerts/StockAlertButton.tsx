import React, { useState } from 'react';
import { Bell, BellOff, Loader2 } from 'lucide-react';
import api from '@/lib/api';

interface StockAlertButtonProps {
  productId: number | string;
  inStock: boolean;
  className?: string;
}

export default function StockAlertButton({ 
  productId, 
  inStock,
  className = '' 
}: StockAlertButtonProps) {
  const [subscribed, setSubscribed] = useState(false);
  const [loading, setLoading] = useState(false);

  const handleToggle = async () => {
    setLoading(true);
    try {
      if (subscribed) {
        await api.delete(`/stock-alerts/${productId}`);
        setSubscribed(false);
      } else {
        await api.post(`/products/${productId}/stock-alert`);
        setSubscribed(true);
      }
    } catch (error) {
      console.error('Stock alert error:', error);
    } finally {
      setLoading(false);
    }
  };

  if (inStock) return null;

  return (
    <button
      onClick={handleToggle}
      disabled={loading}
      className={`flex items-center gap-2 px-4 py-2 border-2 border-primary-500 text-primary-600 rounded-lg hover:bg-primary-50 transition-colors disabled:opacity-50 ${className}`}
    >
      {loading ? (
        <Loader2 className="w-5 h-5 animate-spin" />
      ) : subscribed ? (
        <>
          <BellOff className="w-5 h-5" />
          <span>Alert Active</span>
        </>
      ) : (
        <>
          <Bell className="w-5 h-5" />
          <span>Notify When Available</span>
        </>
      )}
    </button>
  );
}
