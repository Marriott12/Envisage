'use client';

import { createContext, useContext, useState, useEffect, ReactNode } from 'react';

type Locale = 'en' | 'es' | 'fr' | 'de' | 'ar';

interface LocaleContextType {
  locale: Locale;
  setLocale: (locale: Locale) => void;
  t: (key: string) => string;
  formatCurrency: (amount: number) => string;
  formatDate: (date: Date) => string;
  isRTL: boolean;
}

const LocaleContext = createContext<LocaleContextType | undefined>(undefined);

// Currency symbols
const currencySymbols: Record<Locale, string> = {
  en: '$',
  es: '€',
  fr: '€',
  de: '€',
  ar: 'ر.س',
};

// RTL languages
const rtlLanguages: Locale[] = ['ar'];

export function LocaleProvider({ children }: { children: ReactNode }) {
  const [locale, setLocaleState] = useState<Locale>('en');
  const [messages, setMessages] = useState<any>({});

  // Load translations
  useEffect(() => {
    const loadTranslations = async () => {
      try {
        const response = await fetch(`/locales/${locale}/common.json`);
        const data = await response.json();
        setMessages(data);
      } catch (error) {
        console.error('Failed to load translations:', error);
      }
    };

    loadTranslations();
  }, [locale]);

  // Apply RTL if needed
  useEffect(() => {
    const isRTL = rtlLanguages.includes(locale);
    document.documentElement.dir = isRTL ? 'rtl' : 'ltr';
    document.documentElement.lang = locale;
  }, [locale]);

  // Save locale preference
  const setLocale = (newLocale: Locale) => {
    setLocaleState(newLocale);
    localStorage.setItem('locale', newLocale);
  };

  // Load saved locale
  useEffect(() => {
    const savedLocale = localStorage.getItem('locale') as Locale;
    if (savedLocale && ['en', 'es', 'fr', 'de', 'ar'].includes(savedLocale)) {
      setLocaleState(savedLocale);
    }
  }, []);

  // Translation function
  const t = (key: string): string => {
    const keys = key.split('.');
    let value: any = messages;

    for (const k of keys) {
      if (value && typeof value === 'object' && k in value) {
        value = value[k];
      } else {
        return key; // Return key if translation not found
      }
    }

    return typeof value === 'string' ? value : key;
  };

  // Format currency
  const formatCurrency = (amount: number): string => {
    const symbol = currencySymbols[locale];
    const formatted = amount.toFixed(2);

    if (locale === 'ar') {
      return `${formatted} ${symbol}`;
    }

    return `${symbol}${formatted}`;
  };

  // Format date
  const formatDate = (date: Date): string => {
    return new Intl.DateTimeFormat(locale, {
      year: 'numeric',
      month: 'long',
      day: 'numeric',
    }).format(date);
  };

  const isRTL = rtlLanguages.includes(locale);

  return (
    <LocaleContext.Provider
      value={{ locale, setLocale, t, formatCurrency, formatDate, isRTL }}
    >
      {children}
    </LocaleContext.Provider>
  );
}

export function useLocale() {
  const context = useContext(LocaleContext);
  if (!context) {
    throw new Error('useLocale must be used within LocaleProvider');
  }
  return context;
}

export default { LocaleProvider, useLocale };
