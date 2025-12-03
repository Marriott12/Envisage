'use client';

import { useState, useEffect } from 'react';
import { StarIcon } from '@heroicons/react/24/solid';
import { StarIcon as StarOutlineIcon } from '@heroicons/react/24/outline';
import { HandThumbUpIcon, HandThumbDownIcon } from '@heroicons/react/24/outline';
import axios from 'axios';
import { toast } from 'react-hot-toast';

const API_BASE_URL = process.env.NEXT_PUBLIC_API_URL || 'https://envisagezm.com/api';

interface Review {
  id: number;
  user_id: number;
  product_id: number;
  rating: number;
  title?: string;
  review?: string;
  comment?: string;
  images?: string[];
  verified_purchase: boolean;
  helpful_count: number;
  not_helpful_count: number;
  user_name: string;
  user_avatar?: string;
  created_at: string;
  user?: {
    name: string;
    avatar?: string;
  };
}

interface ReviewStats {
  total: number;
  average: number;
  distribution: {
    [key: number]: number;
  };
}

interface ProductReviewsProps {
  productId: number;
  canReview?: boolean;
  onReviewSubmitted?: () => void;
}

export default function ProductReviews({ productId, canReview = false, onReviewSubmitted }: ProductReviewsProps) {
  const [reviews, setReviews] = useState<Review[]>([]);
  const [stats, setStats] = useState<ReviewStats | null>(null);
  const [loading, setLoading] = useState(true);
  const [showReviewForm, setShowReviewForm] = useState(false);
  const [sortBy, setSortBy] = useState('recent');
  const [filterRating, setFilterRating] = useState<number | null>(null);
  const [currentPage, setCurrentPage] = useState(1);
  const [totalPages, setTotalPages] = useState(1);
  
  // Review form state
  const [rating, setRating] = useState(5);
  const [title, setTitle] = useState('');
  const [comment, setComment] = useState('');
  const [submitting, setSubmitting] = useState(false);

  useEffect(() => {
    fetchReviews();
  }, [productId, sortBy, filterRating, currentPage]);

  const fetchReviews = async () => {
    try {
      setLoading(true);
      const params = new URLSearchParams({
        per_page: '10',
        sort_by: sortBy,
        page: currentPage.toString(),
      });
      
      if (filterRating) {
        params.append('rating', filterRating.toString());
      }

      const response = await axios.get(`${API_BASE_URL}/products/${productId}/reviews?${params}`);
      
      if (response.data.status === 'success') {
        setReviews(response.data.data.reviews.data || []);
        setStats(response.data.data.stats);
        setTotalPages(response.data.data.reviews.last_page || 1);
      }
    } catch (error) {
      console.error('Error fetching reviews:', error);
      toast.error('Failed to load reviews');
    } finally {
      setLoading(false);
    }
  };

  const handleSubmitReview = async (e: React.FormEvent) => {
    e.preventDefault();
    
    if (!comment.trim()) {
      toast.error('Please write a review');
      return;
    }

    try {
      setSubmitting(true);
      const token = localStorage.getItem('token');
      
      const response = await axios.post(
        `${API_BASE_URL}/products/${productId}/reviews`,
        {
          rating,
          title: title.trim() || null,
          comment: comment.trim(),
          review: comment.trim(), // Backend compatibility
        },
        {
          headers: {
            Authorization: `Bearer ${token}`,
          },
        }
      );

      if (response.data.status === 'success') {
        toast.success('Review submitted successfully!');
        setShowReviewForm(false);
        setRating(5);
        setTitle('');
        setComment('');
        fetchReviews();
        onReviewSubmitted?.();
      }
    } catch (error: any) {
      console.error('Error submitting review:', error);
      toast.error(error.response?.data?.message || 'Failed to submit review');
    } finally {
      setSubmitting(false);
    }
  };

  const handleMarkHelpful = async (reviewId: number, isHelpful: boolean) => {
    try {
      const token = localStorage.getItem('token');
      
      await axios.post(
        `${API_BASE_URL}/products/${productId}/reviews/${reviewId}/helpful`,
        { is_helpful: isHelpful },
        {
          headers: {
            Authorization: `Bearer ${token}`,
          },
        }
      );

      fetchReviews();
      toast.success('Thank you for your feedback!');
    } catch (error) {
      console.error('Error marking review helpful:', error);
      toast.error('Please log in to mark reviews as helpful');
    }
  };

  const renderStars = (rating: number, size: 'sm' | 'md' | 'lg' = 'md') => {
    const sizeClasses = {
      sm: 'h-4 w-4',
      md: 'h-5 w-5',
      lg: 'h-6 w-6',
    };

    return (
      <div className="flex">
        {[1, 2, 3, 4, 5].map((star) => (
          <StarIcon
            key={star}
            className={`${sizeClasses[size]} ${
              star <= rating ? 'text-yellow-400' : 'text-gray-300'
            }`}
          />
        ))}
      </div>
    );
  };

  const renderRatingDistribution = () => {
    if (!stats) return null;

    return (
      <div className="space-y-2">
        {[5, 4, 3, 2, 1].map((star) => {
          const count = stats.distribution[star] || 0;
          const percentage = stats.total > 0 ? (count / stats.total) * 100 : 0;

          return (
            <button
              key={star}
              onClick={() => setFilterRating(filterRating === star ? null : star)}
              className={`w-full flex items-center gap-2 text-sm hover:bg-gray-50 p-1 rounded ${
                filterRating === star ? 'bg-blue-50' : ''
              }`}
            >
              <span className="text-blue-600 font-medium w-10">{star} star</span>
              <div className="flex-1 h-2 bg-gray-200 rounded-full overflow-hidden">
                <div
                  className="h-full bg-yellow-400"
                  style={{ width: `${percentage}%` }}
                />
              </div>
              <span className="text-gray-600 w-12 text-right">{count}</span>
            </button>
          );
        })}
      </div>
    );
  };

  return (
    <div className="bg-white rounded-lg shadow-sm p-6">
      <h2 className="text-2xl font-bold mb-6">Customer Reviews</h2>

      {/* Rating Summary */}
      {stats && (
        <div className="mb-8 pb-8 border-b border-gray-200">
          <div className="grid md:grid-cols-2 gap-8">
            <div className="text-center">
              <div className="text-5xl font-bold text-gray-900 mb-2">{stats.average}</div>
              {renderStars(Math.round(stats.average), 'lg')}
              <p className="text-gray-600 mt-2">{stats.total} reviews</p>
            </div>
            <div>{renderRatingDistribution()}</div>
          </div>

          {canReview && !showReviewForm && (
            <button
              onClick={() => setShowReviewForm(true)}
              className="mt-6 w-full md:w-auto px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
            >
              Write a Review
            </button>
          )}
        </div>
      )}

      {/* Review Form */}
      {showReviewForm && (
        <form onSubmit={handleSubmitReview} className="mb-8 p-6 bg-gray-50 rounded-lg">
          <h3 className="text-lg font-semibold mb-4">Write Your Review</h3>
          
          <div className="mb-4">
            <label className="block text-sm font-medium text-gray-700 mb-2">Rating</label>
            <div className="flex gap-2">
              {[1, 2, 3, 4, 5].map((star) => (
                <button
                  key={star}
                  type="button"
                  onClick={() => setRating(star)}
                  className="focus:outline-none"
                >
                  {star <= rating ? (
                    <StarIcon className="h-8 w-8 text-yellow-400" />
                  ) : (
                    <StarOutlineIcon className="h-8 w-8 text-gray-300" />
                  )}
                </button>
              ))}
            </div>
          </div>

          <div className="mb-4">
            <label htmlFor="title" className="block text-sm font-medium text-gray-700 mb-2">
              Title (Optional)
            </label>
            <input
              type="text"
              id="title"
              value={title}
              onChange={(e) => setTitle(e.target.value)}
              className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              placeholder="Sum up your review"
              maxLength={255}
            />
          </div>

          <div className="mb-4">
            <label htmlFor="comment" className="block text-sm font-medium text-gray-700 mb-2">
              Review
            </label>
            <textarea
              id="comment"
              value={comment}
              onChange={(e) => setComment(e.target.value)}
              className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              rows={4}
              placeholder="Share your experience with this product"
              maxLength={2000}
              required
            />
          </div>

          <div className="flex gap-3">
            <button
              type="submit"
              disabled={submitting}
              className="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:bg-gray-400 disabled:cursor-not-allowed transition-colors"
            >
              {submitting ? 'Submitting...' : 'Submit Review'}
            </button>
            <button
              type="button"
              onClick={() => setShowReviewForm(false)}
              className="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors"
            >
              Cancel
            </button>
          </div>
        </form>
      )}

      {/* Sort and Filter */}
      <div className="mb-6 flex flex-wrap gap-4 items-center">
        <select
          value={sortBy}
          onChange={(e) => setSortBy(e.target.value)}
          className="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
        >
          <option value="recent">Most Recent</option>
          <option value="helpful">Most Helpful</option>
          <option value="rating_high">Highest Rating</option>
          <option value="rating_low">Lowest Rating</option>
        </select>

        {filterRating && (
          <button
            onClick={() => setFilterRating(null)}
            className="px-4 py-2 bg-blue-100 text-blue-700 rounded-lg hover:bg-blue-200 transition-colors"
          >
            Clear Filter ({filterRating} stars)
          </button>
        )}
      </div>

      {/* Reviews List */}
      {loading ? (
        <div className="text-center py-12">
          <div className="inline-block h-8 w-8 animate-spin rounded-full border-4 border-solid border-blue-600 border-r-transparent"></div>
        </div>
      ) : reviews.length === 0 ? (
        <div className="text-center py-12 text-gray-500">
          <p>No reviews yet. Be the first to review this product!</p>
        </div>
      ) : (
        <div className="space-y-6">
          {reviews.map((review) => (
            <div key={review.id} className="border-b border-gray-200 pb-6 last:border-0">
              <div className="flex items-start gap-4">
                {/* User Avatar */}
                <div className="flex-shrink-0">
                  {review.user?.avatar || review.user_avatar ? (
                    <img
                      src={review.user?.avatar || review.user_avatar}
                      alt={review.user?.name || review.user_name}
                      className="h-12 w-12 rounded-full object-cover"
                    />
                  ) : (
                    <div className="h-12 w-12 rounded-full bg-blue-600 flex items-center justify-center text-white font-semibold">
                      {(review.user?.name || review.user_name || 'U')[0].toUpperCase()}
                    </div>
                  )}
                </div>

                <div className="flex-1">
                  {/* Header */}
                  <div className="mb-2">
                    <div className="flex items-center gap-2 mb-1">
                      <span className="font-semibold text-gray-900">
                        {review.user?.name || review.user_name}
                      </span>
                      {review.verified_purchase && (
                        <span className="text-xs bg-green-100 text-green-800 px-2 py-1 rounded">
                          Verified Purchase
                        </span>
                      )}
                    </div>
                    {renderStars(review.rating, 'sm')}
                    <p className="text-sm text-gray-500 mt-1">
                      {new Date(review.created_at).toLocaleDateString('en-US', {
                        year: 'numeric',
                        month: 'long',
                        day: 'numeric',
                      })}
                    </p>
                  </div>

                  {/* Review Content */}
                  {review.title && (
                    <h4 className="font-semibold text-gray-900 mb-2">{review.title}</h4>
                  )}
                  <p className="text-gray-700 whitespace-pre-wrap">
                    {review.comment || review.review}
                  </p>

                  {/* Review Images */}
                  {review.images && review.images.length > 0 && (
                    <div className="flex gap-2 mt-3">
                      {review.images.map((image, idx) => (
                        <img
                          key={idx}
                          src={image}
                          alt={`Review image ${idx + 1}`}
                          className="h-20 w-20 object-cover rounded-lg cursor-pointer hover:opacity-75"
                        />
                      ))}
                    </div>
                  )}

                  {/* Helpful Buttons */}
                  <div className="flex items-center gap-4 mt-4">
                    <span className="text-sm text-gray-600">Was this helpful?</span>
                    <button
                      onClick={() => handleMarkHelpful(review.id, true)}
                      className="flex items-center gap-1 text-sm text-gray-600 hover:text-blue-600 transition-colors"
                    >
                      <HandThumbUpIcon className="h-4 w-4" />
                      <span>Yes ({review.helpful_count})</span>
                    </button>
                    <button
                      onClick={() => handleMarkHelpful(review.id, false)}
                      className="flex items-center gap-1 text-sm text-gray-600 hover:text-red-600 transition-colors"
                    >
                      <HandThumbDownIcon className="h-4 w-4" />
                      <span>No ({review.not_helpful_count})</span>
                    </button>
                  </div>
                </div>
              </div>
            </div>
          ))}
        </div>
      )}

      {/* Pagination */}
      {totalPages > 1 && (
        <div className="mt-8 flex justify-center gap-2">
          <button
            onClick={() => setCurrentPage(Math.max(1, currentPage - 1))}
            disabled={currentPage === 1}
            className="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            Previous
          </button>
          <span className="px-4 py-2 text-gray-700">
            Page {currentPage} of {totalPages}
          </span>
          <button
            onClick={() => setCurrentPage(Math.min(totalPages, currentPage + 1))}
            disabled={currentPage === totalPages}
            className="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            Next
          </button>
        </div>
      )}
    </div>
  );
}
