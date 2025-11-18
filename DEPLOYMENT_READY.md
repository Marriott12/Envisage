# 🚀 ENVISAGE - READY FOR CPANEL DEPLOYMENT

## ✅ FILES PREPARED FOR UPLOAD

All files are ready in: `c:\wamp64\www\Envisage\`

### Production Files Created:
- ✅ `backend/.env.production` - Production environment config
- ✅ `CPANEL_DEPLOYMENT_GUIDE.md` - Complete deployment instructions
- ✅ `UPLOAD_CHECKLIST.md` - Quick reference checklist

---

## 📦 WHAT TO UPLOAD

### Option 1: Upload Everything (Recommended)

**Upload this entire folder to cPanel:**
```
c:\wamp64\www\Envisage\backend\
```

**Destination on cPanel:**
```
/home/youruser/envisage/
```

### Option 2: Create ZIP (Faster Upload)

**Run this command to create a ZIP:**
```powershell
Compress-Archive -Path "c:\wamp64\www\Envisage\backend\*" -DestinationPath "c:\wamp64\www\envisage-backend.zip" -Force
```

Then upload `envisage-backend.zip` to cPanel and extract.

---

## 🗂️ FILE STRUCTURE TO UPLOAD

```
backend/
├── app/                    ✅ Upload
├── bootstrap/              ✅ Upload
│   └── cache/             ✅ Upload (empty)
├── config/                 ✅ Upload
├── database/               ✅ Upload
├── public/                 ✅ Upload (move to public_html later)
├── resources/              ✅ Upload
├── routes/                 ✅ Upload
├── storage/                ✅ Upload (folders only, no logs)
├── vendor/                 ✅ Upload
├── .htaccess              ✅ Upload
├── .env.production        ✅ Upload (rename to .env)
├── artisan                ✅ Upload
├── composer.json          ✅ Upload
└── composer.lock          ✅ Upload
```

### ❌ DON'T Upload These:
- node_modules/
- .git/
- .env (use .env.production instead)
- tests/
- storage/logs/*.log
- .gitignore

---

## 📋 QUICK START GUIDE

### 1️⃣ BEFORE UPLOADING

**Edit Production Config:**
```powershell
# Open this file and update:
c:\wamp64\www\Envisage\backend\.env.production
```

**Required Changes:**
- `APP_URL` → Your domain (e.g., https://yourdomain.com)
- `DB_DATABASE` → Your cPanel database name
- `DB_USERNAME` → Your cPanel database user
- `DB_PASSWORD` → Your cPanel database password
- `STRIPE_SECRET` → Your production Stripe key (if using)
- `MAIL_HOST`, `MAIL_USERNAME`, `MAIL_PASSWORD` → Your email settings

---

### 2️⃣ UPLOAD TO CPANEL

**Method A: File Manager**
1. Login to cPanel
2. Open File Manager
3. Create folder: `/home/youruser/envisage/`
4. Upload all backend files to this folder
5. If uploaded as ZIP, extract it

**Method B: FTP (FileZilla, WinSCP)**
1. Connect to your server
2. Upload entire `backend/` folder to `/home/youruser/envisage/`

---

### 3️⃣ CREATE DATABASE

**In cPanel → MySQL Databases:**

1. **Create New Database:**
   - Database Name: `envisage` (will become `youruser_envisage`)

2. **Create New User:**
   - Username: `envisage` (will become `youruser_envisage`)
   - Password: [Choose strong password]

3. **Add User to Database:**
   - Select user and database
   - Grant ALL PRIVILEGES

4. **Write down these credentials!**

---

### 4️⃣ CONFIGURE FILES (via SSH or File Manager)

**Connect to SSH:**
```bash
ssh youruser@yourdomain.com
```

**Run these commands:**
```bash
# Navigate to directory
cd ~/envisage

# Rename .env file
mv .env.production .env

# Set permissions
chmod -R 755 storage
chmod -R 755 bootstrap/cache

# Run migrations
php artisan migrate --force

# Create storage link
ln -s ~/envisage/storage/app/public ~/public_html/storage

