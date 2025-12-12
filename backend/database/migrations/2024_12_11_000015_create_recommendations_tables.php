<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRecommendationsTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // User Product Interactions - Track all user-product interactions
        Schema::create('user_product_interactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->string('interaction_type', 20)->index(); // view, cart, wishlist, purchase, rate
            $table->integer('interaction_weight')->default(1); // Different weights for different actions
            $table->decimal('rating', 3, 2)->nullable(); // For explicit ratings
            $table->timestamp('interacted_at')->index();
            $table->timestamps();
            
            $table->index(['user_id', 'interaction_type', 'interacted_at'], 'upi_user_type_date_idx');
            $table->index(['product_id', 'interaction_type'], 'upi_product_type_idx');
        });

        // Product Similarities - Pre-calculated product similarity scores
        Schema::create('product_similarities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('similar_product_id')->constrained('products')->onDelete('cascade');
            $table->decimal('similarity_score', 5, 4); // 0.0000 to 1.0000
            $table->string('similarity_type', 30); // collaborative, content_based, hybrid
            $table->timestamp('calculated_at');
            $table->timestamps();
            
            $table->index(['product_id', 'similarity_score'], 'ps_product_score_idx');
            $table->unique(['product_id', 'similar_product_id', 'similarity_type'], 'ps_unique');
        });

        // User Preferences - Learned user preferences
        Schema::create('user_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->onDelete('cascade');
            $table->json('favorite_categories')->nullable(); // Category IDs with weights
            $table->json('favorite_brands')->nullable(); // Brand names with weights
            $table->json('price_range')->nullable(); // Min/max price preferences
            $table->json('preferred_attributes')->nullable(); // Size, color, style preferences
            $table->decimal('avg_purchase_amount', 10, 2)->default(0);
            $table->integer('purchase_frequency')->default(0); // Purchases per month
            $table->string('user_segment', 50)->nullable(); // budget, mid-range, luxury
            $table->timestamp('last_updated_at')->nullable();
            $table->timestamps();
        });

        // Personalized Recommendations - Cached recommendations for users
        Schema::create('personalized_recommendations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('recommendation_type', 30)->index(); // for_you, trending, similar, etc.
            $table->json('product_ids'); // Array of recommended product IDs in order
            $table->json('scores')->nullable(); // Corresponding recommendation scores
            $table->string('algorithm', 50); // collaborative, content_based, hybrid, trending
            $table->timestamp('generated_at');
            $table->timestamp('expires_at')->index();
            $table->timestamps();
            
            $table->index(['user_id', 'recommendation_type', 'expires_at'], 'pr_user_type_expires_idx');
        });

        // Collaborative Filtering Data - User-user and item-item matrices
        Schema::create('collaborative_filtering_data', function (Blueprint $table) {
            $table->id();
            $table->string('data_type', 20); // user_similarity, item_similarity
            $table->unsignedBigInteger('entity_id'); // User or Product ID
            $table->json('similarity_vector'); // Array of similar entities with scores
            $table->integer('data_version')->default(1);
            $table->timestamp('calculated_at');
            $table->timestamps();
            
            $table->index(['data_type', 'entity_id']);
        });

        // Trending Products - Real-time trending calculation
        Schema::create('trending_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->date('trending_date')->index();
            $table->decimal('trending_score', 8, 2); // Calculated score
            $table->integer('views_count')->default(0);
            $table->integer('purchases_count')->default(0);
            $table->integer('add_to_cart_count')->default(0);
            $table->decimal('momentum', 5, 2)->default(0); // Growth rate
            $table->integer('rank')->nullable();
            $table->timestamps();
            
            $table->unique(['product_id', 'trending_date'], 'tp_product_date_unique');
            $table->index(['trending_date', 'trending_score'], 'tp_date_score_idx');
        });

        // Recommendation Performance - Track recommendation effectiveness
        Schema::create('recommendation_performance', function (Blueprint $table) {
            $table->id();
            $table->string('recommendation_type', 30)->index();
            $table->string('algorithm', 50)->index();
            $table->date('date')->index();
            $table->integer('impressions')->default(0);
            $table->integer('clicks')->default(0);
            $table->integer('conversions')->default(0);
            $table->decimal('click_through_rate', 5, 2)->default(0);
            $table->decimal('conversion_rate', 5, 2)->default(0);
            $table->decimal('revenue', 10, 2)->default(0);
            $table->timestamps();
            
            $table->unique(['recommendation_type', 'algorithm', 'date'], 'rp_unique');
        });

        // Frequently Bought Together - Association rules
        Schema::create('frequently_bought_together', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('bought_with_product_id')->constrained('products')->onDelete('cascade');
            $table->integer('co_occurrence_count')->default(0); // How many times bought together
            $table->decimal('confidence', 5, 4); // P(B|A) - probability
            $table->decimal('lift', 5, 2); // Lift score
            $table->timestamp('last_calculated_at');
            $table->timestamps();
            
            $table->index(['product_id', 'confidence'], 'fbt_product_conf_idx');
            $table->unique(['product_id', 'bought_with_product_id'], 'fbt_unique');
        });

        // Search History - For search-based recommendations
        Schema::create('search_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('session_id', 100)->index();
            $table->string('search_query', 255);
            $table->integer('results_count')->default(0);
            $table->boolean('had_results')->default(true);
            $table->foreignId('clicked_product_id')->nullable()->constrained('products')->onDelete('set null');
            $table->timestamps();
            
            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('search_history');
        Schema::dropIfExists('frequently_bought_together');
        Schema::dropIfExists('recommendation_performance');
        Schema::dropIfExists('trending_products');
        Schema::dropIfExists('collaborative_filtering_data');
        Schema::dropIfExists('personalized_recommendations');
        Schema::dropIfExists('user_preferences');
        Schema::dropIfExists('product_similarities');
        Schema::dropIfExists('user_product_interactions');
    }
}
