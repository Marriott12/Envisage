<?php

namespace App\Services;

use App\Models\Product;
use App\Models\EmailTemplate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

/**
 * AI Content Generation Service
 * 
 * Features:
 * - GPT-powered product descriptions
 * - SEO content optimization
 * - Email personalization
 * - Marketing copy generation
 * - Meta tags and titles generation
 * - Blog content creation
 * - Social media posts
 */
class AIContentGenerationService
{
    protected $mlServiceUrl;
    protected $openaiApiKey;

    public function __construct()
    {
        $this->mlServiceUrl = config('services.ml.url', env('ML_SERVICE_URL', 'http://localhost:5000'));
        $this->openaiApiKey = config('services.openai.key', env('OPENAI_API_KEY'));
    }

    /**
     * Generate product description using GPT
     */
    public function generateProductDescription($productData, $options = [])
    {
        $tone = $options['tone'] ?? 'professional';
        $length = $options['length'] ?? 'medium';
        $includeFeatures = $options['include_features'] ?? true;

        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->openaiApiKey}",
                'Content-Type' => 'application/json',
            ])->timeout(30)->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-4',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => "You are an expert e-commerce copywriter. Write compelling product descriptions that convert visitors into buyers. Use a {$tone} tone.",
                    ],
                    [
                        'role' => 'user',
                        'content' => $this->buildProductDescriptionPrompt($productData, $length, $includeFeatures),
                    ],
                ],
                'max_tokens' => $this->getLengthTokens($length),
                'temperature' => 0.7,
            ]);

            if ($response->successful()) {
                $content = $response->json()['choices'][0]['message']['content'];
                return trim($content);
            }
        } catch (\Exception $e) {
            \Log::warning("GPT product description failed: " . $e->getMessage());
        }

        // Fallback to template-based generation
        return $this->templateBasedDescription($productData);
    }

    /**
     * Generate SEO-optimized meta title and description
     */
    public function generateSEOMetadata($productData)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->openaiApiKey}",
            ])->timeout(20)->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-4',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are an SEO expert. Generate compelling meta titles (under 60 chars) and meta descriptions (under 160 chars) that rank well and drive clicks.',
                    ],
                    [
                        'role' => 'user',
                        'content' => "Generate SEO metadata for:\nProduct: {$productData['name']}\nCategory: {$productData['category']}\nPrice: \${$productData['price']}\nKey Features: " . implode(', ', $productData['features'] ?? []),
                    ],
                ],
                'max_tokens' => 200,
            ]);

            if ($response->successful()) {
                $content = $response->json()['choices'][0]['message']['content'];
                return $this->parseSEOMetadata($content);
            }
        } catch (\Exception $e) {
            \Log::warning("SEO metadata generation failed: " . $e->getMessage());
        }

        // Fallback
        return $this->generateBasicSEO($productData);
    }

    /**
     * Generate personalized email content
     */
    public function generatePersonalizedEmail($userId, $templateType, $data = [])
    {
        $user = \App\Models\User::find($userId);
        
        if (!$user) {
            return null;
        }

        $context = array_merge([
            'user_name' => $user->name,
            'user_email' => $user->email,
        ], $data);

        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->openaiApiKey}",
            ])->timeout(30)->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-4',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are an email marketing expert. Write personalized, engaging emails that drive action. Keep a friendly but professional tone.',
                    ],
                    [
                        'role' => 'user',
                        'content' => $this->buildEmailPrompt($templateType, $context),
                    ],
                ],
                'max_tokens' => 500,
            ]);

            if ($response->successful()) {
                return $response->json()['choices'][0]['message']['content'];
            }
        } catch (\Exception $e) {
            \Log::warning("Personalized email generation failed: " . $e->getMessage());
        }

        return $this->getEmailTemplate($templateType, $context);
    }

    /**
     * Generate marketing copy (ads, banners, etc.)
     */
    public function generateMarketingCopy($campaign, $options = [])
    {
        $platform = $options['platform'] ?? 'general'; // facebook, google, instagram
        $cta = $options['cta'] ?? 'Shop Now';
        $maxLength = $options['max_length'] ?? 150;

        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->openaiApiKey}",
            ])->timeout(20)->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-4',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => "You are a digital marketing expert. Write compelling ad copy for {$platform} that drives conversions. Keep it under {$maxLength} characters.",
                    ],
                    [
                        'role' => 'user',
                        'content' => "Campaign: {$campaign['name']}\nProduct: {$campaign['product']}\nTarget Audience: {$campaign['audience']}\nCall-to-Action: {$cta}",
                    ],
                ],
                'max_tokens' => 150,
            ]);

            if ($response->successful()) {
                return $response->json()['choices'][0]['message']['content'];
            }
        } catch (\Exception $e) {
            \Log::warning("Marketing copy generation failed: " . $e->getMessage());
        }

        return "Discover {$campaign['product']}! {$cta}";
    }

    /**
     * Generate blog post content
     */
    public function generateBlogPost($topic, $keywords = [], $length = 1000)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->openaiApiKey}",
            ])->timeout(60)->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-4',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are a professional blog writer. Create engaging, informative content optimized for SEO.',
                    ],
                    [
                        'role' => 'user',
                        'content' => "Write a {$length}-word blog post about: {$topic}\nInclude these keywords naturally: " . implode(', ', $keywords),
                    ],
                ],
                'max_tokens' => (int)($length * 1.5),
            ]);

            if ($response->successful()) {
                return $response->json()['choices'][0]['message']['content'];
            }
        } catch (\Exception $e) {
            \Log::warning("Blog post generation failed: " . $e->getMessage());
        }

        return null;
    }

    /**
     * Generate social media posts
     */
    public function generateSocialPost($productId, $platform = 'instagram', $occasion = null)
    {
        $product = Product::find($productId);
        
        if (!$product) {
            return null;
        }

        $platformSpecs = [
            'instagram' => ['max_chars' => 2200, 'hashtags' => true],
            'twitter' => ['max_chars' => 280, 'hashtags' => true],
            'facebook' => ['max_chars' => 500, 'hashtags' => false],
            'linkedin' => ['max_chars' => 700, 'hashtags' => true],
        ];

        $spec = $platformSpecs[$platform] ?? $platformSpecs['instagram'];

        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->openaiApiKey}",
            ])->timeout(20)->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-4',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => "You are a social media expert. Create engaging {$platform} posts that drive engagement. " . ($spec['hashtags'] ? "Include relevant hashtags." : ""),
                    ],
                    [
                        'role' => 'user',
                        'content' => "Create a post for:\nProduct: {$product->name}\nPrice: \${$product->price}" . ($occasion ? "\nOccasion: {$occasion}" : "") . "\nKeep under {$spec['max_chars']} characters.",
                    ],
                ],
                'max_tokens' => 200,
            ]);

            if ($response->successful()) {
                return $response->json()['choices'][0]['message']['content'];
            }
        } catch (\Exception $e) {
            \Log::warning("Social post generation failed: " . $e->getMessage());
        }

        // Fallback
        $post = "Check out our {$product->name}! Only \${$product->price}. ";
        if ($spec['hashtags']) {
            $post .= "#shopping #deals #" . str_replace(' ', '', $product->name);
        }
        return $post;
    }

    /**
     * Optimize existing content for SEO
     */
    public function optimizeContentForSEO($content, $targetKeywords = [])
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->openaiApiKey}",
            ])->timeout(30)->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-4',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are an SEO expert. Improve content for better search rankings while maintaining readability and natural flow.',
                    ],
                    [
                        'role' => 'user',
                        'content' => "Optimize this content for these keywords: " . implode(', ', $targetKeywords) . "\n\nContent:\n{$content}",
                    ],
                ],
                'max_tokens' => strlen($content) * 2,
            ]);

            if ($response->successful()) {
                return $response->json()['choices'][0]['message']['content'];
            }
        } catch (\Exception $e) {
            \Log::warning("SEO optimization failed: " . $e->getMessage());
        }

        return $content;
    }

    /**
     * Generate product comparison content
     */
    public function generateProductComparison($product1Id, $product2Id)
    {
        $product1 = Product::find($product1Id);
        $product2 = Product::find($product2Id);

        if (!$product1 || !$product2) {
            return null;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->openaiApiKey}",
            ])->timeout(30)->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-4',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are a product comparison expert. Create objective, helpful comparisons that help customers make informed decisions.',
                    ],
                    [
                        'role' => 'user',
                        'content' => "Compare these products:\n\nProduct 1: {$product1->name} (\${$product1->price})\nDescription: {$product1->description}\n\nProduct 2: {$product2->name} (\${$product2->price})\nDescription: {$product2->description}",
                    ],
                ],
                'max_tokens' => 500,
            ]);

            if ($response->successful()) {
                return $response->json()['choices'][0]['message']['content'];
            }
        } catch (\Exception $e) {
            \Log::warning("Product comparison generation failed: " . $e->getMessage());
        }

        return null;
    }

    /**
     * Generate FAQ content
     */
    public function generateFAQ($productId, $count = 5)
    {
        $product = Product::find($productId);
        
        if (!$product) {
            return [];
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->openaiApiKey}",
            ])->timeout(30)->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-4',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are a customer service expert. Generate helpful FAQs that address common customer questions.',
                    ],
                    [
                        'role' => 'user',
                        'content' => "Generate {$count} FAQ items for:\nProduct: {$product->name}\nDescription: {$product->description}\n\nFormat as JSON array with 'question' and 'answer' fields.",
                    ],
                ],
                'max_tokens' => 800,
            ]);

            if ($response->successful()) {
                $content = $response->json()['choices'][0]['message']['content'];
                return json_decode($content, true) ?? [];
            }
        } catch (\Exception $e) {
            \Log::warning("FAQ generation failed: " . $e->getMessage());
        }

        return [];
    }

    // Helper methods

    protected function buildProductDescriptionPrompt($productData, $length, $includeFeatures)
    {
        $prompt = "Write a compelling product description for:\n";
        $prompt .= "Product Name: {$productData['name']}\n";
        $prompt .= "Category: " . ($productData['category'] ?? 'General') . "\n";
        $prompt .= "Price: \$" . ($productData['price'] ?? 'TBD') . "\n";

        if ($includeFeatures && !empty($productData['features'])) {
            $prompt .= "Key Features:\n";
            foreach ($productData['features'] as $feature) {
                $prompt .= "- {$feature}\n";
            }
        }

        $lengthGuide = [
            'short' => '2-3 sentences',
            'medium' => '1-2 paragraphs',
            'long' => '3-4 paragraphs',
        ];

        $prompt .= "\nLength: " . ($lengthGuide[$length] ?? $lengthGuide['medium']);

        return $prompt;
    }

    protected function buildEmailPrompt($templateType, $context)
    {
        $prompts = [
            'welcome' => "Write a welcome email for a new customer named {$context['user_name']}. Make them feel valued and introduce key features of our store.",
            'abandoned_cart' => "Write a friendly reminder email for {$context['user_name']} who left items in their cart. Include the cart value: \${$context['cart_value']}.",
            'order_confirmation' => "Write an order confirmation email for {$context['user_name']}. Order number: {$context['order_number']}, Total: \${$context['total']}.",
            're_engagement' => "Write a re-engagement email to win back {$context['user_name']} who hasn't shopped in {$context['days_inactive']} days.",
        ];

        return $prompts[$templateType] ?? $prompts['welcome'];
    }

    protected function getLengthTokens($length)
    {
        return [
            'short' => 100,
            'medium' => 250,
            'long' => 500,
        ][$length] ?? 250;
    }

    protected function parseSEOMetadata($content)
    {
        // Parse GPT response into structured data
        preg_match('/Title:\s*(.+)/i', $content, $titleMatch);
        preg_match('/Description:\s*(.+)/i', $content, $descMatch);

        return [
            'meta_title' => $titleMatch[1] ?? 'Product Title',
            'meta_description' => $descMatch[1] ?? 'Product description',
        ];
    }

    protected function generateBasicSEO($productData)
    {
        return [
            'meta_title' => substr($productData['name'] . ' - Buy Online', 0, 60),
            'meta_description' => substr("Shop {$productData['name']} at great prices. " . ($productData['description'] ?? ''), 0, 160),
        ];
    }

    protected function templateBasedDescription($productData)
    {
        return "Discover the {$productData['name']}, a premium product in the {$productData['category']} category. " .
               "Available now at \${$productData['price']}. " .
               "Perfect for anyone looking for quality and value.";
    }

    protected function getEmailTemplate($templateType, $context)
    {
        $templates = [
            'welcome' => "Hi {$context['user_name']}!\n\nWelcome to our store! We're excited to have you with us.\n\nBest regards,\nThe Team",
            'abandoned_cart' => "Hi {$context['user_name']},\n\nYou left some items in your cart. Complete your order now!\n\nBest,\nThe Team",
        ];

        return $templates[$templateType] ?? $templates['welcome'];
    }

    /**
     * Batch generate descriptions for products without one
     */
    public function batchGenerateDescriptions($limit = 50)
    {
        $products = Product::whereNull('description')
            ->orWhere('description', '')
            ->limit($limit)
            ->get();

        $generated = 0;

        foreach ($products as $product) {
            try {
                $description = $this->generateProductDescription([
                    'name' => $product->name,
                    'category' => $product->category->name ?? '',
                    'price' => $product->price,
                ]);

                $product->update(['description' => $description]);
                $generated++;

                // Rate limiting
                sleep(2);
            } catch (\Exception $e) {
                \Log::error("Failed to generate description for product {$product->id}: " . $e->getMessage());
            }
        }

        return $generated;
    }
}
