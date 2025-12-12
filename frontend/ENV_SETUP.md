# Environment Setup Guide

## Quick Setup

### 1. Create Environment File

Copy the example file and update with your values:

```powershell
cd c:\wamp64\www\Envisage\frontend
cp .env.local.example .env.local
```

Or create `.env.local` manually with:

```env
# Required - Backend API
NEXT_PUBLIC_API_URL=http://localhost:8000/api
NEXT_PUBLIC_WS_URL=ws://localhost:6001

# Required - Pusher (for real-time features)
NEXT_PUBLIC_PUSHER_KEY=your_pusher_key
NEXT_PUBLIC_PUSHER_CLUSTER=mt1

# Optional - Analytics
NEXT_PUBLIC_POSTHOG_KEY=
NEXT_PUBLIC_SENTRY_DSN=

# Development
ANALYZE=false
```

### 2. Get Pusher Credentials

1. Go to [Pusher.com](https://pusher.com/)
2. Create a free account
3. Create a new Channels app
4. Copy your app credentials:
   - **App ID**: Add to Laravel `.env` as `PUSHER_APP_ID`
   - **Key**: Add to frontend `.env.local` as `NEXT_PUBLIC_PUSHER_KEY`
   - **Secret**: Add to Laravel `.env` as `PUSHER_APP_SECRET`
   - **Cluster**: Add to both as `PUSHER_APP_CLUSTER`

### 3. Optional Services

#### Sentry (Error Tracking)
1. Go to [Sentry.io](https://sentry.io/)
2. Create account and project
3. Copy DSN to `NEXT_PUBLIC_SENTRY_DSN`

#### PostHog (Analytics)
1. Go to [PostHog.com](https://posthog.com/)
2. Create account
3. Copy project API key to `NEXT_PUBLIC_POSTHOG_KEY`

## Backend Configuration

Ensure your Laravel backend has these in `.env`:

```env
BROADCAST_DRIVER=pusher

PUSHER_APP_ID=your_app_id
PUSHER_APP_KEY=your_key
PUSHER_APP_SECRET=your_secret
PUSHER_APP_CLUSTER=mt1
```

## Testing the Setup

### Test Real-time Connection

```powershell
npm run dev
```

Open browser console and check for:
- ✅ "Echo initialized" message
- ✅ No WebSocket connection errors
- ✅ Pusher connection state: "connected"

### Test Without Pusher (Development)

If you don't have Pusher set up yet, the app will still work but real-time features will be disabled.

## Troubleshooting

### Issue: Pusher connection fails
**Solution**: Check that:
- Pusher credentials are correct
- Laravel broadcasting is enabled
- Laravel Echo server is running (if using Laravel Echo Server instead of Pusher)

### Issue: CORS errors
**Solution**: Add to Laravel `config/cors.php`:
```php
'paths' => ['api/*', 'broadcasting/auth'],
```

### Issue: Authentication errors
**Solution**: Ensure your Laravel API returns proper authentication tokens and they're stored in localStorage as `auth_token`.

## Next Steps

After environment setup:
1. Run `npm run dev` to start development server
2. Test PWA features with `npm run build && npm start`
3. Analyze bundle size with `ANALYZE=true npm run build`
