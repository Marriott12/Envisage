# Frontend Enhancements - Installation & Usage Guide

## Phase 1: Completed ✅

### What Was Implemented

1. **Performance Optimizations**
   - Enhanced Next.js configuration with bundle analyzer, security headers
   - Strict TypeScript configuration with path aliases
   - Performance monitoring hooks (debounce, throttle, intersection observer)
   - Optimized image component with lazy loading

2. **State Management Enhancements**
   - Enhanced Zustand stores with Immer middleware:
     - CartStore: save-for-later, coupon application, discount calculation
     - FilterStore: multi-criteria filtering, saved presets
     - CheckoutStore: multi-step flow, saved addresses, gift options
   - All stores persist to localStorage with DevTools support

3. **Real-time Features**
   - 8 Laravel Echo/Pusher hooks:
     - `useRealtimeProduct`: live stock, price, viewer counts
     - `useRealtimeOrder`: order status, tracking updates
     - `useRealtimeNotifications`: user notifications
     - `useRealtimeCart`: cart sync across devices
     - `useRealtimeFlashSale`: countdown timers
     - `useRealtimeSocialProof`: recent purchase notifications
     - `usePresence`: online users count
     - Echo instance management

4. **Behavioral Tracking**
   - Comprehensive tracking store with hooks:
     - Page view tracking
     - Scroll depth tracking
     - Hover pattern tracking
     - Click tracking with rage-click detection
     - Cart abandonment tracking
     - Search history
     - User preferences learning
   - Auto-sync to backend every 5 minutes

5. **Loading States**
   - 12+ skeleton component variants:
     - Product cards, grids, and details
     - Orders, tables, lists
     - Dashboard cards, charts, forms
     - Categories, reviews
   - Customizable animation (pulse, wave, none)

6. **PWA Configuration**
   - Complete PWA manifest with icons and shortcuts
   - Service worker with:
     - Offline support
     - Cache strategies (network-first, cache-first)
     - Background sync for cart/orders
     - Push notifications
   - Install prompt components for web and iOS

7. **React Query Setup**
   - Optimized query client with smart caching
   - Prefetch utilities for critical data
   - Optimistic update helpers
   - Cache manipulation utilities
   - Global error handlers

8. **Error Handling**
   - Enhanced ErrorBoundary with:
     - Retry logic
     - Error counting and critical error detection
     - Backend error reporting
     - Development mode details
     - Reset functionality

## Installation Instructions

### Step 1: Install Dependencies

```powershell
cd c:\wamp64\www\Envisage\frontend
npm install
```

This will install all new packages including:
- `@next/bundle-analyzer` - Bundle size analysis
- `@tanstack/react-query` + `@tanstack/react-query-devtools` - Data fetching
- `zustand-middleware-immer` - Immutable state updates
- `@sentry/nextjs` - Error tracking
- `posthog-js` - Analytics
- `web-vitals` - Performance monitoring
- `react-intersection-observer` - Lazy loading
- Testing: Vitest, Playwright, Testing Library
- Storybook packages for component documentation

### Step 2: Environment Variables

Add to your `.env.local` file:

```env
# API Configuration
NEXT_PUBLIC_API_URL=http://localhost:8000/api
NEXT_PUBLIC_WS_URL=ws://localhost:6001

# Pusher Configuration
NEXT_PUBLIC_PUSHER_KEY=your_pusher_key
NEXT_PUBLIC_PUSHER_CLUSTER=mt1

# Analytics (Optional)
NEXT_PUBLIC_POSTHOG_KEY=your_posthog_key
NEXT_PUBLIC_SENTRY_DSN=your_sentry_dsn

# Bundle Analyzer (Development)
ANALYZE=false
```

### Step 3: Update Your Root Layout

Update `app/layout.tsx` to include the new providers:

```tsx
import { QueryProvider } from '@/lib/react-query';
import { PWAPrompt, IOSInstallPrompt } from '@/components/PWAPrompt';
import { ErrorBoundary } from '@/components/ErrorBoundary';

export default function RootLayout({ children }: { children: React.ReactNode }) {
  return (
    <html lang="en">
      <head>
        <link rel="manifest" href="/manifest.json" />
        <meta name="theme-color" content="#3b82f6" />
        <meta name="apple-mobile-web-app-capable" content="yes" />
        <meta name="apple-mobile-web-app-status-bar-style" content="default" />
      </head>
      <body>
        <ErrorBoundary>
          <QueryProvider>
            {children}
            <PWAPrompt />
            <IOSInstallPrompt />
          </QueryProvider>
        </ErrorBoundary>
      </body>
    </html>
  );
}
```

