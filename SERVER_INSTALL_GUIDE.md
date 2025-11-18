# 🚀 Using server-install.sh on cPanel

## Overview

The `server-install.sh` script is an **automated installation script** designed to set up your Envisage application on your cPanel server in one command.

**Configured for:**
- Server: server219.web-hosting.com
- Username: envithcy
- Laravel Directory: /home/envithcy/envisage

---

## 📋 Prerequisites

Before running the script:

1. ✅ Files uploaded to `/home/envithcy/envisage/`
2. ✅ Database created in cPanel
3. ✅ `.env` or `.env.production` file configured with database credentials
4. ✅ SSH access to your server

---

## 🎯 Quick Start (3 Steps)

### Step 1: Upload Files

Upload your deployment package:
- Upload `envisage-backend-deployment.zip` to `/home/envithcy/`
- Extract it to create `/home/envithcy/envisage/`

### Step 2: Connect via SSH

```bash
ssh envithcy@server219.web-hosting.com
# Enter your password when prompted
```

### Step 3: Run Installation Script

```bash
cd ~/envisage
chmod +x server-install.sh
./server-install.sh
```

**That's it!** The script will:
- ✓ Check PHP version
- ✓ Setup .env file
- ✓ Install composer dependencies
- ✓ Generate application key
- ✓ Clear all caches
- ✓ Run database migrations
- ✓ Set file permissions
- ✓ Create storage symlink
- ✓ Cache configurations
- ✓ Initialize settings

---

## 📝 What the Script Does

### Automatic Steps:

1. **Checks Laravel directory** - Verifies files are in `/home/envithcy/envisage/`
2. **Checks PHP version** - Ensures PHP is available
3. **Configures .env** - Copies `.env.production` to `.env` if needed
4. **Installs dependencies** - Runs `composer install --no-dev --optimize-autoloader`
5. **Generates app key** - Creates Laravel encryption key
6. **Clears caches** - Removes all cache files
7. **Migrates database** - Creates all database tables
8. **Sets permissions** - Makes storage writable (775)
9. **Links storage** - Creates public storage symlink
10. **Caches config** - Optimizes for production
11. **Initializes settings** - Sets up default app settings

---

## ⚙️ Before Running - Update Script (Optional)

If your server details are different, edit the script:

```bash
nano ~/envisage/server-install.sh
```

**Update these lines:**
```bash
# Configuration
LARAVEL_DIR="/home/YOUR_USERNAME/envisage"  # Change if different
PUBLIC_DIR="/home/YOUR_USERNAME/public_html"  # Change if different
```

Save with `Ctrl+O`, `Enter`, then `Ctrl+X`

---

## 🗄️ Database Configuration

**Before running the script, ensure your `.env` has correct database credentials:**

```bash
nano ~/envisage/.env
```

**Required settings:**
```env
DB_HOST=localhost
DB_DATABASE=envithcy_envisage    # Your actual database name
DB_USERNAME=envithcy_envisage    # Your actual database user
DB_PASSWORD=your_password_here   # Your actual password
```

---

## 🔧 Manual Run (Step-by-Step)

If you prefer to run commands manually instead of using the script:

```bash
# 1. Navigate to directory
cd ~/envisage

# 2. Setup environment
cp .env.production .env
# Edit .env with your settings

# 3. Install dependencies
composer install --no-dev --optimize-autoloader

# 4. Generate key
php artisan key:generate --force

# 5. Clear caches
php artisan config:clear
php artisan cache:clear

# 6. Run migrations
php artisan migrate --force

# 7. Set permissions
chmod -R 775 storage bootstrap/cache

# 8. Link storage
php artisan storage:link

# 9. Cache for production
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## ✅ Verification

After running the script, test these URLs:

```bash
# Health check
curl https://yourdomain.com/api/test

# Products endpoint
curl https://yourdomain.com/api/products

# Public settings
curl https://yourdomain.com/api/settings/public
```

Or visit in browser:
- https://yourdomain.com/api/
- https://yourdomain.com/api/test

---

## 📊 Expected Output

```
==========================================
  ENVISAGE E-COMMERCE AUTO INSTALLER
==========================================

[1/10] Checking Laravel directory...
[OK] Laravel directory found

[2/10] Checking PHP version...
PHP Version: PHP 8.1.x
[OK] PHP is available

[3/10] Checking .env file...
[OK] .env file exists

[4/10] Installing Composer dependencies...
[OK] Composer dependencies installed

[5/10] Generating application key...
[OK] Application key generated

[6/10] Clearing all caches...
[OK] All caches cleared

[7/10] Running database migrations...
[OK] Database migrations completed

[8/10] Setting file permissions...
[OK] Permissions set (775 for storage and cache)

[9/10] Creating storage symlink...
[OK] Storage symlink created

[10/10] Caching configurations for production...
[OK] All configurations cached

==========================================
  INSTALLATION COMPLETE!
==========================================
```

---

## 🐛 Troubleshooting

### Error: "Directory not found"
**Solution:** Upload your files to `/home/envithcy/envisage/` first

### Error: "Composer install failed"
**Solution:** Run manually: `composer install --no-dev --optimize-autoloader`

### Error: "Database migration failed"
**Solution:** 
- Check your `.env` database credentials
- Ensure database exists in cPanel
- Test connection: `php artisan tinker` → `DB::connection()->getPdo();`

### Error: "Permission denied"
**Solution:** Make script executable: `chmod +x server-install.sh`

### Composer not found
**Solution:** Script will auto-download composer if not found

---

## 🔄 Re-running the Script

You can safely re-run the script. It will:
- Skip if key already exists
- Skip if migrations already run
- Update caches
- Fix permissions

---

## 📞 Post-Installation

After successful installation:

1. **Update public/index.php** (if not done):
   ```bash
   nano ~/public_html/index.php
   ```
   Change paths to point to `../envisage/`

2. **Test all endpoints**

3. **Setup cron jobs** (optional):
   ```bash
   * * * * * cd /home/envithcy/envisage && php artisan schedule:run >> /dev/null 2>&1
   ```

4. **Monitor logs**:
   ```bash
   tail -f ~/envisage/storage/logs/laravel.log
   ```

---

## 🏠 Local Testing

For **local Windows testing**, use instead:
```powershell
cd c:\wamp64\www\Envisage
.\local-install.ps1
```

---

## 📚 Related Files

- `local-install.ps1` - Windows version for local testing
- `CPANEL_DEPLOYMENT_GUIDE.md` - Complete deployment guide
- `UPLOAD_CHECKLIST.md` - Pre-deployment checklist

---

## 🎉 Success!

Once the script completes successfully:
- ✅ Backend is fully installed
- ✅ Database is migrated
- ✅ Permissions are set
- ✅ Application is production-ready

Visit your API at: **https://yourdomain.com/api/**

---

**Script Location:** `/home/envithcy/envisage/server-install.sh`  
**Last Updated:** November 13, 2025
