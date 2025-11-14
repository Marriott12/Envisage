# Update Production with Complete Marketplace Data
# Run this script to upload the seeder and setup script

Write-Host "================================================" -ForegroundColor Cyan
Write-Host "   Uploading Marketplace Data & Setup Scripts" -ForegroundColor Cyan
Write-Host "================================================" -ForegroundColor Cyan
Write-Host ""

$backendPath = "c:\wamp64\www\Envisage\backend"
$tempPath = "$env:TEMP\envisage-update"

# Create temp directory
Write-Host "Creating update package..." -ForegroundColor Yellow
if (Test-Path $tempPath) {
    Remove-Item -Path $tempPath -Recurse -Force
}
New-Item -ItemType Directory -Path $tempPath | Out-Null

# Copy files to upload
Write-Host "Copying seeder file..." -ForegroundColor Gray
Copy-Item "$backendPath\database\seeders\CompleteMarketplaceSeeder.php" "$tempPath\CompleteMarketplaceSeeder.php"

Write-Host "Copying production setup script..." -ForegroundColor Gray
Copy-Item "$backendPath\production-setup.sh" "$tempPath\production-setup.sh"

Write-Host "Copying production environment..." -ForegroundColor Gray
Copy-Item "$backendPath\.env.production" "$tempPath\.env.production"

# Create ZIP
Write-Host ""
Write-Host "Creating ZIP file..." -ForegroundColor Yellow
$zipPath = Join-Path ([Environment]::GetFolderPath("Desktop")) "envisage-update.zip"
if (Test-Path $zipPath) {
    Remove-Item $zipPath -Force
}

Compress-Archive -Path "$tempPath\*" -DestinationPath $zipPath -Force

Write-Host "Package created: $zipPath" -ForegroundColor Green

# Cleanup
Remove-Item -Path $tempPath -Recurse -Force

Write-Host ""
Write-Host "================================================" -ForegroundColor Cyan
Write-Host "   Upload Instructions" -ForegroundColor Cyan
Write-Host "================================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "1. Upload envisage-update.zip to cPanel File Manager" -ForegroundColor White
Write-Host "2. Extract to /home/envithcy/envisage/" -ForegroundColor White
Write-Host "3. Run these commands via SSH:" -ForegroundColor White
Write-Host ""
Write-Host "   cd /home/envithcy/envisage" -ForegroundColor Gray
Write-Host "   mv CompleteMarketplaceSeeder.php database/seeders/" -ForegroundColor Gray
Write-Host "   dos2unix production-setup.sh" -ForegroundColor Gray
Write-Host "   chmod +x production-setup.sh" -ForegroundColor Gray
Write-Host "   ./production-setup.sh" -ForegroundColor Gray
Write-Host ""
Write-Host "================================================" -ForegroundColor Cyan
Write-Host ""

# Open desktop folder
$desktopPath = [Environment]::GetFolderPath("Desktop")
Start-Process "explorer.exe" -ArgumentList $desktopPath
