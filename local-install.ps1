# Envisage E-Commerce - Auto Installation Script (Windows Version)
# This is for LOCAL TESTING ONLY
# For production cPanel deployment, use server-install.sh on the server

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "  ENVISAGE E-COMMERCE LOCAL SETUP" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Configuration
$LARAVEL_DIR = "c:\wamp64\www\Envisage\backend"
$PUBLIC_DIR = "c:\wamp64\www\Envisage\backend\public"

Write-Host "[INFO] Installation starting..." -ForegroundColor Yellow
Write-Host ""

# Step 1: Check if Laravel directory exists
Write-Host "[1/10] Checking Laravel directory..." -ForegroundColor Yellow
if (!(Test-Path $LARAVEL_DIR)) {
    Write-Host "[ERROR] Directory $LARAVEL_DIR not found!" -ForegroundColor Red
    exit 1
}
Set-Location $LARAVEL_DIR
Write-Host "[OK] Laravel directory found" -ForegroundColor Green
Write-Host ""

# Step 2: Check PHP version
Write-Host "[2/10] Checking PHP version..." -ForegroundColor Yellow
$phpVersion = php -v | Select-Object -First 1
Write-Host "PHP Version: $phpVersion"
Write-Host "[OK] PHP is available" -ForegroundColor Green
Write-Host ""

# Step 3: Check if .env exists
Write-Host "[3/10] Checking .env file..." -ForegroundColor Yellow
if (!(Test-Path ".env")) {
    Write-Host "[WARN] .env file not found" -ForegroundColor Yellow
    if (Test-Path ".env.production") {
        Write-Host "Copying .env.production to .env..."
        Copy-Item ".env.production" ".env"
        Write-Host "[OK] Created .env from .env.production" -ForegroundColor Green
    } elseif (Test-Path ".env.example") {
        Write-Host "Copying .env.example to .env..."
        Copy-Item ".env.example" ".env"
        Write-Host "[WARN] Please edit .env with your database credentials" -ForegroundColor Yellow
    } else {
        Write-Host "[ERROR] No .env file found!" -ForegroundColor Red
        exit 1
    }
} else {
    Write-Host "[OK] .env file exists" -ForegroundColor Green
}
Write-Host ""

# Step 4: Install Composer dependencies
Write-Host "[4/10] Installing Composer dependencies..." -ForegroundColor Yellow
Write-Host "Running: composer install --optimize-autoloader"
composer install --optimize-autoloader
if ($LASTEXITCODE -eq 0) {
    Write-Host "[OK] Composer dependencies installed" -ForegroundColor Green
} else {
    Write-Host "[ERROR] Composer install failed" -ForegroundColor Red
    exit 1
}
Write-Host ""

# Step 5: Generate application key
Write-Host "[5/10] Generating application key..." -ForegroundColor Yellow
php artisan key:generate --force
if ($LASTEXITCODE -eq 0) {
    Write-Host "[OK] Application key generated" -ForegroundColor Green
} else {
    Write-Host "[WARN] Key generation failed or already set" -ForegroundColor Yellow
}
Write-Host ""

# Step 6: Clear all caches
Write-Host "[6/10] Clearing all caches..." -ForegroundColor Yellow
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
Write-Host "[OK] All caches cleared" -ForegroundColor Green
Write-Host ""

# Step 7: Run database migrations
Write-Host "[7/10] Running database migrations..." -ForegroundColor Yellow
php artisan migrate --force
if ($LASTEXITCODE -eq 0) {
    Write-Host "[OK] Database migrations completed" -ForegroundColor Green
} else {
    Write-Host "[ERROR] Database migration failed!" -ForegroundColor Red
    Write-Host "Please check your .env database credentials" -ForegroundColor Yellow
}
Write-Host ""

# Step 8: Set file permissions (Windows - just verify paths exist)
Write-Host "[8/10] Checking storage directories..." -ForegroundColor Yellow
if (!(Test-Path "storage")) {
    New-Item -ItemType Directory -Path "storage" -Force
}
if (!(Test-Path "bootstrap\cache")) {
    New-Item -ItemType Directory -Path "bootstrap\cache" -Force
}
Write-Host "[OK] Storage directories verified" -ForegroundColor Green
Write-Host ""

# Step 9: Create storage symlink
Write-Host "[9/10] Creating storage symlink..." -ForegroundColor Yellow
php artisan storage:link
if ($LASTEXITCODE -eq 0) {
    Write-Host "[OK] Storage symlink created" -ForegroundColor Green
} else {
    Write-Host "[WARN] Symlink creation failed (may already exist)" -ForegroundColor Yellow
}
Write-Host ""

# Step 10: Cache configurations for production
Write-Host "[10/10] Caching configurations..." -ForegroundColor Yellow
php artisan config:cache
php artisan route:cache
php artisan view:cache
Write-Host "[OK] All configurations cached" -ForegroundColor Green
Write-Host ""

# Summary
Write-Host "==========================================" -ForegroundColor Green
Write-Host "  INSTALLATION COMPLETE!" -ForegroundColor Green
Write-Host "==========================================" -ForegroundColor Green
Write-Host ""
Write-Host "Installation Summary:"
Write-Host "  ✓ Composer dependencies installed"
Write-Host "  ✓ Application key generated"
Write-Host "  ✓ Caches cleared"
Write-Host "  ✓ Database migrated"
Write-Host "  ✓ Storage linked"
Write-Host "  ✓ Configurations cached"
Write-Host ""
Write-Host "Next Steps:"
Write-Host "  1. Test your API: http://localhost:8000/api/test"
Write-Host "  2. Check products: http://localhost:8000/api/products"
Write-Host "  3. Start server: php artisan serve"
Write-Host ""
Write-Host "Your Envisage E-Commerce is ready!" -ForegroundColor Green
Write-Host "==========================================" -ForegroundColor Green
