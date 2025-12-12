import { QueryClient, QueryClientProvider, QueryCache, MutationCache } from '@tanstack/react-query';
import { ReactQueryDevtools } from '@tanstack/react-query-devtools';
import React from 'react';

// Create query client with optimized defaults
export const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      // Stale time - data considered fresh for 5 minutes
      staleTime: 5 * 60 * 1000,
      
      // Cache time - unused data kept in cache for 10 minutes
      gcTime: 10 * 60 * 1000,
      
      // Retry configuration
      retry: (failureCount, error: any) => {
        // Don't retry on 4xx errors
        if (error?.response?.status >= 400 && error?.response?.status < 500) {
          return false;
        }
        return failureCount < 3;
      },
      retryDelay: (attemptIndex) => Math.min(1000 * 2 ** attemptIndex, 30000),
      
      // Refetch configuration
      refetchOnWindowFocus: true,
      refetchOnReconnect: true,
      refetchOnMount: true,
      
      // Network mode
      networkMode: 'offlineFirst',
    },
    mutations: {
      retry: 1,
      networkMode: 'offlineFirst',
    },
  },
  
  // Global error handler
  queryCache: new QueryCache({
    onError: (error: any, query) => {
      // Log error to monitoring service
      console.error('Query error:', error, query);
      
      // Send to backend error tracking
      if (process.env.NODE_ENV === 'production') {
        fetch(`${process.env.NEXT_PUBLIC_API_URL}/errors`, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            type: 'query_error',
            query: query.queryKey,
            error: error.message,
            stack: error.stack,
            timestamp: Date.now(),
          }),
        }).catch(() => {
          // Silently fail
        });
      }
    },
  }),
  
  // Global mutation error handler
  mutationCache: new MutationCache({
    onError: (error: any, _variables, _context, mutation) => {
      console.error('Mutation error:', error, mutation);
      
      // Send to backend error tracking
      if (process.env.NODE_ENV === 'production') {
        fetch(`${process.env.NEXT_PUBLIC_API_URL}/errors`, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            type: 'mutation_error',
            mutation: mutation.options.mutationKey,
            error: error.message,
            stack: error.stack,
            timestamp: Date.now(),
          }),
        }).catch(() => {
          // Silently fail
        });
      }
    },
  }),
});

// Query client provider wrapper
export const QueryProvider: React.FC<{ children: React.ReactNode }> = ({ children }) => {
  return (
    <QueryClientProvider client={queryClient}>
      {children}
      {process.env.NODE_ENV === 'development' && (
        <ReactQueryDevtools initialIsOpen={false} position="bottom" />
      )}
    </QueryClientProvider>
  );
};

// Prefetch utilities
export const prefetchQueries = {
  // Prefetch products
  products: async () => {
    await queryClient.prefetchQuery({
      queryKey: ['products'],
      queryFn: async () => {
        const response = await fetch(`${process.env.NEXT_PUBLIC_API_URL}/products`);
        return response.json();
      },
      staleTime: 10 * 60 * 1000, // 10 minutes
    });
  },

  // Prefetch product details
  productDetails: async (productId: string) => {
    await queryClient.prefetchQuery({
      queryKey: ['product', productId],
      queryFn: async () => {
        const response = await fetch(`${process.env.NEXT_PUBLIC_API_URL}/products/${productId}`);
        return response.json();
      },
      staleTime: 5 * 60 * 1000, // 5 minutes
    });
  },

  // Prefetch categories
  categories: async () => {
    await queryClient.prefetchQuery({
      queryKey: ['categories'],
      queryFn: async () => {
        const response = await fetch(`${process.env.NEXT_PUBLIC_API_URL}/categories`);
        return response.json();
      },
      staleTime: 30 * 60 * 1000, // 30 minutes
    });
  },

  // Prefetch user data
  user: async (userId: string) => {
    await queryClient.prefetchQuery({
      queryKey: ['user', userId],
      queryFn: async () => {
        const response = await fetch(`${process.env.NEXT_PUBLIC_API_URL}/users/${userId}`, {
          headers: {
            Authorization: `Bearer ${localStorage.getItem('auth_token')}`,
          },
        });
        return response.json();
      },
      staleTime: 5 * 60 * 1000, // 5 minutes
    });
  },

  // Prefetch cart
  cart: async () => {
    await queryClient.prefetchQuery({
      queryKey: ['cart'],
      queryFn: async () => {
        const response = await fetch(`${process.env.NEXT_PUBLIC_API_URL}/cart`, {
          headers: {
            Authorization: `Bearer ${localStorage.getItem('auth_token')}`,
          },
        });
        return response.json();
      },
      staleTime: 1 * 60 * 1000, // 1 minute
    });
  },
};

// Invalidate utilities
export const invalidateQueries = {
  // Invalidate all products
  products: () => queryClient.invalidateQueries({ queryKey: ['products'] }),

  // Invalidate specific product
  product: (productId: string) =>
    queryClient.invalidateQueries({ queryKey: ['product', productId] }),

  // Invalidate cart
  cart: () => queryClient.invalidateQueries({ queryKey: ['cart'] }),

  // Invalidate orders
  orders: () => queryClient.invalidateQueries({ queryKey: ['orders'] }),

  // Invalidate user data
  user: (userId: string) => queryClient.invalidateQueries({ queryKey: ['user', userId] }),
};

// Optimistic update utilities
export const optimisticUpdates = {
  // Optimistic cart update
  updateCart: async (
    updater: (oldCart: any) => any,
    onSuccess?: () => void,
    onError?: () => void
  ) => {
    // Cancel any outgoing refetches
    await queryClient.cancelQueries({ queryKey: ['cart'] });

    // Snapshot previous value
    const previousCart = queryClient.getQueryData(['cart']);

    // Optimistically update
    queryClient.setQueryData(['cart'], updater);

    // Return context with rollback function
    return {
      previousCart,
      rollback: () => {
        queryClient.setQueryData(['cart'], previousCart);
        onError?.();
      },
      confirm: () => {
        queryClient.invalidateQueries({ queryKey: ['cart'] });
        onSuccess?.();
      },
    };
  },

  // Optimistic wishlist update
  updateWishlist: async (updater: (oldWishlist: any) => any) => {
    await queryClient.cancelQueries({ queryKey: ['wishlist'] });
    const previousWishlist = queryClient.getQueryData(['wishlist']);
    queryClient.setQueryData(['wishlist'], updater);

    return {
      previousWishlist,
      rollback: () => queryClient.setQueryData(['wishlist'], previousWishlist),
      confirm: () => queryClient.invalidateQueries({ queryKey: ['wishlist'] }),
    };
  },
};

// Cache manipulation utilities
export const cacheUtils = {
  // Get cached data
  getCachedData: <T,>(queryKey: string[]) => {
    return queryClient.getQueryData<T>(queryKey);
  },

  // Set cached data
  setCachedData: <T,>(queryKey: string[], data: T) => {
    queryClient.setQueryData(queryKey, data);
  },

  // Remove from cache
  removeFromCache: (queryKey: string[]) => {
    queryClient.removeQueries({ queryKey });
  },

  // Clear all cache
  clearCache: () => {
    queryClient.clear();
  },

  // Get cache stats
  getCacheStats: () => {
    const cache = queryClient.getQueryCache();
    return {
      totalQueries: cache.getAll().length,
      activeQueries: cache.getAll().filter((q) => q.isActive()).length,
      staleQueries: cache.getAll().filter((q) => q.isStale()).length,
    };
  },
};
