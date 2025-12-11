<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPerformanceIndexes extends Migration
{
    public function up()
    {
        // Products table indexes
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'views_count')) {
                $table->integer('views_count')->default(0)->after('stock');
            }
            if (!Schema::hasColumn('products', 'sales_count')) {
                $table->integer('sales_count')->default(0)->after('views_count');
            }
            if (!Schema::hasColumn('products', 'average_rating')) {
                $table->decimal('average_rating', 3, 2)->nullable()->after('sales_count');
            }
            if (!Schema::hasColumn('products', 'is_featured')) {
                $table->boolean('is_featured')->default(false)->after('average_rating');
            }
            if (!Schema::hasColumn('products', 'featured_at')) {
                $table->timestamp('featured_at')->nullable()->after('is_featured');
            }
        });

        // Add composite indexes for common queries
        try {
            DB::statement('CREATE INDEX idx_products_category_status ON products(category_id, status)');
        } catch (\Exception $e) {}
        try {
            DB::statement('CREATE INDEX idx_products_seller_status ON products(seller_id, status)');
        } catch (\Exception $e) {}
        try {
            DB::statement('CREATE INDEX idx_products_price_range ON products(price, status)');
        } catch (\Exception $e) {}
        try {
            DB::statement('CREATE INDEX idx_products_featured ON products(is_featured, featured_at)');
        } catch (\Exception $e) {}
        try {
            DB::statement('CREATE INDEX idx_products_rating ON products(average_rating, status)');
        } catch (\Exception $e) {}

        // Orders table indexes
        try {
            DB::statement('CREATE INDEX idx_orders_user_status ON orders(user_id, status)');
        } catch (\Exception $e) {}
        try {
            DB::statement('CREATE INDEX idx_orders_created_status ON orders(created_at, status)');
        } catch (\Exception $e) {}
        try {
            DB::statement('CREATE INDEX idx_orders_total ON orders(total_amount, status)');
        } catch (\Exception $e) {}

        // Reviews table indexes
        try {
            DB::statement('CREATE INDEX idx_reviews_product_approved ON reviews(product_id, is_approved)');
        } catch (\Exception $e) {}
        try {
            DB::statement('CREATE INDEX idx_reviews_rating ON reviews(rating, created_at)');
        } catch (\Exception $e) {}

        // Users table enhancements
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'last_login_at')) {
                $table->timestamp('last_login_at')->nullable()->after('remember_token');
            }
            if (!Schema::hasColumn('users', 'last_login_ip')) {
                $table->string('last_login_ip')->nullable()->after('last_login_at');
            }
            if (!Schema::hasColumn('users', 'total_orders')) {
                $table->integer('total_orders')->default(0)->after('last_login_ip');
            }
            if (!Schema::hasColumn('users', 'total_spent')) {
                $table->decimal('total_spent', 10, 2)->default(0)->after('total_orders');
            }
            if (!Schema::hasColumn('users', 'is_verified')) {
                $table->boolean('is_verified')->default(false)->after('email_verified_at');
            }
            if (!Schema::hasColumn('users', 'two_factor_enabled')) {
                $table->boolean('two_factor_enabled')->default(false)->after('is_verified');
            }
            if (!Schema::hasColumn('users', 'two_factor_secret')) {
                $table->text('two_factor_secret')->nullable()->after('two_factor_enabled');
            }
        });

        try {
            DB::statement('CREATE INDEX idx_users_role_status ON users(role, status)');
        } catch (\Exception $e) {}
        try {
            DB::statement('CREATE INDEX idx_users_last_login ON users(last_login_at)');
        } catch (\Exception $e) {}
    }

    public function down()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['views_count', 'sales_count', 'average_rating', 'is_featured', 'featured_at']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['last_login_at', 'last_login_ip', 'total_orders', 'total_spent', 'is_verified', 'two_factor_enabled', 'two_factor_secret']);
        });

        // Indexes will be dropped automatically when columns are dropped
    }
}
