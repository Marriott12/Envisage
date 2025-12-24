# 🧪 API TESTING GUIDE - High Priority Features

This guide will help you test all newly implemented API endpoints for Invoice Generation, Tax Calculation, Multi-Currency, and Import/Export systems.

---

## 🔐 Prerequisites

### 1. Get Authentication Token

First, login to get your bearer token:

```bash
POST http://localhost:8000/api/login
Content-Type: application/json

{
  "email": "your@email.com",
  "password": "your_password"
}
```

**Response:**
```json
{
  "token": "1|abcd1234...",
  "user": {...}
}
```

Copy the token and use it in all subsequent requests as:
```
Authorization: Bearer YOUR_TOKEN_HERE
```

---

## 💱 1. MULTI-CURRENCY SYSTEM

### List All Currencies
```bash
GET http://localhost:8000/api/currencies
Authorization: Bearer {{token}}
```

**Expected Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "code": "USD",
      "name": "US Dollar",
      "symbol": "$",
      "rate": 1.000000,
      "is_active": true,
      "is_base": true
    },
    {
      "id": 2,
      "code": "EUR",
      "name": "Euro",
      "symbol": "€",
      "rate": 0.850000
    }
    // ... more currencies
  ]
}
```

### Convert Currency
```bash
POST http://localhost:8000/api/currencies/convert
Authorization: Bearer {{token}}
Content-Type: application/json

{
  "amount": 100,
  "from": "USD",
  "to": "EUR"
}
```

**Expected Response:**
```json
{
  "success": true,
  "data": {
    "original_amount": 100,
    "original_currency": "USD",
    "converted_amount": 85.00,
    "target_currency": "EUR",
    "exchange_rate": 0.85,
    "formatted": "€85.00"
  }
}
```

### Set User Currency Preference
```bash
PUT http://localhost:8000/api/currencies/user-preference
Authorization: Bearer {{token}}
Content-Type: application/json

{
  "currency": "EUR"
}
```

---

## 💰 2. TAX CALCULATION ENGINE

### Create Tax Rule (Admin Only)
```bash
POST http://localhost:8000/api/taxes/rules
Authorization: Bearer {{token}}
Content-Type: application/json

{
  "name": "California Sales Tax",
  "country": "US",
  "state": "CA",
  "rate": 7.25,
  "tax_type": "sales_tax",
  "is_active": true,
  "priority": 1,
  "applies_to_shipping": true
}
```

### Calculate Tax for Cart
```bash
POST http://localhost:8000/api/taxes/calculate
Authorization: Bearer {{token}}
Content-Type: application/json

{
  "country": "US",
  "state": "CA",
  "city": "Los Angeles",
  "zip_code": "90001",
  "items": [
    {
      "amount": 100.00,
      "category_id": 1,
      "is_digital": false
    },
    {
      "amount": 50.00,
      "category_id": 2,
      "is_digital": false
    }
  ],
  "shipping": 10.00
}
```

**Expected Response:**
```json
{
  "success": true,
  "data": {
    "subtotal": 150.00,
    "shipping": 10.00,
    "tax_amount": 11.60,
    "total": 171.60,
    "breakdown": [
      {
        "tax_type": "sales_tax",
        "rate": 7.25,
        "amount": 10.88,
        "applies_to": "items"
      },
      {
        "tax_type": "sales_tax",
        "rate": 7.25,
        "amount": 0.72,
        "applies_to": "shipping"
      }
    ]
  }
}
```

### Get Tax Rates
```bash
GET http://localhost:8000/api/taxes/rates?country=US&state=CA&city=Los Angeles
Authorization: Bearer {{token}}
```

### Estimate Tax
```bash
POST http://localhost:8000/api/taxes/estimate
Authorization: Bearer {{token}}
Content-Type: application/json

{
  "amount": 100,
  "country": "US",
  "state": "CA"
}
```

---

## 📄 3. INVOICE GENERATION

### Generate Invoice from Order
```bash
POST http://localhost:8000/api/invoices/generate/1
Authorization: Bearer {{token}}
Content-Type: application/json

{
  "notes": "Thank you for your business!",
  "due_days": 30
}
```

**Expected Response:**
```json
{
  "success": true,
  "message": "Invoice generated successfully",
  "data": {
    "id": 1,
    "invoice_number": "INV-20241224-0001",
    "order_id": 1,
    "user_id": 1,
    "status": "pending",
    "subtotal": 100.00,
    "tax_amount": 7.25,
    "total_amount": 107.25,
    "due_date": "2025-01-23",
    "pdf_path": "invoices/INV-20241224-0001.pdf"
  }
}
```

### List User's Invoices
```bash
GET http://localhost:8000/api/invoices
Authorization: Bearer {{token}}
```

**Query Parameters:**
- `?status=pending` - Filter by status
- `?overdue=true` - Only overdue invoices
- `?from_date=2024-01-01` - Date range start
- `?to_date=2024-12-31` - Date range end

### Download Invoice PDF
```bash
GET http://localhost:8000/api/invoices/1/download
Authorization: Bearer {{token}}
```

**Response:** PDF file download

### Email Invoice to Customer
```bash
POST http://localhost:8000/api/invoices/1/email
Authorization: Bearer {{token}}
```

### Mark Invoice as Paid
```bash
PUT http://localhost:8000/api/invoices/1/mark-paid
Authorization: Bearer {{token}}
Content-Type: application/json

{
  "payment_method": "card",
  "payment_reference": "TXN123456",
  "amount": 107.25
}
```

### Bulk Generate Invoices
```bash
POST http://localhost:8000/api/invoices/bulk-generate
Authorization: Bearer {{token}}
Content-Type: application/json

