/**
 * AI Real-Time Events Hooks
 * 
 * Custom React hooks for subscribing to AI-powered real-time events
 * including recommendations, fraud detection, sentiment analysis,
 * chatbot responses, and A/B testing results.
 * 
 * @module useAIEvents
 */

import { useEffect, useState, useCallback, useRef } from 'react';
import { getEcho } from './useRealtime';

// ============================================
// Type Definitions
// ============================================

export interface Recommendation {
  id: number;
  name: string;
  price: number;
  image?: string;
  category?: string;
  score?: number;
}

export interface RecommendationEvent {
  type: 'recommendation_generated';
  recommendations: Recommendation[];
  algorithm: string;
  processing_time: number;
  timestamp: string;
}

export interface FraudAlert {
  type: 'fraud_alert';
  alert_id: number;
  transaction_id: number;
  risk_score: number;
  risk_level: 'low' | 'medium' | 'high' | 'critical';
  indicators: string[];
  timestamp: string;
}

export interface SentimentAnalysis {
  type: 'sentiment_analysis_complete';
  product_id: number;
  total_reviews: number;
  overall_sentiment: 'positive' | 'neutral' | 'negative';
  sentiment_breakdown: {
    positive: number;
    neutral: number;
    negative: number;
  };
  fake_reviews_detected: number;
  timestamp: string;
}

export interface ChatbotResponse {
  type: 'chatbot_response';
  message: string;
  response_time: number;
  suggested_actions: string[];
  timestamp: string;
}

export interface ABTestResult {
  type: 'abtest_winner_determined';
  experiment_name: string;
  winning_variant: string;
  is_statistically_significant: boolean;
  lift_percentage: number;
  confidence_level: number;
  metrics: any;
  timestamp: string;
}

export interface BudgetAlert {
  type: 'budget_alert';
  service: string;
  current_spend: number;
  budget_limit: number;
  percentage_used: number;
  alert_level: 'warning' | 'critical';
  message: string;
  timestamp: string;
}

// ============================================
// Hook: AI Recommendations
// ============================================

/**
 * Subscribe to real-time AI recommendation events for a specific user
 * 
 * @param userId - User ID to subscribe to
 * @returns Recommendations state and control functions
 * 
 * @example
 * ```tsx
 * const { recommendations, loading, generate } = useAIRecommendations(userId);
 * 
 * <button onClick={() => generate('neural', 10)}>
 *   Get Recommendations
 * </button>
 * ```
 */
