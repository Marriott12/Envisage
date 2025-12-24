<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use App\Models\Product;
use App\Models\User;
use App\Models\Order;
use App\Models\ChatMessage;

/**
 * Natural Language Processing Service
 * 
 * Features:
 * - Semantic search with BERT/Sentence-BERT
 * - Conversational AI shopping assistant
 * - Intent recognition and entity extraction
 * - Multi-turn dialogue management
 * - Query understanding and expansion
 * - Sentiment analysis
 */
class NLPService
{
    protected $mlServiceUrl;
    protected $conversationMemory = [];

    public function __construct()
    {
        $this->mlServiceUrl = config('services.ml.url', env('ML_SERVICE_URL', 'http://localhost:5000'));
    }

    /**
     * Semantic search using sentence embeddings
     * Understands meaning beyond keyword matching
     */
    public function semanticSearch($query, $limit = 20, $filters = [])
    {
        $cacheKey = "semantic_search:" . md5($query . json_encode($filters));

        return Cache::remember($cacheKey, 3600, function () use ($query, $limit, $filters) {
            try {
                // Get query embedding from ML service
                $response = Http::timeout(10)->post("{$this->mlServiceUrl}/api/nlp/semantic-search", [
                    'query' => $query,
                    'limit' => $limit,
                    'filters' => $filters,
                    'model' => 'sentence_bert',
                ]);

                if ($response->successful()) {
                    $results = $response->json()['results'];
                    
                    $productIds = array_column($results, 'product_id');
                    $scores = array_column($results, 'score', 'product_id');

                    $products = Product::whereIn('id', $productIds)->get();

                    foreach ($products as $product) {
                        $product->relevance_score = $scores[$product->id] ?? 0;
                    }

                    return $products->sortByDesc('relevance_score')->values();
                }
            } catch (\Exception $e) {
                \Log::warning("Semantic search failed: " . $e->getMessage());
            }

            // Fallback to traditional search
            return $this->fallbackSearch($query, $limit, $filters);
        });
    }

