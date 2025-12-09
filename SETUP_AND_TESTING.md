# Envisage Admin Dashboard - Setup & Testing Guide

## 📋 Prerequisites
- WAMP Server running (Apache + MySQL)
- PHP 8.0+
- Composer
- Node.js 16+
- Thunder Client or Postman

---

## 🗄️ Database Setup

### Step 1: Create Local Database

Open phpMyAdmin (http://localhost/phpmyadmin) and create a new database:

```sql
CREATE DATABASE envisage_marketplace;
```

### Step 2: Update .env File

Edit `backend/.env` and update database credentials for local WAMP:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=envisage_marketplace
DB_USERNAME=root
DB_PASSWORD=
```

**Note:** Default WAMP MySQL user is `root` with empty password.

### Step 3: Run Migrations

```bash
cd C:\wamp64\www\Envisage\backend
php artisan migrate
```

This will create:
- `disputes` table
- `subscriptions` table
- `loyalty_points` table
- Update `subscription_plans` table
- Update `flash_sales` table

### Step 4: Seed Admin User

```bash
php artisan db:seed --class=AdminUserSeeder
```

This creates:
- **Admin User:** admin@envisage.com / admin123
- **Test Buyer:** buyer@envisage.com / buyer123
- **Test Seller:** seller@envisage.com / seller123

---

## 🚀 Backend Setup

### 1. Install Dependencies
```bash
cd C:\wamp64\www\Envisage\backend
composer install
```

### 2. Generate Application Key (if not set)
```bash
php artisan key:generate
```

### 3. Start Laravel Server
```bash
php artisan serve
```

Server will run on: **http://127.0.0.1:8000**

---

## 💻 Frontend Setup

### 1. Install Dependencies
```bash
cd C:\wamp64\www\Envisage\frontend
npm install
```

### 2. Configure API URL

Create/edit `frontend/.env.local`:

```env
NEXT_PUBLIC_API_URL=http://127.0.0.1:8000/api
```

### 3. Start Next.js Development Server
```bash
npm run dev
```

Frontend will run on: **http://localhost:3000**

---

## 🔑 Getting Admin Access Token

### Method 1: Using Thunder Client / Postman

**Request:**
```
POST http://127.0.0.1:8000/api/login
Content-Type: application/json

{
  "email": "admin@envisage.com",
  "password": "admin123"
}
```

**Response:**
```json
{
  "success": true,
  "token": "1|xxxxxxxxxxxxxxxxxxxxxxxx",
  "user": {
    "id": 1,
    "name": "Admin User",
    "email": "admin@envisage.com",
    "role": "admin"
  }
}
```

Copy the `token` value for subsequent API requests.

### Method 2: Using cURL

```bash
curl -X POST http://127.0.0.1:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@envisage.com","password":"admin123"}'
```

---

## 🧪 Testing Admin API Endpoints

### Environment Setup in Thunder Client

Create a new Environment with:
- **base_url:** `http://127.0.0.1:8000/api`
- **admin_token:** `<paste your token here>`

### Quick Test Checklist

#### 1. Analytics Dashboard
```
GET {{base_url}}/admin/analytics?range=30d
Authorization: Bearer {{admin_token}}
```

Expected: Revenue, orders, users, products metrics

#### 2. Get Disputes
```
GET {{base_url}}/admin/disputes
Authorization: Bearer {{admin_token}}
```

Expected: Array of disputes (initially empty)

#### 3. Get Flash Sales
```
GET {{base_url}}/admin/flash-sales
Authorization: Bearer {{admin_token}}
```

Expected: Array of flash sales

#### 4. Get Subscription Plans
```
GET {{base_url}}/admin/subscription-plans
Authorization: Bearer {{admin_token}}
```

Expected: Array of subscription plans

#### 5. Create Subscription Plan
```
POST {{base_url}}/admin/subscription-plans
Authorization: Bearer {{admin_token}}
Content-Type: application/json

{
  "name": "Starter",
  "slug": "starter",
  "description": "Perfect for new sellers",
  "monthly_price": 9.99,
  "yearly_price": 99.99,
  "features": [
    "Up to 25 products",
    "Email support",
    "Basic analytics"
  ],
  "max_products": 25,
  "max_featured_products": 1,
  "commission_rate": 15,
  "is_popular": false,
  "is_active": true
}
```

Expected: 201 Created with plan data

---

## 🌐 Testing Frontend Admin Pages

### 1. Login as Admin

Navigate to: `http://localhost:3000/login`

**Credentials:**
- Email: admin@envisage.com
- Password: admin123

### 2. Access Admin Dashboard

After login, navigate to:
- `http://localhost:3000/admin/dashboard` - Analytics
- `http://localhost:3000/admin/disputes` - Dispute management
- `http://localhost:3000/admin/flash-sales` - Flash sale creator
- `http://localhost:3000/admin/subscriptions` - Subscription editor

### Expected Behavior:
- ✅ Pages check for admin token in localStorage
- ✅ Redirect to /login if not authenticated
- ✅ Display loading spinner while checking auth
- ✅ Load respective admin components

---

## 🔐 Admin Role Assignment

### Method 1: Update Existing User via phpMyAdmin

1. Open phpMyAdmin: `http://localhost/phpmyadmin`
2. Select `envisage_marketplace` database
3. Browse `users` table
4. Find the user you want to make admin
5. Edit the row and set `role` to `admin`
6. Save

### Method 2: Using Tinker

```bash
cd C:\wamp64\www\Envisage\backend
php artisan tinker
```

```php
$user = App\Models\User::find(1); // Replace 1 with user ID
$user->role = 'admin';
$user->save();
exit
```

### Method 3: Create New Admin User

```bash
php artisan tinker
```

```php
App\Models\User::create([
    'name' => 'Your Name',
    'email' => 'your@email.com',
    'password' => Hash::make('your_password'),
    'role' => 'admin',
    'email_verified_at' => now(),
]);
exit
```

---

## ✅ End-to-End Testing Workflows

### Workflow 1: Analytics Dashboard

1. Login as admin
2. Navigate to `/admin/dashboard`
3. Verify analytics cards display:
   - Revenue (today, week, month, change %)
   - Orders (total, pending, completed)
   - Users (buyers, sellers, new users)
   - Products (active, out of stock)
   - Subscriptions (active, revenue)
   - Loyalty points (issued, redeemed)
   - Flash sales (active, revenue)
4. Check top products and sellers lists
5. Verify revenue chart displays
6. Click "Export CSV" button
7. Confirm file downloads
8. Change time range (7d, 30d, 90d)
9. Verify data refreshes

### Workflow 2: Subscription Plan Management

1. Navigate to `/admin/subscriptions`
2. Click "Add New Plan" button
3. Fill in form:
   - Name: "Professional"
   - Slug: "professional"
   - Description: "For growing businesses"
   - Monthly Price: 29.99
   - Yearly Price: 299.99
   - Features: Add 3+ features
   - Max Products: 100
   - Featured Products: 5
   - Commission Rate: 10
   - Check "Popular"
   - Check "Active"
4. Click "Create Plan"
5. Verify success message
6. Verify plan appears in list
7. Click "Edit" on the plan
8. Change monthly price to 24.99
9. Click "Update"
10. Verify changes saved
11. Click "Delete" (if no active subscriptions)
12. Confirm deletion
13. Verify plan removed

### Workflow 3: Dispute Resolution

**Setup:** First create a test dispute in database:

```sql
INSERT INTO disputes (order_id, user_id, type, status, amount, reason, description, created_at, updated_at)
VALUES (1, 2, 'refund', 'pending', 49.99, 'Product not as described', 'The item I received does not match the photos in the listing.', NOW(), NOW());
```

**Testing:**
1. Navigate to `/admin/disputes`
2. Verify dispute appears in list
3. Click on the dispute to view details
4. Review:
   - Order ID
   - Customer name
   - Dispute type
   - Amount
   - Reason and description
   - Status badge
5. Click "Update Status" button
6. Select "Approved"
7. Enter admin response:
   > "We have reviewed your case and approved your refund request. You will receive the refund within 5-7 business days to your original payment method."
8. Click "Submit Response"
9. Verify:
   - Status changes to "Approved"
   - Admin response displays
   - Success notification shows
   - Email sent to customer (check logs)

### Workflow 4: Flash Sale Creation

1. Navigate to `/admin/flash-sales`
2. Click "Create Flash Sale"
3. Fill in form:
   - Name: "Weekend Electronics Sale"
   - Description: "Up to 50% off on selected electronics"
   - Start Time: Tomorrow 9:00 AM
   - End Time: Tomorrow 9:00 PM
   - Discount: 50%
   - Select products (3-5 items)
   - Upload banner image
4. Click "Create Sale"
5. Verify:
   - Sale appears in active sales list
   - Countdown timer shows
   - Products listed correctly
6. Test editing the sale
7. Test deactivating the sale

---

## 📊 Sample Data for Testing

### Create Sample Orders

```sql
INSERT INTO orders (user_id, total_amount, status, payment_status, created_at, updated_at)
VALUES 
(2, 149.99, 'completed', 'paid', '2024-11-28', NOW()),
(3, 89.50, 'completed', 'paid', '2024-11-29', NOW()),
(2, 299.00, 'pending', 'pending', '2024-12-01', NOW());
```

### Create Sample Products

```sql
INSERT INTO products (seller_id, name, price, stock, status, created_at, updated_at)
VALUES
(3, 'Wireless Headphones', 79.99, 50, 'active', NOW(), NOW()),
(3, 'Smart Watch', 199.99, 30, 'active', NOW(), NOW()),
(3, 'Bluetooth Speaker', 49.99, 0, 'active', NOW(), NOW());
```

---

## 🐛 Troubleshooting

### Issue: Database Connection Failed

**Error:** `Access denied for user...`

**Solution:**
1. Check WAMP MySQL service is running
2. Verify database credentials in `.env`
3. Ensure database exists
4. Try default WAMP credentials (root / empty password)

### Issue: Token Invalid

**Error:** `Unauthenticated`

**Solution:**
1. Re-login to get fresh token
2. Check token is copied correctly (no spaces)
3. Ensure token includes the number prefix (e.g., `1|xxx`)
4. Verify `Authorization: Bearer {token}` header format

### Issue: Admin Page Redirects to Login

**Solution:**
1. Check localStorage has `token` and `user` keys
2. Verify user object has `role: "admin"`
3. Clear browser cache and login again
4. Check browser console for errors

### Issue: Import Errors in Next.js

**Error:** `Cannot find module '@/components/...'`

**Solution:**
1. Verify components exist in `src/components/admin/` directory
2. Check `tsconfig.json` has @ alias configured:
   ```json
   {
     "compilerOptions": {
       "paths": {
         "@/*": ["./src/*"]
       }
     }
   }
   ```
3. Or use relative imports: `../../components/admin/...`

### Issue: Migrations Already Ran

**Error:** `Base table or view already exists`

**Solution:**
```bash
php artisan migrate:fresh
php artisan db:seed --class=AdminUserSeeder
```

**⚠️ Warning:** This drops all tables and data!

---

## 📝 Environment Configuration Checklist

### Backend (.env)
- [x] DB_DATABASE set correctly
- [x] DB_USERNAME and DB_PASSWORD correct
- [x] APP_URL matches Laravel server
- [x] SANCTUM_STATEFUL_DOMAINS configured

### Frontend (.env.local)
- [x] NEXT_PUBLIC_API_URL points to Laravel server
- [x] Port doesn't conflict (3000 vs 8000)

### Database
- [x] MySQL service running
- [x] Database created
- [x] Migrations ran
- [x] Admin user seeded

### Laravel
- [x] Dependencies installed (composer install)
- [x] App key generated
- [x] Server running (php artisan serve)

### Next.js
- [x] Dependencies installed (npm install)
- [x] TypeScript configured
- [x] Server running (npm run dev)

---

## 🎯 Success Indicators

✅ **Backend Ready:**
- Migrations complete without errors
- Admin user can login successfully
- All 9 admin endpoints return 200/201/400 as expected
- API documentation accessible

✅ **Frontend Ready:**
- All 4 admin pages load without errors
- AdminLayout displays sidebar correctly
- Auth checks work (redirect when not admin)
- Components render properly

✅ **Integration Working:**
- Frontend can call backend API
- Token authentication succeeds
- CORS allows requests
- Real-time updates work (if WebSocket configured)

---

## 📧 Support

If you encounter issues:

1. Check Laravel logs: `backend/storage/logs/laravel.log`
2. Check browser console for frontend errors
3. Verify database tables exist
4. Confirm user has admin role
5. Test API endpoints with Thunder Client first

---

**Last Updated:** December 3, 2024  
**Version:** 1.0  
**Status:** Ready for local testing
