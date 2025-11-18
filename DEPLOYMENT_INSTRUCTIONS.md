# Social Authentication Deployment Instructions

## What Was Implemented

✅ **Backend Changes:**
- Installed Laravel Socialite package (v5.23.1)
- Created `SocialAuthController` with Facebook & Google OAuth handlers
- Added API routes: `/auth/facebook`, `/auth/google` and their callbacks
- Created database migration for `provider` and `provider_id` columns
- Updated User model to support social authentication
- Added OAuth configuration to `config/services.php`
- Environment variables added for Facebook & Google credentials

✅ **Git Repository:**
- All changes committed to GitHub (commit: 2c1d173)
- Repository: https://github.com/Marriott12/Envisage

---

## Step 1: Set Up OAuth Applications

### Facebook Developer App

1. Visit: https://developers.facebook.com/
2. Create new app → Choose "Consumer"
3. Go to **Settings → Basic**
4. Copy your **App ID** and **App Secret**
5. Add **Facebook Login** product
6. In **Facebook Login → Settings**, add these redirect URIs:
   ```
   https://envisagezm.com/api/auth/facebook/callback
   ```
7. Switch app from "Development" to "Live" mode

### Google Cloud Console

1. Visit: https://console.cloud.google.com/
2. Create new project or select existing
3. Go to **APIs & Services → Credentials**
4. Create **OAuth 2.0 Client ID**
5. Configure consent screen:
   - App name: Envisage Marketplace
   - User support email: your email
6. Create credentials:
   - Application type: Web application
   - Authorized redirect URIs:
     ```
     https://envisagezm.com/api/auth/google/callback
     ```
7. Copy **Client ID** and **Client Secret**

---

## Step 2: Deploy Backend to cPanel

### Option A: Pull from GitHub (Recommended)

```bash
# SSH into your server
cd /home/envithcy/envisage

# Pull latest changes
git pull origin main

# Install new dependencies
composer install --no-dev --optimize-autoloader

# Run migration
php artisan migrate --force

# Clear and rebuild caches
php artisan config:clear
php artisan route:clear
php artisan cache:clear
php artisan config:cache
php artisan route:cache
```

### Option B: Manual File Upload

Upload these files via cPanel File Manager to `/home/envithcy/envisage/`:

**New Files:**
- `app/Http/Controllers/Api/SocialAuthController.php`
- `database/migrations/2025_11_18_105744_add_social_auth_to_users_table.php`

**Updated Files:**
- `composer.json`
- `composer.lock`
- `routes/api.php`
- `config/services.php`
- `app/Models/User.php`

Then run in cPanel Terminal:
```bash
cd /home/envithcy/envisage
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache
```

---

## Step 3: Update Environment Variables

Edit `/home/envithcy/envisage/.env` and add:

```env
# Social Authentication
FACEBOOK_CLIENT_ID=your_facebook_app_id_here
FACEBOOK_CLIENT_SECRET=your_facebook_app_secret_here
FACEBOOK_REDIRECT_URL=https://envisagezm.com/api/auth/facebook/callback

GOOGLE_CLIENT_ID=your_google_client_id_here
GOOGLE_CLIENT_SECRET=your_google_client_secret_here
GOOGLE_REDIRECT_URL=https://envisagezm.com/api/auth/google/callback
```

**Important:** Replace `your_*_here` with actual credentials from Step 1.

After updating, clear config cache:
```bash
php artisan config:clear
php artisan config:cache
```

---

## Step 4: Verify Backend Deployment

### Test API Endpoints

1. **Test Facebook Redirect:**
   ```
   https://envisagezm.com/api/auth/facebook
   ```
   Should redirect to Facebook login page.

2. **Test Google Redirect:**
   ```
   https://envisagezm.com/api/auth/google
   ```
   Should redirect to Google login page.

3. **Check Database:**
   ```sql
   DESCRIBE users;
   ```
   Should show `provider` and `provider_id` columns.

### Test Complete OAuth Flow

1. Navigate to: `https://envisagezm.com/api/auth/facebook`
2. Grant permissions on Facebook
3. You should be redirected back with JSON response:
   ```json
   {
     "status": "success",
     "data": {
       "user": {
         "id": 1,
         "name": "Your Name",
         "email": "your@email.com",
         "role": "buyer"
       },
       "token": "authentication_token_here"
     }
   }
   ```
4. Repeat for Google OAuth

---

## Step 5: Frontend Integration (Next Steps)

You need to add social login buttons to your frontend application.

### Example Implementation

**Update `frontend/app/login/page.tsx`:**

