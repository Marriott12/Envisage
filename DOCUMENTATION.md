# Envisage E-Commerce Platform - System Documentation

**Version**: 2.0  
**Last Updated**: December 16, 2025  
**Status**: Production Ready

---

## Table of Contents

1. [System Overview](#system-overview)
2. [Technology Stack](#technology-stack)
3. [Features](#features)
4. [Installation](#installation)
5. [Configuration](#configuration)
6. [API Reference](#api-reference)
7. [Testing](#testing)
8. [Deployment](#deployment)
9. [Troubleshooting](#troubleshooting)

---

## System Overview

Envisage is a full-featured e-commerce marketplace platform built with Laravel 8 (backend) and Next.js 14 (frontend). The platform supports multiple vendors, advanced payment options, analytics, and 25+ enterprise features.

### Key Capabilities
- Multi-vendor marketplace
- Advanced payment systems (BNPL, Gift Cards)
- Product management (Bundles, Pre-orders, Auctions)
- Analytics and forecasting
- Video reviews and ratings
- Advanced search with filters
- Real-time notifications

---

## Technology Stack

### Backend
- **Framework**: Laravel 8.83.29
- **Database**: MySQL 8.0.31
- **Authentication**: Laravel Sanctum
- **Server**: Apache (WAMP)
- **PHP**: 7.4.33

### Frontend
- **Framework**: Next.js 14
- **Language**: TypeScript
- **Styling**: Tailwind CSS
- **Icons**: Heroicons
- **Charts**: Chart.js + react-chartjs-2
- **Animations**: Framer Motion
- **Notifications**: React Hot Toast

### Development Tools
- **Version Control**: Git
- **Package Managers**: Composer (backend), npm (frontend)
- **Build Tools**: Webpack (Next.js), Laravel Mix

---

## Features

### Phase 1 Features (5)
1. **Product Reviews** - Star ratings, text reviews, helpful votes
2. **Wishlists** - Save products, manage collections
3. **Recently Viewed** - Track browsing history
4. **Express Checkout** - One-click ordering
5. **PWA Support** - Progressive web app capabilities

### Phase 2 Features (10)
1. **Stock Alerts** - Email notifications when products are back in stock
2. **Buy Now Pay Later (BNPL)** - Flexible payment plans with installments
3. **Product Bundles** - Group products with discounts
4. **Flash Sales** - Time-limited deals with countdown timers
5. **Seller Analytics** - Revenue tracking, forecasting, top products
6. **Video Reviews** - Upload and playback video testimonials
7. **Advanced Search** - Filters, suggestions, autocomplete
8. **Gift Cards** - Purchase, redeem, check balance
9. **Pre-Orders** - Reserve products before launch
10. **Auctions** - Bidding system for products

### Core Platform Features (10+)
- User authentication (email, social login)
- Product catalog with categories
- Shopping cart and checkout
- Order management
- Payment processing
- Admin dashboard
- Seller dashboard
- Email notifications
- Role-based permissions
- API endpoints (400+)

---

## Installation

### Prerequisites
- WAMP Server (Apache, MySQL, PHP 7.4+)
- Node.js 16+ and npm
- Composer
- Git

### Quick Start

```bash
# 1. Clone the repository
git clone <repository-url>
cd Envisage

# 2. Install backend dependencies
cd backend
composer install

# 3. Configure environment
cp .env.example .env
# Edit .env with your database credentials

# 4. Generate application key
php artisan key:generate

# 5. Run migrations
php artisan migrate

# 6. Install frontend dependencies
cd ../frontend
npm install

# 7. Start servers
# Terminal 1 - Backend
cd backend
php artisan serve

# Terminal 2 - Frontend
cd frontend
npm run dev
```

### Access Points
- **Frontend**: http://localhost:3000
- **Backend API**: http://127.0.0.1:8000
- **Database**: MySQL on localhost:3306

---

## Configuration

### Environment Variables

#### Backend (.env)
```env
APP_NAME=Envisage
APP_URL=http://127.0.0.1:8000
APP_ENV=local
APP_DEBUG=true

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=envisage
DB_USERNAME=root
DB_PASSWORD=

SANCTUM_STATEFUL_DOMAINS=localhost:3000
SESSION_DOMAIN=localhost

MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
```

#### Frontend (.env.local)
```env
NEXT_PUBLIC_API_URL=http://127.0.0.1:8000
NEXT_PUBLIC_APP_URL=http://localhost:3000
```

### Database Setup

The system includes 113+ tables covering:
- User management (users, roles, permissions)
- Product catalog (products, categories, attributes)
- Orders and payments
- Reviews and ratings
- Bundles and flash sales
- Auctions and bids
- Gift cards and BNPL plans
- Analytics and tracking

---

## API Reference

### Authentication
```
POST   /api/register          - User registration
POST   /api/login             - User login
POST   /api/logout            - User logout
GET    /api/user              - Get authenticated user
```

### Products
```
GET    /api/products          - List products
GET    /api/products/{id}     - Product details
POST   /api/products          - Create product (seller)
PUT    /api/products/{id}     - Update product
DELETE /api/products/{id}     - Delete product
```

### Cart & Checkout
```
GET    /api/cart              - View cart
POST   /api/cart/add          - Add to cart
PUT    /api/cart/{id}         - Update cart item
DELETE /api/cart/{id}         - Remove from cart
POST   /api/checkout          - Process order
```

### Orders
```
GET    /api/orders            - List user orders
GET    /api/orders/{id}       - Order details
POST   /api/orders/{id}/cancel - Cancel order
```

### Reviews
```
GET    /api/products/{id}/reviews    - Get reviews
POST   /api/products/{id}/reviews    - Submit review
POST   /api/video-reviews/upload     - Upload video review
```

### BNPL
```
GET    /api/bnpl/plans        - Available payment plans
POST   /api/bnpl/orders       - Create BNPL order
GET    /api/bnpl/payments     - List payments
POST   /api/bnpl/payments/{id}/pay - Process payment
```

### Search
```
GET    /api/search                  - Search products
GET    /api/search/suggestions      - Autocomplete
GET    /api/search/filters          - Available filters
```

### Seller Analytics
```
GET    /api/seller/analytics        - Dashboard stats
GET    /api/seller/forecast         - Revenue predictions
GET    /api/seller/products/top     - Top products
```

### Pre-Orders
```
POST   /api/preorders         - Place pre-order
GET    /api/preorders         - List pre-orders
DELETE /api/preorders/{id}    - Cancel pre-order
```

### Auctions
```
GET    /api/auctions          - Active auctions
GET    /api/auctions/{id}     - Auction details
POST   /api/auctions/{id}/bid - Place bid
```

**Full API documentation**: See API_ENDPOINTS.md

---

## Testing

### Frontend Testing

```bash
cd frontend

# Run development server
npm run dev

# Build for production
npm run build

# Type checking
npx tsc --noEmit

# Linting
npm run lint
```

### Backend Testing

```bash
cd backend

# Run tests
php artisan test

# Clear caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear

# Check routes
php artisan route:list
```

### Manual Testing Checklist

- [ ] User registration and login
- [ ] Browse products and categories
- [ ] Add items to cart
- [ ] Checkout process
- [ ] Order tracking
- [ ] Product reviews (text and video)
- [ ] Wishlist management
- [ ] Search and filters
- [ ] BNPL payment flow
- [ ] Seller analytics dashboard
- [ ] Pre-order placement
- [ ] Auction bidding

**Full testing guide**: See TESTING_GUIDE.md

---

## Deployment

### Production Checklist

#### Backend
- [ ] Set `APP_ENV=production`
- [ ] Set `APP_DEBUG=false`
- [ ] Generate new `APP_KEY`
- [ ] Configure production database
- [ ] Set up email service (SMTP)
- [ ] Configure CORS for frontend domain
- [ ] Enable caching (`php artisan config:cache`)
- [ ] Optimize routes (`php artisan route:cache`)
- [ ] Run migrations (`php artisan migrate --force`)

#### Frontend
- [ ] Update `NEXT_PUBLIC_API_URL` to production API
- [ ] Build production bundle (`npm run build`)
- [ ] Configure CDN for assets (optional)
- [ ] Set up analytics (Google Analytics, etc.)
- [ ] Configure domain and SSL certificate

#### Server Requirements
- PHP 7.4+ with required extensions
- MySQL 8.0+ or MariaDB 10.3+
- Node.js 16+ (for frontend)
- Apache or Nginx
- SSL certificate
- 512MB+ RAM (minimum)

### Deployment Methods

1. **cPanel Hosting** - See DEPLOYMENT_READY.md
2. **VPS/Dedicated Server** - Use provided server-install.sh
3. **Cloud Platforms** - AWS, DigitalOcean, Heroku

**Full deployment guide**: See DEPLOYMENT_READY.md

---

## Troubleshooting

### Common Issues

#### Frontend won't start
```bash
# Clear node_modules and reinstall
rm -rf node_modules
npm install

# Clear Next.js cache
rm -rf .next
npm run dev
```

#### Backend API errors
```bash
# Clear all caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Reset database (WARNING: deletes data)
php artisan migrate:fresh
```

#### Database connection failed
- Check MySQL service is running
- Verify credentials in `.env`
- Ensure database exists (`CREATE DATABASE envisage;`)
- Check firewall rules

#### CORS errors
- Verify `SANCTUM_STATEFUL_DOMAINS` in backend .env
- Check API URL in frontend .env.local
- Clear browser cache and cookies

#### Chart.js not rendering
- Ensure chart.js and react-chartjs-2 are installed
- Check browser console for errors
- Verify Chart.js registration in component

### Getting Help

- Check error logs: `backend/storage/logs/laravel.log`
- Browser console (F12) for frontend errors
- Review API responses in Network tab
- Check database queries with Laravel Debugbar

---

## Architecture

### Database Schema
- **113+ tables** covering all features
- Indexed foreign keys for performance
- Soft deletes for data recovery
- Timestamps on all tables

### API Design
- RESTful architecture
- JSON responses
- Token-based authentication (Sanctum)
- Rate limiting ready
- Pagination on list endpoints

### Frontend Structure
```
frontend/
├── app/              # Next.js pages (App Router)
├── components/       # React components
├── hooks/            # Custom React hooks
├── lib/              # Utilities (API client, etc.)
├── public/           # Static assets
└── styles/           # Global styles
```

### Backend Structure
```
backend/
├── app/
│   ├── Http/Controllers/  # API controllers
│   ├── Models/            # Eloquent models
│   └── Services/          # Business logic
├── database/
│   └── migrations/        # Database schema
├── routes/
│   └── api.php           # API routes
└── storage/              # Logs, uploads
```

---

## Security

### Implemented Measures
- CSRF protection
- XSS prevention
- SQL injection protection (Eloquent ORM)
- Password hashing (bcrypt)
- Token-based authentication
- Input validation
- File upload validation
- Rate limiting ready
- HTTPS support

### Best Practices
- Keep dependencies updated
- Use environment variables for secrets
- Enable SSL in production
- Regular database backups
- Monitor error logs
- Implement rate limiting
- Use prepared statements

---

## Performance Optimization

### Backend
- Query optimization with eager loading
- Database indexing on foreign keys
- API response caching (Redis ready)
- Route caching in production
- Config caching in production

### Frontend
- Code splitting (automatic with Next.js)
- Image optimization (Next.js Image component)
- Lazy loading components
- CSS optimization (Tailwind purge)
- Bundle size optimization

---

## Maintenance

### Regular Tasks
- Database backups (daily recommended)
- Log rotation and cleanup
- Dependency updates
- Security patches
- Performance monitoring

### Monitoring
- Error tracking (Laravel logs)
- API response times
- Database query performance
- Server resource usage
- User analytics

---

## Support & Resources

### Documentation Files
- **README.md** - Quick start guide
- **API_ENDPOINTS.md** - Complete API reference
- **DEPLOYMENT_READY.md** - Deployment instructions
- **TESTING_GUIDE.md** - Testing procedures
- **THIS FILE** - Comprehensive system documentation

### Code Quality
- TypeScript for type safety
- ESLint for code linting
- Laravel best practices (PSR-12)
- Component-based architecture
- DRY principles

---

## Credits

**Built with:**
- Laravel 8 (Backend Framework)
- Next.js 14 (Frontend Framework)
- MySQL (Database)
- Tailwind CSS (Styling)
- Chart.js (Analytics Visualization)

**Platform Status**: 🟢 Production Ready  
**Total Features**: 25+  
**API Endpoints**: 400+  
**Database Tables**: 113+

---

**© 2025 Envisage E-Commerce Platform. All rights reserved.**
