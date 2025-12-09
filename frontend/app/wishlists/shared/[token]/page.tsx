'use client';

import React, { useState, useEffect } from 'react';
import api from '@/lib/api';
import Link from 'next/link';
import { useParams } from 'next/navigation';

interface WishlistItem {
  id: number;
  product_id: number;
  priority: number;
  notes?: string;
  product: {
    id: number;
    name: string;
    price: number;
    images_urls?: string[];
  };
}

interface SharedWishlist {
  id: number;
  name: string;
  description?: string;
  items: WishlistItem[];
  created_at: string;
}

export default function SharedWishlistPage() {
  const params = useParams();
  const token = params?.token as string;
  const [wishlist, setWishlist] = useState<SharedWishlist | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  useEffect(() => {
    if (token) {
      fetchSharedWishlist();
    }
  }, [token]);

  const fetchSharedWishlist = async () => {
    try {
      const response = await api.get(`/wishlists/shared/${token}`);
      setWishlist(response.data.data);
    } catch (error) {
      console.error('Failed to fetch shared wishlist:', error);
      setError('This wishlist is private or does not exist.');
    } finally {
      setLoading(false);
    }
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
      <div className="flex items-center justify-center min-h-screen">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
      </div>
    );
  }

  if (error || !wishlist) {
    return (
      <div className="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-16 text-center">
        <div className="bg-red-50 border border-red-200 rounded-lg p-8">
          <svg
            className="w-16 h-16 text-red-400 mx-auto mb-4"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              strokeLinecap="round"
              strokeLinejoin="round"
              strokeWidth={2}
              d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
            />
          </svg>
          <h2 className="text-2xl font-bold text-gray-900 mb-2">Wishlist Not Found</h2>
          <p className="text-gray-600">{error}</p>
          <Link
            href="/marketplace"
            className="inline-block mt-6 bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700"
          >
            Browse Marketplace
          </Link>
        </div>
      </div>
    );
  }

  return (
    <div className="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div className="bg-white rounded-lg shadow-md p-6 mb-6">
        <div className="flex items-center gap-3 mb-2">
          <svg
            className="w-8 h-8 text-blue-600"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              strokeLinecap="round"
              strokeLinejoin="round"
              strokeWidth={2}
              d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"
            />
          </svg>
          <h1 className="text-3xl font-bold text-gray-900">{wishlist.name}</h1>
        </div>
        {wishlist.description && (
          <p className="text-gray-600 mb-4">{wishlist.description}</p>
        )}
        <div className="flex items-center gap-2 text-sm text-gray-500">
          <span>{wishlist.items.length} items</span>
          <span>•</span>
          <span>Shared wishlist</span>
        </div>
      </div>

      {wishlist.items.length > 0 ? (
        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
          {wishlist.items.map((item) => (
            <div
              key={item.id}
              className="bg-white border border-gray-200 rounded-lg overflow-hidden hover:shadow-lg transition"
            >
              <Link href={`/marketplace/${item.product_id}`}>
                {item.product.images_urls && item.product.images_urls[0] && (
                  <img
                    src={item.product.images_urls[0]}
                    alt={item.product.name}
                    className="w-full h-48 object-cover"
                  />
                )}
              </Link>
              <div className="p-4">
                <Link
                  href={`/marketplace/${item.product_id}`}
                  className="text-lg font-semibold text-gray-900 hover:text-blue-600 block mb-2"
                >
                  {item.product.name}
                </Link>
                <div className="flex items-center justify-between mb-3">
                  <p className="text-2xl font-bold text-gray-900">
                    ${item.product.price.toFixed(2)}
                  </p>
                  <span className={`text-xs px-2 py-1 rounded ${getPriorityColor(item.priority)}`}>
                    {getPriorityLabel(item.priority)} Priority
                  </span>
                </div>
                {item.notes && (
                  <div className="bg-gray-50 rounded p-3 mb-3">
                    <p className="text-sm text-gray-700">
                      <span className="font-medium">Note:</span> {item.notes}
                    </p>
                  </div>
                )}
                <Link
                  href={`/marketplace/${item.product_id}`}
                  className="block w-full text-center bg-blue-600 text-white py-2 rounded-md hover:bg-blue-700 transition"
                >
                  View Product
                </Link>
              </div>
            </div>
          ))}
        </div>
      ) : (
        <div className="bg-white rounded-lg shadow-md p-12 text-center">
          <p className="text-gray-500">This wishlist is currently empty.</p>
        </div>
      )}
    </div>
  );
}
