# 🚀 Quick Start: WebSocket Real-Time Features

## ⚡ 5-Minute Backend Test

### Step 1: Verify Broadcasting Configuration

```bash
cd backend
cat .env | grep BROADCAST
```

**Expected:**
```
BROADCAST_DRIVER=pusher
PUSHER_APP_ID=your_pusher_app_id
PUSHER_APP_KEY=your_pusher_app_key
PUSHER_APP_SECRET=your_pusher_app_secret
PUSHER_APP_CLUSTER=mt1
```

### Step 2: Run Test Script

```bash
php websocket-test.php
```

**You should see:**
```
✅ 5 broadcast events fired
✅ 3 notification types sent
✅ 8 private channels utilized
```

### Step 3: Check Pusher Dashboard

Visit: https://dashboard.pusher.com  
→ Select your app  
→ View "Debug Console"  
→ You should see 5 events

---

## 🎯 Frontend Integration (4-6 hours)

### Step 1: Install Dependencies (5 minutes)

```bash
cd frontend
npm install --save laravel-echo pusher-js
```

### Step 2: Create Echo Service (10 minutes)

Create `frontend/src/services/echo.js`:

```javascript
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

export default new Echo({
    broadcaster: 'pusher',
    key: process.env.REACT_APP_PUSHER_APP_KEY,
    cluster: process.env.REACT_APP_PUSHER_CLUSTER,
    forceTLS: true,
    authEndpoint: `${process.env.REACT_APP_BACKEND_URL}/api/broadcasting/auth`,
    auth: {
        headers: {
            Authorization: `Bearer ${localStorage.getItem('api_token')}`,
        },
    },
});
```

### Step 3: Add Environment Variables (2 minutes)

Create `frontend/.env`:

```env
REACT_APP_PUSHER_APP_KEY=your_pusher_app_key
REACT_APP_PUSHER_CLUSTER=mt1
REACT_APP_BACKEND_URL=http://localhost:8000
```

### Step 4: Test Connection (15 minutes)

Create `frontend/src/components/WebSocketTest.jsx`:

```javascript
import { useEffect } from 'react';
import echo from '../services/echo';

export default function WebSocketTest({ userId }) {
    useEffect(() => {
        console.log('🔌 Connecting to WebSocket...');
        
        const channel = echo.private(`ai.user.${userId}`);
        
        channel.listen('.recommendation.generated', (event) => {
            console.log('✅ Recommendation received:', event);
            alert(`${event.recommendations.length} recommendations ready!`);
        });

        return () => {
            channel.stopListening('.recommendation.generated');
            echo.leave(`ai.user.${userId}`);
        };
    }, [userId]);

    return <div>WebSocket Connected (check console)</div>;
}
```

### Step 5: Enable Debug Mode (1 minute)

In your React app's main component:

```javascript
import Pusher from 'pusher-js';

// Enable debug logging
Pusher.logToConsole = true;
```

### Step 6: Test End-to-End (30 minutes)

**Backend:**
```bash
php artisan tinker

event(new \App\Events\AI\RecommendationGenerated(
    1, 
    [['id' => 1, 'name' => 'Test Product']], 
    'neural', 
    1000
));
```

**Frontend:**
- Open browser console (F12)
- You should see: `✅ Recommendation received: {...}`
- Alert popup should appear

---

## 📱 Real-World Usage Examples

### 1. Live Recommendations (Simplest)

```javascript
import { useEffect, useState } from 'react';
import echo from '../services/echo';

function RecommendationsPanel({ userId }) {
    const [products, setProducts] = useState([]);
    const [loading, setLoading] = useState(false);

    useEffect(() => {
        const channel = echo.private(`ai.user.${userId}`);
        
        channel.listen('.recommendation.generated', (event) => {
            setProducts(event.recommendations);
            setLoading(false);
        });

        return () => echo.leave(`ai.user.${userId}`);
    }, [userId]);

    const handleGenerate = async () => {
        setLoading(true);
        await fetch('/api/ai/recommendations', {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('api_token')}`,
            },
        });
        // Result will arrive via WebSocket
    };

    return (
        <div>
            <button onClick={handleGenerate} disabled={loading}>
                {loading ? 'Generating...' : 'Get Recommendations'}
            </button>
            {products.map(p => <ProductCard key={p.id} product={p} />)}
        </div>
    );
}
```

### 2. Fraud Alert Toast Notifications

```javascript
import { useEffect } from 'react';
import { toast } from 'react-toastify';
import echo from '../services/echo';

function FraudMonitor({ isAdmin }) {
    useEffect(() => {
        if (!isAdmin) return;

        const channel = echo.private('ai.fraud.admin');
        
        channel.listen('.fraud.alert.created', (event) => {
            const severity = event.risk_level === 'critical' ? 'error' : 'warning';
            
            toast[severity](
                `🚨 Fraud Alert: Transaction #${event.transaction_id} - Risk: ${event.risk_score}%`,
                {
                    autoClose: false,
                    onClick: () => window.location.href = `/admin/fraud/${event.transaction_id}`,
                }
            );
        });

        return () => echo.leave('ai.fraud.admin');
    }, [isAdmin]);

    return null; // Background component
}
```

### 3. Live Chatbot

```javascript
import { useState, useEffect } from 'react';
import echo from '../services/echo';

