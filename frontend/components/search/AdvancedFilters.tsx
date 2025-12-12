'use client';

import { useState } from 'react';
import { X, Save, ChevronDown, ChevronUp, Star } from 'lucide-react';
import { useFilterStore } from '@/lib/stores/enhanced-stores';

interface AdvancedFiltersProps {
  categories?: { id: number; name: string; count?: number }[];
  brands?: { id: string; name: string; count?: number }[];
  onClose?: () => void;
  className?: string;
}

const popularTags = [
  { value: 'new-arrival', label: 'New Arrival' },
  { value: 'sale', label: 'On Sale' },
  { value: 'trending', label: 'Trending' },
  { value: 'featured', label: 'Featured' },
  { value: 'limited', label: 'Limited Edition' },
];

export function AdvancedFilters({
  categories = [],
  brands = [],
  onClose,
  className = '',
}: AdvancedFiltersProps) {
  const {
    minPrice,
    maxPrice,
    brands: selectedBrands,
    tags: selectedTags,
    inStock,
    rating,
    savedFilters,
    setFilter,
    resetFilters,
    saveFilterPreset,
    applyFilterPreset,
    deleteFilterPreset,
  } = useFilterStore();

  const [presetName, setPresetName] = useState('');
  const [showSavePreset, setShowSavePreset] = useState(false);
  const [expandedSections, setExpandedSections] = useState({
    price: true,
    rating: true,
    brands: false,
    tags: true,
  });

  const toggleSection = (section: keyof typeof expandedSections) => {
    setExpandedSections(prev => ({
      ...prev,
      [section]: !prev[section],
    }));
  };

  const handleBrandToggle = (brandId: string) => {
    const newBrands = selectedBrands.includes(brandId)
      ? selectedBrands.filter(id => id !== brandId)
      : [...selectedBrands, brandId];
    setFilter('brands', newBrands);
  };

  const handleTagToggle = (tagValue: string) => {
    const newTags = selectedTags.includes(tagValue)
      ? selectedTags.filter(t => t !== tagValue)
      : [...selectedTags, tagValue];
    setFilter('tags', newTags);
  };

  const handleSavePreset = () => {
    if (presetName.trim()) {
      saveFilterPreset(presetName.trim());
      setPresetName('');
      setShowSavePreset(false);
    }
  };

  const handleRatingChange = (newRating: number) => {
    setFilter('rating', rating === newRating ? null : newRating);
  };

  return (
    <div className={`bg-white rounded-lg shadow-lg p-6 ${className}`}>
      {/* Header */}
      <div className="flex items-center justify-between mb-6">
        <h3 className="text-lg font-semibold text-gray-900">Advanced Filters</h3>
        <div className="flex items-center gap-2">
          <button
            onClick={resetFilters}
            className="text-sm text-gray-600 hover:text-gray-900 px-3 py-1 rounded hover:bg-gray-100"
          >
            Reset All
          </button>
          {onClose && (
            <button
              onClick={onClose}
              className="text-gray-400 hover:text-gray-600"
            >
              <X className="w-5 h-5" />
            </button>
          )}
        </div>
      </div>

      {/* Price Range */}
      <div className="mb-6">
        <button
          onClick={() => toggleSection('price')}
          className="flex items-center justify-between w-full mb-3"
        >
          <span className="font-medium text-gray-900">Price Range</span>
          {expandedSections.price ? (
            <ChevronUp className="w-5 h-5 text-gray-500" />
          ) : (
            <ChevronDown className="w-5 h-5 text-gray-500" />
          )}
        </button>
        {expandedSections.price && (
          <div className="flex items-center gap-3">
            <input
              type="number"
              placeholder="Min"
              value={minPrice ?? ''}
              onChange={(e) => setFilter('minPrice', e.target.value ? Number(e.target.value) : null)}
              className="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent"
            />
            <span className="text-gray-500">-</span>
            <input
              type="number"
              placeholder="Max"
              value={maxPrice ?? ''}
              onChange={(e) => setFilter('maxPrice', e.target.value ? Number(e.target.value) : null)}
              className="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent"
            />
          </div>
        )}
      </div>

      {/* Rating */}
      <div className="mb-6">
        <button
          onClick={() => toggleSection('rating')}
          className="flex items-center justify-between w-full mb-3"
        >
          <span className="font-medium text-gray-900">Rating</span>
          {expandedSections.rating ? (
            <ChevronUp className="w-5 h-5 text-gray-500" />
          ) : (
            <ChevronDown className="w-5 h-5 text-gray-500" />
          )}
        </button>
        {expandedSections.rating && (
          <div className="flex gap-2">
            {[1, 2, 3, 4, 5].map((star) => (
              <button
                key={star}
                onClick={() => handleRatingChange(star)}
                className={`flex items-center gap-1 px-3 py-2 rounded-lg border transition-colors ${
                  rating === star
                    ? 'bg-emerald-50 border-emerald-500 text-emerald-700'
                    : 'border-gray-300 text-gray-700 hover:border-emerald-300'
                }`}
              >
                <Star
                  className={`w-4 h-4 ${
                    rating === star ? 'fill-emerald-500 text-emerald-500' : ''
                  }`}
                />
                <span className="text-sm">{star}+</span>
              </button>
            ))}
          </div>
        )}
      </div>

      {/* Brands */}
      {brands.length > 0 && (
        <div className="mb-6">
          <button
            onClick={() => toggleSection('brands')}
            className="flex items-center justify-between w-full mb-3"
          >
            <span className="font-medium text-gray-900">
              Brands {selectedBrands.length > 0 && `(${selectedBrands.length})`}
            </span>
            {expandedSections.brands ? (
              <ChevronUp className="w-5 h-5 text-gray-500" />
            ) : (
              <ChevronDown className="w-5 h-5 text-gray-500" />
            )}
          </button>
          {expandedSections.brands && (
            <div className="space-y-2 max-h-60 overflow-y-auto">
              {brands.map((brand) => (
                <label
                  key={brand.id}
                  className="flex items-center gap-2 cursor-pointer hover:bg-gray-50 p-2 rounded"
                >
                  <input
                    type="checkbox"
                    checked={selectedBrands.includes(String(brand.id))}
                    onChange={() => handleBrandToggle(String(brand.id))}
                    className="w-4 h-4 text-emerald-600 border-gray-300 rounded focus:ring-emerald-500"
                  />
                  <span className="text-sm text-gray-700">
                    {brand.name}
                    {brand.count !== undefined && (
                      <span className="text-gray-500 ml-1">({brand.count})</span>
                    )}
                  </span>
                </label>
              ))}
            </div>
          )}
        </div>
      )}

      {/* Tags */}
      <div className="mb-6">
        <button
          onClick={() => toggleSection('tags')}
          className="flex items-center justify-between w-full mb-3"
        >
          <span className="font-medium text-gray-900">
            Tags {selectedTags.length > 0 && `(${selectedTags.length})`}
          </span>
          {expandedSections.tags ? (
            <ChevronUp className="w-5 h-5 text-gray-500" />
          ) : (
            <ChevronDown className="w-5 h-5 text-gray-500" />
          )}
        </button>
        {expandedSections.tags && (
          <div className="flex flex-wrap gap-2">
            {popularTags.map((tag) => (
              <button
                key={tag.value}
                onClick={() => handleTagToggle(tag.value)}
                className={`px-3 py-1.5 rounded-full text-sm font-medium transition-colors ${
                  selectedTags.includes(tag.value)
                    ? 'bg-emerald-500 text-white'
                    : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                }`}
              >
                {tag.label}
              </button>
            ))}
          </div>
        )}
      </div>

      {/* In Stock */}
      <div className="mb-6">
        <label className="flex items-center gap-2 cursor-pointer">
          <input
            type="checkbox"
            checked={inStock}
            onChange={(e) => setFilter('inStock', e.target.checked)}
            className="w-4 h-4 text-emerald-600 border-gray-300 rounded focus:ring-emerald-500"
          />
          <span className="text-sm font-medium text-gray-900">In Stock Only</span>
        </label>
      </div>

      {/* Saved Presets */}
      {savedFilters.length > 0 && (
        <div className="mb-6">
          <h4 className="font-medium text-gray-900 mb-3">Saved Presets</h4>
          <div className="space-y-2">
            {savedFilters.map((preset) => (
              <div
                key={preset.name}
                className="flex items-center justify-between p-2 bg-gray-50 rounded-lg"
              >
                <button
                  onClick={() => applyFilterPreset(preset.name)}
                  className="text-sm text-emerald-600 hover:text-emerald-700 font-medium"
                >
                  {preset.name}
                </button>
                <button
                  onClick={() => deleteFilterPreset(preset.name)}
                  className="text-gray-400 hover:text-red-600"
                >
                  <X className="w-4 h-4" />
                </button>
              </div>
            ))}
          </div>
        </div>
      )}

      {/* Save Preset */}
      <div className="border-t pt-4">
        {showSavePreset ? (
          <div className="flex gap-2">
            <input
              type="text"
              placeholder="Preset name"
              value={presetName}
              onChange={(e) => setPresetName(e.target.value)}
              onKeyPress={(e) => e.key === 'Enter' && handleSavePreset()}
              className="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent text-sm"
              autoFocus
            />
            <button
              onClick={handleSavePreset}
              disabled={!presetName.trim()}
              className="px-4 py-2 bg-emerald-500 text-white rounded-lg hover:bg-emerald-600 disabled:bg-gray-300 disabled:cursor-not-allowed"
            >
              <Save className="w-4 h-4" />
            </button>
            <button
              onClick={() => {
                setShowSavePreset(false);
                setPresetName('');
              }}
              className="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50"
            >
              <X className="w-4 h-4" />
            </button>
          </div>
        ) : (
          <button
            onClick={() => setShowSavePreset(true)}
            className="w-full flex items-center justify-center gap-2 px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 text-gray-700 font-medium"
          >
            <Save className="w-4 h-4" />
            Save Current Filters
          </button>
        )}
      </div>
    </div>
  );
}
