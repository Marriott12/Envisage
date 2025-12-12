'use client';

import { useState } from 'react';
import { useCartStore } from '@/lib/store';
import { useCheckoutStore } from '@/lib/stores/enhanced-stores';
import { 
  ShoppingCart, 
  Truck, 
  CreditCard, 
  CheckCircle2,
  ChevronLeft,
  ChevronRight,
  Loader2
} from 'lucide-react';

interface Step {
  id: number;
  title: string;
  icon: React.ReactNode;
  completed: boolean;
}

interface MultiStepCheckoutProps {
  onComplete?: (orderId: string) => void;
  className?: string;
}

export function MultiStepCheckout({ onComplete, className = '' }: MultiStepCheckoutProps) {
  const [currentStep, setCurrentStep] = useState(1);
  const [isProcessing, setIsProcessing] = useState(false);
  const [errors, setErrors] = useState<Record<string, string>>({});

  const cart = useCartStore();
  const checkout = useCheckoutStore();

  const steps: Step[] = [
    {
      id: 1,
      title: 'Cart Review',
      icon: <ShoppingCart className="w-5 h-5" />,
      completed: currentStep > 1,
    },
    {
      id: 2,
      title: 'Shipping',
      icon: <Truck className="w-5 h-5" />,
      completed: currentStep > 2,
    },
    {
      id: 3,
      title: 'Payment',
      icon: <CreditCard className="w-5 h-5" />,
      completed: currentStep > 3,
    },
    {
      id: 4,
      title: 'Review',
      icon: <CheckCircle2 className="w-5 h-5" />,
      completed: currentStep > 4,
    },
  ];

  const validateStep = (step: number): boolean => {
    const newErrors: Record<string, string> = {};

    switch (step) {
      case 1:
        if (cart.items.length === 0) {
          newErrors.cart = 'Your cart is empty';
        }
        break;

      case 2:
        if (!checkout.shippingAddress?.address) {
          newErrors.shipping = 'Shipping address is required';
        }
        if (!checkout.shippingAddress?.city) {
          newErrors.city = 'City is required';
        }
        if (!checkout.shippingAddress?.zipCode) {
          newErrors.postalCode = 'Postal code is required';
        }
        if (!checkout.shippingAddress?.country) {
          newErrors.country = 'Country is required';
        }
        break;

      case 3:
        if (!checkout.paymentMethod) {
          newErrors.payment = 'Payment method is required';
        }
        break;
    }

    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };

  const handleNext = () => {
    if (validateStep(currentStep)) {
      if (currentStep < 4) {
        setCurrentStep(currentStep + 1);
        window.scrollTo({ top: 0, behavior: 'smooth' });
      } else {
        handlePlaceOrder();
      }
    }
  };

  const handleBack = () => {
    if (currentStep > 1) {
      setCurrentStep(currentStep - 1);
      window.scrollTo({ top: 0, behavior: 'smooth' });
    }
  };

  const handlePlaceOrder = async () => {
    setIsProcessing(true);
    
    try {
      // Simulate API call
      const response = await fetch('/api/orders', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          items: cart.items,
          shippingAddress: checkout.shippingAddress,
          billingAddress: checkout.billingAddress,
          paymentMethod: checkout.paymentMethod,
          giftWrap: checkout.giftWrap,
          giftMessage: checkout.giftMessage,
          couponCode: null, // Coupon handled in OrderSummary
          total: cart.getTotalPrice(),
        }),
      });

      if (!response.ok) throw new Error('Order failed');

      const data = await response.json();
      
      // Clear cart and checkout data
      cart.clearCart();
      checkout.reset();

      if (onComplete) {
        onComplete(data.orderId);
      }
    } catch (error) {
      setErrors({ order: 'Failed to place order. Please try again.' });
    } finally {
      setIsProcessing(false);
    }
  };

  return (
    <div className={`max-w-6xl mx-auto ${className}`}>
      {/* Progress Steps */}
      <div className="mb-8">
        <div className="flex items-center justify-between">
          {steps.map((step, index) => (
            <div key={step.id} className="flex-1">
              <div className="flex items-center">
                {/* Step Circle */}
                <div
                  className={`
                    relative flex items-center justify-center w-12 h-12 rounded-full
                    transition-all duration-300
                    ${
                      step.completed
                        ? 'bg-green-500 text-white'
                        : currentStep === step.id
                        ? 'bg-blue-600 text-white ring-4 ring-blue-200'
                        : 'bg-gray-200 text-gray-500'
                    }
                  `}
                >
                  {step.completed ? (
                    <CheckCircle2 className="w-6 h-6" />
                  ) : (
                    step.icon
                  )}
                </div>

                {/* Connector Line */}
                {index < steps.length - 1 && (
                  <div
                    className={`
                      flex-1 h-1 mx-2 transition-colors duration-300
                      ${step.completed ? 'bg-green-500' : 'bg-gray-200'}
                    `}
                  />
                )}
              </div>

              {/* Step Title */}
              <div className="mt-2">
                <p
                  className={`
                    text-sm font-medium
                    ${
                      currentStep === step.id
                        ? 'text-blue-600'
                        : step.completed
                        ? 'text-green-600'
                        : 'text-gray-500'
                    }
                  `}
                >
                  {step.title}
                </p>
              </div>
            </div>
          ))}
        </div>
      </div>

      {/* Error Messages */}
      {Object.keys(errors).length > 0 && (
        <div className="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
          <ul className="list-disc list-inside text-sm text-red-600 space-y-1">
            {Object.values(errors).map((error, index) => (
              <li key={index}>{error}</li>
            ))}
          </ul>
        </div>
      )}

      {/* Step Content */}
      <div className="bg-white rounded-lg shadow-lg p-6 mb-6 min-h-[400px]">
        {currentStep === 1 && <CartReviewStep />}
        {currentStep === 2 && <ShippingStep />}
        {currentStep === 3 && <PaymentStep />}
        {currentStep === 4 && <ReviewStep />}
      </div>

      {/* Navigation Buttons */}
      <div className="flex items-center justify-between">
        <button
          onClick={handleBack}
          disabled={currentStep === 1}
          className="
            flex items-center gap-2 px-6 py-3 rounded-lg
            text-gray-700 bg-gray-100 hover:bg-gray-200
            disabled:opacity-50 disabled:cursor-not-allowed
            transition-colors
          "
        >
          <ChevronLeft className="w-5 h-5" />
          Back
        </button>

        <button
          onClick={handleNext}
          disabled={isProcessing}
          className="
            flex items-center gap-2 px-8 py-3 rounded-lg
            text-white bg-blue-600 hover:bg-blue-700
            disabled:opacity-50 disabled:cursor-not-allowed
            transition-colors font-medium
          "
        >
          {isProcessing ? (
            <>
              <Loader2 className="w-5 h-5 animate-spin" />
              Processing...
            </>
          ) : currentStep === 4 ? (
            <>
              Place Order
              <CheckCircle2 className="w-5 h-5" />
            </>
          ) : (
            <>
              Continue
              <ChevronRight className="w-5 h-5" />
            </>
          )}
        </button>
      </div>
    </div>
  );
}

