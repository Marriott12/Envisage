'use client';

import { useState } from 'react';
import { X, Check, Minus, Plus, ShoppingCart } from 'lucide-react';
import Image from 'next/image';

interface Product {
  id: string;
  title: string;
  price: number;
  image: string;
  rating: number;
  reviews: number;
  inStock: boolean;
  specifications?: Record<string, string | number>;
  features?: string[];
}

interface ProductComparisonProps {
  products: Product[];
  onRemove?: (productId: string) => void;
  onAddToCart?: (productId: string) => void;
  maxProducts?: number;
  className?: string;
}

export function ProductComparison({
  products,
  onRemove,
  onAddToCart,
  maxProducts = 4,
  className = '',
}: ProductComparisonProps) {
  const [highlightDifferences, setHighlightDifferences] = useState(false);

  if (products.length === 0) {
    return (
      <div className="flex flex-col items-center justify-center p-12 bg-gray-50 rounded-lg">
        <p className="text-lg text-gray-600 mb-2">No products to compare</p>
        <p className="text-sm text-gray-500">Add products to start comparing</p>
      </div>
    );
  }

  // Get all unique specification keys
  const allSpecKeys = Array.from(
    new Set(
      products.flatMap((p) => Object.keys(p.specifications || {}))
    )
  ).sort();

  // Get all unique features
  const allFeatures = Array.from(
    new Set(products.flatMap((p) => p.features || []))
  ).sort();

  // Check if a spec value differs across products
  const hasDifferentValues = (key: string) => {
    const values = products.map((p) => p.specifications?.[key]);
    return new Set(values).size > 1;
  };

  return (
    <div className={`bg-white rounded-lg shadow-lg overflow-hidden ${className}`}>
      {/* Header */}
      <div className="flex items-center justify-between p-4 border-b bg-gray-50">
        <h2 className="text-xl font-semibold">
          Product Comparison ({products.length}/{maxProducts})
        </h2>
        <label className="flex items-center gap-2 text-sm cursor-pointer">
          <input
            type="checkbox"
            checked={highlightDifferences}
            onChange={(e) => setHighlightDifferences(e.target.checked)}
            className="w-4 h-4 text-blue-600 rounded"
          />
          Highlight differences
        </label>
      </div>

      {/* Comparison Table */}
      <div className="overflow-x-auto">
        <table className="w-full">
          <thead>
            <tr className="border-b">
              <th className="sticky left-0 bg-white p-4 text-left font-medium text-gray-700 min-w-[150px] z-10">
                Product
              </th>
              {products.map((product) => (
                <th key={product.id} className="p-4 min-w-[250px]">
                  <div className="relative">
                    {/* Remove button */}
                    {onRemove && (
                      <button
                        onClick={() => onRemove(product.id)}
                        className="absolute -top-2 -right-2 p-1 bg-red-500 text-white rounded-full hover:bg-red-600 transition-colors"
                        aria-label={`Remove ${product.title}`}
                      >
                        <X className="w-4 h-4" />
                      </button>
                    )}

                    {/* Product Image */}
                    <div className="relative w-full h-40 mb-3 bg-gray-50 rounded">
                      <Image
                        src={product.image}
                        alt={product.title}
                        fill
                        className="object-contain"
                      />
                    </div>

                    {/* Product Title */}
                    <h3 className="font-medium text-sm mb-2 line-clamp-2">
                      {product.title}
                    </h3>
                  </div>
                </th>
              ))}
            </tr>
          </thead>

          <tbody>
            {/* Price Row */}
            <tr className="border-b hover:bg-gray-50">
              <td className="sticky left-0 bg-white p-4 font-medium text-gray-700 z-10">
                Price
              </td>
              {products.map((product) => (
                <td key={product.id} className="p-4 text-center">
                  <span className="text-2xl font-bold text-gray-900">
                    ${product.price.toFixed(2)}
                  </span>
                </td>
              ))}
            </tr>

            {/* Rating Row */}
            <tr className="border-b hover:bg-gray-50">
              <td className="sticky left-0 bg-white p-4 font-medium text-gray-700 z-10">
                Rating
              </td>
              {products.map((product) => (
                <td key={product.id} className="p-4 text-center">
                  <div className="flex items-center justify-center gap-2">
                    <span className="text-yellow-500 font-medium">
                      ★ {product.rating.toFixed(1)}
                    </span>
                    <span className="text-sm text-gray-500">
                      ({product.reviews})
                    </span>
                  </div>
                </td>
              ))}
            </tr>

            {/* Availability Row */}
            <tr className="border-b hover:bg-gray-50">
              <td className="sticky left-0 bg-white p-4 font-medium text-gray-700 z-10">
                Availability
              </td>
              {products.map((product) => (
                <td key={product.id} className="p-4 text-center">
                  {product.inStock ? (
                    <span className="inline-flex items-center gap-1 text-green-600 font-medium">
                      <Check className="w-4 h-4" />
                      In Stock
                    </span>
                  ) : (
                    <span className="text-red-600 font-medium">Out of Stock</span>
                  )}
                </td>
              ))}
            </tr>

            {/* Specifications */}
            {allSpecKeys.length > 0 && (
              <>
                <tr className="bg-gray-50">
                  <td
                    colSpan={products.length + 1}
                    className="p-3 font-semibold text-gray-900"
                  >
                    Specifications
                  </td>
                </tr>
                {allSpecKeys.map((key) => {
                  const isDifferent = hasDifferentValues(key);
                  const shouldHighlight = highlightDifferences && isDifferent;

                  return (
                    <tr
                      key={key}
                      className={`border-b hover:bg-gray-50 ${
                        shouldHighlight ? 'bg-yellow-50' : ''
                      }`}
                    >
                      <td className="sticky left-0 bg-white p-4 font-medium text-gray-700 z-10">
                        {key}
                      </td>
                      {products.map((product) => (
                        <td
                          key={product.id}
                          className={`p-4 text-center ${
                            shouldHighlight ? 'bg-yellow-50' : ''
                          }`}
                        >
                          {product.specifications?.[key] || (
                            <span className="text-gray-400">-</span>
                          )}
                        </td>
                      ))}
                    </tr>
                  );
                })}
              </>
            )}

            {/* Features */}
            {allFeatures.length > 0 && (
              <>
                <tr className="bg-gray-50">
                  <td
                    colSpan={products.length + 1}
                    className="p-3 font-semibold text-gray-900"
                  >
                    Features
                  </td>
                </tr>
                {allFeatures.map((feature) => (
                  <tr key={feature} className="border-b hover:bg-gray-50">
                    <td className="sticky left-0 bg-white p-4 text-gray-700 z-10">
                      {feature}
                    </td>
                    {products.map((product) => (
                      <td key={product.id} className="p-4 text-center">
                        {product.features?.includes(feature) ? (
                          <Check className="w-5 h-5 text-green-600 mx-auto" />
                        ) : (
                          <Minus className="w-5 h-5 text-gray-300 mx-auto" />
                        )}
                      </td>
                    ))}
                  </tr>
                ))}
              </>
            )}

            {/* Action Row */}
            {onAddToCart && (
              <tr className="bg-gray-50">
                <td className="sticky left-0 bg-gray-50 p-4 z-10"></td>
                {products.map((product) => (
                  <td key={product.id} className="p-4 text-center">
                    <button
                      onClick={() => onAddToCart(product.id)}
                      disabled={!product.inStock}
                      className="w-full flex items-center justify-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:bg-gray-300 disabled:cursor-not-allowed transition-colors"
                    >
                      <ShoppingCart className="w-4 h-4" />
                      Add to Cart
                    </button>
                  </td>
                ))}
              </tr>
            )}
          </tbody>
        </table>
      </div>
    </div>
  );
}

// Compact comparison view
export function CompactProductComparison({
  products,
  onRemove,
  className = '',
}: Pick<ProductComparisonProps, 'products' | 'onRemove' | 'className'>) {
  return (
    <div className={`bg-white rounded-lg shadow-md p-4 ${className}`}>
      <h3 className="font-semibold mb-3">Comparing {products.length} products</h3>
      <div className="space-y-2">
        {products.map((product) => (
          <div
            key={product.id}
            className="flex items-center justify-between p-2 bg-gray-50 rounded"
          >
            <div className="flex items-center gap-3">
              <div className="relative w-12 h-12">
                <Image
                  src={product.image}
                  alt={product.title}
                  fill
                  className="object-contain rounded"
                />
              </div>
              <div>
                <p className="text-sm font-medium line-clamp-1">{product.title}</p>
                <p className="text-sm text-gray-600">${product.price}</p>
              </div>
            </div>
            {onRemove && (
              <button
                onClick={() => onRemove(product.id)}
                className="p-1 hover:bg-gray-200 rounded"
                aria-label={`Remove ${product.title}`}
              >
                <X className="w-4 h-4" />
              </button>
            )}
          </div>
        ))}
      </div>
    </div>
  );
}
