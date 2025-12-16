# Envisage E-Commerce Platform

A comprehensive multi-vendor marketplace platform built with Laravel 8 and Next.js 14.

## 🚀 Quick Start

### Prerequisites
- WAMP Server (PHP 7.4+, MySQL 8.0+)
- Node.js 16+
- Composer

### Installation

```bash
# 1. Clone repository
git clone <repository-url>
cd Envisage

# 2. Backend setup
cd backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate

# 3. Frontend setup
cd ../frontend
npm install

# 4. Start servers
# Terminal 1 - Backend
cd backend
php artisan serve

# Terminal 2 - Frontend  
cd frontend
npm run dev
```

### Access
- **Frontend**: http://localhost:3000
- **Backend API**: http://127.0.0.1:8000

## 📚 Documentation

- **[DOCUMENTATION.md](DOCUMENTATION.md)** - Complete system documentation
- **[API_ENDPOINTS.md](API_ENDPOINTS.md)** - API reference
- **[DEPLOYMENT_READY.md](DEPLOYMENT_READY.md)** - Deployment guide
- **[TESTING_GUIDE.md](TESTING_GUIDE.md)** - Testing procedures

## ✨ Features

### Core Platform (10 features)
- User authentication & authorization
- Product catalog with categories
- Shopping cart & checkout
- Order management
- Payment processing
- Admin & seller dashboards
- Email notifications
- Role-based permissions
- Image uploads
- Real-time notifications

### Phase 1 (5 features)
- Product reviews & ratings
- Wishlists
- Recently viewed products
- Express checkout
- PWA support

### Phase 2 (10 features)
- Stock alerts
- Buy Now Pay Later (BNPL)
- Product bundles
- Flash sales
- Seller analytics & forecasting
- Video reviews
- Advanced search with filters
- Gift cards
- Pre-orders
- Auctions

**Total: 25+ Features**

## 🛠 Tech Stack

**Backend**: Laravel 8, MySQL 8, Sanctum  
**Frontend**: Next.js 14, TypeScript, Tailwind CSS  
**Charts**: Chart.js, react-chartjs-2  
**Icons**: Heroicons  
**Animations**: Framer Motion

## 📊 Statistics

- **API Endpoints**: 400+
- **Database Tables**: 113+
- **Lines of Code**: 35,000+
- **Components**: 50+
- **Pages**: 20+

## 🎯 Key Pages

- `/` - Homepage
- `/search` - Advanced search
- `/products/[id]` - Product details
- `/cart` - Shopping cart
- `/checkout` - Checkout
- `/dashboard` - User dashboard
- `/seller/analytics` - Seller analytics
- `/orders` - Order history
- `/wishlists` - Saved products

## 🧪 Testing

```bash
# Frontend
cd frontend
npm run dev    # Development server
npm run build  # Production build
npx tsc --noEmit  # Type checking

# Backend
cd backend
php artisan test  # Run tests
php artisan route:list  # List routes
```

## 🚢 Deployment

See [DEPLOYMENT_READY.md](DEPLOYMENT_READY.md) for complete deployment instructions.

### Quick Deploy
1. Set `APP_ENV=production` in backend .env
2. Run `php artisan config:cache`
3. Run `npm run build` in frontend
4. Configure web server (Apache/Nginx)
5. Set up SSL certificate

## 🔒 Security

- CSRF protection
- XSS prevention
- SQL injection protection
- Password hashing
- Token authentication
- Input validation
- File upload validation

## 📝 License

All rights reserved © 2025 Envisage E-Commerce Platform

## 🤝 Support

For issues or questions, check [DOCUMENTATION.md](DOCUMENTATION.md) or review error logs:
- Backend: `backend/storage/logs/laravel.log`
- Frontend: Browser console (F12)

---

**Status**: 🟢 Production Ready  
**Version**: 2.0  
**Last Updated**: December 16, 2025
