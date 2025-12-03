'use client';

import React, { useState, useEffect } from 'react';
import api from '@/lib/api';
import Link from 'next/link';
import { useRouter } from 'next/navigation';

interface Product {
  id: number;
  name: string;
  price: number;
  description: string;
  images_urls?: string[];
  rating?: number;
  reviews_count?: number;
  category_id: number;
  seller_id: number;
  stock_quantity: number;
  specifications?: Record<string, any>;
}

export default function ProductComparison() {
  const router = useRouter();
  const [products, setProducts] = useState<Product[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    loadComparisonProducts();
  }, []);

  const loadComparisonProducts = async () => {
    try {
      const compareIds = JSON.parse(localStorage.getItem('compare_products') || '[]');
      
      if (compareIds.length === 0) {
        setLoading(false);
        return;
      }

      const productPromises = compareIds.map((id: number) =>
        api.get(`/products/${id}`)
      );

      const responses = await Promise.all(productPromises);
      const fetchedProducts = responses.map(res => res.data.data);
      setProducts(fetchedProducts);
    } catch (error) {
      console.error('Failed to load comparison products:', error);
    } finally {
      setLoading(false);
    }
  };

  const removeFromComparison = (productId: number) => {
    const compareIds = JSON.parse(localStorage.getItem('compare_products') || '[]');
    const updated = compareIds.filter((id: number) => id !== productId);
    localStorage.setItem('compare_products', JSON.stringify(updated));
    setProducts(products.filter(p => p.id !== productId));
  };

  const clearAll = () => {
    localStorage.removeItem('compare_products');
    setProducts([]);
  };

  const extractSpecifications = (product: Product) => {
    return product.specifications || {};
  };

  const getAllSpecKeys = () => {
    const allKeys = new Set<string>();
    products.forEach(product => {
      Object.keys(extractSpecifications(product)).forEach(key => allKeys.add(key));
    });
    return Array.from(allKeys);
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center min-h-screen">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
      </div>
    );
  }

  if (products.length === 0) {
    return (
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 text-center">
        <div className="bg-gray-50 border border-gray-200 rounded-lg p-12">
          <svg
            className="w-24 h-24 text-gray-300 mx-auto mb-4"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              strokeLinecap="round"
              strokeLinejoin="round"
              strokeWidth={2}
              d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"
            />
          </svg>
          <h2 className="text-2xl font-bold text-gray-900 mb-2">No Products to Compare</h2>
          <p className="text-gray-600 mb-6">
            You haven't added any products to compare yet. Browse the marketplace and click "Add to Compare" on products you're interested in.
          </p>
          <Link
            href="/marketplace"
            className="inline-block bg-blue-600 text-white px-6 py-3 rounded-md hover:bg-blue-700 transition"
          >
            Browse Marketplace
          </Link>
        </div>
      </div>
    );
  }

  const specKeys = getAllSpecKeys();

  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div className="flex justify-between items-center mb-6">
        <h1 className="text-3xl font-bold text-gray-900">Product Comparison</h1>
        <div className="flex gap-3">
          <button
            onClick={clearAll}
            className="text-red-600 hover:text-red-800 font-medium"
          >
            Clear All
          </button>
          <Link
            href="/marketplace"
            className="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition"
          >
            Continue Shopping
          </Link>
        </div>
      </div>

      <div className="bg-white rounded-lg shadow-md overflow-x-auto">
        <table className="w-full border-collapse">
          <thead>
            <tr className="bg-gray-50 border-b">
              <th className="px-6 py-4 text-left text-sm font-semibold text-gray-700 w-48">
                Feature
              </th>
              {products.map(product => (
                <th key={product.id} className="px-6 py-4 text-center relative">
                  <button
                    onClick={() => removeFromComparison(product.id)}
                    className="absolute top-2 right-2 text-gray-400 hover:text-red-600"
                    aria-label="Remove from comparison"
                  >
                    <svg className="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                      <path
                        fillRule="evenodd"
                        d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                        clipRule="evenodd"
                      />
                    </svg>
                  </button>
                  {product.images_urls && product.images_urls[0] && (
                    <img
                      src={product.images_urls[0]}
                      alt={product.name}
                      className="w-32 h-32 object-cover rounded mx-auto mb-3"
                    />
                  )}
                  <Link
                    href={`/marketplace/${product.id}`}
                    className="text-sm font-semibold text-gray-900 hover:text-blue-600 block"
                  >
                    {product.name}
                  </Link>
                </th>
              ))}
            </tr>
          </thead>
          <tbody className="divide-y divide-gray-200">
            {/* Price Row */}
            <tr className="bg-blue-50">
              <td className="px-6 py-4 font-semibold text-gray-700">Price</td>
              {products.map(product => (
                <td key={product.id} className="px-6 py-4 text-center">
                  <span className="text-2xl font-bold text-blue-600">
                    ${product.price.toFixed(2)}
                  </span>
                </td>
              ))}
            </tr>

            {/* Rating Row */}
            <tr>
              <td className="px-6 py-4 font-semibold text-gray-700">Rating</td>
              {products.map(product => (
                <td key={product.id} className="px-6 py-4 text-center">
                  {product.rating ? (
                    <div className="flex items-center justify-center gap-1">
                      <span className="text-yellow-500">★</span>
                      <span className="font-medium">{product.rating.toFixed(1)}</span>
                      <span className="text-gray-500 text-sm">
                        ({product.reviews_count || 0})
                      </span>
                    </div>
                  ) : (
                    <span className="text-gray-400">No reviews</span>
                  )}
                </td>
              ))}
            </tr>

            {/* Stock Row */}
            <tr>
              <td className="px-6 py-4 font-semibold text-gray-700">Availability</td>
              {products.map(product => (
                <td key={product.id} className="px-6 py-4 text-center">
                  {product.stock_quantity > 0 ? (
                    <span className="text-green-600 font-medium">
                      In Stock ({product.stock_quantity})
                    </span>
                  ) : (
                    <span className="text-red-600 font-medium">Out of Stock</span>
                  )}
                </td>
              ))}
            </tr>

            {/* Description Row */}
            <tr>
              <td className="px-6 py-4 font-semibold text-gray-700">Description</td>
              {products.map(product => (
                <td key={product.id} className="px-6 py-4 text-sm text-gray-600">
                  {product.description.substring(0, 150)}
                  {product.description.length > 150 && '...'}
                </td>
              ))}
            </tr>

            {/* Specifications Rows */}
            {specKeys.length > 0 && (
              <>
                <tr className="bg-gray-50">
                  <td colSpan={products.length + 1} className="px-6 py-3 font-bold text-gray-900">
                    Specifications
                  </td>
                </tr>
                {specKeys.map(specKey => (
                  <tr key={specKey}>
                    <td className="px-6 py-4 font-medium text-gray-700 capitalize">
                      {specKey.replace(/_/g, ' ')}
                    </td>
                    {products.map(product => {
                      const specs = extractSpecifications(product);
                      return (
                        <td key={product.id} className="px-6 py-4 text-center text-gray-600">
                          {specs[specKey] || '-'}
                        </td>
                      );
                    })}
                  </tr>
                ))}
              </>
            )}

            {/* Action Row */}
            <tr className="bg-gray-50">
              <td className="px-6 py-4 font-semibold text-gray-700">Action</td>
              {products.map(product => (
                <td key={product.id} className="px-6 py-4 text-center">
                  <Link
                    href={`/marketplace/${product.id}`}
                    className="inline-block bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700 transition"
                  >
                    View Details
                  </Link>
                </td>
              ))}
            </tr>
          </tbody>
        </table>
      </div>

      {products.length < 4 && (
        <div className="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4 text-center">
          <p className="text-blue-800">
            You can compare up to 4 products at once. Add {4 - products.length} more product{4 - products.length !== 1 && 's'} to compare.
          </p>
        </div>
      )}
    </div>
  );
}
