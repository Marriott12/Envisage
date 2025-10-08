// API types and interfaces

export interface PaginationData {
  current_page: number;
  per_page: number;
  total: number;
  last_page: number;
  has_more: boolean;
}

export interface Listing {
  id: number;
  title: string;
  description: string;
  price: number;
  currency: string;
  category: string;
  condition?: string;
  images: string[];
  seller_name: string;
  seller_id: number;
  seller_avatar?: string;
  seller_rating?: number;
  seller_location?: string;
  original_price?: number;
  views: number;
  created_at: string;
  updated_at: string;
  listing?: Listing;
}

export interface Product {
  id: number;
  name: string;
  description: string;
  price: number;
  image: string | null;
  stock: number;
  category: string;
  seller_id?: number;
  featured?: boolean;
  views?: number;
  status?: 'active' | 'inactive' | 'sold';
  created_at: string;
  updated_at: string;
}

export interface CartItem {
  id: number;
  listing_id: number;
  product_id?: number;
  title: string;
  price: number;
  currency: string;
  image: string;
  seller_name: string;
  quantity: number;
}

export interface User {
  id: number;
  name: string;
  email: string;
  role: string;
  roles?: string[];
  permissions?: string[];
  avatar?: string;
}

export interface RegisterCredentials {
  name: string;
  email: string;
  password: string;
  password_confirmation: string;
  terms: boolean;
}

export interface LoginCredentials {
  email: string;
  password: string;
}

export interface ApiResponse<T = any> {
  status: 'success' | 'error';
  message?: string;
  data: T;
}

export interface ListingsResponse {
  listings: Listing[];
  pagination: PaginationData;
}

export interface ProductsResponse {
  products: Product[];
  pagination?: PaginationData;
}
