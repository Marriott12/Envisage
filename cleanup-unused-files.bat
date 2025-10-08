@echo off
REM Cleanup Script for Envisage Project
REM Removes all unused and legacy files
REM Date: October 8, 2025

echo ========================================
echo ENVISAGE PROJECT CLEANUP SCRIPT
echo ========================================
echo.
echo This script will permanently delete unused files:
echo - Temporary fix scripts (fix-*.js, fix-*.ps1)
echo - Legacy database SQL files
echo - Legacy assets directory
echo - Old documentation files
echo.
echo WARNING: This action cannot be undone!
echo.
pause

echo.
echo Starting cleanup...
echo.

REM 1. Remove temporary fix scripts
echo [1/6] Removing temporary fix scripts...
if exist "fix-files.js" (
    del /F /Q "fix-files.js"
    echo   - Deleted fix-files.js
)
if exist "fix-page.js" (
    del /F /Q "fix-page.js"
    echo   - Deleted fix-page.js
)
if exist "fix-condition.js" (
    del /F /Q "fix-condition.js"
    echo   - Deleted fix-condition.js
)
if exist "fix-marketplace.ps1" (
    del /F /Q "fix-marketplace.ps1"
    echo   - Deleted fix-marketplace.ps1
)

REM 2. Remove legacy database SQL files (migrations are in backend)
echo.
echo [2/6] Removing legacy database SQL files...
if exist "database\" (
    if exist "database\admin_schema.sql" (
        del /F /Q "database\admin_schema.sql"
        echo   - Deleted admin_schema.sql
    )
    if exist "database\marketplace_schema.sql" (
        del /F /Q "database\marketplace_schema.sql"
        echo   - Deleted marketplace_schema.sql
    )
    if exist "database\validation_security_schema.sql" (
        del /F /Q "database\validation_security_schema.sql"
        echo   - Deleted validation_security_schema.sql
    )
    REM Remove database directory if empty
    rd "database" 2>nul
    if not exist "database\" (
        echo   - Removed empty database directory
    )
)

REM 3. Remove legacy assets directory (frontend has its own)
echo.
echo [3/6] Removing legacy assets directory...
if exist "assets\" (
    rd /S /Q "assets"
    echo   - Deleted entire assets directory
)

REM 4. Remove old documentation files (keeping essential ones)
echo.
echo [4/6] Removing old documentation files...
if exist "SYSTEM_ANALYSIS_RECOMMENDATIONS.md" (
    del /F /Q "SYSTEM_ANALYSIS_RECOMMENDATIONS.md"
    echo   - Deleted SYSTEM_ANALYSIS_RECOMMENDATIONS.md
)
if exist "FINAL_REVIEW_AND_FIXES.md" (
    del /F /Q "FINAL_REVIEW_AND_FIXES.md"
    echo   - Deleted FINAL_REVIEW_AND_FIXES.md
)
if exist "ERROR_FIX_SUMMARY.md" (
    del /F /Q "ERROR_FIX_SUMMARY.md"
    echo   - Deleted ERROR_FIX_SUMMARY.md
)

REM 5. Remove .htaccess (not needed for Laravel backend)
echo.
echo [5/6] Removing .htaccess file...
if exist ".htaccess" (
    del /F /Q ".htaccess"
    echo   - Deleted .htaccess
)

REM 6. Clean up empty directories
echo.
echo [6/6] Cleaning up empty directories...
for /d %%i in (*) do (
    rd "%%i" 2>nul
    if not exist "%%i\" (
        echo   - Removed empty directory: %%i
    )
)

echo.
echo ========================================
echo CLEANUP COMPLETE!
echo ========================================
echo.
echo The following files/folders remain:
echo - backend/        (Laravel application)
echo - frontend/       (Next.js application)
echo - deploy.bat      (Deployment script)
echo - deploy.sh       (Unix deployment script)
echo - verify-system.bat (System verification)
echo - README.md       (Main documentation)
echo - PRODUCTION_READY_SUMMARY.md (Production guide)
echo - DEPLOYMENT_CHECKLIST.md (Deployment steps)
echo - .gitignore      (Git configuration)
echo.

REM Create cleanup log
echo Cleanup completed on %date% at %time% > cleanup-log.txt
echo All unused files have been removed. >> cleanup-log.txt
echo See cleanup-log.txt for details.

pause
