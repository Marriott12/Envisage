<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

// Default user channel
Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// AI User-specific channels
Broadcast::channel('ai.user.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});

// AI Chatbot conversation channels
Broadcast::channel('ai.chat.{conversationId}', function ($user, $conversationId) {
    // Check if user owns this conversation
    return \App\Models\ChatConversation::where('id', $conversationId)
        ->where('user_id', $user->id)
        ->exists();
});

// Fraud detection - Seller channel
Broadcast::channel('ai.fraud.seller.{sellerId}', function ($user, $sellerId) {
    // Only the seller can access their fraud alerts
    return (int) $user->id === (int) $sellerId && $user->hasRole('seller');
});

// Fraud detection - Admin channel
Broadcast::channel('ai.fraud.admin', function ($user) {
    // Only admins and moderators can access all fraud alerts
    return $user->hasAnyRole(['admin', 'moderator']);
});

// Sentiment Analysis - Seller channel
Broadcast::channel('ai.sentiment.seller.{sellerId}', function ($user, $sellerId) {
    // Only the seller can access their sentiment analysis results
    return (int) $user->id === (int) $sellerId && $user->hasRole('seller');
});

// A/B Testing - Admin channel
Broadcast::channel('ai.abtest.admin', function ($user) {
    // Only admins and data analysts can access A/B test results
    return $user->hasAnyRole(['admin', 'data_analyst']);
});

// General AI notifications channel (budget alerts, system notifications)
Broadcast::channel('ai.notifications.user.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});
