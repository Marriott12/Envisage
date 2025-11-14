#!/bin/bash

# Production Setup Script for Envisage Marketplace
# Run this script after uploading files to cPanel

echo "================================================"
echo "   Envisage Marketplace - Production Setup"
echo "================================================"
echo ""

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Change to application directory
cd /home/envithcy/envisage || exit

echo -e "${YELLOW}Step 1: Updating .env configuration...${NC}"
if [ -f .env.production ]; then
    cp .env.production .env
    echo -e "${GREEN}✓ Production environment configured${NC}"
else
    echo -e "${RED}✗ .env.production not found${NC}"
fi

echo ""
echo -e "${YELLOW}Step 2: Setting permissions...${NC}"
chmod -R 755 storage bootstrap/cache
chmod -R 777 storage/logs
echo -e "${GREEN}✓ Permissions set${NC}"

echo ""
echo -e "${YELLOW}Step 3: Clearing caches...${NC}"
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
echo -e "${GREEN}✓ Caches cleared${NC}"

echo ""
echo -e "${YELLOW}Step 4: Running database migrations...${NC}"
php artisan migrate --force
echo -e "${GREEN}✓ Database migrated${NC}"

echo ""
echo -e "${YELLOW}Step 5: Seeding marketplace data...${NC}"
php artisan db:seed --class=CompleteMarketplaceSeeder --force
echo -e "${GREEN}✓ Marketplace data seeded${NC}"

echo ""
echo -e "${YELLOW}Step 6: Linking storage...${NC}"
php artisan storage:link
echo -e "${GREEN}✓ Storage linked${NC}"

echo ""
echo -e "${YELLOW}Step 7: Optimizing application...${NC}"
php artisan config:cache
php artisan route:cache
php artisan view:cache
echo -e "${GREEN}✓ Application optimized${NC}"

echo ""
echo -e "${YELLOW}Step 8: Creating storage directories...${NC}"
mkdir -p storage/app/public/products
mkdir -p storage/app/public/users
mkdir -p storage/app/public/categories
chmod -R 775 storage/app/public
echo -e "${GREEN}✓ Storage directories created${NC}"

echo ""
echo "================================================"
echo -e "${GREEN}   Setup Complete! 🎉${NC}"
echo "================================================"
echo ""
echo "Your marketplace is ready at: https://envisagezm.com"
echo ""
echo "Default Credentials:"
echo "  Admin:  admin@envisagezm.com / Admin@2025"
echo "  Seller: techstore@envisagezm.com / Seller@2025"
echo ""
echo "Next Steps:"
echo "  1. Update email settings in .env"
echo "  2. Add Stripe keys for payments"
echo "  3. Setup cron job for scheduled tasks"
echo "  4. Deploy frontend"
echo ""
echo "Cron Job Command:"
echo "  * * * * * cd /home/envithcy/envisage && php artisan schedule:run >> /dev/null 2>&1"
echo ""
echo "================================================"
