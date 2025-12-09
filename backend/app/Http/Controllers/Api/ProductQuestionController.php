<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductQuestion;
use App\Models\ProductAnswer;
use App\Models\QuestionUpvote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProductQuestionController extends Controller
{
    public function index($productId)
    {
        $questions = ProductQuestion::where('product_id', $productId)
            ->with(['user', 'answers.user', 'upvotes'])
            ->withCount(['answers', 'upvotes'])
            ->orderByDesc('upvotes_count')
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json($questions);
    }

    public function store(Request $request, $productId)
    {
        $this->middleware('auth:sanctum');
        
        $request->validate([
            'question' => 'required|string|max:500',
        ]);

        $product = Product::findOrFail($productId);

        $question = ProductQuestion::create([
            'product_id' => $product->id,
            'user_id' => Auth::id(),
            'question' => $request->question,
        ]);

        // TODO: Notify seller about new question
        
        return response()->json([
            'question' => $question->load(['user', 'answers']),
        ], 201);
    }

    public function storeAnswer(Request $request, $questionId)
    {
        $this->middleware('auth:sanctum');
        
        $request->validate([
            'answer' => 'required|string|max:1000',
        ]);

        $question = ProductQuestion::with('product')->findOrFail($questionId);
        $userId = Auth::id();
        $isSeller = $question->product->seller_id === $userId;

        $answer = ProductAnswer::create([
            'question_id' => $question->id,
            'user_id' => $userId,
            'answer' => $request->answer,
            'is_seller' => $isSeller,
        ]);

        // TODO: Notify question asker about new answer
        
        return response()->json([
            'answer' => $answer->load('user'),
        ], 201);
    }

    public function upvote($questionId)
    {
        $this->middleware('auth:sanctum');
        
        $userId = Auth::id();
        $question = ProductQuestion::findOrFail($questionId);

        if ($question->hasUpvoted($userId)) {
            // Remove upvote
            QuestionUpvote::where('question_id', $questionId)
                ->where('user_id', $userId)
                ->delete();
            
            return response()->json([
                'message' => 'Upvote removed',
                'upvoted' => false,
            ]);
        } else {
            // Add upvote
            QuestionUpvote::create([
                'question_id' => $questionId,
                'user_id' => $userId,
            ]);
            
            return response()->json([
                'message' => 'Question upvoted',
                'upvoted' => true,
            ]);
        }
    }

    public function markHelpful($answerId)
    {
        $this->middleware('auth:sanctum');
        
        $answer = ProductAnswer::with('question')->findOrFail($answerId);
        $userId = Auth::id();

        // Only question asker can mark answers as helpful
        if ($answer->question->user_id !== $userId) {
            return response()->json([
                'message' => 'Only the question asker can mark answers as helpful',
            ], 403);
        }

        $answer->update([
            'is_helpful' => !$answer->is_helpful,
            'helpful_count' => $answer->is_helpful ? $answer->helpful_count + 1 : max(0, $answer->helpful_count - 1),
        ]);

        return response()->json([
            'answer' => $answer,
        ]);
    }
}
