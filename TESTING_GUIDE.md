# Envisage Admin API Testing Guide

**Date:** December 3, 2024  
**Testing Tool:** Thunder Client / Postman  
**Base URL:** `http://localhost:8000/api`

---

## 🔧 Setup Instructions

### 1. Start Laravel Backend
```bash
cd C:\wamp64\www\Envisage\backend
php artisan serve
```

### 2. Get Admin Token

**Endpoint:** `POST /api/login`

**Request Body:**
```json
{
  "email": "admin@envisage.com",
  "password": "admin123"
}
```

**Response:**
```json
{
  "success": true,
  "token": "1|xxxxxxxxxxxxxxxxxxx",
  "user": {
    "id": 1,
    "name": "Admin User",
    "email": "admin@envisage.com",
    "role": "admin"
  }
}
```

**Copy the token** - you'll use it in all subsequent requests.

---

## 📋 Thunder Client / Postman Collection

### Collection: Envisage Admin API

#### **Environment Variables**
Create these variables in your testing tool:

```json
{
  "base_url": "http://localhost:8000/api",
  "admin_token": "PASTE_YOUR_TOKEN_HERE"
}
```

---

## 🧪 Test Cases

### Test 1: Get Analytics (7 days)

**Method:** GET  
**URL:** `{{base_url}}/admin/analytics?range=7d`  
**Headers:**
```
Authorization: Bearer {{admin_token}}
Accept: application/json
```

**Expected Response:** 200 OK
```json
{
  "success": true,
  "data": {
    "revenue": {
      "today": 0,
      "week": 0,
      "month": 0,
      "change_percentage": 0
    },
    "orders": {...},
    "users": {...},
    "products": {...},
    "subscriptions": {...},
    "loyalty": {...},
    "flash_sales": {...},
    "top_products": [],
    "top_sellers": [],
    "revenue_chart": [...]
  }
}
```

**Test Variations:**
- `?range=7d` - Last 7 days
- `?range=30d` - Last 30 days (default)
- `?range=90d` - Last 90 days

---

### Test 2: Get Analytics (30 days)

**Method:** GET  
**URL:** `{{base_url}}/admin/analytics?range=30d`  
**Headers:**
```
Authorization: Bearer {{admin_token}}
Accept: application/json
```

---

### Test 3: Export Analytics CSV

**Method:** GET  
**URL:** `{{base_url}}/admin/analytics/export?range=30d`  
**Headers:**
```
Authorization: Bearer {{admin_token}}
Accept: application/json
```

**Expected Response:** 200 OK with CSV file download
**File Name:** `analytics-30d-2024-12-03.csv`

---

### Test 4: Get All Disputes

**Method:** GET  
**URL:** `{{base_url}}/admin/disputes`  
**Headers:**
```
Authorization: Bearer {{admin_token}}
Accept: application/json
```

**Expected Response:** 200 OK
```json
{
  "success": true,
  "data": []
}
```

---

### Test 5: Get Pending Disputes

**Method:** GET  
**URL:** `{{base_url}}/admin/disputes?status=pending`  
**Headers:**
```
Authorization: Bearer {{admin_token}}
Accept: application/json
```

---

### Test 6: Search Disputes

**Method:** GET  
**URL:** `{{base_url}}/admin/disputes?search=john&status=all&type=all`  
**Headers:**
```
Authorization: Bearer {{admin_token}}
Accept: application/json
```

---

### Test 7: Update Dispute Status (Approve)

**Method:** PUT  
**URL:** `{{base_url}}/admin/disputes/1/update`  
**Headers:**
```
Authorization: Bearer {{admin_token}}
Content-Type: application/json
Accept: application/json
```
**Body:**
```json
{
  "status": "approved",
  "admin_response": "We have reviewed your case and approved your refund request. You will receive the refund within 5-7 business days."
}
```

**Expected Response:** 200 OK
```json
{
  "success": true,
  "data": {
    "id": 1,
    "status": "approved",
    "admin_response": "We have reviewed your case...",
    ...
  },
  "message": "Dispute updated successfully"
}
```

---

### Test 8: Update Dispute Status (Reject)

**Method:** PUT  
**URL:** `{{base_url}}/admin/disputes/1/update`  
**Headers:**
```
Authorization: Bearer {{admin_token}}
Content-Type: application/json
```
**Body:**
```json
{
  "status": "rejected",
  "admin_response": "After careful review, we cannot approve this request due to policy violations."
}
```

