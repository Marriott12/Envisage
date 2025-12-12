'use client';

import { useState } from 'react';
import { Ruler, User, ChevronDown, Info } from 'lucide-react';

interface SizeChart {
  size: string;
  measurements: Record<string, string | number>;
}

interface SizeGuideProps {
  productType: 'clothing' | 'shoes' | 'accessories';
  gender?: 'men' | 'women' | 'unisex';
  sizeChart?: SizeChart[];
  userMeasurements?: Record<string, number>;
  onSizeRecommend?: (size: string) => void;
  className?: string;
}

const defaultSizeCharts = {
  clothing: {
    men: [
      { size: 'XS', measurements: { chest: '34-36"', waist: '28-30"', hips: '34-36"' } },
      { size: 'S', measurements: { chest: '36-38"', waist: '30-32"', hips: '36-38"' } },
      { size: 'M', measurements: { chest: '38-40"', waist: '32-34"', hips: '38-40"' } },
      { size: 'L', measurements: { chest: '40-42"', waist: '34-36"', hips: '40-42"' } },
      { size: 'XL', measurements: { chest: '42-44"', waist: '36-38"', hips: '42-44"' } },
      { size: 'XXL', measurements: { chest: '44-46"', waist: '38-40"', hips: '44-46"' } },
    ],
    women: [
      { size: 'XS', measurements: { bust: '32-34"', waist: '24-26"', hips: '34-36"' } },
      { size: 'S', measurements: { bust: '34-36"', waist: '26-28"', hips: '36-38"' } },
      { size: 'M', measurements: { bust: '36-38"', waist: '28-30"', hips: '38-40"' } },
      { size: 'L', measurements: { bust: '38-40"', waist: '30-32"', hips: '40-42"' } },
      { size: 'XL', measurements: { bust: '40-42"', waist: '32-34"', hips: '42-44"' } },
      { size: 'XXL', measurements: { bust: '42-44"', waist: '34-36"', hips: '44-46"' } },
    ],
  },
  shoes: {
    men: [
      { size: 'US 7', measurements: { length: '9.8"', width: '3.7"' } },
      { size: 'US 8', measurements: { length: '10.1"', width: '3.8"' } },
      { size: 'US 9', measurements: { length: '10.4"', width: '3.9"' } },
      { size: 'US 10', measurements: { length: '10.7"', width: '4.0"' } },
      { size: 'US 11', measurements: { length: '11.0"', width: '4.1"' } },
      { size: 'US 12', measurements: { length: '11.3"', width: '4.2"' } },
    ],
    women: [
      { size: 'US 5', measurements: { length: '8.8"', width: '3.3"' } },
      { size: 'US 6', measurements: { length: '9.1"', width: '3.4"' } },
      { size: 'US 7', measurements: { length: '9.4"', width: '3.5"' } },
      { size: 'US 8', measurements: { length: '9.7"', width: '3.6"' } },
      { size: 'US 9', measurements: { length: '10.0"', width: '3.7"' } },
      { size: 'US 10', measurements: { length: '10.3"', width: '3.8"' } },
    ],
  },
};

