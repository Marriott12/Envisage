import React, { useEffect, useState } from 'react';
import { paymentApi } from '@/lib/api';
import { useAuth } from '@/hooks/useAuth';

export default function PaymentsPage() {
  const { user, isAuthenticated } = useAuth();
  const [payments, setPayments] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    if (!isAuthenticated) return;
    setLoading(true);
    paymentApi.getMyPayments()
      .then((res) => {
        setPayments(res.data || []);
        setError(null);
      })
      .catch((err) => {
        setError(err.message || 'Failed to load payments');
      })
      .finally(() => setLoading(false));
  }, [isAuthenticated]);

  if (!isAuthenticated) return <div>Please log in to view your payments.</div>;
  if (loading) return <div>Loading payments...</div>;
  if (error) return <div className="text-red-500">{error}</div>;

  return (
    <div className="max-w-3xl mx-auto py-8">
      <h1 className="text-2xl font-bold mb-4">My Payments</h1>
      {payments.length === 0 ? (
        <div>No payments found.</div>
      ) : (
        <table className="w-full border">
          <thead>
            <tr>
              <th>ID</th>
              <th>Order</th>
              <th>Amount</th>
              <th>Status</th>
              <th>Method</th>
              <th>Date</th>
            </tr>
          </thead>
          <tbody>
            {payments.map((p) => (
              <tr key={p.id}>
                <td>{p.id}</td>
                <td>{p.order_id}</td>
                <td>{p.amount}</td>
                <td>{p.status}</td>
                <td>{p.method}</td>
                <td>{p.paid_at || p.created_at}</td>
              </tr>
            ))}
          </tbody>
        </table>
      )}
    </div>
  );
}
