'use client';

import { useEffect, useState } from 'react';
import { Wifi, WifiOff } from 'lucide-react';

/**
 * Offline indicator and handler
 * Shows connection status and cached content availability
 */
export function OfflineIndicator() {
  const [isOnline, setIsOnline] = useState(true);
  const [showNotification, setShowNotification] = useState(false);

  useEffect(() => {
    const handleOnline = () => {
      setIsOnline(true);
      setShowNotification(true);
      setTimeout(() => setShowNotification(false), 3000);
    };

    const handleOffline = () => {
      setIsOnline(false);
      setShowNotification(true);
    };

    setIsOnline(navigator.onLine);

    window.addEventListener('online', handleOnline);
    window.addEventListener('offline', handleOffline);

    return () => {
      window.removeEventListener('online', handleOnline);
      window.removeEventListener('offline', handleOffline);
    };
  }, []);

  if (!showNotification) return null;

  return (
    <div
      className={`fixed top-4 left-1/2 -translate-x-1/2 z-50 px-4 py-2 rounded-lg shadow-lg flex items-center gap-2 ${
        isOnline
          ? 'bg-green-500 text-white'
          : 'bg-gray-800 text-white'
      }`}
      role="status"
      aria-live="polite"
    >
      {isOnline ? (
        <>
          <Wifi className="w-5 h-5" />
          <span>Back online</span>
        </>
      ) : (
        <>
          <WifiOff className="w-5 h-5" />
          <span>You're offline</span>
        </>
      )}
    </div>
  );
}

/**
 * Hook for checking online status
 */
export function useOnlineStatus() {
  const [isOnline, setIsOnline] = useState(true);

  useEffect(() => {
    const handleOnline = () => setIsOnline(true);
    const handleOffline = () => setIsOnline(false);

    setIsOnline(navigator.onLine);

    window.addEventListener('online', handleOnline);
    window.addEventListener('offline', handleOffline);

    return () => {
      window.removeEventListener('online', handleOnline);
      window.removeEventListener('offline', handleOffline);
    };
  }, []);

  return isOnline;
}

/**
 * Offline-capable data fetcher
 */
export async function offlineFetch<T>(
  url: string,
  options?: RequestInit
): Promise<T> {
  try {
    const response = await fetch(url, options);
    const data = await response.json();

    // Cache successful response
    if ('caches' in window) {
      const cache = await caches.open('api-cache-v1');
      cache.put(url, new Response(JSON.stringify(data)));
    }

    return data;
  } catch (error) {
    // Try to get cached data if offline
    if ('caches' in window) {
      const cache = await caches.open('api-cache-v1');
      const cached = await cache.match(url);

      if (cached) {
        return await cached.json();
      }
    }

    throw error;
  }
}

export default { OfflineIndicator, useOnlineStatus, offlineFetch };
