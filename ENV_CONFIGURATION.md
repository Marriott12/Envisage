# Environment Configuration Guide

## 📋 Required Environment Variables

Copy this to your `.env` file and update with your values:

```env
# ==================== APPLICATION ====================
APP_NAME="Envisage Marketplace"
APP_ENV=production  # or local for development
APP_KEY=base64:your_generated_key_here
APP_DEBUG=false  # true for development
APP_URL=https://envisagezm.com

# ==================== DATABASE ====================
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=envithcy_envisage
DB_USERNAME=your_database_username
DB_PASSWORD=your_database_password

# ==================== MAIL CONFIGURATION ====================
# Option 1: Gmail (Development)
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your_email@gmail.com
MAIL_PASSWORD=your_app_password
MAIL_ENCRYPTION=tls

# Option 2: SendGrid (Production - Recommended)
# MAIL_MAILER=smtp
# MAIL_HOST=smtp.sendgrid.net
# MAIL_PORT=587
# MAIL_USERNAME=apikey
# MAIL_PASSWORD=your_sendgrid_api_key
# MAIL_ENCRYPTION=tls

MAIL_FROM_ADDRESS=noreply@envisagezm.com
MAIL_FROM_NAME="${APP_NAME}"

# ==================== STRIPE CONFIGURATION ====================
# Get from: https://dashboard.stripe.com/apikeys
STRIPE_KEY=pk_test_your_publishable_key_here
STRIPE_SECRET=sk_test_your_secret_key_here
STRIPE_WEBHOOK_SECRET=whsec_your_webhook_secret_here

# ==================== QUEUE & CACHE ====================
QUEUE_CONNECTION=database  # Use 'redis' for production
CACHE_DRIVER=file  # Use 'redis' for production
SESSION_DRIVER=file

# ==================== REDIS (Optional) ====================
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# ==================== FRONTEND URL ====================
FRONTEND_URL=http://localhost:3000  # Development
# FRONTEND_URL=https://envisagezm.com  # Production

# ==================== BROADCASTING (WebSocket) ====================
BROADCAST_DRIVER=log  # Change to 'pusher' for real-time chat
PUSHER_APP_ID=local
PUSHER_APP_KEY=local
PUSHER_APP_SECRET=local
PUSHER_HOST=127.0.0.1
PUSHER_PORT=6001
PUSHER_SCHEME=http

# ==================== AWS S3 (Optional - for file storage) ====================
# AWS_ACCESS_KEY_ID=
# AWS_SECRET_ACCESS_KEY=
# AWS_DEFAULT_REGION=us-east-1
# AWS_BUCKET=
# AWS_USE_PATH_STYLE_ENDPOINT=false
```

## 🔑 How to Get API Keys

### Stripe Keys
1. Go to https://dashboard.stripe.com/
2. Create account or login
3. Navigate to Developers → API keys
4. Copy Publishable key → `STRIPE_KEY`
5. Copy Secret key → `STRIPE_SECRET`
6. For webhooks:
   - Go to Developers → Webhooks
   - Click "Add endpoint"
   - URL: `https://yourdomain.com/api/subscriptions/webhook`
   - Select events: All subscription and invoice events
   - Copy webhook secret → `STRIPE_WEBHOOK_SECRET`

### SendGrid API Key
1. Go to https://sendgrid.com/
2. Create account
3. Navigate to Settings → API Keys
4. Create new API key with "Mail Send" permissions
5. Copy key → `MAIL_PASSWORD`

### Gmail App Password
1. Enable 2-factor authentication on Gmail
2. Go to https://myaccount.google.com/apppasswords
3. Select "Mail" and "Other" (custom name)
4. Copy generated password → `MAIL_PASSWORD`

## ⚙️ Quick Setup Commands

```bash
# 1. Copy environment file
cp .env.example .env

# 2. Generate application key
php artisan key:generate

# 3. Create storage link
php artisan storage:link

# 4. Run migrations
php artisan migrate

# 5. Start queue worker
php artisan queue:work

# 6. (In another terminal) Start development server
php artisan serve
```

## 🧪 Testing Configuration

Test if everything is configured correctly:

```bash
# Test database connection
php artisan tinker
>>> DB::connection()->getPdo();
>>> exit

# Test email
php artisan tinker
>>> Mail::raw('Test', function($msg) { $msg->to('test@example.com'); });
>>> exit

# Test API
curl http://localhost:8000/api/test
```

## 🚀 Production Checklist

Before deploying to production:

- [ ] Change `APP_ENV` to `production`
- [ ] Set `APP_DEBUG` to `false`
- [ ] Use strong `APP_KEY`
- [ ] Use production database credentials
- [ ] Use production Stripe keys (pk_live_*, sk_live_*)
- [ ] Configure production mail service (SendGrid)
- [ ] Set correct `FRONTEND_URL`
- [ ] Use Redis for `QUEUE_CONNECTION` and `CACHE_DRIVER`
- [ ] Configure SSL certificate
- [ ] Set up supervisor for queue workers
- [ ] Configure cron for scheduler
- [ ] Enable error logging (Sentry)
- [ ] Set up database backups