// Step 1: Cart Review
function CartReviewStep() {
  const cart = useCartStore();

  if (cart.items.length === 0) {
    return (
      <div className="text-center py-12">
        <ShoppingCart className="w-16 h-16 mx-auto text-gray-400 mb-4" />
        <h3 className="text-xl font-semibold text-gray-700 mb-2">Your cart is empty</h3>
        <p className="text-gray-500">Add items to your cart to continue checkout</p>
      </div>
    );
  }

  return (
    <div>
      <h2 className="text-2xl font-bold mb-6">Review Your Cart</h2>
      
      <div className="space-y-4">
        {cart.items.map((item: any) => (
          <div key={item.id} className="flex items-center gap-4 p-4 bg-gray-50 rounded-lg">
            <img
              src={item.image || '/placeholder.jpg'}
              alt={item.title}
              className="w-20 h-20 object-cover rounded"
            />
            <div className="flex-1">
              <h4 className="font-semibold">{item.title}</h4>
              {item.variant && (
                <p className="text-sm text-gray-500">Variant: {item.variant}</p>
              )}
              <p className="text-sm text-gray-600">Quantity: {item.quantity}</p>
            </div>
            <div className="text-right">
              <p className="font-semibold">${(item.price * item.quantity).toFixed(2)}</p>
              <p className="text-sm text-gray-500">${item.price.toFixed(2)} each</p>
            </div>
          </div>
        ))}
      </div>

      <div className="mt-6 pt-6 border-t">
        <div className="flex justify-between text-lg font-semibold">
          <span>Subtotal:</span>
          <span>${cart.getTotalPrice().toFixed(2)}</span>
        </div>
      </div>
    </div>
  );
}

