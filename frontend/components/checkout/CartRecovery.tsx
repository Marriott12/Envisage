'use client';

import { useState, useEffect } from 'react';
import { useCartStore } from '@/lib/store';
import { ShoppingCart, Clock, Mail, X, ArrowRight, Heart, Bookmark } from 'lucide-react';
import Image from 'next/image';

interface CartRecoveryProps {
  autoShow?: boolean;
  delayMinutes?: number;
  className?: string;
}

interface SavedCart {
  id: string;
  items: any[];
  total: number;
  savedAt: string;
  expiresAt: string;
}

export function CartRecovery({
  autoShow = true,
  delayMinutes = 5,
  className = '',
}: CartRecoveryProps) {
  const cart = useCartStore();
  
  const [showModal, setShowModal] = useState(false);
  const [emailSent, setEmailSent] = useState(false);
  const [email, setEmail] = useState('');
  const [isSendingEmail, setIsSendingEmail] = useState(false);

  // Auto-show abandoned cart modal
  useEffect(() => {
    if (!autoShow || cart.items.length === 0) return;

    const timer = setTimeout(() => {
      // Check if user has been inactive
      const lastActivity = localStorage.getItem('lastCartActivity');
      if (lastActivity) {
        const minutesSinceActivity = (Date.now() - parseInt(lastActivity)) / 60000;
        if (minutesSinceActivity >= delayMinutes) {
          setShowModal(true);
        }
      }
    }, delayMinutes * 60 * 1000);

    return () => clearTimeout(timer);
  }, [autoShow, delayMinutes, cart.items]);

  // Track cart activity
  useEffect(() => {
    if (cart.items.length > 0) {
      localStorage.setItem('lastCartActivity', Date.now().toString());
    }
  }, [cart.items]);

  const handleSaveCart = async () => {
    try {
      const response = await fetch('/api/cart/save', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          items: cart.items,
          total: cart.getTotalPrice(),
        }),
      });

      if (response.ok) {
        const data = await response.json();
        localStorage.setItem('savedCartId', data.cartId);
        setShowModal(false);
      }
    } catch (error) {
      console.error('Failed to save cart:', error);
    }
  };

  const handleSendEmail = async () => {
    if (!email.trim()) return;

    setIsSendingEmail(true);

    try {
      const response = await fetch('/api/cart/email-reminder', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          email,
          items: cart.items,
          total: cart.getTotalPrice(),
        }),
      });

      if (response.ok) {
        setEmailSent(true);
        setTimeout(() => setShowModal(false), 2000);
      }
    } catch (error) {
      console.error('Failed to send email:', error);
    } finally {
      setIsSendingEmail(false);
    }
  };

  const handleContinueShopping = () => {
    setShowModal(false);
  };

  const handleCheckout = () => {
    setShowModal(false);
    window.location.href = '/checkout';
  };

  if (!showModal) return null;

  return (
    <div className={`fixed inset-0 z-50 flex items-center justify-center p-4 ${className}`}>
      {/* Backdrop */}
      <div
        className="absolute inset-0 bg-black bg-opacity-50 backdrop-blur-sm"
        onClick={() => setShowModal(false)}
      />

      {/* Modal */}
      <div className="relative bg-white rounded-2xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-auto">
        {/* Close Button */}
        <button
          onClick={() => setShowModal(false)}
          className="absolute top-4 right-4 p-2 hover:bg-gray-100 rounded-full transition-colors z-10"
        >
          <X className="w-5 h-5 text-gray-500" />
        </button>

        {/* Content */}
        {!emailSent ? (
          <>
            {/* Header */}
            <div className="text-center pt-8 pb-6 px-6 border-b">
              <div className="inline-flex items-center justify-center w-16 h-16 bg-blue-100 rounded-full mb-4">
                <ShoppingCart className="w-8 h-8 text-blue-600" />
              </div>
              <h2 className="text-2xl font-bold mb-2">Don't lose your items!</h2>
              <p className="text-gray-600">
                Your cart is waiting for you. Complete your purchase or save it for later.
              </p>
            </div>

            {/* Cart Items Preview */}
            <div className="p-6 max-h-64 overflow-auto">
              <h3 className="font-semibold mb-4">Items in your cart ({cart.items.length})</h3>
              <div className="space-y-3">
                {cart.items.slice(0, 3).map((item) => (
                  <div key={item.id} className="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                    <Image
                      src={item.image || '/placeholder.jpg'}
                      alt={item.title}
                      width={60}
                      height={60}
                      className="object-cover rounded"
                    />
                    <div className="flex-1 min-w-0">
                      <p className="font-medium text-sm truncate">{item.title}</p>
                      <p className="text-sm text-gray-500">Qty: {item.quantity}</p>
                    </div>
                    <p className="font-semibold">${(item.price * item.quantity).toFixed(2)}</p>
                  </div>
                ))}
                {cart.items.length > 3 && (
                  <p className="text-sm text-gray-500 text-center">
                    +{cart.items.length - 3} more items
                  </p>
                )}
              </div>

              {/* Total */}
              <div className="mt-4 pt-4 border-t flex justify-between items-center">
                <span className="font-semibold">Total:</span>
                <span className="text-2xl font-bold text-blue-600">
                  ${cart.getTotalPrice().toFixed(2)}
                </span>
              </div>
            </div>

            {/* Actions */}
            <div className="p-6 bg-gray-50 space-y-3">
              {/* Continue to Checkout */}
              <button
                onClick={handleCheckout}
                className="
                  w-full py-3 bg-blue-600 text-white rounded-lg font-medium
                  hover:bg-blue-700 transition-colors
                  flex items-center justify-center gap-2
                "
              >
                Continue to Checkout
                <ArrowRight className="w-5 h-5" />
              </button>

              {/* Save for Later */}
              <button
                onClick={handleSaveCart}
                className="
                  w-full py-3 bg-white border-2 border-gray-300 text-gray-700 rounded-lg font-medium
                  hover:border-gray-400 hover:bg-gray-50 transition-colors
                  flex items-center justify-center gap-2
                "
              >
                <Bookmark className="w-5 h-5" />
                Save Cart for Later
              </button>

              {/* Email Reminder */}
              <div className="pt-3 border-t">
                <p className="text-sm text-gray-600 mb-3 text-center">
                  Or get a reminder email to complete your purchase
                </p>
                <div className="flex gap-2">
                  <div className="relative flex-1">
                    <input
                      type="email"
                      value={email}
                      onChange={(e) => setEmail(e.target.value)}
                      placeholder="Enter your email"
                      className="
                        w-full px-4 py-2 pl-10 border border-gray-300 rounded-lg
                        focus:ring-2 focus:ring-blue-500 focus:border-transparent
                      "
                    />
                    <Mail className="absolute left-3 top-3 w-5 h-5 text-gray-400" />
                  </div>
                  <button
                    onClick={handleSendEmail}
                    disabled={isSendingEmail || !email.trim()}
                    className="
                      px-6 py-2 bg-gray-800 text-white rounded-lg
                      hover:bg-gray-900 disabled:opacity-50 disabled:cursor-not-allowed
                      transition-colors
                    "
                  >
                    {isSendingEmail ? 'Sending...' : 'Send'}
                  </button>
                </div>
              </div>

              {/* Continue Shopping */}
              <button
                onClick={handleContinueShopping}
                className="
                  w-full py-2 text-gray-600 hover:text-gray-800
                  transition-colors text-sm
                "
              >
                Continue Shopping
              </button>
            </div>
          </>
        ) : (
          // Success State
          <div className="text-center py-12 px-6">
            <div className="inline-flex items-center justify-center w-16 h-16 bg-green-100 rounded-full mb-4">
              <Mail className="w-8 h-8 text-green-600" />
            </div>
            <h2 className="text-2xl font-bold mb-2">Email Sent!</h2>
            <p className="text-gray-600 mb-6">
              We've sent a reminder to <strong>{email}</strong> with your cart details.
            </p>
            <button
              onClick={() => setShowModal(false)}
              className="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
            >
              Close
            </button>
          </div>
        )}
      </div>
    </div>
  );
}

