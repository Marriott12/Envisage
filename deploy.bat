@echo off
REM ========================================
REM ENVISAGE MARKETPLACE - DEPLOYMENT SCRIPT (Windows)
REM ========================================

echo ========================================
echo ENVISAGE MARKETPLACE DEPLOYMENT
echo ========================================

REM ========================================
REM 1. BACKEND DEPLOYMENT
REM ========================================

echo.
echo [STEP 1] Backend Deployment
echo --------------------------------

cd backend

REM Install Composer dependencies
echo Installing Composer dependencies...
call composer install --optimize-autoloader --no-dev

REM Generate application key (if not exists)
echo Generating application key...
php artisan key:generate --force

REM Run migrations
echo Running database migrations...
php artisan migrate --force

REM Clear caches
echo Clearing caches...
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

REM Optimize for production
echo Optimizing for production...
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo Backend deployment complete!

REM ========================================
REM 2. FRONTEND DEPLOYMENT
REM ========================================

echo.
echo [STEP 2] Frontend Deployment
echo --------------------------------

cd ..\frontend

REM Install npm dependencies
echo Installing npm dependencies...
call npm install

REM Build frontend
echo Building production frontend...
call npm run build

echo Frontend deployment complete!

REM ========================================
REM 3. VERIFICATION
REM ========================================

echo.
echo [STEP 3] Deployment Verification
echo --------------------------------

if not exist "..\backend\.env" (
    echo [ERROR] Missing .env file in backend!
    echo Please copy .env.production.example to .env and configure it.
    pause
    exit /b 1
)

if not exist ".next" (
    echo [ERROR] Frontend build failed!
    pause
    exit /b 1
)

echo All verifications passed!

REM ========================================
REM 4. POST-DEPLOYMENT TASKS
REM ========================================

echo.
echo ========================================
echo POST-DEPLOYMENT CHECKLIST
echo ========================================
echo 1. Update .env with production database credentials
echo 2. Update NEXT_PUBLIC_API_URL in frontend/.env.local
echo 3. Run: php artisan db:seed (if needed)
echo 4. Configure SSL certificate in cPanel
echo 5. Set up cron jobs for Laravel scheduler
echo 6. Test all API endpoints
echo 7. Test frontend pages and functionality
echo.
echo ========================================
echo DEPLOYMENT COMPLETE!
echo ========================================

cd ..
pause