// Step 2: Shipping
function ShippingStep() {
  return (
    <div>
      <h2 className="text-2xl font-bold mb-6">Shipping Information</h2>
      <p className="text-gray-600 mb-4">
        This step will use the AddressForm component (to be created next)
      </p>
      <div className="p-8 bg-gray-50 rounded-lg text-center text-gray-500">
        AddressForm component placeholder
      </div>
    </div>
  );
}

// Step 3: Payment
function PaymentStep() {
  return (
    <div>
      <h2 className="text-2xl font-bold mb-6">Payment Method</h2>
      <p className="text-gray-600 mb-4">
        This step will use the PaymentMethodSelector component (to be created next)
      </p>
      <div className="p-8 bg-gray-50 rounded-lg text-center text-gray-500">
        PaymentMethodSelector component placeholder
      </div>
    </div>
  );
}

// Step 4: Review
function ReviewStep() {
  const cart = useCartStore();
  const checkout = useCheckoutStore();

  return (
    <div>
      <h2 className="text-2xl font-bold mb-6">Review Your Order</h2>

      {/* Order Summary */}
      <div className="space-y-6">
        {/* Items */}
        <div>
          <h3 className="font-semibold mb-3">Order Items ({cart.items.length})</h3>
          <div className="space-y-2">
            {cart.items.map((item: any) => (
              <div key={item.id} className="flex justify-between text-sm">
                <span>
                  {item.title} x {item.quantity}
                </span>
                <span>${(item.price * item.quantity).toFixed(2)}</span>
              </div>
            ))}
          </div>
        </div>

        {/* Shipping Address */}
        {checkout.shippingAddress && (
          <div>
            <h3 className="font-semibold mb-3">Shipping Address</h3>
            <div className="text-sm text-gray-600">
              <p>{checkout.shippingAddress.fullName}</p>
              <p>{checkout.shippingAddress.address}</p>
              <p>
                {checkout.shippingAddress.city}, {checkout.shippingAddress.state}{' '}
                {checkout.shippingAddress.zipCode}
              </p>
              <p>{checkout.shippingAddress.country}</p>
            </div>
          </div>
        )}

        {/* Payment Method */}
        {checkout.paymentMethod && (
          <div>
            <h3 className="font-semibold mb-3">Payment Method</h3>
            <p className="text-sm text-gray-600 capitalize">{checkout.paymentMethod}</p>
          </div>
        )}

        {/* Totals */}
        <div className="pt-6 border-t space-y-2">
          <div className="flex justify-between">
            <span>Subtotal:</span>
            <span>${cart.getTotalPrice().toFixed(2)}</span>
          </div>
          <div className="flex justify-between text-xl font-bold pt-3 border-t">
            <span>Total:</span>
            <span>${cart.getTotalPrice().toFixed(2)}</span>
          </div>
        </div>
      </div>
    </div>
  );
}

// Compact variant for mobile
export function CompactCheckout({ onComplete, className = '' }: MultiStepCheckoutProps) {
  return (
    <div className={`max-w-2xl mx-auto ${className}`}>
      <MultiStepCheckout onComplete={onComplete} />
    </div>
  );
}
