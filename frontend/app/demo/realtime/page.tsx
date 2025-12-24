/**
 * Real-Time AI Features Demo Page
 * 
 * Demonstrates all WebSocket-powered AI features in action
 * 
 * @route /demo/realtime
 */

'use client';

import React, { useState } from 'react';
import {
  RealtimeRecommendationsPanel,
  FraudAlertDashboard,
  SentimentAnalysisMonitor,
  RealtimeChatbotWidget,
  ABTestResultsDashboard,
  ConnectionStatusIndicator,
  AINotificationsCenter,
} from '@/components/AIRealtimeComponents';

export default function RealtimeDemoPage() {
  const [showChatbot, setShowChatbot] = useState(false);
  const [selectedTab, setSelectedTab] = useState('recommendations');

  // Demo user data (replace with actual auth data)
  const userId = 1;
  const sellerId = 1;
  const isAdmin = true;
  const conversationId = `conv_${userId}_${Date.now()}`;

  const tabs = [
    { id: 'recommendations', label: 'AI Recommendations', icon: '🎯' },
    { id: 'fraud', label: 'Fraud Detection', icon: '🛡️' },
    { id: 'sentiment', label: 'Sentiment Analysis', icon: '😊' },
    { id: 'abtest', label: 'A/B Tests', icon: '📊' },
  ];

  return (
    <div className="min-h-screen bg-gray-50">
      {/* Header */}
      <header className="bg-white shadow-sm sticky top-0 z-40">
        <div className="max-w-7xl mx-auto px-4 py-4 flex justify-between items-center">
          <div>
            <h1 className="text-2xl font-bold text-gray-900">
              Real-Time AI Features Demo
            </h1>
            <p className="text-sm text-gray-600">
              Live WebSocket-powered AI capabilities
            </p>
          </div>
          <div className="flex items-center gap-4">
            <AINotificationsCenter userId={userId} />
            <button
              onClick={() => setShowChatbot(!showChatbot)}
              className="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition flex items-center gap-2"
            >
              <span>💬</span>
              <span>AI Chat</span>
            </button>
          </div>
        </div>
      </header>

      {/* Navigation Tabs */}
      <div className="bg-white border-b sticky top-[72px] z-30">
        <div className="max-w-7xl mx-auto px-4">
          <nav className="flex space-x-4 overflow-x-auto">
            {tabs.map((tab) => (
              <button
                key={tab.id}
                onClick={() => setSelectedTab(tab.id)}
                className={`px-4 py-3 font-medium text-sm whitespace-nowrap border-b-2 transition ${
                  selectedTab === tab.id
                    ? 'border-blue-600 text-blue-600'
                    : 'border-transparent text-gray-600 hover:text-gray-900 hover:border-gray-300'
                }`}
              >
                <span className="mr-2">{tab.icon}</span>
                {tab.label}
              </button>
            ))}
          </nav>
        </div>
      </div>

      {/* Main Content */}
      <main className="max-w-7xl mx-auto px-4 py-8">
        {/* Feature Guide */}
        <div className="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
          <h2 className="font-bold text-blue-900 mb-2">🚀 Real-Time Features</h2>
          <p className="text-blue-800 text-sm">
            All updates happen automatically via WebSocket connections. No page refresh needed!
            Watch as AI recommendations, fraud alerts, sentiment analysis, and A/B test results
            stream in real-time.
          </p>
        </div>

        {/* Tab Content */}
        <div className="space-y-6">
          {selectedTab === 'recommendations' && (
            <div>
              <RealtimeRecommendationsPanel
                userId={userId}
                onProductClick={(productId) => {
                  console.log('Product clicked:', productId);
                  alert(`Navigating to product ${productId}`);
                }}
              />
              <div className="mt-6 bg-white rounded-lg shadow-md p-6">
                <h3 className="font-bold text-lg mb-3">How It Works</h3>
                <ul className="space-y-2 text-sm text-gray-700">
                  <li className="flex items-start">
                    <span className="text-blue-600 mr-2">1.</span>
                    Click "Generate" to trigger AI recommendation engine
                  </li>
                  <li className="flex items-start">
                    <span className="text-blue-600 mr-2">2.</span>
                    Backend processes using selected algorithm (Neural, Bandit, etc.)
                  </li>
                  <li className="flex items-start">
                    <span className="text-blue-600 mr-2">3.</span>
                    Results broadcast in real-time via WebSocket
                  </li>
                  <li className="flex items-start">
                    <span className="text-blue-600 mr-2">4.</span>
                    UI updates automatically without page refresh
                  </li>
                </ul>
              </div>
            </div>
          )}

          {selectedTab === 'fraud' && (
            <div>
              <FraudAlertDashboard
                sellerId={sellerId}
                isAdmin={isAdmin}
              />
              <div className="mt-6 bg-white rounded-lg shadow-md p-6">
                <h3 className="font-bold text-lg mb-3">Fraud Detection Features</h3>
                <ul className="space-y-2 text-sm text-gray-700">
                  <li className="flex items-start">
                    <span className="text-red-600 mr-2">🔴</span>
                    <strong>Critical Alerts:</strong> Automatic audio alert + browser notification
                  </li>
                  <li className="flex items-start">
                    <span className="text-orange-600 mr-2">🟠</span>
                    <strong>High Risk:</strong> Browser notification for immediate attention
                  </li>
                  <li className="flex items-start">
                    <span className="text-yellow-600 mr-2">🟡</span>
                    <strong>Medium Risk:</strong> Silent alert in dashboard
                  </li>
                  <li className="flex items-start">
                    <span className="text-blue-600 mr-2">🔵</span>
                    <strong>Low Risk:</strong> Logged for review
                  </li>
                </ul>
                <div className="mt-4 bg-gray-50 rounded p-3">
                  <p className="text-xs text-gray-600">
                    <strong>Note:</strong> Fraud alerts are sent in real-time as suspicious
                    transactions are detected. Multi-channel support allows both sellers and
                    admins to receive alerts simultaneously.
                  </p>
                </div>
              </div>
            </div>
          )}

          {selectedTab === 'sentiment' && (
            <div>
              <SentimentAnalysisMonitor
                sellerId={sellerId}
              />
              <div className="mt-6 bg-white rounded-lg shadow-md p-6">
                <h3 className="font-bold text-lg mb-3">Sentiment Analysis</h3>
                <p className="text-sm text-gray-700 mb-4">
                  Real-time sentiment analysis of product reviews using advanced NLP.
                  Automatically detects positive, negative, and neutral sentiment,
                  and identifies fake reviews.
                </p>
                <div className="grid grid-cols-3 gap-4 text-center">
                  <div className="bg-green-50 rounded p-3">
                    <div className="text-2xl mb-1">😊</div>
                    <p className="text-sm font-medium text-green-800">Positive</p>
                  </div>
                  <div className="bg-gray-50 rounded p-3">
                    <div className="text-2xl mb-1">😐</div>
                    <p className="text-sm font-medium text-gray-800">Neutral</p>
                  </div>
                  <div className="bg-red-50 rounded p-3">
                    <div className="text-2xl mb-1">😞</div>
                    <p className="text-sm font-medium text-red-800">Negative</p>
                  </div>
                </div>
              </div>
            </div>
          )}

          {selectedTab === 'abtest' && (
            <div>
              <ABTestResultsDashboard isAdmin={isAdmin} />
              <div className="mt-6 bg-white rounded-lg shadow-md p-6">
                <h3 className="font-bold text-lg mb-3">A/B Testing Features</h3>
                <ul className="space-y-2 text-sm text-gray-700">
                  <li className="flex items-start">
                    <span className="text-blue-600 mr-2">📊</span>
                    Real-time test result updates as experiments conclude
                  </li>
                  <li className="flex items-start">
                    <span className="text-blue-600 mr-2">📈</span>
                    Statistical significance indicators
                  </li>
                  <li className="flex items-start">
                    <span className="text-blue-600 mr-2">🎯</span>
                    Lift percentage and confidence levels
                  </li>
                  <li className="flex items-start">
                    <span className="text-blue-600 mr-2">🔔</span>
                    Browser notifications for significant results
                  </li>
                </ul>
                <div className="mt-4 bg-green-50 border border-green-200 rounded p-3">
                  <p className="text-sm text-green-800">
                    <strong>✓ Significant results</strong> are highlighted and trigger
                    notifications so you can implement winning variants immediately.
                  </p>
                </div>
              </div>
            </div>
          )}
        </div>

        {/* Technical Details */}
        <div className="mt-8 bg-white rounded-lg shadow-md p-6">
          <h3 className="font-bold text-lg mb-4">Technical Implementation</h3>
          <div className="grid md:grid-cols-2 gap-6">
            <div>
              <h4 className="font-semibold mb-2 text-blue-600">Backend Events</h4>
              <ul className="text-sm space-y-1 text-gray-700">
                <li>• RecommendationGenerated</li>
                <li>• FraudAlertCreated</li>
                <li>• SentimentAnalysisComplete</li>
                <li>• ChatbotResponseReady</li>
                <li>• ABTestWinnerDetermined</li>
              </ul>
            </div>
            <div>
              <h4 className="font-semibold mb-2 text-blue-600">Private Channels</h4>
              <ul className="text-sm space-y-1 text-gray-700">
                <li>• ai.user.{'{userId}'}</li>
                <li>• ai.fraud.seller.{'{sellerId}'}</li>
                <li>• ai.fraud.admin</li>
                <li>• ai.sentiment.seller.{'{sellerId}'}</li>
                <li>• ai.chat.{'{conversationId}'}</li>
                <li>• ai.abtest.admin</li>
                <li>• ai.notifications.user.{'{userId}'}</li>
              </ul>
            </div>
          </div>
          <div className="mt-4 bg-gray-50 rounded p-3">
            <p className="text-xs text-gray-600">
              <strong>Stack:</strong> Laravel Echo + Pusher (or Redis) + React Hooks + TypeScript
            </p>
          </div>
        </div>
      </main>

      {/* Chatbot Widget */}
      {showChatbot && (
        <RealtimeChatbotWidget
          conversationId={conversationId}
          onClose={() => setShowChatbot(false)}
        />
      )}

      {/* Connection Status */}
      <ConnectionStatusIndicator />
    </div>
  );
}
