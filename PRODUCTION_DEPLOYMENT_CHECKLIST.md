# 🚀 Complete Production Deployment Checklist

## Current Status: READY TO DEPLOY

### ✅ Completed Tasks

#### Backend (Laravel API)
- [x] Laravel application deployed to cPanel
- [x] Database migrations executed
- [x] Storage linked
- [x] Production environment configured
- [x] API accessible at https://envisagezm.com/api
- [x] Health check endpoint working
- [x] Test data created (2 products, 1 seller)
- [x] HTTPS enabled

#### Frontend (Next.js)
- [x] Development server running locally
- [x] Connected to production API
- [x] Environment variables configured
- [x] Ready for production build

---

## 📋 Deployment Steps to Complete

### Step 1: Seed Complete Marketplace Data ⏳

**Files Created:**
- ✅ `backend/database/seeders/CompleteMarketplaceSeeder.php`
- ✅ `backend/production-setup.sh`
- ✅ Desktop:`envisage-update.zip`

**Upload Instructions:**
1. Upload `envisage-update.zip` to cPanel File Manager
2. Extract to `/home/envithcy/envisage/`
3. Run via SSH:
```bash
cd /home/envithcy/envisage
mv CompleteMarketplaceSeeder.php database/seeders/
dos2unix production-setup.sh
chmod +x production-setup.sh
./production-setup.sh
```

**What This Does:**
- Creates admin user (admin@envisagezm.com / Admin@2025)
- Creates 3 sellers with test accounts
- Creates 2 buyer accounts
- Creates 9 categories (Electronics, Fashion, etc.)
- Creates 15 products with descriptions
- Configures site settings
- Links storage directories

---

### Step 2: Configure Email Settings ⏳

**Edit `.env` on server:**
```bash
MAIL_MAILER=smtp
MAIL_HOST=mail.envisagezm.com
MAIL_PORT=587
MAIL_USERNAME=noreply@envisagezm.com
MAIL_PASSWORD=[your_email_password]
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@envisagezm.com
MAIL_FROM_NAME="Envisage Marketplace"
```

**Create Email Account in cPanel:**
1. Go to Email Accounts in cPanel
2. Create `noreply@envisagezm.com`
3. Set strong password
4. Update `.env` with password

---

### Step 3: Configure Stripe Payment Gateway ⏳

**Get Stripe Keys:**
1. Visit https://dashboard.stripe.com/register
2. Create account or login
3. Get API keys from Developers section

**Update `.env` on server:**
```bash
STRIPE_SECRET=sk_live_your_actual_key_here
STRIPE_WEBHOOK_SECRET=whsec_your_webhook_secret_here
```

**Test Mode (Optional):**
Use test keys for testing:
```bash
STRIPE_SECRET=sk_test_51xxxxx
```

---

### Step 4: Build & Deploy Frontend ⏳

**Option A: Deploy to Vercel (Recommended)**

```powershell
# Install Vercel CLI
npm i -g vercel

# Deploy
cd c:\wamp64\www\Envisage\frontend
vercel --prod
```

Add environment variables in Vercel:
- `NEXT_PUBLIC_API_URL` = `https://envisagezm.com/api`
- `NEXT_PUBLIC_APP_URL` = `https://your-project.vercel.app`

**Option B: Static Export to cPanel**

1. Build static site:
```powershell
cd c:\wamp64\www\Envisage\frontend
npm run build
```

2. Create ZIP of `out` folder
3. Upload to cPanel
4. Extract to `/home/envithcy/public_html/`

---

### Step 5: Setup Cron Jobs ⏳

**In cPanel → Cron Jobs:**

Add this command to run every minute:
```bash
* * * * * cd /home/envithcy/envisage && php artisan schedule:run >> /dev/null 2>&1
```

**What This Does:**
- Processes scheduled tasks
- Sends notification emails
- Cleans up expired sessions
- Updates order statuses

---