---

### Test 9: Get All Flash Sales

**Method:** GET  
**URL:** `{{base_url}}/admin/flash-sales`  
**Headers:**
```
Authorization: Bearer {{admin_token}}
Accept: application/json
```

**Expected Response:** 200 OK
```json
{
  "success": true,
  "data": []
}
```

---

### Test 10: Get All Subscription Plans

**Method:** GET  
**URL:** `{{base_url}}/admin/subscription-plans`  
**Headers:**
```
Authorization: Bearer {{admin_token}}
Accept: application/json
```

---

### Test 11: Create Subscription Plan

**Method:** POST  
**URL:** `{{base_url}}/admin/subscription-plans`  
**Headers:**
```
Authorization: Bearer {{admin_token}}
Content-Type: application/json
Accept: application/json
```
**Body:**
```json
{
  "name": "Starter",
  "slug": "starter",
  "description": "Perfect for new sellers getting started",
  "monthly_price": 9.99,
  "yearly_price": 99.99,
  "features": [
    "Up to 25 products",
    "Email support",
    "Basic analytics",
    "Mobile app access"
  ],
  "max_products": 25,
  "max_featured_products": 1,
  "commission_rate": 15,
  "is_popular": false,
  "is_active": true
}
```

**Expected Response:** 201 Created
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "Starter",
    "slug": "starter",
    ...
  },
  "message": "Subscription plan created successfully"
}
```

---

### Test 12: Create Professional Plan

**Method:** POST  
**URL:** `{{base_url}}/admin/subscription-plans`  
**Body:**
```json
{
  "name": "Professional",
  "slug": "professional",
  "description": "For growing businesses",
  "monthly_price": 29.99,
  "yearly_price": 299.99,
  "features": [
    "Unlimited products",
    "Priority support",
    "Advanced analytics",
    "Custom branding",
    "API access"
  ],
  "max_products": null,
  "max_featured_products": 5,
  "commission_rate": 10,
  "is_popular": true,
  "is_active": true
}
```

---

### Test 13: Update Subscription Plan

**Method:** PUT  
**URL:** `{{base_url}}/admin/subscription-plans/1`  
**Headers:**
```
Authorization: Bearer {{admin_token}}
Content-Type: application/json
```
**Body:**
```json
{
  "name": "Starter Plus",
  "slug": "starter-plus",
  "description": "Enhanced starter plan",
  "monthly_price": 14.99,
  "yearly_price": 149.99,
  "features": [
    "Up to 50 products",
    "Priority email support",
    "Advanced analytics",
    "Mobile app access"
  ],
  "max_products": 50,
  "max_featured_products": 2,
  "commission_rate": 12,
  "is_popular": false,
  "is_active": true
}
```

---

### Test 14: Delete Subscription Plan

**Method:** DELETE  
**URL:** `{{base_url}}/admin/subscription-plans/1`  
**Headers:**
```
Authorization: Bearer {{admin_token}}
Accept: application/json
```

**Expected Response:** 200 OK
```json
{
  "success": true,
  "message": "Subscription plan deleted successfully"
}
```

**Error Case (Active Subscriptions):** 400 Bad Request
```json
{
  "success": false,
  "message": "Cannot delete plan with active subscriptions"
}
```

---

## ❌ Error Test Cases

### Test E1: Unauthorized Access (No Token)

**Method:** GET  
**URL:** `{{base_url}}/admin/analytics`  
**Headers:**
```
Accept: application/json
```

**Expected Response:** 401 Unauthorized
```json
{
  "success": false,
  "message": "Unauthenticated"
}
```

---

### Test E2: Non-Admin Access

**Method:** GET  
**URL:** `{{base_url}}/admin/analytics`  
**Headers:**
```
Authorization: Bearer {buyer_or_seller_token}
Accept: application/json
```

**Expected Response:** 403 Forbidden
```json
{
  "success": false,
  "message": "Unauthorized. Admin access required."
}
```

---

### Test E3: Invalid Dispute Status

**Method:** PUT  
**URL:** `{{base_url}}/admin/disputes/1/update`  
**Body:**
```json
{
  "status": "invalid_status",
  "admin_response": "Test"
}
```

**Expected Response:** 422 Validation Error
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "status": [
      "The selected status is invalid."
    ]
  }
}
```

---

### Test E4: Missing Required Fields

