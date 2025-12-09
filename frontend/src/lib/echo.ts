import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

// Make Pusher available globally for Laravel Echo
(window as any).Pusher = Pusher;

/**
 * Laravel Echo Configuration
 * 
 * This file configures the WebSocket connection for real-time features.
 * 
 * Setup Instructions:
 * 1. Install dependencies: npm install laravel-echo pusher-js
 * 2. Set environment variables in .env.local:
 *    - NEXT_PUBLIC_PUSHER_KEY (your Pusher key)
 *    - NEXT_PUBLIC_PUSHER_CLUSTER (e.g., 'us2')
 *    - NEXT_PUBLIC_API_URL (your Laravel API URL)
 * 3. Import and use: import { echo } from '@/lib/echo';
 */

const echoConfig: any = {
  broadcaster: 'pusher',
  key: process.env.NEXT_PUBLIC_PUSHER_KEY || '',
  cluster: process.env.NEXT_PUBLIC_PUSHER_CLUSTER || 'mt1',
  forceTLS: true,
  encrypted: true,
  
  // Authorization endpoint
  authEndpoint: `${process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000'}/broadcasting/auth`,
  
  // Custom authorization headers
  auth: {
    headers: {
      Accept: 'application/json',
      // Add authorization token dynamically
      // This will be set when initializing Echo with user token
    },
  },
  
  // Enable client events (for typing indicators, etc.)
  enabledTransports: ['ws', 'wss'],
};

/**
 * Initialize Echo instance
 * Call this function with the user's API token to authenticate WebSocket connections
 * 
 * @param token - User's authentication token
 * @returns Configured Echo instance
 */
export const initializeEcho = (token: string): any => {
  const config: any = {
    ...echoConfig,
    auth: {
      headers: {
        Accept: 'application/json',
        Authorization: `Bearer ${token}`,
      },
    },
  };

  return new (Echo as any)(config);
};

/**
 * Default Echo instance (not authenticated)
 * Use initializeEcho() for authenticated channels
 */
export const echo = new (Echo as any)(echoConfig);

/**
 * Usage Examples:
 * 
 * 1. Listen to a public channel:
 * ```typescript
 * echo.channel('flash-sales')
 *   .listen('FlashSaleStarted', (event) => {
 *     console.log('Flash sale started:', event);
 *   });
 * ```
 * 
 * 2. Listen to a private channel (requires authentication):
 * ```typescript
 * const authenticatedEcho = initializeEcho(userToken);
 * 
 * authenticatedEcho.private(`conversation.${conversationId}`)
 *   .listen('MessageSent', (event) => {
 *     console.log('New message:', event.message);
 *   })
 *   .listenForWhisper('typing', (event) => {
 *     console.log(`${event.user_name} is typing...`);
 *   });
 * ```
 * 
 * 3. Join a presence channel:
 * ```typescript
 * authenticatedEcho.join('online')
 *   .here((users) => {
 *     console.log('Currently online:', users);
 *   })
 *   .joining((user) => {
 *     console.log(`${user.name} joined`);
 *   })
 *   .leaving((user) => {
 *     console.log(`${user.name} left`);
 *   });
 * ```
 * 
 * 4. Send client events (whispers):
 * ```typescript
 * authenticatedEcho.private(`conversation.${conversationId}`)
 *   .whisper('typing', {
 *     user_id: userId,
 *     user_name: userName,
 *   });
 * ```
 * 
 * 5. Leave a channel:
 * ```typescript
 * authenticatedEcho.leave(`conversation.${conversationId}`);
 * ```
 * 
 * 6. React Hook Example:
 * ```typescript
 * import { useEffect } from 'react';
 * import { initializeEcho } from '@/lib/echo';
 * 
 * function useConversationChannel(conversationId: number, token: string) {
 *   useEffect(() => {
 *     const echo = initializeEcho(token);
 *     
 *     echo.private(`conversation.${conversationId}`)
 *       .listen('MessageSent', (event) => {
 *         // Handle new message
 *         console.log('New message:', event.message);
 *       })
 *       .listenForWhisper('typing', (event) => {
 *         // Handle typing indicator
 *         console.log('User typing:', event.user_name);
 *       });
 *     
 *     return () => {
 *       echo.leave(`conversation.${conversationId}`);
 *     };
 *   }, [conversationId, token]);
 * }
 * ```
 */

export default echo;
