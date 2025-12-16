'use client';

import { useEffect, useState } from 'react';
import { Eye, TrendingUp } from 'lucide-react';
import api from '@/lib/api';
import Link from 'next/link';

interface Product {
  id: string;
  name: string;
  slug: string;
  price: number;
  image: string;
  rating?: number;
}

interface RecentlyViewedProps {
  limit?: number;
  className?: string;
}

export default function RecentlyViewed({ limit = 10, className = '' }: RecentlyViewedProps) {
  const [products, setProducts] = useState<Product[]>([]);
  const [isLoading, setIsLoading] = useState(true);

  useEffect(() => {
    fetchRecentlyViewed();
  }, [limit]);

  const fetchRecentlyViewed = async () => {
    try {
      const { data } = await api.get('/recently-viewed', {
        params: { limit },
      });
      setProducts(data.map((item: any) => item.product));
    } catch (error) {
      console.error('Failed to fetch recently viewed:', error);
    } finally {
      setIsLoading(false);
    }
  };

  if (isLoading) {
    return (
      <div className={`${className}`}>
        <div className="animate-pulse space-y-4">
          <div className="h-6 bg-gray-200 rounded w-48"></div>
          <div className="grid grid-cols-2 md:grid-cols-5 gap-4">
            {Array.from({ length: 5 }).map((_, i) => (
              <div key={i} className="h-48 bg-gray-200 rounded"></div>
            ))}
          </div>
        </div>
      </div>
    );
  }

  if (products.length === 0) {
    return null;
  }

  return (
    <div className={`${className}`}>
      <div className="flex items-center gap-2 mb-4">
        <Eye className="w-5 h-5 text-gray-600" />
        <h2 className="text-xl font-semibold">Recently Viewed</h2>
      </div>

      <div className="grid grid-cols-2 md:grid-cols-5 gap-4">
        {products.map((product) => (
          <Link
            key={product.id}
            href={`/products/${product.slug}`}
            className="group block bg-white rounded-lg shadow-sm border hover:shadow-md transition-shadow"
          >
            <div className="aspect-square overflow-hidden rounded-t-lg">
              <img
                src={product.image}
                alt={product.name}
                className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
              />
            </div>
            <div className="p-3">
              <h3 className="text-sm font-medium text-gray-900 line-clamp-2 mb-1">
                {product.name}
              </h3>
              <p className="text-lg font-bold text-indigo-600">
                ${product.price.toFixed(2)}
              </p>
            </div>
          </Link>
        ))}
      </div>
    </div>
  );
}
