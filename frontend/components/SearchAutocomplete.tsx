'use client';

import { useState, useEffect, useRef } from 'react';
import { MagnifyingGlassIcon, ClockIcon, XMarkIcon } from '@heroicons/react/24/outline';
import { motion, AnimatePresence } from 'framer-motion';

interface SearchSuggestion {
  id: string;
  text: string;
  type: 'product' | 'category' | 'recent';
  image?: string;
}

interface SearchAutocompleteProps {
  value: string;
  onChange: (value: string) => void;
  onSearch: () => void;
  placeholder?: string;
}

const POPULAR_SEARCHES = [
  'iPhone',
  'Laptop',
  'Headphones',
  'Furniture',
  'Books',
  'Clothing',
  'Electronics',
  'Home Decor',
];

export default function SearchAutocomplete({
  value,
  onChange,
  onSearch,
  placeholder = 'Search for products...',
}: SearchAutocompleteProps) {
  const [isFocused, setIsFocused] = useState(false);
  const [suggestions, setSuggestions] = useState<SearchSuggestion[]>([]);
  const [recentSearches, setRecentSearches] = useState<string[]>([]);
  const wrapperRef = useRef<HTMLDivElement>(null);

  useEffect(() => {
    // Load recent searches from localStorage
    const stored = localStorage.getItem('recentSearches');
    if (stored) {
      setRecentSearches(JSON.parse(stored));
    }
  }, []);

  useEffect(() => {
    // Generate suggestions based on input
    if (value.trim()) {
      const matchedProducts = POPULAR_SEARCHES
        .filter((search) => search.toLowerCase().includes(value.toLowerCase()))
        .map((text, idx) => ({
          id: `product-${idx}`,
          text,
          type: 'product' as const,
        }));

      setSuggestions(matchedProducts.slice(0, 5));
    } else {
      // Show recent searches when input is empty
      const recent = recentSearches.slice(0, 5).map((text, idx) => ({
        id: `recent-${idx}`,
        text,
        type: 'recent' as const,
      }));
      setSuggestions(recent);
    }
  }, [value, recentSearches]);

  useEffect(() => {
    // Click outside handler
    function handleClickOutside(event: MouseEvent) {
      if (wrapperRef.current && !wrapperRef.current.contains(event.target as Node)) {
        setIsFocused(false);
      }
    }

    document.addEventListener('mousedown', handleClickOutside);
    return () => document.removeEventListener('mousedown', handleClickOutside);
  }, []);

  const handleSelectSuggestion = (suggestion: SearchSuggestion) => {
    onChange(suggestion.text);
    addToRecentSearches(suggestion.text);
    setIsFocused(false);
    onSearch();
  };

  const addToRecentSearches = (search: string) => {
    if (!search.trim()) return;

    const updated = [search, ...recentSearches.filter((s) => s !== search)].slice(0, 10);
    setRecentSearches(updated);
    localStorage.setItem('recentSearches', JSON.stringify(updated));
  };

  const handleClearRecentSearches = () => {
    setRecentSearches([]);
    localStorage.removeItem('recentSearches');
  };

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (value.trim()) {
      addToRecentSearches(value.trim());
      setIsFocused(false);
      onSearch();
    }
  };

  const showDropdown = isFocused && suggestions.length > 0;

  return (
    <div ref={wrapperRef} className="relative w-full">
      <form onSubmit={handleSubmit} className="relative">
        <div className="relative">
          <MagnifyingGlassIcon className="absolute left-4 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400" />
          <input
            type="text"
            value={value}
            onChange={(e) => onChange(e.target.value)}
            onFocus={() => setIsFocused(true)}
            placeholder={placeholder}
            className="w-full pl-12 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
          />
          {value && (
            <button
              type="button"
              onClick={() => onChange('')}
              className="absolute right-4 top-1/2 -translate-y-1/2 p-1 hover:bg-gray-100 rounded-full"
            >
              <XMarkIcon className="h-5 w-5 text-gray-400" />
            </button>
          )}
        </div>
      </form>

      {/* Dropdown Suggestions */}
      <AnimatePresence>
        {showDropdown && (
          <motion.div
            initial={{ opacity: 0, y: -10 }}
            animate={{ opacity: 1, y: 0 }}
            exit={{ opacity: 0, y: -10 }}
            transition={{ duration: 0.2 }}
            className="absolute z-50 w-full mt-2 bg-white rounded-lg shadow-lg border border-gray-200 max-h-96 overflow-y-auto"
          >
            {/* Recent Searches Header */}
            {!value && recentSearches.length > 0 && (
              <div className="px-4 py-3 border-b border-gray-200 flex items-center justify-between">
                <span className="text-sm font-semibold text-gray-700">Recent Searches</span>
                <button
                  onClick={handleClearRecentSearches}
                  className="text-xs text-blue-600 hover:text-blue-700"
                >
                  Clear All
                </button>
              </div>
            )}

            {/* Suggestions Header */}
            {value && (
              <div className="px-4 py-3 border-b border-gray-200">
                <span className="text-sm font-semibold text-gray-700">Suggestions</span>
              </div>
            )}

            {/* Suggestion Items */}
            <div className="py-2">
              {suggestions.map((suggestion) => (
                <button
                  key={suggestion.id}
                  onClick={() => handleSelectSuggestion(suggestion)}
                  className="w-full px-4 py-3 hover:bg-gray-50 flex items-center gap-3 transition-colors"
                >
                  {suggestion.type === 'recent' ? (
                    <ClockIcon className="h-5 w-5 text-gray-400 flex-shrink-0" />
                  ) : (
                    <MagnifyingGlassIcon className="h-5 w-5 text-gray-400 flex-shrink-0" />
                  )}
                  <span className="text-left text-gray-900">{suggestion.text}</span>
                </button>
              ))}
            </div>

            {/* Popular Searches */}
            {!value && recentSearches.length === 0 && (
              <div className="p-4">
                <p className="text-sm font-semibold text-gray-700 mb-3">Popular Searches</p>
                <div className="flex flex-wrap gap-2">
                  {POPULAR_SEARCHES.slice(0, 6).map((search, idx) => (
                    <button
                      key={idx}
                      onClick={() => {
                        onChange(search);
                        addToRecentSearches(search);
                        setIsFocused(false);
                        onSearch();
                      }}
                      className="px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-sm text-gray-700 rounded-full transition-colors"
                    >
                      {search}
                    </button>
                  ))}
                </div>
              </div>
            )}
          </motion.div>
        )}
      </AnimatePresence>
    </div>
  );
}
