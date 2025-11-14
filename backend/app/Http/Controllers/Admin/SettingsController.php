<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class SettingsController extends Controller
{
    /**
     * Get all settings grouped by category
     */
    public function index(Request $request)
    {
        try {
            $group = $request->get('group');
            
            if ($group) {
                $settings = Setting::where('group', $group)->get();
            } else {
                $settings = Setting::all()->groupBy('group');
            }

            return response()->json([
                'settings' => $settings
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve settings: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to retrieve settings'], 500);
        }
    }

    /**
     * Get public settings (for frontend)
     */
    public function public()
    {
        try {
            $settings = Setting::where('is_public', true)
                ->get()
                ->mapWithKeys(function ($setting) {
                    $value = $setting->value;
                    
                    // Cast value based on type
                    if ($setting->type === 'boolean') {
                        $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
                    } elseif ($setting->type === 'number') {
                        $value = (float) $value;
                    } elseif ($setting->type === 'json') {
                        $value = json_decode($value, true);
                    }
                    
                    return [$setting->key => $value];
                });

            return response()->json([
                'settings' => $settings
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve public settings: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to retrieve settings'], 500);
        }
    }

    /**
     * Update multiple settings at once
     */
    public function updateBatch(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'settings' => 'required|array',
            'settings.*.key' => 'required|string',
            'settings.*.value' => 'required',
            'settings.*.group' => 'sometimes|string',
            'settings.*.type' => 'sometimes|in:text,number,boolean,json,password',
            'settings.*.is_public' => 'sometimes|boolean',
            'settings.*.description' => 'sometimes|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $updated = [];
            
            foreach ($request->settings as $settingData) {
                $setting = Setting::updateOrCreate(
                    ['key' => $settingData['key']],
                    [
                        'value' => $settingData['value'],
                        'group' => $settingData['group'] ?? 'general',
                        'type' => $settingData['type'] ?? 'text',
                        'is_public' => $settingData['is_public'] ?? false,
                        'description' => $settingData['description'] ?? null
                    ]
                );
                
                $updated[] = $setting;
            }

            // Clear cache
            Setting::clearCache();

            return response()->json([
                'message' => 'Settings updated successfully',
                'settings' => $updated
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update settings: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to update settings'], 500);
        }
    }

    /**
     * Update a single setting
     */
    public function update(Request $request, $key)
    {
        $validator = Validator::make($request->all(), [
            'value' => 'required',
            'group' => 'sometimes|string',
            'type' => 'sometimes|in:text,number,boolean,json,password',
            'is_public' => 'sometimes|boolean',
            'description' => 'sometimes|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $setting = Setting::updateOrCreate(
                ['key' => $key],
                [
                    'value' => $request->value,
                    'group' => $request->group ?? 'general',
                    'type' => $request->type ?? 'text',
                    'is_public' => $request->is_public ?? false,
                    'description' => $request->description ?? null
                ]
            );

            // Clear cache
            Setting::clearCache();

            return response()->json([
                'message' => 'Setting updated successfully',
                'setting' => $setting
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update setting: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to update setting'], 500);
        }
    }

    /**
     * Delete a setting
     */
    public function destroy($key)
    {
        try {
            $setting = Setting::where('key', $key)->first();
            
            if (!$setting) {
                return response()->json(['error' => 'Setting not found'], 404);
            }

            $setting->delete();
            Setting::clearCache();

            return response()->json(['message' => 'Setting deleted successfully']);
        } catch (\Exception $e) {
            Log::error('Failed to delete setting: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to delete setting'], 500);
        }
    }

    /**
     * Initialize default settings
     */
    public function initializeDefaults()
    {
        try {
            $defaults = [
                // Email Settings
                ['key' => 'mail_driver', 'value' => 'smtp', 'group' => 'email', 'type' => 'text', 'description' => 'Email driver (smtp, sendmail, mailgun)'],
                ['key' => 'mail_host', 'value' => 'smtp.gmail.com', 'group' => 'email', 'type' => 'text', 'description' => 'SMTP host'],
                ['key' => 'mail_port', 'value' => '587', 'group' => 'email', 'type' => 'number', 'description' => 'SMTP port'],
                ['key' => 'mail_username', 'value' => '', 'group' => 'email', 'type' => 'text', 'description' => 'SMTP username'],
                ['key' => 'mail_password', 'value' => '', 'group' => 'email', 'type' => 'password', 'description' => 'SMTP password'],
                ['key' => 'mail_encryption', 'value' => 'tls', 'group' => 'email', 'type' => 'text', 'description' => 'Encryption (tls, ssl)'],
                ['key' => 'mail_from_address', 'value' => '', 'group' => 'email', 'type' => 'text', 'description' => 'From email address'],
                ['key' => 'mail_from_name', 'value' => 'Envisage Marketplace', 'group' => 'email', 'type' => 'text', 'description' => 'From name'],
                
                // Payment Settings
                ['key' => 'stripe_secret_key', 'value' => '', 'group' => 'payment', 'type' => 'password', 'description' => 'Stripe secret key'],
                ['key' => 'stripe_webhook_secret', 'value' => '', 'group' => 'payment', 'type' => 'password', 'description' => 'Stripe webhook secret'],
                ['key' => 'stripe_public_key', 'value' => '', 'group' => 'payment', 'type' => 'text', 'is_public' => true, 'description' => 'Stripe publishable key'],
                ['key' => 'payment_currency', 'value' => 'usd', 'group' => 'payment', 'type' => 'text', 'is_public' => true, 'description' => 'Default currency'],
                
                // SEO Settings
                ['key' => 'site_name', 'value' => 'Envisage Marketplace', 'group' => 'seo', 'type' => 'text', 'is_public' => true, 'description' => 'Site name'],
                ['key' => 'site_description', 'value' => 'Buy and sell quality products online', 'group' => 'seo', 'type' => 'text', 'is_public' => true, 'description' => 'Site description'],
                ['key' => 'site_keywords', 'value' => 'marketplace, ecommerce, shop', 'group' => 'seo', 'type' => 'text', 'is_public' => true, 'description' => 'Site keywords'],
                ['key' => 'meta_image', 'value' => '', 'group' => 'seo', 'type' => 'text', 'is_public' => true, 'description' => 'Default meta image URL'],
                
                // General Settings
                ['key' => 'site_logo', 'value' => '', 'group' => 'general', 'type' => 'text', 'is_public' => true, 'description' => 'Site logo URL'],
                ['key' => 'frontend_url', 'value' => 'http://localhost:3000', 'group' => 'general', 'type' => 'text', 'description' => 'Frontend URL'],
                ['key' => 'enable_registration', 'value' => 'true', 'group' => 'general', 'type' => 'boolean', 'is_public' => true, 'description' => 'Enable user registration'],
                ['key' => 'maintenance_mode', 'value' => 'false', 'group' => 'general', 'type' => 'boolean', 'is_public' => true, 'description' => 'Maintenance mode']
            ];

            foreach ($defaults as $default) {
                Setting::firstOrCreate(
                    ['key' => $default['key']],
                    $default
                );
            }

            Setting::clearCache();

            return response()->json([
                'message' => 'Default settings initialized successfully',
                'count' => count($defaults)
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to initialize defaults: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to initialize defaults'], 500);
        }
    }
}
