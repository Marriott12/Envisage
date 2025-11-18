# 🚀 Envisage E-Commerce - cPanel Deployment Guide

## 📋 Prerequisites

Before uploading, ensure you have:
- ✅ cPanel account with SSH access
- ✅ MySQL database access
- ✅ PHP 7.4+ or 8.0+
- ✅ Composer access (via SSH)
- ✅ Node.js 16+ (for frontend build)
- ✅ Domain or subdomain configured

---

## 📦 STEP 1: Prepare Files for Upload

### Backend Preparation

1. **Create production environment file:**
```powershell
cd c:\wamp64\www\Envisage\backend
Copy-Item .env .env.production
```

2. **Edit `backend/.env.production`** with your production values:
```env
APP_NAME="Envisage Marketplace"
APP_ENV=production
APP_KEY=base64:YOUR_EXISTING_KEY_HERE
APP_DEBUG=false
APP_URL=https://yourdomain.com

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=your_cpanel_database_name
DB_USERNAME=your_cpanel_database_user
DB_PASSWORD=your_cpanel_database_password

# Email Configuration
MAIL_MAILER=smtp
MAIL_HOST=your_mail_server
MAIL_PORT=587
MAIL_USERNAME=your_email@yourdomain.com
MAIL_PASSWORD=your_email_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME="${APP_NAME}"

# Frontend URL
FRONTEND_URL=https://yourdomain.com

# Stripe (Production Keys)
STRIPE_SECRET=sk_live_your_live_key_here
STRIPE_WEBHOOK_SECRET=whsec_your_webhook_secret_here
```

3. **Install production dependencies:**
```powershell
cd c:\wamp64\www\Envisage\backend
composer install --no-dev --optimize-autoloader
```

### Frontend Preparation

1. **Create production environment file:**
```powershell
cd c:\wamp64\www\Envisage\frontend
Copy-Item .env.local .env.production
```

2. **Edit `frontend/.env.production`:**
```env
NEXT_PUBLIC_API_URL=https://yourdomain.com/api
NEXT_PUBLIC_APP_URL=https://yourdomain.com
NEXT_PUBLIC_APP_NAME="Envisage Marketplace"
NEXT_PUBLIC_STRIPE_PUBLIC_KEY=pk_live_your_live_key_here
```

3. **Build frontend for production:**
```powershell
cd c:\wamp64\www\Envisage\frontend
npm install
npm run build
```

---

## 📤 STEP 2: Upload Files to cPanel

### Method 1: Using cPanel File Manager (Easier)

1. **Login to cPanel**
2. **Open File Manager**
3. **Create directory structure:**
   - Create folder: `/home/youruser/envisage/` (or any name you prefer)

