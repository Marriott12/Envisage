<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSecurityFieldsToUsersTable extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            // 2FA fields
            if (!Schema::hasColumn('users', 'two_factor_enabled')) {
                $table->boolean('two_factor_enabled')->default(false)->after('password');
            }
            if (!Schema::hasColumn('users', 'two_factor_secret')) {
                $table->text('two_factor_secret')->nullable()->after('two_factor_enabled');
            }
            if (!Schema::hasColumn('users', 'two_factor_backup_codes')) {
                $table->text('two_factor_backup_codes')->nullable()->after('two_factor_secret');
            }
            
            // Account deletion fields
            if (!Schema::hasColumn('users', 'deletion_requested_at')) {
                $table->timestamp('deletion_requested_at')->nullable();
            }
            if (!Schema::hasColumn('users', 'deletion_reason')) {
                $table->text('deletion_reason')->nullable();
            }
            if (!Schema::hasColumn('users', 'deletion_scheduled_at')) {
                $table->timestamp('deletion_scheduled_at')->nullable();
            }
            
            // GDPR consent fields
            if (!Schema::hasColumn('users', 'marketing_emails_consent')) {
                $table->boolean('marketing_emails_consent')->default(false);
            }
            if (!Schema::hasColumn('users', 'data_sharing_consent')) {
                $table->boolean('data_sharing_consent')->default(false);
            }
            if (!Schema::hasColumn('users', 'analytics_consent')) {
                $table->boolean('analytics_consent')->default(true);
            }
            if (!Schema::hasColumn('users', 'personalization_consent')) {
                $table->boolean('personalization_consent')->default(true);
            }
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'two_factor_enabled',
                'two_factor_secret',
                'two_factor_backup_codes',
                'deletion_requested_at',
                'deletion_reason',
                'deletion_scheduled_at',
                'marketing_emails_consent',
                'data_sharing_consent',
                'analytics_consent',
                'personalization_consent',
            ]);
        });
    }
}
