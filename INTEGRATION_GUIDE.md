# Quick Integration Guide - New Components

## How to Use New Components in Your Pages

### 1. Wishlist Features

#### Add to Product Page
```tsx
// In app/marketplace/[id]/page.tsx
import Link from 'next/link';

// Add wishlist button near "Add to Cart"
<Link 
  href="/wishlists" 
  className="bg-purple-600 text-white px-4 py-2 rounded"
>
  Add to Wishlist
</Link>
```

#### Show Price Alerts
```tsx
// In app/layout.tsx or homepage
import PriceAlertBanner from '@/components/PriceAlertBanner';

export default function Layout() {
  return (
    <>
      <PriceAlertBanner />
      {/* rest of layout */}
    </>
  );
}
```

---

### 2. Product Comparison

#### Add Compare Button to Product Cards
```tsx
// In any product listing
import CompareButton from '@/components/CompareButton';

<CompareButton productId={product.id} />
```

#### Add Compare Link to Header
```tsx
// In header/navigation
import Link from 'next/link';

<Link href="/marketplace/compare">
  Compare ({compareCount})
</Link>

// Listen for updates
useEffect(() => {
  const updateCount = () => {
    const ids = JSON.parse(localStorage.getItem('compare_products') || '[]');
    setCompareCount(ids.length);
  };
  
  window.addEventListener('compareUpdated', updateCount);
  return () => window.removeEventListener('compareUpdated', updateCount);
}, []);
```

---

### 3. Recommendations

#### Add to Homepage
```tsx
// In app/page.tsx
import RecommendationWidget from '@/components/RecommendationWidget';

export default function HomePage() {
  return (
    <>
      <RecommendationWidget 
        title="Trending Now" 
        endpoint="/recommendations/trending"
        limit={6}
      />
      
      <RecommendationWidget 
        title="New Arrivals" 
        endpoint="/recommendations/new-arrivals"
        limit={6}
      />
      
      <RecommendationWidget 
        title="Recommended for You" 
        endpoint="/recommendations"
        limit={10}
      />
    </>
  );
}
```

#### Add to Product Detail Page
```tsx
// In app/marketplace/[id]/page.tsx
import RecommendationWidget from '@/components/RecommendationWidget';

export default function ProductPage({ params }) {
  const productId = params.id;
  
  // Track view
  useEffect(() => {
    api.post(`/products/${productId}/track-view`);
  }, [productId]);
  
  return (
    <>
      {/* Product details */}
      
      <RecommendationWidget 
        title="Customers Also Viewed" 
        endpoint="/products/:productId/also-viewed"
        productId={productId}
        limit={6}
      />
      
      <RecommendationWidget 
        title="Similar Products" 
        endpoint="/products/:productId/similar"
        productId={productId}
        limit={6}
      />
      
      <RecommendationWidget 
        title="Frequently Bought Together" 
        endpoint="/products/:productId/also-bought"
        productId={productId}
        limit={6}
      />
    </>
  );
}
```

---

### 4. Reviews

#### Add to Product Page
```tsx
// Already exists, just verify it's imported
import ReviewList from '@/components/ReviewList';

<ReviewList productId={productId} />
```

---

### 5. Social Sharing

#### Add to Product Page
```tsx
import SocialShare from '@/components/SocialShare';

<SocialShare 
  url={`https://yourdomain.com/marketplace/${productId}`}
  title={product.name}
  description={product.description}
/>
```

---

### 6. Recently Viewed

#### Add to Any Page (Homepage, Cart, etc.)
```tsx
import RecentlyViewed from '@/components/RecentlyViewed';

<RecentlyViewed />
```

---

### 7. Seller Analytics Dashboard

#### Create Seller Dashboard Page
```tsx
// Create app/seller/dashboard/page.tsx
'use client';

import { useState, useEffect } from 'react';
import api from '@/lib/api';

