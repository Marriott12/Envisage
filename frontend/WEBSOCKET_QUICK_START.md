/**
 * Quick Setup Guide for WebSocket Real-Time Features
 */

## ⚡ Quick Start (5 Minutes)

### Step 1: Environment Setup
```bash
cd frontend
cp .env.example .env.local
```

Edit `.env.local` and add:
```env
NEXT_PUBLIC_PUSHER_KEY=your_pusher_app_key
NEXT_PUBLIC_PUSHER_CLUSTER=mt1
NEXT_PUBLIC_ENABLE_WEBSOCKET=true
NEXT_PUBLIC_API_URL=http://localhost:8000/api
```

### Step 2: Start Services
```bash
# Terminal 1: Backend API
cd backend
php artisan serve

# Terminal 2: Queue Worker (Important!)
cd backend
php artisan queue:work

# Terminal 3: Frontend
cd frontend
npm run dev
```

### Step 3: Test Real-Time Features
Visit: **http://localhost:3000/demo/realtime**

You should see:
- ✅ AI Recommendations panel
- ✅ Fraud Alert dashboard
- ✅ Sentiment Analysis monitor
- ✅ A/B Test results
- ✅ Connection status indicator (green = connected)

### Step 4: Test Event Broadcasting

Backend test:
```php
// backend/websocket-test.php (already exists)
php websocket-test.php
```

Watch the frontend update in real-time! 🎉

---

## 📦 What's Included

### Custom Hooks (8)
✅ `useAIRecommendations` - Real-time product recommendations  
✅ `useFraudAlerts` - Fraud detection with audio alerts  
✅ `useSentimentUpdates` - Review sentiment analysis  
✅ `useChatbot` - Real-time AI chatbot  
✅ `useABTestResults` - A/B test results (admin)  
✅ `useAINotifications` - Budget alerts & notifications  
✅ `useConnectionStatus` - WebSocket health monitoring  

### React Components (8)
✅ `RealtimeRecommendationsPanel` - Product recommendations UI  
✅ `FraudAlertDashboard` - Fraud monitoring dashboard  
✅ `SentimentAnalysisMonitor` - Sentiment visualization  
✅ `RealtimeChatbotWidget` - Chat interface  
✅ `ABTestResultsDashboard` - A/B test results  
✅ `ConnectionStatusIndicator` - Connection status  
✅ `AINotificationsCenter` - Notification bell dropdown  

### Demo & Documentation
✅ Full demo page at `/demo/realtime`  
✅ Comprehensive integration guide (WEBSOCKET_FRONTEND_GUIDE.md)  
✅ Updated environment configuration  

---

## 🎯 Integration Examples

### Example 1: Dashboard
```tsx
import { RealtimeRecommendationsPanel } from '@/components/AIRealtimeComponents';

export default function Dashboard() {
  return <RealtimeRecommendationsPanel userId={user.id} />;
}
```

### Example 2: Product Page
```tsx
import { SentimentAnalysisMonitor } from '@/components/AIRealtimeComponents';

export default function ProductPage({ productId }) {
  return <SentimentAnalysisMonitor sellerId={seller.id} productId={productId} />;
}
```

### Example 3: Admin Panel
```tsx
import { ABTestResultsDashboard, FraudAlertDashboard } from '@/components/AIRealtimeComponents';

export default function AdminPanel() {
  return (
    <>
      <ABTestResultsDashboard isAdmin={true} />
      <FraudAlertDashboard isAdmin={true} />
    </>
  );
}
```

---

## 🔧 Troubleshooting

### Issue: Connection Status Shows Red
**Solution:**
1. Check `.env.local` has correct Pusher credentials
2. Verify backend queue worker is running
3. Check browser console for errors

### Issue: No Real-Time Updates
**Solution:**
1. Ensure `php artisan queue:work` is running
2. Test with backend script: `php websocket-test.php`
3. Check browser console for WebSocket errors

### Issue: Browser Notifications Not Working
**Solution:**
1. Allow notifications when browser prompts
2. Check browser settings → Site permissions
3. Test after user interaction (click button)

---

## 📊 Features Breakdown

| Feature | Frontend Hook | Backend Event | Status |
|---------|--------------|---------------|--------|
| AI Recommendations | useAIRecommendations | RecommendationGenerated | ✅ Ready |
| Fraud Detection | useFraudAlerts | FraudAlertCreated | ✅ Ready |
| Sentiment Analysis | useSentimentUpdates | SentimentAnalysisComplete | ✅ Ready |
| AI Chatbot | useChatbot | ChatbotResponseReady | ✅ Ready |
| A/B Testing | useABTestResults | ABTestWinnerDetermined | ✅ Ready |
| Notifications | useAINotifications | Multiple | ✅ Ready |
| Connection Monitor | useConnectionStatus | N/A | ✅ Ready |

---

## 🚀 Production Deployment

### 1. Update Environment Variables
```env
NEXT_PUBLIC_PUSHER_KEY=production_key
NEXT_PUBLIC_PUSHER_CLUSTER=us2
NEXT_PUBLIC_API_URL=https://api.yourdomain.com/api
```

### 2. Build Frontend
```bash
npm run build
npm start
```

### 3. Configure Backend Broadcasting
```php
// config/broadcasting.php
'pusher' => [
    'driver' => 'pusher',
    'key' => env('PUSHER_APP_KEY'),
    'secret' => env('PUSHER_APP_SECRET'),
    'app_id' => env('PUSHER_APP_ID'),
    'cluster' => env('PUSHER_APP_CLUSTER'),
],
```

### 4. Start Queue Worker (Production)
```bash
# Using Supervisor (recommended)
sudo supervisorctl start laravel-worker:*

# Or manually (not recommended)
php artisan queue:work --daemon
```

---

## 📚 Documentation

- **Full Integration Guide:** `WEBSOCKET_FRONTEND_GUIDE.md`
- **Backend Documentation:** `../backend/WEBSOCKET_SETUP.md`
- **Demo Page:** `/demo/realtime`

---

## ✅ Deployment Checklist

- [ ] Update `.env.local` with Pusher credentials
- [ ] Start backend API (`php artisan serve`)
- [ ] Start queue worker (`php artisan queue:work`)
- [ ] Start frontend (`npm run dev`)
- [ ] Test demo page (`/demo/realtime`)
- [ ] Test all 8 real-time features
- [ ] Allow browser notifications
- [ ] Verify connection status (green indicator)
- [ ] Test event broadcasting (`php websocket-test.php`)
- [ ] Review integration guide

---

## 🎉 You're All Set!

Your Envisage AI Platform now has **fully functional real-time features** powered by WebSocket!

**Next Steps:**
1. Visit `/demo/realtime` to see everything in action
2. Integrate components into your existing pages
3. Customize styling to match your brand
4. Deploy to production

**Need Help?** Check `WEBSOCKET_FRONTEND_GUIDE.md` for detailed documentation.

Happy coding! 🚀