### Step 4: Replace Basic Store with Enhanced Stores

Update imports in your components from:
```tsx
import { useCartStore } from '@/lib/store';
```

To:
```tsx
import { useCartStore } from '@/lib/stores/enhanced-stores';
```

The enhanced stores have the same API plus additional features.

### Step 5: Use Real-time Hooks

Example usage in a product page:

```tsx
'use client';

import { useRealtimeProduct } from '@/hooks/useRealtime';

export default function ProductPage({ params }: { params: { id: string } }) {
  const { product, viewers, stockStatus } = useRealtimeProduct(params.id);

  return (
    <div>
      <h1>{product?.name}</h1>
      <p>Stock: {stockStatus}</p>
      <p>{viewers} people viewing this product</p>
    </div>
  );
}
```

### Step 6: Add Behavioral Tracking

Wrap pages with tracking hooks:

```tsx
'use client';

import { usePageTracking, useScrollTracking } from '@/hooks/useBehavioralTracking';

export default function ProductsPage() {
  usePageTracking('products-page');
  useScrollTracking('products-page');

  return <div>Your content</div>;
}
```

### Step 7: Use Optimized Images

Replace `next/image` imports:

```tsx
import { OptimizedImage } from '@/components/ui/OptimizedImage';

<OptimizedImage
  src="/products/product-1.jpg"
  alt="Product"
  width={400}
  height={400}
  aspectRatio="1/1"
  showSkeleton
/>
```

### Step 8: Add Loading States

Use skeleton components for loading states:

```tsx
import { ProductGridSkeleton } from '@/components/LoadingStates/Skeletons';

{isLoading ? <ProductGridSkeleton count={8} /> : <ProductGrid products={products} />}
```

### Step 9: Configure Bundle Analyzer

To analyze bundle size:

```powershell
$env:ANALYZE="true"; npm run build
```

### Step 10: Test PWA Features

1. Build for production: `npm run build`
2. Start production server: `npm start`
3. Open in browser and check:
   - Service worker registration in DevTools → Application → Service Workers
   - Install prompt after 30 seconds
   - Offline functionality (disable network in DevTools)

## Usage Examples

### Using React Query for Data Fetching

```tsx
import { useQuery } from '@tanstack/react-query';

function ProductList() {
  const { data, isLoading, error } = useQuery({
    queryKey: ['products'],
    queryFn: async () => {
      const response = await fetch(`${process.env.NEXT_PUBLIC_API_URL}/products`);
      return response.json();
    },
  });

  if (isLoading) return <ProductGridSkeleton />;
  if (error) return <div>Error loading products</div>;

  return <ProductGrid products={data} />;
}
```

### Using Performance Hooks

```tsx
import { useDebounce, useIntersectionObserver } from '@/hooks/usePerformance';

function SearchInput() {
  const [search, setSearch] = useState('');
  const debouncedSearch = useDebounce(search, 500);

  // Will only trigger after 500ms of no typing
  useEffect(() => {
    if (debouncedSearch) {
      performSearch(debouncedSearch);
    }
  }, [debouncedSearch]);

  return <input value={search} onChange={(e) => setSearch(e.target.value)} />;
}
```

### Using Enhanced Cart Store

```tsx
import { useCartStore } from '@/lib/stores/enhanced-stores';

function CartPage() {
  const { 
    items, 
    savedForLater,
    couponCode,
    discount,
    addItem,
    saveForLater,
    applyCoupon,
    getFinalPrice 
  } = useCartStore();

  return (
    <div>
      <h2>Cart ({items.length})</h2>
      <p>Subtotal: ${getFinalPrice()}</p>
      {discount > 0 && <p>Discount: -${discount}</p>}
      
      <input 
        placeholder="Coupon code"
        onBlur={(e) => applyCoupon(e.target.value)}
      />

      <h3>Saved for Later ({savedForLater.length})</h3>
    </div>
  );
}
```

### Using Behavioral Tracking

```tsx
import { useBehavioralStore } from '@/hooks/useBehavioralTracking';

function ProductCard({ product }) {
  const { trackView, trackClick } = useBehavioralStore();

  useEffect(() => {
    trackView(product.id);
  }, [product.id]);

  return (
    <div onClick={() => trackClick('product-card', 'products-page')}>
      <h3>{product.name}</h3>
    </div>
  );
}
```

