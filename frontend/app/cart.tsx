import React, { useEffect, useState } from 'react';
import { getCart, getUserId } from './api';

export default function CartPage() {
  const [cart, setCart] = useState<any>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    getCart(getUserId()).then(data => {
      setCart(data);
      setLoading(false);
    });
  }, []);

  if (loading) return <div>Loading cart...</div>;
  if (!cart) return <div>No cart found.</div>;

  return (
    <section className="py-8 max-w-3xl mx-auto">
      <h2 className="text-2xl font-bold mb-4">Your Cart</h2>
      <ul>
        {cart.items && cart.items.length > 0 ? cart.items.map((item: any) => (
          <li key={item.id} className="border-b py-2 flex justify-between items-center">
            <span>{item.product?.name || 'Product'}</span>
            <span>Qty: {item.quantity}</span>
            <span>${item.product?.price || 0}</span>
            {/* TODO: Add remove/update buttons */}
          </li>
        )) : <li>Your cart is empty.</li>}
      </ul>
      <div className="mt-6 text-right">
        <button className="bg-blue-600 text-white px-6 py-2 rounded shadow hover:bg-blue-700">Checkout</button>
      </div>
    </section>
  );
}