export default function SellerDashboard() {
  const [analytics, setAnalytics] = useState(null);
  const [period, setPeriod] = useState(30);
  
  useEffect(() => {
    fetchAnalytics();
  }, [period]);
  
  const fetchAnalytics = async () => {
    const response = await api.get(`/analytics/dashboard?period=${period}`);
    setAnalytics(response.data.data);
  };
  
  if (!analytics) return <div>Loading...</div>;
  
  return (
    <div className="max-w-7xl mx-auto p-8">
      <h1 className="text-3xl font-bold mb-6">Seller Dashboard</h1>
      
      {/* Period Selector */}
      <select value={period} onChange={(e) => setPeriod(e.target.value)}>
        <option value={7}>Last 7 Days</option>
        <option value={30}>Last 30 Days</option>
        <option value={90}>Last 90 Days</option>
      </select>
      
      {/* Overview Cards */}
      <div className="grid grid-cols-3 gap-6 mt-6">
        <div className="bg-white p-6 rounded-lg shadow">
          <h3 className="text-gray-500 text-sm">Total Orders</h3>
          <p className="text-3xl font-bold">{analytics.overview.total_orders}</p>
        </div>
        <div className="bg-white p-6 rounded-lg shadow">
          <h3 className="text-gray-500 text-sm">Items Sold</h3>
          <p className="text-3xl font-bold">{analytics.overview.total_items_sold}</p>
        </div>
        <div className="bg-white p-6 rounded-lg shadow">
          <h3 className="text-gray-500 text-sm">Total Revenue</h3>
          <p className="text-3xl font-bold">${analytics.overview.total_revenue}</p>
        </div>
      </div>
      
      {/* Revenue Trend Chart - Use Chart.js or Recharts */}
      <div className="bg-white p-6 rounded-lg shadow mt-6">
        <h2 className="text-xl font-bold mb-4">Revenue Trend</h2>
        {/* Add chart component here */}
      </div>
      
      {/* Top Products */}
      <div className="bg-white p-6 rounded-lg shadow mt-6">
        <h2 className="text-xl font-bold mb-4">Top Products</h2>
        <table className="w-full">
          <thead>
            <tr className="border-b">
              <th className="text-left py-2">Product</th>
              <th className="text-right py-2">Sales</th>
              <th className="text-right py-2">Revenue</th>
            </tr>
          </thead>
          <tbody>
            {analytics.top_products.map((product) => (
              <tr key={product.id} className="border-b">
                <td className="py-2">{product.name}</td>
                <td className="text-right">{product.sales_count}</td>
                <td className="text-right">${product.revenue}</td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  );
}
```

---

### 8. Checkout Enhancements

#### Add Promo Code to Checkout Page
```tsx
// In app/checkout/page.tsx
const [promoCode, setPromoCode] = useState('');
const [discount, setDiscount] = useState(0);

const applyPromoCode = async () => {
  try {
    const response = await api.post('/checkout/validate-promo-code', {
      code: promoCode,
      order_amount: subtotal,
    });
    
    setDiscount(response.data.data.discount_amount);
    alert(response.data.message);
  } catch (error) {
    alert(error.response?.data?.message || 'Invalid promo code');
  }
};

return (
  <div className="flex gap-3 mb-4">
    <input
      type="text"
      placeholder="Enter promo code"
      value={promoCode}
      onChange={(e) => setPromoCode(e.target.value.toUpperCase())}
      className="border rounded px-3 py-2"
    />
    <button 
      onClick={applyPromoCode}
      className="bg-blue-600 text-white px-4 py-2 rounded"
    >
      Apply
    </button>
  </div>
);
```

#### Add Shipping Rate Selector
```tsx
const [shippingRates, setShippingRates] = useState([]);
const [selectedShipping, setSelectedShipping] = useState(null);

useEffect(() => {
  fetchShippingRates();
}, []);

const fetchShippingRates = async () => {
  const response = await api.post('/checkout/shipping-rates', {
    address: shippingAddress,
    cart_total: subtotal,
  });
  setShippingRates(response.data.data);
  setSelectedShipping(response.data.data[0]);
};

return (
  <div className="space-y-2">
    {shippingRates.map((rate) => (
      <label key={rate.id} className="flex items-center gap-3 p-3 border rounded">
        <input
          type="radio"
          name="shipping"
          checked={selectedShipping?.id === rate.id}
          onChange={() => setSelectedShipping(rate)}
        />
        <div className="flex-1">
          <p className="font-medium">{rate.name}</p>
          <p className="text-sm text-gray-500">{rate.description}</p>
        </div>
        <p className="font-bold">${rate.price.toFixed(2)}</p>
      </label>
    ))}
  </div>
);
```

---

## Backend Integration Examples

### Create Promo Code (Admin Panel)
```php
// POST /api/admin/promo-codes
PromoCode::create([
    'code' => 'SAVE20',
    'description' => '20% off all orders',
    'type' => 'percentage',
    'value' => 20,
    'minimum_order_amount' => 50,
    'maximum_discount' => 50,
    'usage_limit' => 100,
    'per_user_limit' => 1,
    'starts_at' => now(),
    'expires_at' => now()->addDays(30),
    'is_active' => true,
]);
```

### Track Product View
```javascript
// On product page load
useEffect(() => {
  api.post(`/products/${productId}/track-view`);
}, [productId]);
```

### Apply Promo Code to Order
```php
// In OrderController::store()
if ($request->promo_code_id) {
    $discount = app(CheckoutController::class)->applyPromoCode(
        $request->promo_code_id,
        $request->user()->id,
        $order->id,
        $order->total
    );
    
    $order->discount_amount = $discount;
    $order->total -= $discount;
    $order->save();
}
```

---

## Testing Endpoints

### Test Recommendations
```bash
# Get personalized recommendations
GET /api/recommendations?limit=10

# Get trending products
GET /api/recommendations/trending?days=7&limit=10

# Track product view
POST /api/products/123/track-view
```

### Test Analytics
```bash
# Get seller dashboard
GET /api/analytics/dashboard?period=30

# Get customer analytics
GET /api/analytics/customers?period=30

# Export data
GET /api/analytics/export?type=sales&period=30
```

### Test Wishlist
```bash
# Create wishlist
POST /api/wishlists
{
  "name": "Birthday Wishlist",
  "description": "Items I want for my birthday",
  "is_public": true
}

# Add item with price alert
POST /api/wishlists/1/items
{
  "product_id": 123,
  "priority": 2,
  "notes": "Size M, Blue color",
  "target_price": 49.99,
  "price_alert_enabled": true
}
```

### Test Promo Code
```bash
# Validate promo code
POST /api/checkout/validate-promo-code
{
  "code": "SAVE20",
  "order_amount": 100.00
}
```

---

## Environment Setup

### Install Backend Dependencies
```bash
cd backend
composer require pragmarx/google2fa-laravel
php artisan storage:link
```

### Create Storage Directories
```bash
mkdir -p storage/app/public/avatars
chmod -R 775 storage
```

### Run Migrations (Production)
```bash
php artisan migrate
php artisan config:cache
php artisan route:cache
```

---

## Quick Checklist

- [ ] Add `CompareButton` to product cards
- [ ] Add `RecommendationWidget` to homepage
- [ ] Add `PriceAlertBanner` to layout
- [ ] Add `RecentlyViewed` to homepage/cart
- [ ] Add wishlist link to navigation
- [ ] Create seller dashboard page
- [ ] Add promo code input to checkout
- [ ] Add shipping rate selector to checkout
- [ ] Test all API endpoints
- [ ] Run migrations on production
- [ ] Test frontend components
- [ ] Deploy to production

---

**All features are ready for integration!** 🎉
