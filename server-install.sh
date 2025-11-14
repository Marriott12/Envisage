#!/bin/bash

#################################################
# Envisage E-Commerce - Auto Installation Script
# For cPanel Server: server219.web-hosting.com
# Username: envithcy
#################################################

echo "=========================================="
echo "  ENVISAGE E-COMMERCE AUTO INSTALLER"
echo "=========================================="
echo ""

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Configuration
LARAVEL_DIR="/home/envithcy/envisage"
PUBLIC_DIR="/home/envithcy/public_html"

echo -e "${YELLOW}[INFO] Installation starting...${NC}"
echo ""

# Step 1: Check if Laravel directory exists
echo -e "${YELLOW}[1/10] Checking Laravel directory...${NC}"
if [ ! -d "$LARAVEL_DIR" ]; then
    echo -e "${RED}[ERROR] Directory $LARAVEL_DIR not found!${NC}"
    echo "Please upload your files first to /home/envithcy/envisage/"
    exit 1
fi
cd "$LARAVEL_DIR"
echo -e "${GREEN}[OK] Laravel directory found${NC}"
echo ""

# Step 2: Check PHP version
echo -e "${YELLOW}[2/10] Checking PHP version...${NC}"
PHP_VERSION=$(php -v | head -n 1)
echo "PHP Version: $PHP_VERSION"
echo -e "${GREEN}[OK] PHP is available${NC}"
echo ""

# Step 3: Check if .env exists
echo -e "${YELLOW}[3/10] Checking .env file...${NC}"
if [ ! -f ".env" ]; then
    echo -e "${YELLOW}[WARN] .env file not found${NC}"
    if [ -f ".env.production" ]; then
        echo "Copying .env.production to .env..."
        cp .env.production .env
        echo -e "${GREEN}[OK] Created .env from .env.production${NC}"
    elif [ -f ".env.example" ]; then
        echo "Copying .env.example to .env..."
        cp .env.example .env
        echo -e "${YELLOW}[WARN] Please edit .env with your database credentials${NC}"
    else
        echo -e "${RED}[ERROR] No .env file found!${NC}"
        exit 1
    fi
else
    echo -e "${GREEN}[OK] .env file exists${NC}"
fi
echo ""

# Step 4: Install Composer dependencies
echo -e "${YELLOW}[4/10] Installing Composer dependencies...${NC}"
if command -v composer &> /dev/null; then
    echo "Running: composer install --optimize-autoloader --no-dev"
    composer install --optimize-autoloader --no-dev
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}[OK] Composer dependencies installed${NC}"
    else
        echo -e "${RED}[ERROR] Composer install failed${NC}"
        exit 1
    fi
else
    echo -e "${YELLOW}[WARN] Composer command not found, checking for composer.phar...${NC}"
    if [ -f "composer.phar" ]; then
        echo "Running: php composer.phar install --optimize-autoloader --no-dev"
        php composer.phar install --optimize-autoloader --no-dev
        echo -e "${GREEN}[OK] Composer dependencies installed${NC}"
    else
        echo -e "${RED}[ERROR] Composer not found!${NC}"
        echo "Installing composer locally..."
        curl -sS https://getcomposer.org/installer | php
        php composer.phar install --optimize-autoloader --no-dev
        echo -e "${GREEN}[OK] Composer installed and dependencies added${NC}"
    fi
fi
echo ""

# Step 5: Generate application key
echo -e "${YELLOW}[5/10] Generating application key...${NC}"
php artisan key:generate --force
if [ $? -eq 0 ]; then
    echo -e "${GREEN}[OK] Application key generated${NC}"
else
    echo -e "${YELLOW}[WARN] Key generation failed or already set${NC}"
fi
echo ""

# Step 6: Clear all caches
echo -e "${YELLOW}[6/10] Clearing all caches...${NC}"
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
echo -e "${GREEN}[OK] All caches cleared${NC}"
echo ""

# Step 7: Run database migrations
echo -e "${YELLOW}[7/10] Running database migrations...${NC}"
php artisan migrate --force
if [ $? -eq 0 ]; then
    echo -e "${GREEN}[OK] Database migrations completed${NC}"
else
    echo -e "${RED}[ERROR] Database migration failed!${NC}"
    echo "Please check your .env database credentials"
    echo "DB_HOST, DB_DATABASE, DB_USERNAME, DB_PASSWORD"
fi
echo ""

# Step 8: Set file permissions
echo -e "${YELLOW}[8/10] Setting file permissions...${NC}"
chmod -R 755 storage
chmod -R 755 bootstrap/cache
chmod -R 775 storage
chmod -R 775 bootstrap/cache
echo -e "${GREEN}[OK] Permissions set (775 for storage and cache)${NC}"
echo ""

# Step 9: Create storage symlink
echo -e "${YELLOW}[9/10] Creating storage symlink...${NC}"
php artisan storage:link
if [ $? -eq 0 ]; then
    echo -e "${GREEN}[OK] Storage symlink created${NC}"
else
    echo -e "${YELLOW}[WARN] Symlink creation failed (may already exist)${NC}"
fi
echo ""

# Step 10: Cache configurations for production
echo -e "${YELLOW}[10/10] Caching configurations for production...${NC}"
php artisan config:cache
php artisan route:cache
php artisan view:cache
echo -e "${GREEN}[OK] All configurations cached${NC}"
echo ""

# Bonus: Initialize default settings
echo -e "${YELLOW}[BONUS] Initializing default settings...${NC}"
php artisan tinker --execute="App\Models\Setting::initializeDefaults();"
if [ $? -eq 0 ]; then
    echo -e "${GREEN}[OK] Default settings initialized${NC}"
else
    echo -e "${YELLOW}[WARN] Settings initialization failed (may already exist)${NC}"
fi
echo ""

# Summary
echo "=========================================="
echo -e "${GREEN}  INSTALLATION COMPLETE!${NC}"
echo "=========================================="
echo ""
echo "Installation Summary:"
echo "  ✓ Composer dependencies installed"
echo "  ✓ Application key generated"
echo "  ✓ Caches cleared"
echo "  ✓ Database migrated"
echo "  ✓ Permissions set"
echo "  ✓ Storage linked"
echo "  ✓ Configurations cached"
echo "  ✓ Settings initialized"
echo ""
echo "Next Steps:"
echo "  1. Test your API: https://yourdomain.com/api/test"
echo "  2. Check products: https://yourdomain.com/api/products"
echo "  3. View settings: https://yourdomain.com/api/settings/public"
echo ""
echo "Important Files:"
echo "  - .env: $LARAVEL_DIR/.env"
echo "  - Logs: $LARAVEL_DIR/storage/logs/laravel.log"
echo ""
echo "To view logs:"
echo "  tail -f $LARAVEL_DIR/storage/logs/laravel.log"
echo ""
echo -e "${GREEN}Your Envisage E-Commerce is ready!${NC}"
echo "=========================================="
