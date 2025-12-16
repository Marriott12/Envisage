'use client';

import { useState, useEffect } from 'react';
import { Zap, CreditCard, MapPin, Check } from 'lucide-react';
import api from '@/lib/api';
import { useAuth } from '@/hooks/useAuth';

interface ExpressCheckoutButtonProps {
  productId?: string;
  onCheckout: () => Promise<void>;
  className?: string;
}

export default function ExpressCheckoutButton({
  productId,
  onCheckout,
  className = '',
}: ExpressCheckoutButtonProps) {
  const { isAuthenticated } = useAuth();
  const [isConfigured, setIsConfigured] = useState(false);
  const [isLoading, setIsLoading] = useState(false);

  useEffect(() => {
    if (isAuthenticated) {
      checkConfiguration();
    }
  }, [isAuthenticated]);

  const checkConfiguration = async () => {
    try {
      const { data } = await api.get('/express-checkout/preferences');
      setIsConfigured(data.enabled && data.default_payment_method_id && data.default_shipping_address_id);
    } catch (error) {
      console.error('Failed to check express checkout:', error);
    }
  };

  const handleExpressCheckout = async () => {
    setIsLoading(true);
    try {
      await onCheckout();
    } catch (error) {
      console.error('Express checkout failed:', error);
    } finally {
      setIsLoading(false);
    }
  };

  if (!isAuthenticated || !isConfigured) {
    return null;
  }

  return (
    <button
      onClick={handleExpressCheckout}
      disabled={isLoading}
      className={`flex items-center justify-center gap-2 w-full bg-gradient-to-r from-indigo-600 to-purple-600 text-white py-3 px-6 rounded-lg font-semibold hover:from-indigo-700 hover:to-purple-700 disabled:opacity-50 transition-all transform hover:scale-105 ${className}`}
    >
      <Zap className="w-5 h-5" />
      {isLoading ? 'Processing...' : 'Express Checkout'}
    </button>
  );
}
