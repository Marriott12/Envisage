'use client';

import { useState, useEffect, useRef } from 'react';

interface PriceRangeSliderProps {
  min: number;
  max: number;
  value: [number, number];
  onChange: (value: [number, number]) => void;
  currency?: string;
  step?: number;
}

export default function PriceRangeSlider({
  min,
  max,
  value,
  onChange,
  currency = 'ZMW',
  step = 1,
}: PriceRangeSliderProps) {
  const [localValue, setLocalValue] = useState<[number, number]>(value);
  const [isDragging, setIsDragging] = useState<'min' | 'max' | null>(null);
  const trackRef = useRef<HTMLDivElement>(null);

  useEffect(() => {
    setLocalValue(value);
  }, [value]);

  const handleMinChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const newMin = Math.min(Number(e.target.value), localValue[1] - step);
    setLocalValue([newMin, localValue[1]]);
  };

  const handleMaxChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const newMax = Math.max(Number(e.target.value), localValue[0] + step);
    setLocalValue([localValue[0], newMax]);
  };

  const handleMinBlur = () => {
    onChange(localValue);
  };

  const handleMaxBlur = () => {
    onChange(localValue);
  };

  const getPercentage = (val: number) => {
    return ((val - min) / (max - min)) * 100;
  };

  return (
    <div className="w-full">
      {/* Range Track */}
      <div className="relative pt-2 pb-8">
        <div
          ref={trackRef}
          className="relative h-2 bg-gray-200 rounded-full"
        >
          {/* Active Range */}
          <div
            className="absolute h-full bg-blue-600 rounded-full"
            style={{
              left: `${getPercentage(localValue[0])}%`,
              width: `${getPercentage(localValue[1]) - getPercentage(localValue[0])}%`,
            }}
          />

          {/* Min Thumb */}
          <div
            className="absolute top-1/2 -translate-y-1/2 -ml-3 w-6 h-6 bg-white border-2 border-blue-600 rounded-full shadow-md cursor-pointer hover:scale-110 transition-transform"
            style={{ left: `${getPercentage(localValue[0])}%` }}
          />

          {/* Max Thumb */}
          <div
            className="absolute top-1/2 -translate-y-1/2 -ml-3 w-6 h-6 bg-white border-2 border-blue-600 rounded-full shadow-md cursor-pointer hover:scale-110 transition-transform"
            style={{ left: `${getPercentage(localValue[1])}%` }}
          />

          {/* Hidden Range Inputs for Accessibility */}
          <input
            type="range"
            min={min}
            max={max}
            step={step}
            value={localValue[0]}
            onChange={handleMinChange}
            onMouseUp={handleMinBlur}
            onTouchEnd={handleMinBlur}
            className="absolute w-full h-full opacity-0 cursor-pointer z-10"
          />
          <input
            type="range"
            min={min}
            max={max}
            step={step}
            value={localValue[1]}
            onChange={handleMaxChange}
            onMouseUp={handleMaxBlur}
            onTouchEnd={handleMaxBlur}
            className="absolute w-full h-full opacity-0 cursor-pointer z-10"
          />
        </div>

        {/* Value Labels */}
        <div className="absolute -bottom-1 left-0 right-0 flex justify-between text-xs text-gray-600">
          <span>{currency} {min}</span>
          <span>{currency} {max}</span>
        </div>
      </div>

      {/* Input Fields */}
      <div className="flex items-center gap-4 mt-4">
        <div className="flex-1">
          <label className="block text-xs text-gray-600 mb-1">Min Price</label>
          <div className="relative">
            <span className="absolute left-3 top-1/2 -translate-y-1/2 text-sm text-gray-500">
              {currency}
            </span>
            <input
              type="number"
              min={min}
              max={localValue[1]}
              step={step}
              value={localValue[0]}
              onChange={(e) => {
                const newMin = Math.max(min, Math.min(Number(e.target.value), localValue[1] - step));
                setLocalValue([newMin, localValue[1]]);
              }}
              onBlur={handleMinBlur}
              className="w-full pl-12 pr-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            />
          </div>
        </div>

        <div className="pt-6 text-gray-400">—</div>

        <div className="flex-1">
          <label className="block text-xs text-gray-600 mb-1">Max Price</label>
          <div className="relative">
            <span className="absolute left-3 top-1/2 -translate-y-1/2 text-sm text-gray-500">
              {currency}
            </span>
            <input
              type="number"
              min={localValue[0]}
              max={max}
              step={step}
              value={localValue[1]}
              onChange={(e) => {
                const newMax = Math.min(max, Math.max(Number(e.target.value), localValue[0] + step));
                setLocalValue([localValue[0], newMax]);
              }}
              onBlur={handleMaxBlur}
              className="w-full pl-12 pr-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            />
          </div>
        </div>
      </div>
    </div>
  );
}
