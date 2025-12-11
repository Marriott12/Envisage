# 🚀 Quick Start: Upload to cPanel (5 Steps)

## ⚡ Fast Track Guide

### Step 1: Prepare (2 minutes)
```
✅ Read CPANEL_DEPLOYMENT_GUIDE.md
✅ Edit backend/.env.production with your production values
✅ Have cPanel login ready
```

### Step 2: cPanel Database Setup (3 minutes)
```
1. Login to cPanel
2. Go to: MySQL Databases
3. Create database: envisage_db
4. Create user: envisage_user (set strong password)
5. Add user to database with ALL PRIVILEGES
6. Save credentials in notepad
```

### Step 3: Upload Files (10 minutes)

**Option A: File Manager (Easier)**
```
1. cPanel → File Manager
2. Create folder: /home/youruser/envisage/
3. Upload entire backend folder to /envisage/
4. Go to backend/public folder
5. Select all files (.htaccess, index.php, etc)
6. Move to: /home/youruser/public_html/
7. Edit public_html/index.php:
   Change: require __DIR__.'/../vendor/autoload.php';
   To: require __DIR__.'/../envisage/vendor/autoload.php';
   
   Change: require_once __DIR__.'/../bootstrap/app.php';
   To: require_once __DIR__.'/../envisage/bootstrap/app.php';
```

**Option B: FTP (FileZilla)**
```
1. Download FileZilla
2. Connect using cPanel credentials
3. Upload backend folder to /envisage/
4. Move public/ contents to public_html/
5. Edit public_html/index.php (same as above)
```

### Step 4: Configure & Install (5 minutes)
```
1. Upload backend/.env.production to /envisage/.env
2. Edit /envisage/.env with your database credentials
3. Open cPanel → Terminal (or SSH)
4. Run these commands:

   cd /home/youruser/envisage
   composer install --optimize-autoloader --no-dev
   php artisan key:generate
   php artisan migrate --force
   php artisan config:cache
   php artisan route:cache
   php artisan storage:link
```

### Step 5: Test (2 minutes)
```
Visit these URLs:

✅ https://yourdomain.com/api/test
   Should show: {"status":"success","message":"API is working"}

✅ https://yourdomain.com/api/products
   Should show: [] or list of products

✅ https://yourdomain.com/api/settings/public
   Should show: public settings

If all work → YOU'RE LIVE! 🎉
```

---

## 📋 Essential Files Reference

### Before Upload - Edit These:
```
backend/.env.production
  ↓ Update:
  - APP_URL=https://yourdomain.com
  - DB_DATABASE=youruser_envisage
  - DB_USERNAME=youruser_envisage_user
  - DB_PASSWORD=your_database_password
  - MAIL_* settings
  - STRIPE_* keys (production)
```

### After Upload - Critical Paths:
```
/home/youruser/
  ├── public_html/
  │   ├── .htaccess      ← Must exist
  │   └── index.php      ← Must point to ../envisage/
  └── envisage/
      ├── .env           ← Your .env.production renamed
      ├── storage/       ← Must be writable (775)
      └── bootstrap/cache/ ← Must be writable (775)
```

---

## 🔧 Common Issues & Fixes

### ❌ "500 Internal Server Error"
```bash
# Fix permissions
chmod -R 775 storage bootstrap/cache

# Regenerate key
php artisan key:generate

# Clear cache
php artisan config:clear
```

### ❌ "Database connection failed"
```bash
# Check .env credentials match cPanel database
# Try DB_HOST=127.0.0.1 instead of localhost
```

### ❌ "404 on API routes"
```bash
# Ensure .htaccess exists in public_html/
# Clear route cache
php artisan route:clear
php artisan route:cache
```

### ❌ "Composer not found"
```bash
# Install composer in your directory
cd /home/youruser/envisage
curl -sS https://getcomposer.org/installer | php
php composer.phar install --no-dev
```

---

## 📞 Need Help?

### Resources:
- 📖 Full Guide: CPANEL_DEPLOYMENT_GUIDE.md
- 📋 Checklist: Follow step-by-step
- 💬 cPanel Support: Contact your hosting provider
- 📚 Laravel Docs: https://laravel.com/docs/8.x/deployment

### Support Commands:
```bash
# View error logs
tail -f /home/youruser/envisage/storage/logs/laravel.log

# Check PHP version
php -v

# Check Laravel installation
php artisan --version

# List all routes
php artisan route:list
```

---

## ✅ Post-Deployment Checklist

- [ ] SSL certificate installed (HTTPS working)
- [ ] Database migrated successfully
- [ ] API endpoints responding
- [ ] .env set to production mode (APP_DEBUG=false)
- [ ] File permissions correct (775 for storage)
- [ ] Stripe keys switched to production
- [ ] Email sending working
- [ ] Cron jobs configured
- [ ] Admin user created
- [ ] Test checkout flow completed

---

**Estimated Total Time: 20-30 minutes**

Good luck with your deployment! 🚀
