import React, { useEffect, useState } from 'react';
import { marketplaceApi } from '@/lib/api';
import { useAuth } from '@/hooks/useAuth';

export default function BlogPage() {
  const { isAuthenticated } = useAuth();
  const [posts, setPosts] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    setLoading(true);
    fetch('/api/blog-posts')
      .then((res) => res.json())
      .then((data) => {
        setPosts(data || []);
        setError(null);
      })
      .catch((err) => {
        setError(err.message || 'Failed to load blog posts');
      })
      .finally(() => setLoading(false));
  }, []);

  if (loading) return <div>Loading blog posts...</div>;
  if (error) return <div className="text-red-500">{error}</div>;

  return (
    <div className="max-w-3xl mx-auto py-8">
      <h1 className="text-2xl font-bold mb-4">Blog</h1>
      {posts.length === 0 ? (
        <div>No blog posts found.</div>
      ) : (
        <ul>
          {posts.map((post) => (
            <li key={post.id} className="mb-4">
              <h2 className="text-lg font-semibold">{post.title}</h2>
              <p className="text-gray-600">{post.content?.slice(0, 120)}...</p>
              <div className="text-xs text-gray-400">By {post.author_id} on {post.published_at || post.created_at}</div>
            </li>
          ))}
        </ul>
      )}
    </div>
  );
}
