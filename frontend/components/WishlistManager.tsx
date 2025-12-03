'use client';

import React, { useState, useEffect } from 'react';
import api from '@/lib/api';
import Link from 'next/link';

interface WishlistItem {
  id: number;
  product_id: number;
  priority: number;
  notes?: string;
  target_price?: number;
  price_alert_enabled: boolean;
  product: {
    id: number;
    name: string;
    price: number;
    images_urls?: string[];
  };
}

interface Wishlist {
  id: number;
  name: string;
  description?: string;
  is_public: boolean;
  share_token: string;
  items_count: number;
  items?: WishlistItem[];
  created_at: string;
}

export default function WishlistManager() {
  const [wishlists, setWishlists] = useState<Wishlist[]>([]);
  const [selectedWishlist, setSelectedWishlist] = useState<Wishlist | null>(null);
  const [loading, setLoading] = useState(true);
  const [showCreateModal, setShowCreateModal] = useState(false);
  const [showAddItemModal, setShowAddItemModal] = useState(false);
  const [newWishlist, setNewWishlist] = useState({
    name: '',
    description: '',
    is_public: false,
  });
  const [newItem, setNewItem] = useState({
    product_id: '',
    priority: 0,
    notes: '',
    target_price: '',
    price_alert_enabled: false,
  });

  useEffect(() => {
    fetchWishlists();
  }, []);

  const fetchWishlists = async () => {
    try {
      const response = await api.get('/wishlists');
      setWishlists(response.data.data);
    } catch (error) {
      console.error('Failed to fetch wishlists:', error);
    } finally {
      setLoading(false);
    }
  };

  const fetchWishlistDetails = async (id: number) => {
    try {
      const response = await api.get(`/wishlists/${id}`);
      setSelectedWishlist(response.data.data);
    } catch (error) {
      console.error('Failed to fetch wishlist details:', error);
    }
  };

  const createWishlist = async (e: React.FormEvent) => {
    e.preventDefault();
    try {
      await api.post('/wishlists', newWishlist);
      setShowCreateModal(false);
      setNewWishlist({ name: '', description: '', is_public: false });
      fetchWishlists();
    } catch (error) {
      console.error('Failed to create wishlist:', error);
    }
  };

  const deleteWishlist = async (id: number) => {
    if (!confirm('Are you sure you want to delete this wishlist?')) return;
    try {
      await api.delete(`/wishlists/${id}`);
      fetchWishlists();
      if (selectedWishlist?.id === id) {
        setSelectedWishlist(null);
      }
    } catch (error) {
      console.error('Failed to delete wishlist:', error);
    }
  };

  const addItemToWishlist = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!selectedWishlist) return;

    try {
      const payload = {
        product_id: parseInt(newItem.product_id),
        priority: newItem.priority,
        notes: newItem.notes || undefined,
        target_price: newItem.target_price ? parseFloat(newItem.target_price) : undefined,
        price_alert_enabled: newItem.price_alert_enabled,
      };

      await api.post(`/wishlists/${selectedWishlist.id}/items`, payload);
      setShowAddItemModal(false);
      setNewItem({
        product_id: '',
        priority: 0,
        notes: '',
        target_price: '',
        price_alert_enabled: false,
      });
      fetchWishlistDetails(selectedWishlist.id);
    } catch (error) {
      console.error('Failed to add item to wishlist:', error);
    }
  };

  const removeItemFromWishlist = async (itemId: number) => {
    if (!selectedWishlist) return;
    if (!confirm('Remove this item from wishlist?')) return;

    try {
      await api.delete(`/wishlists/${selectedWishlist.id}/items/${itemId}`);
      fetchWishlistDetails(selectedWishlist.id);
    } catch (error) {
      console.error('Failed to remove item:', error);
    }
  };

  const copyShareLink = (token: string) => {
    const url = `${window.location.origin}/wishlists/shared/${token}`;
    navigator.clipboard.writeText(url);
    alert('Share link copied to clipboard!');
  };

  const getPriorityLabel = (priority: number) => {
    switch (priority) {
      case 2: return 'High';
      case 1: return 'Medium';
      default: return 'Low';
    }
  };

  const getPriorityColor = (priority: number) => {
    switch (priority) {
      case 2: return 'bg-red-100 text-red-800';
      case 1: return 'bg-yellow-100 text-yellow-800';
      default: return 'bg-gray-100 text-gray-800';
    }
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center min-h-[400px]">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
      </div>
    );
  }

  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div className="flex justify-between items-center mb-6">
        <h1 className="text-3xl font-bold text-gray-900">My Wishlists</h1>
        <button
          onClick={() => setShowCreateModal(true)}
          className="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition"
        >
          Create Wishlist
        </button>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {/* Wishlists Sidebar */}
        <div className="lg:col-span-1">
          <div className="bg-white rounded-lg shadow-md p-4">
            <h2 className="text-lg font-semibold mb-4">Your Wishlists</h2>
            {wishlists.length === 0 ? (
              <p className="text-gray-500 text-sm">No wishlists yet. Create your first one!</p>
            ) : (
              <div className="space-y-2">
                {wishlists.map((wishlist) => (
                  <div
                    key={wishlist.id}
                    className={`p-3 border rounded-md cursor-pointer transition ${
                      selectedWishlist?.id === wishlist.id
                        ? 'border-blue-500 bg-blue-50'
                        : 'border-gray-200 hover:border-blue-300'
                    }`}
                    onClick={() => fetchWishlistDetails(wishlist.id)}
                  >
                    <div className="flex justify-between items-start">
                      <div className="flex-1">
                        <h3 className="font-medium text-gray-900">{wishlist.name}</h3>
                        <p className="text-sm text-gray-500">{wishlist.items_count} items</p>
                        {wishlist.is_public && (
                          <span className="inline-block mt-1 text-xs bg-green-100 text-green-800 px-2 py-0.5 rounded">
                            Public
                          </span>
                        )}
                      </div>
                      <button
                        onClick={(e) => {
                          e.stopPropagation();
                          deleteWishlist(wishlist.id);
                        }}
                        className="text-red-600 hover:text-red-800 text-sm"
                      >
                        Delete
                      </button>
                    </div>
                  </div>
                ))}
              </div>
            )}
          </div>
        </div>

        {/* Wishlist Details */}
        <div className="lg:col-span-2">
          {selectedWishlist ? (
            <div className="bg-white rounded-lg shadow-md p-6">
              <div className="flex justify-between items-start mb-6">
                <div>
                  <h2 className="text-2xl font-bold text-gray-900">{selectedWishlist.name}</h2>
                  {selectedWishlist.description && (
                    <p className="text-gray-600 mt-1">{selectedWishlist.description}</p>
                  )}
                </div>
                <div className="flex gap-2">
                  {selectedWishlist.is_public && (
                    <button
                      onClick={() => copyShareLink(selectedWishlist.share_token)}
                      className="bg-gray-200 text-gray-700 px-3 py-1 rounded-md text-sm hover:bg-gray-300"
                    >
                      Copy Share Link
                    </button>
                  )}
                  <button
                    onClick={() => setShowAddItemModal(true)}
                    className="bg-blue-600 text-white px-3 py-1 rounded-md text-sm hover:bg-blue-700"
                  >
                    Add Product
                  </button>
                </div>
              </div>

              {selectedWishlist.items && selectedWishlist.items.length > 0 ? (
                <div className="space-y-4">
                  {selectedWishlist.items.map((item) => (
                    <div key={item.id} className="border rounded-lg p-4 hover:shadow-md transition">
                      <div className="flex gap-4">
                        {item.product.images_urls && item.product.images_urls[0] && (
                          <img
                            src={item.product.images_urls[0]}
                            alt={item.product.name}
                            className="w-24 h-24 object-cover rounded"
                          />
                        )}
                        <div className="flex-1">
                          <Link
                            href={`/marketplace/${item.product_id}`}
                            className="text-lg font-semibold text-gray-900 hover:text-blue-600"
                          >
                            {item.product.name}
                          </Link>
                          <p className="text-xl font-bold text-gray-900 mt-1">
                            ${item.product.price.toFixed(2)}
                          </p>
                          <div className="flex gap-2 mt-2">
                            <span className={`text-xs px-2 py-1 rounded ${getPriorityColor(item.priority)}`}>
                              {getPriorityLabel(item.priority)} Priority
                            </span>
                            {item.price_alert_enabled && item.target_price && (
                              <span className="text-xs px-2 py-1 rounded bg-purple-100 text-purple-800">
                                Price Alert: ${item.target_price.toFixed(2)}
                              </span>
                            )}
                          </div>
                          {item.notes && (
                            <p className="text-sm text-gray-600 mt-2">Note: {item.notes}</p>
                          )}
                        </div>
                        <button
                          onClick={() => removeItemFromWishlist(item.id)}
                          className="text-red-600 hover:text-red-800"
                        >
                          Remove
                        </button>
                      </div>
                    </div>
                  ))}
                </div>
              ) : (
                <div className="text-center py-12">
                  <p className="text-gray-500">This wishlist is empty. Add some products!</p>
                </div>
              )}
            </div>
          ) : (
            <div className="bg-white rounded-lg shadow-md p-12 text-center">
              <p className="text-gray-500">Select a wishlist to view its items</p>
            </div>
          )}
        </div>
      </div>

      {/* Create Wishlist Modal */}
      {showCreateModal && (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
          <div className="bg-white rounded-lg p-6 max-w-md w-full">
            <h2 className="text-xl font-bold mb-4">Create New Wishlist</h2>
            <form onSubmit={createWishlist}>
              <div className="space-y-4">
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">
                    Wishlist Name *
                  </label>
                  <input
                    type="text"
                    required
                    value={newWishlist.name}
                    onChange={(e) => setNewWishlist({ ...newWishlist, name: e.target.value })}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md"
                    placeholder="e.g., Birthday Wishlist"
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">
                    Description
                  </label>
                  <textarea
                    value={newWishlist.description}
                    onChange={(e) => setNewWishlist({ ...newWishlist, description: e.target.value })}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md"
                    rows={3}
                    placeholder="Optional description"
                  />
                </div>
                <div className="flex items-center">
                  <input
                    type="checkbox"
                    id="is_public"
                    checked={newWishlist.is_public}
                    onChange={(e) => setNewWishlist({ ...newWishlist, is_public: e.target.checked })}
                    className="mr-2"
                  />
                  <label htmlFor="is_public" className="text-sm text-gray-700">
                    Make this wishlist public (shareable)
                  </label>
                </div>
              </div>
              <div className="flex justify-end gap-2 mt-6">
                <button
                  type="button"
                  onClick={() => setShowCreateModal(false)}
                  className="px-4 py-2 text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  className="px-4 py-2 text-white bg-blue-600 rounded-md hover:bg-blue-700"
                >
                  Create
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* Add Item Modal */}
      {showAddItemModal && (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
          <div className="bg-white rounded-lg p-6 max-w-md w-full">
            <h2 className="text-xl font-bold mb-4">Add Product to Wishlist</h2>
            <form onSubmit={addItemToWishlist}>
              <div className="space-y-4">
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">
                    Product ID *
                  </label>
                  <input
                    type="number"
                    required
                    value={newItem.product_id}
                    onChange={(e) => setNewItem({ ...newItem, product_id: e.target.value })}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md"
                    placeholder="Enter product ID"
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">
                    Priority
                  </label>
                  <select
                    value={newItem.priority}
                    onChange={(e) => setNewItem({ ...newItem, priority: parseInt(e.target.value) })}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md"
                  >
                    <option value={0}>Low</option>
                    <option value={1}>Medium</option>
                    <option value={2}>High</option>
                  </select>
                </div>
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">
                    Notes
                  </label>
                  <textarea
                    value={newItem.notes}
                    onChange={(e) => setNewItem({ ...newItem, notes: e.target.value })}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md"
                    rows={2}
                    placeholder="Optional notes"
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">
                    Target Price (for alerts)
                  </label>
                  <input
                    type="number"
                    step="0.01"
                    value={newItem.target_price}
                    onChange={(e) => setNewItem({ ...newItem, target_price: e.target.value })}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md"
                    placeholder="0.00"
                  />
                </div>
                <div className="flex items-center">
                  <input
                    type="checkbox"
                    id="price_alert"
                    checked={newItem.price_alert_enabled}
                    onChange={(e) => setNewItem({ ...newItem, price_alert_enabled: e.target.checked })}
                    className="mr-2"
                  />
                  <label htmlFor="price_alert" className="text-sm text-gray-700">
                    Enable price alert
                  </label>
                </div>
              </div>
              <div className="flex justify-end gap-2 mt-6">
                <button
                  type="button"
                  onClick={() => setShowAddItemModal(false)}
                  className="px-4 py-2 text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  className="px-4 py-2 text-white bg-blue-600 rounded-md hover:bg-blue-700"
                >
                  Add to Wishlist
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
}