**Method:** POST  
**URL:** `{{base_url}}/admin/subscription-plans`  
**Body:**
```json
{
  "name": "Test Plan"
}
```

**Expected Response:** 422 Validation Error
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "slug": ["The slug field is required."],
    "monthly_price": ["The monthly price field is required."],
    "features": ["The features field is required."],
    ...
  }
}
```

---

### Test E5: Duplicate Slug

**Method:** POST  
**URL:** `{{base_url}}/admin/subscription-plans`  
**Body:**
```json
{
  "name": "Another Plan",
  "slug": "starter",
  "monthly_price": 19.99,
  "features": ["Feature 1"],
  "max_featured_products": 1,
  "commission_rate": 10
}
```

**Expected Response:** 422 Validation Error
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "slug": ["The slug has already been taken."]
  }
}
```

---

## 📊 Testing Checklist

### Analytics Endpoints
- [ ] GET /admin/analytics (7d, 30d, 90d)
- [ ] GET /admin/analytics/export
- [ ] Verify revenue calculations
- [ ] Verify change percentages
- [ ] Verify top products list
- [ ] Verify top sellers list
- [ ] Verify revenue chart data

### Dispute Endpoints
- [ ] GET /admin/disputes (all)
- [ ] GET /admin/disputes?status=pending
- [ ] GET /admin/disputes?status=approved
- [ ] GET /admin/disputes?type=refund
- [ ] GET /admin/disputes?search=customer
- [ ] PUT /admin/disputes/{id}/update (approve)
- [ ] PUT /admin/disputes/{id}/update (reject)
- [ ] PUT /admin/disputes/{id}/update (escalate)
- [ ] PUT /admin/disputes/{id}/update (resolve)

### Flash Sale Endpoints
- [ ] GET /admin/flash-sales
- [ ] Verify products relationship loaded
- [ ] Verify sale status calculation

### Subscription Plan Endpoints
- [ ] GET /admin/subscription-plans
- [ ] POST /admin/subscription-plans (valid data)
- [ ] POST /admin/subscription-plans (invalid data)
- [ ] POST /admin/subscription-plans (duplicate slug)
- [ ] PUT /admin/subscription-plans/{id} (valid data)
- [ ] DELETE /admin/subscription-plans/{id} (no active subs)
- [ ] DELETE /admin/subscription-plans/{id} (has active subs)

### Security Tests
- [ ] Request without token (401)
- [ ] Request with buyer token (403)
- [ ] Request with seller token (403)
- [ ] Request with admin token (200)

### Validation Tests
- [ ] Invalid status values
- [ ] Missing required fields
- [ ] Negative prices
- [ ] Commission rate > 100
- [ ] Duplicate slugs

---

## 🚀 Quick Start Commands

### Using cURL

**1. Get Analytics:**
```bash
curl -X GET "http://localhost:8000/api/admin/analytics?range=30d" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

**2. Create Plan:**
```bash
curl -X POST "http://localhost:8000/api/admin/subscription-plans" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "name": "Basic",
    "slug": "basic",
    "monthly_price": 9.99,
    "yearly_price": 99.99,
    "features": ["10 products"],
    "max_products": 10,
    "max_featured_products": 1,
    "commission_rate": 15,
    "is_active": true
  }'
```

---

## 📝 Expected Database State

Before testing, ensure you have:

### Users Table
- At least 1 admin user with `role = 'admin'`

### Orders Table  
- Sample orders with `payment_status = 'paid'`

### Products Table
- Sample products with various prices

### Optional (for complete testing):
- Sample disputes in `disputes` table
- Sample flash sales in `flash_sales` table
- Sample subscription plans in `subscription_plans` table
- Sample subscriptions in `subscriptions` table

---

## 🎯 Success Criteria

All tests pass if:
- ✅ All endpoints return proper status codes
- ✅ Response format matches documentation
- ✅ Validation errors are clear and helpful
- ✅ Authentication/authorization works correctly
- ✅ Data relationships load properly
- ✅ Calculations are accurate
- ✅ CSV export downloads correctly

---

## 📧 Contact for Issues

If any tests fail:
1. Check Laravel logs: `storage/logs/laravel.log`
2. Verify database migrations ran
3. Confirm models exist and have relationships
4. Check middleware configuration
5. Verify user has admin role

---

**Last Updated:** December 3, 2024  
**Status:** Ready for testing  
**Endpoints:** 9 total
