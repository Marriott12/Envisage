'use client';

import { useCurrency } from '@/contexts/CurrencyContext';
import { Loader2 } from 'lucide-react';

interface PriceProps {
  amount: number;
  originalCurrency?: string;
  showOriginal?: boolean;
  className?: string;
  originalClassName?: string;
  size?: 'sm' | 'md' | 'lg' | 'xl';
}

export default function Price({
  amount,
  originalCurrency = 'ZMW',
  showOriginal = false,
  className = '',
  originalClassName = '',
  size = 'md',
}: PriceProps) {
  const { selectedCurrency, formatPrice, convertPrice, loading } = useCurrency();

  if (loading) {
    return <Loader2 className="w-4 h-4 animate-spin text-gray-400" />;
  }

  const convertedAmount = convertPrice(amount, originalCurrency);
  const displayPrice = formatPrice(convertedAmount);

  const sizeClasses = {
    sm: 'text-sm',
    md: 'text-base',
    lg: 'text-lg',
    xl: 'text-xl',
  };

  return (
    <div className="inline-flex items-center gap-2">
      <span className={`font-semibold ${sizeClasses[size]} ${className}`}>
        {displayPrice}
      </span>
      {showOriginal && selectedCurrency?.code !== originalCurrency && (
        <span className={`text-xs text-gray-500 ${originalClassName}`}>
          ({formatPrice(amount, originalCurrency)})
        </span>
      )}
    </div>
  );
}

// Variant for displaying discount/original price
interface PriceWithDiscountProps {
  price: number;
  originalPrice?: number;
  originalCurrency?: string;
  showDiscount?: boolean;
  className?: string;
  size?: 'sm' | 'md' | 'lg' | 'xl';
}

export function PriceWithDiscount({
  price,
  originalPrice,
  originalCurrency = 'ZMW',
  showDiscount = true,
  className = '',
  size = 'md',
}: PriceWithDiscountProps) {
  const { formatPrice, convertPrice, loading } = useCurrency();

  if (loading) {
    return <Loader2 className="w-4 h-4 animate-spin text-gray-400" />;
  }

  const convertedPrice = convertPrice(price, originalCurrency);
  const convertedOriginalPrice = originalPrice ? convertPrice(originalPrice, originalCurrency) : null;
  const discount = convertedOriginalPrice 
    ? Math.round(((convertedOriginalPrice - convertedPrice) / convertedOriginalPrice) * 100)
    : 0;

  const sizeClasses = {
    sm: 'text-sm',
    md: 'text-base',
    lg: 'text-lg',
    xl: 'text-2xl',
  };

  const originalSizeClasses = {
    sm: 'text-xs',
    md: 'text-sm',
    lg: 'text-base',
    xl: 'text-lg',
  };

  return (
    <div className={`flex items-center gap-2 flex-wrap ${className}`}>
      <span className={`font-bold text-primary-600 ${sizeClasses[size]}`}>
        {formatPrice(convertedPrice)}
      </span>
      {convertedOriginalPrice && convertedOriginalPrice > convertedPrice && (
        <>
          <span className={`text-gray-500 line-through ${originalSizeClasses[size]}`}>
            {formatPrice(convertedOriginalPrice)}
          </span>
          {showDiscount && discount > 0 && (
            <span className="px-2 py-0.5 bg-red-100 text-red-600 text-xs font-semibold rounded">
              {discount}% OFF
            </span>
          )}
        </>
      )}
    </div>
  );
}

// Range price display (e.g., "$10 - $20")
interface PriceRangeProps {
  minPrice: number;
  maxPrice: number;
  originalCurrency?: string;
  className?: string;
  size?: 'sm' | 'md' | 'lg';
}

export function PriceRange({
  minPrice,
  maxPrice,
  originalCurrency = 'ZMW',
  className = '',
  size = 'md',
}: PriceRangeProps) {
  const { formatPrice, convertPrice, loading } = useCurrency();

  if (loading) {
    return <Loader2 className="w-4 h-4 animate-spin text-gray-400" />;
  }

  const convertedMin = convertPrice(minPrice, originalCurrency);
  const convertedMax = convertPrice(maxPrice, originalCurrency);

  const sizeClasses = {
    sm: 'text-sm',
    md: 'text-base',
    lg: 'text-lg',
  };

  return (
    <span className={`font-semibold ${sizeClasses[size]} ${className}`}>
      {formatPrice(convertedMin)} - {formatPrice(convertedMax)}
    </span>
  );
}