export const useAIRecommendations = (userId: number | string | null) => {
  const [recommendations, setRecommendations] = useState<Recommendation[]>([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<Error | null>(null);
  const [lastUpdate, setLastUpdate] = useState<Date | null>(null);

  useEffect(() => {
    if (!userId) return;

    const echo = getEcho();
    if (!echo) return;

    const channel = echo.private(`ai.user.${userId}`);

    const handleRecommendation = (event: RecommendationEvent) => {
      console.log('✅ Recommendations received:', event);
      setRecommendations(event.recommendations);
      setLoading(false);
      setLastUpdate(new Date(event.timestamp));
      setError(null);
    };

    channel.listen('.recommendation.generated', handleRecommendation);

    return () => {
      channel.stopListening('.recommendation.generated');
      echo.leave(`ai.user.${userId}`);
    };
  }, [userId]);

  const generate = useCallback(async (algorithm: string = 'neural', count: number = 10) => {
    setLoading(true);
    setError(null);

    try {
      const token = localStorage.getItem('auth_token');
      const response = await fetch(`${process.env.NEXT_PUBLIC_API_URL}/ai/recommendations`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${token}`,
        },
        body: JSON.stringify({ algorithm, count }),
      });

      if (!response.ok) {
        throw new Error('Failed to generate recommendations');
      }

      // Real-time update will arrive via WebSocket
    } catch (err) {
      setError(err as Error);
      setLoading(false);
    }
  }, []);

  const clear = useCallback(() => {
    setRecommendations([]);
    setError(null);
    setLastUpdate(null);
  }, []);

  return {
    recommendations,
    loading,
    error,
    lastUpdate,
    generate,
    clear,
  };
};

// ============================================
// Hook: Fraud Detection Alerts
// ============================================

/**
 * Subscribe to real-time fraud detection alerts
 * 
 * @param sellerId - Seller ID (optional, for seller-specific alerts)
 * @param isAdmin - Whether user is admin (receives all alerts)
 * @returns Fraud alerts state and control functions
 * 
 * @example
 * ```tsx
 * const { alerts, unreadCount, markAsRead } = useFraudAlerts(sellerId, isAdmin);
 * ```
 */
export const useFraudAlerts = (sellerId?: number | string, isAdmin: boolean = false) => {
  const [alerts, setAlerts] = useState<FraudAlert[]>([]);
  const [unreadCount, setUnreadCount] = useState(0);
  const audioRef = useRef<HTMLAudioElement | null>(null);

  useEffect(() => {
    // Initialize alert sound
    if (typeof window !== 'undefined') {
      audioRef.current = new Audio('/sounds/alert.mp3');
    }

    const echo = getEcho();
    if (!echo) return;

    const channels: any[] = [];

    // Subscribe to seller-specific channel
    if (sellerId) {
      const sellerChannel = echo.private(`ai.fraud.seller.${sellerId}`);
      channels.push(sellerChannel);
    }

    // Subscribe to admin channel
    if (isAdmin) {
      const adminChannel = echo.private('ai.fraud.admin');
      channels.push(adminChannel);
    }

    const handleFraudAlert = (event: FraudAlert) => {
      console.log('🚨 Fraud alert received:', event);
      
      setAlerts(prev => [event, ...prev]);
      setUnreadCount(prev => prev + 1);

      // Play alert sound for high/critical risk
      if (['high', 'critical'].includes(event.risk_level) && audioRef.current) {
        audioRef.current.play().catch(() => {});
      }

      // Show browser notification
      if ('Notification' in window && Notification.permission === 'granted') {
        new Notification(`Fraud Alert - ${event.risk_level.toUpperCase()}`, {
          body: `Transaction #${event.transaction_id} - Risk Score: ${event.risk_score}%`,
          icon: '/icons/warning.png',
          tag: `fraud-${event.alert_id}`,
        });
      }
    };

    channels.forEach(channel => {
      channel.listen('.fraud.alert.created', handleFraudAlert);
    });

    return () => {
      channels.forEach(channel => {
        channel.stopListening('.fraud.alert.created');
      });
    };
  }, [sellerId, isAdmin]);

  const markAsRead = useCallback(() => {
    setUnreadCount(0);
  }, []);

  const clearAlerts = useCallback(() => {
    setAlerts([]);
    setUnreadCount(0);
  }, []);

  const dismissAlert = useCallback((alertId: number) => {
    setAlerts(prev => prev.filter(alert => alert.alert_id !== alertId));
  }, []);

  return {
    alerts,
    unreadCount,
    markAsRead,
    clearAlerts,
    dismissAlert,
  };
};

// ============================================
// Hook: Sentiment Analysis Updates
// ============================================

/**
 * Subscribe to real-time sentiment analysis updates for seller's products
 * 
 * @param sellerId - Seller ID
 * @returns Sentiment analysis state and filter functions
 * 
 * @example
 * ```tsx
 * const { sentimentData, filterByProduct } = useSentimentUpdates(sellerId);
 * ```
 */
export const useSentimentUpdates = (sellerId: number | string | null) => {
  const [sentimentData, setSentimentData] = useState<SentimentAnalysis[]>([]);
  const [filteredData, setFilteredData] = useState<SentimentAnalysis[]>([]);

  useEffect(() => {
    if (!sellerId) return;

    const echo = getEcho();
    if (!echo) return;

    const channel = echo.private(`ai.sentiment.seller.${sellerId}`);

    const handleSentimentUpdate = (event: SentimentAnalysis) => {
      console.log('📊 Sentiment analysis received:', event);
      setSentimentData(prev => [event, ...prev]);
      setFilteredData(prev => [event, ...prev]);
    };

    channel.listen('.sentiment.analysis.complete', handleSentimentUpdate);

    return () => {
      channel.stopListening('.sentiment.analysis.complete');
      echo.leave(`ai.sentiment.seller.${sellerId}`);
    };
  }, [sellerId]);

  const filterByProduct = useCallback((productId: number) => {
    setFilteredData(sentimentData.filter(item => item.product_id === productId));
  }, [sentimentData]);

  const clearFilter = useCallback(() => {
    setFilteredData(sentimentData);
  }, [sentimentData]);

  const getLatestForProduct = useCallback((productId: number) => {
    return sentimentData.find(item => item.product_id === productId);
  }, [sentimentData]);

  return {
    sentimentData: filteredData,
    allSentimentData: sentimentData,
    filterByProduct,
    clearFilter,
    getLatestForProduct,
  };
};

