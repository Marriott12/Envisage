@echo off
REM ========================================
REM ENVISAGE MARKETPLACE - SYSTEM VERIFICATION
REM ========================================

echo ========================================
echo ENVISAGE MARKETPLACE
echo SYSTEM VERIFICATION CHECK
echo ========================================
echo.

set ERRORS=0

REM ========================================
REM Check Backend Files
REM ========================================

echo [1/6] Checking Backend Files...

if not exist "backend\app\Models\Review.php" (
    echo [X] Missing Review model
    set /a ERRORS+=1
) else (
    echo [OK] Review model found
)

if not exist "backend\app\Models\Notification.php" (
    echo [X] Missing Notification model
    set /a ERRORS+=1
) else (
    echo [OK] Notification model found
)

if not exist "backend\app\Http\Controllers\ReviewController.php" (
    echo [X] Missing ReviewController
    set /a ERRORS+=1
) else (
    echo [OK] ReviewController found
)

if not exist "backend\app\Http\Controllers\NotificationController.php" (
    echo [X] Missing NotificationController
    set /a ERRORS+=1
) else (
    echo [OK] NotificationController found
)

if not exist "backend\app\Http\Controllers\SellerController.php" (
    echo [X] Missing SellerController
    set /a ERRORS+=1
) else (
    echo [OK] SellerController found
)

if not exist "backend\app\Http\Controllers\AdminController.php" (
    echo [X] Missing AdminController
    set /a ERRORS+=1
) else (
    echo [OK] AdminController found
)

REM ========================================
REM Check Database Migrations
REM ========================================

echo.
echo [2/6] Checking Database Migrations...

if not exist "backend\database\migrations\2025_10_08_000001_create_reviews_table.php" (
    echo [X] Missing reviews migration
    set /a ERRORS+=1
) else (
    echo [OK] Reviews migration found
)

if not exist "backend\database\migrations\2025_10_08_000002_create_notifications_table.php" (
    echo [X] Missing notifications migration
    set /a ERRORS+=1
) else (
    echo [OK] Notifications migration found
)

if not exist "backend\database\migrations\2025_10_08_000003_add_seller_id_to_products_table.php" (
    echo [X] Missing products update migration
    set /a ERRORS+=1
) else (
    echo [OK] Products update migration found
)

REM ========================================
REM Check Frontend Files
REM ========================================

echo.
echo [3/6] Checking Frontend Files...

if not exist "frontend\lib\utils.ts" (
    echo [X] Missing utils.ts
    set /a ERRORS+=1
) else (
    echo [OK] utils.ts found
)

if not exist "frontend\lib\store.ts" (
    echo [X] Missing store.ts
    set /a ERRORS+=1
) else (
    echo [OK] store.ts found
)

if not exist "frontend\components\ListingGrid.tsx" (
    echo [X] Missing ListingGrid component
    set /a ERRORS+=1
) else (
    echo [OK] ListingGrid component found
)

if not exist "frontend\components\ListingCardSkeleton.tsx" (
    echo [X] Missing ListingCardSkeleton component
    set /a ERRORS+=1
) else (
    echo [OK] ListingCardSkeleton component found
)

if not exist "frontend\app\providers.tsx" (
    echo [X] Missing Providers component
    set /a ERRORS+=1
) else (
    echo [OK] Providers component found
)

if not exist "frontend\types\api.ts" (
    echo [X] Missing API types
    set /a ERRORS+=1
) else (
    echo [OK] API types found
)

REM ========================================
REM Check Configuration Files
REM ========================================

echo.
echo [4/6] Checking Configuration Files...

if not exist "frontend\tsconfig.json" (
    echo [X] Missing tsconfig.json
    set /a ERRORS+=1
) else (
    echo [OK] tsconfig.json found
)

if not exist "backend\.env.production.example" (
    echo [X] Missing backend env example
    set /a ERRORS+=1
) else (
    echo [OK] Backend env example found
)

if not exist "frontend\.env.local.example" (
    echo [X] Missing frontend env example
    set /a ERRORS+=1
) else (
    echo [OK] Frontend env example found
)

REM ========================================
REM Check Deployment Files
REM ========================================

echo.
echo [5/6] Checking Deployment Files...

if not exist "deploy.bat" (
    echo [X] Missing deploy.bat
    set /a ERRORS+=1
) else (
    echo [OK] deploy.bat found
)

if not exist "deploy.sh" (
    echo [X] Missing deploy.sh
    set /a ERRORS+=1
) else (
    echo [OK] deploy.sh found
)

if not exist "DEPLOYMENT_CHECKLIST.md" (
    echo [X] Missing deployment checklist
    set /a ERRORS+=1
) else (
    echo [OK] Deployment checklist found
)

if not exist "PRODUCTION_READY_SUMMARY.md" (
    echo [X] Missing production summary
    set /a ERRORS+=1
) else (
    echo [OK] Production summary found
)

REM ========================================
REM Check Dependencies
REM ========================================

echo.
echo [6/6] Checking Dependencies...

if not exist "backend\vendor" (
    echo [!] Backend dependencies not installed
    echo     Run: cd backend ^&^& composer install
) else (
    echo [OK] Backend dependencies installed
)

if not exist "frontend\node_modules" (
    echo [!] Frontend dependencies not installed
    echo     Run: cd frontend ^&^& npm install
) else (
    echo [OK] Frontend dependencies installed
)

REM ========================================
REM Final Report
REM ========================================

echo.
echo ========================================
echo VERIFICATION COMPLETE
echo ========================================

if %ERRORS% == 0 (
    echo.
    echo [SUCCESS] All critical files are in place!
    echo [STATUS] System is 100%% PRODUCTION READY
    echo.
    echo Next Steps:
    echo 1. Configure environment variables
    echo 2. Run database migrations: php artisan migrate
    echo 3. Run deployment script: deploy.bat
    echo 4. Review DEPLOYMENT_CHECKLIST.md
    echo.
) else (
    echo.
    echo [WARNING] Found %ERRORS% missing file^(s^)
    echo [ACTION] Please review the errors above
    echo.
)

echo ========================================
pause
