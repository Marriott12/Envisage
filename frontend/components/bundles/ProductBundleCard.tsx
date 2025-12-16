import React from 'react';
import { Package, Tag } from 'lucide-react';
import Image from 'next/image';
import Link from 'next/link';

interface BundleItem {
  product_id: number;
  quantity: number;
  product: {
    id: number;
    name: string;
    image: string;
    price: number;
  };
}

interface Bundle {
  id: number;
  name: string;
  description: string;
  regular_price: number;
  bundle_price: number;
  discount_percentage: number;
  image?: string;
  items?: BundleItem[];
}

interface ProductBundleCardProps {
  bundle: Bundle;
}

export default function ProductBundleCard({ bundle }: ProductBundleCardProps) {
  const savings = bundle.regular_price - bundle.bundle_price;

  return (
    <div className="bg-white border-2 border-primary-200 rounded-lg overflow-hidden hover:shadow-xl transition-shadow">
      <div className="bg-gradient-to-r from-primary-500 to-primary-600 text-white px-4 py-2 flex items-center gap-2">
        <Package className="w-5 h-5" />
        <span className="font-bold">Bundle Deal</span>
        <span className="ml-auto bg-white text-primary-600 px-2 py-1 rounded-full text-sm font-bold">
          Save {bundle.discount_percentage.toFixed(0)}%
        </span>
      </div>

      <div className="p-4">
        <h3 className="font-bold text-lg mb-2">{bundle.name}</h3>
        <p className="text-gray-600 text-sm mb-4">{bundle.description}</p>

        {/* Bundle Items */}
        {bundle.items && bundle.items.length > 0 && (
          <div className="space-y-2 mb-4">
            <p className="font-semibold text-sm">Includes:</p>
            {bundle.items.map((item, index) => (
              <div key={index} className="flex items-center gap-2 text-sm">
                <div className="w-12 h-12 relative rounded overflow-hidden flex-shrink-0">
                  <Image
                    src={item.product.image || '/placeholder.jpg'}
                    alt={item.product.name}
                    fill
                    className="object-cover"
                  />
                </div>
                <span className="text-gray-700">
                  {item.quantity}x {item.product.name}
                </span>
              </div>
            ))}
          </div>
        )}

        {/* Pricing */}
        <div className="border-t pt-4">
          <div className="flex items-baseline justify-between mb-2">
            <span className="text-gray-500 text-sm">Regular Price:</span>
            <span className="text-gray-500 line-through">
              ${bundle.regular_price.toFixed(2)}
            </span>
          </div>
          <div className="flex items-baseline justify-between mb-3">
            <span className="font-semibold">Bundle Price:</span>
            <span className="text-2xl font-bold text-primary-600">
              ${bundle.bundle_price.toFixed(2)}
            </span>
          </div>
          <div className="bg-green-50 border border-green-200 rounded-lg px-3 py-2 text-center mb-4">
            <span className="text-green-700 font-bold">
              You Save ${savings.toFixed(2)}!
            </span>
          </div>

          <button className="w-full bg-primary-600 text-white py-3 rounded-lg font-bold hover:bg-primary-700 transition-colors flex items-center justify-center gap-2">
            <Tag className="w-5 h-5" />
            Add Bundle to Cart
          </button>
        </div>
      </div>
    </div>
  );
}
