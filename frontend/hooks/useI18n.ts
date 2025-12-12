/**
 * Translation hook for accessing localized strings
 * 
 * @example
 * ```tsx
 * const { t } = useTranslation();
 * return <h1>{t('common.welcome')}</h1>;
 * ```
 */
'use client';

import { useLocale } from '@/components/i18n/LocaleProvider';

export function useTranslation() {
  const { t, locale } = useLocale();

  return {
    t,
    locale,
  };
}

/**
 * Currency formatting hook
 * 
 * @example
 * ```tsx
 * const { formatCurrency } = useCurrency();
 * return <p>{formatCurrency(99.99)}</p>; // $99.99
 * ```
 */
export function useCurrency() {
  const { formatCurrency, locale } = useLocale();

  return {
    formatCurrency,
    locale,
  };
}

/**
 * Date formatting hook
 * 
 * @example
 * ```tsx
 * const { formatDate } = useDate();
 * return <p>{formatDate(new Date())}</p>; // December 12, 2025
 * ```
 */
export function useDate() {
  const { formatDate, locale } = useLocale();

  const formatRelative = (date: Date): string => {
    const now = new Date();
    const diff = now.getTime() - date.getTime();
    const seconds = Math.floor(diff / 1000);
    const minutes = Math.floor(seconds / 60);
    const hours = Math.floor(minutes / 60);
    const days = Math.floor(hours / 24);

    if (seconds < 60) return 'just now';
    if (minutes < 60) return `${minutes}m ago`;
    if (hours < 24) return `${hours}h ago`;
    if (days < 7) return `${days}d ago`;
    
    return formatDate(date);
  };

  const formatTime = (date: Date): string => {
    return new Intl.DateTimeFormat(locale, {
      hour: 'numeric',
      minute: 'numeric',
    }).format(date);
  };

  const formatDateTime = (date: Date): string => {
    return new Intl.DateTimeFormat(locale, {
      year: 'numeric',
      month: 'long',
      day: 'numeric',
      hour: 'numeric',
      minute: 'numeric',
    }).format(date);
  };

  return {
    formatDate,
    formatRelative,
    formatTime,
    formatDateTime,
    locale,
  };
}

/**
 * Number formatting hook
 * 
 * @example
 * ```tsx
 * const { formatNumber } = useNumber();
 * return <p>{formatNumber(1234567)}</p>; // 1,234,567
 * ```
 */
export function useNumber() {
  const { locale } = useLocale();

  const formatNumber = (value: number): string => {
    return new Intl.NumberFormat(locale).format(value);
  };

  const formatCompact = (value: number): string => {
    return new Intl.NumberFormat(locale, {
      notation: 'compact',
      compactDisplay: 'short',
    }).format(value);
  };

  const formatPercent = (value: number): string => {
    return new Intl.NumberFormat(locale, {
      style: 'percent',
      minimumFractionDigits: 0,
      maximumFractionDigits: 2,
    }).format(value);
  };

  return {
    formatNumber,
    formatCompact,
    formatPercent,
    locale,
  };
}

/**
 * RTL detection hook
 * 
 * @example
 * ```tsx
 * const { isRTL } = useRTL();
 * return <div className={isRTL ? 'flex-row-reverse' : 'flex-row'}>...</div>;
 * ```
 */
export function useRTL() {
  const { isRTL, locale } = useLocale();

  return {
    isRTL,
    locale,
  };
}
