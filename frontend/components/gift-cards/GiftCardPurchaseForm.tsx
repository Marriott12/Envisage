import React, { useState } from 'react';
import { Gift, Mail, MessageSquare, Loader2 } from 'lucide-react';
import api from '@/lib/api';

interface GiftCardPurchaseFormProps {
  templateId: number;
  templateName: string;
  onSuccess?: () => void;
}

export default function GiftCardPurchaseForm({ 
  templateId,
  templateName,
  onSuccess 
}: GiftCardPurchaseFormProps) {
  const [amount, setAmount] = useState('50');
  const [recipientEmail, setRecipientEmail] = useState('');
  const [recipientName, setRecipientName] = useState('');
  const [message, setMessage] = useState('');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');

  const predefinedAmounts = [25, 50, 100, 250, 500];

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError('');
    setLoading(true);

    try {
      await api.post('/gift-cards/purchase', {
        template_id: templateId,
        amount: parseFloat(amount),
        recipient_email: recipientEmail,
        recipient_name: recipientName,
        message,
      });

      if (onSuccess) onSuccess();
    } catch (err: any) {
      setError(err.response?.data?.message || 'Failed to purchase gift card');
    } finally {
      setLoading(false);
    }
  };

  return (
    <form onSubmit={handleSubmit} className="bg-white rounded-lg shadow-lg p-6 max-w-2xl mx-auto">
      <div className="flex items-center gap-3 mb-6">
        <Gift className="w-8 h-8 text-primary-600" />
        <div>
          <h2 className="text-2xl font-bold">Purchase Gift Card</h2>
          <p className="text-gray-600">{templateName}</p>
        </div>
      </div>

      {error && (
        <div className="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-4">
          {error}
        </div>
      )}

      {/* Amount Selection */}
      <div className="mb-6">
        <label className="block text-sm font-semibold mb-2">Select Amount</label>
        <div className="grid grid-cols-5 gap-2 mb-3">
          {predefinedAmounts.map((amt) => (
            <button
              key={amt}
              type="button"
              onClick={() => setAmount(amt.toString())}
              className={`py-2 rounded-lg font-semibold transition-colors ${
                amount === amt.toString()
                  ? 'bg-primary-600 text-white'
                  : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
              }`}
            >
              ${amt}
            </button>
          ))}
        </div>
        <input
          type="number"
          value={amount}
          onChange={(e) => setAmount(e.target.value)}
          min="10"
          max="1000"
          step="0.01"
          className="w-full px-4 py-2 border rounded-lg"
          placeholder="Or enter custom amount"
          required
        />
      </div>

      {/* Recipient Info */}
      <div className="mb-6 space-y-4">
        <div>
          <label className="block text-sm font-semibold mb-2">
            <Mail className="w-4 h-4 inline mr-1" />
            Recipient Email
          </label>
          <input
            type="email"
            value={recipientEmail}
            onChange={(e) => setRecipientEmail(e.target.value)}
            className="w-full px-4 py-2 border rounded-lg"
            placeholder="recipient@example.com"
            required
          />
        </div>

        <div>
          <label className="block text-sm font-semibold mb-2">
            Recipient Name
          </label>
          <input
            type="text"
            value={recipientName}
            onChange={(e) => setRecipientName(e.target.value)}
            className="w-full px-4 py-2 border rounded-lg"
            placeholder="John Doe"
            required
          />
        </div>

        <div>
          <label className="block text-sm font-semibold mb-2">
            <MessageSquare className="w-4 h-4 inline mr-1" />
            Personal Message (Optional)
          </label>
          <textarea
            value={message}
            onChange={(e) => setMessage(e.target.value)}
            className="w-full px-4 py-2 border rounded-lg"
            rows={3}
            maxLength={500}
            placeholder="Happy Birthday! Hope you enjoy shopping..."
          />
          <p className="text-xs text-gray-500 mt-1">
            {message.length}/500 characters
          </p>
        </div>
      </div>

      {/* Total */}
      <div className="bg-gray-50 rounded-lg p-4 mb-6">
        <div className="flex justify-between items-center">
          <span className="text-lg font-semibold">Total:</span>
          <span className="text-3xl font-bold text-primary-600">
            ${parseFloat(amount || '0').toFixed(2)}
          </span>
        </div>
      </div>

      {/* Submit Button */}
      <button
        type="submit"
        disabled={loading}
        className="w-full bg-primary-600 text-white py-3 rounded-lg font-bold hover:bg-primary-700 transition-colors disabled:opacity-50 flex items-center justify-center gap-2"
      >
        {loading ? (
          <>
            <Loader2 className="w-5 h-5 animate-spin" />
            Processing...
          </>
        ) : (
          <>
            <Gift className="w-5 h-5" />
            Purchase Gift Card
          </>
        )}
      </button>

      <p className="text-xs text-gray-500 text-center mt-4">
        Gift card will be sent immediately via email to the recipient
      </p>
    </form>
  );
}
