'use client';

import React, { useState, useEffect } from 'react';
import { MagnifyingGlassIcon, AdjustmentsHorizontalIcon, XMarkIcon } from '@heroicons/react/24/outline';
import api from '@/lib/api';
import { useRouter } from 'next/navigation';
import Image from 'next/image';

interface SearchFilter {
  id: string;
  name: string;
  type: 'range' | 'checkbox' | 'select';
  options?: Array<{ value: string; label: string; count?: number }>;
  min?: number;
  max?: number;
}

interface SearchSuggestion {
  query: string;
  type: 'recent' | 'popular' | 'autocomplete';
}

interface Product {
  id: number;
  name: string;
  price: number;
  images: string[];
  rating: number;
  reviews_count: number;
}

export default function AdvancedSearch() {
  const router = useRouter();
  const [query, setQuery] = useState('');
  const [suggestions, setSuggestions] = useState<SearchSuggestion[]>([]);
  const [showSuggestions, setShowSuggestions] = useState(false);
  const [filters, setFilters] = useState<SearchFilter[]>([]);
  const [selectedFilters, setSelectedFilters] = useState<Record<string, any>>({});
  const [showFilters, setShowFilters] = useState(false);
  const [results, setResults] = useState<Product[]>([]);
  const [loading, setLoading] = useState(false);
  const [totalResults, setTotalResults] = useState(0);

  useEffect(() => {
    if (query.length > 2) {
      fetchSuggestions();
    } else {
      setSuggestions([]);
    }
  }, [query]);

  useEffect(() => {
    fetchFilters();
  }, []);

  const fetchSuggestions = async () => {
    try {
      const response = await api.get('/search/suggestions', {
        params: { q: query }
      });
      setSuggestions(response.data);
    } catch (error) {
      console.error('Failed to fetch suggestions:', error);
    }
  };

  const fetchFilters = async () => {
    try {
      const response = await api.get('/search/filters');
      setFilters(response.data);
    } catch (error) {
      console.error('Failed to fetch filters:', error);
    }
  };

  const handleSearch = async (searchQuery: string = query) => {
    if (!searchQuery.trim()) return;

    setLoading(true);
    setShowSuggestions(false);

    try {
      const response = await api.get('/search', {
        params: {
          q: searchQuery,
          ...selectedFilters
        }
      });

      setResults(response.data.products);
      setTotalResults(response.data.total);

      // Track search
      await api.post('/search/track', { query: searchQuery });
    } catch (error) {
      console.error('Search failed:', error);
    } finally {
      setLoading(false);
    }
  };

  const handleFilterChange = (filterId: string, value: any) => {
    setSelectedFilters(prev => ({
      ...prev,
      [filterId]: value
    }));
  };

  const handleRemoveFilter = (filterId: string) => {
    const newFilters = { ...selectedFilters };
    delete newFilters[filterId];
    setSelectedFilters(newFilters);
  };

  const handleClearFilters = () => {
    setSelectedFilters({});
  };

  const activeFilterCount = Object.keys(selectedFilters).length;

  return (
    <div className="min-h-screen bg-gray-50">
      {/* Search Header */}
      <div className="bg-white shadow-sm sticky top-0 z-10">
        <div className="max-w-7xl mx-auto px-4 py-4">
          <div className="flex gap-4">
            {/* Search Input */}
            <div className="flex-1 relative">
              <div className="relative">
                <MagnifyingGlassIcon className="absolute left-3 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400" />
                <input
                  type="text"
                  value={query}
                  onChange={(e) => setQuery(e.target.value)}
                  onFocus={() => setShowSuggestions(true)}
                  onKeyPress={(e) => e.key === 'Enter' && handleSearch()}
                  placeholder="Search for products..."
                  className="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                />
              </div>

              {/* Suggestions Dropdown */}
              {showSuggestions && suggestions.length > 0 && (
                <div className="absolute top-full left-0 right-0 mt-2 bg-white border border-gray-200 rounded-lg shadow-lg max-h-96 overflow-y-auto z-20">
                  {suggestions.map((suggestion, index) => (
                    <button
                      key={index}
                      onClick={() => {
                        setQuery(suggestion.query);
                        handleSearch(suggestion.query);
                      }}
                      className="w-full px-4 py-3 text-left hover:bg-gray-50 flex items-center justify-between group"
                    >
                      <span className="text-gray-900">{suggestion.query}</span>
                      {suggestion.type === 'popular' && (
                        <span className="text-xs text-gray-500">Popular</span>
                      )}
                    </button>
                  ))}
                </div>
              )}
            </div>

            {/* Filter Button */}
            <button
              onClick={() => setShowFilters(!showFilters)}
              className="btn-secondary flex items-center gap-2"
            >
              <AdjustmentsHorizontalIcon className="w-5 h-5" />
              Filters
              {activeFilterCount > 0 && (
                <span className="bg-primary-600 text-white text-xs font-bold px-2 py-0.5 rounded-full">
                  {activeFilterCount}
                </span>
              )}
            </button>

            {/* Search Button */}
            <button
              onClick={() => handleSearch()}
              className="btn-primary px-8"
              disabled={loading}
            >
              {loading ? 'Searching...' : 'Search'}
            </button>
          </div>

          {/* Active Filters */}
          {activeFilterCount > 0 && (
            <div className="flex items-center gap-2 mt-4 flex-wrap">
              <span className="text-sm text-gray-600">Active filters:</span>
              {Object.entries(selectedFilters).map(([key, value]) => (
                <span
                  key={key}
                  className="inline-flex items-center gap-1 px-3 py-1 bg-primary-100 text-primary-700 rounded-full text-sm"
                >
                  {key}: {Array.isArray(value) ? value.join(', ') : value}
                  <button
                    onClick={() => handleRemoveFilter(key)}
                    className="hover:text-primary-900"
                  >
                    <XMarkIcon className="w-4 h-4" />
                  </button>
                </span>
              ))}
              <button
                onClick={handleClearFilters}
                className="text-sm text-primary-600 hover:text-primary-700 font-medium"
              >
                Clear all
              </button>
            </div>
          )}
        </div>
      </div>

      {/* Main Content */}
      <div className="max-w-7xl mx-auto px-4 py-6">
        <div className="flex gap-6">
          {/* Filters Sidebar */}
          {showFilters && (
            <div className="w-64 flex-shrink-0">
              <div className="bg-white rounded-lg shadow p-6 sticky top-24">
                <h3 className="font-semibold text-gray-900 mb-4">Filters</h3>
                <div className="space-y-6">
                  {filters.map((filter) => (
                    <div key={filter.id}>
                      <label className="block text-sm font-medium text-gray-700 mb-2">
                        {filter.name}
                      </label>
                      {filter.type === 'checkbox' && filter.options && (
                        <div className="space-y-2">
                          {filter.options.map((option) => (
                            <label key={option.value} className="flex items-center">
                              <input
                                type="checkbox"
                                checked={selectedFilters[filter.id]?.includes(option.value)}
                                onChange={(e) => {
                                  const current = selectedFilters[filter.id] || [];
                                  handleFilterChange(
                                    filter.id,
                                    e.target.checked
                                      ? [...current, option.value]
                                      : current.filter((v: string) => v !== option.value)
                                  );
                                }}
                                className="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                              />
                              <span className="ml-2 text-sm text-gray-700">
                                {option.label}
                                {option.count && (
                                  <span className="text-gray-500 ml-1">({option.count})</span>
                                )}
                              </span>
                            </label>
                          ))}
                        </div>
                      )}
                      {filter.type === 'range' && (
                        <div className="space-y-2">
                          <input
                            type="range"
                            min={filter.min}
                            max={filter.max}
                            value={selectedFilters[filter.id] || filter.min}
                            onChange={(e) => handleFilterChange(filter.id, e.target.value)}
                            className="w-full"
                          />
                          <div className="flex justify-between text-xs text-gray-500">
                            <span>${filter.min}</span>
                            <span>${selectedFilters[filter.id] || filter.max}</span>
                          </div>
                        </div>
                      )}
                    </div>
                  ))}
                </div>
              </div>
            </div>
          )}

          {/* Results */}
          <div className="flex-1">
            {loading ? (
              <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                {[...Array(6)].map((_, i) => (
                  <div key={i} className="bg-white rounded-lg shadow animate-pulse">
                    <div className="aspect-square bg-gray-200"></div>
                    <div className="p-4 space-y-2">
                      <div className="h-4 bg-gray-200 rounded w-3/4"></div>
                      <div className="h-4 bg-gray-200 rounded w-1/2"></div>
                    </div>
                  </div>
                ))}
              </div>
            ) : results.length > 0 ? (
              <>
                <div className="mb-4 flex items-center justify-between">
                  <p className="text-gray-600">
                    <span className="font-semibold text-gray-900">{totalResults}</span> results found
                  </p>
                </div>
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                  {results.map((product) => (
                    <div
                      key={product.id}
                      onClick={() => router.push(`/products/${product.id}`)}
                      className="bg-white rounded-lg shadow hover:shadow-lg transition-shadow cursor-pointer"
                    >
                      <div className="aspect-square relative">
                        <Image
                          src={product.images[0] || '/placeholder.jpg'}
                          alt={product.name}
                          fill
                          className="object-cover rounded-t-lg"
                        />
                      </div>
                      <div className="p-4">
                        <h3 className="font-medium text-gray-900 mb-2 line-clamp-2">
                          {product.name}
                        </h3>
                        <div className="flex items-center justify-between">
                          <span className="text-lg font-bold text-primary-600">
                            ${product.price.toFixed(2)}
                          </span>
                          <div className="flex items-center gap-1 text-sm text-gray-600">
                            <span>⭐</span>
                            <span>{product.rating.toFixed(1)}</span>
                            <span className="text-gray-400">({product.reviews_count})</span>
                          </div>
                        </div>
                      </div>
                    </div>
                  ))}
                </div>
              </>
            ) : query ? (
              <div className="text-center py-12">
                <MagnifyingGlassIcon className="w-16 h-16 text-gray-400 mx-auto mb-4" />
                <h3 className="text-lg font-medium text-gray-900 mb-2">No results found</h3>
                <p className="text-gray-600">
                  Try adjusting your search or filters to find what you're looking for
                </p>
              </div>
            ) : (
              <div className="text-center py-12">
                <MagnifyingGlassIcon className="w-16 h-16 text-gray-400 mx-auto mb-4" />
                <h3 className="text-lg font-medium text-gray-900 mb-2">Start searching</h3>
                <p className="text-gray-600">
                  Enter a search query above to find products
                </p>
              </div>
            )}
          </div>
        </div>
      </div>
    </div>
  );
}
