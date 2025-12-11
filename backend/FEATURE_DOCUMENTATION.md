# E-Commerce Platform - Complete Feature Documentation

## Overview
This is a fully-featured Laravel 8 e-commerce backend API with dynamic admin configuration, payment integration, comprehensive testing, and SEO optimization.

## Table of Contents
1. [Features](#features)
2. [Installation](#installation)
3. [Admin Settings Management](#admin-settings-management)
4. [Testing Suite](#testing-suite)
5. [API Documentation](#api-documentation)
6. [SEO Features](#seo-features)
7. [Payment Integration](#payment-integration)
8. [Email Configuration](#email-configuration)

---

## Features

### ✅ Core Features
- **User Authentication**: Register, login, logout with Laravel Sanctum
- **Product Management**: CRUD operations for products with image upload
- **Shopping Cart**: Add, update, remove items from cart
- **Order Management**: Create orders, view order history, track status
- **Payment Integration**: Stripe payment gateway with webhooks
- **Email Notifications**: Order confirmations, password resets
- **Admin Panel**: Manage products, orders, users, and system settings
- **Security**: XSS protection, CSRF, rate limiting, input validation

### ✅ Advanced Features
- **Dynamic Admin Settings**: Configure email, payment, SEO without editing code
- **SEO Optimization**: Meta tags, sitemap.xml, robots.txt, structured data
- **Comprehensive Testing**: PHPUnit tests for all major features
- **Image Upload System**: Secure file uploads with validation
- **Search & Filter**: Product search with category filters
- **Role-Based Access**: Admin, seller, and user roles

---

## Installation

### Prerequisites
- PHP 7.4+
- MySQL 5.7+
- Composer
- WAMP/XAMPP/LAMP server

### Setup Steps

1. **Clone the repository**
```bash
cd c:\wamp64\www\envisage\backend
```

2. **Install dependencies**
```bash
composer install
```

3. **Configure environment**
```bash
cp .env.example .env
php artisan key:generate
```

4. **Update .env file**
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=envisage_db
DB_USERNAME=root
DB_PASSWORD=

STRIPE_SECRET=your_stripe_secret_key
STRIPE_PUBLIC_KEY=your_stripe_public_key
STRIPE_WEBHOOK_SECRET=your_webhook_secret

MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
```

5. **Run migrations**
```bash
php artisan migrate
```

6. **Create storage link**
```bash
php artisan storage:link
```

7. **Initialize default settings**
```bash
# Start the server first
php artisan serve

# Then make a POST request to:
POST http://localhost:8000/api/admin/settings/initialize
Authorization: Bearer {admin_token}
```

---

## Admin Settings Management

### Overview
The settings system allows admins to configure email, payment, SEO, and general settings dynamically through the API without editing environment files.

### Settings Groups

#### 1. **Email Settings**
- `mail_driver`: SMTP, sendmail, mailgun, etc.
- `mail_host`: SMTP server host
- `mail_port`: SMTP port (587, 465, etc.)
- `mail_username`: SMTP username
- `mail_password`: SMTP password (encrypted)
- `mail_encryption`: TLS or SSL
- `mail_from_address`: Default sender email
- `mail_from_name`: Default sender name

#### 2. **Payment Settings**
- `stripe_secret_key`: Stripe secret API key (private)
- `stripe_public_key`: Stripe publishable key (public)
- `stripe_webhook_secret`: Stripe webhook signing secret
- `payment_currency`: Default currency (USD, EUR, etc.)

#### 3. **SEO Settings**
- `site_name`: Website name (public)
- `site_description`: Meta description (public)
- `site_keywords`: Meta keywords (public)
- `meta_image`: Default OG image URL (public)

#### 4. **General Settings**
- `site_logo`: Logo URL
- `frontend_url`: Frontend application URL
- `enable_registration`: Allow new user registration
- `maintenance_mode`: Enable/disable maintenance mode

### API Endpoints

#### Get All Settings (Admin)
```http
GET /api/admin/settings
GET /api/admin/settings?group=email
Authorization: Bearer {admin_token}
```

#### Get Public Settings (No Auth Required)
```http
GET /api/settings/public
```

Returns only settings marked as `is_public=true` (e.g., site_name, payment_currency).

#### Update Single Setting
```http
PUT /api/admin/settings/{key}
Authorization: Bearer {admin_token}
Content-Type: application/json

{
  "value": "new_value"
}
```

#### Update Multiple Settings
```http
POST /api/admin/settings/batch
Authorization: Bearer {admin_token}
Content-Type: application/json

{
  "settings": [
    {"key": "mail_host", "value": "smtp.gmail.com"},
    {"key": "mail_port", "value": "587"},
    {"key": "site_name", "value": "My Store"}
  ]
}
```

#### Delete Setting
```http
DELETE /api/admin/settings/{key}
Authorization: Bearer {admin_token}
```

#### Initialize Default Settings
```http
POST /api/admin/settings/initialize
Authorization: Bearer {admin_token}
```

Creates 20 default settings across all groups.

### Usage Examples

**Example 1: Update Email Settings**
```bash
curl -X POST http://localhost:8000/api/admin/settings/batch \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "settings": [
      {"key": "mail_host", "value": "smtp.gmail.com"},
      {"key": "mail_port", "value": "587"},
      {"key": "mail_username", "value": "your-email@gmail.com"},
      {"key": "mail_password", "value": "your-app-password"},
      {"key": "mail_encryption", "value": "tls"}
    ]
  }'
```

**Example 2: Update Stripe Keys**
```bash
curl -X PUT http://localhost:8000/api/admin/settings/stripe_secret_key \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"value": "sk_live_YOUR_KEY"}'
```

### How Dynamic Config Works

1. **DynamicConfigServiceProvider** loads settings on application boot
2. Settings override `.env` values if they exist in database
3. If database is unavailable, falls back to `.env` values
4. Settings are cached for 1 hour to improve performance
5. Cache clears automatically when settings are updated

---

## Testing Suite

### Overview
Comprehensive PHPUnit test suite covering authentication, products, payments, cart, and orders.

### Running Tests

**Run all tests:**
```bash
php artisan test
```

**Run specific test file:**
```bash
php artisan test --filter AuthTest
php artisan test --filter ProductTest
php artisan test --filter PaymentTest
php artisan test --filter CartTest
php artisan test --filter OrderTest
```

**Run with coverage:**
```bash
php artisan test --coverage
```

### Test Coverage

#### 1. **AuthTest** (6 tests)
- User registration
- Registration validation
- User login
- Login with wrong credentials
- User logout
- Get authenticated user profile

#### 2. **ProductTest** (7 tests)
- List all products
- Admin can create product
- Regular user cannot create product
- Admin can update product
- Admin can delete product
- Product search
- Seller can view own products

#### 3. **PaymentTest** (8 tests)
- Create payment intent
- Confirm payment
- Payment requires authentication
- Admin can refund payment
- Regular user cannot refund
- Webhook handles payment succeeded
- Payment intent requires order_id
- User can only pay for own orders

#### 4. **CartTest** (9 tests)
- Add product to cart
- Update cart quantity
- Remove product from cart
- View cart
- Clear cart
- Cannot add out of stock product
- Cannot add quantity exceeding stock
- Cart requires authentication

#### 5. **OrderTest** (11 tests)
- Create order from cart
- View own orders
- View order details
- Cannot view others' orders
- Admin can update order status
- Regular user cannot update status
- Cannot create order with empty cart
- Order reduces product stock
- Admin can view all orders
- User can cancel pending order
- Cannot cancel shipped order

**Total: 41 comprehensive tests**

### Test Database
Tests use `RefreshDatabase` trait to ensure a clean database state for each test. Configure test database in `phpunit.xml`:

```xml
<env name="DB_DATABASE" value="envisage_test"/>
```

---

## API Documentation

### Authentication

#### Register
```http
POST /api/register
Content-Type: application/json

{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "password123",
  "password_confirmation": "password123"
}
```

#### Login
```http
POST /api/login
Content-Type: application/json

{
  "email": "john@example.com",
  "password": "password123"
}
```

#### Logout
```http
POST /api/logout
Authorization: Bearer {token}
```

### Products

#### List Products
```http
GET /api/products
GET /api/products?search=widget&category=electronics&sort=price_asc
```

#### Create Product (Admin)
```http
POST /api/admin/products
Authorization: Bearer {admin_token}
Content-Type: multipart/form-data

name: Product Name
description: Product description
price: 99.99
stock: 100
category: electronics
image: (file upload)
```

#### Update Product (Admin)
```http
PUT /api/admin/products/{id}
Authorization: Bearer {admin_token}
Content-Type: application/json

{
  "name": "Updated Name",
  "price": 149.99
}
```

#### Delete Product (Admin)
```http
DELETE /api/admin/products/{id}
Authorization: Bearer {admin_token}
```

### Cart

#### View Cart
```http
GET /api/cart
Authorization: Bearer {token}
```

#### Add to Cart
```http
POST /api/cart/add
Authorization: Bearer {token}
Content-Type: application/json

{
  "product_id": 1,
  "quantity": 2
}
```

#### Update Cart
```http
PUT /api/cart/update
Authorization: Bearer {token}
Content-Type: application/json

{
  "product_id": 1,
  "quantity": 5
}
```

#### Remove from Cart
```http
DELETE /api/cart/remove/{product_id}
Authorization: Bearer {token}
```

### Orders

#### Create Order
```http
POST /api/orders
Authorization: Bearer {token}
Content-Type: application/json

{
  "shipping_address": "123 Main St, City, Country",
  "payment_method": "stripe"
}
```

#### View Orders
```http
GET /api/orders
Authorization: Bearer {token}
```

#### View Order Details
```http
GET /api/orders/{id}
Authorization: Bearer {token}
```

### Payment

#### Create Payment Intent
```http
POST /api/payment/intent
Authorization: Bearer {token}
Content-Type: application/json

{
  "order_id": 1
}
```

#### Confirm Payment
```http
POST /api/payment/confirm
Authorization: Bearer {token}
Content-Type: application/json

{
  "order_id": 1,
  "payment_intent_id": "pi_xxx"
}
```

---

## SEO Features

### Meta Tags

#### Get Meta Tags
```http
GET /api/meta?type=home
GET /api/meta?type=product&id=1
GET /api/meta?type=category&slug=electronics
```

**Response:**
```json
{
  "meta_tags": [
    {"property": "og:title", "content": "Product Name"},
    {"property": "og:description", "content": "Description"},
    {"property": "og:image", "content": "https://..."},
    {"name": "twitter:card", "content": "summary_large_image"}
  ]
}
```

### Sitemap

#### Get XML Sitemap
```http
GET /api/sitemap.xml
```

Returns XML sitemap with all active products.

### Robots.txt

#### Get robots.txt
```http
GET /api/robots.txt
```

```
User-agent: *
Allow: /
Sitemap: http://your-frontend-url/api/sitemap.xml
```

### Structured Data

#### Get JSON-LD Structured Data
```http
GET /api/structured-data?type=product&id=1
GET /api/structured-data?type=website
```

**Product Response:**
```json
{
  "@context": "https://schema.org/",
  "@type": "Product",
  "name": "Product Name",
  "description": "Description",
  "image": "https://...",
  "offers": {
    "@type": "Offer",
    "price": "99.99",
    "priceCurrency": "USD"
  }
}
```

---

## Payment Integration

### Stripe Setup

1. **Get Stripe API Keys**
   - Visit https://dashboard.stripe.com/apikeys
   - Copy Secret Key and Publishable Key

2. **Configure via Admin Settings**
```bash
POST /api/admin/settings/batch
{
  "settings": [
    {"key": "stripe_secret_key", "value": "sk_test_xxx"},
    {"key": "stripe_public_key", "value": "pk_test_xxx"},
    {"key": "stripe_webhook_secret", "value": "whsec_xxx"}
  ]
}
```

3. **Set Up Webhook**
   - URL: `https://your-domain.com/api/webhook/stripe`
   - Events: `payment_intent.succeeded`, `payment_intent.payment_failed`

### Payment Flow

1. User creates order: `POST /api/orders`
2. Frontend requests payment intent: `POST /api/payment/intent`
3. Frontend collects card details with Stripe.js
4. Stripe confirms payment
5. Webhook updates order status to "paid"
6. Email confirmation sent to user

---

## Email Configuration

### Using Gmail SMTP

Update settings via API:
```json
{
  "settings": [
    {"key": "mail_driver", "value": "smtp"},
    {"key": "mail_host", "value": "smtp.gmail.com"},
    {"key": "mail_port", "value": "587"},
    {"key": "mail_username", "value": "your-email@gmail.com"},
    {"key": "mail_password", "value": "your-app-password"},
    {"key": "mail_encryption", "value": "tls"},
    {"key": "mail_from_address", "value": "noreply@yourstore.com"},
    {"key": "mail_from_name", "value": "Your Store"}
  ]
}
```

### Email Templates

- **Order Confirmation**: Sent when order is created
- **Payment Confirmation**: Sent when payment succeeds
- **Password Reset**: Sent when user requests password reset

---

## Security Features

1. **Authentication**: Laravel Sanctum token-based auth
2. **Authorization**: Role-based access (admin, seller, user)
3. **Input Validation**: All requests validated
4. **XSS Protection**: Input sanitization
5. **CSRF Protection**: Built-in Laravel protection
6. **Rate Limiting**: API throttling enabled
7. **Password Hashing**: Bcrypt hashing
8. **Secure File Uploads**: Validation and sanitization

---

## Performance Optimization

### Caching
- Settings cached for 1 hour
- Cache::remember used for frequently accessed data
- Clear settings cache: Automatic on update

### Database Optimization
- Indexed columns: product name, category, status
- Eager loading relationships to avoid N+1 queries
- Pagination on all list endpoints

---

## Troubleshooting

### Common Issues

**1. Database Connection Error**
- Ensure MySQL is running
- Check .env database credentials
- Run `php artisan config:clear`

**2. Stripe Error**
- Verify Stripe keys in settings
- Check webhook secret is correct
- Test mode vs live mode keys

**3. Email Not Sending**
- Check mail settings in database
- Test SMTP credentials
- Check mail logs: `storage/logs/laravel.log`

**4. Test Failures**
- Run `php artisan config:clear`
- Check database is empty: `php artisan migrate:fresh`
- Ensure .env.testing is configured

---

## API Postman Collection

Import the collection from `docs/postman_collection.json` to test all endpoints.

---

## License

MIT License

---

## Support

For issues or questions:
- Email: support@yourstore.com
- Documentation: https://docs.yourstore.com
