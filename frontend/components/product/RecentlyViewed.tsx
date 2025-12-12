'use client';

import { useEffect, useState } from 'react';
import { Clock, X } from 'lucide-react';
import Image from 'next/image';
import Link from 'next/link';
import { useBehavioralStore } from '@/hooks/useBehavioralTracking';

interface RecentlyViewedProps {
  limit?: number;
  excludeCurrentProduct?: string;
  showClearAll?: boolean;
  className?: string;
}

interface Product {
  id: string;
  title: string;
  price: number;
  image: string;
  slug: string;
}

export function RecentlyViewed({
  limit = 8,
  excludeCurrentProduct,
  showClearAll = true,
  className = '',
}: RecentlyViewedProps) {
  const { recentlyViewed, getRecentViews, clearHistory } = useBehavioralStore();
  const [products, setProducts] = useState<Product[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const fetchProducts = async () => {
      const viewedIds = getRecentViews(limit + 1)
        .filter((id) => id !== excludeCurrentProduct)
        .slice(0, limit);

      if (viewedIds.length === 0) {
        setLoading(false);
        return;
      }

      try {
        const response = await fetch('/api/products/batch', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ ids: viewedIds }),
        });
        const data = await response.json();
        setProducts(data.products || []);
      } catch (error) {
        console.error('Failed to fetch recently viewed products:', error);
      } finally {
        setLoading(false);
      }
    };

    fetchProducts();
  }, [limit, excludeCurrentProduct, recentlyViewed.length]);

  const handleClearAll = () => {
    clearHistory();
    setProducts([]);
  };

  if (loading) {
    return (
      <div className={`bg-white rounded-lg shadow-md p-6 ${className}`}>
        <div className="h-6 w-48 bg-gray-200 rounded animate-pulse mb-4" />
        <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
          {Array.from({ length: 4 }).map((_, i) => (
            <div key={i} className="space-y-2">
              <div className="aspect-square bg-gray-200 rounded animate-pulse" />
              <div className="h-4 bg-gray-200 rounded animate-pulse" />
              <div className="h-4 w-20 bg-gray-200 rounded animate-pulse" />
            </div>
          ))}
        </div>
      </div>
    );
  }

  if (products.length === 0) return null;

  return (
    <div className={`bg-white rounded-lg shadow-md p-6 ${className}`}>
      {/* Header */}
      <div className="flex items-center justify-between mb-4">
        <h2 className="text-xl font-semibold flex items-center gap-2">
          <Clock className="w-5 h-5" />
          Recently Viewed
        </h2>
        {showClearAll && (
          <button
            onClick={handleClearAll}
            className="text-sm text-gray-600 hover:text-gray-900 font-medium"
          >
            Clear All
          </button>
        )}
      </div>

      {/* Product Grid */}
      <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-4 gap-4">
        {products.map((product) => (
          <Link
            key={product.id}
            href={`/products/${product.slug}`}
            className="group"
          >
            <div className="relative aspect-square bg-gray-100 rounded-lg overflow-hidden mb-2">
              <Image
                src={product.image}
                alt={product.title}
                fill
                className="object-cover group-hover:scale-105 transition-transform duration-300"
              />
            </div>
            <h3 className="text-sm font-medium text-gray-900 line-clamp-2 mb-1 group-hover:text-blue-600">
              {product.title}
            </h3>
            <p className="text-sm font-semibold text-gray-900">
              ${product.price.toFixed(2)}
            </p>
          </Link>
        ))}
      </div>
    </div>
  );
}

// Compact horizontal scrollable version
export function RecentlyViewedCarousel({
  limit = 10,
  excludeCurrentProduct,
  className = '',
}: Omit<RecentlyViewedProps, 'showClearAll'>) {
  const { getRecentViews } = useBehavioralStore();
  const [products, setProducts] = useState<Product[]>([]);

  useEffect(() => {
    const fetchProducts = async () => {
      const viewedIds = getRecentViews(limit + 1)
        .filter((id) => id !== excludeCurrentProduct)
        .slice(0, limit);

      if (viewedIds.length === 0) return;

      try {
        const response = await fetch('/api/products/batch', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ ids: viewedIds }),
        });
        const data = await response.json();
        setProducts(data.products || []);
      } catch (error) {
        console.error('Failed to fetch products:', error);
      }
    };

    fetchProducts();
  }, [limit, excludeCurrentProduct]);

  if (products.length === 0) return null;

  return (
    <div className={className}>
      <h3 className="text-lg font-semibold mb-3 flex items-center gap-2">
        <Clock className="w-5 h-5" />
        Recently Viewed
      </h3>
      <div className="flex gap-3 overflow-x-auto pb-2 scrollbar-hide">
        {products.map((product) => (
          <Link
            key={product.id}
            href={`/products/${product.slug}`}
            className="flex-shrink-0 w-32 group"
          >
            <div className="relative aspect-square bg-gray-100 rounded-lg overflow-hidden mb-2">
              <Image
                src={product.image}
                alt={product.title}
                fill
                className="object-cover group-hover:scale-105 transition-transform duration-300"
              />
            </div>
            <p className="text-xs text-gray-900 line-clamp-2 mb-1">
              {product.title}
            </p>
            <p className="text-sm font-semibold text-gray-900">
              ${product.price.toFixed(2)}
            </p>
          </Link>
        ))}
      </div>
    </div>
  );
}
