'use client';

import { useCurrency } from '../../contexts/CurrencyContext';
import { ArrowsRightLeftIcon } from '@heroicons/react/24/outline';

interface CurrencyComparisonProps {
  amount: number;
  fromCurrency: string;
  toCurrency?: string;
  showRate?: boolean;
  className?: string;
}

export default function CurrencyComparison({
  amount,
  fromCurrency,
  toCurrency,
  showRate = false,
  className = '',
}: CurrencyComparisonProps) {
  const { selectedCurrency, formatPrice, convertPrice, currencies } = useCurrency();
  
  const targetCurrency = toCurrency || selectedCurrency?.code || 'USD';
  
  if (fromCurrency === targetCurrency) {
    return (
      <span className={className}>
        {formatPrice(amount, fromCurrency)}
      </span>
    );
  }

  const convertedAmount = convertPrice(amount, fromCurrency);
  const fromCurrencyData = currencies.find(c => c.code === fromCurrency);
  const toCurrencyData = currencies.find(c => c.code === targetCurrency);

  const exchangeRate = fromCurrencyData && toCurrencyData
    ? toCurrencyData.exchange_rate / fromCurrencyData.exchange_rate
    : 1;

  return (
    <div className={`inline-flex items-center gap-2 ${className}`}>
      <span className="font-semibold text-gray-900">
        {formatPrice(convertedAmount, targetCurrency)}
      </span>
      <ArrowsRightLeftIcon className="h-4 w-4 text-gray-400" />
      <span className="text-sm text-gray-500">
        {formatPrice(amount, fromCurrency)}
      </span>
      {showRate && (
        <span className="text-xs text-gray-400">
          (1 {fromCurrency} = {exchangeRate.toFixed(4)} {targetCurrency})
        </span>
      )}
    </div>
  );
}

// Currency Badge Component
export function CurrencyBadge({ 
  code, 
  className = '' 
}: { 
  code: string; 
  className?: string;
}) {
  const { currencies } = useCurrency();
  const currency = currencies.find(c => c.code === code);

  if (!currency) {
    return <span className={className}>{code}</span>;
  }

  return (
    <span className={`inline-flex items-center gap-1 px-2 py-1 bg-gray-100 rounded-md text-xs font-medium ${className}`}>
      <span className="text-base">{currency.symbol}</span>
      <span>{currency.code}</span>
    </span>
  );
}

// Price Range Component with Currency
export function PriceRange({
  minPrice,
  maxPrice,
  currency = 'USD',
  className = '',
}: {
  minPrice: number;
  maxPrice: number;
  currency?: string;
  className?: string;
}) {
  const { formatPrice, convertPrice } = useCurrency();

  const minConverted = convertPrice(minPrice, currency);
  const maxConverted = convertPrice(maxPrice, currency);

  return (
    <span className={`font-medium ${className}`}>
      {formatPrice(minConverted)} - {formatPrice(maxConverted)}
    </span>
  );
}

// Currency Indicator (shows if price is in different currency)
export function CurrencyIndicator({ 
  originalCurrency 
}: { 
  originalCurrency: string 
}) {
  const { selectedCurrency } = useCurrency();

  if (!selectedCurrency || selectedCurrency.code === originalCurrency) {
    return null;
  }

  return (
    <span className="inline-flex items-center gap-1 text-xs text-blue-600 bg-blue-50 px-2 py-0.5 rounded">
      <ArrowsRightLeftIcon className="h-3 w-3" />
      Converted from {originalCurrency}
    </span>
  );
}
