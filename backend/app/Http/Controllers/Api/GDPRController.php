<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Review;
use App\Models\Address;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class GDPRController extends Controller
{
    /**
     * Export user data (GDPR compliance)
     */
    public function exportData(Request $request)
    {
        $user = $request->user();

        // Collect all user data
        $userData = [
            'personal_information' => [
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'date_of_birth' => $user->date_of_birth,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
            ],
            'profile' => [
                'bio' => $user->bio,
                'avatar' => $user->avatar,
                'cover_photo' => $user->cover_photo,
                'location' => $user->location,
            ],
            'addresses' => [],
            'orders' => Order::where('user_id', $user->id)
                ->with(['items', 'shipping', 'payment'])
                ->get(),
            'reviews' => Review::where('user_id', $user->id)->get(),
            'wishlist' => $user->wishlist()->with('product')->get(),
            'cart' => $user->cart()->with('product')->get(),
            'payment_methods' => $user->paymentMethods()->get()->map(function ($method) {
                return [
                    'type' => $method->type,
                    'brand' => $method->brand,
                    'last_four' => $method->last_four,
                    'exp_month' => $method->exp_month,
                    'exp_year' => $method->exp_year,
                ];
            }),
            'loyalty_points' => $user->loyalty_points,
            'total_spent' => $user->total_spent,
            'total_orders' => $user->total_orders,
        ];

        // Create JSON file
        $filename = 'user_data_' . $user->id . '_' . time() . '.json';
        $path = 'exports/' . $filename;
        
        Storage::put($path, json_encode($userData, JSON_PRETTY_PRINT));

        // Create download URL
        $downloadUrl = Storage::url($path);

        // Log the export
        SecurityController::logEvent($user->id, 'data_export', 'success');

        return response()->json([
            'message' => 'Data export prepared successfully',
            'download_url' => $downloadUrl,
            'filename' => $filename,
            'expires_at' => now()->addHours(24)->toDateTimeString()
        ]);
    }

    /**
     * Download exported data
     */
    public function downloadData(Request $request, $filename)
    {
        $user = $request->user();
        
        // Verify filename belongs to user
        if (!preg_match('/^user_data_' . $user->id . '_\d+\.json$/', $filename)) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        $path = 'exports/' . $filename;

        if (!Storage::exists($path)) {
            return response()->json([
                'message' => 'File not found or expired'
            ], 404);
        }

        return Storage::download($path);
    }

    /**
     * Request account deletion (GDPR right to be forgotten)
     */
    public function requestDeletion(Request $request)
    {
        $request->validate([
            'password' => 'required|string',
            'reason' => 'nullable|string|max:500',
            'confirm' => 'required|boolean|accepted'
        ]);

        $user = $request->user();

        // Verify password
        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Invalid password'
            ], 400);
        }

        // Check for pending orders
        $pendingOrders = Order::where('user_id', $user->id)
            ->whereIn('status', ['pending', 'processing', 'shipped'])
            ->count();

        if ($pendingOrders > 0) {
            return response()->json([
                'message' => 'Cannot delete account with pending orders. Please wait for all orders to be completed.',
                'pending_orders' => $pendingOrders
            ], 400);
        }

        // Mark account for deletion (30-day grace period)
        $user->deletion_requested_at = now();
        $user->deletion_reason = $request->reason;
        $user->deletion_scheduled_at = now()->addDays(30);
        $user->save();

        // Log the request
        SecurityController::logEvent($user->id, 'deletion_requested', 'success', $request->reason);

        return response()->json([
            'message' => 'Account deletion requested successfully',
            'scheduled_at' => $user->deletion_scheduled_at->toDateTimeString(),
            'grace_period_days' => 30,
            'note' => 'You can cancel this request within 30 days by logging in.'
        ]);
    }

    /**
     * Cancel account deletion request
     */
    public function cancelDeletion(Request $request)
    {
        $user = $request->user();

        if (!$user->deletion_requested_at) {
            return response()->json([
                'message' => 'No deletion request found'
            ], 400);
        }

        // Check if grace period has expired
        if (now()->greaterThan($user->deletion_scheduled_at)) {
            return response()->json([
                'message' => 'Grace period has expired. Account deletion cannot be cancelled.'
            ], 400);
        }

        // Cancel deletion
        $user->deletion_requested_at = null;
        $user->deletion_reason = null;
        $user->deletion_scheduled_at = null;
        $user->save();

        // Log the cancellation
        SecurityController::logEvent($user->id, 'deletion_cancelled', 'success');

        return response()->json([
            'message' => 'Account deletion cancelled successfully'
        ]);
    }

    /**
     * Get data portability (machine-readable format)
     */
    public function portabilityExport(Request $request)
    {
        $user = $request->user();

        // Export in standardized format for data portability
        $portableData = [
            'format' => 'JSON',
            'version' => '1.0',
            'exported_at' => now()->toIso8601String(),
            'user' => [
                'identifier' => $user->id,
                'email' => $user->email,
                'name' => $user->name,
                'created' => $user->created_at->toIso8601String(),
            ],
            'orders' => Order::where('user_id', $user->id)->get()->map(function ($order) {
                return [
                    'id' => $order->id,
                    'date' => $order->created_at->toIso8601String(),
                    'total' => $order->total_amount,
                    'currency' => $order->currency ?? 'USD',
                    'status' => $order->status,
                    'items' => $order->items->map(function ($item) {
                        return [
                            'product' => $item->product_name,
                            'quantity' => $item->quantity,
                            'price' => $item->price,
                        ];
                    }),
                ];
            }),
            'reviews' => Review::where('user_id', $user->id)->get()->map(function ($review) {
                return [
                    'date' => $review->created_at->toIso8601String(),
                    'rating' => $review->rating,
                    'comment' => $review->comment,
                    'product_id' => $review->product_id,
                ];
            }),
        ];

        return response()->json($portableData);
    }

    /**
     * Get consent management
     */
    public function getConsents(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'marketing_emails' => $user->marketing_emails_consent ?? false,
            'data_sharing' => $user->data_sharing_consent ?? false,
            'analytics' => $user->analytics_consent ?? true,
            'personalization' => $user->personalization_consent ?? true,
        ]);
    }

    /**
     * Update consent preferences
     */
    public function updateConsents(Request $request)
    {
        $request->validate([
            'marketing_emails' => 'boolean',
            'data_sharing' => 'boolean',
            'analytics' => 'boolean',
            'personalization' => 'boolean',
        ]);

        $user = $request->user();

        if ($request->has('marketing_emails')) {
            $user->marketing_emails_consent = $request->marketing_emails;
        }
        if ($request->has('data_sharing')) {
            $user->data_sharing_consent = $request->data_sharing;
        }
        if ($request->has('analytics')) {
            $user->analytics_consent = $request->analytics;
        }
        if ($request->has('personalization')) {
            $user->personalization_consent = $request->personalization;
        }

        $user->save();

        // Log consent change
        SecurityController::logEvent($user->id, 'consent_updated', 'success', null, $request->only([
            'marketing_emails', 'data_sharing', 'analytics', 'personalization'
        ]));

        return response()->json([
            'message' => 'Consent preferences updated successfully',
            'consents' => [
                'marketing_emails' => $user->marketing_emails_consent,
                'data_sharing' => $user->data_sharing_consent,
                'analytics' => $user->analytics_consent,
                'personalization' => $user->personalization_consent,
            ]
        ]);
    }
}