```tsx
const handleFacebookLogin = () => {
  window.location.href = 'https://envisagezm.com/api/auth/facebook';
};

const handleGoogleLogin = () => {
  window.location.href = 'https://envisagezm.com/api/auth/google';
};

// Add buttons to your login form:
<button 
  onClick={handleFacebookLogin}
  className="w-full bg-blue-600 text-white py-2 rounded"
>
  Continue with Facebook
</button>

<button 
  onClick={handleGoogleLogin}
  className="w-full bg-red-600 text-white py-2 rounded"
>
  Continue with Google
</button>
```

### Handle OAuth Callback

The OAuth callback returns JSON, so you'll need to:

1. Create a callback page that extracts the token
2. Store the token in localStorage
3. Redirect to dashboard

**Example callback handler:**

```tsx
// frontend/app/auth/callback/page.tsx
'use client';

import { useEffect } from 'react';
import { useRouter } from 'next/navigation';

export default function AuthCallback() {
  const router = useRouter();

  useEffect(() => {
    // Extract token from URL or response
    const params = new URLSearchParams(window.location.search);
    const token = params.get('token');
    
    if (token) {
      localStorage.setItem('token', token);
      router.push('/dashboard');
    }
  }, []);

  return <div>Completing authentication...</div>;
}
```

---

## Technical Details

### New API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/auth/facebook` | Redirect to Facebook OAuth |
| GET | `/api/auth/facebook/callback` | Handle Facebook callback |
| GET | `/api/auth/google` | Redirect to Google OAuth |
| GET | `/api/auth/google/callback` | Handle Google callback |

### Database Schema Changes

```sql
ALTER TABLE users 
ADD COLUMN provider VARCHAR(50) NULL,
ADD COLUMN provider_id VARCHAR(100) NULL,
ADD INDEX (provider),
ADD INDEX (provider_id);
```

### How Authentication Works

1. User clicks "Login with Facebook/Google"
2. Frontend redirects to `/api/auth/{provider}`
3. Laravel redirects to OAuth provider
4. User approves on Facebook/Google
5. OAuth provider redirects to `/api/auth/{provider}/callback`
6. Backend receives user data from OAuth provider
7. Backend creates or updates user record
8. Backend generates Sanctum token
9. Backend returns JSON with user data and token
10. Frontend stores token and redirects to dashboard

### Security Features

- **Stateless OAuth** - Works with SPA architecture
- **Rate Limiting** - 5 requests per minute
- **Auto-verification** - Social login users are auto-verified
- **Account Linking** - Existing email accounts are linked to social providers
- **Random Passwords** - Social accounts get secure random passwords
- **Indexed Lookups** - Fast provider ID queries

---

## Troubleshooting

### "App Not Setup" Error (Facebook)
- Ensure app is in "Live" mode, not "Development"
- Check redirect URI matches exactly

### "Redirect URI Mismatch" Error
- Verify redirect URIs in OAuth console match `.env` exactly
- Ensure using `https://` not `http://`

### "Invalid Client" Error
- Double-check CLIENT_ID and CLIENT_SECRET in `.env`
- Run `php artisan config:clear` after updating `.env`

### Users Not Created
- Check Laravel logs: `/storage/logs/laravel.log`
- Verify migration ran: `php artisan migrate:status`
- Test database connection

### OAuth Loop/Infinite Redirect
- Clear browser cache and cookies
- Check CORS configuration in `config/cors.php`
- Verify callback URLs don't have trailing slashes

---

## Production Checklist

- [ ] Facebook app created and in "Live" mode
- [ ] Google OAuth app created
- [ ] OAuth credentials added to production `.env`
- [ ] Backend code deployed to cPanel
- [ ] Composer dependencies installed
- [ ] Database migration executed
- [ ] Config cache cleared and rebuilt
- [ ] Facebook OAuth flow tested end-to-end
- [ ] Google OAuth flow tested end-to-end
- [ ] Frontend buttons added for social login
- [ ] Token storage implemented in frontend
- [ ] Error handling added for failed OAuth

---

## Support Resources

- **Laravel Socialite Docs:** https://laravel.com/docs/8.x/socialite
- **Facebook Login Docs:** https://developers.facebook.com/docs/facebook-login
- **Google OAuth Docs:** https://developers.google.com/identity/protocols/oauth2
- **Your Setup Guide:** `SOCIAL_AUTH_SETUP.md`

---

## Git Commits

Latest commits related to social authentication:

1. **2c1d173** - Fix: Update .gitignore to exclude build files
2. **a954450** - Feature: Add Facebook and Google OAuth social authentication
3. **50e6f30** - Fix: Add Vercel frontend to CORS allowed origins

View full history: https://github.com/Marriott12/Envisage/commits/main
