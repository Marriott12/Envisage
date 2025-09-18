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
  login: (credentials: LoginCredentials) => Promise<{ success: boolean; message: string }>;
  register: (credentials: RegisterCredentials) => Promise<{ success: boolean; message: string }>;
  logout: () => Promise<void>;
  getUser: () => Promise<User | null>;
  refreshToken: () => Promise<boolean>;
}

// API Base URL
const API_BASE_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost/envisage/api';

// Create axios instance with default config
const authApi = axios.create({
  baseURL: API_BASE_URL,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
  withCredentials: true, // Enable cookies for httpOnly
});

// Token storage utility
class TokenStorage {
  private static readonly TOKEN_KEY = 'envisage_auth_token';
  private static readonly REFRESH_KEY = 'envisage_refresh_token';
  private static readonly EXPIRES_KEY = 'envisage_token_expires';

  static setToken(token: string, expiresIn?: number): void {
    try {
      // Try to set httpOnly cookie first (if server supports it)
      document.cookie = `${this.TOKEN_KEY}=${token}; Path=/; HttpOnly; Secure; SameSite=Strict; Max-Age=${expiresIn || 3600}`;
    } catch (error) {
      // Fallback to localStorage if httpOnly cookies aren't supported
      localStorage.setItem(this.TOKEN_KEY, token);
      if (expiresIn) {
        const expiresAt = Date.now() + (expiresIn * 1000);
        localStorage.setItem(this.EXPIRES_KEY, expiresAt.toString());
      }
    }
  }

  static getToken(): string | null {
    try {
      // Try to get from cookie first
      const cookies = document.cookie.split(';');
      const tokenCookie = cookies.find(cookie => 
        cookie.trim().startsWith(`${this.TOKEN_KEY}=`)
      );
      
      if (tokenCookie) {
        return tokenCookie.split('=')[1];
      }
    } catch (error) {
      console.warn('Could not read from cookies:', error);
    }

    // Fallback to localStorage
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
    try {
      // Remove httpOnly cookie
      document.cookie = `${this.TOKEN_KEY}=; Path=/; HttpOnly; Secure; SameSite=Strict; Max-Age=0`;
    } catch (error) {
      console.warn('Could not remove cookie:', error);
    }

    // Remove from localStorage as fallback
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

  // Initialize auth state on mount
  useEffect(() => {
    const initializeAuth = async () => {
      const token = TokenStorage.getToken();
      
      if (token && !TokenStorage.isTokenExpired()) {
        try {
          const userData = await getUser();
          if (userData) {
            setUser(userData);
          }
        } catch (error) {
          console.error('Failed to initialize auth:', error);
          TokenStorage.removeToken();
        }
      }
      
      setIsLoading(false);
    };

    initializeAuth();
  }, []);

  // Login function
  const login = useCallback(async (credentials: LoginCredentials): Promise<{ success: boolean; message: string }> => {
    try {
      setIsLoading(true);
      
      const response: AxiosResponse<AuthResponse> = await authApi.post('/auth/login', credentials);
      const { status, message, data } = response.data;

      if (status === 'success' && data) {
        // Store token
        TokenStorage.setToken(data.token, data.expires_in);
        
        // Set user data
        setUser(data.user);
        
        toast.success('Login successful!');
        return { success: true, message };
      } else {
        toast.error(message || 'Login failed');
        return { success: false, message: message || 'Login failed' };
      }
    } catch (error: any) {
      const errorMessage = error.response?.data?.message || 'Login failed. Please try again.';
      toast.error(errorMessage);
      return { success: false, message: errorMessage };
    } finally {
      setIsLoading(false);
    }
  }, []);

  // Register function
  const register = useCallback(async (credentials: RegisterCredentials): Promise<{ success: boolean; message: string }> => {
    try {
      setIsLoading(true);
      
      const response: AxiosResponse<AuthResponse> = await authApi.post('/auth/register', credentials);
      const { status, message, data } = response.data;

      if (status === 'success' && data) {
        // Store token
        TokenStorage.setToken(data.token, data.expires_in);
        
        // Set user data
        setUser(data.user);
        
        toast.success('Registration successful!');
        return { success: true, message };
      } else {
        toast.error(message || 'Registration failed');
        return { success: false, message: message || 'Registration failed' };
      }
    } catch (error: any) {
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
      // Call logout endpoint to invalidate token on server
      await authApi.post('/auth/logout');
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

  // Get current user function
  const getUser = useCallback(async (): Promise<User | null> => {
    try {
      const token = TokenStorage.getToken();
      if (!token) {
        return null;
      }

      const response: AxiosResponse<{ status: string; data: User }> = await authApi.get('/auth/user');
      
      if (response.data.status === 'success') {
        const userData = response.data.data;
        setUser(userData);
        return userData;
      }
      
      return null;
    } catch (error: any) {
      console.error('Failed to get user:', error);
      
      if (error.response?.status === 401) {
        TokenStorage.removeToken();
        setUser(null);
      }
      
      return null;
    }
  }, []);

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
