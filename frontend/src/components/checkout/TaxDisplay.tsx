'use client';

import { useState, useEffect } from 'react';
import { taxApi } from '../../../lib/highPriorityApi';
import { InformationCircleIcon } from '@heroicons/react/24/outline';
import { useCurrency } from '../../contexts/CurrencyContext';

interface TaxBreakdown {
  tax_type: string;
  rate: number;
  amount: number;
  applies_to: string;
}

interface TaxCalculationResult {
  subtotal: number;
  shipping: number;
  tax_amount: number;
  total: number;
  breakdown: TaxBreakdown[];
}

interface CartItem {
  id: number;
  product_id: number;
  price: number;
  quantity: number;
  category_id?: number;
  is_digital?: boolean;
}

interface TaxDisplayProps {
  items: CartItem[];
  shipping: number;
  shippingAddress?: {
    country: string;
    state?: string;
    city?: string;
    zip_code?: string;
  };
  onTaxCalculated?: (tax: number, total: number) => void;
}

export default function TaxDisplay({
  items,
  shipping,
  shippingAddress,
  onTaxCalculated,
}: TaxDisplayProps) {
  const [taxResult, setTaxResult] = useState<TaxCalculationResult | null>(null);
  const [loading, setLoading] = useState(false);
  const [showBreakdown, setShowBreakdown] = useState(false);
  const { formatPrice } = useCurrency();

  useEffect(() => {
    if (shippingAddress?.country && items.length > 0) {
      calculateTax();
    } else {
      setTaxResult(null);
    }
  }, [items, shipping, shippingAddress]);

  const calculateTax = async () => {
    if (!shippingAddress) return;

    try {
      setLoading(true);
      
      const taxItems = items.map(item => ({
        amount: item.price * item.quantity,
        category_id: item.category_id,
        is_digital: item.is_digital || false,
      }));

      const response = await taxApi.calculate({
        country: shippingAddress.country,
        state: shippingAddress.state,
        city: shippingAddress.city,
        zip_code: shippingAddress.zip_code,
        items: taxItems,
        shipping,
      });

      if (response.success) {
        setTaxResult(response.data);
        if (onTaxCalculated) {
          onTaxCalculated(response.data.tax_amount, response.data.total);
        }
      }
    } catch (error) {
      console.error('Tax calculation failed:', error);
      // Use 0 tax on error
      const subtotal = items.reduce((sum, item) => sum + (item.price * item.quantity), 0);
      setTaxResult({
        subtotal,
        shipping,
        tax_amount: 0,
        total: subtotal + shipping,
        breakdown: [],
      });
      if (onTaxCalculated) {
        onTaxCalculated(0, subtotal + shipping);
      }
    } finally {
      setLoading(false);
    }
  };

  if (!shippingAddress?.country) {
    return (
      <div className="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
        <div className="flex items-start">
          <InformationCircleIcon className="h-5 w-5 text-yellow-600 mt-0.5" />
          <p className="ml-2 text-sm text-yellow-800">
            Please provide a shipping address to calculate tax
          </p>
        </div>
      </div>
    );
  }

  if (loading) {
    return (
      <div className="space-y-2">
        <div className="flex justify-between text-sm">
          <span className="text-gray-600">Calculating tax...</span>
          <div className="h-4 w-16 bg-gray-200 rounded animate-pulse"></div>
        </div>
      </div>
    );
  }

  if (!taxResult) {
    return null;
  }

  return (
    <div className="space-y-3">
      {/* Subtotal */}
      <div className="flex justify-between text-sm">
        <span className="text-gray-600">Subtotal</span>
        <span className="font-medium text-gray-900">
          {formatPrice(taxResult.subtotal)}
        </span>
      </div>

      {/* Shipping */}
      {shipping > 0 && (
        <div className="flex justify-between text-sm">
          <span className="text-gray-600">Shipping</span>
          <span className="font-medium text-gray-900">
            {formatPrice(taxResult.shipping)}
          </span>
        </div>
      )}

      {/* Tax */}
      <div className="flex justify-between text-sm border-t pt-2">
        <div className="flex items-center gap-1">
          <span className="text-gray-600">Tax</span>
          {taxResult.breakdown.length > 0 && (
            <button
              onClick={() => setShowBreakdown(!showBreakdown)}
              className="text-primary-600 hover:text-primary-700"
              title="Show tax breakdown"
            >
              <InformationCircleIcon className="h-4 w-4" />
            </button>
          )}
        </div>
        <span className="font-medium text-gray-900">
          {taxResult.tax_amount > 0 ? formatPrice(taxResult.tax_amount) : 'Calculated at checkout'}
        </span>
      </div>

      {/* Tax Breakdown */}
      {showBreakdown && taxResult.breakdown.length > 0 && (
        <div className="bg-gray-50 rounded-lg p-3 space-y-2 border border-gray-200">
          <p className="text-xs font-medium text-gray-700 uppercase">Tax Breakdown</p>
          {taxResult.breakdown.map((tax, index) => (
            <div key={index} className="flex justify-between text-xs">
              <div className="text-gray-600">
                <span className="capitalize">{tax.tax_type.replace('_', ' ')}</span>
                <span className="text-gray-400 ml-1">({tax.rate}%)</span>
                {tax.applies_to && (
                  <span className="text-gray-400 ml-1">on {tax.applies_to}</span>
                )}
              </div>
              <span className="font-medium text-gray-900">
                {formatPrice(tax.amount)}
              </span>
            </div>
          ))}
        </div>
      )}

      {/* Total */}
      <div className="flex justify-between text-base font-bold border-t-2 pt-3">
        <span className="text-gray-900">Total</span>
        <span className="text-primary-600">
          {formatPrice(taxResult.total)}
        </span>
      </div>

      {/* Tax Note */}
      {taxResult.tax_amount === 0 && taxResult.breakdown.length === 0 && (
        <p className="text-xs text-gray-500 italic">
          No tax applicable for this location
        </p>
      )}
    </div>
  );
}

