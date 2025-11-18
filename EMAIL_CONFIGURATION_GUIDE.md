# 📧 Email Configuration Guide for Envisage Marketplace

## Overview
The marketplace needs email functionality for:
- User registration verification
- Password resets
- Order confirmations
- Seller notifications
- Support communications
- Marketing (optional)

---

## Option 1: cPanel Email (Recommended for Your Setup)

### Step 1: Create Email Account in cPanel

1. Login to cPanel at https://server219.web-hosting.com:2083
2. Go to **Email Accounts**
3. Click **Create**
4. Set up:
   - **Email:** `noreply@envisagezm.com`
   - **Password:** (Generate strong password)
   - **Storage:** 250 MB is sufficient
5. Click **Create**

### Step 2: Get Email Settings

After creating, note these settings:
- **Incoming Server:** mail.envisagezm.com
- **SMTP Server:** mail.envisagezm.com
- **SMTP Port:** 587 (TLS) or 465 (SSL)
- **Username:** noreply@envisagezm.com
- **Password:** [the password you set]

### Step 3: Update Backend .env

SSH into server and edit `.env`:
```bash
cd /home/envithcy/envisage
nano .env
```

Update these lines:
```bash
MAIL_MAILER=smtp
MAIL_HOST=mail.envisagezm.com
MAIL_PORT=587
MAIL_USERNAME=noreply@envisagezm.com
MAIL_PASSWORD=your_actual_password_here
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@envisagezm.com
MAIL_FROM_NAME="Envisage Marketplace"
```

Save and exit (Ctrl+X, Y, Enter)

### Step 4: Clear Cache and Test

```bash
php artisan config:cache
php artisan tinker
```

Test email:
```php
use Illuminate\Support\Facades\Mail;

Mail::raw('Test email from Envisage Marketplace', function($message) {
    $message->to('your-email@example.com')
            ->subject('Test Email');
});

// Should return true if successful
exit
```

---

## Option 2: Gmail SMTP (For Testing Only)

⚠️ **Not recommended for production** - Has daily limits and may get blocked

### Setup:
1. Enable 2-factor authentication on Gmail
2. Generate App Password: https://myaccount.google.com/apppasswords
3. Update `.env`:

```bash
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your_app_password_here
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your-email@gmail.com
MAIL_FROM_NAME="Envisage Marketplace"
```

---

## Option 3: SendGrid (Professional Solution)

Great for high volume, reliable delivery, analytics.

### Setup:
1. Sign up at https://sendgrid.com (Free tier: 100 emails/day)
2. Verify your sender email
3. Create API key
4. Update `.env`:

```bash
MAIL_MAILER=smtp
MAIL_HOST=smtp.sendgrid.net
MAIL_PORT=587
MAIL_USERNAME=apikey
MAIL_PASSWORD=your_sendgrid_api_key_here
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@envisagezm.com
MAIL_FROM_NAME="Envisage Marketplace"
```

**Pricing:**
- Free: 100 emails/day
- Essentials: $19.95/month - 50,000 emails
- Pro: $89.95/month - 100,000 emails

---

## Option 4: Mailgun

Another professional email service with good deliverability.

### Setup:
1. Sign up at https://www.mailgun.com (Free tier: 5,000 emails/month)
2. Add and verify your domain
3. Get SMTP credentials
4. Update `.env`:

```bash
MAIL_MAILER=mailgun
MAILGUN_DOMAIN=mg.envisagezm.com
MAILGUN_SECRET=your_mailgun_api_key_here
MAILGUN_ENDPOINT=api.mailgun.net
MAIL_FROM_ADDRESS=noreply@envisagezm.com
MAIL_FROM_NAME="Envisage Marketplace"
```

---

## Email Templates

The marketplace has built-in templates for:
- Welcome email (user registration)
- Email verification
- Password reset
- Order confirmation
- Order status updates
- Seller notifications

These are located in:
```
backend/resources/views/emails/
```

To customize:
1. Edit the Blade templates
2. Update branding/colors
3. Add your logo

---

## Email Queue (Recommended for Production)

For better performance, use email queues:

### Setup Queue Driver:

