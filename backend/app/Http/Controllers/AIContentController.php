<?php

namespace App\Http\Controllers;

use App\Services\AIContentGenerationService;
use Illuminate\Http\Request;
use App\Models\Product;

class AIContentController extends Controller
{
    protected $aiContentService;

    public function __construct(AIContentGenerationService $aiContentService)
    {
        $this->aiContentService = $aiContentService;
    }

    /**
     * Generate product description
     */
    public function generateDescription(Request $request)
    {
        $request->validate([
            'product_id' => 'nullable|exists:products,id',
            'product_data' => 'required_without:product_id|array',
            'length' => 'in:short,medium,long',
            'tone' => 'in:professional,luxury,casual,friendly',
        ]);

        if ($request->has('product_id')) {
            $product = Product::findOrFail($request->input('product_id'));
            $productData = [
                'name' => $product->name,
                'category' => $product->category->name,
                'features' => $product->features ?? [],
                'benefits' => $product->benefits ?? [],
            ];
        } else {
            $productData = $request->input('product_data');
        }

        $options = [
            'length' => $request->input('length', 'medium'),
            'tone' => $request->input('tone', 'professional'),
        ];

        $description = $this->aiContentService->generateProductDescription($productData, $options);

        return response()->json([
            'success' => true,
            'data' => $description,
        ]);
    }

    /**
     * Generate SEO metadata
     */
    public function generateSEO(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
        ]);

        $product = Product::findOrFail($request->input('product_id'));
        
        $productData = [
            'name' => $product->name,
            'description' => $product->description,
            'category' => $product->category->name,
            'price' => $product->price,
        ];

        $seo = $this->aiContentService->generateSEOMetadata($productData);

        return response()->json([
            'success' => true,
            'data' => $seo,
        ]);
    }

    /**
     * Generate personalized email
     */
    public function generateEmail(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'email_type' => 'required|in:welcome,abandoned_cart,order_confirmation,re_engagement',
            'data' => 'array',
        ]);

        $userId = $request->input('user_id');
        $emailType = $request->input('email_type');
        $data = $request->input('data', []);

        $email = $this->aiContentService->generatePersonalizedEmail($userId, $emailType, $data);

        return response()->json([
            'success' => true,
            'data' => $email,
        ]);
    }

    /**
     * Generate marketing copy
     */
    public function generateMarketingCopy(Request $request)
    {
        $request->validate([
            'campaign' => 'required|array',
            'campaign.product_id' => 'required|exists:products,id',
            'campaign.platform' => 'required|in:facebook,google,instagram,twitter',
            'campaign.objective' => 'required|in:awareness,consideration,conversion',
            'tone' => 'in:professional,luxury,casual,friendly',
        ]);

        $campaign = $request->input('campaign');
        $options = [
            'tone' => $request->input('tone', 'professional'),
        ];

        $copy = $this->aiContentService->generateMarketingCopy($campaign, $options);

        return response()->json([
            'success' => true,
            'data' => $copy,
        ]);
    }

    /**
     * Generate blog post
     */
    public function generateBlog(Request $request)
    {
        $request->validate([
            'topic' => 'required|string|max:200',
            'keywords' => 'array',
            'length' => 'integer|min:500|max:5000',
        ]);

        $topic = $request->input('topic');
        $keywords = $request->input('keywords', []);
        $length = $request->input('length', 1000);

        $blog = $this->aiContentService->generateBlogPost($topic, $keywords, $length);

        return response()->json([
            'success' => true,
            'data' => $blog,
        ]);
    }

    /**
     * Generate social media post
     */
    public function generateSocial(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'platform' => 'required|in:instagram,facebook,twitter,linkedin',
            'occasion' => 'nullable|string',
        ]);

        $productId = $request->input('product_id');
        $platform = $request->input('platform');
        $occasion = $request->input('occasion');

        $post = $this->aiContentService->generateSocialPost($productId, $platform, $occasion);

        return response()->json([
            'success' => true,
            'data' => $post,
        ]);
    }

    /**
     * Generate FAQ
     */
    public function generateFAQ(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'count' => 'integer|min:1|max:20',
        ]);

        $productId = $request->input('product_id');
        $count = $request->input('count', 10);

        $faq = $this->aiContentService->generateFAQ($productId, $count);

        return response()->json([
            'success' => true,
            'data' => $faq,
        ]);
    }

    /**
     * Generate product comparison
     */
    public function generateComparison(Request $request)
    {
        $request->validate([
            'product1_id' => 'required|exists:products,id',
            'product2_id' => 'required|exists:products,id',
        ]);

        $product1Id = $request->input('product1_id');
        $product2Id = $request->input('product2_id');

        $comparison = $this->aiContentService->generateProductComparison($product1Id, $product2Id);

        return response()->json([
            'success' => true,
            'data' => $comparison,
        ]);
    }
}
