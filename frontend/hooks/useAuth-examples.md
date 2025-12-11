# useAuth Hook - Example Usage Guide

The `useAuth` hook provides a complete authentication solution for the Next.js frontend, integrating with the PHP JWT backend.

## Key Features

- ✅ **JWT Token Management**: Automatically handles token storage and refresh
- ✅ **HttpOnly Cookie Support**: Falls back to localStorage if cookies unavailable
- ✅ **Auto Token Refresh**: Prevents token expiration with background refresh
- ✅ **Request Interceptors**: Automatically adds Authorization header
- ✅ **Error Handling**: Handles 401 errors and token expiration
- ✅ **TypeScript Support**: Full type safety for all functions and data

## API Reference

### Hook Return Values

```typescript
interface UseAuthReturn {
  user: User | null;                    // Current authenticated user
  isAuthenticated: boolean;             // Authentication status
  isLoading: boolean;                   // Loading state
  login: (credentials) => Promise<{success, message}>;
  register: (credentials) => Promise<{success, message}>;
  logout: () => Promise<void>;
  getUser: () => Promise<User | null>;
  refreshToken: () => Promise<boolean>;
}
```

### User Type

```typescript
interface User {
  id: number;
  name: string;
  email: string;
  email_verified_at: string | null;
  created_at: string;
  updated_at: string;
}
```

## Usage Examples

### 1. Basic Authentication in Login Page

```typescript
'use client';

import React from 'react';
import { useAuth } from '@/hooks/useAuth';
import { useForm } from 'react-hook-form';

export default function LoginPage() {
  const { login, isLoading } = useAuth();
  const { register, handleSubmit, formState: { errors } } = useForm();

  const onSubmit = async (data) => {
    const result = await login({
      email: data.email,
      password: data.password,
      remember_me: data.remember_me
    });

    if (result.success) {
      // Redirect handled automatically by hook
      console.log('Login successful!');
    } else {
      console.error('Login failed:', result.message);
    }
  };

  return (
    <form onSubmit={handleSubmit(onSubmit)}>
      <input 
        {...register('email', { required: true })} 
        type="email" 
        placeholder="Email" 
      />
      <input 
        {...register('password', { required: true })} 
        type="password" 
        placeholder="Password" 
      />
      <input 
        {...register('remember_me')} 
        type="checkbox" 
      />
      <button type="submit" disabled={isLoading}>
        {isLoading ? 'Signing in...' : 'Sign In'}
      </button>
    </form>
  );
}
```

### 2. Registration Form

```typescript
'use client';

import React from 'react';
import { useAuth } from '@/hooks/useAuth';
import { useForm } from 'react-hook-form';

export default function RegisterPage() {
  const { register: registerUser, isLoading } = useAuth();
  const { register, handleSubmit, watch } = useForm();
  
  const password = watch('password');

  const onSubmit = async (data) => {
    const result = await registerUser({
      name: data.name,
      email: data.email,
      password: data.password,
      password_confirmation: data.password_confirmation
    });

    if (result.success) {
      // User automatically logged in after registration
      console.log('Registration successful!');
    }
  };

  return (
    <form onSubmit={handleSubmit(onSubmit)}>
      <input {...register('name', { required: true })} placeholder="Full Name" />
      <input {...register('email', { required: true })} type="email" placeholder="Email" />
      <input {...register('password', { required: true })} type="password" placeholder="Password" />
      <input 
        {...register('password_confirmation', {
          validate: (value) => value === password || 'Passwords do not match'
        })} 
        type="password" 
        placeholder="Confirm Password" 
      />
      <button type="submit" disabled={isLoading}>
        {isLoading ? 'Creating Account...' : 'Sign Up'}
      </button>
    </form>
  );
}
```

### 3. Protected Route Component

```typescript
'use client';

import React from 'react';
import { useAuth } from '@/hooks/useAuth';
import { useRouter } from 'next/navigation';

export default function ProtectedRoute({ children }) {
  const { isAuthenticated, isLoading } = useAuth();
  const router = useRouter();

  React.useEffect(() => {
    if (!isLoading && !isAuthenticated) {
      router.push('/login');
    }
  }, [isAuthenticated, isLoading, router]);

  if (isLoading) {
    return <div>Loading...</div>;
  }

  return isAuthenticated ? <>{children}</> : null;
}

// Usage in protected pages:
export default function DashboardPage() {
  return (
    <ProtectedRoute>
      <div>Protected dashboard content</div>
    </ProtectedRoute>
  );
}
```

### 4. User Profile Display

```typescript
'use client';

import React from 'react';
import { useAuth } from '@/hooks/useAuth';

export default function UserProfile() {
  const { user, logout, isAuthenticated } = useAuth();

  if (!isAuthenticated) {
    return <div>Please log in to view profile</div>;
  }

  return (
    <div>
      <h1>Welcome, {user?.name}!</h1>
      <p>Email: {user?.email}</p>
      <p>Account Status: {user?.email_verified_at ? 'Verified' : 'Unverified'}</p>
      <p>Member since: {new Date(user?.created_at).toLocaleDateString()}</p>
      
      <button onClick={logout}>
        Logout
      </button>
    </div>
  );
}
```