**Option A: Database Queue (Simple)**
```bash
# In .env
QUEUE_CONNECTION=database

# Create jobs table
php artisan queue:table
php artisan migrate

# Run queue worker (in screen or supervisor)
php artisan queue:work --daemon
```

**Option B: Redis Queue (Better Performance)**
```bash
# In .env
QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379

# Run queue worker
php artisan queue:work redis --daemon
```

### Setup Supervisor (Keep Queue Worker Running)

Create `/etc/supervisor/conf.d/envisage-worker.conf`:
```ini
[program:envisage-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /home/envithcy/envisage/artisan queue:work --sleep=3 --tries=3
autostart=true
autorestart=true
user=envithcy
numprocs=2
redirect_stderr=true
stdout_logfile=/home/envithcy/envisage/storage/logs/worker.log
```

---

## Testing Checklist

After configuration, test these:

```bash
php artisan tinker
```

### 1. Simple Test Email
```php
Mail::raw('Hello from Envisage!', function($msg) {
    $msg->to('test@example.com')->subject('Test');
});
```

### 2. Test User Registration Email
```php
$user = App\Models\User::first();
$user->sendEmailVerificationNotification();
```

### 3. Test Password Reset
```php
use Illuminate\Support\Facades\Password;
Password::sendResetLink(['email' => 'admin@envisagezm.com']);
```

### 4. Test Order Confirmation
```php
// After creating an order
$order = App\Models\Order::first();
Mail::to($order->user->email)->send(new App\Mail\OrderConfirmation($order));
```

---

## Troubleshooting

### Email Not Sending?

**Check logs:**
```bash
tail -f storage/logs/laravel.log
```

**Common issues:**

1. **Authentication failed**
   - Check username/password
   - Verify 2FA or app passwords if using Gmail

2. **Connection timeout**
   - Check firewall rules
   - Verify port (587 vs 465)
   - Try different encryption (tls vs ssl)

3. **TLS error**
   - Change `MAIL_ENCRYPTION=ssl` and `MAIL_PORT=465`
   - Or disable verification (not recommended):
   ```bash
   MAIL_VERIFY_PEER=false
   ```

4. **SPF/DKIM issues**
   - Configure SPF record in DNS
   - Setup DKIM in cPanel

### Email Goes to Spam?

**Fix deliverability:**

1. **Add SPF Record** (in DNS)
   ```
   Type: TXT
   Name: @
   Value: v=spf1 include:_spf.google.com ~all
   ```

2. **Add DKIM** (in cPanel > Email Deliverability)
   - Install DKIM
   - Add generated record to DNS

3. **Add DMARC Record**
   ```
   Type: TXT
   Name: _dmarc
   Value: v=DMARC1; p=none; rua=mailto:admin@envisagezm.com
   ```

4. **Use Professional Service** (SendGrid/Mailgun)
   - Better deliverability
   - Automatic SPF/DKIM setup

---

## Production Checklist

Before going live:

- [ ] Email account created
- [ ] SMTP credentials configured
- [ ] Config cache cleared
- [ ] Test email sent successfully
- [ ] SPF record added
- [ ] DKIM configured
- [ ] Email templates customized
- [ ] Queue worker running (optional)
- [ ] Monitoring setup (optional)

---

## Recommended Setup for Production

**For Envisage Marketplace:**

1. **Start with cPanel email** - It's included in your hosting
2. **Monitor volume** - If sending >1000 emails/day, consider SendGrid
3. **Setup queues** - Use database queue for reliability
4. **Configure SPF/DKIM** - Prevent emails going to spam
5. **Test thoroughly** - All email types before launch

---

## Quick Start Commands

```bash
# Test email configuration
php artisan tinker
Mail::raw('Test', function($m) { $m->to('test@example.com')->subject('Test'); });
exit

# Clear config after changes
php artisan config:cache

# View email in logs (for debugging)
# Set MAIL_MAILER=log in .env
php artisan config:cache
# Emails will be written to storage/logs/laravel.log

# Queue commands
php artisan queue:work     # Process queued emails
php artisan queue:failed   # View failed jobs
php artisan queue:retry all # Retry failed jobs
```

---

**Status:** Ready to configure
**Next Step:** Create email account in cPanel and update .env

