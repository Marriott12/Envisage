# Envisage Marketplace API Documentation

Base URL: `http://localhost/envisage/backend/public/api`

## Authentication

All protected routes require a Bearer token in the Authorization header:
```
Authorization: Bearer {token}
```

---

## Public Endpoints

### Health Check
- **GET** `/test`
- Returns API status

### Authentication

#### Register
- **POST** `/register`
- Body: `{ name, email, password, password_confirmation }`
- Returns: `{ user, token }`

#### Login
- **POST** `/login`
- Body: `{ email, password }`
- Returns: `{ user, token }`

### Products

#### List Products
- **GET** `/products`
- Query params: 
  - `search` - Search in title/description
  - `category_id` - Filter by category
  - `min_price`, `max_price` - Price range
  - `sort_by` - Field to sort (price, created_at, views)
  - `sort_order` - asc/desc
  - `per_page` - Items per page (default: 15)
- Returns: Paginated products with seller info

#### View Product
- **GET** `/products/{id}`
- Returns: Product details with seller, category, and favorited status
- Increments view count

### Categories

#### List Categories
- **GET** `/categories`
- Query params:
  - `with_parents` - Include parent category
  - `with_children` - Include child categories
  - `root_only` - Only top-level categories
- Returns: All active categories with product counts

#### View Category
- **GET** `/categories/{id}`
- Returns: Category with products and subcategories

---

## Protected Endpoints (Requires Authentication)

### User Management

#### Get Profile
- **GET** `/user/profile`
- Returns: Current user's profile

#### Update Profile
- **PUT** `/user/profile`
- Body: `{ name, email, phone, address, city, country }`
- Returns: Updated user

#### Upload Avatar
- **POST** `/user/avatar`
- Body: `multipart/form-data` with `avatar` file (max 2MB, jpg/png/gif)
- Returns: Avatar URL and updated user

#### Change Password
- **PUT** `/user/password`
- Body: `{ current_password, password, password_confirmation }`
- Returns: Success message

#### Delete Account
- **DELETE** `/user/account`
- Body: `{ password }`
- Returns: Success message (soft delete)

### Favorites

#### Get Favorites
- **GET** `/favorites`
- Returns: User's favorited products with seller info

#### Add to Favorites
- **POST** `/favorites/{productId}`
- Returns: Success message

#### Remove from Favorites
- **DELETE** `/favorites/{productId}`
- Returns: Success message

#### Check if Favorited
- **GET** `/favorites/check/{productId}`
- Returns: `{ is_favorited: boolean }`

### Cart

#### Get Cart
- **GET** `/cart`
- Returns: `{ items, total, item_count }`

#### Add to Cart
- **POST** `/cart`
- Body: `{ product_id, quantity }`
- Validates stock availability
- Returns: Updated cart

#### Update Cart Item
- **PUT** `/cart/{id}`
- Body: `{ quantity }`
- Validates stock
- Returns: Updated cart item

#### Remove from Cart
- **DELETE** `/cart/{id}`
- Returns: Success message

#### Clear Cart
- **DELETE** `/cart`
- Returns: Success message

### Orders

#### Get Orders
- **GET** `/orders`
- Query params:
  - `status` - Filter by status
  - `payment_status` - Filter by payment status
  - `search` - Search order number
  - `per_page` - Items per page
- Returns: Paginated orders with items

#### View Order
- **GET** `/orders/{id}`
- Returns: Order details with items and sellers

#### Checkout
- **POST** `/checkout`
- Body:
```json
{
  "payment_method": "cash_on_delivery",
  "shipping_address": {
    "name": "John Doe",
    "phone": "+260123456789",
    "address": "123 Main St",
    "city": "Lusaka",
    "country": "Zambia"
  },
  "notes": "Please deliver after 5pm"
}
```
- Creates order from cart items
- Decrements product stock
- Clears cart
- Returns: Created order

#### Cancel Order
- **PUT** `/orders/{id}/cancel`
- Only for pending/processing orders
- Restores product stock
- Returns: Updated order

---

## Seller/Admin Endpoints (Requires role:admin,seller)

### Product Management

#### Create Product
- **POST** `/products`
- Body:
```json
{
  "title": "Product name",
  "description": "Description",
  "price": 299.99,
  "stock": 100,
  "category_id": 1,
  "images": ["url1", "url2"],
  "condition": "new",
  "brand": "Brand Name",
  "weight": 1.5,
  "dimensions": "10x20x5",
  "featured": true
}
```
- Returns: Created product

#### Update Product
- **PUT** `/products/{id}`
- Authorization: Must own product or be admin
- Body: Same as create (partial updates allowed)
- Returns: Updated product

#### Delete Product
- **DELETE** `/products/{id}`
- Authorization: Must own product or be admin
- Returns: Success message (soft delete)

#### Get My Products
- **GET** `/seller/products`
- Query params:
  - `status` - Filter by status
  - `search` - Search in title
  - `per_page` - Items per page
- Returns: Seller's products

---

## Admin Endpoints (Requires role:admin)

### User Role Management

#### Assign Role
- **POST** `/users/{userId}/assign-role`
- Body: `{ role: "seller" }`

#### Remove Role
- **POST** `/users/{userId}/remove-role`
- Body: `{ role: "seller" }`

#### Give Permission
- **POST** `/users/{userId}/give-permission`
- Body: `{ permission: "edit-products" }`

#### Revoke Permission
- **POST** `/users/{userId}/revoke-permission`
- Body: `{ permission: "edit-products" }`

### Admin Dashboard
- **GET** `/admin/overview` - Get system overview
- **GET** `/admin/users` - List all users
- **PUT** `/admin/users/{id}` - Update user

---

## Response Formats

### Success Response
```json
{
  "data": {},
  "message": "Operation successful"
}
```

### Error Response
```json
{
  "message": "Error description",
  "errors": {
    "field": ["Validation error"]
  }
}
```

### Pagination Response
```json
{
  "current_page": 1,
  "data": [],
  "first_page_url": "...",
  "from": 1,
  "last_page": 5,
  "per_page": 15,
  "to": 15,
  "total": 75
}
```

---

## Status Codes

- `200` - Success
- `201` - Created
- `400` - Bad Request
- `401` - Unauthorized
- `403` - Forbidden
- `404` - Not Found
- `422` - Validation Error
- `500` - Server Error

---

## Product Statuses

- `draft` - Not visible to buyers
- `active` - Live and available
- `out_of_stock` - No stock available
- `archived` - Hidden from listings

## Order Statuses

- `pending` - Awaiting processing
- `processing` - Being prepared
- `shipped` - In transit
- `delivered` - Completed
- `cancelled` - Cancelled

## Payment Statuses

- `pending` - Awaiting payment
- `paid` - Payment received
- `failed` - Payment failed
- `refunded` - Payment refunded
