# API Endpoints Quick Reference

## Authentication
```
POST   /api/auth/login                    # Login
POST   /api/auth/register                 # Register
POST   /api/auth/logout                   # Logout
```

## 💬 Messaging System
```
GET    /api/messages/conversations                    # List conversations
GET    /api/messages/conversations/{id}               # Get conversation
POST   /api/messages/conversations/start              # Start conversation
POST   /api/messages/conversations/{id}/messages      # Send message
POST   /api/messages/conversations/{id}/mark-read     # Mark as read
GET    /api/messages/unread-count                     # Unread count
```

## ❓ Product Q&A
```
GET    /api/products/{productId}/questions            # List questions
POST   /api/products/{productId}/questions            # Ask question
POST   /api/questions/{questionId}/answers            # Answer question
POST   /api/questions/{questionId}/upvote             # Upvote question
POST   /api/questions/answers/{answerId}/helpful      # Mark helpful
```

## 🔄 Disputes & Returns
```
POST   /api/orders/{orderId}/disputes                 # Create dispute
GET    /api/disputes                                  # List disputes
PUT    /api/disputes/{disputeId}                      # Update dispute
POST   /api/orders/{orderId}/returns                  # Create return
GET    /api/returns                                   # List returns
PUT    /api/returns/{returnId}/approve                # Approve return
PUT    /api/returns/{returnId}/tracking               # Add tracking
POST   /api/returns/{returnId}/confirm                # Confirm receipt
```

## 💳 Subscriptions
```
GET    /api/subscriptions/plans                       # List plans
GET    /api/subscriptions/current                     # Current subscription
POST   /api/subscriptions/subscribe                   # Subscribe
POST   /api/subscriptions/cancel                      # Cancel
POST   /api/subscriptions/feature-product             # Feature product
POST   /api/subscriptions/webhook                     # Stripe webhook
```

## 🎁 Loyalty & Rewards
```
GET    /api/loyalty/points                            # My points
GET    /api/loyalty/transactions                      # Transaction history
GET    /api/loyalty/rewards                           # Rewards catalog
POST   /api/loyalty/redeem                            # Redeem reward
GET    /api/loyalty/redemptions                       # My redemptions
GET    /api/loyalty/referral-code                     # Get referral code
GET    /api/loyalty/referrals                         # My referrals
POST   /api/loyalty/apply-referral                    # Apply referral
```

## ⚡ Flash Sales
```
GET    /api/flash-sales                               # List active sales
GET    /api/flash-sales/{id}                          # Sale details
POST   /api/flash-sales                               # Create sale
POST   /api/flash-sales/products/{id}/purchase        # Purchase item
GET    /api/flash-sales/my/purchases                  # My purchases
POST   /api/flash-sales/{id}/end                      # End sale
```

## 📦 Product Bundles
```
GET    /api/bundles                                   # List bundles
GET    /api/bundles/{id}                              # Bundle details
POST   /api/bundles                                   # Create bundle
PUT    /api/bundles/{id}                              # Update bundle
DELETE /api/bundles/{id}                              # Delete bundle
GET    /api/products/{id}/frequently-bought           # Recommendations
```

## 📊 Inventory Management
```
PUT    /api/inventory/products/{id}/stock             # Update stock
GET    /api/inventory/products/{id}/history           # Stock history
GET    /api/inventory/low-stock-alerts                # Get alerts
POST   /api/inventory/products/{id}/low-stock-threshold  # Set threshold
POST   /api/inventory/import                          # Import products
GET    /api/inventory/import/{id}/status              # Import status
GET    /api/inventory/export                          # Export products
POST   /api/inventory/bulk-update-prices              # Bulk price update
```

## 🛒 Abandoned Carts
```
POST   /api/abandoned-carts/track                     # Track cart
GET    /api/abandoned-carts/list                      # List carts
GET    /api/abandoned-carts/stats                     # Statistics
GET    /api/abandoned-carts/recover/{token}           # Recover cart
GET    /api/abandoned-carts/email/{id}/open           # Track open
GET    /api/abandoned-carts/email/{id}/click          # Track click
```

## Request Examples

### Start Conversation
```bash
curl -X POST https://envisagezm.com/api/messages/conversations/start \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "product_id": 123,
    "message": "Is this item still available?"
  }'
```

### Ask Question
```bash
curl -X POST https://envisagezm.com/api/products/123/questions \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "question": "What is the warranty period?"
  }'
```

### Create Dispute
```bash
curl -X POST https://envisagezm.com/api/orders/456/disputes \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: multipart/form-data" \
  -F "type=quality_issue" \
  -F "reason=Product arrived damaged" \
  -F "evidence[]=@photo1.jpg" \
  -F "evidence[]=@photo2.jpg"
```

### Subscribe to Plan
```bash
curl -X POST https://envisagezm.com/api/subscriptions/subscribe \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "plan_id": 2
  }'
```

### Redeem Reward
```bash
curl -X POST https://envisagezm.com/api/loyalty/redeem \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "reward_id": 5
  }'
```

### Purchase Flash Sale Item
```bash
curl -X POST https://envisagezm.com/api/flash-sales/products/789/purchase \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "quantity": 2
  }'
```

### Create Bundle
```bash
curl -X POST https://envisagezm.com/api/bundles \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Summer Bundle",
    "description": "Best summer products",
    "discount_type": "percentage",
    "discount_value": 20,
    "products": [
      {"product_id": 1, "quantity": 1},
      {"product_id": 2, "quantity": 1},
      {"product_id": 3, "quantity": 1}
    ]
  }'
```

### Update Inventory
```bash
curl -X PUT https://envisagezm.com/api/inventory/products/123/stock \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "quantity": 50,
    "type": "restock",
    "notes": "New shipment received"
  }'
```

### Import Products
```bash
curl -X POST https://envisagezm.com/api/inventory/import \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: multipart/form-data" \
  -F "file=@products.csv"
```

## Response Formats

### Success Response
```json
{
  "data": { ... },
  "message": "Success"
}
```

### Error Response
```json
{
  "message": "Validation failed",
  "errors": {
    "field_name": ["Error message"]
  }
}
```

### Paginated Response
```json
{
  "data": [...],
  "current_page": 1,
  "last_page": 5,
  "per_page": 20,
  "total": 100,
  "from": 1,
  "to": 20
}
```

## Status Codes

- `200` - Success
- `201` - Created
- `204` - No Content
- `400` - Bad Request
- `401` - Unauthorized
- `403` - Forbidden
- `404` - Not Found
- `422` - Validation Error
- `500` - Server Error
