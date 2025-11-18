# Envisage E-Commerce Marketplace - Setup Guide

## Prerequisites

- ✅ PHP 7.3+ or 8.0+ (Installed via WAMP)
- ✅ Composer
- ✅ MySQL 5.7+ (Included in WAMP)
- ✅ Node.js 16+
- ✅ npm or yarn

## Important Note: Windows File Locking Issue

If you encounter "Could not delete" errors during `composer install`, this is due to Windows Defender or antivirus software scanning files. 

**Solutions:**
1. Temporarily disable real-time protection during installation
2. Add the `c:\wamp64\www\Envisage` folder to Windows Defender exclusions
3. Use the `--prefer-source` flag: `composer install --prefer-source`

## Backend Setup (Laravel)

### 1. Navigate to Backend Directory
```powershell
cd c:\wamp64\www\Envisage\backend
```

### 2. Install Dependencies
```powershell
# If you get file locking errors, try one of these alternatives:
composer install --prefer-source
# OR
composer install --no-scripts
# Then run:
composer dump-autoload
```

### 3. Create Environment File
```powershell
Copy-Item .env.example .env
```

### 4. Generate Application Key
```powershell
php artisan key:generate
```

### 5. Configure Database
Edit `backend/.env` and update these settings:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=envisage_db
DB_USERNAME=root
DB_PASSWORD=
```

### 6. Create Database
Open phpMyAdmin (http://localhost/phpmyadmin) or use MySQL command:
```sql
CREATE DATABASE envisage_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 7. Run Migrations
```powershell
php artisan migrate
```

### 8. Seed Database (Optional)
```powershell
php artisan db:seed
```

### 9. Create Storage Link
```powershell
php artisan storage:link
```

### 10. Set Permissions (if needed)
```powershell
# Make storage and cache writable
icacls storage /grant Everyone:F /T
icacls bootstrap\cache /grant Everyone:F /T
```

### 11. Start Laravel Development Server
```powershell
php artisan serve
```
Backend will run on: **http://localhost:8000**

## Frontend Setup (Next.js)

### 1. Navigate to Frontend Directory
```powershell
cd c:\wamp64\www\Envisage\frontend
```

### 2. Install Dependencies
```powershell
npm install
# OR
yarn install
```

### 3. Create Environment File
```powershell
Copy-Item .env.local.example .env.local
```

### 4. Configure Environment
Edit `frontend/.env.local`:
```env
NEXT_PUBLIC_API_URL=http://localhost:8000/api
NEXT_PUBLIC_APP_URL=http://localhost:3000
NEXT_PUBLIC_APP_NAME="Envisage Marketplace"
```

### 5. Start Next.js Development Server
```powershell
npm run dev
# OR
yarn dev
```
Frontend will run on: **http://localhost:3000**

## Configuration

### Email Setup (Optional for Development)
Edit `backend/.env`:
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io  # Use Mailtrap for testing
MAIL_PORT=2525
MAIL_USERNAME=your_mailtrap_username
MAIL_PASSWORD=your_mailtrap_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@envisage.local
MAIL_FROM_NAME="${APP_NAME}"
```

### Stripe Payment (Optional for Development)
1. Get your test keys from https://dashboard.stripe.com/test/apikeys
2. Edit `backend/.env`:
```env
STRIPE_SECRET=sk_test_your_stripe_secret_key_here
STRIPE_WEBHOOK_SECRET=whsec_your_webhook_secret_here
```
3. Edit `frontend/.env.local`:
```env
NEXT_PUBLIC_STRIPE_PUBLIC_KEY=pk_test_your_stripe_public_key_here
```

## Testing

### Backend Tests
```powershell
cd backend
php artisan test
```

### Frontend Tests
```powershell
cd frontend
npm test
```

## Default Access

After seeding the database, you can log in with:

**Admin Account:**
- Email: admin@envisage.com
- Password: password

**Seller Account:**
- Email: seller@envisage.com
- Password: password

**User Account:**
- Email: user@envisage.com
- Password: password

## Troubleshooting

### Composer Install Fails
1. Add exclusion to Windows Defender for the project folder
2. Use `composer install --prefer-source` instead
3. Try `composer install --no-scripts` followed by `composer dump-autoload`

### Port Already in Use
- Backend: Change port with `php artisan serve --port=8001`
- Frontend: Next.js will automatically suggest an alternative port

### Database Connection Error
1. Ensure WAMP MySQL is running
2. Verify database credentials in `.env`
3. Check if database exists

### CORS Errors
- Ensure `SANCTUM_STATEFUL_DOMAINS` in `backend/.env` includes `localhost:3000`
- Check `config/cors.php` allows your frontend URL

### Storage/Cache Permission Errors
```powershell
cd backend
icacls storage /grant Everyone:F /T
icacls bootstrap\cache /grant Everyone:F /T
```

## Next Steps

1. ✅ Clone repository
2. ✅ Setup backend environment
3. ✅ Setup frontend environment
4. 🔄 Configure database
5. 🔄 Run migrations
6. 🔄 Start development servers
7. 📝 Begin development!

## Useful Commands

### Backend
```powershell
# Clear cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Run specific test
php artisan test --filter TestName

# Create new migration
php artisan make:migration create_table_name

# Create new controller
php artisan make:controller ControllerName
```

### Frontend
```powershell
# Build for production
npm run build

# Type checking
npm run type-check

# Linting
npm run lint
```

## Production Deployment

For production deployment, refer to:
- `DEPLOYMENT_CHECKLIST.md`
- `deploy.bat` (Windows) or `deploy.sh` (Linux/Mac)

---

**Built with ❤️ by the Envisage Team**
