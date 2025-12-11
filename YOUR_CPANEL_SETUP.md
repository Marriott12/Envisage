# 🎯 YOUR Envisage cPanel Deployment - Custom Guide

## 🔑 Your Server Information

**cPanel URL:** https://server219.web-hosting.com:2083
**Username:** envithcy
**Web Root:** `/home/envithcy/public_html`

---

## 📂 YOUR Exact Directory Structure

Based on your cPanel, here's EXACTLY where files should go:

```
/home/envithcy/
├── public_html/              ← Your domain points here (CURRENT LOCATION)
│   ├── .htaccess            ← Upload from backend/public/.htaccess
│   ├── index.php            ← Upload from backend/public/index.php (MODIFY!)
│   ├── favicon.png          ← Upload from backend/public/
│   └── logo.png             ← Upload from backend/public/
│
└── envisage/                ← CREATE THIS - Laravel app (secure location)
    ├── app/
    ├── bootstrap/
    ├── config/
    ├── database/
    ├── resources/
    ├── routes/
    ├── storage/             ← Set to 775 permissions
    ├── vendor/              ← Install via composer
    ├── .env                 ← Your production environment
    ├── artisan
    └── composer.json
```

---

## 🚀 Step-by-Step Deployment (YOUR Setup)

### Step 1: Create Laravel Directory (2 minutes)

1. **Login to cPanel:** https://server219.web-hosting.com:2083
2. **Open File Manager**
3. **Click "Home" or navigate to:** `/home/envithcy/`
4. **Click "+ Folder"** (top left)
5. **Create new folder named:** `envisage`
6. **Result:** You should now have `/home/envithcy/envisage/`

---

### Step 2: Upload Backend Files (10 minutes)

**IMPORTANT:** Upload to `/home/envithcy/envisage/` (NOT public_html yet!)

#### Option A: Upload ZIP file (Faster)

1. **On your PC, compress backend folder:**
   - Navigate to: `c:\wamp64\www\envisage\backend`
   - Select all folders EXCEPT:
     - ❌ node_modules/
     - ❌ vendor/ (will install on server)
     - ❌ .git/
     - ❌ tests/ (optional)
   - Right-click → Send to → Compressed folder
   - Name it: `envisage-backend.zip`

2. **Upload to cPanel:**
   - File Manager → Navigate to `/home/envithcy/envisage/`
   - Click "Upload" button (top right)
   - Select `envisage-backend.zip`
   - Wait for upload to complete

3. **Extract:**
   - Right-click `envisage-backend.zip`
   - Click "Extract"
   - Destination: `/home/envithcy/envisage/`
   - Click "Extract Files"
   - Delete the zip file after extraction

#### Option B: Upload folders individually

1. In File Manager, navigate to `/home/envithcy/envisage/`
2. Upload these folders from `c:\wamp64\www\envisage\backend`:
   - app/
   - bootstrap/
   - config/
   - database/
   - resources/
   - routes/
   - storage/
