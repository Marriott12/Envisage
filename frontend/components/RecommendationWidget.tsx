'use client';

import React, { useState, useEffect } from 'react';
import api from '@/lib/api';
import Link from 'next/link';

interface Product {
  id: number;
  name: string;
  price: number;
  images_urls?: string[];
  rating?: number;
  reviews_count?: number;
}

interface RecommendationWidgetProps {
  title: string;
  endpoint: string;
  productId?: number;
  limit?: number;
}

export default function RecommendationWidget({
  title,
  endpoint,
  productId,
  limit = 6,
}: RecommendationWidgetProps) {
  const [products, setProducts] = useState<Product[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    fetchRecommendations();
  }, [endpoint, productId]);

  const fetchRecommendations = async () => {
    try {
      let url = endpoint;
      if (productId) {
        url = url.replace(':productId', productId.toString());
      }
      url += `?limit=${limit}`;

      const response = await api.get(url);
      setProducts(response.data.data);
    } catch (error) {
      console.error('Failed to fetch recommendations:', error);
    } finally {
      setLoading(false);
    }
  };

  if (loading) {
    return (
      <div className="py-8">
        <h2 className="text-2xl font-bold text-gray-900 mb-6">{title}</h2>
        <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
          {[...Array(6)].map((_, i) => (
            <div key={i} className="bg-gray-200 animate-pulse rounded-lg h-64"></div>
          ))}
        </div>
      </div>
    );
  }

  if (products.length === 0) {
    return null;
  }

  return (
    <div className="py-8">
      <h2 className="text-2xl font-bold text-gray-900 mb-6">{title}</h2>
      <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
        {products.map((product) => (
          <Link
            key={product.id}
            href={`/marketplace/${product.id}`}
            className="bg-white border border-gray-200 rounded-lg overflow-hidden hover:shadow-lg transition group"
          >
            {product.images_urls && product.images_urls[0] && (
              <div className="aspect-square overflow-hidden">
                <img
                  src={product.images_urls[0]}
                  alt={product.name}
                  className="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300"
                />
              </div>
            )}
            <div className="p-3">
              <h3 className="text-sm font-semibold text-gray-900 line-clamp-2 mb-2 group-hover:text-blue-600">
                {product.name}
              </h3>
              <div className="flex items-center justify-between">
                <p className="text-lg font-bold text-gray-900">${product.price.toFixed(2)}</p>
                {product.rating && (
                  <div className="flex items-center gap-1 text-xs">
                    <span className="text-yellow-500">★</span>
                    <span>{product.rating.toFixed(1)}</span>
                  </div>
                )}
              </div>
            </div>
          </Link>
        ))}
      </div>
    </div>
  );
}
