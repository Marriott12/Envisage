'use client';

import React, { useState } from 'react';

interface CompareButtonProps {
  productId: number;
}

export default function CompareButton({ productId }: CompareButtonProps) {
  const [isInCompare, setIsInCompare] = useState(() => {
    if (typeof window === 'undefined') return false;
    const compareIds = JSON.parse(localStorage.getItem('compare_products') || '[]');
    return compareIds.includes(productId);
  });

  const toggleCompare = (e: React.MouseEvent) => {
    e.preventDefault();
    e.stopPropagation();

    const compareIds = JSON.parse(localStorage.getItem('compare_products') || '[]');

    if (isInCompare) {
      const updated = compareIds.filter((id: number) => id !== productId);
      localStorage.setItem('compare_products', JSON.stringify(updated));
      setIsInCompare(false);
    } else {
      if (compareIds.length >= 4) {
        alert('You can only compare up to 4 products at once.');
        return;
      }
      compareIds.push(productId);
      localStorage.setItem('compare_products', JSON.stringify(compareIds));
      setIsInCompare(true);
    }

    // Dispatch custom event to update compare count in header
    window.dispatchEvent(new Event('compareUpdated'));
  };

  return (
    <button
      onClick={toggleCompare}
      className={`flex items-center gap-2 px-3 py-1.5 rounded-md text-sm font-medium transition ${
        isInCompare
          ? 'bg-blue-600 text-white hover:bg-blue-700'
          : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
      }`}
      title={isInCompare ? 'Remove from comparison' : 'Add to comparison'}
    >
      <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path
          strokeLinecap="round"
          strokeLinejoin="round"
          strokeWidth={2}
          d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"
        />
      </svg>
      {isInCompare ? 'In Comparison' : 'Compare'}
    </button>
  );
}