3. Upload these files:
   - artisan
   - composer.json
   - composer.lock
   - .env.production (we'll rename it later)

---

### Step 3: Move Public Files to public_html (5 minutes)

**Now move the public folder contents:**

1. **In File Manager, navigate to:** `/home/envithcy/envisage/public/`
   (If you uploaded everything, this folder exists in your envisage directory)

2. **Select ALL files in public/ folder:**
   - .htaccess
   - index.php
   - favicon.png
   - logo.png
   - Any other files in public/

3. **Click "Move" button** (top menu)

4. **Destination:** `/home/envithcy/public_html/`

5. **⚠️ IMPORTANT:** If you have existing files in public_html, you may want to backup or rename them first!

6. **Click "Move File(s)"**

---

### Step 4: Edit index.php (CRITICAL - 2 minutes)

**This is THE MOST IMPORTANT STEP!**

1. **Navigate to:** `/home/envithcy/public_html/`
2. **Right-click `index.php`** → **Edit** or **Code Editor**
3. **Find these two lines:**

```php
require __DIR__.'/../vendor/autoload.php';
```
**Change to:**
```php
require __DIR__.'/../envisage/vendor/autoload.php';
```

**And find:**
```php
$app = require_once __DIR__.'/../bootstrap/app.php';
```
**Change to:**
```php
$app = require_once __DIR__.'/../envisage/bootstrap/app.php';
```

4. **Click "Save Changes"**

---

### Step 5: Create Database (3 minutes)

1. **In cPanel, search for:** "MySQL Databases" or "MySQL Database Wizard"

2. **Create Database:**
   - Name: `envisage_db` (will become `envithcy_envisage_db`)
   - Click "Create Database"

3. **Create Database User:**
   - Username: `envisage_user` (will become `envithcy_envisage_user`)
   - Password: Click "Generate Password" → **SAVE THIS PASSWORD!**
   - Click "Create User"

4. **Add User to Database:**
   - Select database: `envithcy_envisage_db`
   - Select user: `envithcy_envisage_user`
   - Click "Add"
   - **Select ALL PRIVILEGES**
   - Click "Make Changes"

5. **Save these credentials:**
   ```
   DB_HOST=localhost
   DB_PORT=3306
   DB_DATABASE=envithcy_envisage_db
   DB_USERNAME=envithcy_envisage_user
   DB_PASSWORD=[the password you generated]
   ```

---

### Step 6: Create .env File (5 minutes)

1. **Navigate to:** `/home/envithcy/envisage/`

2. **If you uploaded .env.production:**
   - Right-click `.env.production`
   - Click "Rename"
   - Rename to: `.env`

3. **Or Create new .env file:**
   - Click "+ File"
   - Name: `.env`
   - Right-click → Edit

4. **Paste this configuration (UPDATE THE VALUES!):**

```env
# Application Settings
APP_NAME="Envisage E-Commerce"
APP_ENV=production
APP_KEY=base64:IEo/XpNBVzzdYQ6G1tOnGDiKsy2qjlebogLzV1EmG7I=
APP_DEBUG=false
APP_URL=https://yourdomain.com

# Logging
LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=error

# Database Configuration - UPDATE WITH YOUR CREDENTIALS!
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=envithcy_envisage_db
DB_USERNAME=envithcy_envisage_user
DB_PASSWORD=YOUR_PASSWORD_FROM_STEP_5

# Cache & Sessions
BROADCAST_DRIVER=log
CACHE_DRIVER=file
FILESYSTEM_DRIVER=local
QUEUE_CONNECTION=database
SESSION_DRIVER=file
SESSION_LIFETIME=120

# Email Configuration - UPDATE THESE!
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-gmail-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME="${APP_NAME}"

# Stripe Payment - USE PRODUCTION KEYS!
STRIPE_KEY=pk_live_your_publishable_key
STRIPE_SECRET=sk_live_your_secret_key
STRIPE_WEBHOOK_SECRET=whsec_your_webhook_secret

# Frontend URL
FRONTEND_URL=https://yourdomain.com

# AWS (Optional)
AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=
AWS_USE_PATH_STYLE_ENDPOINT=false
```

5. **Click "Save Changes"**

---

### Step 7: Install Composer Dependencies (5 minutes)

**Option A: Using cPanel Terminal (Recommended)**

1. **In cPanel, search for:** "Terminal" or "SSH Access"
2. **Click to open Terminal**
3. **Run these commands:**

```bash
# Navigate to your Laravel directory
cd /home/envithcy/envisage

# Check PHP version
php -v

# Install dependencies
composer install --optimize-autoloader --no-dev

# Generate app key
php artisan key:generate

# Clear any cached config
php artisan config:clear
```

**Option B: Using SSH Client (PuTTY)**

1. **Download PuTTY:** https://www.putty.org/
2. **Connect to:** server219.web-hosting.com (port 22)
3. **Login with:** Username: envithcy, Password: your cPanel password
4. **Run the same commands as Option A**

**Option C: No Terminal Access**

If you don't have terminal access, you'll need to:
1. Download composer.phar
2. Upload to `/home/envithcy/envisage/`
3. Use Cron Jobs to run installation (contact your host for help)

---

### Step 8: Run Database Migration (3 minutes)

**Via Terminal/SSH:**

```bash
# Navigate to Laravel directory
cd /home/envithcy/envisage

# Run migrations
php artisan migrate --force

# Initialize default settings
php artisan tinker
>>> App\Models\Setting::initializeDefaults();
>>> exit
```

---

### Step 9: Set File Permissions (2 minutes)

**Via Terminal/SSH:**

```bash
cd /home/envithcy/envisage

# Set permissions for storage and cache
chmod -R 775 storage
chmod -R 775 bootstrap/cache

# Create storage symlink
php artisan storage:link
```

**Via File Manager:**

1. Navigate to `/home/envithcy/envisage/storage/`
2. Right-click → "Change Permissions"
3. Set to: **775** (check: Owner: RWX, Group: RWX, World: R-X)
4. Check "Recurse into subdirectories"
5. Click "Change Permissions"

6. Repeat for `/home/envithcy/envisage/bootstrap/cache/`

---

### Step 10: Cache Configuration (2 minutes)

**Via Terminal:**

```bash
cd /home/envithcy/envisage

# Cache for better performance
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

### Step 11: Test Your Installation (2 minutes)

**Visit these URLs in your browser:**

1. **API Health Check:**
   ```
   https://yourdomain.com/api/test
   ```
   **Should return:**
   ```json
   {"status":"success","message":"API is working"}
   ```

2. **Products Endpoint:**
   ```
   https://yourdomain.com/api/products
   ```
   **Should return:** `[]` or product list

3. **Public Settings:**
   ```
   https://yourdomain.com/api/settings/public
   ```
   **Should return:** Settings JSON

4. **Sitemap:**
   ```
   https://yourdomain.com/api/sitemap.xml
   ```
   **Should return:** XML sitemap

---

## ✅ Quick Verification Checklist

**After deployment, verify:**

- [ ] Created `/home/envithcy/envisage/` directory
- [ ] Uploaded all backend files to `/envisage/`
- [ ] Moved public/ contents to `public_html/`
- [ ] Edited `public_html/index.php` with correct paths
- [ ] Created database: `envithcy_envisage_db`
- [ ] Created database user: `envithcy_envisage_user`
- [ ] Created `.env` file with correct credentials
- [ ] Ran `composer install --no-dev`
- [ ] Ran `php artisan migrate --force`
- [ ] Set permissions 775 on storage/ and bootstrap/cache/
- [ ] Ran `php artisan config:cache`
- [ ] Tested `/api/test` endpoint (working ✅)
- [ ] Installed SSL certificate
- [ ] Forced HTTPS in .htaccess

---

## 🔧 Troubleshooting YOUR Setup

### Issue: "500 Internal Server Error"

```bash
# Via terminal
cd /home/envithcy/envisage
chmod -R 775 storage bootstrap/cache
php artisan config:clear
php artisan key:generate
```

### Issue: "Database connection refused"

Check your `.env` file:
```env
DB_HOST=localhost  (try also: 127.0.0.1)
DB_DATABASE=envithcy_envisage_db  (full name with prefix)
DB_USERNAME=envithcy_envisage_user  (full name with prefix)
```

### Issue: "Composer not found"

```bash
# Check if composer is installed
which composer

# If not found, install locally
cd /home/envithcy/envisage
curl -sS https://getcomposer.org/installer | php
php composer.phar install --no-dev
```

### Issue: "Can't access terminal"

Contact your hosting provider and ask them to:
1. Enable SSH access for your account
2. Or enable cPanel Terminal feature
3. Or help you run composer commands

---

## 📞 Your Hosting Support

**Server:** server219.web-hosting.com
**cPanel:** https://server219.web-hosting.com:2083

If you need help:
- Contact your hosting provider's support
- Provide them your username: `envithcy`
- Ask about: SSH access, Composer installation, PHP version

---

## 🎉 Your Deployment Path

```
✅ Step 1: Created /home/envithcy/envisage/
✅ Step 2: Uploaded backend files
✅ Step 3: Moved public/ to public_html/
✅ Step 4: Edited public_html/index.php
✅ Step 5: Created database
✅ Step 6: Created .env file
✅ Step 7: Installed composer dependencies
✅ Step 8: Ran migrations
✅ Step 9: Set permissions
✅ Step 10: Cached configuration
✅ Step 11: Tested endpoints
```

**Total Time: ~30-40 minutes**

---

## 📝 Your Server Paths Reference

**Quick Copy-Paste Commands:**

```bash
# Navigate to Laravel
cd /home/envithcy/envisage

# View logs
tail -f /home/envithcy/envisage/storage/logs/laravel.log

# Clear all caches
php artisan optimize:clear

# Rebuild caches
php artisan optimize

# Check routes
php artisan route:list
```

**File Manager Paths:**
- Laravel Root: `/home/envithcy/envisage/`
- Web Root: `/home/envithcy/public_html/`
- Storage: `/home/envithcy/envisage/storage/`
- Logs: `/home/envithcy/envisage/storage/logs/`
- .env: `/home/envithcy/envisage/.env`

---

**Ready to deploy? Follow the steps above in order!** 🚀

Good luck! If you get stuck on any step, let me know which step and what error you're seeing.
