<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddAiPerformanceIndexes extends Migration
{
    /**
     * Run the migrations - Add AI Performance Indexes
     * Critical indexes for Envisage AI Platform v2.0 at scale
     *
     * @return void
     */
    public function up()
    {
        // Recommendations Performance Indexes - check if columns exist
        if (Schema::hasTable('recommendations') && Schema::hasColumn('recommendations', 'user_id')) {
            Schema::table('recommendations', function (Blueprint $table) {
                $table->index(['user_id', 'created_at'], 'idx_recommendations_user_created');
            });
        }

        // Orders Indexes (for predictive analytics)
        if (Schema::hasTable('orders') && Schema::hasColumn('orders', 'user_id')) {
            Schema::table('orders', function (Blueprint $table) {
                if (!$this->indexExists('orders', 'idx_orders_user_date')) {
                    $table->index(['user_id', 'created_at'], 'idx_orders_user_date');
                }
                if (!$this->indexExists('orders', 'idx_orders_status_date') && Schema::hasColumn('orders', 'status')) {
                    $table->index(['status', 'created_at'], 'idx_orders_status_date');
                }
                if (!$this->indexExists('orders', 'idx_orders_total_date') && Schema::hasColumn('orders', 'total')) {
                    $table->index(['total', 'created_at'], 'idx_orders_total_date');
                }
            });
        }

        // Products Indexes (for trending, recommendations)
        if (Schema::hasTable('products') && Schema::hasColumn('products', 'category_id')) {
            Schema::table('products', function (Blueprint $table) {
                if (!$this->indexExists('products', 'idx_products_category_date')) {
                    $table->index(['category_id', 'created_at'], 'idx_products_category_date');
                }
                if (!$this->indexExists('products', 'idx_products_price_stock') && Schema::hasColumn('products', 'price') && Schema::hasColumn('products', 'stock')) {
                    $table->index(['price', 'stock'], 'idx_products_price_stock');
                }
            });
        }

        // Reviews indexes
        if (Schema::hasTable('reviews') && Schema::hasColumn('reviews', 'product_id')) {
            Schema::table('reviews', function (Blueprint $table) {
                if (!$this->indexExists('reviews', 'idx_reviews_product_date')) {
                    $table->index(['product_id', 'created_at'], 'idx_reviews_product_date');
                }
                if (!$this->indexExists('reviews', 'idx_reviews_user_date') && Schema::hasColumn('reviews', 'user_id')) {
                    $table->index(['user_id', 'created_at'], 'idx_reviews_user_date');
                }
            });
        }
    }
    
    /**
     * Check if index exists on table
     *
     * @param string $table
     * @param string $index
     * @return bool
     */
    protected function indexExists(string $table, string $index): bool
    {
        $indexes = DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$index]);
        return !empty($indexes);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasTable('products')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropIndex('idx_products_category_date');
                $table->dropIndex('idx_products_price_stock');
                $table->dropIndex('idx_products_created');
            });
        }

        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropIndex('idx_orders_user_date');
                $table->dropIndex('idx_orders_status_date');
                $table->dropIndex('idx_orders_total_date');
            });
        }

        if (Schema::hasTable('reviews')) {
            Schema::table('reviews', function (Blueprint $table) {
                $table->dropIndex('idx_reviews_product_sentiment');
                $table->dropIndex('idx_reviews_user_date');
                $table->dropIndex('idx_reviews_created');
            });
        }

        if (Schema::hasTable('fraud_alerts')) {
            Schema::table('fraud_alerts', function (Blueprint $table) {
                $table->dropIndex('idx_fraud_status_risk');
                $table->dropIndex('idx_fraud_level_date');
                $table->dropIndex('idx_fraud_order');
                $table->dropIndex('idx_fraud_user_status');
            });
        }

        if (Schema::hasTable('recommendations')) {
            Schema::table('recommendations', function (Blueprint $table) {
                $table->dropIndex('idx_recommendations_user_created');
                $table->dropIndex('idx_recommendations_product_score');
                $table->dropIndex('idx_recommendations_algorithm');
            });
        }
    }
}
