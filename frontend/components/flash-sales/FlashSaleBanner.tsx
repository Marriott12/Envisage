import React, { useEffect, useState } from 'react';
import { Zap, Tag } from 'lucide-react';
import CountdownTimer from '@/components/CountdownTimer';
import api from '@/lib/api';
import Link from 'next/link';
import Image from 'next/image';

interface FlashSale {
  id: number;
  name: string;
  slug: string;
  ends_at: string;
  banner_image?: string;
  products: Array<{
    id: number;
    product_id: number;
    sale_price: number;
    original_price: number;
    discount_percentage: number;
    quantity_available: number;
    quantity_sold: number;
    product: {
      id: number;
      name: string;
      image: string;
      slug: string;
    };
  }>;
}

export default function FlashSaleBanner() {
  const [flashSale, setFlashSale] = useState<FlashSale | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    fetchActiveFlashSale();
  }, []);

  const fetchActiveFlashSale = async () => {
    try {
      const response = await api.get('/flash-sales/active');
      if (response.data && response.data.length > 0) {
        setFlashSale(response.data[0]);
      }
    } catch (error) {
      console.error('Failed to fetch flash sale:', error);
    } finally {
      setLoading(false);
    }
  };

  if (loading || !flashSale) return null;

  return (
    <div className="bg-gradient-to-r from-orange-500 via-red-500 to-pink-500 text-white rounded-lg overflow-hidden shadow-2xl mb-8">
      <div className="p-6">
        <div className="flex items-center justify-between mb-4">
          <div className="flex items-center gap-3">
            <Zap className="w-8 h-8 animate-pulse" />
            <div>
              <h2 className="text-3xl font-bold">{flashSale.name}</h2>
              <p className="text-white/90">Limited time offers - Don't miss out!</p>
            </div>
          </div>
          <CountdownTimer 
            endDate={flashSale.ends_at}
            onExpire={() => setFlashSale(null)}
            className="bg-black/30 px-4 py-2 rounded-lg"
          />
        </div>

        <div className="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4 mt-6">
          {flashSale.products.slice(0, 6).map((item) => {
            const stockPercentage = ((item.quantity_available - item.quantity_sold) / item.quantity_available) * 100;
            
            return (
              <Link 
                key={item.id}
                href={`/marketplace/${item.product.slug}`}
                className="bg-white text-gray-900 rounded-lg overflow-hidden hover:scale-105 transition-transform"
              >
                <div className="relative aspect-square">
                  <Image
                    src={item.product.image || '/placeholder.jpg'}
                    alt={item.product.name}
                    fill
                    className="object-cover"
                  />
                  <div className="absolute top-2 right-2 bg-red-500 text-white px-2 py-1 rounded-full text-sm font-bold">
                    -{Math.round(item.discount_percentage)}%
                  </div>
                </div>
                <div className="p-3">
                  <h3 className="font-semibold text-sm line-clamp-2 mb-2">
                    {item.product.name}
                  </h3>
                  <div className="flex items-center justify-between mb-2">
                    <span className="text-lg font-bold text-red-600">
                      ${item.sale_price}
                    </span>
                    <span className="text-sm text-gray-500 line-through">
                      ${item.original_price}
                    </span>
                  </div>
                  {/* Stock bar */}
                  <div className="w-full bg-gray-200 rounded-full h-2 mb-1">
                    <div 
                      className="bg-gradient-to-r from-red-500 to-orange-500 h-2 rounded-full"
                      style={{ width: `${100 - stockPercentage}%` }}
                    />
                  </div>
                  <p className="text-xs text-gray-600">
                    {item.quantity_available - item.quantity_sold} left
                  </p>
                </div>
              </Link>
            );
          })}
        </div>

        <div className="text-center mt-6">
          <Link 
            href={`/flash-sales/${flashSale.slug}`}
            className="inline-flex items-center gap-2 bg-white text-red-600 px-6 py-3 rounded-lg font-bold hover:bg-gray-100 transition-colors"
          >
            <Tag className="w-5 h-5" />
            View All {flashSale.products.length} Deals
          </Link>
        </div>
      </div>
    </div>
  );
}
