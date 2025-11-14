'use client';

import { useState, useEffect, useCallback } from 'react';
import { useRouter } from 'next/navigation';
import axios, { AxiosResponse } from 'axios';
import { toast } from 'react-hot-toast';

// Types for authentication
export interface User {
  id: number;
  name: string;
  email: string;
  email_verified_at: string | null;
  created_at: string;
  updated_at: string;
  roles?: string[];
}

export interface LoginCredentials {
  email: string;
  password: string;
  remember_me?: boolean;
}

export interface RegisterCredentials {
  name: string;
  email: string;
  password: string;
  password_confirmation: string;
  terms?: boolean;
}

export interface AuthResponse {
  status: 'success' | 'error';
  message: string;
  data?: {
    user: User;
    token: string;
    expires_in: number;
  };
}

export interface UseAuthReturn {
  user: User | null;
  isAuthenticated: boolean;
  isLoading: boolean;
  login: (credentials: LoginCredentials) => Promise<{ success: boolean; message: string; user?: User }>;
  register: (credentials: RegisterCredentials) => Promise<{ success: boolean; message: string; user?: User }>;
  logout: () => Promise<void>;
  getUser: () => Promise<User | null>;
  refreshToken: () => Promise<boolean>;
}

// API Base URL
const API_BASE_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000/api';

// Create axios instance with default config
const authApi = axios.create({
  baseURL: API_BASE_URL,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
  // withCredentials removed - using token-based auth, not session-based
});

// Token storage utility
class TokenStorage {
  private static readonly TOKEN_KEY = 'envisage_auth_token';
  private static readonly REFRESH_KEY = 'envisage_refresh_token';
  private static readonly EXPIRES_KEY = 'envisage_token_expires';

  static setToken(token: string, expiresIn?: number): void {
    // Store in localStorage (HttpOnly can only be set from server-side)
    localStorage.setItem(this.TOKEN_KEY, token);
    if (expiresIn) {
      const expiresAt = Date.now() + (expiresIn * 1000);
      localStorage.setItem(this.EXPIRES_KEY, expiresAt.toString());
    }
  }

  static getToken(): string | null {
    // Get from localStorage
    const token = localStorage.getItem(this.TOKEN_KEY);
    const expiresAt = localStorage.getItem(this.EXPIRES_KEY);
    
    if (token && expiresAt) {
      if (Date.now() > parseInt(expiresAt)) {
        this.removeToken();
        return null;
      }
    }
    
    return token;
  }

  static removeToken(): void {
    // Remove from localStorage
    localStorage.removeItem(this.TOKEN_KEY);
    localStorage.removeItem(this.REFRESH_KEY);
    localStorage.removeItem(this.EXPIRES_KEY);
  }

  static isTokenExpired(): boolean {
    const expiresAt = localStorage.getItem(this.EXPIRES_KEY);
    if (!expiresAt) return false;
    return Date.now() > parseInt(expiresAt);
  }
}

// Request interceptor to add auth header
authApi.interceptors.request.use((config) => {
  const token = TokenStorage.getToken();
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

// Response interceptor to handle auth errors
authApi.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      // Token expired or invalid
      TokenStorage.removeToken();
      window.location.href = '/login';
    }
    return Promise.reject(error);
  }
);

