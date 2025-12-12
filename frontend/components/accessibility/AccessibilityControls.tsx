'use client';

import { useState, useEffect } from 'react';
import { Type, Contrast, MousePointer, Maximize2, Minimize2 } from 'lucide-react';

/**
 * Accessibility controls panel for user customization
 * WCAG 2.1 Success Criterion 1.4.3, 1.4.4, 1.4.8
 */
export function AccessibilityControls() {
  const [isOpen, setIsOpen] = useState(false);
  const [fontSize, setFontSize] = useState(100);
  const [highContrast, setHighContrast] = useState(false);
  const [reducedMotion, setReducedMotion] = useState(false);
  const [largerCursor, setLargerCursor] = useState(false);

  useEffect(() => {
    // Apply font size
    document.documentElement.style.fontSize = `${fontSize}%`;

    // Apply high contrast
    if (highContrast) {
      document.documentElement.classList.add('high-contrast');
    } else {
      document.documentElement.classList.remove('high-contrast');
    }

    // Apply reduced motion
    if (reducedMotion) {
      document.documentElement.classList.add('reduce-motion');
    } else {
      document.documentElement.classList.remove('reduce-motion');
    }

    // Apply larger cursor
    if (largerCursor) {
      document.documentElement.classList.add('large-cursor');
    } else {
      document.documentElement.classList.remove('large-cursor');
    }
  }, [fontSize, highContrast, reducedMotion, largerCursor]);

  // Load preferences from localStorage
  useEffect(() => {
    const savedFontSize = localStorage.getItem('a11y-font-size');
    const savedHighContrast = localStorage.getItem('a11y-high-contrast');
    const savedReducedMotion = localStorage.getItem('a11y-reduced-motion');
    const savedLargerCursor = localStorage.getItem('a11y-larger-cursor');

    if (savedFontSize) setFontSize(parseInt(savedFontSize));
    if (savedHighContrast) setHighContrast(savedHighContrast === 'true');
    if (savedReducedMotion) setReducedMotion(savedReducedMotion === 'true');
    if (savedLargerCursor) setLargerCursor(savedLargerCursor === 'true');
  }, []);

  const handleFontSizeChange = (newSize: number) => {
    setFontSize(newSize);
    localStorage.setItem('a11y-font-size', newSize.toString());
  };

  const handleHighContrastToggle = () => {
    const newValue = !highContrast;
    setHighContrast(newValue);
    localStorage.setItem('a11y-high-contrast', newValue.toString());
  };

  const handleReducedMotionToggle = () => {
    const newValue = !reducedMotion;
    setReducedMotion(newValue);
    localStorage.setItem('a11y-reduced-motion', newValue.toString());
  };

  const handleLargerCursorToggle = () => {
    const newValue = !largerCursor;
    setLargerCursor(newValue);
    localStorage.setItem('a11y-larger-cursor', newValue.toString());
  };

  const resetAll = () => {
    setFontSize(100);
    setHighContrast(false);
    setReducedMotion(false);
    setLargerCursor(false);
    localStorage.removeItem('a11y-font-size');
    localStorage.removeItem('a11y-high-contrast');
    localStorage.removeItem('a11y-reduced-motion');
    localStorage.removeItem('a11y-larger-cursor');
  };

  return (
    <>
      {/* Toggle Button */}
      <button
        onClick={() => setIsOpen(!isOpen)}
        className="fixed bottom-4 right-4 z-50 bg-blue-600 text-white p-3 rounded-full shadow-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-600 focus:ring-offset-2"
        aria-label="Accessibility settings"
        aria-expanded={isOpen}
      >
        {isOpen ? <Minimize2 className="w-6 h-6" /> : <Maximize2 className="w-6 h-6" />}
      </button>

      {/* Controls Panel */}
      {isOpen && (
        <div
          className="fixed bottom-20 right-4 z-50 bg-white rounded-lg shadow-xl w-80 max-w-full p-6"
          role="dialog"
          aria-label="Accessibility controls"
        >
          <h2 className="text-xl font-bold mb-4">Accessibility Settings</h2>

          {/* Font Size */}
          <div className="mb-6">
            <label htmlFor="font-size" className="flex items-center gap-2 font-medium mb-2">
              <Type className="w-5 h-5" />
              Text Size: {fontSize}%
            </label>
            <input
              id="font-size"
              type="range"
              min="75"
              max="150"
              step="25"
              value={fontSize}
              onChange={(e) => handleFontSizeChange(parseInt(e.target.value))}
              className="w-full"
              aria-valuemin={75}
              aria-valuemax={150}
              aria-valuenow={fontSize}
              aria-valuetext={`${fontSize} percent`}
            />
            <div className="flex justify-between text-xs text-gray-500 mt-1">
              <span>75%</span>
              <span>100%</span>
              <span>125%</span>
              <span>150%</span>
            </div>
          </div>

          {/* High Contrast */}
          <div className="mb-4">
            <label className="flex items-center justify-between cursor-pointer">
              <span className="flex items-center gap-2 font-medium">
                <Contrast className="w-5 h-5" />
                High Contrast
              </span>
              <input
                type="checkbox"
                checked={highContrast}
                onChange={handleHighContrastToggle}
                className="w-5 h-5"
                aria-describedby="high-contrast-desc"
              />
            </label>
            <p id="high-contrast-desc" className="text-sm text-gray-600 mt-1 ml-7">
              Increases contrast for better visibility
            </p>
          </div>

          {/* Reduced Motion */}
          <div className="mb-4">
            <label className="flex items-center justify-between cursor-pointer">
              <span className="flex items-center gap-2 font-medium">
                <span className="w-5 h-5 flex items-center justify-center">🎬</span>
                Reduce Motion
              </span>
              <input
                type="checkbox"
                checked={reducedMotion}
                onChange={handleReducedMotionToggle}
                className="w-5 h-5"
                aria-describedby="reduced-motion-desc"
              />
            </label>
            <p id="reduced-motion-desc" className="text-sm text-gray-600 mt-1 ml-7">
              Minimizes animations and transitions
            </p>
          </div>

          {/* Larger Cursor */}
          <div className="mb-6">
            <label className="flex items-center justify-between cursor-pointer">
              <span className="flex items-center gap-2 font-medium">
                <MousePointer className="w-5 h-5" />
                Large Cursor
              </span>
              <input
                type="checkbox"
                checked={largerCursor}
                onChange={handleLargerCursorToggle}
                className="w-5 h-5"
                aria-describedby="large-cursor-desc"
              />
            </label>
            <p id="large-cursor-desc" className="text-sm text-gray-600 mt-1 ml-7">
              Makes the cursor easier to see
            </p>
          </div>

          {/* Reset Button */}
          <button
            onClick={resetAll}
            className="w-full bg-gray-200 text-gray-700 py-2 rounded-lg hover:bg-gray-300 transition"
          >
            Reset to Defaults
          </button>
        </div>
      )}
    </>
  );
}

export default AccessibilityControls;
