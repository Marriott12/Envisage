# 🚀 CPANEL UPLOAD - QUICK START

## ⚡ FASTEST METHOD (3 Steps)

### Step 1: Edit Production Config (2 minutes)

**Open and edit:**
```
c:\wamp64\www\Envisage\backend\.env.production
```

**Change these values:**
```env
APP_URL=https://yourdomain.com                    ← Your domain
DB_DATABASE=youruser_envisage                     ← Your cPanel database
DB_USERNAME=youruser_envisage                     ← Your cPanel user
DB_PASSWORD=your_password_here                    ← Your cPanel password
```

### Step 2: Create ZIP Package (1 minute)

**Run this in PowerShell:**
```powershell
cd c:\wamp64\www\Envisage
.\create-deployment-package.ps1
```

This creates: `c:\wamp64\www\envisage-backend-deployment.zip`

### Step 3: Upload & Deploy (5 minutes)

**A. Upload via cPanel:**
1. Login to cPanel
2. File Manager → Create folder: `/home/youruser/envisage/`
3. Upload the ZIP file
4. Extract it

**B. Setup via SSH:**
```bash
cd ~/envisage
mv .env.production .env
chmod -R 755 storage bootstrap/cache
php artisan migrate --force
ln -s ~/envisage/storage/app/public ~/public_html/storage
php artisan config:cache && php artisan route:cache
```

**C. Move public files:**
- Copy `~/envisage/public/index.php` to `~/public_html/index.php`
- Copy `~/envisage/public/.htaccess` to `~/public_html/.htaccess`
- Edit `~/public_html/index.php` - change paths to `../envisage/`

**Done!** Test: https://yourdomain.com/api/

---

## 📚 Full Documentation

| File | Purpose |
|------|---------|
| **DEPLOYMENT_READY.md** | Complete deployment summary |
| **CPANEL_DEPLOYMENT_GUIDE.md** | Detailed step-by-step guide |
| **UPLOAD_CHECKLIST.md** | Quick reference checklist |

---

## ✅ Created Files

- ✅ `.env.production` - Production environment config
- ✅ `create-deployment-package.ps1` - ZIP creator script
- ✅ All deployment documentation

---

## 🎯 You're Ready!

Everything is prepared for cPanel deployment. Follow the 3 steps above!
