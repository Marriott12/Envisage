/**
 * WebSocket Integration Test Component
 * 
 * Use this component to verify all real-time features are working correctly
 * 
 * @usage Add to any page to test WebSocket connectivity
 */

'use client';

import React, { useState, useEffect } from 'react';
import {
  useAIRecommendations,
  useFraudAlerts,
  useSentimentUpdates,
  useChatbot,
  useABTestResults,
  useAINotifications,
  useConnectionStatus,
} from '@/hooks/useAIEvents';

export default function WebSocketTestComponent() {
  const [logs, setLogs] = useState<string[]>([]);
  const [testResults, setTestResults] = useState<Record<string, boolean>>({});

  // Initialize all hooks
  const userId = 1;
  const sellerId = 1;
  const conversationId = `test_${Date.now()}`;

  const recommendations = useAIRecommendations(userId);
  const fraudAlerts = useFraudAlerts(sellerId, true);
  const sentiment = useSentimentUpdates(sellerId);
  const chatbot = useChatbot(conversationId);
  const abTests = useABTestResults(true);
  const notifications = useAINotifications(userId);
  const connection = useConnectionStatus();

  const addLog = (message: string, type: 'info' | 'success' | 'error' = 'info') => {
    const timestamp = new Date().toLocaleTimeString();
    const logMessage = `[${timestamp}] ${message}`;
    setLogs(prev => [logMessage, ...prev].slice(0, 100)); // Keep last 100 logs
    console.log(`[WebSocket Test] ${logMessage}`);

    if (type === 'success') {
      setTestResults(prev => ({ ...prev, [message]: true }));
    }
  };

  // Test 1: Connection Status
  useEffect(() => {
    if (connection.connected) {
      addLog('✅ WebSocket connection established', 'success');
    } else if (connection.connecting) {
      addLog('⏳ Connecting to WebSocket...', 'info');
    } else if (connection.error) {
      addLog(`❌ Connection error: ${connection.error}`, 'error');
    }
  }, [connection.connected, connection.connecting, connection.error]);

  // Test 2: Recommendations Hook
  useEffect(() => {
    if (recommendations.recommendations.length > 0) {
      addLog(`✅ Received ${recommendations.recommendations.length} recommendations`, 'success');
    }
    if (recommendations.error) {
      addLog(`❌ Recommendations error: ${recommendations.error.message}`, 'error');
    }
  }, [recommendations.recommendations, recommendations.error]);

  // Test 3: Fraud Alerts Hook
  useEffect(() => {
    if (fraudAlerts.alerts.length > 0) {
      addLog(`✅ Received ${fraudAlerts.alerts.length} fraud alerts`, 'success');
    }
  }, [fraudAlerts.alerts]);

  // Test 4: Sentiment Hook
  useEffect(() => {
    if (sentiment.sentimentData.length > 0) {
      addLog(`✅ Received sentiment analysis data`, 'success');
    }
  }, [sentiment.sentimentData]);

  // Test 5: Chatbot Hook
  useEffect(() => {
    if (chatbot.messages.length > 0) {
      addLog(`✅ Chatbot messages: ${chatbot.messages.length}`, 'success');
    }
    if (chatbot.error) {
      addLog(`❌ Chatbot error: ${chatbot.error.message}`, 'error');
    }
  }, [chatbot.messages, chatbot.error]);

  // Test 6: A/B Test Hook
  useEffect(() => {
    if (abTests.testResults.length > 0) {
      addLog(`✅ Received A/B test results`, 'success');
    }
  }, [abTests.testResults]);

  // Test 7: Notifications Hook
  useEffect(() => {
    if (notifications.notifications.length > 0) {
      addLog(`✅ Received ${notifications.notifications.length} notifications`, 'success');
    }
  }, [notifications.notifications]);

  // Auto-test functions
  const runTests = () => {
    addLog('🚀 Starting WebSocket integration tests...', 'info');
    
    // Test recommendations
    setTimeout(() => {
      addLog('Testing AI Recommendations...', 'info');
      recommendations.generate('neural', 5);
    }, 1000);

    // Test chatbot
    setTimeout(() => {
      addLog('Testing Chatbot...', 'info');
      chatbot.sendMessage('Hello, this is a test message');
    }, 2000);
  };

  return (
    <div className="fixed bottom-4 right-4 w-96 max-h-[600px] bg-white rounded-lg shadow-2xl border border-gray-200 flex flex-col">
      {/* Header */}
      <div className="bg-gradient-to-r from-blue-600 to-blue-800 text-white p-4 rounded-t-lg">
        <h3 className="font-bold text-lg">WebSocket Integration Test</h3>
        <p className="text-xs text-blue-100">Real-time feature diagnostics</p>
      </div>

      {/* Status Overview */}
      <div className="p-4 border-b bg-gray-50">
        <div className="grid grid-cols-2 gap-2 text-xs">
          <div className={`flex items-center ${connection.connected ? 'text-green-600' : 'text-red-600'}`}>
            <span className="mr-1">{connection.connected ? '🟢' : '🔴'}</span>
            Connection
          </div>
          <div className={`flex items-center ${recommendations.recommendations.length > 0 ? 'text-green-600' : 'text-gray-400'}`}>
            <span className="mr-1">{recommendations.recommendations.length > 0 ? '✅' : '⏳'}</span>
            Recommendations
          </div>
          <div className={`flex items-center ${fraudAlerts.alerts.length > 0 ? 'text-green-600' : 'text-gray-400'}`}>
            <span className="mr-1">{fraudAlerts.alerts.length > 0 ? '✅' : '⏳'}</span>
            Fraud Alerts
          </div>
          <div className={`flex items-center ${chatbot.messages.length > 0 ? 'text-green-600' : 'text-gray-400'}`}>
            <span className="mr-1">{chatbot.messages.length > 0 ? '✅' : '⏳'}</span>
            Chatbot
          </div>
          <div className={`flex items-center ${sentiment.sentimentData.length > 0 ? 'text-green-600' : 'text-gray-400'}`}>
            <span className="mr-1">{sentiment.sentimentData.length > 0 ? '✅' : '⏳'}</span>
            Sentiment
          </div>
          <div className={`flex items-center ${notifications.notifications.length > 0 ? 'text-green-600' : 'text-gray-400'}`}>
            <span className="mr-1">{notifications.notifications.length > 0 ? '✅' : '⏳'}</span>
            Notifications
          </div>
        </div>
      </div>

      {/* Test Controls */}
      <div className="p-4 border-b bg-white">
        <button
          onClick={runTests}
          className="w-full px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition text-sm font-medium"
        >
          Run Integration Tests
        </button>
      </div>

      {/* Logs */}
      <div className="flex-1 overflow-y-auto p-4 bg-gray-50">
        <div className="space-y-1">
          {logs.length === 0 ? (
            <p className="text-gray-400 text-xs text-center py-4">
              Click "Run Integration Tests" to start
            </p>
          ) : (
            logs.map((log, idx) => (
              <div
                key={idx}
                className={`text-xs font-mono p-2 rounded ${
                  log.includes('✅')
                    ? 'bg-green-50 text-green-800'
                    : log.includes('❌')
                    ? 'bg-red-50 text-red-800'
                    : log.includes('⏳')
                    ? 'bg-yellow-50 text-yellow-800'
                    : 'bg-gray-100 text-gray-700'
                }`}
              >
                {log}
              </div>
            ))
          )}
        </div>
      </div>

      {/* Footer */}
      <div className="p-3 border-t bg-white text-xs text-gray-600">
        <div className="flex justify-between items-center">
          <span>Logs: {logs.length}</span>
          <button
            onClick={() => setLogs([])}
            className="text-blue-600 hover:text-blue-800"
          >
            Clear Logs
          </button>
        </div>
      </div>
    </div>
  );
}

/**
 * Usage Instructions:
 * 
 * 1. Add to any page:
 * ```tsx
 * import WebSocketTestComponent from '@/components/WebSocketTestComponent';
 * 
 * export default function MyPage() {
 *   return (
 *     <>
 *       <div>Your page content</div>
 *       <WebSocketTestComponent />
 *     </>
 *   );
 * }
 * ```
 * 
 * 2. Or add to layout for global testing:
 * ```tsx
 * // app/layout.tsx
 * import WebSocketTestComponent from '@/components/WebSocketTestComponent';
 * 
 * export default function RootLayout({ children }) {
 *   return (
 *     <html>
 *       <body>
 *         {children}
 *         {process.env.NODE_ENV === 'development' && <WebSocketTestComponent />}
 *       </body>
 *     </html>
 *   );
 * }
 * ```
 * 
 * 3. Check results:
 * - Green ✅ = Feature working
 * - Red ❌ = Error detected
 * - Yellow ⏳ = Waiting for data
 * - Gray = Not tested yet
 */
