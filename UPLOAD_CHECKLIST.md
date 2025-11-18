# 📋 CPANEL UPLOAD CHECKLIST

## ✅ PRE-UPLOAD PREPARATION

### 1. Backend Files to Upload:
```
✅ app/
✅ bootstrap/
✅ config/
✅ database/
✅ public/
✅ resources/
✅ routes/
✅ storage/ (empty folders only)
✅ vendor/ (after composer install --no-dev)
✅ .htaccess
✅ artisan
✅ composer.json
✅ composer.lock
✅ .env.production (rename to .env after upload)
```

### 2. Files to EXCLUDE (Don't Upload):
```
❌ node_modules/
❌ .git/
❌ .env (use .env.production instead)
❌ storage/logs/*.log
❌ tests/
❌ .gitignore
❌ .editorconfig
❌ phpunit.xml
```

---

## 📤 UPLOAD STEPS

### Step 1: Create Production .env
```powershell
cd c:\wamp64\www\Envisage\backend
Copy-Item .env .env.production
```

**Edit `.env.production` with YOUR details:**
- APP_URL=https://yourdomain.com
- DB_DATABASE=your_cpanel_db
- DB_USERNAME=your_cpanel_user
- DB_PASSWORD=your_cpanel_password
- STRIPE_SECRET=sk_live_...

### Step 2: Install Production Dependencies
```powershell
composer install --no-dev --optimize-autoloader
```

### Step 3: Compress for Upload (Optional - Faster)
```powershell
# Create ZIP file
Compress-Archive -Path "c:\wamp64\www\Envisage\backend\*" -DestinationPath "c:\wamp64\www\envisage-backend.zip"
```

### Step 4: Upload via cPanel File Manager
1. Login to cPanel
2. File Manager → Create folder: `/home/youruser/envisage/`
3. Upload ZIP or individual files
4. Extract if using ZIP
5. Move `public/*` files to `public_html/`

---

## 🗄️ DATABASE SETUP

### In cPanel → MySQL Databases:

1. **Create Database:**
   - Name: `youruser_envisage` ✅

2. **Create User:**
   - Username: `youruser_envisage` ✅
   - Password: _________________ ✅

3. **Add User to Database:**
   - All Privileges ✅

4. **Note Credentials:**
   ```
   Database: youruser_envisage
   Username: youruser_envisage
   Password: [your password]
   Host: localhost
   ```

---

## 🔧 SSH COMMANDS (Copy-Paste)

```bash
# 1. Navigate to directory
cd ~/envisage

# 2. Rename environment file
mv .env.production .env

# 3. Set permissions
chmod -R 755 storage bootstrap/cache

# 4. Install dependencies (if not uploaded)
composer install --no-dev --optimize-autoloader

# 5. Run migrations
php artisan migrate --force

# 6. Create storage link
ln -s ~/envisage/storage/app/public ~/public_html/storage

# 7. Cache configuration
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 8. Test database connection
php artisan tinker
# Type: DB::connection()->getPdo();
# Press Ctrl+C to exit
```

---

## 📝 UPDATE index.php

**File:** `/home/youruser/public_html/index.php`

**Change these lines:**

```php
// FROM:
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';

// TO:
require __DIR__.'/../envisage/vendor/autoload.php';
$app = require_once __DIR__.'/../envisage/bootstrap/app.php';
```

---

## ✅ VERIFICATION

### Test these URLs after upload:

1. **API Base:**
   ```
   https://yourdomain.com/api/
   ```
   Should return JSON

2. **Health Check:**
   ```
   https://yourdomain.com/api/test
   ```

3. **Products Endpoint:**
   ```
   https://yourdomain.com/api/products
   ```

---

## 🎯 QUICK REFERENCE

| Task | Command |
|------|---------|
| Clear cache | `php artisan cache:clear` |
| View logs | `tail -f storage/logs/laravel.log` |
| Run migrations | `php artisan migrate --force` |
| Create admin | `php artisan tinker` |
| Check routes | `php artisan route:list` |

---

## 📊 DIRECTORY STRUCTURE ON CPANEL

```
/home/youruser/
├── envisage/               ← Backend application
│   ├── app/
│   ├── bootstrap/
│   ├── config/
│   ├── database/
│   ├── public/            ← Don't use this
│   ├── routes/
│   ├── storage/
│   ├── vendor/
│   ├── .env
│   └── artisan
│
└── public_html/           ← Public web root
    ├── index.php          ← From backend/public/
    ├── .htaccess          ← From backend/public/
    └── storage/           ← Symlink to envisage/storage/app/public
```

---

## ⚠️ COMMON ISSUES & FIXES

### 500 Error
```bash
chmod -R 755 storage bootstrap/cache
php artisan cache:clear
```

### Database Error
- Check .env credentials
- Verify database exists
- Test connection: `php artisan tinker` then `DB::connection()->getPdo();`

### Routes Not Working
```bash
php artisan route:clear
php artisan route:cache
```

### File Upload Error
```bash
chmod -R 755 storage
php artisan storage:link
```

---

## 🎉 POST-DEPLOYMENT

After successful upload:

- [ ] API responding at `/api/`
- [ ] Database connected
- [ ] Migrations completed
- [ ] Storage link created
- [ ] SSL certificate installed
- [ ] Cron jobs configured
- [ ] Email tested
- [ ] Stripe webhooks configured

---

## 📞 NEED HELP?

Check the full guide: `CPANEL_DEPLOYMENT_GUIDE.md`

---

**Ready to deploy!** 🚀

Follow steps 1-4 above, then use SSH commands to complete setup.
