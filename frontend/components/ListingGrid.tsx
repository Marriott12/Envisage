import React from 'react';

interface ListingGridProps {
  children: React.ReactNode;
}

export default function ListingGrid({ children }: ListingGridProps) {
  return (
    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
      {children}
    </div>
  );
}
