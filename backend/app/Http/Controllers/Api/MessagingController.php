<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use App\Events\MessageSent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class MessagingController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function conversations()
    {
        $userId = Auth::id();
        
        $conversations = Conversation::where('buyer_id', $userId)
            ->orWhere('seller_id', $userId)
            ->with(['buyer', 'seller', 'product', 'messages' => function($query) {
                $query->latest()->limit(1);
            }])
            ->orderBy('updated_at', 'desc')
            ->paginate(20);

        return response()->json($conversations);
    }

    public function show($id)
    {
        $userId = Auth::id();
        
        $conversation = Conversation::where('id', $id)
            ->where(function($query) use ($userId) {
                $query->where('buyer_id', $userId)
                      ->orWhere('seller_id', $userId);
            })
            ->with(['buyer', 'seller', 'product'])
            ->firstOrFail();

        $messages = $conversation->messages()
            ->with('sender')
            ->orderBy('created_at', 'asc')
            ->paginate(50);

        // Mark messages as read
        $conversation->markAsRead($userId);

        return response()->json([
            'conversation' => $conversation,
            'messages' => $messages,
        ]);
    }

    public function start(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'message' => 'required|string|max:5000',
        ]);

        $product = \App\Models\Product::findOrFail($request->product_id);
        $buyerId = Auth::id();
        $sellerId = $product->seller_id;

        if ($buyerId === $sellerId) {
            return response()->json([
                'message' => 'You cannot message yourself'
            ], 400);
        }

        // Check if conversation already exists
        $conversation = Conversation::where('product_id', $product->id)
            ->where('buyer_id', $buyerId)
            ->where('seller_id', $sellerId)
            ->first();

        if (!$conversation) {
            $conversation = Conversation::create([
                'product_id' => $product->id,
                'buyer_id' => $buyerId,
                'seller_id' => $sellerId,
            ]);
        }

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $buyerId,
            'message' => $request->message,
        ]);

        return response()->json([
            'conversation' => $conversation->load(['buyer', 'seller', 'product']),
            'message' => $message->load('sender'),
        ], 201);
    }

    public function sendMessage(Request $request, $conversationId)
    {
        $request->validate([
            'message' => 'required|string|max:5000',
            'attachments' => 'nullable|array|max:5',
            'attachments.*' => 'file|max:10240|mimes:jpg,jpeg,png,pdf,doc,docx',
        ]);

        $userId = Auth::id();
        
        $conversation = Conversation::where('id', $conversationId)
            ->where(function($query) use ($userId) {
                $query->where('buyer_id', $userId)
                      ->orWhere('seller_id', $userId);
            })
            ->firstOrFail();

        $attachments = [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('messages/attachments', 'public');
                $attachments[] = [
                    'name' => $file->getClientOriginalName(),
                    'path' => $path,
                    'size' => $file->getSize(),
                    'type' => $file->getMimeType(),
                ];
            }
        }

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $userId,
            'message' => $request->message,
            'attachments' => $attachments,
        ]);

        $conversation->touch(); // Update conversation timestamp

        // Broadcast the message to all participants in the conversation
        broadcast(new MessageSent($message->load('sender')))->toOthers();

        // TODO: Send email notification if recipient is offline

        return response()->json([
            'message' => $message->load('sender'),
        ], 201);
    }

    public function markAsRead($conversationId)
    {
        $userId = Auth::id();
        
        $conversation = Conversation::where('id', $conversationId)
            ->where(function($query) use ($userId) {
                $query->where('buyer_id', $userId)
                      ->orWhere('seller_id', $userId);
            })
            ->firstOrFail();

        $conversation->markAsRead($userId);

        return response()->json([
            'message' => 'Conversation marked as read',
        ]);
    }

    public function unreadCount()
    {
        $userId = Auth::id();
        
        $count = Message::whereHas('conversation', function($query) use ($userId) {
                $query->where('buyer_id', $userId)
                      ->orWhere('seller_id', $userId);
            })
            ->where('sender_id', '!=', $userId)
            ->where('is_read', false)
            ->count();

        return response()->json([
            'unread_count' => $count,
        ]);
    }
}
