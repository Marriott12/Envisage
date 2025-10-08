import React, { useEffect, useState } from 'react';
import { getOrders } from './api';

export default function OrdersPage() {
  const [orders, setOrders] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    getOrders().then(data => {
      setOrders(data);
      setLoading(false);
    });
  }, []);

  if (loading) return <div>Loading orders...</div>;
  if (!orders.length) return <div>No orders found.</div>;

  return (
    <section className="py-8 max-w-4xl mx-auto">
      <h2 className="text-2xl font-bold mb-4">Order History</h2>
      <ul>
        {orders.map(order => (
          <li key={order.id} className="border-b py-2">
            <div>Order #{order.id}</div>
            <div>Status: {order.status || 'N/A'}</div>
            <div>Total: ${order.total || 0}</div>
            {/* TODO: Add tracking, details */}
          </li>
        ))}
      </ul>
    </section>
  );
}