function Chatbot({ conversationId }) {
    const [messages, setMessages] = useState([]);
    const [input, setInput] = useState('');
    const [typing, setTyping] = useState(false);

    useEffect(() => {
        const channel = echo.private(`ai.chat.${conversationId}`);
        
        channel.listen('.chatbot.response.ready', (event) => {
            setMessages(prev => [...prev, {
                role: 'assistant',
                content: event.message,
            }]);
            setTyping(false);
        });

        return () => echo.leave(`ai.chat.${conversationId}`);
    }, [conversationId]);

    const send = async () => {
        setMessages(prev => [...prev, { role: 'user', content: input }]);
        setInput('');
        setTyping(true);
        
        await fetch(`/api/ai/chat/${conversationId}`, {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('api_token')}`,
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ message: input }),
        });
    };

    return (
        <div className="chatbot">
            {messages.map((m, i) => <div key={i}>{m.content}</div>)}
            {typing && <div>AI is typing...</div>}
            <input value={input} onChange={e => setInput(e.target.value)} />
            <button onClick={send}>Send</button>
        </div>
    );
}
```

---

## 🐛 Common Issues & Fixes

### Issue: "Auth failed" in console

**Fix:**
```javascript
// Ensure token is fresh
const token = localStorage.getItem('api_token');
if (!token) {
    alert('Please login first');
}

// Check token is included in headers
const echo = new Echo({
    // ... other config
    auth: {
        headers: {
            Authorization: `Bearer ${token}`,
            Accept: 'application/json',
        },
    },
});
```

### Issue: Events not arriving

**Checklist:**
1. ✅ Pusher credentials match in backend `.env` and frontend `.env`
2. ✅ `BROADCAST_DRIVER=pusher` in backend `.env`
3. ✅ User is authenticated (has valid token)
4. ✅ Channel name is correct (check `routes/channels.php`)
5. ✅ User has permission to access channel

**Debug:**
```javascript
Pusher.logToConsole = true;

echo.connector.pusher.connection.bind('connected', () => {
    console.log('✅ Connected to Pusher');
});

echo.connector.pusher.connection.bind('error', (err) => {
    console.error('❌ Connection error:', err);
});
```

### Issue: "Cannot find module 'laravel-echo'"

**Fix:**
```bash
npm install --save laravel-echo pusher-js
```

---

## 📊 Testing Checklist

### Backend Tests
- [ ] Broadcasting driver configured (`BROADCAST_DRIVER=pusher`)
- [ ] Pusher credentials set in `.env`
- [ ] Run `php websocket-test.php` successfully
- [ ] Events visible in Pusher dashboard
- [ ] Notifications created in database

### Frontend Tests
- [ ] Laravel Echo installed (`npm list laravel-echo`)
- [ ] Pusher JS installed (`npm list pusher-js`)
- [ ] Environment variables configured
- [ ] Echo service created and imported
- [ ] Debug mode enabled (`Pusher.logToConsole = true`)
- [ ] Connection established (check console)
- [ ] Channel subscription successful
- [ ] Events received in browser console

### End-to-End Test
- [ ] User authenticated with valid token
- [ ] Frontend connected to WebSocket
- [ ] Backend event fired (`php websocket-test.php`)
- [ ] Event received in frontend (console log)
- [ ] UI updated with event data

---

## 🎯 Production Deployment

### Option 1: Pusher (Recommended)

**Pros:**
- ✅ 5-minute setup
- ✅ No server management
- ✅ Auto-scaling
- ✅ Built-in analytics

**Setup:**
1. Sign up: https://pusher.com
2. Create app → Get credentials
3. Update `.env` files (backend + frontend)
4. Deploy!

**Cost:** Free tier: 200k messages/day, $49/month unlimited

### Option 2: Self-Hosted Echo Server

**Pros:**
- ✅ Full control
- ✅ No per-message costs
- ✅ Private infrastructure

**Setup:**
```bash
npm install -g laravel-echo-server
cd backend
laravel-echo-server init
laravel-echo-server start

# Use Supervisor for production
```

**Cost:** Server costs only (~$10-20/month VPS)

---

## 📚 Resources

### Documentation
- [Full WebSocket Guide](WEBSOCKET_REAL_TIME_IMPLEMENTATION.md) (800+ lines)
- [Enterprise Implementation](ENTERPRISE_IMPLEMENTATION_COMPLETE.md)
- [Quick Reference](QUICK_REFERENCE_ENTERPRISE.md)

### External Links
- [Laravel Broadcasting Docs](https://laravel.com/docs/9.x/broadcasting)
- [Laravel Echo Docs](https://laravel.com/docs/9.x/broadcasting#client-side-installation)
- [Pusher Docs](https://pusher.com/docs)
- [Echo Server GitHub](https://github.com/tlaverdure/laravel-echo-server)

---

## ✅ Success Criteria

**You'll know it's working when:**

1. ✅ Backend test script shows "✅ 5 broadcast events fired"
2. ✅ Pusher dashboard shows events in debug console
3. ✅ Frontend console shows "✅ Connected to Pusher"
4. ✅ Events arrive in browser console in real-time
5. ✅ UI updates automatically without page refresh

**Expected Timeline:**
- Backend testing: 5 minutes
- Frontend setup: 30 minutes
- Integration & testing: 3 hours
- Production deployment: 1 hour

**Total: 4-6 hours** for complete integration

---

🎉 **That's it! You now have real-time AI features!** 🎉

---

*Quick Start Guide - December 24, 2025*
