# 🚀 Envisage Marketplace - Quick Launch Card

## ⚡ 5-Step Launch Process (90 Minutes Total)

### ✅ Step 1: Upload Seeder Package (15 min)
```bash
File: Desktop/envisage-update.zip
Upload to: /home/envithcy/envisage/ via cPanel
Extract and run:
  cd /home/envithcy/envisage
  mv CompleteMarketplaceSeeder.php database/seeders/
  dos2unix production-setup.sh
  chmod +x production-setup.sh
  ./production-setup.sh
```

### 📧 Step 2: Configure Email (15 min)
```bash
cPanel → Email Accounts → Create:
  Email: noreply@envisagezm.com
  Password: [strong password]

Update .env:
  MAIL_HOST=mail.envisagezm.com
  MAIL_USERNAME=noreply@envisagezm.com
  MAIL_PASSWORD=[your password]

Test:
  php artisan config:cache
  php artisan tinker
  Mail::raw('Test', function($m){$m->to('test@email.com')->subject('Test');});
```

### 💳 Step 3: Add Stripe Keys (10 min - Optional)
```bash
Get keys: https://dashboard.stripe.com/apikeys

Update .env:
  STRIPE_SECRET=sk_test_or_live_your_key
  
Clear cache:
  php artisan config:cache
```

### 🌐 Step 4: Deploy Frontend (30 min)

**Option A: Vercel (Recommended)**
```powershell
npm i -g vercel
cd c:\wamp64\www\Envisage\frontend
vercel --prod
```

**Option B: cPanel**
```powershell
cd c:\wamp64\www\Envisage\frontend
npm run build
# Upload 'out' folder to public_html
```

### ⏰ Step 5: Setup Cron (5 min)
```bash
cPanel → Cron Jobs → Add:
  * * * * * cd /home/envithcy/envisage && php artisan schedule:run >> /dev/null 2>&1
```

---

## 🧪 Test Checklist (20 min)

- [ ] Visit https://envisagezm.com
- [ ] See 15 products listed
- [ ] Browse categories
- [ ] Add to cart
- [ ] Register/Login
- [ ] Seller dashboard access
- [ ] Create test product
- [ ] Complete checkout
- [ ] Receive email

---

## 🔑 Default Credentials

**Admin:** admin@envisagezm.com / Admin@2025
**Seller:** techstore@envisagezm.com / Seller@2025
**Buyer:** john@example.com / Buyer@2025

---

## 📊 What You'll Have After Setup

- ✅ 6 users (1 admin, 3 sellers, 2 buyers)
- ✅ 9 categories (Electronics, Fashion, etc.)
- ✅ 15 products with descriptions
- ✅ Working checkout system
- ✅ Email notifications
- ✅ Payment processing ready
- ✅ Professional marketplace UI

---

## 🛠️ Quick Commands

```bash
# Clear all caches
php artisan optimize:clear

# Recache everything
php artisan optimize

# View logs
tail -f storage/logs/laravel.log

# Test API
curl https://envisagezm.com/api/products
```

---

## 📞 Server Info

**SSH:** envithcy@server219.web-hosting.com
**cPanel:** https://server219.web-hosting.com:2083
**API:** https://envisagezm.com/api
**Laravel:** /home/envithcy/envisage/

---

## 🐛 Quick Fixes

**500 Error?**
```bash
chmod -R 755 storage bootstrap/cache
php artisan cache:clear
```

**CORS Error?**
```bash
php artisan config:cache
```

**Images Not Loading?**
```bash
php artisan storage:link
chmod -R 775 storage/app/public
```

---

## 📚 Full Documentation

See these files for complete guides:
- `COMPLETE_DEPLOYMENT_SUMMARY.md`
- `PRODUCTION_DEPLOYMENT_CHECKLIST.md`
- `EMAIL_CONFIGURATION_GUIDE.md`
- `FRONTEND_DEPLOYMENT.md`

---

## ⚡ One-Command Server Test

```bash
cd /home/envithcy/envisage && php artisan tinker --execute="echo 'Products: '.App\Models\Product::count().PHP_EOL; echo 'Users: '.App\Models\User::count().PHP_EOL; echo 'Categories: '.App\Models\Category::count().PHP_EOL;"
```

Expected output after seeding:
- Products: 15
- Users: 6
- Categories: 9

---

**🎉 You're ready to launch! Follow the 5 steps above.**

**Next:** Upload `envisage-update.zip` from Desktop → Run `production-setup.sh`
