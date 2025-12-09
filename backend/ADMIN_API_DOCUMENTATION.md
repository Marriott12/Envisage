# Admin API Endpoints Documentation

**Base URL:** `http://localhost:8000/api/admin`  
**Authentication:** Bearer Token (Sanctum)  
**Authorization:** Admin role required  
**Date Created:** December 2024

---

## 🔐 Authentication

All admin endpoints require:
1. Valid Sanctum authentication token in `Authorization` header
2. User role must be `admin`

### Headers
```http
Authorization: Bearer {token}
Accept: application/json
Content-Type: application/json
```

### Unauthorized Response
```json
{
  "success": false,
  "message": "Unauthorized. Admin access required."
}
```

---

## 📊 Analytics Endpoints

### 1. Get Analytics Data

**Endpoint:** `GET /admin/analytics`

**Query Parameters:**
- `range` (optional): Time range - `7d`, `30d`, or `90d` (default: `30d`)

**Example Request:**
```http
GET /admin/analytics?range=30d
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "revenue": {
      "today": 1234.56,
      "week": 8765.43,
      "month": 34567.89,
      "change_percentage": 15.5
    },
    "orders": {
      "total": 1250,
      "pending": 45,
      "completed": 1180,
      "cancelled": 25,
      "change_percentage": 12.3
    },
    "users": {
      "total": 5678,
      "buyers": 4500,
      "sellers": 1178,
      "new_this_month": 234,
      "change_percentage": 8.7
    },
    "products": {
      "total": 8900,
      "active": 8500,
      "out_of_stock": 400,
      "change_percentage": 5.2
    },
    "subscriptions": {
      "active": 450,
      "revenue_this_month": 12345.67,
      "change_percentage": 18.9
    },
    "loyalty": {
      "total_points_issued": 123456,
      "total_points_redeemed": 45678,
      "active_members": 2340
    },
    "flash_sales": {
      "active": 3,
      "revenue_this_month": 23456.78,
      "products_sold": 567
    },
    "top_products": [
      {
        "id": 123,
        "name": "Product Name",
        "sales": 234,
        "revenue": 5678.90,
        "image": "https://..."
      }
    ],
    "top_sellers": [
      {
        "id": 45,
        "name": "Seller Name",
        "products": 56,
        "revenue": 12345.67,
        "rating": 4.8
      }
    ],
    "revenue_chart": [
      {
        "date": "2024-12-01",
        "revenue": 1234.56
      }
    ]
  }
}
```

### 2. Export Analytics CSV

**Endpoint:** `GET /admin/analytics/export`

**Query Parameters:**
- `range` (optional): Time range - `7d`, `30d`, or `90d` (default: `30d`)

**Example Request:**
```http
GET /admin/analytics/export?range=30d
Authorization: Bearer {token}
```

**Response:**
- Content-Type: `text/csv`
- File download: `analytics-30d-2024-12-03.csv`

**CSV Format:**
```csv
Metric,Value
Total Revenue,34567.89
Today Revenue,1234.56
Week Revenue,8765.43
Total Orders,1250
Pending Orders,45
Completed Orders,1180
Total Users,5678
Total Products,8900
Active Subscriptions,450
Subscription Revenue,12345.67
```

---

## ⚖️ Dispute Management Endpoints

### 3. Get All Disputes

**Endpoint:** `GET /admin/disputes`

**Query Parameters:**
- `status` (optional): Filter by status - `pending`, `approved`, `rejected`, `resolved`, `escalated`, `all`
- `type` (optional): Filter by type - `return`, `refund`, `complaint`, `quality_issue`, `not_received`, `all`
- `search` (optional): Search by order number, customer name, or email

**Example Request:**
```http
GET /admin/disputes?status=pending&search=john
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 123,
      "order": {
        "id": 456,
        "order_number": "ORD-2024-12345",
        "total_amount": 299.99
      },
      "user": {
        "id": 789,
        "name": "John Doe",
        "email": "john@example.com"
      },
      "type": "refund",
      "status": "pending",
      "amount": 299.99,
      "reason": "Product defective",
      "description": "The product arrived damaged and doesn't work as expected.",
      "evidence": "[{\"url\": \"https://...\", \"type\": \"image\"}]",
      "admin_response": null,
      "created_at": "2024-12-01T10:30:00Z",
      "updated_at": "2024-12-01T10:30:00Z"
    }
  ]
}
```

### 4. Update Dispute Status

**Endpoint:** `PUT /admin/disputes/{id}/update`

**Request Body:**
```json
{
  "status": "approved",
  "admin_response": "We have reviewed your case and approved your refund request. You will receive the refund within 5-7 business days."
}
```