// ============================================
// Hook: Real-Time Chatbot
// ============================================

/**
 * Subscribe to real-time chatbot responses for a conversation
 * 
 * @param conversationId - Conversation ID
 * @returns Chatbot state and messaging functions
 * 
 * @example
 * ```tsx
 * const { messages, typing, sendMessage } = useChatbot(conversationId);
 * ```
 */
export const useChatbot = (conversationId: string | null) => {
  const [messages, setMessages] = useState<any[]>([]);
  const [typing, setTyping] = useState(false);
  const [error, setError] = useState<Error | null>(null);

  useEffect(() => {
    if (!conversationId) return;

    const echo = getEcho();
    if (!echo) return;

    const channel = echo.private(`ai.chat.${conversationId}`);

    const handleChatbotResponse = (event: ChatbotResponse) => {
      console.log('💬 Chatbot response received:', event);
      
      setMessages(prev => [...prev, {
        id: Date.now(),
        role: 'assistant',
        content: event.message,
        suggestedActions: event.suggested_actions,
        timestamp: new Date(event.timestamp),
      }]);
      
      setTyping(false);
    };

    channel.listen('.chatbot.response.ready', handleChatbotResponse);

    return () => {
      channel.stopListening('.chatbot.response.ready');
      echo.leave(`ai.chat.${conversationId}`);
    };
  }, [conversationId]);

  const sendMessage = useCallback(async (message: string) => {
    if (!conversationId || !message.trim()) return;

    // Add user message optimistically
    const userMessage = {
      id: Date.now(),
      role: 'user',
      content: message,
      timestamp: new Date(),
    };
    setMessages(prev => [...prev, userMessage]);
    setTyping(true);
    setError(null);

    try {
      const token = localStorage.getItem('auth_token');
      const response = await fetch(
        `${process.env.NEXT_PUBLIC_API_URL}/ai/chat/${conversationId}`,
        {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${token}`,
          },
          body: JSON.stringify({ message }),
        }
      );

      if (!response.ok) {
        throw new Error('Failed to send message');
      }

      // Real-time response will arrive via WebSocket
    } catch (err) {
      setError(err as Error);
      setTyping(false);
    }
  }, [conversationId]);

  const clearMessages = useCallback(() => {
    setMessages([]);
    setError(null);
  }, []);

  return {
    messages,
    typing,
    error,
    sendMessage,
    clearMessages,
  };
};

// ============================================
// Hook: A/B Test Results
// ============================================

/**
 * Subscribe to real-time A/B test results (admin only)
 * 
 * @param isAdmin - Whether user has admin access
 * @returns A/B test results state
 * 
 * @example
 * ```tsx
 * const { testResults, getResultByName } = useABTestResults(isAdmin);
 * ```
 */
export const useABTestResults = (isAdmin: boolean = false) => {
  const [testResults, setTestResults] = useState<ABTestResult[]>([]);

  useEffect(() => {
    if (!isAdmin) return;

    const echo = getEcho();
    if (!echo) return;

    const channel = echo.private('ai.abtest.admin');

    const handleABTestResult = (event: ABTestResult) => {
      console.log('🧪 A/B test result received:', event);
      setTestResults(prev => [event, ...prev]);

      // Show notification for significant results
      if (event.is_statistically_significant) {
        if ('Notification' in window && Notification.permission === 'granted') {
          new Notification('A/B Test Complete', {
            body: `${event.experiment_name}: Winner is ${event.winning_variant} (+${event.lift_percentage.toFixed(1)}%)`,
            icon: '/icons/experiment.png',
          });
        }
      }
    };

    channel.listen('.abtest.winner.determined', handleABTestResult);

    return () => {
      channel.stopListening('.abtest.winner.determined');
      echo.leave('ai.abtest.admin');
    };
  }, [isAdmin]);

  const getResultByName = useCallback((experimentName: string) => {
    return testResults.find(result => result.experiment_name === experimentName);
  }, [testResults]);

  const clearResults = useCallback(() => {
    setTestResults([]);
  }, []);

  return {
    testResults,
    getResultByName,
    clearResults,
  };
};

// ============================================
// Hook: AI Notifications (Budget, Alerts, etc.)
// ============================================

/**
 * Subscribe to AI-related notifications (budget alerts, etc.)
 * 
 * @param userId - User ID
 * @returns Notifications state
 * 
 * @example
 * ```tsx
 * const { notifications, unreadCount, markAsRead } = useAINotifications(userId);
 * ```
 */
export const useAINotifications = (userId: number | string | null) => {
  const [notifications, setNotifications] = useState<any[]>([]);
  const [unreadCount, setUnreadCount] = useState(0);

  useEffect(() => {
    if (!userId) return;

    const echo = getEcho();
    if (!echo) return;

    const channel = echo.private(`ai.notifications.user.${userId}`);

    const handleNotification = (notification: any) => {
      console.log('🔔 AI notification received:', notification);
      
      setNotifications(prev => [{
        ...notification,
        id: Date.now(),
        read: false,
        timestamp: new Date(),
      }, ...prev]);
      
      setUnreadCount(prev => prev + 1);

      // Show browser notification
      if ('Notification' in window && Notification.permission === 'granted') {
        let title = 'AI Notification';
        let body = notification.message || JSON.stringify(notification);
        
        if (notification.type === 'budget_alert') {
          title = notification.alert_level === 'critical' ? '🚨 Critical Budget Alert' : '⚠️ Budget Warning';
          body = `${notification.service} - ${notification.percentage_used.toFixed(1)}% of budget used`;
        }

        new Notification(title, { body });
      }
    };

    channel.notification(handleNotification);

    return () => {
      echo.leave(`ai.notifications.user.${userId}`);
    };
  }, [userId]);

  const markAsRead = useCallback((notificationId?: number) => {
    if (notificationId) {
      setNotifications(prev => 
        prev.map(n => n.id === notificationId ? { ...n, read: true } : n)
      );
    } else {
      setNotifications(prev => prev.map(n => ({ ...n, read: true })));
      setUnreadCount(0);
    }
  }, []);

  const clearNotifications = useCallback(() => {
    setNotifications([]);
    setUnreadCount(0);
  }, []);

  return {
    notifications,
    unreadCount,
    markAsRead,
    clearNotifications,
  };
};

// ============================================
// Hook: Connection Status
// ============================================

/**
 * Monitor WebSocket connection status
 * 
 * @returns Connection state and reconnect function
 * 
 * @example
 * ```tsx
 * const { connected, reconnect } = useConnectionStatus();
 * ```
 */
export const useConnectionStatus = () => {
  const [connected, setConnected] = useState(false);
  const [connecting, setConnecting] = useState(false);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    const echo = getEcho();
    if (!echo) return;

    const connector = (echo as any).connector;
    if (!connector || !connector.pusher) return;

    const pusher = connector.pusher;

    pusher.connection.bind('connected', () => {
      console.log('✅ WebSocket connected');
      setConnected(true);
      setConnecting(false);
      setError(null);
    });

    pusher.connection.bind('connecting', () => {
      console.log('🔌 WebSocket connecting...');
      setConnecting(true);
    });

    pusher.connection.bind('disconnected', () => {
      console.log('❌ WebSocket disconnected');
      setConnected(false);
      setConnecting(false);
    });

    pusher.connection.bind('error', (err: any) => {
      console.error('❌ WebSocket error:', err);
      setError(err.error?.data?.message || 'Connection error');
      setConnecting(false);
    });

    return () => {
      pusher.connection.unbind_all();
    };
  }, []);

  const reconnect = useCallback(() => {
    const echo = getEcho();
    if (!echo) return;

    const connector = (echo as any).connector;
    if (connector && connector.pusher) {
      connector.pusher.connect();
    }
  }, []);

  return {
    connected,
    connecting,
    error,
    reconnect,
  };
};