### Step 6: Update CORS for Frontend Domain ⏳

**If frontend on different domain:**

Edit `backend/config/cors.php`:
```php
'allowed_origins' => [
    'https://envisagezm.com',
    'https://your-frontend-domain.com',
    env('FRONTEND_URL'),
],
```

Then on server:
```bash
php artisan config:cache
```

---

### Step 7: Production Optimizations ⏳

**Performance:**
```bash
cd /home/envithcy/envisage

# Enable OPcache in cPanel PHP settings
# Enable Memcached/Redis (if available)

# Laravel optimizations
php artisan optimize
php artisan view:cache
php artisan event:cache
```

**Security:**
- [ ] Disable directory listing in cPanel
- [ ] Enable ModSecurity
- [ ] Setup SSL (already done ✓)
- [ ] Configure firewall rules
- [ ] Set secure file permissions (755 folders, 644 files)

**Cloudflare (Optional):**
1. Point DNS to Cloudflare
2. Enable caching
3. Enable minification
4. Enable Brotli compression

---

### Step 8: Test Complete Flows ⏳

**Test Checklist:**
- [ ] Visit frontend homepage
- [ ] Browse products
- [ ] Search functionality
- [ ] Product details page
- [ ] Add to cart
- [ ] User registration
- [ ] User login
- [ ] Seller dashboard
- [ ] Create product
- [ ] Upload images
- [ ] Complete checkout
- [ ] Payment processing
- [ ] Email notifications
- [ ] Admin panel access

---

### Step 9: Final Checks ⏳

**Security:**
- [ ] `APP_DEBUG=false` in production .env
- [ ] Strong database password
- [ ] Strong admin password
- [ ] HTTPS enforced
- [ ] Secure cookies enabled

**Performance:**
- [ ] Cache enabled
- [ ] Images optimized
- [ ] CDN configured (optional)
- [ ] Database indexed

**Functionality:**
- [ ] All APIs responding
- [ ] Email sending working
- [ ] Payments processing
- [ ] File uploads working
- [ ] Search working

---

## 📊 Production Credentials

### Admin Access
- Email: admin@envisagezm.com
- Password: Admin@2025
- Role: Administrator

### Seller Accounts
1. techstore@envisagezm.com / Seller@2025
2. electronics@envisagezm.com / Seller@2025
3. fashion@envisagezm.com / Seller@2025

### Buyer Accounts
1. john@example.com / Buyer@2025
2. sarah@example.com / Buyer@2025

### Server Access
- SSH: envithcy@server219.web-hosting.com
- cPanel: https://server219.web-hosting.com:2083
- Username: envithcy

### Database
- Host: localhost
- Database: envithcy_envisage
- Username: envithcy_envisage
- Password: Envisage@2025

---

## 🎯 Next Actions

1. **Immediate:**
   - Upload and run production-setup.sh
   - Verify test data is seeded
   - Test API endpoints

2. **Within 24 Hours:**
   - Configure email sending
   - Add Stripe keys
   - Deploy frontend
   - Setup cron jobs

3. **Within 1 Week:**
   - Add real products
   - Configure payments
   - Test complete flows
   - Launch to users

---

## 📞 Support & Documentation

- API Docs: `/backend/FEATURE_DOCUMENTATION.md`
- Setup Guide: `/backend/SETUP_GUIDE.md`
- Frontend Guide: `/FRONTEND_DEPLOYMENT.md`
- Server Install: `/backend/SERVER_INSTALL_GUIDE.md`

---

## 🎉 Launch Checklist

Before going live:
- [ ] All production credentials changed from defaults
- [ ] Backup strategy in place
- [ ] Monitoring setup (optional)
- [ ] Terms of service added
- [ ] Privacy policy added
- [ ] Contact information updated
- [ ] Social media links added
- [ ] Analytics configured (Google Analytics, etc.)

---

**Status:** Ready for production deployment!
**Next Step:** Upload and run the update package to seed complete marketplace data.