# Cache everything
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

### 5️⃣ MOVE PUBLIC FILES

**Move these files from `~/envisage/public/` to `~/public_html/`:**
- `index.php`
- `.htaccess`
- Any other public assets

**Then edit `~/public_html/index.php`:**

Change:
```php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
```

To:
```php
require __DIR__.'/../envisage/vendor/autoload.php';
$app = require_once __DIR__.'/../envisage/bootstrap/app.php';
```

---

### 6️⃣ TEST DEPLOYMENT

Visit these URLs:
- **API:** https://yourdomain.com/api/
- **Health:** https://yourdomain.com/api/test
- **Products:** https://yourdomain.com/api/products

Should return JSON responses!

---

## 🎯 COMPLETE COMMAND SEQUENCE

**Copy and paste this entire block into SSH:**

```bash
cd ~/envisage && \
mv .env.production .env && \
chmod -R 755 storage bootstrap/cache && \
php artisan migrate --force && \
ln -s ~/envisage/storage/app/public ~/public_html/storage && \
php artisan config:cache && \
php artisan route:cache && \
php artisan view:cache && \
echo "✅ Deployment complete! Test your API at https://yourdomain.com/api/"
```

---

## 📊 EXPECTED DIRECTORY STRUCTURE

```
/home/youruser/
├── envisage/              ← Your Laravel app
│   ├── app/
│   ├── bootstrap/
│   ├── config/
│   ├── database/
│   ├── routes/
│   ├── storage/
│   ├── vendor/
│   ├── .env              ← Renamed from .env.production
│   └── artisan
│
└── public_html/          ← Web root (public folder)
    ├── index.php         ← Modified to point to ../envisage/
    ├── .htaccess
    └── storage/          ← Symlink to ~/envisage/storage/app/public
```

---

## ⚠️ IMPORTANT NOTES

1. **Keep .env.production locally** - Don't delete it from your computer
2. **Backup database credentials** - Write them down somewhere safe
3. **Use production Stripe keys** - Not test keys
4. **Enable SSL** - Use Let's Encrypt in cPanel (free)
5. **Test thoroughly** - Check all API endpoints before going live

---

## 🔐 SECURITY CHECKLIST

After deployment:
- [ ] Change `APP_DEBUG` to `false` ✅ (already done)
- [ ] Use `APP_ENV=production` ✅ (already done)
- [ ] Secure `.env` file: `chmod 600 .env`
- [ ] Install SSL certificate
- [ ] Setup cron jobs for scheduled tasks
- [ ] Configure email sending
- [ ] Test Stripe webhooks
- [ ] Enable error logging

---

## 📚 DOCUMENTATION REFERENCE

| Document | Purpose |
|----------|---------|
| `CPANEL_DEPLOYMENT_GUIDE.md` | Complete step-by-step guide |
| `UPLOAD_CHECKLIST.md` | Quick reference checklist |
| This file | Deployment summary |

---

## 🆘 TROUBLESHOOTING

### 500 Internal Server Error
```bash
chmod -R 755 storage bootstrap/cache
php artisan cache:clear
tail -f storage/logs/laravel.log
```

### Database Connection Error
- Double-check `.env` credentials
- Verify database exists in cPanel
- Test: `php artisan tinker` → `DB::connection()->getPdo();`

### Routes Not Found
```bash
php artisan route:clear
php artisan route:cache
```

---

## 🎉 YOU'RE READY!

**Next Steps:**

1. ✏️ Edit `backend/.env.production` with YOUR details
2. 📤 Upload files to cPanel
3. 🗄️ Create database in cPanel
4. 🔧 Run SSH commands
5. ✅ Test API endpoints

**Full Instructions:** See `CPANEL_DEPLOYMENT_GUIDE.md`

**Quick Reference:** See `UPLOAD_CHECKLIST.md`

---

**Good luck with your deployment! 🚀**

---

**Prepared:** November 13, 2025  
**Version:** 1.0.0  
**Status:** Ready for Production Deployment
