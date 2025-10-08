export const getSellerListings = async () => {
  return axios.get(`${API_BASE}/seller/listings`, { headers: getAuthHeaders() }).then(res => res.data);
};

export const getSellerAnalytics = async () => {
  return axios.get(`${API_BASE}/seller/analytics`, { headers: getAuthHeaders() }).then(res => res.data);
};

export const submitReview = async (productId: number, rating: number, review: string) => {
  return axios.post(`${API_BASE}/products/${productId}/reviews`, { rating, review }, { headers: getAuthHeaders() });
};

export const getNotifications = async () => {
  return axios.get(`${API_BASE}/notifications`, { headers: getAuthHeaders() }).then(res => res.data);
};

export const updateProfile = async (name: string, email: string) => {
  return axios.put(`${API_BASE}/user`, { name, email }, { headers: getAuthHeaders() });
};

export const getAdminData = async () => {
  return axios.get(`${API_BASE}/admin/overview`, { headers: getAuthHeaders() }).then(res => res.data);
};

export const logoutUser = async () => {
  return axios.post(`${API_BASE}/logout`, {}, { headers: getAuthHeaders() });
};

export const updateCartItem = async (cartId: number, itemId: number, quantity: number) => {
  return axios.put(`${API_BASE}/cart/${cartId}/item/${itemId}`, { quantity }, { headers: getAuthHeaders() });
};

export const removeCartItem = async (cartId: number, itemId: number) => {
  return axios.delete(`${API_BASE}/cart/${cartId}/item/${itemId}`, { headers: getAuthHeaders() });
};

export const makePayment = async (cartId: number, paymentData: any) => {
  return axios.post(`${API_BASE}/payments`, { cart_id: cartId, ...paymentData }, { headers: getAuthHeaders() });
};
export const getUserId = () => {
  // For demo, decode token or use 1
  return 1;
};

export const getAuthHeaders = () => {
  const token = localStorage.getItem('token');
  return token ? { Authorization: `Bearer ${token}` } : {};
};
export const login = async (email: string, password: string) => {
  const res = await axios.post(`${API_BASE}/login`, { email, password });
  return res.data;
};

export const register = async (name: string, email: string, password: string) => {
  const res = await axios.post(`${API_BASE}/register`, { name, email, password });
  return res.data;
};
export const addToCart = async (userId: number, productId: number, quantity: number = 1) => {
  // Ensure cart exists
  await axios.post(`${API_BASE}/cart`, { user_id: userId });
  // Find cart for user
  const cartRes = await axios.get(`${API_BASE}/cart/${userId}`);
  const cartId = cartRes.data.id;
  // Add item to cart
  return axios.post(`${API_BASE}/cart/${cartId}/add-item`, { product_id: productId, quantity });
};
import axios from 'axios';

const API_BASE = 'http://localhost:8000/api';

export const getProducts = async () => {
  const res = await axios.get(`${API_BASE}/products`);
  return res.data;
};

export const getCart = async (userId: number) => {
  const res = await axios.get(`${API_BASE}/cart/${userId}`, { headers: getAuthHeaders() });
  return res.data;
};

export const getOrders = async () => {
  const res = await axios.get(`${API_BASE}/orders`, { headers: getAuthHeaders() });
  return res.data;
};

export const getPayments = async () => {
  const res = await axios.get(`${API_BASE}/payments`, { headers: getAuthHeaders() });
  return res.data;
};

export const getBlogPosts = async () => {
  const res = await axios.get(`${API_BASE}/blog-posts`, { headers: getAuthHeaders() });
  return res.data;
};