**Validation Rules:**
- `status` (required): One of `pending`, `approved`, `rejected`, `resolved`, `escalated`
- `admin_response` (optional): String, admin's message to customer

**Example Request:**
```http
PUT /admin/disputes/123/update
Authorization: Bearer {token}
Content-Type: application/json

{
  "status": "approved",
  "admin_response": "Refund approved. Processing within 5-7 business days."
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 123,
    "status": "approved",
    "admin_response": "Refund approved. Processing within 5-7 business days.",
    "order": {...},
    "user": {...}
  },
  "message": "Dispute updated successfully"
}
```

**Error Response (Validation):**
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "status": ["The status field is required."]
  }
}
```

---

## ⚡ Flash Sale Management Endpoints

### 5. Get All Flash Sales

**Endpoint:** `GET /admin/flash-sales`

**Example Request:**
```http
GET /admin/flash-sales
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 12,
      "name": "Black Friday Sale",
      "description": "Biggest sale of the year!",
      "start_time": "2024-12-15T00:00:00Z",
      "end_time": "2024-12-16T23:59:59Z",
      "discount_percentage": 50,
      "is_active": true,
      "products": [
        {
          "id": 345,
          "product_id": 678,
          "product": {
            "id": 678,
            "name": "Product Name",
            "price": 99.99,
            "images": "[\"https://...\"]"
          },
          "sale_price": 49.99,
          "quantity_available": 100,
          "quantity_sold": 45,
          "per_user_limit": 2
        }
      ],
      "created_at": "2024-12-01T10:00:00Z"
    }
  ]
}
```

---

## 💳 Subscription Plan Management Endpoints

### 6. Get All Subscription Plans

**Endpoint:** `GET /admin/subscription-plans`

**Example Request:**
```http
GET /admin/subscription-plans
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Professional",
      "slug": "professional",
      "description": "Perfect for growing businesses",
      "monthly_price": 29.99,
      "yearly_price": 299.99,
      "features": [
        "Unlimited products",
        "Priority support",
        "Advanced analytics",
        "Custom branding"
      ],
      "max_products": null,
      "max_featured_products": 5,
      "commission_rate": 10,
      "is_popular": true,
      "is_active": true,
      "created_at": "2024-11-01T00:00:00Z"
    }
  ]
}
```

### 7. Create Subscription Plan

**Endpoint:** `POST /admin/subscription-plans`

**Request Body:**
```json
{
  "name": "Professional",
  "slug": "professional",
  "description": "Perfect for growing businesses",
  "monthly_price": 29.99,
  "yearly_price": 299.99,
  "features": [
    "Unlimited products",
    "Priority support",
    "Advanced analytics"
  ],
  "max_products": null,
  "max_featured_products": 5,
  "commission_rate": 10,
  "is_popular": true,
  "is_active": true
}
```

**Validation Rules:**
- `name` (required): String, max 255 characters
- `slug` (required): String, unique, used in URLs
- `description` (optional): String
- `monthly_price` (required): Numeric, min 0
- `yearly_price` (optional): Numeric, min 0
- `features` (required): Array of strings
- `max_products` (optional): Integer, min 0, null = unlimited
- `max_featured_products` (required): Integer, min 0
- `commission_rate` (required): Numeric, 0-100
- `is_popular` (optional): Boolean, default false
- `is_active` (optional): Boolean, default true

**Example Request:**
```http
POST /admin/subscription-plans
Authorization: Bearer {token}
Content-Type: application/json

{
  "name": "Enterprise",
  "slug": "enterprise",
  "monthly_price": 99.99,
  "yearly_price": 999.99,
  "features": ["Unlimited everything", "Dedicated support"],
  "max_products": null,
  "max_featured_products": 20,
  "commission_rate": 5,
  "is_popular": false,
  "is_active": true
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 4,
    "name": "Enterprise",
    "slug": "enterprise",
    "monthly_price": 99.99,
    ...
  },
  "message": "Subscription plan created successfully"
}
```

### 8. Update Subscription Plan

**Endpoint:** `PUT /admin/subscription-plans/{id}`

**Request Body:** Same as create, with same validation rules

**Note:** `slug` must be unique except for current plan being updated

**Example Request:**
```http
PUT /admin/subscription-plans/4
Authorization: Bearer {token}
Content-Type: application/json

