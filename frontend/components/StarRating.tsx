'use client';

import { StarIcon } from '@heroicons/react/24/solid';
import { StarIcon as StarOutlineIcon } from '@heroicons/react/24/outline';

interface StarRatingProps {
  rating: number;
  maxRating?: number;
  size?: 'xs' | 'sm' | 'md' | 'lg' | 'xl';
  showNumber?: boolean;
  interactive?: boolean;
  onChange?: (rating: number) => void;
}

export default function StarRating({
  rating,
  maxRating = 5,
  size = 'md',
  showNumber = false,
  interactive = false,
  onChange,
}: StarRatingProps) {
  const sizeClasses = {
    xs: 'h-3 w-3',
    sm: 'h-4 w-4',
    md: 'h-5 w-5',
    lg: 'h-6 w-6',
    xl: 'h-8 w-8',
  };

  const handleClick = (star: number) => {
    if (interactive && onChange) {
      onChange(star);
    }
  };

  return (
    <div className="flex items-center gap-1">
      <div className="flex">
        {Array.from({ length: maxRating }, (_, index) => index + 1).map((star) => {
          const isFullStar = star <= Math.floor(rating);
          const isPartialStar = star === Math.ceil(rating) && rating % 1 !== 0;
          const partialPercent = isPartialStar ? (rating % 1) * 100 : 0;

          return (
            <div
              key={star}
              className={`relative ${interactive ? 'cursor-pointer' : ''}`}
              onClick={() => handleClick(star)}
            >
              {isPartialStar ? (
                <div className="relative">
                  <StarOutlineIcon className={`${sizeClasses[size]} text-gray-300`} />
                  <div
                    className="absolute top-0 left-0 overflow-hidden"
                    style={{ width: `${partialPercent}%` }}
                  >
                    <StarIcon className={`${sizeClasses[size]} text-yellow-400`} />
                  </div>
                </div>
              ) : (
                <StarIcon
                  className={`${sizeClasses[size]} ${
                    isFullStar ? 'text-yellow-400' : 'text-gray-300'
                  }`}
                />
              )}
            </div>
          );
        })}
      </div>
      {showNumber && (
        <span className="text-sm text-gray-600 ml-1">
          {rating.toFixed(1)}
        </span>
      )}
    </div>
  );
}
