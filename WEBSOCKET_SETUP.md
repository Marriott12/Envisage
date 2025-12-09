# WebSocket Configuration Guide for Envisage Marketplace

## Overview
This guide provides instructions for setting up real-time WebSocket functionality for the messaging system using Laravel Broadcasting and Pusher (or Laravel WebSockets).

## Configuration Options

### Option 1: Using Pusher (Recommended for Production)

#### Step 1: Sign up for Pusher
1. Go to https://pusher.com and create a free account
2. Create a new Channels app
3. Note your app credentials: App ID, Key, Secret, and Cluster

#### Step 2: Install Pusher PHP SDK
```bash
composer require pusher/pusher-php-server
```

#### Step 3: Configure Environment Variables
Add to your `.env` file:
```env
BROADCAST_DRIVER=pusher

PUSHER_APP_ID=your_app_id
PUSHER_APP_KEY=your_app_key
PUSHER_APP_SECRET=your_app_secret
PUSHER_APP_CLUSTER=mt1
PUSHER_HOST=
PUSHER_PORT=443
PUSHER_SCHEME=https
```

#### Step 4: Uncomment Broadcasting Service Provider
In `config/app.php`, uncomment:
```php
App\Providers\BroadcastServiceProvider::class,
```

#### Step 5: Frontend Configuration
Install Laravel Echo and Pusher JS:
```bash
npm install --save laravel-echo pusher-js
```

Create `src/lib/echo.ts`:
```typescript
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

export const echo = new Echo({
    broadcaster: 'pusher',
    key: process.env.NEXT_PUBLIC_PUSHER_APP_KEY,
    cluster: process.env.NEXT_PUBLIC_PUSHER_APP_CLUSTER,
    forceTLS: true,
    authEndpoint: `${process.env.NEXT_PUBLIC_API_URL}/broadcasting/auth`,
    auth: {
        headers: {
            Authorization: `Bearer ${localStorage.getItem('api_token')}`,
        },
    },
});
```

### Option 2: Using Laravel WebSockets (Self-Hosted)

#### Step 1: Install Laravel WebSockets
```bash
composer require beyondcode/laravel-websockets
php artisan vendor:publish --provider="BeyondCode\LaravelWebSockets\WebSocketsServiceProvider" --tag="migrations"
php artisan migrate
php artisan vendor:publish --provider="BeyondCode\LaravelWebSockets\WebSocketsServiceProvider" --tag="config"
```

#### Step 2: Configure Environment Variables
Add to your `.env` file:
```env
BROADCAST_DRIVER=pusher

PUSHER_APP_ID=local
PUSHER_APP_KEY=local
PUSHER_APP_SECRET=local
PUSHER_APP_CLUSTER=mt1
PUSHER_HOST=127.0.0.1
PUSHER_PORT=6001
PUSHER_SCHEME=http
```

#### Step 3: Start WebSocket Server
```bash
php artisan websockets:serve
```

Access the WebSocket dashboard at: http://localhost:8000/laravel-websockets

#### Step 4: Frontend Configuration
Install Laravel Echo and Pusher JS:
```bash
npm install --save laravel-echo pusher-js
```

Create `src/lib/echo.ts`:
```typescript
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

export const echo = new Echo({
    broadcaster: 'pusher',
    key: 'local',
    wsHost: window.location.hostname,
    wsPort: 6001,
    forceTLS: false,
    disableStats: true,
    authEndpoint: `${process.env.NEXT_PUBLIC_API_URL}/broadcasting/auth`,
    auth: {
        headers: {
            Authorization: `Bearer ${localStorage.getItem('api_token')}`,
        },
    },
});
```

## Implementation in Components

### Updated MessageInbox Component with Real-Time

Add to `MessageInbox.tsx`:

```typescript
import { echo } from '@/lib/echo';

// Inside the component, add this useEffect after fetching conversations:
useEffect(() => {
    if (!selectedConversation || !apiToken) return;

    // Join private conversation channel
    const channel = echo.private(`conversation.${selectedConversation.id}`);

    // Listen for new messages
    channel.listen('.message.sent', (data: any) => {
        setMessages(prevMessages => [...prevMessages, data]);
        scrollToBottom();
    });

    // Listen for typing indicator
    channel.listen('.user.typing', (data: any) => {
        if (data.user_id !== userId) {
            // Show typing indicator
            console.log(`${data.user_name} is typing...`);
        }
    });

    // Cleanup on unmount or conversation change
    return () => {
        echo.leave(`conversation.${selectedConversation.id}`);
    };
}, [selectedConversation?.id]);

// Add typing indicator function
const handleTyping = () => {
    if (!selectedConversation) return;
    
    // Debounce typing events (emit max once per 2 seconds)
    // This would require a more sophisticated implementation with timers
};
```

