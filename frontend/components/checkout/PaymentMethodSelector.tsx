'use client';

import { useState } from 'react';
import { useCheckoutStore } from '@/lib/stores/enhanced-stores';
import { 
  CreditCard, 
  Smartphone,
  Wallet,
  Check,
  Lock,
  Plus,
  Trash2
} from 'lucide-react';

interface PaymentMethod {
  id: string;
  type: 'credit_card' | 'paypal' | 'apple_pay' | 'google_pay';
  last4?: string;
  brand?: string;
  expiryMonth?: string;
  expiryYear?: string;
  isDefault?: boolean;
  email?: string;
}

interface PaymentMethodSelectorProps {
  onSelect?: (method: PaymentMethod) => void;
  showSavedMethods?: boolean;
  className?: string;
}

export function PaymentMethodSelector({
  onSelect,
  showSavedMethods = true,
  className = '',
}: PaymentMethodSelectorProps) {
  const checkout = useCheckoutStore();
  
  const [selectedMethod, setSelectedMethod] = useState<string | null>(null);
  const [savedMethods, setSavedMethods] = useState<PaymentMethod[]>([
    {
      id: '1',
      type: 'credit_card',
      brand: 'Visa',
      last4: '4242',
      expiryMonth: '12',
      expiryYear: '2025',
      isDefault: true,
    },
    {
      id: '2',
      type: 'paypal',
      email: 'user@example.com',
    },
  ]);

  const [showNewCardForm, setShowNewCardForm] = useState(false);
  const [newCardData, setNewCardData] = useState({
    cardNumber: '',
    cardName: '',
    expiryDate: '',
    cvv: '',
    saveCard: false,
  });

  const paymentOptions = [
    {
      id: 'credit_card',
      name: 'Credit Card',
      icon: <CreditCard className="w-6 h-6" />,
      description: 'Visa, Mastercard, Amex',
    },
    {
      id: 'paypal',
      name: 'PayPal',
      icon: <Wallet className="w-6 h-6" />,
      description: 'Pay with your PayPal account',
    },
    {
      id: 'apple_pay',
      name: 'Apple Pay',
      icon: <Smartphone className="w-6 h-6" />,
      description: 'Fast and secure payment',
    },
    {
      id: 'google_pay',
      name: 'Google Pay',
      icon: <Smartphone className="w-6 h-6" />,
      description: 'Quick checkout with Google',
    },
  ];

  const handleSelectMethod = (methodId: string) => {
    setSelectedMethod(methodId);
    checkout.setPaymentMethod(methodId);
    
    const method = savedMethods.find((m) => m.id === methodId);
    if (method && onSelect) {
      onSelect(method);
    }
  };

  const handleSelectPaymentOption = (optionId: string) => {
    if (optionId === 'credit_card') {
      setShowNewCardForm(true);
    } else {
      setSelectedMethod(optionId);
      checkout.setPaymentMethod(optionId);
    }
  };

  const handleDeleteMethod = async (methodId: string) => {
    try {
      const response = await fetch(`/api/payment-methods/${methodId}`, {
        method: 'DELETE',
      });

      if (response.ok) {
        setSavedMethods(savedMethods.filter((m) => m.id !== methodId));
      }
    } catch (error) {
      console.error('Failed to delete payment method:', error);
    }
  };

  const handleSubmitNewCard = async (e: React.FormEvent) => {
    e.preventDefault();

    // In production, tokenize the card with Stripe/Braintree
    const newMethod: PaymentMethod = {
      id: Date.now().toString(),
      type: 'credit_card',
      brand: detectCardBrand(newCardData.cardNumber),
      last4: newCardData.cardNumber.slice(-4),
      expiryMonth: newCardData.expiryDate.split('/')[0],
      expiryYear: newCardData.expiryDate.split('/')[1],
    };

    if (newCardData.saveCard) {
      setSavedMethods([...savedMethods, newMethod]);
    }

    handleSelectMethod(newMethod.id);
    setShowNewCardForm(false);
    
    // Reset form
    setNewCardData({
      cardNumber: '',
      cardName: '',
      expiryDate: '',
      cvv: '',
      saveCard: false,
    });
  };

  const detectCardBrand = (cardNumber: string): string => {
    const cleaned = cardNumber.replace(/\s/g, '');
    if (cleaned.startsWith('4')) return 'Visa';
    if (cleaned.startsWith('5')) return 'Mastercard';
    if (cleaned.startsWith('3')) return 'Amex';
    return 'Unknown';
  };

  const formatCardNumber = (value: string): string => {
    const cleaned = value.replace(/\s/g, '');
    const chunks = cleaned.match(/.{1,4}/g) || [];
    return chunks.join(' ').substr(0, 19);
  };

  const formatExpiryDate = (value: string): string => {
    const cleaned = value.replace(/\D/g, '');
    if (cleaned.length >= 2) {
      return `${cleaned.slice(0, 2)}/${cleaned.slice(2, 4)}`;
    }
    return cleaned;
  };

  return (
    <div className={className}>
      {/* Saved Payment Methods */}
      {showSavedMethods && savedMethods.length > 0 && (
        <div className="mb-6">
          <h3 className="text-lg font-semibold mb-3">Saved Payment Methods</h3>
          <div className="space-y-3">
            {savedMethods.map((method) => (
              <div
                key={method.id}
                className={`
                  relative p-4 rounded-lg border-2 cursor-pointer transition-all
                  ${
                    selectedMethod === method.id
                      ? 'border-blue-500 bg-blue-50'
                      : 'border-gray-200 hover:border-gray-300'
                  }
                `}
                onClick={() => handleSelectMethod(method.id)}
              >
                <div className="flex items-center justify-between">
                  <div className="flex items-center gap-3">
                    {method.type === 'credit_card' ? (
                      <CreditCard className="w-6 h-6 text-gray-600" />
                    ) : method.type === 'paypal' ? (
                      <Wallet className="w-6 h-6 text-gray-600" />
                    ) : (
                      <Smartphone className="w-6 h-6 text-gray-600" />
                    )}
                    
                    <div>
                      {method.type === 'credit_card' ? (
                        <>
                          <p className="font-semibold">
                            {method.brand} ending in {method.last4}
                          </p>
                          <p className="text-sm text-gray-500">
                            Expires {method.expiryMonth}/{method.expiryYear}
                          </p>
                        </>
                      ) : (
                        <>
                          <p className="font-semibold capitalize">
                            {method.type.replace('_', ' ')}
                          </p>
                          {method.email && (
                            <p className="text-sm text-gray-500">{method.email}</p>
                          )}
                        </>
                      )}
                    </div>
                  </div>

                  <div className="flex items-center gap-2">
                    {selectedMethod === method.id && (
                      <Check className="w-5 h-5 text-blue-500" />
                    )}
                    <button
                      onClick={(e) => {
                        e.stopPropagation();
                        handleDeleteMethod(method.id);
                      }}
                      className="p-1 hover:bg-red-50 rounded transition-colors"
                    >
                      <Trash2 className="w-4 h-4 text-red-500" />
                    </button>
                  </div>
                </div>

                {method.isDefault && (
                  <span className="inline-block mt-2 px-2 py-1 text-xs bg-gray-100 rounded">
                    Default
                  </span>
                )}
              </div>
            ))}
          </div>
        </div>
      )}

      {/* Payment Options */}
      {!showNewCardForm && (
        <div className="mb-6">
          <h3 className="text-lg font-semibold mb-3">
            {savedMethods.length > 0 ? 'Or Choose a Payment Method' : 'Choose Payment Method'}
          </h3>
          <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
            {paymentOptions.map((option) => (
              <button
                key={option.id}
                onClick={() => handleSelectPaymentOption(option.id)}
                className={`
                  p-4 rounded-lg border-2 text-left transition-all
                  ${
                    selectedMethod === option.id
                      ? 'border-blue-500 bg-blue-50'
                      : 'border-gray-200 hover:border-gray-300'
                  }
                `}
              >
                <div className="flex items-center gap-3 mb-2">
                  {option.icon}
                  <span className="font-semibold">{option.name}</span>
                  {selectedMethod === option.id && (
                    <Check className="w-5 h-5 text-blue-500 ml-auto" />
                  )}
                </div>
                <p className="text-sm text-gray-500">{option.description}</p>
              </button>
            ))}
          </div>
        </div>
      )}

      {/* New Card Form */}
      {showNewCardForm && (
        <div className="bg-white rounded-lg border-2 border-blue-500 p-6">
          <div className="flex items-center justify-between mb-4">
            <h3 className="text-lg font-semibold">Add New Card</h3>
            <button
              onClick={() => setShowNewCardForm(false)}
              className="text-sm text-gray-500 hover:text-gray-700"
            >
              Cancel
            </button>
          </div>

          <form onSubmit={handleSubmitNewCard} className="space-y-4">
            {/* Card Number */}
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">
                Card Number *
              </label>
              <div className="relative">
                <input
                  type="text"
                  required
                  value={newCardData.cardNumber}
                  onChange={(e) =>
                    setNewCardData({
                      ...newCardData,
                      cardNumber: formatCardNumber(e.target.value),
                    })
                  }
                  placeholder="1234 5678 9012 3456"
                  maxLength={19}
                  className="
                    w-full px-4 py-2 pl-10 border border-gray-300 rounded-lg
                    focus:ring-2 focus:ring-blue-500 focus:border-transparent
                  "
                />
                <CreditCard className="absolute left-3 top-3 w-5 h-5 text-gray-400" />
              </div>
            </div>

            {/* Cardholder Name */}
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">
                Cardholder Name *
              </label>
              <input
                type="text"
                required
                value={newCardData.cardName}
                onChange={(e) =>
                  setNewCardData({ ...newCardData, cardName: e.target.value })
                }
                placeholder="John Doe"
                className="
                  w-full px-4 py-2 border border-gray-300 rounded-lg
                  focus:ring-2 focus:ring-blue-500 focus:border-transparent
                "
              />
            </div>

            {/* Expiry Date and CVV */}
            <div className="grid grid-cols-2 gap-4">
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">
                  Expiry Date *
                </label>
                <input
                  type="text"
                  required
                  value={newCardData.expiryDate}
                  onChange={(e) =>
                    setNewCardData({
                      ...newCardData,
                      expiryDate: formatExpiryDate(e.target.value),
                    })
                  }
                  placeholder="MM/YY"
                  maxLength={5}
                  className="
                    w-full px-4 py-2 border border-gray-300 rounded-lg
                    focus:ring-2 focus:ring-blue-500 focus:border-transparent
                  "
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">
                  CVV *
                </label>
                <div className="relative">
                  <input
                    type="text"
                    required
                    value={newCardData.cvv}
                    onChange={(e) =>
                      setNewCardData({
                        ...newCardData,
                        cvv: e.target.value.replace(/\D/g, '').substr(0, 4),
                      })
                    }
                    placeholder="123"
                    maxLength={4}
                    className="
                      w-full px-4 py-2 pl-10 border border-gray-300 rounded-lg
                      focus:ring-2 focus:ring-blue-500 focus:border-transparent
                    "
                  />
                  <Lock className="absolute left-3 top-3 w-5 h-5 text-gray-400" />
                </div>
              </div>
            </div>

            {/* Save Card Checkbox */}
            <div className="flex items-center gap-2">
              <input
                type="checkbox"
                id="saveCard"
                checked={newCardData.saveCard}
                onChange={(e) =>
                  setNewCardData({ ...newCardData, saveCard: e.target.checked })
                }
                className="w-4 h-4 text-blue-600 rounded focus:ring-2 focus:ring-blue-500"
              />
              <label htmlFor="saveCard" className="text-sm text-gray-700">
                Save this card for future purchases
              </label>
            </div>

            {/* Security Notice */}
            <div className="flex items-start gap-2 p-3 bg-gray-50 rounded-lg">
              <Lock className="w-5 h-5 text-gray-500 flex-shrink-0 mt-0.5" />
              <p className="text-xs text-gray-600">
                Your payment information is encrypted and secure. We never store your CVV.
              </p>
            </div>

            {/* Submit Button */}
            <button
              type="submit"
              className="
                w-full py-3 bg-blue-600 text-white rounded-lg font-medium
                hover:bg-blue-700 transition-colors flex items-center justify-center gap-2
              "
            >
              <Plus className="w-5 h-5" />
              Add Payment Method
            </button>
          </form>
        </div>
      )}

      {/* Add New Card Button */}
      {!showNewCardForm && (
        <button
          onClick={() => setShowNewCardForm(true)}
          className="
            w-full py-3 border-2 border-dashed border-gray-300 rounded-lg
            text-gray-600 hover:border-gray-400 hover:text-gray-700
            transition-colors flex items-center justify-center gap-2
          "
        >
          <Plus className="w-5 h-5" />
          Add New Payment Method
        </button>
      )}
    </div>
  );
}

// Quick payment selector (compact variant)
export function QuickPaymentSelector({ onSelect, className = '' }: Omit<PaymentMethodSelectorProps, 'showSavedMethods'>) {
  return (
    <PaymentMethodSelector
      onSelect={onSelect}
      showSavedMethods={false}
      className={className}
    />
  );
}
