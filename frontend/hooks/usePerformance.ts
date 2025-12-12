import { useEffect, useRef, useState, useCallback } from 'react';
import { useRouter } from 'next/navigation';

// Hook for debouncing values
export const useDebounce = <T,>(value: T, delay: number): T => {
  const [debouncedValue, setDebouncedValue] = useState<T>(value);

  useEffect(() => {
    const handler = setTimeout(() => {
      setDebouncedValue(value);
    }, delay);

    return () => {
      clearTimeout(handler);
    };
  }, [value, delay]);

  return debouncedValue;
};

// Hook for throttling callbacks
export const useThrottle = <T extends (...args: any[]) => any>(
  callback: T,
  delay: number
): T => {
  const lastRan = useRef(Date.now());

  return useCallback(
    ((...args) => {
      if (Date.now() - lastRan.current >= delay) {
        callback(...args);
        lastRan.current = Date.now();
      }
    }) as T,
    [callback, delay]
  );
};

// Hook for Intersection Observer (lazy loading)
export const useIntersectionObserver = (
  options?: IntersectionObserverInit
): [(node: Element | null) => void, boolean, IntersectionObserverEntry | null] => {
  const [isIntersecting, setIsIntersecting] = useState(false);
  const [entry, setEntry] = useState<IntersectionObserverEntry | null>(null);
  const [node, setNode] = useState<Element | null>(null);

  useEffect(() => {
    if (!node) return;

    const observer = new IntersectionObserver(
      ([entry]) => {
        setIsIntersecting(entry.isIntersecting);
        setEntry(entry);
      },
      { threshold: 0.1, ...options }
    );

    observer.observe(node);

    return () => {
      observer.disconnect();
    };
  }, [node, options]);

  return [setNode, isIntersecting, entry];
};

// Hook for prefetching routes
export const usePrefetch = () => {
  const router = useRouter();

  const prefetch = useCallback(
    (href: string) => {
      router.prefetch(href);
    },
    [router]
  );

  const prefetchOnHover = useCallback(
    (href: string) => ({
      onMouseEnter: () => prefetch(href),
      onTouchStart: () => prefetch(href),
    }),
    [prefetch]
  );

  return { prefetch, prefetchOnHover };
};

// Hook for measuring component render time
export const useRenderTime = (componentName: string) => {
  const renderStartTime = useRef<number>(0);

  useEffect(() => {
    renderStartTime.current = performance.now();

    return () => {
      const renderTime = performance.now() - renderStartTime.current;
      if (process.env.NODE_ENV === 'development') {
        console.log(`[Render Time] ${componentName}: ${renderTime.toFixed(2)}ms`);
      }

      // Send to analytics in production
      if (process.env.NODE_ENV === 'production' && renderTime > 100) {
        // Only report slow renders
        fetch(`${process.env.NEXT_PUBLIC_API_URL}/analytics/performance`, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            metric: 'component_render',
            component: componentName,
            duration: renderTime,
            timestamp: Date.now(),
          }),
        }).catch(() => {
          // Silently fail
        });
      }
    };
  });
};

// Hook for tracking Core Web Vitals
export const useWebVitals = () => {
  useEffect(() => {
    if (typeof window === 'undefined') return;

    const reportWebVital = (metric: {
      name: string;
      value: number;
      id: string;
      rating: 'good' | 'needs-improvement' | 'poor';
    }) => {
      // Send to analytics
      fetch(`${process.env.NEXT_PUBLIC_API_URL}/analytics/web-vitals`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          name: metric.name,
          value: metric.value,
          id: metric.id,
          rating: metric.rating,
          url: window.location.href,
          timestamp: Date.now(),
        }),
      }).catch(() => {
        // Silently fail
      });
    };

    // Use web-vitals library if available
    import('web-vitals').then(({ onCLS, onFID, onFCP, onLCP, onTTFB, onINP }) => {
      onCLS(reportWebVital);
      onFID(reportWebVital);
      onFCP(reportWebVital);
      onLCP(reportWebVital);
      onTTFB(reportWebVital);
      onINP(reportWebVital);
    }).catch(() => {
      // web-vitals not available
    });
  }, []);
};