// Saved Carts List Component
export function SavedCartsList({ className = '' }: { className?: string }) {
  const cart = useCartStore();
  const [savedCarts, setSavedCarts] = useState<SavedCart[]>([]);
  const [isLoading, setIsLoading] = useState(true);

  useEffect(() => {
    fetchSavedCarts();
  }, []);

  const fetchSavedCarts = async () => {
    try {
      const response = await fetch('/api/cart/saved');
      if (response.ok) {
        const data = await response.json();
        setSavedCarts(data.carts || []);
      }
    } catch (error) {
      console.error('Failed to fetch saved carts:', error);
    } finally {
      setIsLoading(false);
    }
  };

  const handleRestoreCart = async (savedCart: SavedCart) => {
    try {
      // Clear current cart
      cart.clearCart();

      // Restore items
      savedCart.items.forEach((item) => {
        cart.addItem(item);
      });

      // Delete saved cart
      await fetch(`/api/cart/saved/${savedCart.id}`, { method: 'DELETE' });
      
      // Refresh list
      fetchSavedCarts();
    } catch (error) {
      console.error('Failed to restore cart:', error);
    }
  };

  const handleDeleteSavedCart = async (cartId: string) => {
    try {
      await fetch(`/api/cart/saved/${cartId}`, { method: 'DELETE' });
      fetchSavedCarts();
    } catch (error) {
      console.error('Failed to delete saved cart:', error);
    }
  };

  if (isLoading) {
    return (
      <div className="flex items-center justify-center py-12">
        <Clock className="w-8 h-8 animate-spin text-gray-400" />
      </div>
    );
  }

  if (savedCarts.length === 0) {
    return (
      <div className={`text-center py-12 ${className}`}>
        <Heart className="w-16 h-16 mx-auto text-gray-400 mb-4" />
        <h3 className="text-xl font-semibold text-gray-700 mb-2">No saved carts</h3>
        <p className="text-gray-500">Save your cart to come back to it later</p>
      </div>
    );
  }

  return (
    <div className={`space-y-4 ${className}`}>
      <h2 className="text-2xl font-bold mb-6">Saved Carts</h2>
      
      {savedCarts.map((savedCart) => (
        <div
          key={savedCart.id}
          className="border border-gray-200 rounded-lg p-6 hover:shadow-md transition-shadow"
        >
          <div className="flex items-start justify-between mb-4">
            <div>
              <h3 className="font-semibold mb-1">
                Cart from {new Date(savedCart.savedAt).toLocaleDateString()}
              </h3>
              <p className="text-sm text-gray-500">
                {savedCart.items.length} items • Total: ${savedCart.total.toFixed(2)}
              </p>
              <p className="text-xs text-gray-400 mt-1">
                Expires: {new Date(savedCart.expiresAt).toLocaleDateString()}
              </p>
            </div>
            
            <button
              onClick={() => handleDeleteSavedCart(savedCart.id)}
              className="p-2 hover:bg-red-50 rounded transition-colors"
            >
              <X className="w-5 h-5 text-red-500" />
            </button>
          </div>

          {/* Items Preview */}
          <div className="grid grid-cols-2 md:grid-cols-4 gap-2 mb-4">
            {savedCart.items.slice(0, 4).map((item, index) => (
              <div key={index} className="relative aspect-square bg-gray-100 rounded overflow-hidden">
                <Image
                  src={item.image || '/placeholder.jpg'}
                  alt={item.name}
                  fill
                  className="object-cover"
                />
              </div>
            ))}
          </div>

          {/* Restore Button */}
          <button
            onClick={() => handleRestoreCart(savedCart)}
            className="
              w-full py-2 bg-blue-600 text-white rounded-lg font-medium
              hover:bg-blue-700 transition-colors
              flex items-center justify-center gap-2
            "
          >
            <ShoppingCart className="w-5 h-5" />
            Restore to Cart
          </button>
        </div>
      ))}
    </div>
  );
}

