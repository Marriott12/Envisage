<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthController extends Controller
{
    /**
     * Redirect to Facebook for authentication
     */
    public function redirectToFacebook()
    {
        return Socialite::driver('facebook')
            ->stateless()
            ->redirect();
    }

    /**
     * Handle Facebook callback
     */
    public function handleFacebookCallback(Request $request)
    {
        try {
            $socialUser = Socialite::driver('facebook')
                ->stateless()
                ->user();

            return $this->handleSocialUser($socialUser, 'facebook');
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to authenticate with Facebook: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Redirect to Google for authentication
     */
    public function redirectToGoogle()
    {
        return Socialite::driver('google')
            ->stateless()
            ->redirect();
    }

    /**
     * Handle Google callback
     */
    public function handleGoogleCallback(Request $request)
    {
        try {
            $socialUser = Socialite::driver('google')
                ->stateless()
                ->user();

            return $this->handleSocialUser($socialUser, 'google');
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to authenticate with Google: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Handle social user authentication/registration
     */
    private function handleSocialUser($socialUser, $provider)
    {
        // Check if user exists with this provider ID
        $user = User::where('provider', $provider)
            ->where('provider_id', $socialUser->getId())
            ->first();

        // If not found, check if email exists
        if (!$user) {
            $user = User::where('email', $socialUser->getEmail())->first();
            
            if ($user) {
                // Link existing user with social provider
                $user->update([
                    'provider' => $provider,
                    'provider_id' => $socialUser->getId(),
                ]);
            }
        }

        // Create new user if doesn't exist
        if (!$user) {
            $user = User::create([
                'name' => $socialUser->getName(),
                'email' => $socialUser->getEmail(),
                'provider' => $provider,
                'provider_id' => $socialUser->getId(),
                'password' => Hash::make(Str::random(16)), // Random password
                'email_verified_at' => now(), // Auto-verify social logins
                'role' => 'buyer', // Default role
            ]);
        }

        // Create token
        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role ?? 'buyer',
                ],
                'token' => $token,
            ]
        ]);
    }
}
