<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddProfileEnhancementsToUsersTable extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('avatar')->nullable()->after('email');
            $table->string('phone')->nullable()->after('avatar');
            $table->text('bio')->nullable()->after('phone');
            $table->boolean('two_factor_enabled')->default(false)->after('bio');
            $table->string('two_factor_secret')->nullable()->after('two_factor_enabled');
            $table->timestamp('two_factor_confirmed_at')->nullable()->after('two_factor_secret');
        });

        // Notification preferences table
        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->boolean('email_order_updates')->default(true);
            $table->boolean('email_promotions')->default(true);
            $table->boolean('email_price_alerts')->default(true);
            $table->boolean('email_new_messages')->default(true);
            $table->boolean('push_order_updates')->default(false);
            $table->boolean('push_promotions')->default(false);
            $table->boolean('push_price_alerts')->default(false);
            $table->boolean('push_new_messages')->default(false);
            $table->boolean('sms_order_updates')->default(false);
            $table->timestamps();
        });

        // Shipping addresses table
        Schema::create('shipping_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('label')->default('Home'); // Home, Work, etc.
            $table->string('recipient_name');
            $table->string('address_line1');
            $table->string('address_line2')->nullable();
            $table->string('city');
            $table->string('state');
            $table->string('postal_code');
            $table->string('country')->default('USA');
            $table->string('phone');
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        // User sessions for security tracking
        Schema::create('user_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('ip_address', 45);
            $table->text('user_agent');
            $table->string('device_type')->nullable();
            $table->string('location')->nullable();
            $table->timestamp('last_activity')->useCurrent();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('user_sessions');
        Schema::dropIfExists('shipping_addresses');
        Schema::dropIfExists('notification_preferences');
        
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'avatar',
                'phone',
                'bio',
                'two_factor_enabled',
                'two_factor_secret',
                'two_factor_confirmed_at',
            ]);
        });
    }
}
