import api from '@/lib/api';

export interface ReviewFormData {
  rating: number;
  title: string;
  comment: string;
  images?: File[];
}

export const reviewService = {
  // Get reviews for a product
  getReviews: async (productId: string, params?: any) => {
    const { data } = await api.get(`/products/${productId}/reviews`, { params });
    return data;
  },

  // Get review statistics
  getStatistics: async (productId: string) => {
    const { data } = await api.get(`/products/${productId}/reviews/statistics`);
    return data;
  },

  // Submit a review
  submitReview: async (productId: string, reviewData: ReviewFormData) => {
    const formData = new FormData();
    formData.append('rating', reviewData.rating.toString());
    formData.append('title', reviewData.title);
    formData.append('comment', reviewData.comment);

    if (reviewData.images) {
      reviewData.images.forEach((image, index) => {
        formData.append(`images[${index}]`, image);
      });
    }

    const { data } = await api.post(`/products/${productId}/reviews`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });
    return data;
  },

  // Update a review
  updateReview: async (reviewId: string, reviewData: Partial<ReviewFormData>) => {
    const { data } = await api.put(`/reviews/${reviewId}`, reviewData);
    return data;
  },

  // Delete a review
  deleteReview: async (reviewId: string) => {
    const { data } = await api.delete(`/reviews/${reviewId}`);
    return data;
  },

  // Mark review as helpful
  markHelpful: async (reviewId: string, isHelpful: boolean) => {
    const { data } = await api.post(`/reviews/${reviewId}/helpful`, { is_helpful: isHelpful });
    return data;
  },

  // Get user's reviews
  getMyReviews: async (params?: any) => {
    const { data } = await api.get('/reviews/my-reviews', { params });
    return data;
  },
};

export default reviewService;