// Mini cart recovery banner (non-intrusive)
export function CartRecoveryBanner({ className = '' }: { className?: string }) {
  const cart = useCartStore();
  const [show, setShow] = useState(false);

  useEffect(() => {
    if (cart.items.length === 0) return;
    
    const timer = setTimeout(() => setShow(true), 3 * 60 * 1000); // 3 minutes
    return () => clearTimeout(timer);
  }, [cart.items.length]);

  if (!show) return null;

  return (
    <div className={`fixed bottom-4 right-4 z-40 max-w-sm ${className}`}>
      <div className="bg-white rounded-lg shadow-xl border-2 border-blue-500 p-4 animate-bounce-slow">
        <button
          onClick={() => setShow(false)}
          className="absolute top-2 right-2 text-gray-400 hover:text-gray-600"
        >
          <X className="w-4 h-4" />
        </button>
        
        <div className="flex items-start gap-3">
          <div className="flex-shrink-0 w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
            <Clock className="w-5 h-5 text-blue-600" />
          </div>
          <div className="flex-1">
            <h4 className="font-semibold mb-1">Don't forget your cart!</h4>
            <p className="text-sm text-gray-600 mb-3">
              You have {cart.items.length} item{cart.items.length > 1 ? 's' : ''} waiting
            </p>
            <div className="flex gap-2">
              <a
                href="/checkout"
                className="text-sm px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700"
              >
                Checkout
              </a>
              <button
                onClick={() => setShow(false)}
                className="text-sm px-4 py-2 text-gray-600 hover:text-gray-800"
              >
                Later
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
