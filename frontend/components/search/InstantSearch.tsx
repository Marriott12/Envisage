'use client';

import { useState, useEffect, useRef } from 'react';
import { MagnifyingGlassIcon, XMarkIcon } from '@heroicons/react/24/outline';
import { useDebounce } from '@/hooks/usePerformance';
import { useBehavioralStore } from '@/hooks/useBehavioralTracking';
import Link from 'next/link';

interface SearchResult {
  id: number;
  title: string;
  slug: string;
  price: number;
  image: string;
  category: string;
  type: 'product' | 'category' | 'brand';
}

interface InstantSearchProps {
  placeholder?: string;
  onResultClick?: (result: SearchResult) => void;
  className?: string;
}

export const InstantSearch: React.FC<InstantSearchProps> = ({
  placeholder = 'Search products, categories, brands...',
  onResultClick,
  className = '',
}) => {
  const [query, setQuery] = useState('');
  const [results, setResults] = useState<SearchResult[]>([]);
  const [isLoading, setIsLoading] = useState(false);
  const [isOpen, setIsOpen] = useState(false);
  const [selectedIndex, setSelectedIndex] = useState(-1);
  
  const debouncedQuery = useDebounce(query, 300);
  const searchRef = useRef<HTMLDivElement>(null);
  const inputRef = useRef<HTMLInputElement>(null);
  
  const { trackSearch } = useBehavioralStore();

  // Fetch search results
  useEffect(() => {
    if (debouncedQuery.length < 2) {
      setResults([]);
      setIsLoading(false);
      return;
    }

    const searchProducts = async () => {
      setIsLoading(true);
      
      try {
        const response = await fetch(
          `${process.env.NEXT_PUBLIC_API_URL}/search?q=${encodeURIComponent(debouncedQuery)}&limit=8`,
          {
            headers: {
              'Content-Type': 'application/json',
            },
          }
        );

        if (response.ok) {
          const data = await response.json();
          setResults(data.results || []);
          trackSearch(debouncedQuery, data.results?.length || 0);
        }
      } catch (error) {
        console.error('Search error:', error);
        setResults([]);
      } finally {
        setIsLoading(false);
      }
    };

    searchProducts();
  }, [debouncedQuery, trackSearch]);

  // Click outside to close
  useEffect(() => {
    const handleClickOutside = (event: MouseEvent) => {
      if (searchRef.current && !searchRef.current.contains(event.target as Node)) {
        setIsOpen(false);
      }
    };

    document.addEventListener('mousedown', handleClickOutside);
    return () => document.removeEventListener('mousedown', handleClickOutside);
  }, []);

  // Keyboard navigation
  const handleKeyDown = (e: React.KeyboardEvent) => {
    if (!isOpen || results.length === 0) return;

    switch (e.key) {
      case 'ArrowDown':
        e.preventDefault();
        setSelectedIndex((prev) => (prev < results.length - 1 ? prev + 1 : prev));
        break;
      case 'ArrowUp':
        e.preventDefault();
        setSelectedIndex((prev) => (prev > 0 ? prev - 1 : -1));
        break;
      case 'Enter':
        e.preventDefault();
        if (selectedIndex >= 0 && results[selectedIndex]) {
          handleResultClick(results[selectedIndex]);
        }
        break;
      case 'Escape':
        e.preventDefault();
        setIsOpen(false);
        inputRef.current?.blur();
        break;
    }
  };

  const handleResultClick = (result: SearchResult) => {
    onResultClick?.(result);
    setQuery('');
    setIsOpen(false);
    setResults([]);
  };

  const handleClear = () => {
    setQuery('');
    setResults([]);
    setSelectedIndex(-1);
    inputRef.current?.focus();
  };

  return (
    <div ref={searchRef} className={`relative ${className}`}>
      {/* Search Input */}
      <div className="relative">
        <div className="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
          <MagnifyingGlassIcon className="h-5 w-5 text-gray-400" />
        </div>
        
        <input
          ref={inputRef}
          type="text"
          value={query}
          onChange={(e) => {
            setQuery(e.target.value);
            setIsOpen(true);
            setSelectedIndex(-1);
          }}
          onFocus={() => {
            if (query.length >= 2 && results.length > 0) {
              setIsOpen(true);
            }
          }}
          onKeyDown={handleKeyDown}
          placeholder={placeholder}
          className="block w-full rounded-lg border border-gray-300 bg-white py-3 pl-10 pr-10 text-sm placeholder-gray-400 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
          autoComplete="off"
        />

        {query && (
          <button
            onClick={handleClear}
            className="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600"
            aria-label="Clear search"
          >
            <XMarkIcon className="h-5 w-5" />
          </button>
        )}

        {isLoading && (
          <div className="absolute inset-y-0 right-10 flex items-center pr-3">
            <div className="h-4 w-4 animate-spin rounded-full border-2 border-gray-300 border-t-blue-600" />
          </div>
        )}
      </div>

      {/* Search Results Dropdown */}
      {isOpen && query.length >= 2 && (
        <div className="absolute z-50 mt-2 w-full rounded-lg border border-gray-200 bg-white shadow-lg">
          {results.length > 0 ? (
            <ul className="max-h-96 overflow-y-auto py-2">
              {results.map((result, index) => (
                <li key={`${result.type}-${result.id}`}>
                  <Link
                    href={`/products/${result.slug}`}
                    onClick={() => handleResultClick(result)}
                    className={`flex items-center gap-3 px-4 py-3 hover:bg-gray-50 ${
                      index === selectedIndex ? 'bg-gray-50' : ''
                    }`}
                  >
                    {/* Product Image */}
                    {result.type === 'product' && (
                      <div className="h-12 w-12 flex-shrink-0 overflow-hidden rounded border border-gray-200">
                        <img
                          src={result.image}
                          alt={result.title}
                          className="h-full w-full object-cover"
                        />
                      </div>
                    )}

                    {/* Result Info */}
                    <div className="flex-1 min-w-0">
                      <p className="truncate text-sm font-medium text-gray-900">
                        {result.title}
                      </p>
                      {result.type === 'product' && (
                        <p className="text-sm text-gray-500">{result.category}</p>
                      )}
                      {result.type === 'category' && (
                        <span className="inline-flex items-center rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-800">
                          Category
                        </span>
                      )}
                      {result.type === 'brand' && (
                        <span className="inline-flex items-center rounded-full bg-purple-100 px-2 py-0.5 text-xs font-medium text-purple-800">
                          Brand
                        </span>
                      )}
                    </div>

                    {/* Price */}
                    {result.type === 'product' && (
                      <div className="text-sm font-semibold text-gray-900">
                        ${result.price.toFixed(2)}
                      </div>
                    )}
                  </Link>
                </li>
              ))}

              {/* View All Results */}
              <li className="border-t border-gray-200">
                <Link
                  href={`/search?q=${encodeURIComponent(query)}`}
                  className="flex items-center justify-center px-4 py-3 text-sm font-medium text-blue-600 hover:bg-gray-50"
                  onClick={() => setIsOpen(false)}
                >
                  View all results for "{query}"
                </Link>
              </li>
            </ul>
          ) : !isLoading ? (
            <div className="px-4 py-8 text-center text-sm text-gray-500">
              No results found for "{query}"
            </div>
          ) : null}
        </div>
      )}
    </div>
  );
};

// Compact version for mobile/header
export const CompactSearch: React.FC<{
  onExpand?: () => void;
}> = ({ onExpand }) => {
  const [isExpanded, setIsExpanded] = useState(false);

  if (!isExpanded) {
    return (
      <button
        onClick={() => {
          setIsExpanded(true);
          onExpand?.();
        }}
        className="flex h-10 w-10 items-center justify-center rounded-lg border border-gray-300 bg-white text-gray-600 hover:bg-gray-50 lg:hidden"
        aria-label="Open search"
      >
        <MagnifyingGlassIcon className="h-5 w-5" />
      </button>
    );
  }

  return (
    <div className="fixed inset-0 z-50 bg-white lg:hidden">
      <div className="flex items-center gap-3 border-b border-gray-200 p-4">
        <button
          onClick={() => setIsExpanded(false)}
          className="text-gray-600"
          aria-label="Close search"
        >
          <XMarkIcon className="h-6 w-6" />
        </button>
        <InstantSearch className="flex-1" placeholder="Search..." />
      </div>
    </div>
  );
};
