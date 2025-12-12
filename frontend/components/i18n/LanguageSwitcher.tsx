'use client';

import { useState } from 'react';
import { Globe, Check } from 'lucide-react';
import { useLocale } from './LocaleProvider';

/**
 * Language switcher component
 */
export function LanguageSwitcher() {
  const { locale, setLocale } = useLocale();
  const [isOpen, setIsOpen] = useState(false);

  const languages = [
    { code: 'en', name: 'English', flag: '🇺🇸' },
    { code: 'es', name: 'Español', flag: '🇪🇸' },
    { code: 'fr', name: 'Français', flag: '🇫🇷' },
    { code: 'de', name: 'Deutsch', flag: '🇩🇪' },
    { code: 'ar', name: 'العربية', flag: '🇸🇦' },
  ];

  const currentLanguage = languages.find((lang) => lang.code === locale);

  return (
    <div className="relative">
      <button
        onClick={() => setIsOpen(!isOpen)}
        className="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-gray-100 transition"
        aria-label="Change language"
        aria-expanded={isOpen}
      >
        <Globe className="w-5 h-5" />
        <span className="text-sm">{currentLanguage?.flag}</span>
        <span className="text-sm font-medium hidden md:inline">
          {currentLanguage?.name}
        </span>
      </button>

      {isOpen && (
        <>
          {/* Backdrop */}
          <div
            className="fixed inset-0 z-40"
            onClick={() => setIsOpen(false)}
            aria-hidden="true"
          />

          {/* Dropdown */}
          <div
            className="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 z-50"
            role="menu"
          >
            {languages.map((language) => (
              <button
                key={language.code}
                onClick={() => {
                  setLocale(language.code as any);
                  setIsOpen(false);
                }}
                className="w-full flex items-center justify-between px-4 py-3 hover:bg-gray-50 transition first:rounded-t-lg last:rounded-b-lg"
                role="menuitem"
              >
                <div className="flex items-center gap-3">
                  <span className="text-xl">{language.flag}</span>
                  <span className="text-sm font-medium">{language.name}</span>
                </div>
                {locale === language.code && (
                  <Check className="w-4 h-4 text-blue-600" />
                )}
              </button>
            ))}
          </div>
        </>
      )}
    </div>
  );
}

/**
 * Compact language switcher for mobile
 */
export function CompactLanguageSwitcher() {
  const { locale, setLocale } = useLocale();

  const languages = [
    { code: 'en', name: 'EN' },
    { code: 'es', name: 'ES' },
    { code: 'fr', name: 'FR' },
    { code: 'de', name: 'DE' },
    { code: 'ar', name: 'AR' },
  ];

  return (
    <div className="flex items-center gap-1">
      {languages.map((language) => (
        <button
          key={language.code}
          onClick={() => setLocale(language.code as any)}
          className={`px-2 py-1 text-xs rounded transition ${
            locale === language.code
              ? 'bg-blue-600 text-white'
              : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
          }`}
          aria-label={`Switch to ${language.name}`}
          aria-pressed={locale === language.code}
        >
          {language.name}
        </button>
      ))}
    </div>
  );
}

export default { LanguageSwitcher, CompactLanguageSwitcher };
