'use client';

import { useState, useEffect } from 'react';
import { ClockIcon, XMarkIcon } from '@heroicons/react/24/outline';
import Link from 'next/link';
import StarRating from './StarRating';

interface RecentProduct {
  id: number;
  title: string;
  price: number;
  currency: string;
  image?: string;
  rating?: number;
  viewedAt: number;
}

interface RecentlyViewedProductsProps {
  maxItems?: number;
  className?: string;
  showTitle?: boolean;
}

export default function RecentlyViewedProducts({
  maxItems = 6,
  className = '',
  showTitle = true,
}: RecentlyViewedProductsProps) {
  const [recentProducts, setRecentProducts] = useState<RecentProduct[]>([]);

  useEffect(() => {
    loadRecentProducts();
  }, []);

  const loadRecentProducts = () => {
    try {
      const stored = localStorage.getItem('recentlyViewed');
      if (stored) {
        const products: RecentProduct[] = JSON.parse(stored);
        // Sort by most recent first
        const sorted = products.sort((a, b) => b.viewedAt - a.viewedAt);
        setRecentProducts(sorted.slice(0, maxItems));
      }
    } catch (error) {
      console.error('Error loading recently viewed products:', error);
    }
  };

  const removeProduct = (productId: number) => {
    try {
      const updated = recentProducts.filter((p) => p.id !== productId);
      setRecentProducts(updated);
      localStorage.setItem('recentlyViewed', JSON.stringify(updated));
    } catch (error) {
      console.error('Error removing product:', error);
    }
  };

  const clearAll = () => {
    setRecentProducts([]);
    localStorage.removeItem('recentlyViewed');
  };

  if (recentProducts.length === 0) {
    return null;
  }

  return (
    <div className={`bg-white rounded-lg shadow-sm p-6 ${className}`}>
      {/* Header */}
      {showTitle && (
        <div className="flex items-center justify-between mb-6">
          <div className="flex items-center gap-2">
            <ClockIcon className="h-6 w-6 text-gray-600" />
            <h2 className="text-xl font-bold text-gray-900">Recently Viewed</h2>
          </div>
          <button
            onClick={clearAll}
            className="text-sm text-blue-600 hover:text-blue-700 transition-colors"
          >
            Clear All
          </button>
        </div>
      )}

      {/* Products Grid */}
      <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
        {recentProducts.map((product) => (
          <div key={product.id} className="group relative">
            <Link href={`/marketplace/${product.id}`}>
              <div className="aspect-square bg-gray-100 rounded-lg overflow-hidden mb-2 relative">
                {product.image ? (
                  <img
                    src={product.image}
                    alt={product.title}
                    className="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300"
                  />
                ) : (
                  <div className="w-full h-full flex items-center justify-center text-gray-400">
                    No Image
                  </div>
                )}

                {/* Remove Button */}
                <button
                  onClick={(e) => {
                    e.preventDefault();
                    removeProduct(product.id);
                  }}
                  className="absolute top-2 right-2 p-1.5 bg-white/90 hover:bg-white rounded-full shadow-md opacity-0 group-hover:opacity-100 transition-opacity"
                  aria-label="Remove"
                >
                  <XMarkIcon className="h-4 w-4 text-gray-600" />
                </button>
              </div>

              <h3 className="text-sm font-medium text-gray-900 truncate mb-1">
                {product.title}
              </h3>

              {product.rating && product.rating > 0 && (
                <div className="mb-1">
                  <StarRating rating={product.rating} size="xs" />
                </div>
              )}

              <p className="text-sm font-bold text-blue-600">
                {product.currency} {product.price.toFixed(2)}
              </p>
            </Link>
          </div>
        ))}
      </div>
    </div>
  );
}

// Helper function to add product to recently viewed (to be called from product detail page)
export function addToRecentlyViewed(product: Omit<RecentProduct, 'viewedAt'>) {
  try {
    const stored = localStorage.getItem('recentlyViewed');
    const existing: RecentProduct[] = stored ? JSON.parse(stored) : [];

    // Remove if already exists
    const filtered = existing.filter((p) => p.id !== product.id);

    // Add to beginning with current timestamp
    const updated: RecentProduct[] = [
      { ...product, viewedAt: Date.now() },
      ...filtered,
    ].slice(0, 20); // Keep max 20 items

    localStorage.setItem('recentlyViewed', JSON.stringify(updated));
  } catch (error) {
    console.error('Error adding to recently viewed:', error);
  }
}
