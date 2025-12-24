/**
 * AI Real-Time Components
 * 
 * Ready-to-use React components for AI real-time features
 * 
 * @module AIRealtimeComponents
 */

import React, { useState } from 'react';
import {
  useAIRecommendations,
  useFraudAlerts,
  useSentimentUpdates,
  useChatbot,
  useABTestResults,
  useAINotifications,
  useConnectionStatus,
} from '@/hooks/useAIEvents';

// ============================================
// Real-Time Recommendations Panel
// ============================================

interface RecommendationsPanelProps {
  userId: number | string;
  onProductClick?: (productId: number) => void;
}

export const RealtimeRecommendationsPanel: React.FC<RecommendationsPanelProps> = ({ 
  userId, 
  onProductClick 
}) => {
  const { recommendations, loading, error, lastUpdate, generate, clear } = useAIRecommendations(userId);
  const [algorithm, setAlgorithm] = useState('neural');

  return (
    <div className="bg-white rounded-lg shadow-md p-6">
      <div className="flex justify-between items-center mb-4">
        <h2 className="text-2xl font-bold text-gray-800">
          AI Recommendations
          {lastUpdate && (
            <span className="text-sm text-gray-500 ml-2">
              Updated {new Date(lastUpdate).toLocaleTimeString()}
            </span>
          )}
        </h2>
        <div className="flex gap-2">
          <select
            value={algorithm}
            onChange={(e) => setAlgorithm(e.target.value)}
            className="px-3 py-2 border rounded-md text-sm"
            disabled={loading}
          >
            <option value="neural">Neural Network</option>
            <option value="bandit">Multi-Armed Bandit</option>
            <option value="session">Session-Based</option>
            <option value="context">Context-Aware</option>
            <option value="hybrid">Hybrid</option>
          </select>
          <button
            onClick={() => generate(algorithm, 10)}
            disabled={loading}
            className="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:bg-gray-400 transition"
          >
            {loading ? (
              <>
                <span className="inline-block animate-spin mr-2">⟳</span>
                Generating...
              </>
            ) : (
              'Generate'
            )}
          </button>
          {recommendations.length > 0 && (
            <button
              onClick={clear}
              className="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 transition"
            >
              Clear
            </button>
          )}
        </div>
      </div>

      {error && (
        <div className="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded mb-4">
          Error: {error.message}
        </div>
      )}

      {loading && (
        <div className="flex items-center justify-center py-12">
          <div className="text-center">
            <div className="inline-block animate-spin text-4xl mb-2">⟳</div>
            <p className="text-gray-600">AI is generating personalized recommendations...</p>
          </div>
        </div>
      )}

      {!loading && recommendations.length === 0 && (
        <div className="text-center py-12 text-gray-500">
          <p className="text-lg mb-2">No recommendations yet</p>
          <p className="text-sm">Click "Generate" to get AI-powered product suggestions</p>
        </div>
      )}

      {!loading && recommendations.length > 0 && (
        <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
          {recommendations.map((product) => (
            <div
              key={product.id}
              className="border rounded-lg p-3 hover:shadow-lg transition cursor-pointer"
              onClick={() => onProductClick?.(product.id)}
            >
              {product.image && (
                <img
                  src={product.image}
                  alt={product.name}
                  className="w-full h-32 object-cover rounded mb-2"
                />
              )}
              <h3 className="font-semibold text-sm mb-1 truncate">{product.name}</h3>
              <p className="text-blue-600 font-bold">${product.price?.toFixed(2)}</p>
              {product.score && (
                <p className="text-xs text-gray-500 mt-1">
                  Match: {(product.score * 100).toFixed(0)}%
                </p>
              )}
            </div>
          ))}
        </div>
      )}
    </div>
  );
};

// ============================================
// Fraud Alert Dashboard
// ============================================

interface FraudDashboardProps {
  sellerId?: number | string;
  isAdmin?: boolean;
}