// Simplified version for order summary
export function OrderTaxSummary({ 
  subtotal, 
  tax, 
  shipping, 
  total,
  taxBreakdown 
}: {
  subtotal: number;
  tax: number;
  shipping: number;
  total: number;
  taxBreakdown?: TaxBreakdown[];
}) {
  const [showBreakdown, setShowBreakdown] = useState(false);
  const { formatPrice } = useCurrency();

  return (
    <div className="space-y-2">
      <div className="flex justify-between text-sm">
        <span className="text-gray-600">Subtotal</span>
        <span className="font-medium">{formatPrice(subtotal)}</span>
      </div>
      
      {shipping > 0 && (
        <div className="flex justify-between text-sm">
          <span className="text-gray-600">Shipping</span>
          <span className="font-medium">{formatPrice(shipping)}</span>
        </div>
      )}
      
      <div className="flex justify-between text-sm border-t pt-2">
        <div className="flex items-center gap-1">
          <span className="text-gray-600">Tax</span>
          {taxBreakdown && taxBreakdown.length > 0 && (
            <button
              onClick={() => setShowBreakdown(!showBreakdown)}
              className="text-primary-600 hover:text-primary-700"
            >
              <InformationCircleIcon className="h-4 w-4" />
            </button>
          )}
        </div>
        <span className="font-medium">{formatPrice(tax)}</span>
      </div>

      {showBreakdown && taxBreakdown && taxBreakdown.length > 0 && (
        <div className="bg-gray-50 rounded p-2 space-y-1">
          {taxBreakdown.map((item, index) => (
            <div key={index} className="flex justify-between text-xs text-gray-600">
              <span>{item.tax_type} ({item.rate}%)</span>
              <span>{formatPrice(item.amount)}</span>
            </div>
          ))}
        </div>
      )}
      
      <div className="flex justify-between text-lg font-bold border-t-2 pt-2">
        <span>Total</span>
        <span className="text-primary-600">{formatPrice(total)}</span>
      </div>
    </div>
  );
}