4. **Upload Backend Files:**
   - Navigate to `/home/youruser/envisage/`
   - Upload ALL backend files EXCEPT:
     - `node_modules/` (don't upload)
     - `storage/logs/*.log` (optional)
     - `.env` (upload `.env.production` instead)
   
5. **Move public directory contents:**
   - After upload, move contents of `/home/youruser/envisage/public/*` to `/home/youruser/public_html/`
   - Or use a subdirectory like `/home/youruser/public_html/api/`

### Method 2: Using FTP/SFTP (Faster for large files)

Use FileZilla or WinSCP:
- **Host:** Your domain or server IP
- **Username:** Your cPanel username
- **Password:** Your cPanel password
- **Port:** 21 (FTP) or 22 (SFTP)

Upload structure:
```
/home/youruser/
├── envisage/              # Backend application
│   ├── app/
│   ├── bootstrap/
│   ├── config/
│   ├── database/
│   ├── routes/
│   ├── storage/
│   ├── vendor/           # Upload this
│   ├── .env.production   # Rename to .env after upload
│   ├── artisan
│   └── composer.json
│
└── public_html/          # Web root
    ├── index.php         # From backend/public/
    ├── .htaccess         # From backend/public/
    └── storage/          # Symlink (create later)
```

---

## 🗄️ STEP 3: Setup Database

1. **Login to cPanel → MySQL Databases**
2. **Create new database:**
   - Database Name: `youruser_envisage`
3. **Create database user:**
   - Username: `youruser_envisage`
   - Password: (strong password)
4. **Add user to database:**
   - User: `youruser_envisage`
   - Database: `youruser_envisage`
   - Privileges: ALL PRIVILEGES

5. **Note these credentials** - you'll need them for `.env` file

---

## 🔧 STEP 4: Configure Backend (via SSH)

### Connect via SSH:
```bash
ssh youruser@yourdomain.com
# Or use cPanel Terminal
```

### 1. Navigate to application directory:
```bash
cd ~/envisage
```

### 2. Rename environment file:
```bash
mv .env.production .env
```

### 3. Set proper permissions:
```bash
chmod -R 755 storage
chmod -R 755 bootstrap/cache
chown -R youruser:youruser storage
chown -R youruser:youruser bootstrap/cache
```

### 4. Install dependencies (if not uploaded):
```bash
composer install --no-dev --optimize-autoloader
```

### 5. Run migrations:
```bash
php artisan migrate --force
```

### 6. Create storage link:
```bash
# If public is in public_html
ln -s /home/youruser/envisage/storage/app/public /home/youruser/public_html/storage

# Or if using subdirectory
ln -s /home/youruser/envisage/storage/app/public /home/youruser/public_html/api/storage
```

### 7. Cache configuration:
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 8. Set file permissions:
```bash
chmod -R 755 /home/youruser/public_html
```

---

## 📝 STEP 5: Update public/index.php

The `public/index.php` file needs to point to the correct location.

**Edit `/home/youruser/public_html/index.php`:**

```php
<?php

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

/*
|--------------------------------------------------------------------------
| Check If The Application Is Under Maintenance
|--------------------------------------------------------------------------
*/

if (file_exists($maintenance = __DIR__.'/../envisage/storage/framework/maintenance.php')) {
    require $maintenance;
}

/*
|--------------------------------------------------------------------------
| Register The Auto Loader
|--------------------------------------------------------------------------
*/

require __DIR__.'/../envisage/vendor/autoload.php';

/*
|--------------------------------------------------------------------------
| Run The Application
|--------------------------------------------------------------------------
*/

$app = require_once __DIR__.'/../envisage/bootstrap/app.php';

$kernel = $app->make(Kernel::class);

$response = $kernel->handle(
    $request = Request::capture()
)->send();

$kernel->terminate($request, $response);
```

**Key changes:**
- Change `__DIR__.'/../storage'` to `__DIR__.'/../envisage/storage'`
- Change `__DIR__.'/../vendor/autoload.php'` to `__DIR__.'/../envisage/vendor/autoload.php'`
- Change `__DIR__.'/../bootstrap/app.php'` to `__DIR__.'/../envisage/bootstrap/app.php'`

---

## 🌐 STEP 6: Configure .htaccess

**Ensure `/home/youruser/public_html/.htaccess` contains:**

```apache
<IfModule mod_rewrite.c>
    <IfModule mod_negotiation.c>
        Options -MultiViews -Indexes
    </IfModule>

    RewriteEngine On

    # Handle Authorization Header
    RewriteCond %{HTTP:Authorization} .
    RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]

    # Redirect Trailing Slashes If Not A Folder...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_URI} (.+)/$
    RewriteRule ^ %1 [L,R=301]

    # Send Requests To Front Controller...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>
```

---

## 🎨 STEP 7: Deploy Frontend (Optional - For Full Setup)

### Option 1: Static Export (Recommended for cPanel)

1. **Build static version locally:**
```powershell
cd c:\wamp64\www\Envisage\frontend
npm run build
npm run export  # If available
```

2. **Upload `out/` or `.next/` folder to public_html**

### Option 2: Node.js App (If cPanel supports)

1. **Setup Node.js app in cPanel:**
   - Go to cPanel → Setup Node.js App
   - Application Root: `/home/youruser/frontend`
   - Application URL: Your domain
   - Application Startup File: `server.js` or `npm run start`
   - Node.js version: 18.x or 20.x

2. **Upload frontend files**

3. **Install dependencies via SSH:**
```bash
cd ~/frontend
npm install --production
npm run build
```

---

## ✅ STEP 8: Verify Deployment

1. **Test Backend API:**
   - Visit: `https://yourdomain.com/api/`
   - Should return JSON with API info

2. **Test Database Connection:**
```bash
cd ~/envisage
php artisan tinker
# Try: DB::connection()->getPdo();
```

3. **Check Logs:**
```bash
tail -f ~/envisage/storage/logs/laravel.log
```

4. **Test File Upload:**
   - Ensure storage is writable
   - Test product image upload

---

## 🔒 STEP 9: Security & Optimization

### 1. Secure .env file:
```bash
chmod 600 ~/envisage/.env
```

### 2. Disable directory listing:
Add to `.htaccess`:
```apache
Options -Indexes
```

### 3. Setup SSL Certificate:
- cPanel → SSL/TLS → Install SSL
- Use Let's Encrypt (free)

### 4. Setup Cron Jobs:
cPanel → Cron Jobs → Add:
```bash
* * * * * cd /home/youruser/envisage && php artisan schedule:run >> /dev/null 2>&1
```

### 5. Setup Queue Worker (Optional):
```bash
# Add to crontab
* * * * * cd /home/youruser/envisage && php artisan queue:work --stop-when-empty
```

---

## 🐛 Troubleshooting

### Issue: 500 Internal Server Error

**Solution:**
```bash
cd ~/envisage
chmod -R 755 storage bootstrap/cache
php artisan cache:clear
php artisan config:clear
```

### Issue: Database Connection Failed

**Check:**
- Database credentials in `.env`
- Database exists in cPanel
- User has privileges

### Issue: Routes not working

**Solution:**
```bash
php artisan route:clear
php artisan route:cache
```

### Issue: Storage/uploads not working

**Solution:**
```bash
cd ~/envisage
php artisan storage:link
chmod -R 755 storage
```

---

## 📊 Post-Deployment Checklist

- [ ] Backend API responding
- [ ] Database connected
- [ ] Migrations run successfully
- [ ] Storage link created
- [ ] File uploads working
- [ ] Email configuration tested
- [ ] Stripe webhooks configured
- [ ] SSL certificate installed
- [ ] Cron jobs setup
- [ ] Error logging working
- [ ] Frontend deployed (if applicable)

---

## 🔗 Important URLs

After deployment, test these:

- API Base: `https://yourdomain.com/api/`
- Health Check: `https://yourdomain.com/api/test`
- Products: `https://yourdomain.com/api/products`
- Register: `https://yourdomain.com/api/register`
- Login: `https://yourdomain.com/api/login`

---

## 📞 Support

If you encounter issues:
1. Check `storage/logs/laravel.log`
2. Review cPanel error logs
3. Test API endpoints
4. Verify file permissions

---

## 🎉 Deployment Complete!

Your Envisage E-Commerce platform should now be live!

**Next Steps:**
1. Test all functionality
2. Create admin user
3. Upload products
4. Configure payment gateway
5. Test checkout process

---

**Last Updated:** November 13, 2025  
**Version:** 1.0.0