export const FraudAlertDashboard: React.FC<FraudDashboardProps> = ({ 
  sellerId, 
  isAdmin = false 
}) => {
  const { alerts, unreadCount, markAsRead, dismissAlert } = useFraudAlerts(sellerId, isAdmin);

  const getRiskColor = (level: string) => {
    switch (level) {
      case 'critical': return 'bg-red-100 border-red-500 text-red-900';
      case 'high': return 'bg-orange-100 border-orange-500 text-orange-900';
      case 'medium': return 'bg-yellow-100 border-yellow-500 text-yellow-900';
      default: return 'bg-blue-100 border-blue-500 text-blue-900';
    }
  };

  return (
    <div className="bg-white rounded-lg shadow-md p-6">
      <div className="flex justify-between items-center mb-4">
        <h2 className="text-2xl font-bold text-gray-800">
          Fraud Alerts
          {unreadCount > 0 && (
            <span className="ml-2 px-2 py-1 bg-red-500 text-white text-xs rounded-full">
              {unreadCount} new
            </span>
          )}
        </h2>
        {unreadCount > 0 && (
          <button
            onClick={markAsRead}
            className="text-sm text-blue-600 hover:text-blue-800"
          >
            Mark all as read
          </button>
        )}
      </div>

      {alerts.length === 0 ? (
        <div className="text-center py-12">
          <div className="text-6xl mb-4">✅</div>
          <p className="text-gray-600 text-lg">No fraud alerts</p>
          <p className="text-gray-500 text-sm">All transactions appear legitimate</p>
        </div>
      ) : (
        <div className="space-y-4 max-h-96 overflow-y-auto">
          {alerts.map((alert) => (
            <div
              key={alert.alert_id}
              className={`border-l-4 p-4 rounded ${getRiskColor(alert.risk_level)}`}
            >
              <div className="flex justify-between items-start mb-2">
                <div>
                  <h3 className="font-bold text-lg">
                    Transaction #{alert.transaction_id}
                  </h3>
                  <p className="text-sm opacity-75">
                    {new Date(alert.timestamp).toLocaleString()}
                  </p>
                </div>
                <button
                  onClick={() => dismissAlert(alert.alert_id)}
                  className="text-gray-500 hover:text-gray-700"
                >
                  ✕
                </button>
              </div>
              
              <div className="mb-3">
                <span className="font-semibold">Risk Score: </span>
                <span className="text-xl font-bold">{alert.risk_score}%</span>
                <span className="ml-2 px-2 py-1 rounded text-xs font-semibold uppercase">
                  {alert.risk_level}
                </span>
              </div>

              <div>
                <p className="font-semibold mb-1">Fraud Indicators:</p>
                <ul className="list-disc list-inside text-sm space-y-1">
                  {alert.indicators.map((indicator, idx) => (
                    <li key={idx}>{indicator.replace(/_/g, ' ')}</li>
                  ))}
                </ul>
              </div>

              <button className="mt-3 px-4 py-2 bg-white bg-opacity-50 rounded hover:bg-opacity-75 transition text-sm font-semibold">
                Review Transaction →
              </button>
            </div>
          ))}
        </div>
      )}
    </div>
  );
};

// ============================================
// Sentiment Analysis Monitor
// ============================================

interface SentimentMonitorProps {
  sellerId: number | string;
  productId?: number;
}

