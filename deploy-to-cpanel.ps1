# Envisage E-Commerce - cPanel Deployment Preparation Script
# Run this BEFORE uploading to cPanel

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "  ENVISAGE CPANEL DEPLOYMENT PREP" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Step 1: Clean up backend
Write-Host "[1/3] Cleaning up backend directory..." -ForegroundColor Yellow
cd backend

# Remove development files
if (Test-Path "node_modules") {
    Remove-Item -Recurse -Force "node_modules"
    Write-Host "  [OK] Removed node_modules" -ForegroundColor Green
}

# Clear cache and logs
if (Test-Path "storage/logs") {
    Get-ChildItem "storage/logs" -Filter "*.log" -ErrorAction SilentlyContinue | Remove-Item -Force
    Write-Host "  [OK] Cleared log files" -ForegroundColor Green
}

if (Test-Path "bootstrap/cache") {
    Get-ChildItem "bootstrap/cache" -Filter "*.php" -ErrorAction SilentlyContinue | Remove-Item -Force
    Write-Host "  [OK] Cleared bootstrap cache" -ForegroundColor Green
}

Write-Host ""

# Step 2: Copy .env to .env.production
Write-Host "[2/3] Creating production .env template..." -ForegroundColor Yellow

if (Test-Path ".env") {
    Copy-Item ".env" ".env.production"
    Write-Host "  [OK] Created .env.production template" -ForegroundColor Green
    Write-Host "  [INFO] Please edit .env.production with your production values" -ForegroundColor Yellow
} else {
    Write-Host "  [WARN] .env file not found" -ForegroundColor Yellow
}

Write-Host ""

# Step 3: Create summary
Write-Host "[3/3] Creating deployment summary..." -ForegroundColor Yellow

cd ..

Write-Host ""
Write-Host "========================================" -ForegroundColor Green
Write-Host "  PREPARATION COMPLETE!" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Green
Write-Host ""
Write-Host "Files created:" -ForegroundColor Cyan
Write-Host "  [OK] backend/.env.production" -ForegroundColor White
Write-Host "  [OK] CPANEL_DEPLOYMENT_GUIDE.md" -ForegroundColor White
Write-Host ""
Write-Host "Next Steps:" -ForegroundColor Yellow
Write-Host "  1. Read CPANEL_DEPLOYMENT_GUIDE.md (complete guide)" -ForegroundColor White
Write-Host "  2. Update backend/.env.production with your production details" -ForegroundColor White
Write-Host "  3. Upload files to cPanel following the guide" -ForegroundColor White
Write-Host "  4. Run installation commands via SSH" -ForegroundColor White
Write-Host ""
Write-Host "Quick Summary:" -ForegroundColor Cyan
Write-Host "  - Upload backend files to /home/youruser/envisage/" -ForegroundColor White

Write-Host "  - Move public/ contents to public_html/" -ForegroundColor White  
Write-Host "  - Update public_html/index.php paths" -ForegroundColor White
Write-Host "  - Create database and update .env" -ForegroundColor White
Write-Host "  - Run: composer install --no-dev" -ForegroundColor White
Write-Host "  - Run: php artisan migrate --force" -ForegroundColor White
Write-Host "  - Run: php artisan config:cache" -ForegroundColor White
Write-Host ""
Write-Host "Ready to deploy!" -ForegroundColor Green
Write-Host ""
