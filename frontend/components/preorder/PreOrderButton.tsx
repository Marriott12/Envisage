'use client';

import React from 'react';
import { ClockIcon, TruckIcon, BellIcon } from '@heroicons/react/24/outline';
import Image from 'next/image';

interface PreOrderProduct {
  id: number;
  name: string;
  price: number;
  images: string[];
  expected_ship_date: string;
  preorder_limit: number | null;
  orders_count: number;
  charge_now: boolean;
  deposit_amount?: number;
}

interface PreOrderButtonProps {
  product: PreOrderProduct;
  onPreOrder: (productId: number) => void;
  loading?: boolean;
}

export default function PreOrderButton({ product, onPreOrder, loading = false }: PreOrderButtonProps) {
  const isLimitReached = product.preorder_limit !== null && 
    product.orders_count >= product.preorder_limit;

  const daysUntilShip = Math.ceil(
    (new Date(product.expected_ship_date).getTime() - new Date().getTime()) / (1000 * 60 * 60 * 24)
  );

  const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('en-US', {
      month: 'long',
      day: 'numeric',
      year: 'numeric'
    });
  };

  return (
    <div className="space-y-4">
      {/* Pre-order Badge */}
      <div className="bg-gradient-to-r from-purple-500 to-pink-500 text-white px-4 py-2 rounded-lg inline-block">
        <span className="font-bold">PRE-ORDER</span>
      </div>

      {/* Expected Ship Date */}
      <div className="bg-blue-50 border border-blue-200 rounded-lg p-4">
        <div className="flex items-start gap-3">
          <TruckIcon className="w-5 h-5 text-blue-600 flex-shrink-0 mt-0.5" />
          <div>
            <p className="text-sm font-semibold text-blue-900 mb-1">
              Expected to ship:
            </p>
            <p className="text-blue-800">
              {formatDate(product.expected_ship_date)}
            </p>
            <p className="text-sm text-blue-700 mt-1">
              Approximately {daysUntilShip} days from now
            </p>
          </div>
        </div>
      </div>

      {/* Availability Info */}
      {product.preorder_limit && (
        <div className="flex items-center justify-between text-sm">
          <span className="text-gray-600">Pre-orders available:</span>
          <span className={`font-semibold ${
            isLimitReached ? 'text-red-600' : 'text-gray-900'
          }`}>
            {product.preorder_limit - product.orders_count} of {product.preorder_limit}
          </span>
        </div>
      )}

      {/* Payment Info */}
      <div className="bg-gray-50 border border-gray-200 rounded-lg p-4">
        <div className="flex items-start gap-3">
          <ClockIcon className="w-5 h-5 text-gray-600 flex-shrink-0 mt-0.5" />
          <div className="flex-1">
            <p className="text-sm font-semibold text-gray-900 mb-2">
              Payment Information:
            </p>
            {product.charge_now ? (
              <div className="space-y-1 text-sm text-gray-700">
                <p>• Full payment of ${product.price.toFixed(2)} required now</p>
                <p>• Secure your spot in the queue</p>
                <p>• No additional charges at shipment</p>
              </div>
            ) : product.deposit_amount ? (
              <div className="space-y-1 text-sm text-gray-700">
                <p>• Deposit: ${product.deposit_amount.toFixed(2)} now</p>
                <p>• Remaining: ${(product.price - product.deposit_amount).toFixed(2)} at shipment</p>
                <p>• Reserve your order with a deposit</p>
              </div>
            ) : (
              <div className="space-y-1 text-sm text-gray-700">
                <p>• No charge until shipment</p>
                <p>• Reserve your spot for free</p>
                <p>• Payment processed when ready to ship</p>
              </div>
            )}
          </div>
        </div>
      </div>

      {/* Pre-Order Button */}
      <button
        onClick={() => onPreOrder(product.id)}
        disabled={loading || isLimitReached}
        className={`w-full py-4 rounded-lg font-bold text-lg transition-all ${
          isLimitReached
            ? 'bg-gray-300 text-gray-500 cursor-not-allowed'
            : 'bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white shadow-lg hover:shadow-xl'
        }`}
      >
        {loading ? (
          'Processing...'
        ) : isLimitReached ? (
          'Pre-Order Limit Reached'
        ) : product.charge_now ? (
          `Pre-Order Now - $${product.price.toFixed(2)}`
        ) : product.deposit_amount ? (
          `Reserve with $${product.deposit_amount.toFixed(2)} Deposit`
        ) : (
          'Reserve Your Pre-Order - Free'
        )}
      </button>

      {/* Benefits */}
      <div className="bg-green-50 border border-green-200 rounded-lg p-4">
        <div className="flex items-start gap-3">
          <BellIcon className="w-5 h-5 text-green-600 flex-shrink-0 mt-0.5" />
          <div>
            <p className="text-sm font-semibold text-green-900 mb-2">
              Pre-Order Benefits:
            </p>
            <ul className="text-sm text-green-800 space-y-1">
              <li>✓ Guaranteed allocation</li>
              <li>✓ Priority shipping</li>
              <li>✓ Email updates on status</li>
              <li>✓ Cancel anytime before shipment</li>
            </ul>
          </div>
        </div>
      </div>

      {/* Disclaimer */}
      <p className="text-xs text-gray-500 text-center">
        Expected ship date is an estimate and subject to change. You'll be notified of any delays.
      </p>
    </div>
  );
}