export const SentimentAnalysisMonitor: React.FC<SentimentMonitorProps> = ({ 
  sellerId, 
  productId 
}) => {
  const { sentimentData, getLatestForProduct } = useSentimentUpdates(sellerId);
  const latestData = productId ? getLatestForProduct(productId) : sentimentData[0];

  const getSentimentEmoji = (sentiment: string) => {
    switch (sentiment) {
      case 'positive': return '😊';
      case 'negative': return '😞';
      default: return '😐';
    }
  };

  const getSentimentColor = (sentiment: string) => {
    switch (sentiment) {
      case 'positive': return 'text-green-600';
      case 'negative': return 'text-red-600';
      default: return 'text-gray-600';
    }
  };

  if (!latestData) {
    return (
      <div className="bg-white rounded-lg shadow-md p-6">
        <h2 className="text-xl font-bold mb-4">Sentiment Analysis</h2>
        <p className="text-gray-500 text-center py-8">
          No sentiment data available yet
        </p>
      </div>
    );
  }

  const total = latestData.sentiment_breakdown.positive + 
                latestData.sentiment_breakdown.neutral + 
                latestData.sentiment_breakdown.negative;

  return (
    <div className="bg-white rounded-lg shadow-md p-6">
      <div className="flex items-center justify-between mb-4">
        <h2 className="text-xl font-bold">Sentiment Analysis</h2>
        <span className="text-sm text-gray-500">
          {new Date(latestData.timestamp).toLocaleString()}
        </span>
      </div>

      <div className="text-center mb-6">
        <div className="text-6xl mb-2">
          {getSentimentEmoji(latestData.overall_sentiment)}
        </div>
        <p className={`text-2xl font-bold ${getSentimentColor(latestData.overall_sentiment)}`}>
          {latestData.overall_sentiment.toUpperCase()}
        </p>
        <p className="text-gray-600 text-sm">
          Based on {latestData.total_reviews} reviews
        </p>
      </div>

      <div className="space-y-3 mb-4">
        <div>
          <div className="flex justify-between mb-1">
            <span className="text-sm font-medium text-green-600">Positive</span>
            <span className="text-sm">{latestData.sentiment_breakdown.positive}</span>
          </div>
          <div className="w-full bg-gray-200 rounded-full h-2">
            <div
              className="bg-green-500 h-2 rounded-full"
              style={{ width: `${(latestData.sentiment_breakdown.positive / total) * 100}%` }}
            />
          </div>
        </div>

        <div>
          <div className="flex justify-between mb-1">
            <span className="text-sm font-medium text-gray-600">Neutral</span>
            <span className="text-sm">{latestData.sentiment_breakdown.neutral}</span>
          </div>
          <div className="w-full bg-gray-200 rounded-full h-2">
            <div
              className="bg-gray-500 h-2 rounded-full"
              style={{ width: `${(latestData.sentiment_breakdown.neutral / total) * 100}%` }}
            />
          </div>
        </div>

        <div>
          <div className="flex justify-between mb-1">
            <span className="text-sm font-medium text-red-600">Negative</span>
            <span className="text-sm">{latestData.sentiment_breakdown.negative}</span>
          </div>
          <div className="w-full bg-gray-200 rounded-full h-2">
            <div
              className="bg-red-500 h-2 rounded-full"
              style={{ width: `${(latestData.sentiment_breakdown.negative / total) * 100}%` }}
            />
          </div>
        </div>
      </div>

      {latestData.fake_reviews_detected > 0 && (
        <div className="bg-yellow-50 border border-yellow-200 rounded p-3">
          <p className="text-yellow-800 text-sm">
            ⚠️ <strong>{latestData.fake_reviews_detected}</strong> fake reviews detected
          </p>
        </div>
      )}
    </div>
  );
};

// ============================================
// Real-Time Chatbot Widget
// ============================================

interface ChatbotWidgetProps {
  conversationId: string;
  onClose?: () => void;
}

