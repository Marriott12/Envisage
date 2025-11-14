import axios from 'axios';

// API configuration
const API_BASE_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost/envisage/backend/public/api';

// Create axios instance
const api = axios.create({
  baseURL: API_BASE_URL,
  timeout: 10000,
  headers: {
    'Content-Type': 'application/json',
  },
});

// Request interceptor to add auth token
api.interceptors.request.use(
  (config) => {
    if (typeof window !== 'undefined') {
      const token = localStorage.getItem('auth_token');
      if (token) {
        config.headers.Authorization = `Bearer ${token}`;
      }
    }
    return config;
  },
  (error) => {
    return Promise.reject(error);
  }
);

// Response interceptor for error handling
api.interceptors.response.use(
  (response) => {
    return response.data;
  },
  (error) => {
    if (error.response?.status === 401) {
      // Handle unauthorized access
      if (typeof window !== 'undefined') {
        localStorage.removeItem('auth_token');
        localStorage.removeItem('user_data');
        window.location.href = '/auth/login';
      }
    }
    return Promise.reject(error.response?.data || error);
  }
);

// Types
export interface Listing {
  id: number;
  title: string;
  description: string;
  price: number;
  currency: string;
  category: string | { id: number; name: string; slug?: string };
  condition_type?: string;
  condition?: string;
  location?: string;
  seller_location?: string;
  seller_id: number;
  seller_name?: string;
  seller?: {
    id: number;
    name: string;
    email: string;
  };
  seller_email?: string;
  seller_rating?: number;
  seller_verified?: boolean;
  images: string[];
  status: 'active' | 'sold' | 'inactive';
  views: number;
  original_price?: number;
  created_at: string;
  updated_at: string;
}

export interface PaginationData {
  total: number;
  per_page: number;
  current_page: number;
  last_page: number;
  has_more: boolean;
}

export interface ListingsResponse {
  status: string;
  message: string;
  data: {
    listings: Listing[];
    pagination: PaginationData;
  };
}

export interface ListingDetailResponse {
  status: string;
  message: string;
  data: {
    listing: Listing;
  };
}

export interface OrderData {
  shipping_address: {
    name: string;
    address_line_1: string;
    address_line_2?: string;
    city: string;
    state: string;
    postal_code: string;
    country: string;
    phone: string;
  };
  billing_address?: {
    name: string;
    address_line_1: string;
    address_line_2?: string;
    city: string;
    state: string;
    postal_code: string;
    country: string;
  };
  payment_method: string;
  notes?: string;
}

export interface OrderResponse {
  status: string;
  message: string;
  data: {
    order_id: number;
    payment_url?: string;
    payment_data?: any;
  };
}

// API functions
export const marketplaceApi = {
  // Get products with filters and pagination
  getListings: async (params: {
    page?: number;
    per_page?: number;
    category?: string;
    min_price?: number;
    max_price?: number;
    search?: string;
    sort?: string;
  } = {}): Promise<ListingsResponse> => {
    const queryParams = new URLSearchParams();
    Object.entries(params).forEach(([key, value]) => {
      if (value !== undefined && value !== '') {
        queryParams.append(key, value.toString());
      }
    });
    return api.get(`/products?${queryParams.toString()}`);
  },

  // Get single product details
  getListing: async (id: number): Promise<ListingDetailResponse> => {
    return api.get(`/products/${id}`);
  },

  // Create new product
  createListing: async (listingData: Partial<Listing>): Promise<any> => {
    return api.post('/products', listingData);
  },

  // Update product
  updateListing: async (id: number, listingData: Partial<Listing>): Promise<any> => {
    return api.put(`/products/${id}`, listingData);
  },

  // Delete product
  deleteListing: async (id: number): Promise<any> => {
    return api.delete(`/products/${id}`);
  },

  // Get seller's own products
  getMyListings: async (params: {
    status?: string;
    search?: string;
    per_page?: number;
  } = {}): Promise<ListingsResponse> => {
    const queryParams = new URLSearchParams();
    Object.entries(params).forEach(([key, value]) => {
      if (value !== undefined && value !== '') {
        queryParams.append(key, value.toString());
      }
    });
    return api.get(`/seller/products?${queryParams.toString()}`);
  },

  // Search products
  searchListings: async (query: string, filters: any = {}): Promise<ListingsResponse> => {
    return api.get('/products', {
      params: { search: query, ...filters }
    });
  },

  // Buy product (create order)
  buyListing: async (productId: number, orderData: OrderData): Promise<OrderResponse> => {
    // The backend expects: user_id, status, total, shipping_address, billing_address, payment_method, payment_status, items
    // We'll send a single item order for the selected product
    const payload = {
      ...orderData,
      items: [
        {
          product_id: productId,
          quantity: 1,
        },
      ],
    };
    return api.post('/orders', payload);
  },
};

// Auth API functions
export const authApi = {
  login: async (email: string, password: string): Promise<any> => {
    return api.post('/auth/login', { email, password });
  },

  register: async (name: string, email: string, password: string): Promise<any> => {
    return api.post('/auth/register', { name, email, password });
  },

  getProfile: async (): Promise<any> => {
    return api.get('/auth/profile');
  },

  updateProfile: async (profileData: any): Promise<any> => {
    return api.put('/auth/profile', profileData);
  },

  logout: async (): Promise<any> => {
    return api.post('/auth/logout');
  },
};

// Payment API functions
export const paymentApi = {
  // Create a payment record (for manual or external payment flows)
  createPayment: async (paymentData: any): Promise<any> => {
    return api.post('/payments', paymentData);
  },

  // Get payment details
  getPayment: async (id: number): Promise<any> => {
    return api.get(`/payments/${id}`);
  },

  // Update payment (e.g., mark as paid/verified)
  updatePayment: async (id: number, data: any): Promise<any> => {
    return api.put(`/payments/${id}`, data);
  },

  // List all payments for the authenticated user
  getMyPayments: async (params: any = {}): Promise<any> => {
    const queryParams = new URLSearchParams(params);
    return api.get(`/payments?${queryParams.toString()}`);
  },
};

export default api;
