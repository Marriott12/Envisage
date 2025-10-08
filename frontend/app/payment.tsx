import React, { useState } from 'react';
import { makePayment } from './api';

export default function PaymentPage() {
  const [amount, setAmount] = useState('');
  const [success, setSuccess] = useState(false);
  const [error, setError] = useState('');

  const handlePay = async (e: React.FormEvent) => {
    e.preventDefault();
    setError('');
    setSuccess(false);
    try {
      // For demo, use cartId 1
      await makePayment(1, { amount });
      setSuccess(true);
    } catch {
      setError('Payment failed');
    }
  };

  return (
    <section className="py-8 max-w-2xl mx-auto">
      <h2 className="text-2xl font-bold mb-4">Payment</h2>
      <form className="space-y-4" onSubmit={handlePay}>
        <input type="number" placeholder="Amount" value={amount} onChange={e => setAmount(e.target.value)} className="w-full border rounded px-4 py-2" />
        <button className="bg-green-600 text-white px-6 py-2 rounded shadow hover:bg-green-700">Pay</button>
        {success && <div className="text-green-600 mt-2">Payment successful!</div>}
        {error && <div className="text-red-500 mt-2">{error}</div>}
      </form>
    </section>
  );
}