export const useAuth = (): UseAuthReturn => {
  const [user, setUser] = useState<User | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const router = useRouter();

  const isAuthenticated = !!user && !!TokenStorage.getToken();

  // Get current user function (defined before useEffect)
  const getUser = useCallback(async (): Promise<User | null> => {
    try {
      const token = TokenStorage.getToken();
      if (!token) {
        return null;
      }

      const response: AxiosResponse<User> = await authApi.get('/user', {
        headers: {
          'Authorization': `Bearer ${token}`
        }
      });
      
      const userData = response.data;
      setUser(userData);
      return userData;
    } catch (error: any) {
      console.error('Failed to get user:', error);
      
      if (error.response?.status === 401 || error.response?.status === 404) {
        TokenStorage.removeToken();
        setUser(null);
      }
      
      return null;
    }
  }, []);

  // Initialize auth state on mount
  useEffect(() => {
    const initializeAuth = async () => {
      const token = TokenStorage.getToken();
      
      if (token && !TokenStorage.isTokenExpired()) {
        try {
          await getUser();
        } catch (error) {
          console.error('Failed to initialize auth:', error);
          TokenStorage.removeToken();
        }
      }
      
      setIsLoading(false);
    };

    initializeAuth();
  }, [getUser]);

  // Login function
  const login = useCallback(async (credentials: LoginCredentials): Promise<{ success: boolean; message: string; user?: User }> => {
    try {
      setIsLoading(true);
      
      const response: AxiosResponse = await authApi.post('/login', credentials);
      
      const { access_token, user } = response.data;
      
      if (access_token) {
        TokenStorage.setToken(access_token);
        setUser(user);
        toast.success(`Welcome back, ${user.name}!`);
        return { success: true, message: 'Login successful', user };
      }
      
      toast.error('Login failed - no token received');
      return { success: false, message: 'Login failed' };
    } catch (error: any) {
      console.error('❌ Login error:', error);
      const errorMessage = error.response?.data?.message || 'Login failed. Please check your credentials.';
      toast.error(errorMessage);
      return { success: false, message: errorMessage };
    } finally {
      setIsLoading(false);
    }
  }, []);

  // Register function
  const register = useCallback(async (credentials: RegisterCredentials): Promise<{ success: boolean; message: string; user?: User }> => {
    try {
      setIsLoading(true);
      
      const response: AxiosResponse = await authApi.post('/register', credentials);
      
      const { access_token, user } = response.data;

      if (access_token && user) {
        TokenStorage.setToken(access_token);
        setUser(user);
        toast.success(`Welcome to Envisage, ${user.name}!`);
        return { success: true, message: 'Registration successful', user };
      } else {
        toast.error('Registration failed - no token received');
        return { success: false, message: 'Registration failed' };
      }
    } catch (error: any) {
      console.error('Registration error:', error);
      const errorMessage = error.response?.data?.message || 'Registration failed. Please try again.';
      toast.error(errorMessage);
      return { success: false, message: errorMessage };
    } finally {
      setIsLoading(false);
    }
  }, []);

  // Logout function
  const logout = useCallback(async (): Promise<void> => {
    try {
      const token = TokenStorage.getToken();
      // Call logout endpoint to invalidate token on server
      await authApi.post('/logout', {}, {
        headers: {
          'Authorization': `Bearer ${token}`
        }
      });
    } catch (error) {
      // Continue with logout even if server request fails
      console.error('Logout request failed:', error);
    } finally {
      // Clear local auth state
      TokenStorage.removeToken();
      setUser(null);
      
      toast.success('Logged out successfully');
      router.push('/');
    }
  }, [router]);

  // Refresh token function
  const refreshToken = useCallback(async (): Promise<boolean> => {
    try {
      const response: AxiosResponse<AuthResponse> = await authApi.post('/auth/refresh');
      const { status, data } = response.data;

      if (status === 'success' && data) {
        TokenStorage.setToken(data.token, data.expires_in);
        setUser(data.user);
        return true;
      }
      
      return false;
    } catch (error) {
      console.error('Token refresh failed:', error);
      TokenStorage.removeToken();
      setUser(null);
      return false;
    }
  }, []);

  // Auto-refresh token before expiration
  useEffect(() => {
    if (!isAuthenticated) return;

    const interval = setInterval(async () => {
      const token = TokenStorage.getToken();
      if (token && TokenStorage.isTokenExpired()) {
        const refreshed = await refreshToken();
        if (!refreshed) {
          router.push('/login');
        }
      }
    }, 5 * 60 * 1000); // Check every 5 minutes

    return () => clearInterval(interval);
  }, [isAuthenticated, refreshToken, router]);

  return {
    user,
    isAuthenticated,
    isLoading,
    login,
    register,
    logout,
    getUser,
    refreshToken,
  };
};

export default useAuth;
