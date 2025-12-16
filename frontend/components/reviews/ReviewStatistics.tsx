'use client';

import RatingStars from './RatingStars';

interface ReviewStatisticsProps {
  statistics: {
    total_reviews: number;
    average_rating: number;
    five_star: number;
    four_star: number;
    three_star: number;
    two_star: number;
    one_star: number;
    verified_purchases: number;
  };
  onFilterByRating?: (rating: number | null) => void;
}

export default function ReviewStatistics({ statistics, onFilterByRating }: ReviewStatisticsProps) {
  const ratings = [
    { star: 5, count: statistics.five_star },
    { star: 4, count: statistics.four_star },
    { star: 3, count: statistics.three_star },
    { star: 2, count: statistics.two_star },
    { star: 1, count: statistics.one_star },
  ];

  const getPercentage = (count: number) => {
    if (statistics.total_reviews === 0) return 0;
    return Math.round((count / statistics.total_reviews) * 100);
  };

  return (
    <div className="bg-white p-6 rounded-lg shadow-sm border">
      <div className="grid md:grid-cols-2 gap-8">
        {/* Average Rating */}
        <div className="text-center md:border-r">
          <div className="text-5xl font-bold text-gray-900 mb-2">
            {statistics.average_rating.toFixed(1)}
          </div>
          <RatingStars rating={statistics.average_rating} size="lg" />
          <p className="text-sm text-gray-600 mt-3">
            Based on {statistics.total_reviews} {statistics.total_reviews === 1 ? 'review' : 'reviews'}
          </p>
          {statistics.verified_purchases > 0 && (
            <p className="text-xs text-green-600 mt-1">
              {statistics.verified_purchases} verified {statistics.verified_purchases === 1 ? 'purchase' : 'purchases'}
            </p>
          )}
        </div>

        {/* Rating Distribution */}
        <div className="space-y-2">
          {ratings.map(({ star, count }) => {
            const percentage = getPercentage(count);
            return (
              <button
                key={star}
                onClick={() => onFilterByRating?.(star)}
                className="w-full flex items-center gap-3 hover:bg-gray-50 p-2 rounded-lg transition-colors"
              >
                <div className="flex items-center gap-1 min-w-[80px]">
                  <span className="text-sm font-medium">{star}</span>
                  <RatingStars rating={star} size="sm" maxRating={1} />
                </div>
                <div className="flex-1 h-2 bg-gray-200 rounded-full overflow-hidden">
                  <div
                    className="h-full bg-yellow-400 transition-all duration-300"
                    style={{ width: `${percentage}%` }}
                  />
                </div>
                <span className="text-sm text-gray-600 min-w-[60px] text-right">
                  {percentage}% ({count})
                </span>
              </button>
            );
          })}
        </div>
      </div>
    </div>
  );
}
