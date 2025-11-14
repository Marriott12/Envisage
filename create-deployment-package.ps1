# Envisage - Create Deployment Package
# Run this to create a ZIP file ready for cPanel upload

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "  ENVISAGE DEPLOYMENT PACKAGE CREATOR" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

$projectRoot = "c:\wamp64\www\Envisage"
$backendPath = Join-Path $projectRoot "backend"
$outputPath = "c:\wamp64\www"

# Check if backend exists
if (!(Test-Path $backendPath)) {
    Write-Host "❌ Backend directory not found!" -ForegroundColor Red
    Write-Host "   Expected: $backendPath" -ForegroundColor Yellow
    exit 1
}

Write-Host "[1/4] Checking .env.production..." -ForegroundColor Yellow
if (!(Test-Path "$backendPath\.env.production")) {
    Write-Host "  ⚠️  .env.production not found, creating from .env..." -ForegroundColor Yellow
    Copy-Item "$backendPath\.env" "$backendPath\.env.production"
    Write-Host "  ✅ Created .env.production" -ForegroundColor Green
    Write-Host "  ⚠️  IMPORTANT: Edit .env.production with production values before deploying!" -ForegroundColor Yellow
} else {
    Write-Host "  ✅ .env.production exists" -ForegroundColor Green
}

Write-Host ""
Write-Host "[2/4] Cleaning up temporary files..." -ForegroundColor Yellow

# Remove files that shouldn't be uploaded
$cleanupItems = @(
    "$backendPath\.env",
    "$backendPath\storage\logs\*.log"
)

foreach ($item in $cleanupItems) {
    if (Test-Path $item) {
        Remove-Item -Path $item -Force -ErrorAction SilentlyContinue
        Write-Host "  ✅ Removed: $item" -ForegroundColor Green
    }
}

Write-Host ""
Write-Host "[3/4] Creating deployment ZIP..." -ForegroundColor Yellow

$zipFile = Join-Path $outputPath "envisage-backend-deployment.zip"

# Remove old ZIP if exists
if (Test-Path $zipFile) {
    Remove-Item $zipFile -Force
    Write-Host "  🗑️  Removed old ZIP file" -ForegroundColor Gray
}

# Create ZIP
try {
    Compress-Archive -Path "$backendPath\*" -DestinationPath $zipFile -CompressionLevel Optimal -Force
    Write-Host "  ✅ ZIP file created successfully!" -ForegroundColor Green
} catch {
    Write-Host "  ❌ Failed to create ZIP: $($_.Exception.Message)" -ForegroundColor Red
    exit 1
}

# Get file size
$fileSize = (Get-Item $zipFile).Length / 1MB
$fileSizeFormatted = [math]::Round($fileSize, 2)

Write-Host ""
Write-Host "[4/4] Package Summary" -ForegroundColor Yellow
Write-Host "  📦 File: $zipFile" -ForegroundColor White
Write-Host "  📊 Size: $fileSizeFormatted MB" -ForegroundColor White

Write-Host ""
Write-Host "========================================" -ForegroundColor Green
Write-Host "  DEPLOYMENT PACKAGE READY!" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Green
Write-Host ""

Write-Host "📁 Location:" -ForegroundColor Cyan
Write-Host "   $zipFile" -ForegroundColor White
Write-Host ""

Write-Host "📋 Next Steps:" -ForegroundColor Yellow
Write-Host "   1. ✏️  Edit backend\.env.production with YOUR production settings:" -ForegroundColor White
Write-Host "      - Database credentials" -ForegroundColor Gray
Write-Host "      - Domain URL" -ForegroundColor Gray
Write-Host "      - Stripe keys (production)" -ForegroundColor Gray
Write-Host "      - Email settings" -ForegroundColor Gray
Write-Host ""
Write-Host "   2. 📤 Upload to cPanel:" -ForegroundColor White
Write-Host "      - Login to cPanel File Manager" -ForegroundColor Gray
Write-Host "      - Create folder: /home/youruser/envisage/" -ForegroundColor Gray
Write-Host "      - Upload $zipFile" -ForegroundColor Gray
Write-Host "      - Extract the ZIP file" -ForegroundColor Gray
Write-Host ""
Write-Host "   3. 🔧 Complete setup via SSH (see DEPLOYMENT_READY.md)" -ForegroundColor White
Write-Host ""

Write-Host "Documentation:" -ForegroundColor Cyan
Write-Host "   - DEPLOYMENT_READY.md  (Quick start guide)" -ForegroundColor White
Write-Host "   - CPANEL_DEPLOYMENT_GUIDE.md  (Complete guide)" -ForegroundColor White
Write-Host "   - UPLOAD_CHECKLIST.md  (Step-by-step checklist)" -ForegroundColor White
Write-Host ""

Write-Host "Happy Deploying!" -ForegroundColor Green
Write-Host ""

# Ask if user wants to open the folder
$openFolder = Read-Host "Open ZIP file location? (y/n)"
if ($openFolder -eq 'y' -or $openFolder -eq 'Y') {
    Invoke-Item (Split-Path $zipFile -Parent)
}