## Backend Requirements

### 1. Laravel Echo Broadcasting Configuration

Ensure your Laravel backend has:

```php
// config/broadcasting.php
'connections' => [
    'pusher' => [
        'driver' => 'pusher',
        'key' => env('PUSHER_APP_KEY'),
        'secret' => env('PUSHER_APP_SECRET'),
        'app_id' => env('PUSHER_APP_ID'),
        'options' => [
            'cluster' => env('PUSHER_APP_CLUSTER'),
            'useTLS' => true,
        ],
    ],
],
```

### 2. Broadcast Events

Create events for real-time features:

```php
// app/Events/StockUpdated.php
class StockUpdated implements ShouldBroadcast
{
    public function broadcastOn()
    {
        return new Channel('product.' . $this->productId);
    }

    public function broadcastAs()
    {
        return 'StockUpdated';
    }
}
```

### 3. Analytics Endpoints

Add endpoints for behavioral data:

```php
// routes/api.php
Route::post('/analytics/behavior', [AnalyticsController::class, 'storeBehavior']);
Route::post('/analytics/performance', [AnalyticsController::class, 'storePerformance']);
Route::post('/analytics/web-vitals', [AnalyticsController::class, 'storeWebVitals']);
Route::post('/errors', [ErrorController::class, 'store']);
```

### 4. Authentication for Broadcasting

```php
// routes/channels.php
Broadcast::channel('user.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});
```

## Testing

### Run Unit Tests
```powershell
npm run test
```

### Run E2E Tests
```powershell
npx playwright test
```

### Launch Storybook
```powershell
npm run storybook
```

## Performance Monitoring

### 1. View Bundle Size
```powershell
$env:ANALYZE="true"; npm run build
```

### 2. Check Web Vitals
- Install Web Vitals extension in Chrome
- Check metrics in DevTools → Lighthouse

### 3. Monitor Cache Performance
```tsx
import { cacheUtils } from '@/lib/react-query';

console.log(cacheUtils.getCacheStats());
// { totalQueries: 15, activeQueries: 3, staleQueries: 5 }
```

## Troubleshooting

### Issue: Service Worker Not Registering
**Solution**: Ensure you're running in production mode (`npm run build && npm start`)

### Issue: Real-time Events Not Working
**Solution**: Check Pusher credentials and Laravel broadcasting configuration

### Issue: Type Errors After Adding Dependencies
**Solution**: Restart TypeScript server in VSCode (Ctrl+Shift+P → "Restart TS Server")

### Issue: Images Not Loading
**Solution**: Add image domains to `next.config.js`:
```js
images: {
  domains: ['your-domain.com'],
}
```

## Next Steps (Phase 2+)

1. **Install all dependencies**: `npm install`
2. **Test the setup**: `npm run dev`
3. **Move to Phase 2**: Install dependencies and verify everything works
4. **Continue with Phase 3**: Implement search & discovery features
5. **Set up monitoring**: Configure Sentry and PostHog
6. **Build component library**: Set up Storybook
7. **Write tests**: Add unit and E2E tests

## File Structure

```
frontend/
├── components/
│   ├── ErrorBoundary.tsx (Enhanced)
│   ├── PWAPrompt.tsx (New)
│   ├── LoadingStates/
│   │   └── Skeletons.tsx (New)
│   └── ui/
│       └── OptimizedImage.tsx (New)
├── hooks/
│   ├── useBehavioralTracking.ts (New)
│   ├── usePerformance.ts (New)
│   └── useRealtime.ts (New)
├── lib/
│   ├── react-query.tsx (New)
│   └── stores/
│       └── enhanced-stores.ts (New)
├── public/
│   ├── manifest.json (New)
│   └── sw.js (New)
├── next.config.js (Enhanced)
├── tsconfig.json (Enhanced)
└── package.json (Updated)
```

## Support & Documentation

- **Next.js Docs**: https://nextjs.org/docs
- **React Query Docs**: https://tanstack.com/query/latest
- **Zustand Docs**: https://docs.pmnd.rs/zustand
- **PWA Docs**: https://web.dev/progressive-web-apps/
- **Laravel Echo Docs**: https://laravel.com/docs/broadcasting

---

**Phase 1 Complete!** ✅ 
All core performance optimizations, state management enhancements, and PWA features are implemented.
