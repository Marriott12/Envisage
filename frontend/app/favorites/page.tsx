'use client';

import React, { useState } from 'react';
import { motion } from 'framer-motion';
import {
  HeartIcon,
  ShoppingCartIcon,
  TrashIcon,
  MagnifyingGlassIcon,
} from '@heroicons/react/24/outline';
import { HeartIcon as HeartSolidIcon } from '@heroicons/react/24/solid';
import { useAuth } from '@/hooks/useAuth';
import Header from '@/components/Header';
import ProtectedRoute from '@/components/ProtectedRoute';
import Link from 'next/link';
import { toast } from 'react-hot-toast';

// Mock data - replace with API call
const mockFavorites = [
  {
    id: 1,
    title: 'Wireless Bluetooth Headphones',
    price: 129.99,
    image: '/images/p1.jpg',
    seller: 'Tech Store',
    rating: 4.5,
    inStock: true,
    addedDate: '2025-10-01'
  },
  {
    id: 2,
    title: 'Ergonomic Laptop Stand',
    price: 49.99,
    image: '/images/p2.jpg',
    seller: 'Office Supplies',
    rating: 4.8,
    inStock: true,
    addedDate: '2025-10-03'
  },
  {
    id: 3,
    title: 'USB-C Fast Charging Cable',
    price: 15.99,
    image: '/images/p3.jpg',
    seller: 'Electronics Hub',
    rating: 4.3,
    inStock: false,
    addedDate: '2025-10-05'
  },
];

