# Envisage Quick Setup Script for Windows
# Run this script in PowerShell as Administrator

Write-Host "========================================" -ForegroundColor Cyan
Write-Host " Envisage E-Commerce Setup" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Get current directory
$projectRoot = $PSScriptRoot

# Backend Setup
Write-Host "[1/6] Setting up Backend..." -ForegroundColor Yellow
$backendPath = Join-Path $projectRoot "backend"
cd $backendPath

# Create .env if it doesn't exist
if (!(Test-Path ".env")) {
    Write-Host "  - Creating .env file..." -ForegroundColor Green
    Copy-Item ".env.example" ".env"
}

# Install composer dependencies
Write-Host "  - Installing Composer dependencies..." -ForegroundColor Green
Write-Host "    (This may take a few minutes. If it fails due to file locking," -ForegroundColor Gray
Write-Host "     temporarily disable Windows Defender real-time protection)" -ForegroundColor Gray

# Try composer install with different strategies
$composerSuccess = $false
$strategies = @("--prefer-source", "--prefer-dist --no-scripts", "")

foreach ($strategy in $strategies) {
    Write-Host "    Trying: composer install $strategy" -ForegroundColor Gray
    $proc = Start-Process -FilePath "composer" -ArgumentList "install $strategy" -NoNewWindow -Wait -PassThru
    if ($proc.ExitCode -eq 0) {
        $composerSuccess = $true
        break
    }
}

if (!$composerSuccess) {
    Write-Host "  ✗ Composer install failed. Please run manually:" -ForegroundColor Red
    Write-Host "    cd backend" -ForegroundColor Yellow
    Write-Host "    composer install --prefer-source" -ForegroundColor Yellow
    Write-Host ""
    Write-Host "  If this continues to fail, add c:\wamp64\www\Envisage to Windows Defender exclusions" -ForegroundColor Yellow
}

# Generate application key
if (Test-Path "vendor\autoload.php") {
    Write-Host "  - Generating application key..." -ForegroundColor Green
    php artisan key:generate --force
}

Write-Host ""

# Frontend Setup
Write-Host "[2/6] Setting up Frontend..." -ForegroundColor Yellow
$frontendPath = Join-Path $projectRoot "frontend"
cd $frontendPath

# Create .env.local if it doesn't exist
if (!(Test-Path ".env.local")) {
    Write-Host "  - Creating .env.local file..." -ForegroundColor Green
    Copy-Item ".env.local.example" ".env.local"
}

# Install npm dependencies
Write-Host "  - Installing npm dependencies..." -ForegroundColor Green
Write-Host "    (This may take a few minutes...)" -ForegroundColor Gray
npm install --legacy-peer-deps 2>&1 | Out-Null

if ($LASTEXITCODE -eq 0) {
    Write-Host "  ✓ npm install completed successfully" -ForegroundColor Green
} else {
    Write-Host "  ✗ npm install failed. Please run manually:" -ForegroundColor Red
    Write-Host "    cd frontend" -ForegroundColor Yellow
    Write-Host "    npm install" -ForegroundColor Yellow
}

Write-Host ""

# Database Setup
Write-Host "[3/6] Database Configuration" -ForegroundColor Yellow
Write-Host "  Please ensure:" -ForegroundColor Cyan
Write-Host "  1. WAMP MySQL service is running" -ForegroundColor White
Write-Host "  2. Create database 'envisage_db' via phpMyAdmin" -ForegroundColor White
Write-Host "     URL: http://localhost/phpmyadmin" -ForegroundColor Gray
Write-Host "  3. Update backend/.env with your database credentials" -ForegroundColor White
Write-Host ""

# Ask if database is ready
$dbReady = Read-Host "  Is the database ready? (y/n)"

if ($dbReady -eq "y" -or $dbReady -eq "Y") {
    cd $backendPath
    if (Test-Path "vendor\autoload.php") {
        Write-Host "  - Running migrations..." -ForegroundColor Green
        php artisan migrate --force
        
        $seedDb = Read-Host "  Would you like to seed the database with sample data? (y/n)"
        if ($seedDb -eq "y" -or $seedDb -eq "Y") {
            Write-Host "  - Seeding database..." -ForegroundColor Green
            php artisan db:seed --force
        }
        
        Write-Host "  - Creating storage link..." -ForegroundColor Green
        php artisan storage:link
    }
} else {
    Write-Host "  Skipping database setup. Run these commands manually later:" -ForegroundColor Yellow
    Write-Host "    cd backend" -ForegroundColor Gray
    Write-Host "    php artisan migrate" -ForegroundColor Gray
    Write-Host "    php artisan db:seed" -ForegroundColor Gray
    Write-Host "    php artisan storage:link" -ForegroundColor Gray
}

Write-Host ""

# Summary
Write-Host "[4/6] Setup Summary" -ForegroundColor Yellow
Write-Host "  Backend: $backendPath" -ForegroundColor White
Write-Host "  Frontend: $frontendPath" -ForegroundColor White
Write-Host ""

# Configuration Review
Write-Host "[5/6] Configuration Files" -ForegroundColor Yellow
Write-Host "  Please review and update these files:" -ForegroundColor Cyan
Write-Host "  - backend/.env (Database, Mail, Stripe)" -ForegroundColor White
Write-Host "  - frontend/.env.local (API URL, Stripe Public Key)" -ForegroundColor White
Write-Host ""

# Start Servers
Write-Host "[6/6] Start Development Servers" -ForegroundColor Yellow
Write-Host ""
Write-Host "  To start the backend (in a new terminal):" -ForegroundColor Cyan
Write-Host "    cd $backendPath" -ForegroundColor White
Write-Host "    php artisan serve" -ForegroundColor Green
Write-Host "    # Runs on http://localhost:8000" -ForegroundColor Gray
Write-Host ""
Write-Host "  To start the frontend (in another new terminal):" -ForegroundColor Cyan
Write-Host "    cd $frontendPath" -ForegroundColor White
Write-Host "    npm run dev" -ForegroundColor Green
Write-Host "    # Runs on http://localhost:3000" -ForegroundColor Gray
Write-Host ""

Write-Host "========================================" -ForegroundColor Cyan
Write-Host " Setup Complete!" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Next Steps:" -ForegroundColor Yellow
Write-Host "1. Review SETUP_GUIDE.md for detailed instructions" -ForegroundColor White
Write-Host "2. Configure your .env files" -ForegroundColor White
Write-Host "3. Start the development servers" -ForegroundColor White
Write-Host "4. Visit http://localhost:3000" -ForegroundColor White
Write-Host ""

cd $projectRoot
