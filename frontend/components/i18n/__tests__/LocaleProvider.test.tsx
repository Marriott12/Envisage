import { describe, it, expect, beforeEach, vi } from 'vitest';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import { LocaleProvider, useLocale } from '@/components/i18n/LocaleProvider';

// Test component that uses locale
function TestComponent() {
  const { locale, setLocale, t, formatCurrency, formatDate, isRTL } = useLocale();

  return (
    <div>
      <div data-testid="locale">{locale}</div>
      <div data-testid="rtl">{isRTL ? 'rtl' : 'ltr'}</div>
      <div data-testid="currency">{formatCurrency(99.99)}</div>
      <div data-testid="date">{formatDate(new Date('2025-12-12'))}</div>
      <div data-testid="translation">{t('common.welcome')}</div>
      <button onClick={() => setLocale('es')} data-testid="switch-spanish">
        Switch to Spanish
      </button>
      <button onClick={() => setLocale('ar')} data-testid="switch-arabic">
        Switch to Arabic
      </button>
    </div>
  );
}

describe('LocaleProvider', () => {
  beforeEach(() => {
    localStorage.clear();
    document.documentElement.dir = 'ltr';
    document.documentElement.lang = 'en';
    
    // Mock fetch for translation files
    global.fetch = vi.fn((url) => {
      const locale = url.toString().includes('es') ? 'es' : 
                    url.toString().includes('fr') ? 'fr' : 'en';
      
      const translations = {
        en: { common: { welcome: 'Welcome to Envisage' } },
        es: { common: { welcome: 'Bienvenido a Envisage' } },
        fr: { common: { welcome: 'Bienvenue chez Envisage' } },
      };

      return Promise.resolve({
        ok: true,
        json: () => Promise.resolve(translations[locale as keyof typeof translations]),
      } as Response);
    });
  });

  it('renders with default locale (English)', async () => {
    render(
      <LocaleProvider>
        <TestComponent />
      </LocaleProvider>
    );

    await waitFor(() => {
      expect(screen.getByTestId('locale')).toHaveTextContent('en');
    });
  });

  it('formats currency correctly for English', async () => {
    render(
      <LocaleProvider>
        <TestComponent />
      </LocaleProvider>
    );

    await waitFor(() => {
      expect(screen.getByTestId('currency')).toHaveTextContent('$99.99');
    });
  });

  it('switches to Spanish and updates UI', async () => {
    render(
      <LocaleProvider>
        <TestComponent />
      </LocaleProvider>
    );

    const switchButton = screen.getByTestId('switch-spanish');
    fireEvent.click(switchButton);

    await waitFor(() => {
      expect(screen.getByTestId('locale')).toHaveTextContent('es');
      expect(screen.getByTestId('translation')).toHaveTextContent('Bienvenido a Envisage');
    });
  });

  it('switches to Arabic and enables RTL', async () => {
    render(
      <LocaleProvider>
        <TestComponent />
      </LocaleProvider>
    );

    const switchButton = screen.getByTestId('switch-arabic');
    fireEvent.click(switchButton);

    await waitFor(() => {
      expect(screen.getByTestId('locale')).toHaveTextContent('ar');
      expect(screen.getByTestId('rtl')).toHaveTextContent('rtl');
      expect(document.documentElement.dir).toBe('rtl');
    });
  });

  it('persists locale preference to localStorage', async () => {
    render(
      <LocaleProvider>
        <TestComponent />
      </LocaleProvider>
    );

    const switchButton = screen.getByTestId('switch-spanish');
    fireEvent.click(switchButton);

    await waitFor(() => {
      expect(localStorage.getItem('locale')).toBe('es');
    });
  });

  it('formats date according to locale', async () => {
    render(
      <LocaleProvider>
        <TestComponent />
      </LocaleProvider>
    );

    await waitFor(() => {
      const dateText = screen.getByTestId('date').textContent;
      expect(dateText).toContain('2025');
      expect(dateText).toContain('December' || 'Dec');
    });
  });

  it('returns key when translation not found', async () => {
    render(
      <LocaleProvider>
        <TestComponent />
      </LocaleProvider>
    );

    const { t } = useLocale();
    expect(t('non.existent.key')).toBe('non.existent.key');
  });
});
