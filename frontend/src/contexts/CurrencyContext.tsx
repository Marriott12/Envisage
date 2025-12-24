'use client';

import React, { createContext, useContext, useState, useEffect, ReactNode } from 'react';
import axios from 'axios';
import { toast } from 'react-hot-toast';

const API_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000/api';

interface Currency {
  id: number;
  code: string;
  name: string;
  symbol: string;
  exchange_rate: number;
  rate?: number; // Alias for exchange_rate
  format: string;
  is_active: boolean;
  is_base?: boolean;
  decimal_places: number;
}

interface CurrencyContextType {
  selectedCurrency: Currency | null;
  currencies: Currency[];
  loading: boolean;
  setSelectedCurrency: (currency: Currency) => void;
  formatPrice: (amount: number, currencyCode?: string) => string;
  convertPrice: (amount: number, fromCurrency?: string) => number;
}

const CurrencyContext = createContext<CurrencyContextType | undefined>(undefined);

export function CurrencyProvider({ children }: { children: ReactNode }) {
  const [selectedCurrency, setSelectedCurrencyState] = useState<Currency | null>(null);
  const [currencies, setCurrencies] = useState<Currency[]>([]);
  const [loading, setLoading] = useState(true);

  // Fetch available currencies on mount
  useEffect(() => {
    fetchCurrencies();
    loadUserPreference();
  }, []);

  const fetchCurrencies = async () => {
    try {
      // Use the new API endpoint
      const response = await axios.get(`${API_URL}/currencies`);
      if (response.data.success || response.data.status === 'success') {
        const currencyData = response.data.data || response.data;
        setCurrencies(currencyData);
        
        // Set default currency (USD as base) if none selected
        if (!selectedCurrency) {
          const baseCurrency = currencyData.find((c: Currency) => c.is_base || c.code === 'USD') 
                             || currencyData[0];
          setSelectedCurrencyState(baseCurrency);
        }
      }
    } catch (error) {
      console.error('Failed to fetch currencies:', error);
      // Set USD as fallback
      setSelectedCurrencyState({
        id: 1,
        code: 'USD',
        name: 'US Dollar',
        symbol: '$',
        exchange_rate: 1,
        format: '{symbol}{amount}',
        is_active: true,
        decimal_places: 2,
      });
    } finally {
      setLoading(false);
    }
  };

  const loadUserPreference = async () => {
    try {
      const token = localStorage.getItem('token');
      if (!token) {
        // Load from localStorage for guest users
        const savedCurrency = localStorage.getItem('preferred_currency');
        if (savedCurrency) {
          const currency = JSON.parse(savedCurrency);
          setSelectedCurrencyState(currency);
        }
        return;
      }

      const response = await axios.get(`${API_URL}/currencies/user-preference`, {
        headers: { Authorization: `Bearer ${token}` },
      });

      if (response.data.status === 'success' && response.data.data) {
        setSelectedCurrencyState(response.data.data);
      }
    } catch (error) {
      console.error('Failed to load currency preference:', error);
    }
  };

  const setSelectedCurrency = async (currency: Currency) => {
    setSelectedCurrencyState(currency);
    
    // Save to localStorage for guests
    localStorage.setItem('preferred_currency', JSON.stringify(currency));

    // Save to backend for authenticated users
    try {
      const token = localStorage.getItem('token');
      if (token) {
        await axios.put(
          `${API_URL}/currencies/user-preference`,
          { currency_code: currency.code },
          { headers: { Authorization: `Bearer ${token}` } }
        );
      }
    } catch (error) {
      console.error('Failed to save currency preference:', error);
    }
  };

  const formatPrice = (amount: number, currencyCode?: string): string => {
    const currency = currencyCode 
      ? currencies.find(c => c.code === currencyCode) || selectedCurrency
      : selectedCurrency;

    if (!currency) return `ZMW ${amount.toFixed(2)}`;

    const formattedAmount = amount.toFixed(currency.decimal_places);
    return currency.format
      .replace('{symbol}', currency.symbol)
      .replace('{amount}', formattedAmount);
  };

  const convertPrice = (amount: number, fromCurrency: string = 'ZMW'): number => {
    if (!selectedCurrency || fromCurrency === selectedCurrency.code) {
      return amount;
    }

    // Find the source currency
    const sourceCurrency = currencies.find(c => c.code === fromCurrency);
    if (!sourceCurrency) return amount;

    // Convert from source to base (ZMW), then to target
    const amountInBase = amount / sourceCurrency.exchange_rate;
    const convertedAmount = amountInBase * selectedCurrency.exchange_rate;

    return convertedAmount;
  };

  return (
    <CurrencyContext.Provider
      value={{
        selectedCurrency,
        currencies,
        loading,
        setSelectedCurrency,
        formatPrice,
        convertPrice,
      }}
    >
      {children}
    </CurrencyContext.Provider>
  );
}

export function useCurrency() {
  const context = useContext(CurrencyContext);
  if (context === undefined) {
    throw new Error('useCurrency must be used within a CurrencyProvider');
  }
  return context;
}
