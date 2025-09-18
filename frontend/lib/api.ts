import axios from 'axios';

// API configuration
const API_BASE_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:3000/api';

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
  category: string;
  condition_type: string;
  location: string;
  seller_id: number;
  seller_name: string;
  seller_email: string;
  seller_rating?: number;
  images: string[];
  status: 'active' | 'sold' | 'inactive';
  views: number;
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
  // Get listings with filters and pagination
  getListings: async (params: {
    page?: number;
    per_page?: number;
    category?: string;
    condition?: string;
    min_price?: number;
    max_price?: number;
    location?: string;
    search?: string;
    sort?: string;
  } = {}): Promise<ListingsResponse> => {
    const queryParams = new URLSearchParams();
    
    Object.entries(params).forEach(([key, value]) => {
      if (value !== undefined && value !== '') {
        queryParams.append(key, value.toString());
      }
    });

    return api.get(`/marketplace/listings?${queryParams.toString()}`);
  },

  // Get single listing details
  getListing: async (id: number): Promise<ListingDetailResponse> => {
    return api.get(`/marketplace/listing/${id}`);
  },

  // Create new listing
  createListing: async (listingData: Partial<Listing>): Promise<any> => {
    return api.post('/marketplace/listing', listingData);
  },

  // Update listing
  updateListing: async (id: number, listingData: Partial<Listing>): Promise<any> => {
    return api.put(`/marketplace/listing/${id}`, listingData);
  },

  // Delete listing
  deleteListing: async (id: number): Promise<any> => {
    return api.delete(`/marketplace/listing/${id}`);
  },

  // Buy listing (create order)
  buyListing: async (id: number, orderData: OrderData): Promise<OrderResponse> => {
    return api.post(`/marketplace/listing/${id}/buy`, orderData);
  },

  // Get categories
  getCategories: async (): Promise<any> => {
    return api.get('/marketplace/categories');
  },

  // Get user's listings
  getMyListings: async (params: {
    page?: number;
    per_page?: number;
    status?: string;
  } = {}): Promise<ListingsResponse> => {
    const queryParams = new URLSearchParams();
    
    Object.entries(params).forEach(([key, value]) => {
      if (value !== undefined && value !== '') {
        queryParams.append(key, value.toString());
      }
    });

    return api.get(`/marketplace/my-listings?${queryParams.toString()}`);
  },

  // Search listings
  searchListings: async (query: string, filters: any = {}): Promise<ListingsResponse> => {
    return api.get('/marketplace/search', {
      params: { q: query, ...filters }
    });
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
  createPayment: async (paymentData: any): Promise<any> => {
    return api.post('/payments/create', paymentData);
  },

  getPayment: async (id: number): Promise<any> => {
    return api.get(`/payments/${id}`);
  },

  verifyPayment: async (id: number): Promise<any> => {
    return api.get(`/payments/${id}/verify`);
  },

  getMyPayments: async (params: any = {}): Promise<any> => {
    const queryParams = new URLSearchParams(params);
    return api.get(`/payments/my-payments?${queryParams.toString()}`);
  },
};

export default api;