### 5. Navigation Header with Auth

```typescript
'use client';

import React from 'react';
import Link from 'next/link';
import { useAuth } from '@/hooks/useAuth';

export default function Header() {
  const { user, isAuthenticated, logout, isLoading } = useAuth();

  return (
    <header>
      <nav>
        <Link href="/">Home</Link>
        <Link href="/marketplace">Marketplace</Link>
        
        {isLoading ? (
          <div>Loading...</div>
        ) : isAuthenticated ? (
          <div>
            <span>Hello, {user?.name}!</span>
            <Link href="/dashboard">Dashboard</Link>
            <Link href="/profile">Profile</Link>
            <button onClick={logout}>Logout</button>
          </div>
        ) : (
          <div>
            <Link href="/login">Login</Link>
            <Link href="/register">Register</Link>
          </div>
        )}
      </nav>
    </header>
  );
}
```

### 6. API Calls with Auto-Auth

```typescript
'use client';

import React, { useEffect, useState } from 'react';
import { useAuth } from '@/hooks/useAuth';
import axios from 'axios';

export default function UserOrders() {
  const { isAuthenticated, user } = useAuth();
  const [orders, setOrders] = useState([]);
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    if (isAuthenticated) {
      fetchOrders();
    }
  }, [isAuthenticated]);

  const fetchOrders = async () => {
    try {
      setLoading(true);
      // Token automatically added by axios interceptor
      const response = await axios.get('/api/orders');
      setOrders(response.data.data);
    } catch (error) {
      console.error('Failed to fetch orders:', error);
      // 401 errors automatically handled by interceptor
    } finally {
      setLoading(false);
    }
  };

  if (!isAuthenticated) {
    return <div>Please login to view orders</div>;
  }

  return (
    <div>
      <h2>My Orders</h2>
      {loading ? (
        <div>Loading orders...</div>
      ) : (
        <ul>
          {orders.map(order => (
            <li key={order.id}>
              Order #{order.id} - {order.status}
            </li>
          ))}
        </ul>
      )}
    </div>
  );
}
```

### 7. Conditional Rendering Based on Auth

```typescript
'use client';

import React from 'react';
import { useAuth } from '@/hooks/useAuth';

export default function HomePage() {
  const { isAuthenticated, user, isLoading } = useAuth();

  if (isLoading) {
    return <div>Loading...</div>;
  }

  return (
    <div>
      <h1>Welcome to Envisage Marketplace</h1>
      
      {isAuthenticated ? (
        <div>
          <h2>Welcome back, {user?.name}!</h2>
          <p>Ready to find great deals?</p>
          <Link href="/marketplace">Browse Marketplace</Link>
          <Link href="/dashboard">Go to Dashboard</Link>
        </div>
      ) : (
        <div>
          <h2>Join thousands of happy customers</h2>
          <p>Create an account to start buying and selling</p>
          <Link href="/register">Get Started</Link>
          <Link href="/login">Sign In</Link>
        </div>
      )}
    </div>
  );
}
```

### 8. Auto-Refresh Token Example

```typescript
'use client';

import React, { useEffect } from 'react';
import { useAuth } from '@/hooks/useAuth';

export default function App({ children }) {
  const { refreshToken, isAuthenticated } = useAuth();

  // Manual token refresh (usually not needed as it's automatic)
  useEffect(() => {
    if (isAuthenticated) {
      // Refresh token every 30 minutes
      const interval = setInterval(async () => {
        const success = await refreshToken();
        if (!success) {
          console.log('Token refresh failed, user will be logged out');
        }
      }, 30 * 60 * 1000);

      return () => clearInterval(interval);
    }
  }, [isAuthenticated, refreshToken]);

  return <>{children}</>;
}
```

## Integration with API Calls

The hook automatically configures axios interceptors to:

1. **Add Authorization Header**: All requests include `Bearer {token}`
2. **Handle 401 Errors**: Automatically redirect to login on token expiration
3. **Token Refresh**: Refresh expired tokens before requests

```typescript
// No need to manually add Authorization header
const response = await axios.get('/api/marketplace/listings');

// The hook's interceptor automatically adds:
// headers: { Authorization: 'Bearer your-jwt-token' }
```

## Environment Variables

Create `.env.local`:

```env
NEXT_PUBLIC_API_URL=http://localhost/envisage/api
```

## Error Handling

The hook provides comprehensive error handling:

- **Network Errors**: Displays user-friendly messages
- **Validation Errors**: Returns specific field errors
- **401 Unauthorized**: Auto-logout and redirect to login
- **Token Expiration**: Automatic refresh or logout

## Best Practices

1. **Use ProtectedRoute**: Wrap protected pages/components
2. **Check isLoading**: Always handle loading states
3. **Handle Errors**: Check result.success in login/register
4. **Auto-Logout**: Let the hook handle token expiration
5. **Type Safety**: Use TypeScript interfaces provided

This hook provides a complete, production-ready authentication solution that integrates seamlessly with your PHP JWT backend! 🚀
