# Create auth-fix package for production upload

$timestamp = Get-Date -Format "yyyyMMdd_HHmmss"
$packageName = "auth-controller-fix-$timestamp.zip"

Write-Host "Creating auth controller fix package..." -ForegroundColor Cyan

# Create temp directory
$tempDir = "c:\wamp64\www\Envisage\temp-auth-fix"
New-Item -ItemType Directory -Path $tempDir -Force | Out-Null

# Copy AuthController
$controllerPath = "c:\wamp64\www\Envisage\backend\app\Http\Controllers"
$destPath = "$tempDir\app\Http\Controllers"
New-Item -ItemType Directory -Path $destPath -Force | Out-Null
Copy-Item "$controllerPath\AuthController.php" "$destPath\" -Force

# Create deployment instructions
@"
AUTH CONTROLLER FIX - DEPLOYMENT INSTRUCTIONS
==============================================

Files Changed:
1. app/Http/Controllers/AuthController.php

Changes Made:
- Fixed getRoleNames() calls to use simple 'role' field
- Changed default registration role from 'user' to 'customer'
- Updated login response to return 'role' instead of 'roles'
- Updated getUser response to return 'role' instead of 'roles'

Deployment Steps:
1. Upload via cPanel File Manager or FTP
2. Extract to /home/envithcy/envisage/
3. Overwrite existing AuthController.php
4. No cache clearing needed (code change only)

Test Login:
- Email: admin@envisagezm.com
- Password: Admin@2025
- Should redirect to dashboard after login

Generated: $timestamp
"@ | Out-File "$tempDir\DEPLOYMENT_INSTRUCTIONS.txt"

# Create zip
Compress-Archive -Path "$tempDir\*" -DestinationPath "c:\wamp64\www\Envisage\$packageName" -Force

# Cleanup
Remove-Item $tempDir -Recurse -Force

Write-Host "`n✓ Package created: $packageName" -ForegroundColor Green
Write-Host "`nUpload this file to production via cPanel:" -ForegroundColor Yellow
Write-Host "  File: c:\wamp64\www\Envisage\$packageName" -ForegroundColor White
Write-Host "  Destination: /home/envithcy/envisage/" -ForegroundColor White
Write-Host "`nThen extract and test login!" -ForegroundColor Cyan
