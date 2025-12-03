'use client';

import React, { useState, useEffect } from 'react';
import { useRouter } from 'next/navigation';
import { useAuth } from '@/hooks/useAuth';
import { toast } from 'react-hot-toast';
import axios from 'axios';
import ProtectedRoute from '@/components/ProtectedRoute';
import Header from '@/components/Header';
import {
  CreditCardIcon,
  TruckIcon,
  ShieldCheckIcon,
  CheckCircleIcon,
} from '@heroicons/react/24/outline';
import { motion } from 'framer-motion';

const API_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost/Envisage/backend/public/api';

// Token storage utility
class TokenStorage {
  private static readonly TOKEN_KEY = 'envisage_auth_token';

  static getToken(): string | null {
    return localStorage.getItem(this.TOKEN_KEY);
  }
}

interface CartItem {
  id: number;
  product_id: number;
  quantity: number;
  product: {
    id: number;
    title: string;
    price: number;
    images?: string[];
    seller_id: number;
  };
}

interface CartData {
  items: CartItem[];
  total: number;
  item_count: number;
}

function CheckoutContent() {
  const router = useRouter();
  const { user } = useAuth();
  const [cart, setCart] = useState<CartData | null>(null);
  const [loading, setLoading] = useState(true);
  const [submitting, setSubmitting] = useState(false);
  const [step, setStep] = useState(1); // 1: Shipping, 2: Payment, 3: Review

  // Form state
  const [shippingInfo, setShippingInfo] = useState({
    name: user?.name || '',
    phone: '',
    address_line_1: '',
    address_line_2: '',
    city: '',
    state: '',
    postal_code: '',
    country: 'Zambia',
  });

  const [paymentMethod, setPaymentMethod] = useState('stripe');
  const [useShippingForBilling, setUseShippingForBilling] = useState(true);
  const [agreeToTerms, setAgreeToTerms] = useState(false);

  useEffect(() => {
    fetchCart();
  }, []);

  const fetchCart = async () => {
    try {
      const token = TokenStorage.getToken();
      if (!token) {
        router.push('/login?redirect=/checkout');
        return;
      }

      const response = await axios.get(`${API_URL}/cart`, {
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json',
        },
      });

      if (response.data.status === 'success') {
        const cartData = response.data.data;
        setCart(cartData);

        // If cart is empty, redirect to cart page
        if (!cartData.items || cartData.items.length === 0) {
          toast.error('Your cart is empty');
          router.push('/cart');
        }
      }
    } catch (error: any) {
      console.error('Failed to fetch cart:', error);
      toast.error('Failed to load cart');
      router.push('/cart');
    } finally {
      setLoading(false);
    }
  };

  const handleInputChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const { name, value } = e.target;
    setShippingInfo(prev => ({ ...prev, [name]: value }));
  };

  const validateStep = (currentStep: number): boolean => {
    if (currentStep === 1) {
      // Validate shipping information
      const requiredFields = ['name', 'phone', 'address_line_1', 'city', 'state', 'postal_code', 'country'];
      for (const field of requiredFields) {
        if (!shippingInfo[field as keyof typeof shippingInfo]) {
          toast.error(`Please fill in ${field.replace('_', ' ')}`);
          return false;
        }
      }
    }

    if (currentStep === 2) {
      // Validate payment method
      if (!paymentMethod) {
        toast.error('Please select a payment method');
        return false;
      }
    }

    if (currentStep === 3) {
      // Validate terms agreement
      if (!agreeToTerms) {
        toast.error('Please agree to the terms and conditions');
        return false;
      }
    }

    return true;
  };

  const handleNext = () => {
    if (validateStep(step)) {
      setStep(step + 1);
    }
  };

  const handleBack = () => {
    setStep(step - 1);
  };

  const handlePlaceOrder = async () => {
    if (!validateStep(3)) return;

    setSubmitting(true);

    try {
      const token = TokenStorage.getToken();
      if (!token) {
        toast.error('Please login to continue');
        router.push('/login?redirect=/checkout');
        return;
      }

      const orderData = {
        shipping_address: shippingInfo,
        payment_method: paymentMethod,
        billing_same_as_shipping: useShippingForBilling,
      };

      const response = await axios.post(`${API_URL}/checkout`, orderData, {
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json',
        },
      });

      if (response.data.status === 'success') {
        toast.success('Order placed successfully!');
        
        // If there's a payment URL (for Stripe), redirect to it
        if (response.data.data.payment_url) {
          window.location.href = response.data.data.payment_url;
        } else {
          // Otherwise, go to order confirmation
          router.push(`/orders/${response.data.data.order_id}`);
        }
      }
    } catch (error: any) {
      console.error('Checkout failed:', error);
      toast.error(error.response?.data?.message || 'Failed to place order');
    } finally {
      setSubmitting(false);
    }
  };

  if (loading) {
    return (
      <div className="min-h-screen bg-gray-50 flex items-center justify-center">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600"></div>
      </div>
    );
  }

  if (!cart || cart.items.length === 0) {
    return null; // Will redirect
  }

  const shippingCost = 0; // Free shipping for now
  const tax = 0; // Tax calculation can be added later
  const total = cart.total + shippingCost + tax;

  return (
    <>
      <Header />
      <div className="min-h-screen bg-gray-50 py-12">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          {/* Header */}
          <div className="mb-8">
            <h1 className="text-3xl font-bold text-gray-900">Checkout</h1>
            <p className="text-gray-600 mt-2">Complete your purchase securely</p>
          </div>

          {/* Progress Steps */}
          <div className="mb-8">
            <div className="flex items-center justify-between">
              {[1, 2, 3].map((num) => (
                <React.Fragment key={num}>
                  <div className="flex items-center">
                    <div className={`flex items-center justify-center w-10 h-10 rounded-full ${
                      step >= num ? 'bg-primary-600 text-white' : 'bg-gray-300 text-gray-600'
                    }`}>
                      {step > num ? (
                        <CheckCircleIcon className="h-6 w-6" />
                      ) : (
                        num
                      )}
                    </div>
                    <span className={`ml-2 text-sm font-medium ${
                      step >= num ? 'text-primary-600' : 'text-gray-500'
                    }`}>
                      {num === 1 && 'Shipping'}
                      {num === 2 && 'Payment'}
                      {num === 3 && 'Review'}
                    </span>
                  </div>
                  {num < 3 && (
                    <div className={`flex-1 h-1 mx-4 ${
                      step > num ? 'bg-primary-600' : 'bg-gray-300'
                    }`} />
                  )}
                </React.Fragment>
              ))}
            </div>
          </div>

          <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
            {/* Main Content */}
            <div className="lg:col-span-2">
              <div className="bg-white rounded-lg shadow-sm p-6">
                {/* Step 1: Shipping Information */}
                {step === 1 && (
                  <motion.div
                    initial={{ opacity: 0, x: 20 }}
                    animate={{ opacity: 1, x: 0 }}
                    exit={{ opacity: 0, x: -20 }}
                  >
                    <div className="flex items-center gap-2 mb-6">
                      <TruckIcon className="h-6 w-6 text-primary-600" />
                      <h2 className="text-xl font-semibold text-gray-900">Shipping Information</h2>
                    </div>

                    <div className="space-y-4">
                      <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                          <label className="block text-sm font-medium text-gray-700 mb-1">Full Name *</label>
                          <input
                            type="text"
                            name="name"
                            value={shippingInfo.name}
                            onChange={handleInputChange}
                            className="input-field"
                            required
                          />
                        </div>
                        <div>
                          <label className="block text-sm font-medium text-gray-700 mb-1">Phone Number *</label>
                          <input
                            type="tel"
                            name="phone"
                            value={shippingInfo.phone}
                            onChange={handleInputChange}
                            className="input-field"
                            required
                          />
                        </div>
                      </div>

                      <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">Address Line 1 *</label>
                        <input
                          type="text"
                          name="address_line_1"
                          value={shippingInfo.address_line_1}
                          onChange={handleInputChange}
                          className="input-field"
                          placeholder="Street address, P.O. box"
                          required
                        />
                      </div>

                      <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">Address Line 2</label>
                        <input
                          type="text"
                          name="address_line_2"
                          value={shippingInfo.address_line_2}
                          onChange={handleInputChange}
                          className="input-field"
                          placeholder="Apartment, suite, unit, etc. (optional)"
                        />
                      </div>

                      <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                          <label className="block text-sm font-medium text-gray-700 mb-1">City *</label>
                          <input
                            type="text"
                            name="city"
                            value={shippingInfo.city}
                            onChange={handleInputChange}
                            className="input-field"
                            required
                          />
                        </div>
                        <div>
                          <label className="block text-sm font-medium text-gray-700 mb-1">State/Province *</label>
                          <input
                            type="text"
                            name="state"
                            value={shippingInfo.state}
                            onChange={handleInputChange}
                            className="input-field"
                            required
                          />
                        </div>
                        <div>
                          <label className="block text-sm font-medium text-gray-700 mb-1">Postal Code *</label>
                          <input
                            type="text"
                            name="postal_code"
                            value={shippingInfo.postal_code}
                            onChange={handleInputChange}
                            className="input-field"
                            required
                          />
                        </div>
                      </div>

                      <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">Country *</label>
                        <input
                          type="text"
                          name="country"
                          value={shippingInfo.country}
                          onChange={handleInputChange}
                          className="input-field"
                          required
                        />
                      </div>
                    </div>
                  </motion.div>
                )}

                {/* Step 2: Payment Method */}
                {step === 2 && (
                  <motion.div
                    initial={{ opacity: 0, x: 20 }}
                    animate={{ opacity: 1, x: 0 }}
                    exit={{ opacity: 0, x: -20 }}
                  >
                    <div className="flex items-center gap-2 mb-6">
                      <CreditCardIcon className="h-6 w-6 text-primary-600" />
                      <h2 className="text-xl font-semibold text-gray-900">Payment Method</h2>
                    </div>

                    <div className="space-y-4">
                      <label className="flex items-center p-4 border rounded-lg cursor-pointer hover:bg-gray-50">
                        <input
                          type="radio"
                          name="payment_method"
                          value="stripe"
                          checked={paymentMethod === 'stripe'}
                          onChange={(e) => setPaymentMethod(e.target.value)}
                          className="mr-3"
                        />
                        <CreditCardIcon className="h-6 w-6 text-gray-400 mr-3" />
                        <div>
                          <div className="font-medium text-gray-900">Credit/Debit Card</div>
                          <div className="text-sm text-gray-600">Pay securely with Stripe</div>
                        </div>
                      </label>

                      <label className="flex items-center p-4 border rounded-lg cursor-pointer hover:bg-gray-50">
                        <input
                          type="radio"
                          name="payment_method"
                          value="flutterwave"
                          checked={paymentMethod === 'flutterwave'}
                          onChange={(e) => setPaymentMethod(e.target.value)}
                          className="mr-3"
                        />
                        <CreditCardIcon className="h-6 w-6 text-gray-400 mr-3" />
                        <div>
                          <div className="font-medium text-gray-900">Bank Transfer & Mobile Money</div>
                          <div className="text-sm text-gray-600">Pay with Flutterwave</div>
                        </div>
                      </label>

                      <div className="mt-6">
                        <label className="flex items-center">
                          <input
                            type="checkbox"
                            checked={useShippingForBilling}
                            onChange={(e) => setUseShippingForBilling(e.target.checked)}
                            className="mr-2"
                          />
                          <span className="text-sm text-gray-700">Billing address same as shipping</span>
                        </label>
                      </div>
                    </div>
                  </motion.div>
                )}

                {/* Step 3: Review Order */}
                {step === 3 && (
                  <motion.div
                    initial={{ opacity: 0, x: 20 }}
                    animate={{ opacity: 1, x: 0 }}
                    exit={{ opacity: 0, x: -20 }}
                  >
                    <div className="flex items-center gap-2 mb-6">
                      <ShieldCheckIcon className="h-6 w-6 text-primary-600" />
                      <h2 className="text-xl font-semibold text-gray-900">Review Your Order</h2>
                    </div>

                    <div className="space-y-6">
                      {/* Shipping Address */}
                      <div className="border rounded-lg p-4">
                        <h3 className="font-medium text-gray-900 mb-2">Shipping Address</h3>
                        <div className="text-sm text-gray-600">
                          <p>{shippingInfo.name}</p>
                          <p>{shippingInfo.address_line_1}</p>
                          {shippingInfo.address_line_2 && <p>{shippingInfo.address_line_2}</p>}
                          <p>{shippingInfo.city}, {shippingInfo.state} {shippingInfo.postal_code}</p>
                          <p>{shippingInfo.country}</p>
                          <p>Phone: {shippingInfo.phone}</p>
                        </div>
                        <button
                          onClick={() => setStep(1)}
                          className="text-primary-600 text-sm mt-2 hover:underline"
                        >
                          Edit
                        </button>
                      </div>

                      {/* Payment Method */}
                      <div className="border rounded-lg p-4">
                        <h3 className="font-medium text-gray-900 mb-2">Payment Method</h3>
                        <p className="text-sm text-gray-600">
                          {paymentMethod === 'stripe' ? 'Credit/Debit Card (Stripe)' : 'Bank Transfer & Mobile Money (Flutterwave)'}
                        </p>
                        <button
                          onClick={() => setStep(2)}
                          className="text-primary-600 text-sm mt-2 hover:underline"
                        >
                          Edit
                        </button>
                      </div>

                      {/* Terms and Conditions */}
                      <div className="border rounded-lg p-4">
                        <label className="flex items-start">
                          <input
                            type="checkbox"
                            checked={agreeToTerms}
                            onChange={(e) => setAgreeToTerms(e.target.checked)}
                            className="mr-2 mt-1"
                          />
                          <span className="text-sm text-gray-700">
                            I agree to the{' '}
                            <a href="/terms" className="text-primary-600 hover:underline">
                              Terms & Conditions
                            </a>{' '}
                            and{' '}
                            <a href="/privacy" className="text-primary-600 hover:underline">
                              Privacy Policy
                            </a>
                          </span>
                        </label>
                      </div>
                    </div>
                  </motion.div>
                )}

                {/* Action Buttons */}
                <div className="flex justify-between mt-8 pt-6 border-t">
                  {step > 1 && (
                    <button
                      onClick={handleBack}
                      className="btn-secondary"
                      disabled={submitting}
                    >
                      Back
                    </button>
                  )}
                  
                  {step < 3 ? (
                    <button
                      onClick={handleNext}
                      className="btn-primary ml-auto"
                    >
                      Continue
                    </button>
                  ) : (
                    <button
                      onClick={handlePlaceOrder}
                      disabled={submitting || !agreeToTerms}
                      className="btn-primary ml-auto"
                    >
                      {submitting ? (
                        <>
                          <div className="animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2"></div>
                          Processing...
                        </>
                      ) : (
                        `Place Order • ZMW ${total.toFixed(2)}`
                      )}
                    </button>
                  )}
                </div>
              </div>
            </div>

            {/* Order Summary Sidebar */}
            <div className="lg:col-span-1">
              <div className="bg-white rounded-lg shadow-sm p-6 sticky top-4">
                <h2 className="text-lg font-semibold text-gray-900 mb-4">Order Summary</h2>
                
                <div className="space-y-4 mb-6">
                  {cart.items.map((item) => (
                    <div key={item.id} className="flex gap-3">
                      <div className="w-16 h-16 bg-gray-200 rounded-lg flex-shrink-0">
                        {item.product.images && item.product.images[0] && (
                          <img
                            src={item.product.images[0]}
                            alt={item.product.title}
                            className="w-full h-full object-cover rounded-lg"
                          />
                        )}
                      </div>
                      <div className="flex-1 min-w-0">
                        <p className="font-medium text-gray-900 truncate">{item.product.title}</p>
                        <p className="text-sm text-gray-600">Qty: {item.quantity}</p>
                        <p className="text-sm font-semibold text-primary-600">
                          ZMW {(item.product.price * item.quantity).toFixed(2)}
                        </p>
                      </div>
                    </div>
                  ))}
                </div>

                <div className="border-t pt-4 space-y-2 text-sm">
                  <div className="flex justify-between text-gray-600">
                    <span>Subtotal</span>
                    <span>ZMW {cart.total.toFixed(2)}</span>
                  </div>
                  <div className="flex justify-between text-gray-600">
                    <span>Shipping</span>
                    <span>{shippingCost > 0 ? `ZMW ${shippingCost.toFixed(2)}` : 'Free'}</span>
                  </div>
                  {tax > 0 && (
                    <div className="flex justify-between text-gray-600">
                      <span>Tax</span>
                      <span>ZMW {tax.toFixed(2)}</span>
                    </div>
                  )}
                  <div className="border-t pt-2 flex justify-between text-lg font-semibold text-gray-900">
                    <span>Total</span>
                    <span>ZMW {total.toFixed(2)}</span>
                  </div>
                </div>

                <div className="mt-6 p-3 bg-green-50 border border-green-200 rounded-lg">
                  <div className="flex items-center gap-2">
                    <ShieldCheckIcon className="h-4 w-4 text-green-600" />
                    <span className="text-sm font-medium text-green-800">Secure Checkout</span>
                  </div>
                  <p className="text-xs text-green-700 mt-1">
                    Your payment information is encrypted and secure
                  </p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </>
  );
}

export default function CheckoutPage() {
  return (
    <ProtectedRoute>
      <CheckoutContent />
    </ProtectedRoute>
  );
}
