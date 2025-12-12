import { useEffect, useCallback, useRef } from 'react';
import { create } from 'zustand';
import { persist } from 'zustand/middleware';

// Behavioral tracking store
interface BehavioralData {
  sessionId: string;
  recentlyViewed: Array<{ id: string; timestamp: number }>;
  searchHistory: Array<{ query: string; timestamp: number; results: number }>;
  clickPatterns: Array<{ element: string; timestamp: number; page: string }>;
  scrollDepth: Record<string, number>;
  timeOnPage: Record<string, number>;
  hoverPatterns: Array<{ element: string; duration: number; timestamp: number }>;
  cartBehavior: {
    itemsAdded: number;
    itemsRemoved: number;
    abandonedCarts: number;
  };
  preferences: {
    favoriteCategories: string[];
    priceRange: { min: number; max: number };
    preferredBrands: string[];
  };
}

interface BehavioralStore extends BehavioralData {
  trackView: (productId: string) => void;
  trackSearch: (query: string, results: number) => void;
  trackClick: (element: string, page: string) => void;
  trackScroll: (page: string, depth: number) => void;
  trackTimeOnPage: (page: string, duration: number) => void;
  trackHover: (element: string, duration: number) => void;
  trackCartAction: (action: 'add' | 'remove' | 'abandon') => void;
  updatePreferences: (prefs: Partial<BehavioralData['preferences']>) => void;
  getRecentViews: (limit?: number) => string[];
  getSearchHistory: (limit?: number) => string[];
  clearHistory: () => void;
}

export const useBehavioralStore = create<BehavioralStore>()(
  persist(
    (set, get) => ({
      sessionId: typeof window !== 'undefined' ? `session_${Date.now()}_${Math.random()}` : '',
      recentlyViewed: [],
      searchHistory: [],
      clickPatterns: [],
      scrollDepth: {},
      timeOnPage: {},
      hoverPatterns: [],
      cartBehavior: {
        itemsAdded: 0,
        itemsRemoved: 0,
        abandonedCarts: 0,
      },
      preferences: {
        favoriteCategories: [],
        priceRange: { min: 0, max: 10000 },
        preferredBrands: [],
      },

      trackView: (productId: string) => {
        set((state) => {
          const filtered = state.recentlyViewed.filter((v) => v.id !== productId);
          return {
            recentlyViewed: [
              { id: productId, timestamp: Date.now() },
              ...filtered,
            ].slice(0, 50), // Keep last 50
          };
        });
      },

      trackSearch: (query: string, results: number) => {
        set((state) => ({
          searchHistory: [
            { query, timestamp: Date.now(), results },
            ...state.searchHistory,
          ].slice(0, 100), // Keep last 100
        }));
      },

      trackClick: (element: string, page: string) => {
        set((state) => ({
          clickPatterns: [
            { element, timestamp: Date.now(), page },
            ...state.clickPatterns,
          ].slice(0, 200), // Keep last 200
        }));
      },

      trackScroll: (page: string, depth: number) => {
        set((state) => ({
          scrollDepth: {
            ...state.scrollDepth,
            [page]: Math.max(state.scrollDepth[page] || 0, depth),
          },
        }));
      },

      trackTimeOnPage: (page: string, duration: number) => {
        set((state) => ({
          timeOnPage: {
            ...state.timeOnPage,
            [page]: (state.timeOnPage[page] || 0) + duration,
          },
        }));
      },

      trackHover: (element: string, duration: number) => {
        set((state) => ({
          hoverPatterns: [
            { element, duration, timestamp: Date.now() },
            ...state.hoverPatterns,
          ].slice(0, 100), // Keep last 100
        }));
      },

      trackCartAction: (action: 'add' | 'remove' | 'abandon') => {
        set((state) => {
          const behavior = { ...state.cartBehavior };
          if (action === 'add') behavior.itemsAdded += 1;
          else if (action === 'remove') behavior.itemsRemoved += 1;
          else if (action === 'abandon') behavior.abandonedCarts += 1;
          return { cartBehavior: behavior };
        });
      },

      updatePreferences: (prefs) => {
        set((state) => ({
          preferences: { ...state.preferences, ...prefs },
        }));
      },

      getRecentViews: (limit = 10) => {
        return get()
          .recentlyViewed.slice(0, limit)
          .map((v) => v.id);
      },

      getSearchHistory: (limit = 10) => {
        return get()
          .searchHistory.slice(0, limit)
          .map((s) => s.query);
      },

      clearHistory: () => {
        set({
          recentlyViewed: [],
          searchHistory: [],
          clickPatterns: [],
          scrollDepth: {},
          timeOnPage: {},
          hoverPatterns: [],
        });
      },
    }),
    {
      name: 'behavioral-store',
      version: 1,
    }
  )
);

// Hook for tracking page views
export const usePageTracking = (pageName: string) => {
  const { trackTimeOnPage } = useBehavioralStore();
  const startTimeRef = useRef<number>(Date.now());

  useEffect(() => {
    startTimeRef.current = Date.now();

    return () => {
      const duration = Date.now() - startTimeRef.current;
      trackTimeOnPage(pageName, duration);
    };
  }, [pageName, trackTimeOnPage]);
};

