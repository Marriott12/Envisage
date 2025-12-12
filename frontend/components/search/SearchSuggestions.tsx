'use client';

import { useEffect, useState } from 'react';
import { Clock, TrendingUp, X } from 'lucide-react';
import { useBehavioralStore } from '@/hooks/useBehavioralTracking';

interface SearchSuggestionsProps {
  onSelect: (query: string) => void;
  className?: string;
}

export function SearchSuggestions({
  onSelect,
  className = '',
}: SearchSuggestionsProps) {
  const { searchHistory, clearHistory } = useBehavioralStore();
  const [popularSearches, setPopularSearches] = useState<string[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    // Fetch popular searches from API
    const fetchPopularSearches = async () => {
      try {
        const response = await fetch('/api/search/popular');
        const data = await response.json();
        setPopularSearches(data.searches || []);
      } catch (error) {
        console.error('Failed to fetch popular searches:', error);
        // Fallback to default suggestions
        setPopularSearches([
          'wireless headphones',
          'smart watch',
          'laptop backpack',
          'gaming keyboard',
          'phone case',
        ]);
      } finally {
        setLoading(false);
      }
    };

    fetchPopularSearches();
  }, []);

  const removeSearchHistoryItem = (query: string, e: React.MouseEvent) => {
    e.stopPropagation();
    // Remove from store
    const { searchHistory: history } = useBehavioralStore.getState();
    const updated = history.filter((item) => item.query !== query);
    useBehavioralStore.setState({ searchHistory: updated });
  };

  const recentSearches = searchHistory.slice(-5).reverse();

  return (
    <div className={`bg-white rounded-lg shadow-lg border border-gray-200 ${className}`}>
      {/* Recent Searches */}
      {recentSearches.length > 0 && (
        <div className="p-4 border-b border-gray-200">
          <div className="flex items-center justify-between mb-3">
            <h3 className="text-sm font-semibold text-gray-900 flex items-center gap-2">
              <Clock className="w-4 h-4" />
              Recent Searches
            </h3>
            <button
              onClick={clearHistory}
              className="text-xs text-blue-600 hover:text-blue-700 font-medium"
            >
              Clear All
            </button>
          </div>
          <div className="space-y-2">
            {recentSearches.map((item, index) => (
              <button
                key={index}
                onClick={() => onSelect(item.query)}
                className="w-full flex items-center justify-between p-2 hover:bg-gray-50 rounded-lg text-left group"
              >
                <div className="flex items-center gap-3">
                  <Clock className="w-4 h-4 text-gray-400" />
                  <span className="text-sm text-gray-700">{item.query}</span>
                  {item.results !== undefined && (
                    <span className="text-xs text-gray-500">
                      ({item.results} results)
                    </span>
                  )}
                </div>
                <button
                  onClick={(e) => removeSearchHistoryItem(item.query, e)}
                  className="opacity-0 group-hover:opacity-100 p-1 hover:bg-gray-200 rounded transition-opacity"
                  aria-label={`Remove "${item.query}" from history`}
                >
                  <X className="w-3 h-3 text-gray-500" />
                </button>
              </button>
            ))}
          </div>
        </div>
      )}

      {/* Popular Searches */}
      <div className="p-4">
        <h3 className="text-sm font-semibold text-gray-900 flex items-center gap-2 mb-3">
          <TrendingUp className="w-4 h-4" />
          Popular Searches
        </h3>
        {loading ? (
          <div className="space-y-2">
            {[1, 2, 3, 4, 5].map((i) => (
              <div
                key={i}
                className="h-8 bg-gray-100 rounded-lg animate-pulse"
              />
            ))}
          </div>
        ) : (
          <div className="space-y-2">
            {popularSearches.map((search, index) => (
              <button
                key={index}
                onClick={() => onSelect(search)}
                className="w-full flex items-center gap-3 p-2 hover:bg-gray-50 rounded-lg text-left"
              >
                <TrendingUp className="w-4 h-4 text-gray-400" />
                <span className="text-sm text-gray-700">{search}</span>
              </button>
            ))}
          </div>
        )}
      </div>
    </div>
  );
}

// Compact version for mobile
export function CompactSearchSuggestions({
  onSelect,
  className = '',
}: SearchSuggestionsProps) {
  const { searchHistory } = useBehavioralStore();
  const recentSearches = searchHistory.slice(-3).reverse();

  return (
    <div className={`bg-white rounded-lg shadow-md border border-gray-200 ${className}`}>
      {recentSearches.length > 0 && (
        <div className="p-3">
          <h4 className="text-xs font-semibold text-gray-500 mb-2">RECENT</h4>
          <div className="flex flex-wrap gap-2">
            {recentSearches.map((item, index) => (
              <button
                key={index}
                onClick={() => onSelect(item.query)}
                className="px-3 py-1 bg-gray-100 hover:bg-gray-200 rounded-full text-xs text-gray-700 transition-colors"
              >
                {item.query}
              </button>
            ))}
          </div>
        </div>
      )}
    </div>
  );
}