## Events Implemented

### 1. MessageSent Event
**File:** `app/Events/MessageSent.php`
- Broadcasts when a new message is sent
- Channel: `conversation.{id}`
- Event name: `message.sent`
- Data: Complete message object with sender info

### 2. UserTyping Event
**File:** `app/Events/UserTyping.php`
- Broadcasts when a user is typing
- Channel: `conversation.{id}`
- Event name: `user.typing`
- Data: User ID and name

## Channel Authorization

**File:** `routes/channels.php`

Channels are automatically authorized based on:
- User must be either the buyer or seller in the conversation
- Authentication is required via Laravel Sanctum token

## Testing WebSocket Connection

### Backend Test
```bash
# Run queue worker (for broadcasting jobs)
php artisan queue:work

# Test broadcast
php artisan tinker
>>> $message = App\Models\Message::first();
>>> broadcast(new App\Events\MessageSent($message));
```

### Frontend Test
```javascript
// In browser console
echo.private('conversation.1')
    .listen('.message.sent', (e) => {
        console.log('New message:', e);
    });
```

## Production Deployment

### Using Pusher
1. Upgrade to paid Pusher plan for production limits
2. Update environment variables with production credentials
3. Enable TLS/SSL encryption

### Using Laravel WebSockets
1. Use a process manager like Supervisor to keep WebSocket server running:

```ini
[program:websockets]
command=php /path/to/artisan websockets:serve
numprocs=1
autostart=true
autorestart=true
user=www-data
```

2. Configure Nginx reverse proxy:
```nginx
location /ws {
    proxy_pass http://127.0.0.1:6001;
    proxy_http_version 1.1;
    proxy_set_header Upgrade $http_upgrade;
    proxy_set_header Connection "Upgrade";
    proxy_set_header Host $host;
}
```

3. Use Redis as broadcast driver for better performance:
```env
BROADCAST_DRIVER=redis
QUEUE_CONNECTION=redis
```

## Monitoring and Debugging

### Enable Debug Mode
In `.env`:
```env
APP_DEBUG=true
BROADCAST_DRIVER=log  # Logs all broadcasts instead of sending
```

### WebSocket Dashboard (Laravel WebSockets only)
Access at `/laravel-websockets` to monitor:
- Active connections
- Message statistics
- Connection errors

### Browser DevTools
- Check Network tab for WebSocket connections
- Look for `ws://` or `wss://` connections
- Monitor console for Echo connection status

## Common Issues

### Issue: "Unable to connect to WebSocket server"
**Solution:** 
- Check if WebSocket server is running (`php artisan websockets:serve`)
- Verify PUSHER_HOST and PUSHER_PORT in `.env`
- Check firewall settings

### Issue: "401 Unauthorized on broadcasting/auth"
**Solution:**
- Verify Sanctum token is being sent in headers
- Check channel authorization logic in `routes/channels.php`
- Ensure user is authenticated

### Issue: Messages not appearing in real-time
**Solution:**
- Check if queue worker is running (`php artisan queue:work`)
- Verify event is being broadcast (check logs)
- Ensure frontend is listening on correct channel

## Performance Optimization

1. **Use Redis for broadcasting:**
```env
BROADCAST_DRIVER=redis
```

2. **Implement message pagination:** Limit initial message load

3. **Debounce typing indicators:** Don't send on every keystroke

4. **Connection pooling:** Reuse WebSocket connections

5. **Message queuing:** Queue broadcast jobs for better performance

## Security Considerations

1. **Channel Authorization:** Always verify user permissions in `routes/channels.php`
2. **Rate Limiting:** Implement rate limits on broadcasting endpoints
3. **Message Validation:** Sanitize messages before broadcasting
4. **HTTPS/WSS:** Use secure connections in production
5. **Token Expiration:** Handle expired authentication tokens gracefully

## Next Steps

1. Install and configure broadcasting driver (Pusher or Laravel WebSockets)
2. Update frontend to use Echo library
3. Test real-time messaging functionality
4. Implement typing indicators
5. Add online/offline presence
6. Deploy to production with proper monitoring
