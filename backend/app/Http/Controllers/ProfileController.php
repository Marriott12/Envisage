<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\NotificationPreference;
use App\Models\ShippingAddress;
use App\Models\UserSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use PragmaRX\Google2FA\Google2FA;

class ProfileController extends Controller
{
    // Get user profile with all related data
    public function show(Request $request)
    {
        $user = $request->user()->load([
            'notificationPreferences',
            'shippingAddresses',
            'userSessions' => function($query) {
                $query->orderBy('last_activity', 'desc')->limit(5);
            }
        ]);

        return response()->json([
            'success' => true,
            'data' => $user,
        ]);
    }

    // Update basic profile information
    public function update(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:20',
            'bio' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $user->update($request->only(['name', 'email', 'phone', 'bio']));

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'data' => $user,
        ]);
    }

    // Upload avatar
    public function uploadAvatar(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'avatar' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();

        // Delete old avatar if exists
        if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
            Storage::disk('public')->delete($user->avatar);
        }

        // Store new avatar
        $path = $request->file('avatar')->store('avatars', 'public');
        $user->avatar = $path;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Avatar uploaded successfully',
            'data' => [
                'avatar' => Storage::url($path),
            ],
        ]);
    }

    // Change password
    public function changePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Current password is incorrect',
            ], 422);
        }

        $user->password = Hash::make($request->new_password);
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Password changed successfully',
        ]);
    }

    // Enable Two-Factor Authentication
    public function enableTwoFactor(Request $request)
    {
        $user = $request->user();
        $google2fa = new Google2FA();

        $secret = $google2fa->generateSecretKey();
        $qrCodeUrl = $google2fa->getQRCodeUrl(
            config('app.name'),
            $user->email,
            $secret
        );

        $user->two_factor_secret = $secret;
        $user->save();

        return response()->json([
            'success' => true,
            'data' => [
                'secret' => $secret,
                'qr_code_url' => $qrCodeUrl,
            ],
        ]);
    }

    // Confirm and activate Two-Factor Authentication
    public function confirmTwoFactor(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string|size:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();
        $google2fa = new Google2FA();

        $valid = $google2fa->verifyKey($user->two_factor_secret, $request->code);

        if (!$valid) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid verification code',
            ], 422);
        }

        $user->two_factor_enabled = true;
        $user->two_factor_confirmed_at = now();
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Two-factor authentication enabled successfully',
        ]);
    }

    // Disable Two-Factor Authentication
    public function disableTwoFactor(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();

        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Incorrect password',
            ], 422);
        }

        $user->two_factor_enabled = false;
        $user->two_factor_secret = null;
        $user->two_factor_confirmed_at = null;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Two-factor authentication disabled successfully',
        ]);
    }

    // Get notification preferences
    public function getNotificationPreferences(Request $request)
    {
        $preferences = $request->user()->notificationPreferences;

        if (!$preferences) {
            $preferences = NotificationPreference::create([
                'user_id' => $request->user()->id,
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $preferences,
        ]);
    }

    // Update notification preferences
    public function updateNotificationPreferences(Request $request)
    {
        $user = $request->user();

        $preferences = $user->notificationPreferences;
        if (!$preferences) {
            $preferences = NotificationPreference::create(['user_id' => $user->id]);
        }

        $preferences->update($request->only([
            'email_order_updates',
            'email_promotions',
            'email_price_alerts',
            'email_new_messages',
            'push_order_updates',
            'push_promotions',
            'push_price_alerts',
            'push_new_messages',
            'sms_order_updates',
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Notification preferences updated',
            'data' => $preferences,
        ]);
    }

    // Shipping Addresses CRUD
    public function getShippingAddresses(Request $request)
    {
        $addresses = $request->user()->shippingAddresses;

        return response()->json([
            'success' => true,
            'data' => $addresses,
        ]);
    }

    public function createShippingAddress(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'label' => 'required|string|max:50',
            'recipient_name' => 'required|string|max:255',
            'address_line1' => 'required|string|max:255',
            'address_line2' => 'nullable|string|max:255',
            'city' => 'required|string|max:100',
            'state' => 'required|string|max:100',
            'postal_code' => 'required|string|max:20',
            'country' => 'required|string|max:100',
            'phone' => 'required|string|max:20',
            'is_default' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $address = ShippingAddress::create([
            'user_id' => $request->user()->id,
            ...$request->only([
                'label', 'recipient_name', 'address_line1', 'address_line2',
                'city', 'state', 'postal_code', 'country', 'phone', 'is_default'
            ]),
        ]);

        if ($request->is_default) {
            $address->setAsDefault();
        }

        return response()->json([
            'success' => true,
            'message' => 'Shipping address created successfully',
            'data' => $address,
        ], 201);
    }

    public function updateShippingAddress(Request $request, $id)
    {
        $address = ShippingAddress::where('user_id', $request->user()->id)->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'label' => 'sometimes|string|max:50',
            'recipient_name' => 'sometimes|string|max:255',
            'address_line1' => 'sometimes|string|max:255',
            'address_line2' => 'nullable|string|max:255',
            'city' => 'sometimes|string|max:100',
            'state' => 'sometimes|string|max:100',
            'postal_code' => 'sometimes|string|max:20',
            'country' => 'sometimes|string|max:100',
            'phone' => 'sometimes|string|max:20',
            'is_default' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $address->update($request->all());

        if ($request->is_default) {
            $address->setAsDefault();
        }

        return response()->json([
            'success' => true,
            'message' => 'Shipping address updated successfully',
            'data' => $address,
        ]);
    }

    public function deleteShippingAddress(Request $request, $id)
    {
        $address = ShippingAddress::where('user_id', $request->user()->id)->findOrFail($id);
        $address->delete();

        return response()->json([
            'success' => true,
            'message' => 'Shipping address deleted successfully',
        ]);
    }

    // Get active sessions
    public function getActiveSessions(Request $request)
    {
        $sessions = $request->user()->userSessions()
            ->orderBy('last_activity', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $sessions,
        ]);
    }

    // Log current session
    public function logSession(Request $request)
    {
        UserSession::create([
            'user_id' => $request->user()->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'device_type' => $this->getDeviceType($request->userAgent()),
            'last_activity' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Session logged',
        ]);
    }

    private function getDeviceType($userAgent)
    {
        if (preg_match('/mobile/i', $userAgent)) {
            return 'Mobile';
        } elseif (preg_match('/tablet/i', $userAgent)) {
            return 'Tablet';
        }
        return 'Desktop';
    }
}
