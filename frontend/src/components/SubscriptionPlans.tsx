import React, { useState, useEffect } from 'react';
import { Check, Crown, Zap, TrendingUp, Star } from 'lucide-react';

interface Plan {
  id: number;
  name: string;
  description: string;
  price: number;
  billing_cycle: 'monthly' | 'yearly' | 'lifetime';
  product_limit: number;
  commission_rate: number;
  featured_slots: number;
  features: string[];
  is_active: boolean;
}

interface Subscription {
  id: number;
  plan: Plan;
  start_date: string;
  end_date: string;
  status: 'active' | 'cancelled' | 'expired';
  auto_renew: boolean;
}

interface SubscriptionPlansProps {
  userId: number;
  apiToken: string;
}

export default function SubscriptionPlans({ userId, apiToken }: SubscriptionPlansProps) {
  const [plans, setPlans] = useState<Plan[]>([]);
  const [currentSubscription, setCurrentSubscription] = useState<Subscription | null>(null);
  const [billingCycle, setBillingCycle] = useState<'monthly' | 'yearly'>('monthly');
  const [loading, setLoading] = useState(true);
  const [subscribing, setSubscribing] = useState(false);

  const API_BASE = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000/api';

  useEffect(() => {
    fetchPlans();
    fetchCurrentSubscription();
  }, []);

  const fetchPlans = async () => {
    try {
      const response = await fetch(`${API_BASE}/subscriptions/plans`);
      const data = await response.json();
      if (data.success) {
        setPlans(data.data);
      }
    } catch (error) {
      console.error('Failed to fetch plans:', error);
    }
  };

  const fetchCurrentSubscription = async () => {
    setLoading(true);
    try {
      const response = await fetch(`${API_BASE}/subscriptions/current`, {
        headers: {
          'Authorization': `Bearer ${apiToken}`,
          'Accept': 'application/json',
        },
      });
      const data = await response.json();
      if (data.success && data.data) {
        setCurrentSubscription(data.data);
      }
    } catch (error) {
      console.error('Failed to fetch current subscription:', error);
    } finally {
      setLoading(false);
    }
  };

  const subscribe = async (planId: number) => {
    setSubscribing(true);
    try {
      const response = await fetch(`${API_BASE}/subscriptions/subscribe`, {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${apiToken}`,
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        body: JSON.stringify({ plan_id: planId }),
      });
      const data = await response.json();
      if (data.success && data.checkout_url) {
        // Redirect to Stripe Checkout
        window.location.href = data.checkout_url;
      }
    } catch (error) {
      console.error('Failed to subscribe:', error);
      alert('Failed to start subscription. Please try again.');
    } finally {
      setSubscribing(false);
    }
  };

  const cancelSubscription = async () => {
    if (!confirm('Are you sure you want to cancel your subscription? You will lose access at the end of your billing period.')) {
      return;
    }

    try {
      const response = await fetch(`${API_BASE}/subscriptions/cancel`, {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${apiToken}`,
          'Accept': 'application/json',
        },
      });
      const data = await response.json();
      if (data.success) {
        alert('Subscription cancelled successfully. You will retain access until the end of your billing period.');
        fetchCurrentSubscription();
      }
    } catch (error) {
      console.error('Failed to cancel subscription:', error);
      alert('Failed to cancel subscription. Please try again.');
    }
  };

  const getPlanIcon = (planName: string) => {
    if (planName.toLowerCase().includes('premium') || planName.toLowerCase().includes('pro')) {
      return <Crown className="text-yellow-500" size={32} />;
    }
    if (planName.toLowerCase().includes('basic') || planName.toLowerCase().includes('starter')) {
      return <Zap className="text-blue-500" size={32} />;
    }
    return <Star className="text-purple-500" size={32} />;
  };

  const filteredPlans = plans.filter(plan => plan.billing_cycle === billingCycle);

  if (loading) {
    return (
      <div className="text-center py-12">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-purple-500 mx-auto"></div>
        <p className="mt-4 text-gray-600">Loading subscription plans...</p>
      </div>
    );
  }

  return (
    <div className="max-w-7xl mx-auto px-4 py-12">
      {/* Header */}
      <div className="text-center mb-12">
        <h1 className="text-4xl font-bold mb-4">Choose Your Plan</h1>
        <p className="text-xl text-gray-600 mb-8">
          Scale your business with our flexible subscription plans
        </p>

        {/* Billing Cycle Toggle */}
        <div className="inline-flex bg-gray-100 rounded-lg p-1">
          <button
            onClick={() => setBillingCycle('monthly')}
            className={`px-6 py-2 rounded-lg font-medium transition ${
              billingCycle === 'monthly'
                ? 'bg-white text-purple-600 shadow'
                : 'text-gray-600'
            }`}
          >
            Monthly
          </button>
          <button
            onClick={() => setBillingCycle('yearly')}
            className={`px-6 py-2 rounded-lg font-medium transition ${
              billingCycle === 'yearly'
                ? 'bg-white text-purple-600 shadow'
                : 'text-gray-600'
            }`}
          >
            Yearly
            <span className="ml-2 text-xs bg-green-100 text-green-700 px-2 py-1 rounded">
              Save 20%
            </span>
          </button>
        </div>
      </div>

      {/* Current Subscription Banner */}
      {currentSubscription && currentSubscription.status === 'active' && (
        <div className="mb-8 bg-gradient-to-r from-purple-500 to-pink-500 text-white rounded-lg p-6">
          <div className="flex items-center justify-between">
            <div>
              <h3 className="text-xl font-bold mb-2">
                Current Plan: {currentSubscription.plan.name}
              </h3>
              <p className="text-purple-100">
                {currentSubscription.auto_renew ? 'Renews' : 'Expires'} on{' '}
                {new Date(currentSubscription.end_date).toLocaleDateString()}
              </p>
            </div>
            <button
              onClick={cancelSubscription}
              className="bg-white text-purple-600 px-6 py-2 rounded-lg font-medium hover:bg-purple-50 transition"
            >
              Cancel Subscription
            </button>
          </div>
        </div>
      )}

      {/* Plans Grid */}
      <div className="grid md:grid-cols-3 gap-8">
        {filteredPlans.map((plan) => {
          const isCurrentPlan = currentSubscription?.plan.id === plan.id;
          const isPremium = plan.name.toLowerCase().includes('premium') || 
                          plan.name.toLowerCase().includes('pro');

          return (
            <div
              key={plan.id}
              className={`bg-white rounded-2xl shadow-lg border-2 transition-transform hover:scale-105 ${
                isPremium ? 'border-purple-500 relative' : 'border-gray-200'
              }`}
            >
              {isPremium && (
                <div className="absolute -top-4 left-1/2 transform -translate-x-1/2">
                  <span className="bg-gradient-to-r from-purple-500 to-pink-500 text-white px-4 py-1 rounded-full text-sm font-bold">
                    POPULAR
                  </span>
                </div>
              )}

              <div className="p-8">
                {/* Plan Header */}
                <div className="text-center mb-6">
                  <div className="mb-4 flex justify-center">
                    {getPlanIcon(plan.name)}
                  </div>
                  <h3 className="text-2xl font-bold mb-2">{plan.name}</h3>
                  <p className="text-gray-600 text-sm">{plan.description}</p>
                </div>

                {/* Pricing */}
                <div className="text-center mb-6">
                  <div className="flex items-baseline justify-center">
                    <span className="text-5xl font-bold">${plan.price}</span>
                    <span className="text-gray-600 ml-2">/{plan.billing_cycle}</span>
                  </div>
                  {billingCycle === 'yearly' && (
                    <p className="text-sm text-green-600 mt-2">
                      Save ${(plan.price * 12 * 0.2).toFixed(2)}/year
                    </p>
                  )}
                </div>

                {/* Features */}
                <ul className="space-y-3 mb-8">
                  <li className="flex items-start gap-2">
                    <Check className="text-green-500 flex-shrink-0 mt-1" size={20} />
                    <span className="text-gray-700">
                      List up to <strong>{plan.product_limit}</strong> products
                    </span>
                  </li>
                  <li className="flex items-start gap-2">
                    <Check className="text-green-500 flex-shrink-0 mt-1" size={20} />
                    <span className="text-gray-700">
                      <strong>{plan.commission_rate}%</strong> commission rate
                    </span>
                  </li>
                  <li className="flex items-start gap-2">
                    <Check className="text-green-500 flex-shrink-0 mt-1" size={20} />
                    <span className="text-gray-700">
                      <strong>{plan.featured_slots}</strong> featured product slots
                    </span>
                  </li>
                  {plan.features && plan.features.map((feature, idx) => (
                    <li key={idx} className="flex items-start gap-2">
                      <Check className="text-green-500 flex-shrink-0 mt-1" size={20} />
                      <span className="text-gray-700">{feature}</span>
                    </li>
                  ))}
                </ul>

                {/* CTA Button */}
                <button
                  onClick={() => subscribe(plan.id)}
                  disabled={isCurrentPlan || subscribing}
                  className={`w-full py-3 rounded-lg font-bold text-lg transition ${
                    isCurrentPlan
                      ? 'bg-gray-200 text-gray-500 cursor-not-allowed'
                      : isPremium
                      ? 'bg-gradient-to-r from-purple-500 to-pink-500 text-white hover:opacity-90'
                      : 'bg-purple-600 text-white hover:bg-purple-700'
                  }`}
                >
                  {isCurrentPlan ? 'Current Plan' : subscribing ? 'Processing...' : 'Get Started'}
                </button>
              </div>
            </div>
          );
        })}
      </div>

      {/* FAQ Section */}
      <div className="mt-16 bg-gray-50 rounded-lg p-8">
        <h2 className="text-2xl font-bold mb-6 text-center">Frequently Asked Questions</h2>
        <div className="grid md:grid-cols-2 gap-6">
          <div>
            <h3 className="font-bold mb-2">Can I change my plan later?</h3>
            <p className="text-gray-600 text-sm">
              Yes! You can upgrade or downgrade your plan at any time. Changes take effect immediately.
            </p>
          </div>
          <div>
            <h3 className="font-bold mb-2">What payment methods do you accept?</h3>
            <p className="text-gray-600 text-sm">
              We accept all major credit cards, debit cards, and digital wallets through Stripe.
            </p>
          </div>
          <div>
            <h3 className="font-bold mb-2">Is there a free trial?</h3>
            <p className="text-gray-600 text-sm">
              Yes! All plans come with a 14-day free trial. No credit card required to start.
            </p>
          </div>
          <div>
            <h3 className="font-bold mb-2">Can I cancel anytime?</h3>
            <p className="text-gray-600 text-sm">
              Absolutely. You can cancel your subscription at any time. You'll keep access until the end of your billing period.
            </p>
          </div>
        </div>
      </div>

      {/* Trust Indicators */}
      <div className="mt-12 text-center">
        <p className="text-gray-600 mb-4">Trusted by over 10,000+ sellers worldwide</p>
        <div className="flex items-center justify-center gap-8 text-sm text-gray-500">
          <div className="flex items-center gap-2">
            <Check className="text-green-500" size={16} />
            Secure Payments
          </div>
          <div className="flex items-center gap-2">
            <Check className="text-green-500" size={16} />
            Cancel Anytime
          </div>
          <div className="flex items-center gap-2">
            <Check className="text-green-500" size={16} />
            24/7 Support
          </div>
        </div>
      </div>
    </div>
  );
}
