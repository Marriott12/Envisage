# Social Authentication Setup Guide

This guide will help you set up Facebook and Google OAuth authentication for the Envisage Marketplace.

## Prerequisites

- Laravel Socialite package installed ✅
- Database migration completed ✅
- Backend routes configured ✅

## Step 1: Create Facebook App

1. Go to [Facebook Developers](https://developers.facebook.com/)
2. Click "My Apps" → "Create App"
3. Choose "Consumer" as app type
4. Fill in app details:
   - App Name: "Envisage Marketplace"
   - Contact Email: Your email
5. In the app dashboard, go to "Settings" → "Basic"
6. Note down:
   - **App ID** (this is your FACEBOOK_CLIENT_ID)
   - **App Secret** (this is your FACEBOOK_CLIENT_SECRET)
7. Add "Facebook Login" product to your app
8. In "Facebook Login" → "Settings":
   - Set "Valid OAuth Redirect URIs" to:
     ```
     https://envisagezm.com/api/auth/facebook/callback
     http://localhost:8000/api/auth/facebook/callback (for local testing)
     ```
9. Make your app live:
   - Go to "App Settings" → "Basic"
   - Switch "App Mode" from "Development" to "Live"

## Step 2: Create Google OAuth App

1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Create a new project or select existing one
3. Navigate to "APIs & Services" → "Credentials"
4. Click "Create Credentials" → "OAuth 2.0 Client ID"
5. Configure the OAuth consent screen if prompted:
   - User Type: External
   - App name: "Envisage Marketplace"
   - User support email: Your email
   - Developer contact: Your email
6. Create OAuth Client ID:
   - Application type: "Web application"
   - Name: "Envisage Marketplace"
   - Authorized redirect URIs:
     ```
     https://envisagezm.com/api/auth/facebook/callback
     http://localhost:8000/api/auth/google/callback (for local testing)
     ```
7. Note down:
   - **Client ID** (this is your GOOGLE_CLIENT_ID)
   - **Client Secret** (this is your GOOGLE_CLIENT_SECRET)

## Step 3: Update Environment Variables

Add the following to your `.env` file on the production server:

```env
# Social Authentication
FACEBOOK_CLIENT_ID=your_facebook_app_id_here
FACEBOOK_CLIENT_SECRET=your_facebook_app_secret_here
FACEBOOK_REDIRECT_URL=https://envisagezm.com/api/auth/facebook/callback

GOOGLE_CLIENT_ID=your_google_client_id_here
GOOGLE_CLIENT_SECRET=your_google_client_secret_here
GOOGLE_REDIRECT_URL=https://envisagezm.com/api/auth/google/callback
```

## Step 4: Deploy Backend Changes

Upload the following files to your production server (`/home/envithcy/envisage/`):

1. **Updated Files:**
   - `composer.json` (added laravel/socialite)
   - `composer.lock` (updated dependencies)
   - `app/Http/Controllers/Api/SocialAuthController.php` (new file)
   - `routes/api.php` (added social auth routes)
   - `config/services.php` (added Facebook/Google config)
   - `app/Models/User.php` (added provider fields)
   - `database/migrations/2025_11_18_105744_add_social_auth_to_users_table.php` (new file)
   - `.env` (add OAuth credentials)

2. **Run on production server via SSH or cPanel Terminal:**
   ```bash
   cd /home/envithcy/envisage
   composer install --no-dev --optimize-autoloader
   php artisan migrate --force
   php artisan config:cache
   php artisan route:cache
   ```

## Step 5: API Endpoints

The following endpoints are now available:

### Facebook Authentication
- **Redirect to Facebook:** `GET /api/auth/facebook`
- **Callback:** `GET /api/auth/facebook/callback`

### Google Authentication
- **Redirect to Google:** `GET /api/auth/google`
- **Callback:** `GET /api/auth/google/callback`

## Step 6: Frontend Integration (Next Steps)

You'll need to add social login buttons to your frontend. Example implementation:

```typescript
// Facebook Login
const handleFacebookLogin = () => {
  window.location.href = 'https://envisagezm.com/api/auth/facebook';
};

// Google Login
const handleGoogleLogin = () => {
  window.location.href = 'https://envisagezm.com/api/auth/google';
};
```

The callback will return a JSON response with:
```json
{
  "status": "success",
  "data": {
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com",
      "role": "buyer"
    },
    "token": "your_auth_token_here"
  }
}
```

## Database Changes

The migration adds two new columns to the `users` table:
- `provider` (varchar 50, nullable) - stores "facebook" or "google"
- `provider_id` (varchar 100, nullable) - stores the social provider's user ID
- Indexes on both columns for faster lookups

## How It Works

1. User clicks "Login with Facebook" or "Login with Google" button
2. User is redirected to Facebook/Google OAuth consent screen
3. After approval, user is redirected back to your callback URL
4. Backend receives user data from social provider
5. System checks if user exists:
   - If exists by provider ID: Log them in
   - If exists by email: Link social provider to existing account
   - If new user: Create new account with social data
6. Backend returns authentication token
7. Frontend stores token and redirects to dashboard

## Security Notes

- Social login users are auto-verified (no email verification needed)
- Random passwords are generated for social accounts
- Provider IDs are indexed for fast lookups
- All endpoints use rate limiting (5 requests per minute)
- Stateless authentication (works with SPA/API)

## Testing

### Local Testing
1. Update your local `.env` with the OAuth credentials
2. Add `http://localhost:8000/api/auth/facebook/callback` and `http://localhost:8000/api/auth/google/callback` to your OAuth app redirect URIs
3. Test the flow locally

### Production Testing
1. Ensure all credentials are in production `.env`
2. Test each provider:
   - Navigate to `https://envisagezm.com/api/auth/facebook`
   - Complete OAuth flow
   - Verify token is returned
3. Check database for new user entries

## Troubleshooting

### "App Not Setup" Error
- Ensure Facebook app is in "Live" mode, not "Development"
- Check that redirect URIs match exactly (including https://)

### "Redirect URI Mismatch" Error
- Verify redirect URIs in Facebook/Google console match your `.env` settings exactly

### "Invalid Client" Error
- Double-check your CLIENT_ID and CLIENT_SECRET in `.env`
- Ensure no extra spaces or quotes

### Users Not Being Created
- Check Laravel logs: `storage/logs/laravel.log`
- Verify database connection
- Ensure migration ran successfully: `php artisan migrate:status`

## Next Steps

1. ✅ Install Laravel Socialite
2. ✅ Create OAuth apps (Facebook & Google)
3. ✅ Update environment variables
4. ✅ Deploy backend changes
5. ⏳ Update frontend with social login buttons
6. ⏳ Test OAuth flow end-to-end
7. ⏳ Handle token storage in frontend
8. ⏳ Add error handling for failed OAuth attempts

## Support

For issues, check:
- Laravel Socialite Documentation: https://laravel.com/docs/8.x/socialite
- Facebook Login Docs: https://developers.facebook.com/docs/facebook-login
- Google OAuth Docs: https://developers.google.com/identity/protocols/oauth2
