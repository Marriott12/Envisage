import React, { useState } from 'react';
import { submitReview } from './api';

export default function ReviewsPage() {
  const [review, setReview] = useState('');
  const [rating, setRating] = useState(5);
  const [success, setSuccess] = useState(false);
  const [error, setError] = useState('');

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError('');
    setSuccess(false);
    try {
      // For demo, use productId 1
      await submitReview(1, rating, review);
      setSuccess(true);
      setReview('');
    } catch {
      setError('Failed to submit review');
    }
  };

  return (
    <section className="py-8 max-w-2xl mx-auto">
      <h2 className="text-2xl font-bold mb-4">Leave a Review</h2>
      <form className="space-y-4" onSubmit={handleSubmit}>
        <textarea value={review} onChange={e => setReview(e.target.value)} placeholder="Your review" className="w-full border rounded px-4 py-2" />
        <select value={rating} onChange={e => setRating(Number(e.target.value))} className="w-full border rounded px-4 py-2">
          {[1,2,3,4,5].map(r => <option key={r} value={r}>{r} Star{r > 1 ? 's' : ''}</option>)}
        </select>
        <button className="bg-blue-600 text-white px-6 py-2 rounded shadow hover:bg-blue-700">Submit Review</button>
        {success && <div className="text-green-600 mt-2">Review submitted!</div>}
        {error && <div className="text-red-500 mt-2">{error}</div>}
      </form>
    </section>
  );
}