// Hook for image lazy loading with blur-up
export const useImageOptimization = (src: string) => {
  const [isLoaded, setIsLoaded] = useState(false);
  const [currentSrc, setCurrentSrc] = useState<string>('');

  useEffect(() => {
    // Generate blur placeholder (tiny base64 image)
    const blurDataURL = `data:image/svg+xml;base64,${btoa(
      `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 400 300"><filter id="b" color-interpolation-filters="sRGB"><feGaussianBlur stdDeviation="20"/></filter><rect width="100%" height="100%" fill="#f0f0f0" filter="url(#b)"/></svg>`
    )}`;

    setCurrentSrc(blurDataURL);

    const img = new Image();
    img.src = src;
    img.onload = () => {
      setCurrentSrc(src);
      setIsLoaded(true);
    };
  }, [src]);

  return { currentSrc, isLoaded };
};

// Hook for idle callback
export const useIdleCallback = (callback: () => void, deps: React.DependencyList) => {
  useEffect(() => {
    if (typeof window === 'undefined' || !('requestIdleCallback' in window)) {
      // Fallback for browsers that don't support requestIdleCallback
      const timeout = setTimeout(callback, 1);
      return () => clearTimeout(timeout);
    }

    const handle = requestIdleCallback(callback);

    return () => cancelIdleCallback(handle);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, deps);
};

// Hook for detecting slow network
export const useNetworkStatus = () => {
  const [networkStatus, setNetworkStatus] = useState<{
    online: boolean;
    effectiveType: string;
    downlink: number;
    rtt: number;
    saveData: boolean;
  }>({
    online: true,
    effectiveType: '4g',
    downlink: 10,
    rtt: 50,
    saveData: false,
  });

  useEffect(() => {
    if (typeof window === 'undefined') return;

    const updateNetworkStatus = () => {
      const connection = (navigator as any).connection || (navigator as any).mozConnection || (navigator as any).webkitConnection;

      setNetworkStatus({
        online: navigator.onLine,
        effectiveType: connection?.effectiveType || '4g',
        downlink: connection?.downlink || 10,
        rtt: connection?.rtt || 50,
        saveData: connection?.saveData || false,
      });
    };

    updateNetworkStatus();

    window.addEventListener('online', updateNetworkStatus);
    window.addEventListener('offline', updateNetworkStatus);

    const connection = (navigator as any).connection || (navigator as any).mozConnection || (navigator as any).webkitConnection;
    connection?.addEventListener('change', updateNetworkStatus);

    return () => {
      window.removeEventListener('online', updateNetworkStatus);
      window.removeEventListener('offline', updateNetworkStatus);
      connection?.removeEventListener('change', updateNetworkStatus);
    };
  }, []);

  return networkStatus;
};

// Hook for memory leak detection
export const useMemoryLeakDetection = (componentName: string) => {
  useEffect(() => {
    if (process.env.NODE_ENV !== 'development') return;

    const checkMemory = () => {
      if ('memory' in performance) {
        const memory = (performance as any).memory;
        const usedMemoryMB = memory.usedJSHeapSize / 1048576;
        const totalMemoryMB = memory.totalJSHeapSize / 1048576;

        if (usedMemoryMB > totalMemoryMB * 0.9) {
          console.warn(`[Memory Warning] ${componentName}: High memory usage (${usedMemoryMB.toFixed(2)}MB)`);
        }
      }
    };

    const interval = setInterval(checkMemory, 10000); // Check every 10 seconds

    return () => {
      clearInterval(interval);
    };
  }, [componentName]);
};

// Hook for bundle size optimization hints
export const useBundleSizeHint = (moduleName: string, size: number) => {
  useEffect(() => {
    if (process.env.NODE_ENV === 'development' && size > 500) {
      // Warn about large modules (>500KB)
      console.warn(`[Bundle Size] ${moduleName} is large (${size}KB). Consider code splitting.`);
    }
  }, [moduleName, size]);
};

// Hook for detecting user inactivity
export const useInactivity = (timeoutMs: number, onInactive: () => void) => {
  const timeoutRef = useRef<NodeJS.Timeout>();

  useEffect(() => {
    const resetTimer = () => {
      if (timeoutRef.current) {
        clearTimeout(timeoutRef.current);
      }
      timeoutRef.current = setTimeout(onInactive, timeoutMs);
    };

    const events = ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart'];
    events.forEach((event) => {
      document.addEventListener(event, resetTimer, { passive: true });
    });

    resetTimer();

    return () => {
      if (timeoutRef.current) {
        clearTimeout(timeoutRef.current);
      }
      events.forEach((event) => {
        document.removeEventListener(event, resetTimer);
      });
    };
  }, [timeoutMs, onInactive]);
};

// Hook for battery-aware loading
export const useBatteryStatus = () => {
  const [batteryStatus, setBatteryStatus] = useState<{
    charging: boolean;
    level: number;
    chargingTime: number;
    dischargingTime: number;
  } | null>(null);

  useEffect(() => {
    if (typeof navigator === 'undefined' || !('getBattery' in navigator)) return;

    (navigator as any).getBattery().then((battery: any) => {
      const updateBatteryStatus = () => {
        setBatteryStatus({
          charging: battery.charging,
          level: battery.level,
          chargingTime: battery.chargingTime,
          dischargingTime: battery.dischargingTime,
        });
      };

      updateBatteryStatus();

      battery.addEventListener('chargingchange', updateBatteryStatus);
      battery.addEventListener('levelchange', updateBatteryStatus);

      return () => {
        battery.removeEventListener('chargingchange', updateBatteryStatus);
        battery.removeEventListener('levelchange', updateBatteryStatus);
      };
    });
  }, []);

  return batteryStatus;
};