export function SizeGuide({
  productType,
  gender = 'unisex',
  sizeChart,
  userMeasurements,
  onSizeRecommend,
  className = '',
}: SizeGuideProps) {
  const [activeTab, setActiveTab] = useState<'chart' | 'measure' | 'fit'>('chart');
  const [measurements, setMeasurements] = useState(userMeasurements || {});

  // Use provided chart or default
  const chart =
    sizeChart ||
    (productType !== 'accessories' && gender !== 'unisex'
      ? defaultSizeCharts[productType][gender]
      : []);

  const measurementKeys =
    chart.length > 0 && chart[0] ? Object.keys(chart[0].measurements) : [];

  const handleMeasurementChange = (key: string, value: string) => {
    const numValue = parseFloat(value);
    if (!isNaN(numValue)) {
      setMeasurements((prev) => ({ ...prev, [key]: numValue }));
    }
  };

  const recommendSize = () => {
    // Simple size recommendation logic based on measurements
    if (Object.keys(measurements).length === 0) return null;

    // This is a simplified example - real implementation would be more sophisticated
    const avgMeasurement = Object.values(measurements).reduce((a, b) => a + b, 0) / Object.values(measurements).length;
    
    if (productType === 'clothing') {
      if (avgMeasurement < 32) return 'XS';
      if (avgMeasurement < 36) return 'S';
      if (avgMeasurement < 40) return 'M';
      if (avgMeasurement < 44) return 'L';
      if (avgMeasurement < 48) return 'XL';
      return 'XXL';
    }
    
    return chart[Math.floor(chart.length / 2)]?.size || 'M';
  };

  const recommendedSize = recommendSize();

  return (
    <div className={`bg-white rounded-lg shadow-lg ${className}`}>
      {/* Header */}
      <div className="p-4 border-b">
        <h2 className="text-xl font-semibold flex items-center gap-2">
          <Ruler className="w-5 h-5" />
          Size Guide
        </h2>
        <p className="text-sm text-gray-600 mt-1">
          Find your perfect fit
        </p>
      </div>

      {/* Tabs */}
      <div className="flex border-b">
        <button
          onClick={() => setActiveTab('chart')}
          className={`flex-1 px-4 py-3 text-sm font-medium transition-colors ${
            activeTab === 'chart'
              ? 'text-blue-600 border-b-2 border-blue-600'
              : 'text-gray-600 hover:text-gray-900'
          }`}
        >
          Size Chart
        </button>
        <button
          onClick={() => setActiveTab('measure')}
          className={`flex-1 px-4 py-3 text-sm font-medium transition-colors ${
            activeTab === 'measure'
              ? 'text-blue-600 border-b-2 border-blue-600'
              : 'text-gray-600 hover:text-gray-900'
          }`}
        >
          Measure Yourself
        </button>
        <button
          onClick={() => setActiveTab('fit')}
          className={`flex-1 px-4 py-3 text-sm font-medium transition-colors ${
            activeTab === 'fit'
              ? 'text-blue-600 border-b-2 border-blue-600'
              : 'text-gray-600 hover:text-gray-900'
          }`}
        >
          Fit Guide
        </button>
      </div>

      {/* Content */}
      <div className="p-4">
        {/* Size Chart Tab */}
        {activeTab === 'chart' && (
          <div className="overflow-x-auto">
            {chart.length > 0 ? (
              <table className="w-full text-sm">
                <thead>
                  <tr className="border-b">
                    <th className="text-left p-2 font-medium">Size</th>
                    {measurementKeys.map((key) => (
                      <th key={key} className="text-center p-2 font-medium capitalize">
                        {key}
                      </th>
                    ))}
                  </tr>
                </thead>
                <tbody>
                  {chart.map((row, idx) => (
                    <tr
                      key={idx}
                      className={`border-b hover:bg-gray-50 ${
                        recommendedSize === row.size ? 'bg-blue-50' : ''
                      }`}
                    >
                      <td className="p-2 font-medium">
                        {row.size}
                        {recommendedSize === row.size && (
                          <span className="ml-2 text-xs text-blue-600 font-semibold">
                            Recommended
                          </span>
                        )}
                      </td>
                      {measurementKeys.map((key) => (
                        <td key={key} className="text-center p-2">
                          {String((row.measurements as Record<string, string | number>)[key] || '-')}
                        </td>
                      ))}
                    </tr>
                  ))}
                </tbody>
              </table>
            ) : (
              <p className="text-center text-gray-500 py-8">
                Size chart not available for this product
              </p>
            )}
          </div>
        )}

        {/* Measure Yourself Tab */}
        {activeTab === 'measure' && (
          <div className="space-y-4">
            <div className="bg-blue-50 p-4 rounded-lg flex gap-3">
              <Info className="w-5 h-5 text-blue-600 flex-shrink-0 mt-0.5" />
              <div className="text-sm text-blue-900">
                <p className="font-medium mb-1">How to measure:</p>
                <ul className="list-disc list-inside space-y-1">
                  {productType === 'clothing' && (
                    <>
                      <li>Chest/Bust: Measure around the fullest part</li>
                      <li>Waist: Measure around your natural waistline</li>
                      <li>Hips: Measure around the fullest part</li>
                    </>
                  )}
                  {productType === 'shoes' && (
                    <>
                      <li>Length: Measure from heel to longest toe</li>
                      <li>Width: Measure across the widest part of your foot</li>
                    </>
                  )}
                </ul>
              </div>
            </div>

            {measurementKeys.map((key) => (
              <div key={key}>
                <label className="block text-sm font-medium text-gray-700 mb-1 capitalize">
                  {key}
                </label>
                <input
                  type="number"
                  step="0.1"
                  value={measurements[key] || ''}
                  onChange={(e) => handleMeasurementChange(key, e.target.value)}
                  placeholder="Enter measurement"
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                />
              </div>
            ))}

            <button
              onClick={() => {
                const size = recommendSize();
                if (size && onSizeRecommend) {
                  onSizeRecommend(size);
                }
              }}
              disabled={Object.keys(measurements).length === 0}
              className="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:bg-gray-300 disabled:cursor-not-allowed transition-colors"
            >
              Get Size Recommendation
            </button>

            {recommendedSize && (
              <div className="p-4 bg-green-50 border border-green-200 rounded-lg">
                <p className="text-green-900 font-medium">
                  Recommended Size: <span className="text-2xl">{recommendedSize}</span>
                </p>
              </div>
            )}
          </div>
        )}

        {/* Fit Guide Tab */}
        {activeTab === 'fit' && (
          <div className="space-y-4">
            <div className="space-y-3">
              <div className="border-l-4 border-blue-500 pl-4">
                <h3 className="font-medium text-gray-900">Slim Fit</h3>
                <p className="text-sm text-gray-600">
                  Tailored cut that follows the body's natural shape. Minimal ease for a modern, fitted look.
                </p>
              </div>

              <div className="border-l-4 border-green-500 pl-4">
                <h3 className="font-medium text-gray-900">Regular Fit</h3>
                <p className="text-sm text-gray-600">
                  Classic fit with comfortable room. Most popular choice for everyday wear.
                </p>
              </div>

              <div className="border-l-4 border-purple-500 pl-4">
                <h3 className="font-medium text-gray-900">Relaxed Fit</h3>
                <p className="text-sm text-gray-600">
                  Loose, comfortable fit with plenty of room for movement. Great for casual wear.
                </p>
              </div>
            </div>

            <div className="bg-gray-50 p-4 rounded-lg">
              <h4 className="font-medium mb-2">Between sizes?</h4>
              <p className="text-sm text-gray-600">
                If you're between sizes, we recommend sizing up for a more comfortable fit,
                or sizing down for a more fitted look.
              </p>
            </div>

            <div className="bg-gray-50 p-4 rounded-lg">
              <h4 className="font-medium mb-2">Model Information</h4>
              <p className="text-sm text-gray-600">
                Model is 6'0" / 183 cm and wearing size M<br />
                Chest: 38" / 97 cm, Waist: 32" / 81 cm
              </p>
            </div>
          </div>
        )}
      </div>
    </div>
  );
}

// Compact size selector with guide
export function SizeSelector({
  sizes,
  selectedSize,
  onSizeSelect,
  onGuideOpen,
  className = '',
}: {
  sizes: string[];
  selectedSize?: string;
  onSizeSelect: (size: string) => void;
  onGuideOpen?: () => void;
  className?: string;
}) {
  return (
    <div className={className}>
      <div className="flex items-center justify-between mb-2">
        <label className="text-sm font-medium text-gray-700">Size</label>
        {onGuideOpen && (
          <button
            onClick={onGuideOpen}
            className="text-sm text-blue-600 hover:text-blue-700 font-medium flex items-center gap-1"
          >
            <Ruler className="w-4 h-4" />
            Size Guide
          </button>
        )}
      </div>
      <div className="flex flex-wrap gap-2">
        {sizes.map((size) => (
          <button
            key={size}
            onClick={() => onSizeSelect(size)}
            className={`px-4 py-2 border rounded-lg font-medium transition-all ${
              selectedSize === size
                ? 'border-blue-600 bg-blue-50 text-blue-600'
                : 'border-gray-300 hover:border-gray-400'
            }`}
          >
            {size}
          </button>
        ))}
      </div>
    </div>
  );
}