    /**
     * Conversational AI shopping assistant
     */
    public function chatWithAssistant($userId, $message, $conversationId = null)
    {
        // Get conversation history
        $history = $this->getConversationHistory($userId, $conversationId);

        // Add current message
        $history[] = ['role' => 'user', 'content' => $message];

        try {
            // Call AI service
            $response = Http::timeout(15)->post("{$this->mlServiceUrl}/api/nlp/chat", [
                'user_id' => $userId,
                'conversation_id' => $conversationId ?? uniqid('conv_'),
                'message' => $message,
                'history' => array_slice($history, -10), // Last 10 messages
                'user_context' => $this->getUserContext($userId),
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                // Save conversation
                $this->saveConversation($userId, $conversationId ?? $data['conversation_id'], $message, $data['response']);

                // Execute actions if any
                if (isset($data['actions'])) {
                    $actionResults = $this->executeActions($data['actions'], $userId);
                    $data['action_results'] = $actionResults;
                }

                return $data;
            }
        } catch (\Exception $e) {
            \Log::error("Chat assistant failed: " . $e->getMessage());
        }

        // Fallback response
        return $this->getFallbackResponse($message);
    }

    /**
     * Extract intent and entities from user query
     */
    public function extractIntent($query)
    {
        try {
            $response = Http::timeout(5)->post("{$this->mlServiceUrl}/api/nlp/intent", [
                'query' => $query,
            ]);

            if ($response->successful()) {
                return $response->json();
            }
        } catch (\Exception $e) {
            \Log::warning("Intent extraction failed: " . $e->getMessage());
        }

        // Fallback to rule-based intent detection
        return $this->ruleBasedIntentExtraction($query);
    }

    /**
     * Rule-based intent extraction (fallback)
     */
    protected function ruleBasedIntentExtraction($query)
    {
        $query = strtolower($query);
        $intent = 'search';
        $entities = [];

        // Detect intent patterns
        if (preg_match('/\b(buy|purchase|order|add to cart)\b/', $query)) {
            $intent = 'purchase';
        } elseif (preg_match('/\b(track|where is|status of|my order)\b/', $query)) {
            $intent = 'track_order';
        } elseif (preg_match('/\b(return|refund|cancel)\b/', $query)) {
            $intent = 'return';
        } elseif (preg_match('/\b(recommend|suggest|best|top)\b/', $query)) {
            $intent = 'recommendation';
        } elseif (preg_match('/\b(compare|difference|vs|versus)\b/', $query)) {
            $intent = 'compare';
        } elseif (preg_match('/\?(what|how|when|where|why|which)/', $query)) {
            $intent = 'question';
        }

        // Extract price entities
        if (preg_match('/under\s+\$?(\d+(?:\.\d{2})?)/', $query, $matches)) {
            $entities['max_price'] = floatval($matches[1]);
        }
        if (preg_match('/over\s+\$?(\d+(?:\.\d{2})?)/', $query, $matches)) {
            $entities['min_price'] = floatval($matches[1]);
        }
        if (preg_match('/\$?(\d+(?:\.\d{2})?)\s*-\s*\$?(\d+(?:\.\d{2})?)/', $query, $matches)) {
            $entities['min_price'] = floatval($matches[1]);
            $entities['max_price'] = floatval($matches[2]);
        }

        // Extract color entities
        $colors = ['red', 'blue', 'green', 'black', 'white', 'yellow', 'orange', 'purple', 'pink', 'brown'];
        foreach ($colors as $color) {
            if (strpos($query, $color) !== false) {
                $entities['color'] = $color;
                break;
            }
        }

        // Extract size entities
        $sizes = ['small', 'medium', 'large', 'xl', 'xxl', 'xs'];
        foreach ($sizes as $size) {
            if (preg_match('/\b' . $size . '\b/', $query)) {
                $entities['size'] = strtoupper($size);
                break;
            }
        }

        return [
            'intent' => $intent,
            'entities' => $entities,
            'confidence' => 0.7,
        ];
    }

    /**
     * Query expansion for better search results
     */
    public function expandQuery($query)
    {
        try {
            $response = Http::timeout(5)->post("{$this->mlServiceUrl}/api/nlp/expand-query", [
                'query' => $query,
            ]);

            if ($response->successful()) {
                return $response->json()['expanded_queries'];
            }
        } catch (\Exception $e) {
            \Log::warning("Query expansion failed: " . $e->getMessage());
        }

        // Fallback: simple synonym expansion
        return $this->synonymExpansion($query);
    }

    /**
     * Simple synonym expansion
     */
    protected function synonymExpansion($query)
    {
        $synonyms = [
            'cheap' => ['affordable', 'budget', 'inexpensive'],
            'expensive' => ['premium', 'luxury', 'high-end'],
            'phone' => ['smartphone', 'mobile', 'cell phone'],
            'laptop' => ['notebook', 'computer'],
            'shoe' => ['footwear', 'sneaker'],
        ];

        $words = explode(' ', strtolower($query));
        $expanded = [$query];

        foreach ($words as $word) {
            if (isset($synonyms[$word])) {
                foreach ($synonyms[$word] as $synonym) {
                    $expandedQuery = str_replace($word, $synonym, $query);
                    $expanded[] = $expandedQuery;
                }
            }
        }

        return array_unique($expanded);
    }

    /**
     * Analyze sentiment of text (reviews, messages)
     */
    public function analyzeSentiment($text)
    {
        try {
            $response = Http::timeout(5)->post("{$this->mlServiceUrl}/api/nlp/sentiment", [
                'text' => $text,
            ]);

            if ($response->successful()) {
                return $response->json();
            }
        } catch (\Exception $e) {
            \Log::warning("Sentiment analysis failed: " . $e->getMessage());
        }

        // Fallback: basic sentiment analysis
        return $this->basicSentimentAnalysis($text);
    }

    /**
     * Basic sentiment analysis (fallback)
     */
    protected function basicSentimentAnalysis($text)
    {
        $positiveWords = ['good', 'great', 'excellent', 'amazing', 'wonderful', 'love', 'best', 'perfect', 'awesome', 'fantastic'];
        $negativeWords = ['bad', 'terrible', 'horrible', 'awful', 'worst', 'hate', 'poor', 'disappointing', 'useless'];

        $text = strtolower($text);
        $positiveCount = 0;
        $negativeCount = 0;

        foreach ($positiveWords as $word) {
            $positiveCount += substr_count($text, $word);
        }

        foreach ($negativeWords as $word) {
            $negativeCount += substr_count($text, $word);
        }

        $totalWords = str_word_count($text);
        $score = ($positiveCount - $negativeCount) / max($totalWords, 1);

        if ($score > 0.1) {
            $sentiment = 'positive';
        } elseif ($score < -0.1) {
            $sentiment = 'negative';
        } else {
            $sentiment = 'neutral';
        }

        return [
            'sentiment' => $sentiment,
            'score' => $score,
            'confidence' => min(abs($score) * 2, 1.0),
        ];
    }

    /**
     * Extract key phrases from text
     */
    public function extractKeyPhrases($text, $limit = 5)
    {
        try {
            $response = Http::timeout(5)->post("{$this->mlServiceUrl}/api/nlp/keyphrases", [
                'text' => $text,
                'limit' => $limit,
            ]);

            if ($response->successful()) {
                return $response->json()['keyphrases'];
            }
        } catch (\Exception $e) {
            \Log::warning("Keyphrase extraction failed: " . $e->getMessage());
        }

        // Fallback: simple word frequency
        return $this->extractTopWords($text, $limit);
    }

    /**
     * Extract top words by frequency
     */
    protected function extractTopWords($text, $limit)
    {
        $stopWords = ['the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by', 'from', 'is', 'was', 'are', 'were'];
        
        $words = str_word_count(strtolower($text), 1);
        $words = array_diff($words, $stopWords);
        $wordCounts = array_count_values($words);
        arsort($wordCounts);

        return array_slice(array_keys($wordCounts), 0, $limit);
    }

    /**
     * Get conversation history
     */
    protected function getConversationHistory($userId, $conversationId)
    {
        if (!$conversationId) {
            return [];
        }

        return ChatMessage::where('user_id', $userId)
            ->where('conversation_id', $conversationId)
            ->orderBy('created_at')
            ->limit(20)
            ->get()
            ->map(function ($msg) {
                return [
                    'role' => $msg->role,
                    'content' => $msg->content,
                ];
            })
            ->toArray();
    }

    /**
     * Save conversation message
     */
    protected function saveConversation($userId, $conversationId, $userMessage, $assistantResponse)
    {
        ChatMessage::create([
            'user_id' => $userId,
            'conversation_id' => $conversationId,
            'role' => 'user',
            'content' => $userMessage,
        ]);

        ChatMessage::create([
            'user_id' => $userId,
            'conversation_id' => $conversationId,
            'role' => 'assistant',
            'content' => $assistantResponse['message'] ?? $assistantResponse,
        ]);
    }

    /**
     * Get user context for personalization
     */
    protected function getUserContext($userId)
    {
        $user = User::find($userId);
        
        if (!$user) {
            return [];
        }

        $recentOrders = Order::where('user_id', $userId)
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        return [
            'user_name' => $user->name,
            'recent_purchases' => $recentOrders->pluck('id')->toArray(),
            'preferences' => $user->preferences ?? [],
        ];
    }

    /**
     * Execute actions from assistant
     */
    protected function executeActions($actions, $userId)
    {
        $results = [];

        foreach ($actions as $action) {
            switch ($action['type']) {
                case 'search_products':
                    $results[] = $this->semanticSearch($action['query'], $action['limit'] ?? 5);
                    break;
                case 'get_recommendations':
                    $recommendationService = app(AdvancedRecommendationService::class);
                    $results[] = $recommendationService->getNeuralRecommendations($userId, $action['limit'] ?? 5);
                    break;
                case 'track_order':
                    $order = Order::where('user_id', $userId)
                        ->where('order_number', $action['order_number'])
                        ->first();
                    $results[] = $order ? $order->status : 'Order not found';
                    break;
            }
        }

        return $results;
    }

    /**
     * Fallback response
     */
    protected function getFallbackResponse($message)
    {
        $responses = [
            "I'm here to help! Could you tell me more about what you're looking for?",
            "I'd be happy to assist you. What products are you interested in?",
            "Let me help you find the perfect item. What are you shopping for today?",
        ];

        return [
            'response' => $responses[array_rand($responses)],
            'conversation_id' => uniqid('conv_'),
        ];
    }

    /**
     * Fallback traditional search
     */
    protected function fallbackSearch($query, $limit, $filters)
    {
        $searchService = app(SearchService::class);
        return $searchService->search($query, $filters, $limit);
    }

    /**
     * Generate product description using GPT
     */
    public function generateProductDescription($productData, $tone = 'professional')
    {
        try {
            $response = Http::timeout(15)->post("{$this->mlServiceUrl}/api/nlp/generate-description", [
                'product_name' => $productData['name'],
                'category' => $productData['category'] ?? '',
                'features' => $productData['features'] ?? [],
                'tone' => $tone,
                'length' => 'medium',
            ]);

            if ($response->successful()) {
                return $response->json()['description'];
            }
        } catch (\Exception $e) {
            \Log::warning("Description generation failed: " . $e->getMessage());
        }

        return null;
    }

    /**
     * Auto-complete search suggestions
     */
    public function getAutocompleteSuggestions($partialQuery, $limit = 10)
    {
        try {
            $response = Http::timeout(3)->post("{$this->mlServiceUrl}/api/nlp/autocomplete", [
                'query' => $partialQuery,
                'limit' => $limit,
            ]);

            if ($response->successful()) {
                return $response->json()['suggestions'];
            }
        } catch (\Exception $e) {
            \Log::debug("Autocomplete failed: " . $e->getMessage());
        }

        // Fallback to database search
        return Product::where('name', 'LIKE', "{$partialQuery}%")
            ->orWhere('description', 'LIKE', "%{$partialQuery}%")
            ->limit($limit)
            ->pluck('name')
            ->toArray();
    }
}
