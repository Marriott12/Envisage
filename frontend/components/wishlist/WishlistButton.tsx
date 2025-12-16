'use client';

import { useState, useEffect } from 'react';
import { Heart } from 'lucide-react';
import { useAuth } from '@/hooks/useAuth';
import api from '@/lib/api';

interface WishlistButtonProps {
  productId: string;
  size?: 'sm' | 'md' | 'lg';
  showLabel?: boolean;
}

export default function WishlistButton({ productId, size = 'md', showLabel = false }: WishlistButtonProps) {
  const { isAuthenticated } = useAuth();
  const [isInWishlist, setIsInWishlist] = useState(false);
  const [isLoading, setIsLoading] = useState(false);

  const sizeClasses = {
    sm: 'w-4 h-4',
    md: 'w-5 h-5',
    lg: 'w-6 h-6',
  };

  useEffect(() => {
    if (isAuthenticated) {
      checkWishlist();
    }
  }, [productId, isAuthenticated]);

  const checkWishlist = async () => {
    try {
      const { data } = await api.get(`/products/${productId}/wishlist-check`);
      setIsInWishlist(data.in_wishlist);
    } catch (error) {
      console.error('Failed to check wishlist:', error);
    }
  };

  const toggleWishlist = async (e: React.MouseEvent) => {
    e.preventDefault();
    e.stopPropagation();

    if (!isAuthenticated) {
      // Redirect to login or show login modal
      return;
    }

    setIsLoading(true);
    try {
      if (isInWishlist) {
        // Remove from wishlist - would need wishlist item ID
        // For now, we'll just toggle the state
        setIsInWishlist(false);
      } else {
        await api.post('/wishlists/quick-add', { product_id: productId });
        setIsInWishlist(true);
      }
    } catch (error) {
      console.error('Failed to toggle wishlist:', error);
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <button
      onClick={toggleWishlist}
      disabled={isLoading}
      className={`flex items-center gap-2 p-2 rounded-lg hover:bg-gray-100 transition-colors disabled:opacity-50 ${
        isInWishlist ? 'text-red-500' : 'text-gray-600'
      }`}
      title={isInWishlist ? 'Remove from wishlist' : 'Add to wishlist'}
    >
      <Heart
        className={`${sizeClasses[size]} ${isInWishlist ? 'fill-current' : ''}`}
      />
      {showLabel && (
        <span className="text-sm font-medium">
          {isInWishlist ? 'In Wishlist' : 'Add to Wishlist'}
        </span>
      )}
    </button>
  );
}
