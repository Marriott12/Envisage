'use client';

import { useEffect, useState } from 'react';
import { Sparkles, TrendingUp, Heart, Zap } from 'lucide-react';
import Image from 'next/image';
import Link from 'next/link';

interface Product {
  id: string;
  title: string;
  price: number;
  image: string;
  slug: string;
  rating?: number;
  reviews?: number;
}

interface RecommendedProductsProps {
  userId?: string;
  productId?: string;
  strategy?: 'collaborative' | 'content' | 'trending' | 'personalized';
  limit?: number;
  title?: string;
  className?: string;
}

export function RecommendedProducts({
  userId,
  productId,
  strategy = 'personalized',
  limit = 6,
  title,
  className = '',
}: RecommendedProductsProps) {
  const [products, setProducts] = useState<Product[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const fetchRecommendations = async () => {
      setLoading(true);
      try {
        const params = new URLSearchParams({
          strategy,
          limit: limit.toString(),
          ...(userId && { userId }),
          ...(productId && { productId }),
        });

        const response = await fetch(`/api/recommendations?${params}`);
        const data = await response.json();
        setProducts(data.products || []);
      } catch (error) {
        console.error('Failed to fetch recommendations:', error);
      } finally {
        setLoading(false);
      }
    };

    fetchRecommendations();
  }, [userId, productId, strategy, limit]);

  const getIcon = () => {
    switch (strategy) {
      case 'trending':
        return <TrendingUp className="w-5 h-5" />;
      case 'collaborative':
        return <Heart className="w-5 h-5" />;
      case 'content':
        return <Zap className="w-5 h-5" />;
      default:
        return <Sparkles className="w-5 h-5" />;
    }
  };

  const getDefaultTitle = () => {
    switch (strategy) {
      case 'trending':
        return 'Trending Now';
      case 'collaborative':
        return 'Customers Also Bought';
      case 'content':
        return 'Similar Products';
      default:
        return 'Recommended For You';
    }
  };

  if (loading) {
    return (
      <div className={`bg-white rounded-lg shadow-md p-6 ${className}`}>
        <div className="h-6 w-48 bg-gray-200 rounded animate-pulse mb-4" />
        <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
          {Array.from({ length: limit }).map((_, i) => (
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
      <h2 className="text-xl font-semibold mb-4 flex items-center gap-2">
        {getIcon()}
        {title || getDefaultTitle()}
      </h2>

      {/* Product Grid */}
      <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
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
            <div className="flex items-center justify-between">
              <p className="text-sm font-semibold text-gray-900">
                ${product.price.toFixed(2)}
              </p>
              {product.rating && (
                <div className="flex items-center gap-1 text-xs text-gray-600">
                  <span className="text-yellow-500">★</span>
                  <span>{product.rating}</span>
                </div>
              )}
            </div>
          </Link>
        ))}
      </div>
    </div>
  );
}

// Frequently Bought Together variant
export function FrequentlyBoughtTogether({
  productId,
  onAddAllToCart,
  className = '',
}: {
  productId: string;
  onAddAllToCart?: (productIds: string[]) => void;
  className?: string;
}) {
  const [mainProduct, setMainProduct] = useState<Product | null>(null);
  const [bundleProducts, setBundleProducts] = useState<Product[]>([]);
  const [selectedIds, setSelectedIds] = useState<Set<string>>(new Set());
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const fetchBundle = async () => {
      try {
        const response = await fetch(`/api/products/${productId}/bundle`);
        const data = await response.json();
        setMainProduct(data.product);
        setBundleProducts(data.bundleProducts || []);
        setSelectedIds(new Set([productId, ...(data.bundleProducts || []).map((p: Product) => p.id)]));
      } catch (error) {
        console.error('Failed to fetch bundle:', error);
      } finally {
        setLoading(false);
      }
    };

    fetchBundle();
  }, [productId]);

  const toggleProduct = (id: string) => {
    setSelectedIds((prev) => {
      const newSet = new Set(prev);
      if (newSet.has(id)) {
        newSet.delete(id);
      } else {
        newSet.add(id);
      }
      return newSet;
    });
  };

  const totalPrice = [mainProduct, ...bundleProducts]
    .filter((p) => p && selectedIds.has(p.id))
    .reduce((sum, p) => sum + (p?.price || 0), 0);

  if (loading || !mainProduct || bundleProducts.length === 0) return null;

  return (
    <div className={`bg-white rounded-lg shadow-md border border-gray-200 p-6 ${className}`}>
      <h2 className="text-xl font-semibold mb-4 flex items-center gap-2">
        <Heart className="w-5 h-5 text-red-500" />
        Frequently Bought Together
      </h2>

      {/* Products */}
      <div className="space-y-4 mb-6">
        {/* Main Product */}
        <div className="flex items-center gap-4 p-4 bg-blue-50 rounded-lg">
          <input
            type="checkbox"
            checked={selectedIds.has(mainProduct.id)}
            onChange={() => toggleProduct(mainProduct.id)}
            className="w-5 h-5 text-blue-600 rounded"
          />
          <div className="relative w-20 h-20 flex-shrink-0">
            <Image
              src={mainProduct.image}
              alt={mainProduct.title}
              fill
              className="object-cover rounded"
            />
          </div>
          <div className="flex-1">
            <p className="font-medium text-sm line-clamp-2">{mainProduct.title}</p>
            <p className="text-sm font-semibold text-gray-900 mt-1">
              ${mainProduct.price.toFixed(2)}
            </p>
          </div>
        </div>

        {/* Bundle Products */}
        {bundleProducts.map((product) => (
          <div key={product.id} className="flex items-center gap-4 p-4 border border-gray-200 rounded-lg">
            <input
              type="checkbox"
              checked={selectedIds.has(product.id)}
              onChange={() => toggleProduct(product.id)}
              className="w-5 h-5 text-blue-600 rounded"
            />
            <div className="relative w-20 h-20 flex-shrink-0">
              <Image
                src={product.image}
                alt={product.title}
                fill
                className="object-cover rounded"
              />
            </div>
            <div className="flex-1">
              <p className="font-medium text-sm line-clamp-2">{product.title}</p>
              <p className="text-sm font-semibold text-gray-900 mt-1">
                ${product.price.toFixed(2)}
              </p>
            </div>
          </div>
        ))}
      </div>

      {/* Total and CTA */}
      <div className="flex items-center justify-between pt-4 border-t">
        <div>
          <p className="text-sm text-gray-600">Total for {selectedIds.size} items:</p>
          <p className="text-2xl font-bold text-gray-900">${totalPrice.toFixed(2)}</p>
        </div>
        <button
          onClick={() => onAddAllToCart?.(Array.from(selectedIds))}
          disabled={selectedIds.size === 0}
          className="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:bg-gray-300 disabled:cursor-not-allowed transition-colors font-medium"
        >
          Add Selected to Cart
        </button>
      </div>
    </div>
  );
}