{
  "name": "Enterprise Plus",
  "slug": "enterprise-plus",
  "monthly_price": 149.99,
  ...
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 4,
    "name": "Enterprise Plus",
    ...
  },
  "message": "Subscription plan updated successfully"
}
```

### 9. Delete Subscription Plan

**Endpoint:** `DELETE /admin/subscription-plans/{id}`

**Example Request:**
```http
DELETE /admin/subscription-plans/4
Authorization: Bearer {token}
```

**Success Response:**
```json
{
  "success": true,
  "message": "Subscription plan deleted successfully"
}
```

**Error Response (Active Subscriptions):**
```json
{
  "success": false,
  "message": "Cannot delete plan with active subscriptions"
}
```

**Note:** Plans with active subscriptions cannot be deleted. You must wait for all subscriptions to end or migrate them to another plan.

---

## 🔒 Security & Rate Limiting

### Authentication
- All endpoints require valid Sanctum token
- Token must be passed in `Authorization: Bearer {token}` header

### Authorization
- User role must be `admin`
- Checked via `role:admin` middleware
- Returns 403 Forbidden if user is not admin

### Rate Limiting
- No specific rate limit on admin routes
- General API rate limit applies: 120 requests per minute per user

---

## 📝 Error Handling

### Standard Error Response
```json
{
  "success": false,
  "message": "Error description",
  "error": "Technical details (in development only)"
}
```

### HTTP Status Codes
- `200` - Success
- `201` - Created (new resource)
- `400` - Bad Request (business logic error)
- `401` - Unauthorized (not authenticated)
- `403` - Forbidden (not admin)
- `422` - Validation Error
- `500` - Server Error

### Validation Error Response
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "field_name": [
      "Error message 1",
      "Error message 2"
    ]
  }
}
```

---

## 🧪 Testing Examples

### Using cURL

**Get Analytics:**
```bash
curl -X GET "http://localhost:8000/api/admin/analytics?range=7d" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

**Update Dispute:**
```bash
curl -X PUT "http://localhost:8000/api/admin/disputes/123/update" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "status": "approved",
    "admin_response": "Approved and processing refund"
  }'
```

**Create Subscription Plan:**
```bash
curl -X POST "http://localhost:8000/api/admin/subscription-plans" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "name": "Starter",
    "slug": "starter",
    "monthly_price": 9.99,
    "yearly_price": 99.99,
    "features": ["10 products", "Basic support"],
    "max_products": 10,
    "max_featured_products": 1,
    "commission_rate": 15,
    "is_active": true
  }'
```

### Using Postman

1. **Set Authorization:**
   - Type: Bearer Token
   - Token: Your admin user token

2. **Set Headers:**
   - `Accept: application/json`
   - `Content-Type: application/json` (for POST/PUT)

3. **Example Collection:**
   - GET Analytics
   - GET Disputes
   - PUT Update Dispute
   - GET Flash Sales
   - GET Subscription Plans
   - POST Create Plan
   - PUT Update Plan
   - DELETE Delete Plan
   - GET Export Analytics CSV

---

## 📋 Implementation Checklist

### Backend (Completed ✅)
- [x] AdminController.php created with all methods
- [x] Routes registered in routes/api.php
- [x] CheckRole middleware exists
- [x] Middleware registered in Kernel.php

### Models Required (Check Existence)
- [ ] Dispute model with relationships
- [ ] FlashSale model with products relationship
- [ ] SubscriptionPlan model
- [ ] Subscription model
- [ ] LoyaltyPoint model
- [ ] LoyaltyTransaction model

### Frontend (Completed ✅)
- [x] AdminDisputeManagement.tsx
- [x] AdminFlashSaleCreator.tsx
- [x] AdminSubscriptionEditor.tsx
- [x] AdminAnalyticsDashboard.tsx

### Next Steps
1. Verify all models exist and have correct relationships
2. Test all endpoints with Postman/Thunder Client
3. Create Next.js admin pages
4. Implement admin authentication flow
5. Add role assignment UI for users
6. Deploy and test in production

---

## 🎯 Business Logic Notes

### Dispute Workflow
1. Customer creates dispute
2. Admin receives notification
3. Admin reviews evidence and customer info
4. Admin can: approve, reject, or escalate
5. Customer receives email notification of decision
6. Approved disputes trigger refund process
7. All disputes can be marked as resolved

### Flash Sale Status Logic
- **Scheduled:** `start_time > now`
- **Active:** `start_time <= now <= end_time AND is_active = true`
- **Ended:** `end_time < now OR is_active = false`

### Subscription Plan Deletion
- Cannot delete plans with active subscriptions
- Must wait for subscriptions to expire
- Or migrate subscriptions to another plan first

### Analytics Calculations
- Revenue: Sum of paid orders only
- Change %: Compare current period vs previous equal period
- Top products/sellers: Ranked by revenue in selected time range
- Chart data: Daily revenue aggregation

---

**Last Updated:** December 3, 2024  
**API Version:** 1.0  
**Controller:** App\Http\Controllers\Api\AdminController