function FavoritesContent() {
  const { user } = useAuth();
  const [favorites, setFavorites] = useState(mockFavorites);
  const [searchQuery, setSearchQuery] = useState('');

  const handleRemove = (id: number) => {
    setFavorites(favorites.filter(item => item.id !== id));
    toast.success('Removed from favorites');
  };

  const handleAddToCart = (item: any) => {
    // TODO: Implement add to cart functionality
    toast.success(`${item.title} added to cart!`);
  };

  const filteredFavorites = favorites.filter(item =>
    item.title.toLowerCase().includes(searchQuery.toLowerCase()) ||
    item.seller.toLowerCase().includes(searchQuery.toLowerCase())
  );

  return (
    <>
      <Header />
      <div className="min-h-screen bg-gray-50 py-8">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          {/* Page Header */}
          <motion.div
            initial={{ opacity: 0, y: -20 }}
            animate={{ opacity: 1, y: 0 }}
            className="mb-8"
          >
            <div className="flex items-center justify-between">
              <div>
                <h1 className="text-3xl font-bold text-gray-900">My Favorites</h1>
                <p className="text-gray-600 mt-1">
                  {favorites.length} {favorites.length === 1 ? 'item' : 'items'} saved
                </p>
              </div>
              {favorites.length > 0 && (
                <button
                  onClick={() => {
                    setFavorites([]);
                    toast.success('All favorites cleared');
                  }}
                  className="text-red-600 hover:text-red-700 font-medium"
                >
                  Clear All
                </button>
              )}
            </div>
          </motion.div>

          {/* Search */}
          {favorites.length > 0 && (
            <motion.div
              initial={{ opacity: 0, y: 20 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ delay: 0.1 }}
              className="mb-6"
            >
              <div className="relative max-w-md">
                <MagnifyingGlassIcon className="absolute left-3 top-3 h-5 w-5 text-gray-400" />
                <input
                  type="text"
                  value={searchQuery}
                  onChange={(e) => setSearchQuery(e.target.value)}
                  placeholder="Search favorites..."
                  className="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                />
              </div>
            </motion.div>
          )}

          {/* Favorites Grid */}
          {filteredFavorites.length === 0 ? (
            <motion.div
              initial={{ opacity: 0 }}
              animate={{ opacity: 1 }}
              className="bg-white rounded-lg shadow-md p-12 text-center"
            >
              <HeartIcon className="h-16 w-16 text-gray-400 mx-auto mb-4" />
              <h3 className="text-lg font-medium text-gray-900 mb-2">
                {searchQuery ? 'No favorites match your search' : 'No favorites yet'}
              </h3>
              <p className="text-gray-600 mb-6">
                {searchQuery 
                  ? 'Try a different search term' 
                  : 'Start adding items to your wishlist by clicking the heart icon on products'
                }
              </p>
              {!searchQuery && (
                <Link href="/marketplace" className="btn-primary px-6 py-2 inline-block">
                  Browse Products
                </Link>
              )}
            </motion.div>
          ) : (
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
              {filteredFavorites.map((item, index) => (
                <motion.div
                  key={item.id}
                  initial={{ opacity: 0, y: 20 }}
                  animate={{ opacity: 1, y: 0 }}
                  transition={{ delay: index * 0.1 }}
                  className="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow"
                >
                  {/* Product Image */}
                  <div className="relative">
                    <div className="h-48 bg-gray-100 flex items-center justify-center">
                      <ShoppingCartIcon className="h-16 w-16 text-gray-400" />
                    </div>
                    {!item.inStock && (
                      <div className="absolute top-0 left-0 right-0 bottom-0 bg-black/50 flex items-center justify-center">
                        <span className="bg-red-600 text-white px-4 py-2 rounded-full text-sm font-medium">
                          Out of Stock
                        </span>
                      </div>
                    )}
                    <button
                      onClick={() => handleRemove(item.id)}
                      className="absolute top-2 right-2 p-2 bg-white rounded-full shadow-lg hover:bg-gray-100 transition-colors"
                    >
                      <HeartSolidIcon className="h-5 w-5 text-red-500" />
                    </button>
                  </div>

                  {/* Product Info */}
                  <div className="p-4">
                    <Link href={`/products/${item.id}`}>
                      <h3 className="font-semibold text-gray-900 mb-2 hover:text-primary-600 transition-colors line-clamp-2">
                        {item.title}
                      </h3>
                    </Link>
                    
                    <div className="flex items-center space-x-1 mb-2">
                      {[...Array(5)].map((_, i) => (
                        <svg
                          key={i}
                          className={`h-4 w-4 ${i < Math.floor(item.rating) ? 'text-yellow-400' : 'text-gray-300'}`}
                          fill="currentColor"
                          viewBox="0 0 20 20"
                        >
                          <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                        </svg>
                      ))}
                      <span className="text-sm text-gray-600 ml-2">({item.rating})</span>
                    </div>

                    <p className="text-sm text-gray-600 mb-3">by {item.seller}</p>

                    <div className="flex items-center justify-between">
                      <span className="text-xl font-bold text-gray-900">
                        ZMW {item.price.toFixed(2)}
                      </span>
                    </div>

                    {/* Actions */}
                    <div className="mt-4 space-y-2">
                      <button
                        onClick={() => handleAddToCart(item)}
                        disabled={!item.inStock}
                        className={`w-full flex items-center justify-center space-x-2 px-4 py-2 rounded-lg font-medium transition-colors ${
                          item.inStock
                            ? 'bg-primary-600 text-white hover:bg-primary-700'
                            : 'bg-gray-300 text-gray-500 cursor-not-allowed'
                        }`}
                      >
                        <ShoppingCartIcon className="h-5 w-5" />
                        <span>{item.inStock ? 'Add to Cart' : 'Out of Stock'}</span>
                      </button>
                      
                      <button
                        onClick={() => handleRemove(item.id)}
                        className="w-full flex items-center justify-center space-x-2 px-4 py-2 border border-red-300 text-red-600 rounded-lg hover:bg-red-50 font-medium transition-colors"
                      >
                        <TrashIcon className="h-5 w-5" />
                        <span>Remove</span>
                      </button>
                    </div>
                  </div>
                </motion.div>
              ))}
            </div>
          )}
        </div>
      </div>
    </>
  );
}

export default function FavoritesPage() {
  return (
    <ProtectedRoute>
      <FavoritesContent />
    </ProtectedRoute>
  );
}
