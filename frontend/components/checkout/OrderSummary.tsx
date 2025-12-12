'use client';

import { useState, useEffect } from 'react';
import { useCartStore } from '@/lib/store';
import { useCheckoutStore } from '@/lib/stores/enhanced-stores';
import { Tag, Gift, Truck, Package, Percent, Loader2, X } from 'lucide-react';

interface OrderSummaryProps {
  showCouponInput?: boolean;
  showGiftWrap?: boolean;
  showShippingOptions?: boolean;
  className?: string;
}

interface ShippingOption {
  id: string;
  name: string;
  price: number;
  estimatedDays: string;
  description: string;
}

export function OrderSummary({
  showCouponInput = true,
  showGiftWrap = true,
  showShippingOptions = true,
  className = '',
}: OrderSummaryProps) {
  const cart = useCartStore();
  const checkout = useCheckoutStore();

  const [couponCode, setCouponCode] = useState('');
  const [isApplyingCoupon, setIsApplyingCoupon] = useState(false);
  const [couponError, setCouponError] = useState('');
  
  const [shippingOptions, setShippingOptions] = useState<ShippingOption[]>([
    {
      id: 'standard',
      name: 'Standard Shipping',
      price: 5.99,
      estimatedDays: '5-7 business days',
      description: 'Standard ground shipping',
    },
    {
      id: 'express',
      name: 'Express Shipping',
      price: 12.99,
      estimatedDays: '2-3 business days',
      description: 'Faster delivery',
    },
    {
      id: 'overnight',
      name: 'Overnight',
      price: 24.99,
      estimatedDays: '1 business day',
      description: 'Next day delivery',
    },
  ]);

  const [selectedShipping, setSelectedShipping] = useState<string>('standard');
  const [isCalculatingShipping, setIsCalculatingShipping] = useState(false);

  // Calculate tax (example: 8.5%)
  const [shippingCost, setShippingCostLocal] = useState(0);
  const [taxAmount, setTaxAmount] = useState(0);
  const [appliedCoupon, setAppliedCoupon] = useState<{code: string; discount: number; type: string} | null>(null);

  const taxRate = 0.085;
  const subtotal = cart.getTotalPrice();
  const shippingCostValue = shippingOptions.find((s) => s.id === selectedShipping)?.price || 0;
  const discount = appliedCoupon?.discount || 0;
  const giftWrapCost = checkout.giftWrap ? 4.99 : 0;
  const tax = (subtotal + shippingCostValue + giftWrapCost - discount) * taxRate;
  const total = subtotal + shippingCostValue + giftWrapCost + tax - discount;

  // Update local state with calculated values
  useEffect(() => {
    setShippingCostLocal(shippingCostValue);
    setTaxAmount(tax);
  }, [shippingCostValue, tax]);

  // Calculate shipping options based on address
  useEffect(() => {
    if (checkout.shippingAddress && showShippingOptions) {
      calculateShipping();
    }
  }, [checkout.shippingAddress]);

  const calculateShipping = async () => {
    setIsCalculatingShipping(true);
    
    try {
      const response = await fetch('/api/shipping/calculate', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          address: checkout.shippingAddress,
          items: cart.items,
        }),
      });

      if (response.ok) {
        const data = await response.json();
        if (data.methods) {
          setShippingOptions(data.methods);
        }
      }
    } catch (error) {
      console.error('Failed to calculate shipping:', error);
    } finally {
      setIsCalculatingShipping(false);
    }
  };

  const handleApplyCoupon = async () => {
    if (!couponCode.trim()) return;

    setIsApplyingCoupon(true);
    setCouponError('');

    try {
      const response = await fetch('/api/coupons/validate', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          code: couponCode.toUpperCase(),
          cartTotal: subtotal,
        }),
      });

      const data = await response.json();

      if (response.ok && data.valid) {
        setAppliedCoupon({
          code: couponCode.toUpperCase(),
          discount: data.discount,
          type: data.type,
        });
        setCouponCode('');
      } else {
        setCouponError(data.message || 'Invalid coupon code');
      }
    } catch (error) {
      setCouponError('Failed to apply coupon. Please try again.');
    } finally {
      setIsApplyingCoupon(false);
    }
  };

  const handleRemoveCoupon = () => {
    setAppliedCoupon(null);
    setCouponError('');
  };

  const handleToggleGiftWrap = () => {
    checkout.setGiftOptions(!checkout.giftWrap, checkout.giftMessage);
  };

  const handleGiftMessageChange = (message: string) => {
    checkout.setGiftOptions(checkout.giftWrap, message);
  };

  return (
    <div className={`bg-white rounded-lg shadow-lg p-6 ${className}`}>
      <h2 className="text-2xl font-bold mb-6">Order Summary</h2>

      {/* Cart Items Summary */}
      <div className="space-y-3 mb-6 pb-6 border-b">
        {cart.items.map((item) => (
          <div key={item.id} className="flex justify-between text-sm">
            <div className="flex-1">
              <p className="font-medium">{item.title}</p>
              <p className="text-gray-500">Qty: {item.quantity}</p>
            </div>
            <p className="font-semibold">${(item.price * item.quantity).toFixed(2)}</p>
          </div>
        ))}
      </div>

      {/* Coupon Input */}
      {showCouponInput && (
        <div className="mb-6 pb-6 border-b">
          <div className="flex items-center gap-2 mb-2">
            <Tag className="w-5 h-5 text-gray-500" />
            <h3 className="font-semibold">Promo Code</h3>
          </div>

          {appliedCoupon ? (
            <div className="flex items-center justify-between p-3 bg-green-50 border border-green-200 rounded-lg">
              <div className="flex items-center gap-2">
                <Percent className="w-5 h-5 text-green-600" />
                <div>
                  <p className="font-medium text-green-700">{appliedCoupon.code}</p>
                  <p className="text-sm text-green-600">
                    -${appliedCoupon.discount.toFixed(2)} discount applied
                  </p>
                </div>
              </div>
              <button
                onClick={handleRemoveCoupon}
                className="p-1 hover:bg-green-100 rounded transition-colors"
              >
                <X className="w-5 h-5 text-green-600" />
              </button>
            </div>
          ) : (
            <div>
              <div className="flex gap-2">
                <input
                  type="text"
                  value={couponCode}
                  onChange={(e) => setCouponCode(e.target.value.toUpperCase())}
                  onKeyPress={(e) => e.key === 'Enter' && handleApplyCoupon()}
                  placeholder="Enter code"
                  className="
                    flex-1 px-4 py-2 border border-gray-300 rounded-lg
                    focus:ring-2 focus:ring-blue-500 focus:border-transparent
                  "
                />
                <button
                  onClick={handleApplyCoupon}
                  disabled={isApplyingCoupon || !couponCode.trim()}
                  className="
                    px-6 py-2 bg-gray-800 text-white rounded-lg
                    hover:bg-gray-900 disabled:opacity-50 disabled:cursor-not-allowed
                    transition-colors flex items-center gap-2
                  "
                >
                  {isApplyingCoupon ? (
                    <>
                      <Loader2 className="w-4 h-4 animate-spin" />
                      Applying...
                    </>
                  ) : (
                    'Apply'
                  )}
                </button>
              </div>
              {couponError && (
                <p className="mt-2 text-sm text-red-600">{couponError}</p>
              )}
            </div>
          )}
        </div>
      )}

      {/* Shipping Options */}
      {showShippingOptions && (
        <div className="mb-6 pb-6 border-b">
          <div className="flex items-center gap-2 mb-3">
            <Truck className="w-5 h-5 text-gray-500" />
            <h3 className="font-semibold">Shipping Method</h3>
          </div>

          {isCalculatingShipping ? (
            <div className="flex items-center justify-center py-4">
              <Loader2 className="w-6 h-6 animate-spin text-gray-400" />
              <span className="ml-2 text-gray-500">Calculating shipping...</span>
            </div>
          ) : (
            <div className="space-y-2">
              {shippingOptions.map((option) => (
                <label
                  key={option.id}
                  className={`
                    flex items-start gap-3 p-3 rounded-lg border-2 cursor-pointer
                    transition-all
                    ${
                      selectedShipping === option.id
                        ? 'border-blue-500 bg-blue-50'
                        : 'border-gray-200 hover:border-gray-300'
                    }
                  `}
                >
                  <input
                    type="radio"
                    name="shipping"
                    value={option.id}
                    checked={selectedShipping === option.id}
                    onChange={(e) => setSelectedShipping(e.target.value)}
                    className="mt-1 w-4 h-4 text-blue-600 focus:ring-2 focus:ring-blue-500"
                  />
                  <div className="flex-1">
                    <div className="flex justify-between mb-1">
                      <span className="font-medium">{option.name}</span>
                      <span className="font-semibold">${option.price.toFixed(2)}</span>
                    </div>
                    <p className="text-sm text-gray-500">{option.estimatedDays}</p>
                    <p className="text-xs text-gray-400">{option.description}</p>
                  </div>
                </label>
              ))}
            </div>
          )}
        </div>
      )}

      {/* Gift Wrap Option */}
      {showGiftWrap && (
        <div className="mb-6 pb-6 border-b">
          <div className="flex items-center justify-between mb-3">
            <div className="flex items-center gap-2">
              <Gift className="w-5 h-5 text-gray-500" />
              <h3 className="font-semibold">Gift Wrap</h3>
            </div>
            <label className="relative inline-flex items-center cursor-pointer">
              <input
                type="checkbox"
                checked={checkout.giftWrap || false}
                onChange={handleToggleGiftWrap}
                className="sr-only peer"
              />
              <div className="
                w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4
                peer-focus:ring-blue-300 rounded-full peer
                peer-checked:after:translate-x-full peer-checked:after:border-white
                after:content-[''] after:absolute after:top-[2px] after:left-[2px]
                after:bg-white after:border-gray-300 after:border after:rounded-full
                after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600
              "></div>
            </label>
          </div>

          {checkout.giftWrap && (
            <div className="space-y-3">
              <div className="flex justify-between text-sm">
                <span className="text-gray-600">Gift wrap service</span>
                <span className="font-medium">+$4.99</span>
              </div>
              <textarea
                value={checkout.giftMessage || ''}
                onChange={(e) => handleGiftMessageChange(e.target.value)}
                placeholder="Add a gift message (optional)"
                rows={3}
                maxLength={200}
                className="
                  w-full px-3 py-2 border border-gray-300 rounded-lg
                  focus:ring-2 focus:ring-blue-500 focus:border-transparent
                  text-sm
                "
              />
              <p className="text-xs text-gray-500">
                {checkout.giftMessage?.length || 0}/200 characters
              </p>
            </div>
          )}
        </div>
      )}

      {/* Price Breakdown */}
      <div className="space-y-3 mb-6">
        <div className="flex justify-between text-gray-700">
          <span>Subtotal:</span>
          <span>${subtotal.toFixed(2)}</span>
        </div>

        <div className="flex justify-between text-gray-700">
          <span>Shipping:</span>
          <span>${shippingCostValue.toFixed(2)}</span>
        </div>

        {checkout.giftWrap && (
          <div className="flex justify-between text-gray-700">
            <span>Gift Wrap:</span>
            <span>+${giftWrapCost.toFixed(2)}</span>
          </div>
        )}

        {appliedCoupon && appliedCoupon.discount > 0 && (
          <div className="flex justify-between text-green-600 font-medium">
            <span>Discount:</span>
            <span>-${appliedCoupon.discount.toFixed(2)}</span>
          </div>
        )}

        <div className="flex justify-between text-gray-700">
          <span>Tax:</span>
          <span>${tax.toFixed(2)}</span>
        </div>
      </div>

      {/* Total */}
      <div className="pt-6 border-t">
        <div className="flex justify-between items-center mb-4">
          <span className="text-xl font-bold">Total:</span>
          <span className="text-2xl font-bold text-blue-600">
            ${total.toFixed(2)}
          </span>
        </div>

        {/* Savings Badge */}
        {appliedCoupon && appliedCoupon.discount > 0 && (
          <div className="flex items-center justify-center gap-2 p-2 bg-green-50 rounded-lg">
            <Package className="w-5 h-5 text-green-600" />
            <span className="text-sm text-green-700 font-medium">
              You're saving ${appliedCoupon.discount.toFixed(2)}!
            </span>
          </div>
        )}
      </div>
    </div>
  );
}

// Compact summary for mobile
export function CompactOrderSummary({ className = '' }: { className?: string }) {
  const cart = useCartStore();
  const total = cart.getTotalPrice();

  return (
    <div className={`bg-white rounded-lg shadow p-4 ${className}`}>
      <div className="flex items-center justify-between mb-2">
        <span className="font-medium">Order Total</span>
        <span className="text-xl font-bold text-blue-600">${total.toFixed(2)}</span>
      </div>
      <p className="text-sm text-gray-500">{cart.items.length} items</p>
    </div>
  );
}
