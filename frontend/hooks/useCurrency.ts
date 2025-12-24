'use client';

import { useState, useEffect } from 'react';
import { currencyApi } from '../lib/highPriorityApi';
import { useCurrency } from '../src/contexts/CurrencyContext';

interface ExchangeRate {
  from: string;
  to: string;
  rate: number;
  updated_at: string;
}

export function useCurrencyConverter() {
  const { selectedCurrency, formatPrice, convertPrice } = useCurrency();

  const convert = async (amount: number, from: string, to?: string) => {
    const targetCurrency = to || selectedCurrency?.code || 'USD';
    
    if (from === targetCurrency) {
      return { amount, formatted: formatPrice(amount, from) };
    }

    try {
      const response = await currencyApi.convert(amount, from, targetCurrency);
      if (response.success) {
        return {
          amount: response.data.converted_amount,
          formatted: response.data.formatted,
          rate: response.data.exchange_rate,
        };
      }
    } catch (error) {
      console.error('Currency conversion failed:', error);
    }

    // Fallback to local conversion
    const converted = convertPrice(amount, from);
    return { amount: converted, formatted: formatPrice(converted) };
  };

  return { convert, selectedCurrency, formatPrice };
}

export function useCurrencyRates(baseCurrency: string = 'USD') {
  const [rates, setRates] = useState<ExchangeRate[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    fetchRates();
  }, [baseCurrency]);

  const fetchRates = async () => {
    try {
      setLoading(true);
      setError(null);
      const response = await currencyApi.getRates(baseCurrency);
      
      if (response.success) {
        setRates(response.data);
      }
    } catch (err: any) {
      setError(err.message || 'Failed to fetch exchange rates');
    } finally {
      setLoading(false);
    }
  };

  return { rates, loading, error, refetch: fetchRates };
}

export function useUserCurrencyPreference() {
  const { selectedCurrency, setSelectedCurrency } = useCurrency();
  const [saving, setSaving] = useState(false);

  const updatePreference = async (currencyCode: string) => {
    try {
      setSaving(true);
      const response = await currencyApi.setUserPreference(currencyCode);
      
      if (response.success) {
        // Currency context will handle the update
        return true;
      }
      return false;
    } catch (error) {
      console.error('Failed to update currency preference:', error);
      return false;
    } finally {
      setSaving(false);
    }
  };

  return {
    currentCurrency: selectedCurrency,
    updatePreference,
    saving,
  };
}
