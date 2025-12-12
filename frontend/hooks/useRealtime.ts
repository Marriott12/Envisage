import { useEffect, useState, useCallback } from 'react';
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

declare global {
  interface Window {
    Pusher: typeof Pusher;
    Echo: Echo;
  }
}

// Initialize Echo instance
let echoInstance: Echo | null = null;

export const initializeEcho = () => {
  if (typeof window === 'undefined') return null;
  
  if (echoInstance) return echoInstance;

  window.Pusher = Pusher;

  echoInstance = new Echo({
    broadcaster: 'pusher',
    key: process.env.NEXT_PUBLIC_PUSHER_KEY,
    cluster: process.env.NEXT_PUBLIC_PUSHER_CLUSTER || 'mt1',
    forceTLS: true,
    authEndpoint: `${process.env.NEXT_PUBLIC_API_URL}/broadcasting/auth`,
    auth: {
      headers: {
        Authorization: `Bearer ${typeof window !== 'undefined' ? localStorage.getItem('auth_token') : ''}`,
      },
    },
  });

  window.Echo = echoInstance;

  return echoInstance;
};

export const getEcho = () => {
  if (!echoInstance) {
    return initializeEcho();
  }
  return echoInstance;
};

// Hook for real-time product updates
export const useRealtimeProduct = (productId: string | number) => {
  const [product, setProduct] = useState<any>(null);
  const [viewers, setViewers] = useState<number>(0);
  const [stockStatus, setStockStatus] = useState<'in_stock' | 'low_stock' | 'out_of_stock'>('in_stock');

  useEffect(() => {
    const echo = getEcho();
    if (!echo || !productId) return;

    const channel = echo.channel(`product.${productId}`);

    channel
      .listen('StockUpdated', (data: any) => {
        setProduct((prev: any) => ({ ...prev, stock: data.stock }));
        setStockStatus(data.stock_status);
      })
      .listen('PriceChanged', (data: any) => {
        setProduct((prev: any) => ({ ...prev, price: data.price, original_price: data.original_price }));
      })
      .listen('ViewersUpdated', (data: any) => {
        setViewers(data.viewers);
      });

    return () => {
      channel.stopListening('StockUpdated');
      channel.stopListening('PriceChanged');
      channel.stopListening('ViewersUpdated');
      echo.leave(`product.${productId}`);
    };
  }, [productId]);

  return { product, viewers, stockStatus };
};

// Hook for real-time order updates
export const useRealtimeOrder = (orderId: string | number) => {
  const [orderStatus, setOrderStatus] = useState<string>('');
  const [trackingInfo, setTrackingInfo] = useState<any>(null);
  const [updates, setUpdates] = useState<any[]>([]);

  useEffect(() => {
    const echo = getEcho();
    if (!echo || !orderId) return;

    const channel = echo.private(`order.${orderId}`);

    channel
      .listen('OrderStatusUpdated', (data: any) => {
        setOrderStatus(data.status);
        setUpdates((prev) => [data, ...prev]);
      })
      .listen('TrackingUpdated', (data: any) => {
        setTrackingInfo(data.tracking);
      });

    return () => {
      channel.stopListening('OrderStatusUpdated');
      channel.stopListening('TrackingUpdated');
      echo.leave(`order.${orderId}`);
    };
  }, [orderId]);

  return { orderStatus, trackingInfo, updates };
};

// Hook for real-time notifications
export const useRealtimeNotifications = (userId: string | number) => {
  const [notifications, setNotifications] = useState<any[]>([]);
  const [unreadCount, setUnreadCount] = useState<number>(0);

  useEffect(() => {
    const echo = getEcho();
    if (!echo || !userId) return;

    const channel = echo.private(`user.${userId}`);

    channel
      .notification((notification: any) => {
        setNotifications((prev) => [notification, ...prev]);
        setUnreadCount((prev) => prev + 1);
      });

    return () => {
      echo.leave(`user.${userId}`);
    };
  }, [userId]);

  const markAsRead = useCallback((notificationId: string) => {
    setNotifications((prev) =>
      prev.map((n) => (n.id === notificationId ? { ...n, read: true } : n))
    );
    setUnreadCount((prev) => Math.max(0, prev - 1));
  }, []);

  const markAllAsRead = useCallback(() => {
    setNotifications((prev) => prev.map((n) => ({ ...n, read: true })));
    setUnreadCount(0);
  }, []);

  return { notifications, unreadCount, markAsRead, markAllAsRead };
};

// Hook for live cart sync across tabs/devices
export const useRealtimeCart = (userId: string | number) => {
  const [cartUpdates, setCartUpdates] = useState<any>(null);

  useEffect(() => {
    const echo = getEcho();
    if (!echo || !userId) return;

    const channel = echo.private(`cart.${userId}`);

    channel
      .listen('CartUpdated', (data: any) => {
        setCartUpdates(data);
      });

    return () => {
      channel.stopListening('CartUpdated');
      echo.leave(`cart.${userId}`);
    };
  }, [userId]);

  return { cartUpdates };
};

// Hook for flash sale countdown
export const useRealtimeFlashSale = (saleId: string | number) => {
  const [timeRemaining, setTimeRemaining] = useState<number>(0);
  const [itemsSold, setItemsSold] = useState<number>(0);
  const [itemsRemaining, setItemsRemaining] = useState<number>(0);
  const [isActive, setIsActive] = useState<boolean>(true);

  useEffect(() => {
    const echo = getEcho();
    if (!echo || !saleId) return;

    const channel = echo.channel(`flash-sale.${saleId}`);

    channel
      .listen('TimeUpdated', (data: any) => {
        setTimeRemaining(data.time_remaining);
      })
      .listen('ItemSold', (data: any) => {
        setItemsSold(data.items_sold);
        setItemsRemaining(data.items_remaining);
      })
      .listen('SaleEnded', () => {
        setIsActive(false);
        setTimeRemaining(0);
      });

    return () => {
      channel.stopListening('TimeUpdated');
      channel.stopListening('ItemSold');
      channel.stopListening('SaleEnded');
      echo.leave(`flash-sale.${saleId}`);
    };
  }, [saleId]);

  return { timeRemaining, itemsSold, itemsRemaining, isActive };
};

// Hook for social proof notifications
export const useRealtimeSocialProof = () => {
  const [recentPurchases, setRecentPurchases] = useState<any[]>([]);

  useEffect(() => {
    const echo = getEcho();
    if (!echo) return;

    const channel = echo.channel('social-proof');

    channel.listen('RecentPurchase', (data: any) => {
      setRecentPurchases((prev) => [data, ...prev].slice(0, 10)); // Keep last 10
    });

    return () => {
      channel.stopListening('RecentPurchase');
      echo.leave('social-proof');
    };
  }, []);

  return { recentPurchases };
};

// Hook for presence channel (online users)
export const usePresence = (channelName: string) => {
  const [users, setUsers] = useState<any[]>([]);
  const [count, setCount] = useState<number>(0);

  useEffect(() => {
    const echo = getEcho();
    if (!echo) return;

    const channel = echo.join(channelName);

    channel
      .here((users: any[]) => {
        setUsers(users);
        setCount(users.length);
      })
      .joining((user: any) => {
        setUsers((prev) => [...prev, user]);
        setCount((prev) => prev + 1);
      })
      .leaving((user: any) => {
        setUsers((prev) => prev.filter((u) => u.id !== user.id));
        setCount((prev) => prev - 1);
      });

    return () => {
      echo.leave(channelName);
    };
  }, [channelName]);

  return { users, count };
};
