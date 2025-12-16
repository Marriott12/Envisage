'use client';

import { useState } from 'react';
import { ThumbsUp, ThumbsDown, CheckCircle, MoreVertical, Flag } from 'lucide-react';
import RatingStars from './RatingStars';
import { formatDistanceToNow } from 'date-fns';

interface Review {
  id: string;
  user: {
    id: string;
    name: string;
    avatar?: string;
  };
  rating: number;
  title: string;
  comment: string;
  is_verified_purchase: boolean;
  helpful_count: number;
  not_helpful_count: number;
  created_at: string;
  images?: Array<{
    id: string;
    image_url: string;
    thumbnail_url: string;
  }>;
  responses?: Array<{
    id: string;
    user: { name: string };
    responder_type: 'admin' | 'seller';
    response: string;
    created_at: string;
  }>;
}

interface ReviewListProps {
  reviews: Review[];
  onHelpful: (reviewId: string, isHelpful: boolean) => Promise<void>;
  onReport?: (reviewId: string) => void;
  currentUserId?: string;
}

export default function ReviewList({ reviews, onHelpful, onReport, currentUserId }: ReviewListProps) {
  const [votingStates, setVotingStates] = useState<Record<string, boolean>>({});
  const [selectedImage, setSelectedImage] = useState<string | null>(null);

  const handleVote = async (reviewId: string, isHelpful: boolean) => {
    if (votingStates[reviewId]) return;

    setVotingStates(prev => ({ ...prev, [reviewId]: true }));
    try {
      await onHelpful(reviewId, isHelpful);
    } finally {
      setVotingStates(prev => ({ ...prev, [reviewId]: false }));
    }
  };

  if (reviews.length === 0) {
    return (
      <div className="text-center py-12 bg-gray-50 rounded-lg">
        <p className="text-gray-600">No reviews yet. Be the first to review this product!</p>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      {reviews.map((review) => (
        <div key={review.id} className="bg-white p-6 rounded-lg shadow-sm border">
          {/* Header */}
          <div className="flex items-start justify-between mb-4">
            <div className="flex items-center gap-3">
              <div className="w-10 h-10 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-full flex items-center justify-center text-white font-semibold">
                {review.user.name.charAt(0).toUpperCase()}
              </div>
              <div>
                <div className="flex items-center gap-2">
                  <p className="font-medium text-gray-900">{review.user.name}</p>
                  {review.is_verified_purchase && (
                    <span className="flex items-center gap-1 text-xs text-green-600 bg-green-50 px-2 py-1 rounded-full">
                      <CheckCircle className="w-3 h-3" />
                      Verified Purchase
                    </span>
                  )}
                </div>
                <p className="text-sm text-gray-500">
                  {formatDistanceToNow(new Date(review.created_at), { addSuffix: true })}
                </p>
              </div>
            </div>

            {onReport && (
              <button
                onClick={() => onReport(review.id)}
                className="text-gray-400 hover:text-gray-600"
                title="Report review"
              >
                <Flag className="w-5 h-5" />
              </button>
            )}
          </div>

          {/* Rating */}
          <div className="mb-3">
            <RatingStars rating={review.rating} size="sm" />
          </div>

          {/* Title */}
          <h4 className="font-semibold text-gray-900 mb-2">{review.title}</h4>

          {/* Comment */}
          <p className="text-gray-700 leading-relaxed mb-4">{review.comment}</p>

          {/* Images */}
          {review.images && review.images.length > 0 && (
            <div className="flex gap-2 mb-4 overflow-x-auto pb-2">
              {review.images.map((image) => (
                <button
                  key={image.id}
                  onClick={() => setSelectedImage(image.image_url)}
                  className="flex-shrink-0 w-20 h-20 rounded-lg overflow-hidden hover:opacity-80 transition-opacity"
                >
                  <img
                    src={image.thumbnail_url}
                    alt="Review"
                    className="w-full h-full object-cover"
                  />
                </button>
              ))}
            </div>
          )}

          {/* Helpful votes */}
          <div className="flex items-center gap-4 pt-4 border-t">
            <span className="text-sm text-gray-600">Was this helpful?</span>
            <div className="flex items-center gap-2">
              <button
                onClick={() => handleVote(review.id, true)}
                disabled={votingStates[review.id]}
                className="flex items-center gap-1 px-3 py-1.5 rounded-lg border border-gray-300 hover:bg-gray-50 disabled:opacity-50 transition-colors"
              >
                <ThumbsUp className="w-4 h-4" />
                <span className="text-sm">{review.helpful_count}</span>
              </button>
              <button
                onClick={() => handleVote(review.id, false)}
                disabled={votingStates[review.id]}
                className="flex items-center gap-1 px-3 py-1.5 rounded-lg border border-gray-300 hover:bg-gray-50 disabled:opacity-50 transition-colors"
              >
                <ThumbsDown className="w-4 h-4" />
                <span className="text-sm">{review.not_helpful_count}</span>
              </button>
            </div>
          </div>

          {/* Seller/Admin Responses */}
          {review.responses && review.responses.length > 0 && (
            <div className="mt-4 pl-4 border-l-2 border-indigo-200 space-y-3">
              {review.responses.map((response) => (
                <div key={response.id} className="bg-indigo-50 p-4 rounded-lg">
                  <div className="flex items-center gap-2 mb-2">
                    <span className="text-sm font-medium text-indigo-900">
                      {response.user.name}
                    </span>
                    <span className="text-xs text-indigo-600 bg-indigo-100 px-2 py-0.5 rounded-full">
                      {response.responder_type === 'seller' ? 'Seller' : 'Admin'}
                    </span>
                    <span className="text-xs text-gray-500">
                      {formatDistanceToNow(new Date(response.created_at), { addSuffix: true })}
                    </span>
                  </div>
                  <p className="text-sm text-gray-700">{response.response}</p>
                </div>
              ))}
            </div>
          )}
        </div>
      ))}

      {/* Image Modal */}
      {selectedImage && (
        <div
          className="fixed inset-0 bg-black/80 z-50 flex items-center justify-center p-4"
          onClick={() => setSelectedImage(null)}
        >
          <img
            src={selectedImage}
            alt="Review"
            className="max-w-full max-h-full object-contain"
          />
        </div>
      )}
    </div>
  );
}