export const RealtimeChatbotWidget: React.FC<ChatbotWidgetProps> = ({ 
  conversationId, 
  onClose 
}) => {
  const { messages, typing, error, sendMessage } = useChatbot(conversationId);
  const [input, setInput] = useState('');

  const handleSend = () => {
    if (!input.trim()) return;
    sendMessage(input);
    setInput('');
  };

  return (
    <div className="fixed bottom-4 right-4 w-96 h-[500px] bg-white rounded-lg shadow-2xl flex flex-col">
      {/* Header */}
      <div className="bg-blue-600 text-white p-4 rounded-t-lg flex justify-between items-center">
        <div className="flex items-center">
          <div className="w-3 h-3 bg-green-400 rounded-full mr-2"></div>
          <h3 className="font-bold">AI Assistant</h3>
        </div>
        {onClose && (
          <button onClick={onClose} className="text-white hover:text-gray-200">
            ✕
          </button>
        )}
      </div>

      {/* Messages */}
      <div className="flex-1 overflow-y-auto p-4 space-y-3">
        {messages.length === 0 && (
          <div className="text-center text-gray-500 mt-8">
            <p className="text-lg mb-2">👋 Hi! I'm your AI assistant</p>
            <p className="text-sm">Ask me anything about products or orders</p>
          </div>
        )}

        {messages.map((message) => (
          <div
            key={message.id}
            className={`flex ${message.role === 'user' ? 'justify-end' : 'justify-start'}`}
          >
            <div
              className={`max-w-[80%] rounded-lg p-3 ${
                message.role === 'user'
                  ? 'bg-blue-600 text-white'
                  : 'bg-gray-100 text-gray-800'
              }`}
            >
              <p className="text-sm">{message.content}</p>
              {message.suggestedActions && message.suggestedActions.length > 0 && (
                <div className="mt-2 space-y-1">
                  {message.suggestedActions.map((action: string, idx: number) => (
                    <button
                      key={idx}
                      className="block w-full text-left text-xs bg-white bg-opacity-20 hover:bg-opacity-30 rounded px-2 py-1"
                    >
                      {action.replace(/_/g, ' ')}
                    </button>
                  ))}
                </div>
              )}
            </div>
          </div>
        ))}

        {typing && (
          <div className="flex justify-start">
            <div className="bg-gray-100 rounded-lg p-3">
              <div className="flex space-x-1">
                <div className="w-2 h-2 bg-gray-400 rounded-full animate-bounce"></div>
                <div className="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style={{ animationDelay: '0.1s' }}></div>
                <div className="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style={{ animationDelay: '0.2s' }}></div>
              </div>
            </div>
          </div>
        )}
      </div>

      {/* Input */}
      <div className="border-t p-4">
        {error && (
          <p className="text-red-600 text-xs mb-2">Error: {error.message}</p>
        )}
        <div className="flex gap-2">
          <input
            type="text"
            value={input}
            onChange={(e) => setInput(e.target.value)}
            onKeyPress={(e) => e.key === 'Enter' && handleSend()}
            placeholder="Type your message..."
            className="flex-1 px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-600"
            disabled={typing}
          />
          <button
            onClick={handleSend}
            disabled={typing || !input.trim()}
            className="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:bg-gray-400 transition"
          >
            Send
          </button>
        </div>
      </div>
    </div>
  );
};

// ============================================
// A/B Test Results Dashboard
// ============================================

interface ABTestDashboardProps {
  isAdmin: boolean;
}

export const ABTestResultsDashboard: React.FC<ABTestDashboardProps> = ({ isAdmin }) => {
  const { testResults } = useABTestResults(isAdmin);

  if (!isAdmin) {
    return null;
  }

  return (
    <div className="bg-white rounded-lg shadow-md p-6">
      <h2 className="text-2xl font-bold mb-4">A/B Test Results</h2>

      {testResults.length === 0 ? (
        <p className="text-gray-500 text-center py-8">No test results yet</p>
      ) : (
        <div className="space-y-4">
          {testResults.map((result, idx) => (
            <div key={idx} className="border rounded-lg p-4">
              <div className="flex justify-between items-start mb-3">
                <div>
                  <h3 className="font-bold text-lg">{result.experiment_name}</h3>
                  <p className="text-sm text-gray-600">
                    {new Date(result.timestamp).toLocaleString()}
                  </p>
                </div>
                {result.is_statistically_significant ? (
                  <span className="px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm font-semibold">
                    ✓ Significant
                  </span>
                ) : (
                  <span className="px-3 py-1 bg-gray-100 text-gray-800 rounded-full text-sm font-semibold">
                    Not Significant
                  </span>
                )}
              </div>

              <div className="grid grid-cols-3 gap-4 mb-3">
                <div>
                  <p className="text-sm text-gray-600">Winner</p>
                  <p className="font-bold text-lg">{result.winning_variant}</p>
                </div>
                <div>
                  <p className="text-sm text-gray-600">Lift</p>
                  <p className={`font-bold text-lg ${result.lift_percentage > 0 ? 'text-green-600' : 'text-red-600'}`}>
                    {result.lift_percentage > 0 ? '+' : ''}{result.lift_percentage.toFixed(1)}%
                  </p>
                </div>
                <div>
                  <p className="text-sm text-gray-600">Confidence</p>
                  <p className="font-bold text-lg">
                    {(result.confidence_level * 100).toFixed(0)}%
                  </p>
                </div>
              </div>

              <button className="w-full py-2 bg-blue-50 text-blue-600 rounded hover:bg-blue-100 transition text-sm font-semibold">
                View Full Analysis →
              </button>
            </div>
          ))}
        </div>
      )}
    </div>
  );
};

