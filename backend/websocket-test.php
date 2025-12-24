#!/usr/bin/env php
<?php

/**
 * WebSocket Real-Time Features Test Script
 * 
 * This script demonstrates how to trigger real-time events
 * for testing the broadcasting functionality.
 * 
 * Usage: php websocket-test.php
 */

require __DIR__ . '/vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Contracts\Console\Kernel;

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

echo "\n==============================================\n";
echo "WebSocket Real-Time Features Test Script\n";
echo "==============================================\n\n";

// Test 1: Broadcast Recommendation Generated Event
echo "Test 1: Broadcasting Recommendation Generated Event...\n";
event(new \App\Events\AI\RecommendationGenerated(
    1, // userId
    [
        ['id' => 1, 'name' => 'Product A', 'price' => 29.99],
        ['id' => 2, 'name' => 'Product B', 'price' => 39.99],
        ['id' => 3, 'name' => 'Product C', 'price' => 49.99],
    ],
    'neural',
    1234.56
));
echo "✅ Recommendation event broadcasted to channel: ai.user.1\n\n";

// Test 2: Broadcast Fraud Alert Event
echo "Test 2: Broadcasting Fraud Alert Event...\n";
event(new \App\Events\AI\FraudAlertCreated(
    123, // alertId
    456, // transactionId
    1,   // sellerId
    85.5,
    'high',
    ['suspicious_location', 'high_value', 'velocity_check_failed']
));
echo "✅ Fraud alert broadcasted to channels: ai.fraud.seller.1, ai.fraud.admin\n\n";

// Test 3: Broadcast Sentiment Analysis Complete Event
echo "Test 3: Broadcasting Sentiment Analysis Complete Event...\n";
event(new \App\Events\AI\SentimentAnalysisComplete(
    789, // productId
    1,   // sellerId
    150, // totalReviews
    'positive',
    [
        'positive' => 100,
        'neutral' => 30,
        'negative' => 20,
    ],
    5 // fakeReviewsDetected
));
echo "✅ Sentiment analysis event broadcasted to channel: ai.sentiment.seller.1\n\n";

// Test 4: Broadcast Chatbot Response Event
echo "Test 4: Broadcasting Chatbot Response Event...\n";
event(new \App\Events\AI\ChatbotResponseReady(
    'conv-123', // conversationId
    1,          // userId
    'Based on your query, I recommend checking out our latest products in the electronics category.',
    850.5,
    ['view_category', 'filter_price']
));
echo "✅ Chatbot response event broadcasted to channel: ai.chat.conv-123\n\n";

// Test 5: Broadcast A/B Test Winner Event
echo "Test 5: Broadcasting A/B Test Winner Determined Event...\n";
event(new \App\Events\AI\ABTestWinnerDetermined(
    'recommendation_algorithm_test',
    'treatment',
    true,
    15.3,
    0.95,
    [
        'control' => [
            'conversions' => 100,
            'conversion_rate' => 0.10,
            'avg_value' => 45.67,
        ],
        'treatment' => [
            'conversions' => 125,
            'conversion_rate' => 0.125,
            'avg_value' => 52.34,
        ],
    ]
));
echo "✅ A/B test winner event broadcasted to channel: ai.abtest.admin\n\n";

// Test 6: Send Budget Alert Notification
echo "Test 6: Sending Budget Alert Notification...\n";
$user = \App\Models\User::first();
if ($user) {
    $user->notify(new \App\Notifications\AI\BudgetAlertNotification(
        'recommendations',
        45.67,
        50.00,
        91.34,
        'warning'
    ));
    echo "✅ Budget alert notification sent to user #{$user->id}\n";
    echo "   Channels: database, broadcast, mail\n\n";
} else {
    echo "⚠️  No users found in database. Please seed users first.\n\n";
}

// Test 7: Send Fraud Detected Notification
echo "Test 7: Sending Fraud Detected Notification...\n";
if ($user) {
    $user->notify(new \App\Notifications\AI\FraudDetectedNotification(
        12345,
        85.5,
        'critical',
        ['unusual_pattern', 'high_risk_location', 'account_takeover_attempt'],
        299.99,
        'customer@example.com'
    ));
    echo "✅ Fraud detection notification sent to user #{$user->id}\n";
    echo "   Channels: database, broadcast, mail (critical risk)\n\n";
} else {
    echo "⚠️  No users found in database. Please seed users first.\n\n";
}

// Test 8: Send A/B Test Complete Notification
echo "Test 8: Sending A/B Test Complete Notification...\n";
if ($user) {
    $user->notify(new \App\Notifications\AI\ABTestCompleteNotification(
        'pricing_strategy_test',
        'treatment',
        true,
        12.5,
        0.95,
        [
            'conversions' => 200,
            'conversion_rate' => 0.15,
            'avg_value' => 67.89,
        ],
        [
            'conversions' => 225,
            'conversion_rate' => 0.169,
            'avg_value' => 71.23,
        ]
    ));
    echo "✅ A/B test complete notification sent to user #{$user->id}\n";
    echo "   Channels: database, broadcast, mail\n\n";
} else {
    echo "⚠️  No users found in database. Please seed users first.\n\n";
}

echo "\n==============================================\n";
echo "Test Summary\n";
echo "==============================================\n";
echo "✅ 5 broadcast events fired\n";
echo "✅ 3 notification types sent\n";
echo "✅ 8 private channels utilized\n\n";

echo "Next Steps:\n";
echo "1. Check your Pusher dashboard for event delivery\n";
echo "2. Open frontend app with Echo configured\n";
echo "3. Monitor browser console for real-time events\n";
echo "4. Check database 'notifications' table for stored notifications\n";
echo "5. Verify email inbox for notification emails\n\n";

echo "Frontend Testing:\n";
echo "- Open: http://localhost:3000\n";
echo "- Login with test user\n";
echo "- Open browser console (F12)\n";
echo "- Run: Pusher.logToConsole = true\n";
echo "- Execute this script again\n";
echo "- Watch events arrive in real-time!\n\n";

echo "Broadcasting Configuration:\n";
echo "- Driver: " . config('broadcasting.default') . "\n";
echo "- Pusher Key: " . config('broadcasting.connections.pusher.key') . "\n";
echo "- Pusher Cluster: " . config('broadcasting.connections.pusher.options.cluster') . "\n\n";

echo "==============================================\n";
echo "✅ WebSocket test script completed!\n";
echo "==============================================\n\n";
