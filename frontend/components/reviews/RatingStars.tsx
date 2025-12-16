'use client';

import { useState } from 'react';
import { Star, StarHalf } from 'lucide-react';

interface RatingStarsProps {
  rating: number;
  maxRating?: number;
  size?: 'sm' | 'md' | 'lg';
  interactive?: boolean;
  onRatingChange?: (rating: number) => void;
  showValue?: boolean;
}

export default function RatingStars({
  rating,
  maxRating = 5,
  size = 'md',
  interactive = false,
  onRatingChange,
  showValue = false,
}: RatingStarsProps) {
  const [hoverRating, setHoverRating] = useState(0);

  const sizeClasses = {
    sm: 'w-4 h-4',
    md: 'w-5 h-5',
    lg: 'w-6 h-6',
  };

  const displayRating = interactive && hoverRating > 0 ? hoverRating : rating;

  const renderStar = (index: number) => {
    const starNumber = index + 1;
    const isFilled = starNumber <= Math.floor(displayRating);
    const isHalf = !isFilled && starNumber === Math.ceil(displayRating) && displayRating % 1 !== 0;

    return (
      <button
        key={index}
        type="button"
        className={`${interactive ? 'cursor-pointer hover:scale-110' : 'cursor-default'} transition-transform`}
        onClick={() => interactive && onRatingChange?.(starNumber)}
        onMouseEnter={() => interactive && setHoverRating(starNumber)}
        onMouseLeave={() => interactive && setHoverRating(0)}
        disabled={!interactive}
      >
        {isHalf ? (
          <StarHalf className={`${sizeClasses[size]} fill-yellow-400 text-yellow-400`} />
        ) : (
          <Star
            className={`${sizeClasses[size]} ${
              isFilled ? 'fill-yellow-400 text-yellow-400' : 'fill-gray-200 text-gray-200'
            }`}
          />
        )}
      </button>
    );
  };

  return (
    <div className="flex items-center gap-1">
      {Array.from({ length: maxRating }, (_, i) => renderStar(i))}
      {showValue && (
        <span className="ml-2 text-sm text-gray-600">
          {displayRating.toFixed(1)}
        </span>
      )}
    </div>
  );
}
