'use client';

import { useEffect } from 'react';
import api from '@/lib/api';

interface TrackViewProps {
  productId: string;
}

export default function TrackView({ productId }: TrackViewProps) {
  useEffect(() => {
    // Track view after a short delay to ensure it's a genuine view
    const timer = setTimeout(() => {
      trackProductView();
    }, 2000);

    return () => clearTimeout(timer);
  }, [productId]);

  const trackProductView = async () => {
    try {
      await api.post(`/products/${productId}/track-view`);
    } catch (error) {
      console.error('Failed to track product view:', error);
    }
  };

  return null; // This component doesn't render anything
}
