import React, { useEffect, useState } from 'react';
import { useAuth } from '@/hooks/useAuth';
import { toast } from 'react-hot-toast';
import { marketplaceApi } from '@/lib/api';

export default function CartPage() {
  const { user, isAuthenticated } = useAuth();
  const [cart, setCart] = useState<any>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    if (!isAuthenticated || !user) return;
    setLoading(true);
    fetch(`/api/cart/${user.id}`)
      .then((res) => res.json())
      .then((data) => {
        setCart(data);
        setError(null);
      })
      .catch((err) => {
        setError(err.message || 'Failed to load cart');
      })
      .finally(() => setLoading(false));
  }, [isAuthenticated, user]);

  if (!isAuthenticated) return <div>Please log in to view your cart.</div>;
  if (loading) return <div>Loading cart...</div>;
  if (error) return <div className="text-red-500">{error}</div>;

  return (
    <div className="max-w-3xl mx-auto py-8">
      <h1 className="text-2xl font-bold mb-4">My Cart</h1>
      {!cart || !cart.items || cart.items.length === 0 ? (
        <div>Your cart is empty.</div>
      ) : (
        <ul>
          {cart.items.map((item: any) => (
            <li key={item.id} className="mb-4">
              <div className="font-semibold">{item.product?.name}</div>
              <div>Qty: {item.quantity}</div>
              <div>Price: {item.product?.price}</div>
            </li>
          ))}
        </ul>
      )}
    </div>
  );
}
