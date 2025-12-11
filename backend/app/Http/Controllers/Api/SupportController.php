<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Models\TicketMessage;
use Illuminate\Http\Request;

class SupportController extends Controller
{
    public function index()
    {
        $tickets = SupportTicket::with(['user', 'assignedTo'])
            ->where('user_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['success' => true, 'data' => $tickets]);
    }

    public function create(Request $request)
    {
        $request->validate([
            'subject' => 'required|string|max:255',
            'description' => 'required|string',
            'category' => 'required|in:general,order,payment,shipping,product,account,technical',
            'priority' => 'nullable|in:low,medium,high,urgent',
            'order_id' => 'nullable|exists:orders,id',
        ]);

        $ticket = SupportTicket::create([
            'user_id' => auth()->id(),
            'subject' => $request->subject,
            'description' => $request->description,
            'category' => $request->category,
            'priority' => $request->priority ?? 'medium',
            'order_id' => $request->order_id,
            'status' => 'open',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Support ticket created',
            'data' => $ticket,
        ], 201);
    }

    public function show($id)
    {
        $ticket = SupportTicket::with(['user', 'messages.user', 'order'])
            ->where('user_id', auth()->id())
            ->findOrFail($id);

        return response()->json(['success' => true, 'data' => $ticket]);
    }

    public function addMessage(Request $request, $id)
    {
        $request->validate([
            'message' => 'required|string',
        ]);

        $ticket = SupportTicket::where('user_id', auth()->id())->findOrFail($id);

        $message = TicketMessage::create([
            'ticket_id' => $ticket->id,
            'user_id' => auth()->id(),
            'message' => $request->message,
            'is_internal' => false,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Message added',
            'data' => $message,
        ], 201);
    }

    public function close($id)
    {
        $ticket = SupportTicket::where('user_id', auth()->id())->findOrFail($id);
        $ticket->update(['status' => 'closed', 'resolved_at' => now()]);

        return response()->json(['success' => true, 'message' => 'Ticket closed']);
    }
}
