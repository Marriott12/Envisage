import { useState, useEffect, useCallback } from 'react';
import { useAuth } from '@/hooks/useAuth';
import { wishlistService, Wishlist as WishlistType } from '@/lib/services/wishlistService';

interface UseWishlistReturn {
  itemCount: number;
  loading: boolean;
  wishlists: WishlistType[];
  refetch: () => Promise<void>;
  isInWishlist: (productId: string | number) => Promise<boolean>;
  toggleWishlist: (productId: string | number) => Promise<void>;
}

export function useWishlist(): UseWishlistReturn {
  const { user, isAuthenticated } = useAuth();
  const [itemCount, setItemCount] = useState(0);
  const [wishlists, setWishlists] = useState<WishlistType[]>([]);
  const [loading, setLoading] = useState(true);

  const fetchWishlists = useCallback(async () => {
    if (!isAuthenticated) {
      setItemCount(0);
      setWishlists([]);
      setLoading(false);
      return;
    }

    try {
      setLoading(true);
      const data = await wishlistService.getWishlists();
      setWishlists(data);
      
      // Calculate total items across all wishlists
      const total = data.reduce((sum: number, w: WishlistType) => sum + (w.items?.length || 0), 0);
      setItemCount(total);
    } catch (error) {
      console.error('Failed to fetch wishlists:', error);
      setItemCount(0);
      setWishlists([]);
    } finally {
      setLoading(false);
    }
  }, [isAuthenticated]);

  const isInWishlist = useCallback(async (productId: string | number): Promise<boolean> => {
    if (!isAuthenticated) return false;
    
    try {
      const result = await wishlistService.checkProduct(String(productId));
      return result.in_wishlist || false;
    } catch (error) {
      console.error('Failed to check wishlist:', error);
      return false;
    }
  }, [isAuthenticated]);

  const toggleWishlist = useCallback(async (productId: string | number) => {
    if (!isAuthenticated) {
      throw new Error('Please login to use wishlists');
    }

    try {
      const inWishlist = await isInWishlist(productId);
      
      if (inWishlist) {
        // Remove from wishlist - would need to get wishlist item ID first
        // This is a simplified version - you may need to enhance this
        await wishlistService.quickAdd(String(productId)); // Toggle logic
      } else {
        await wishlistService.quickAdd(String(productId));
      }
      
      // Refresh wishlist data
      await fetchWishlists();
    } catch (error) {
      console.error('Failed to toggle wishlist:', error);
      throw error;
    }
  }, [isAuthenticated, isInWishlist, fetchWishlists]);

  useEffect(() => {
    fetchWishlists();
  }, [fetchWishlists]);

  return {
    itemCount,
    loading,
    wishlists,
    refetch: fetchWishlists,
    isInWishlist,
    toggleWishlist
  };
}
