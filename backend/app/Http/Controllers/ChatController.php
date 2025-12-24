<?php

namespace App\Http\Controllers;

use App\Services\NLPService;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    protected $nlpService;

    public function __construct(NLPService $nlpService)
    {
        $this->nlpService = $nlpService;
    }

    /**
     * Chat with AI shopping assistant
     */
    public function chat(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:1000',
            'conversation_id' => 'nullable|string',
        ]);

        $userId = $request->user()->id ?? null;
        $message = $request->input('message');
        $conversationId = $request->input('conversation_id');

        $response = $this->nlpService->chatWithAssistant($userId, $message, $conversationId);

        return response()->json([
            'success' => true,
            'data' => $response,
        ]);
    }

    /**
     * Semantic search
     */
    public function semanticSearch(Request $request)
    {
        $request->validate([
            'query' => 'required|string|max:500',
            'limit' => 'integer|min:1|max:50',
            'filters' => 'array',
        ]);

        $query = $request->input('query');
        $limit = $request->input('limit', 20);
        $filters = $request->input('filters', []);

        $results = $this->nlpService->semanticSearch($query, $limit, $filters);

        return response()->json([
            'success' => true,
            'data' => $results,
        ]);
    }

    /**
     * Extract intent from query
     */
    public function extractIntent(Request $request)
    {
        $request->validate([
            'query' => 'required|string|max:500',
        ]);

        $query = $request->input('query');

        $intent = $this->nlpService->extractIntent($query);

        return response()->json([
            'success' => true,
            'data' => $intent,
        ]);
    }

    /**
     * Autocomplete suggestions
     */
    public function autocomplete(Request $request)
    {
        $request->validate([
            'query' => 'required|string|max:100',
            'limit' => 'integer|min:1|max:20',
        ]);

        $query = $request->input('query');
        $limit = $request->input('limit', 10);

        $suggestions = $this->nlpService->getAutocompleteSuggestions($query, $limit);

        return response()->json([
            'success' => true,
            'suggestions' => $suggestions,
        ]);
    }

    /**
     * Analyze sentiment
     */
    public function sentiment(Request $request)
    {
        $request->validate([
            'text' => 'required|string|max:5000',
        ]);

        $text = $request->input('text');

        $sentiment = $this->nlpService->analyzeSentiment($text);

        return response()->json([
            'success' => true,
            'data' => $sentiment,
        ]);
    }
}
