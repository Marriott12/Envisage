<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use Illuminate\Http\Request;

class LiveChatController extends Controller
{
    /**
     * Get user conversations
     */
    public function conversations(Request $request)
    {
        $user = $request->user();

        $conversations = ChatConversation::where('buyer_id', $user->id)
            ->orWhere('seller_id', $user->id)
            ->with(['buyer', 'seller', 'product', 'lastMessage'])
            ->orderBy('updated_at', 'desc')
            ->paginate(20);

        return response()->json($conversations);
    }

    /**
     * Start conversation with seller
     */
    public function startConversation(Request $request)
    {
        $request->validate([
            'seller_id' => 'required|integer|exists:users,id',
            'product_id' => 'required|integer|exists:products,id',
            'message' => 'required|string|max:1000',
        ]);

        $buyer = $request->user();

        // Check if conversation already exists
        $conversation = ChatConversation::where('buyer_id', $buyer->id)
            ->where('seller_id', $request->seller_id)
            ->where('product_id', $request->product_id)
            ->first();

        if (!$conversation) {
            $conversation = ChatConversation::create([
                'buyer_id' => $buyer->id,
                'seller_id' => $request->seller_id,
                'product_id' => $request->product_id,
                'status' => 'active',
            ]);
        }

        // Create message
        $message = ChatMessage::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $buyer->id,
            'message' => $request->message,
            'is_read' => false,
        ]);

        return response()->json([
            'conversation' => $conversation->load(['buyer', 'seller', 'product']),
            'message' => $message
        ]);
    }

    /**
     * Get conversation messages
     */
    public function messages(Request $request, $conversationId)
    {
        $user = $request->user();

        $conversation = ChatConversation::where('id', $conversationId)
            ->where(function ($query) use ($user) {
                $query->where('buyer_id', $user->id)
                      ->orWhere('seller_id', $user->id);
            })
            ->firstOrFail();

        $messages = ChatMessage::where('conversation_id', $conversationId)
            ->with('sender')
            ->orderBy('created_at', 'asc')
            ->paginate(50);

        // Mark messages as read
        ChatMessage::where('conversation_id', $conversationId)
            ->where('sender_id', '!=', $user->id)
            ->where('is_read', false)
            ->update(['is_read' => true, 'read_at' => now()]);

        return response()->json($messages);
    }

    /**
     * Send message
     */
    public function sendMessage(Request $request, $conversationId)
    {
        $request->validate([
            'message' => 'required|string|max:1000',
            'attachment' => 'nullable|file|max:5120', // 5MB
        ]);

        $user = $request->user();

        $conversation = ChatConversation::where('id', $conversationId)
            ->where(function ($query) use ($user) {
                $query->where('buyer_id', $user->id)
                      ->orWhere('seller_id', $user->id);
            })
            ->firstOrFail();

        $attachmentUrl = null;
        if ($request->hasFile('attachment')) {
            $attachmentUrl = $request->file('attachment')->store('chat_attachments', 'public');
        }

        $message = ChatMessage::create([
            'conversation_id' => $conversationId,
            'sender_id' => $user->id,
            'message' => $request->message,
            'attachment_url' => $attachmentUrl,
            'is_read' => false,
        ]);

        $conversation->touch();

        return response()->json([
            'message' => $message->load('sender')
        ]);
    }

    /**
     * Get unread count
     */
    public function unreadCount(Request $request)
    {
        $user = $request->user();

        $count = ChatMessage::whereHas('conversation', function ($query) use ($user) {
                $query->where('buyer_id', $user->id)
                      ->orWhere('seller_id', $user->id);
            })
            ->where('sender_id', '!=', $user->id)
            ->where('is_read', false)
            ->count();

        return response()->json([
            'unread_count' => $count
        ]);
    }

    /**
     * Close conversation
     */
    public function closeConversation(Request $request, $conversationId)
    {
        $user = $request->user();

        $conversation = ChatConversation::where('id', $conversationId)
            ->where(function ($query) use ($user) {
                $query->where('buyer_id', $user->id)
                      ->orWhere('seller_id', $user->id);
            })
            ->firstOrFail();

        $conversation->update([
            'status' => 'closed',
            'closed_at' => now(),
        ]);

        return response()->json([
            'message' => 'Conversation closed successfully'
        ]);
    }
}