// ============================================
// WebSocket Connection Status
// ============================================

export const ConnectionStatusIndicator: React.FC = () => {
  const { connected, connecting, error, reconnect } = useConnectionStatus();

  if (connected) {
    return (
      <div className="fixed bottom-4 left-4 bg-green-100 text-green-800 px-4 py-2 rounded-lg shadow-md flex items-center">
        <div className="w-2 h-2 bg-green-500 rounded-full mr-2 animate-pulse"></div>
        <span className="text-sm font-medium">Live Updates Active</span>
      </div>
    );
  }

  if (connecting) {
    return (
      <div className="fixed bottom-4 left-4 bg-yellow-100 text-yellow-800 px-4 py-2 rounded-lg shadow-md flex items-center">
        <div className="w-2 h-2 bg-yellow-500 rounded-full mr-2 animate-ping"></div>
        <span className="text-sm font-medium">Connecting...</span>
      </div>
    );
  }

  return (
    <div className="fixed bottom-4 left-4 bg-red-100 text-red-800 px-4 py-2 rounded-lg shadow-md">
      <div className="flex items-center mb-2">
        <div className="w-2 h-2 bg-red-500 rounded-full mr-2"></div>
        <span className="text-sm font-medium">Disconnected</span>
      </div>
      {error && <p className="text-xs mb-2">{error}</p>}
      <button
        onClick={reconnect}
        className="text-xs bg-red-200 hover:bg-red-300 px-3 py-1 rounded transition"
      >
        Reconnect
      </button>
    </div>
  );
};

// ============================================
// AI Notifications Center
// ============================================

interface NotificationsCenterProps {
  userId: number | string;
}

export const AINotificationsCenter: React.FC<NotificationsCenterProps> = ({ userId }) => {
  const { notifications, unreadCount, markAsRead, clearNotifications } = useAINotifications(userId);
  const [isOpen, setIsOpen] = useState(false);

  return (
    <div className="relative">
      {/* Bell Icon */}
      <button
        onClick={() => setIsOpen(!isOpen)}
        className="relative p-2 hover:bg-gray-100 rounded-full transition"
      >
        <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
        </svg>
        {unreadCount > 0 && (
          <span className="absolute top-0 right-0 w-5 h-5 bg-red-500 text-white text-xs rounded-full flex items-center justify-center">
            {unreadCount}
          </span>
        )}
      </button>

      {/* Dropdown */}
      {isOpen && (
        <div className="absolute right-0 mt-2 w-96 bg-white rounded-lg shadow-xl z-50 max-h-96 overflow-hidden flex flex-col">
          <div className="p-4 border-b flex justify-between items-center">
            <h3 className="font-bold">AI Notifications</h3>
            {notifications.length > 0 && (
              <button
                onClick={() => {
                  clearNotifications();
                  setIsOpen(false);
                }}
                className="text-sm text-red-600 hover:text-red-800"
              >
                Clear all
              </button>
            )}
          </div>

          <div className="overflow-y-auto flex-1">
            {notifications.length === 0 ? (
              <p className="text-gray-500 text-center py-8">No notifications</p>
            ) : (
              notifications.map((notif) => (
                <div
                  key={notif.id}
                  className={`p-4 border-b hover:bg-gray-50 cursor-pointer ${
                    !notif.read ? 'bg-blue-50' : ''
                  }`}
                  onClick={() => markAsRead(notif.id)}
                >
                  <p className="text-sm font-medium">{notif.message || notif.type}</p>
                  <p className="text-xs text-gray-500 mt-1">
                    {notif.timestamp.toLocaleString()}
                  </p>
                </div>
              ))
            )}
          </div>
        </div>
      )}
    </div>
  );
};
