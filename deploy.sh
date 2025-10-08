#!/bin/bash

# ========================================
# ENVISAGE MARKETPLACE - DEPLOYMENT SCRIPT
# ========================================

echo "🚀 Starting Envisage Marketplace Deployment..."

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# ========================================
# 1. BACKEND DEPLOYMENT
# ========================================

echo -e "\n${YELLOW}📦 Step 1: Backend Deployment${NC}"

cd backend || exit 1

# Install Composer dependencies
echo "Installing Composer dependencies..."
composer install --optimize-autoloader --no-dev

# Generate application key
echo "Generating application key..."
php artisan key:generate

# Run migrations
echo "Running database migrations..."
php artisan migrate --force

# Clear caches
echo "Clearing caches..."
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

# Optimize for production
echo "Optimizing for production..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Set permissions
echo "Setting file permissions..."
chmod -R 755 storage bootstrap/cache
chmod -R 775 storage
chmod -R 775 bootstrap/cache

echo -e "${GREEN}✓ Backend deployment complete!${NC}"

# ========================================
# 2. FRONTEND DEPLOYMENT  
# ========================================

echo -e "\n${YELLOW}📦 Step 2: Frontend Deployment${NC}"

cd ../frontend || exit 1

# Install npm dependencies
echo "Installing npm dependencies..."
npm install

# Build frontend
echo "Building production frontend..."
npm run build

echo -e "${GREEN}✓ Frontend deployment complete!${NC}"

# ========================================
# 3. VERIFICATION
# ========================================

echo -e "\n${YELLOW}🔍 Step 3: Deployment Verification${NC}"

# Check if .env exists
if [ ! -f "../backend/.env" ]; then
    echo -e "${RED}✗ Missing .env file in backend!${NC}"
    echo "Please copy .env.production.example to .env and configure it."
    exit 1
fi

# Check if build directory exists
if [ ! -d ".next" ]; then
    echo -e "${RED}✗ Frontend build failed!${NC}"
    exit 1
fi

echo -e "${GREEN}✓ All verifications passed!${NC}"

# ========================================
# 4. POST-DEPLOYMENT TASKS
# ========================================

echo -e "\n${YELLOW}📝 Post-Deployment Tasks:${NC}"
echo "1. Update .env with production database credentials"
echo "2. Update NEXT_PUBLIC_API_URL in frontend/.env.local"
echo "3. Run: php artisan db:seed (if needed)"
echo "4. Configure SSL certificate in cPanel"
echo "5. Set up cron jobs for Laravel scheduler"
echo "6. Test all API endpoints"
echo "7. Test frontend pages and functionality"

echo -e "\n${GREEN}🎉 Deployment Complete!${NC}"
echo "Your application is ready for production."
