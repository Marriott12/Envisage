import api from '@/lib/api';

export interface Wishlist {
  id: string;
  user_id: string;
  name: string;
  description?: string;
  privacy: 'private' | 'public' | 'shared';
  share_token?: string;
  is_default: boolean;
  items?: WishlistItem[];
  share_url?: string;
  created_at: string;
  updated_at: string;
}

export interface WishlistItem {
  id: string;
  wishlist_id: string;
  product_id: string;
  note?: string;
  priority: number;
  price_when_added: number;
  product?: any;
  created_at: string;
}

export const wishlistService = {
  // Get all wishlists
  getWishlists: async () => {
    const { data } = await api.get('/wishlists');
    return data as Wishlist[];
  },

  // Get a specific wishlist
  getWishlist: async (wishlistId: string) => {
    const { data } = await api.get(`/wishlists/${wishlistId}`);
    return data as Wishlist;
  },

  // Create wishlist
  createWishlist: async (wishlistData: Partial<Wishlist>) => {
    const { data } = await api.post('/wishlists', wishlistData);
    return data;
  },

  // Update wishlist
  updateWishlist: async (wishlistId: string, wishlistData: Partial<Wishlist>) => {
    const { data } = await api.put(`/wishlists/${wishlistId}`, wishlistData);
    return data;
  },

  // Delete wishlist
  deleteWishlist: async (wishlistId: string) => {
    const { data } = await api.delete(`/wishlists/${wishlistId}`);
    return data;
  },

  // Quick add to default wishlist
  quickAdd: async (productId: string) => {
    const { data } = await api.post('/wishlists/quick-add', { product_id: productId });
    return data;
  },

  // Add item to wishlist
  addItem: async (wishlistId: string, itemData: { product_id: string; note?: string; priority?: number }) => {
    const { data } = await api.post(`/wishlists/${wishlistId}/items`, itemData);
    return data;
  },

  // Remove item from wishlist
  removeItem: async (wishlistId: string, itemId: string) => {
    const { data } = await api.delete(`/wishlists/${wishlistId}/items/${itemId}`);
    return data;
  },

  // Update wishlist item
  updateItem: async (wishlistId: string, itemId: string, itemData: { note?: string; priority?: number }) => {
    const { data } = await api.put(`/wishlists/${wishlistId}/items/${itemId}`, itemData);
    return data;
  },

  // Share wishlist
  shareWishlist: async (wishlistId: string, shareData: { email: string; permission?: string; expires_in_days?: number }) => {
    const { data } = await api.post(`/wishlists/${wishlistId}/share`, shareData);
    return data;
  },

  // Get shared wishlist
  getSharedWishlist: async (token: string) => {
    const { data } = await api.get(`/wishlists/shared/${token}`);
    return data as Wishlist;
  },

  // Check if product is in wishlist
  checkProduct: async (productId: string) => {
    const { data } = await api.get(`/products/${productId}/wishlist-check`);
    return data;
  },
};

export default wishlistService;