// Hook for tracking scroll depth
export const useScrollTracking = (pageName: string) => {
  const { trackScroll } = useBehavioralStore();

  useEffect(() => {
    let maxScroll = 0;

    const handleScroll = () => {
      const scrollHeight = document.documentElement.scrollHeight - window.innerHeight;
      const scrollDepth = (window.scrollY / scrollHeight) * 100;
      maxScroll = Math.max(maxScroll, scrollDepth);
    };

    const handleUnload = () => {
      trackScroll(pageName, maxScroll);
    };

    window.addEventListener('scroll', handleScroll, { passive: true });
    window.addEventListener('beforeunload', handleUnload);

    return () => {
      window.removeEventListener('scroll', handleScroll);
      window.removeEventListener('beforeunload', handleUnload);
      trackScroll(pageName, maxScroll);
    };
  }, [pageName, trackScroll]);
};

// Hook for tracking hover patterns
export const useHoverTracking = (elementId: string) => {
  const { trackHover } = useBehavioralStore();
  const startTimeRef = useRef<number>(0);

  const onMouseEnter = useCallback(() => {
    startTimeRef.current = Date.now();
  }, []);

  const onMouseLeave = useCallback(() => {
    if (startTimeRef.current > 0) {
      const duration = Date.now() - startTimeRef.current;
      if (duration > 100) { // Only track hovers longer than 100ms
        trackHover(elementId, duration);
      }
      startTimeRef.current = 0;
    }
  }, [elementId, trackHover]);

  return { onMouseEnter, onMouseLeave };
};

// Hook for tracking clicks
export const useClickTracking = () => {
  const { trackClick } = useBehavioralStore();

  const trackClickEvent = useCallback(
    (element: string, page: string) => {
      trackClick(element, page);
    },
    [trackClick]
  );

  return trackClickEvent;
};

// Hook for rage click detection
export const useRageClickDetection = (onRageClick?: (element: string) => void) => {
  const clicksRef = useRef<{ element: string; timestamp: number }[]>([]);

  const detectRageClick = useCallback(
    (element: string) => {
      const now = Date.now();
      clicksRef.current.push({ element, timestamp: now });

      // Remove clicks older than 2 seconds
      clicksRef.current = clicksRef.current.filter(
        (click) => now - click.timestamp < 2000
      );

      // Check for rage clicks (5+ clicks within 2 seconds on same element)
      const recentClicks = clicksRef.current.filter(
        (click) => click.element === element
      );

      if (recentClicks.length >= 5) {
        onRageClick?.(element);
        clicksRef.current = []; // Reset after detection
      }
    },
    [onRageClick]
  );

  return detectRageClick;
};

// Hook for cart abandonment detection
export const useCartAbandonmentTracking = () => {
  const { trackCartAction } = useBehavioralStore();
  const abandonTimeoutRef = useRef<NodeJS.Timeout>();

  useEffect(() => {
    const handleBeforeUnload = (e: BeforeUnloadEvent) => {
      const cartItems = localStorage.getItem('cart-store');
      if (cartItems) {
        const cart = JSON.parse(cartItems);
        if (cart.state?.items?.length > 0) {
          trackCartAction('abandon');
          // Optional: Show warning message
          e.preventDefault();
          e.returnValue = '';
        }
      }
    };

    window.addEventListener('beforeunload', handleBeforeUnload);

    return () => {
      window.removeEventListener('beforeunload', handleBeforeUnload);
      if (abandonTimeoutRef.current) {
        clearTimeout(abandonTimeoutRef.current);
      }
    };
  }, [trackCartAction]);

  const resetAbandonTimer = useCallback(() => {
    if (abandonTimeoutRef.current) {
      clearTimeout(abandonTimeoutRef.current);
    }

    abandonTimeoutRef.current = setTimeout(() => {
      trackCartAction('abandon');
    }, 30 * 60 * 1000); // 30 minutes
  }, [trackCartAction]);

  return { resetAbandonTimer };
};

// Hook for sending behavioral data to backend
export const useSyncBehavioralData = () => {
  const behavioralData = useBehavioralStore();

  const syncToBackend = useCallback(async () => {
    try {
      const response = await fetch(`${process.env.NEXT_PUBLIC_API_URL}/analytics/behavior`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Authorization: `Bearer ${localStorage.getItem('auth_token')}`,
        },
        body: JSON.stringify({
          session_id: behavioralData.sessionId,
          recently_viewed: behavioralData.recentlyViewed.slice(0, 20),
          search_history: behavioralData.searchHistory.slice(0, 20),
          click_patterns: behavioralData.clickPatterns.slice(0, 50),
          scroll_depth: behavioralData.scrollDepth,
          time_on_page: behavioralData.timeOnPage,
          hover_patterns: behavioralData.hoverPatterns.slice(0, 20),
          cart_behavior: behavioralData.cartBehavior,
          preferences: behavioralData.preferences,
        }),
      });

      if (!response.ok) {
        console.error('Failed to sync behavioral data');
      }
    } catch (error) {
      console.error('Error syncing behavioral data:', error);
    }
  }, [behavioralData]);

  useEffect(() => {
    // Sync every 5 minutes
    const interval = setInterval(syncToBackend, 5 * 60 * 1000);

    // Sync on unmount
    return () => {
      clearInterval(interval);
      syncToBackend();
    };
  }, [syncToBackend]);

  return { syncToBackend };
};