{
  "order_ids": [1, 2, 3, 4, 5],
  "due_days": 30
}
```

### Get Invoice Statistics
```bash
GET http://localhost:8000/api/invoices/stats
Authorization: Bearer {{token}}
```

**Expected Response:**
```json
{
  "success": true,
  "data": {
    "total_invoices": 25,
    "pending_amount": 5250.00,
    "paid_amount": 12300.00,
    "overdue_count": 3,
    "overdue_amount": 780.00,
    "this_month": {
      "count": 8,
      "amount": 2150.00
    }
  }
}
```

---

## 📦 4. IMPORT/EXPORT SYSTEM

### Download Product Import Template
```bash
GET http://localhost:8000/api/import/template?type=products
Authorization: Bearer {{token}}
```

**Response:** CSV file with headers:
```csv
name,description,price,stock_quantity,category_id,sku,status
```

### Validate Product Import
```bash
POST http://localhost:8000/api/import/validate
Authorization: Bearer {{token}}
Content-Type: multipart/form-data

file: [CSV FILE]
```

**Expected Response:**
```json
{
  "success": true,
  "data": {
    "valid": true,
    "total_rows": 100,
    "errors": [],
    "preview": [
      {
        "row": 1,
        "name": "Product A",
        "price": 29.99
      }
    ]
  }
}
```

### Import Products
```bash
POST http://localhost:8000/api/import/products
Authorization: Bearer {{token}}
Content-Type: multipart/form-data

file: [CSV FILE]
update_existing: true
```

**Expected Response:**
```json
{
  "success": true,
  "message": "Products imported successfully",
  "data": {
    "imported": 95,
    "updated": 3,
    "failed": 2,
    "errors": [
      {
        "row": 10,
        "error": "Duplicate SKU: ABC123"
      }
    ]
  }
}
```

### Export Products
```bash
POST http://localhost:8000/api/export/products
Authorization: Bearer {{token}}
Content-Type: application/json

{
  "category_id": 1,
  "status": "active",
  "seller_id": null
}
```

**Response:** CSV file download

### Export Orders
```bash
POST http://localhost:8000/api/export/orders
Authorization: Bearer {{token}}
Content-Type: application/json

{
  "start_date": "2024-01-01",
  "end_date": "2024-12-31",
  "status": "delivered"
}
```

**Response:** CSV file download

### Export Customers (Admin Only)
```bash
POST http://localhost:8000/api/export/customers
Authorization: Bearer {{token}}
Content-Type: application/json

{
  "registered_after": "2024-01-01",
  "has_orders": true
}
```

**Response:** CSV file download

---

## 🧪 TESTING SCENARIOS

### Complete Invoice Workflow
1. **Create an order** (use existing order API)
2. **Generate invoice** from order: `POST /api/invoices/generate/{orderId}`
3. **View invoice** details: `GET /api/invoices/{id}`
4. **Download PDF**: `GET /api/invoices/{id}/download`
5. **Email invoice**: `POST /api/invoices/{id}/email`
6. **Mark as paid**: `PUT /api/invoices/{id}/mark-paid`

### Complete Tax Workflow
1. **Create tax rules** (admin): `POST /api/taxes/rules`
2. **Calculate tax** for cart: `POST /api/taxes/calculate`
3. **Apply to checkout** process
4. **View tax breakdown** in order

### Complete Currency Workflow
1. **List currencies**: `GET /api/currencies`
2. **Set user preference**: `PUT /api/currencies/user-preference`
3. **Convert prices**: `POST /api/currencies/convert`
4. **Display in user's currency** across the app

### Complete Import/Export Workflow
1. **Download template**: `GET /api/import/template?type=products`
2. **Fill CSV** with product data
3. **Validate**: `POST /api/import/validate`
4. **Import**: `POST /api/import/products`
5. **Export for backup**: `POST /api/export/products`

---

## ⚠️ Common Errors & Solutions

### 401 Unauthorized
```json
{"message": "Unauthenticated."}
```
**Solution:** Check your Bearer token is valid and not expired

### 403 Forbidden
```json
{"message": "This action is unauthorized."}
```
**Solution:** Ensure you have the right role (admin for some endpoints)

### 422 Validation Error
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "amount": ["The amount field is required."]
  }
}
```
**Solution:** Check required fields and data types

### 404 Not Found
```json
{"message": "Invoice not found"}
```
**Solution:** Verify the resource ID exists and belongs to you

---

## 📊 Test Results Checklist

- [ ] All 10 currencies are listed
- [ ] Currency conversion works correctly
- [ ] User can set currency preference
- [ ] Tax rules can be created (admin)
- [ ] Tax calculation returns correct amounts
- [ ] Tax rates are retrieved by location
- [ ] Invoice generates from order
- [ ] PDF downloads successfully
- [ ] Invoice emails are sent
- [ ] Invoice marked as paid updates status
- [ ] Product import template downloads
- [ ] CSV validation works with errors
- [ ] Products import successfully
- [ ] Products export with filters
- [ ] Orders export with date range

---

## 🚀 Next Steps After Testing

1. **Integrate Frontend Components**
   - Currency switcher in header
   - Tax display in checkout
   - Invoice viewer/download button
   - Import/export UI for sellers

2. **Configure Environment**
   - Add exchange rate API key to `.env`
   - Configure SMTP for invoice emails
   - Set up cron for rate updates

3. **Create Sample Data**
   - Add tax rules for common jurisdictions
   - Test with real order data
   - Generate sample invoices

4. **Production Setup**
   - Test all endpoints on staging
   - Configure proper email templates
   - Set up automated backups
   - Enable rate limiting

---

**Thunder Client Collection:** `thunder-client/thunder-collection_high-priority-features.json`

**Test Script:** `backend/test-new-features.php`
