'use client';

import React, { useState } from 'react';
import { CheckCircleIcon, ClockIcon, CreditCardIcon } from '@heroicons/react/24/outline';
import api from '@/lib/api';
import { toast } from 'react-hot-toast';

interface BNPLPlan {
  id: number;
  name: string;
  installments: number;
  interval: string;
  interest_rate: number;
  min_amount: number;
  max_amount: number;
  processing_fee: number;
}

interface BNPLCheckoutProps {
  orderAmount: number;
  onPlanSelected: (planId: number) => void;
  onCancel: () => void;
}

export default function BNPLCheckout({ orderAmount, onPlanSelected, onCancel }: BNPLCheckoutProps) {
  const [plans, setPlans] = useState<BNPLPlan[]>([]);
  const [selectedPlan, setSelectedPlan] = useState<number | null>(null);
  const [loading, setLoading] = useState(false);
  const [loadingPlans, setLoadingPlans] = useState(true);

  React.useEffect(() => {
    fetchPlans();
  }, [orderAmount]);

  const fetchPlans = async () => {
    try {
      setLoadingPlans(true);
      const response = await api.get('/bnpl/plans', {
        params: { amount: orderAmount }
      });
      setPlans(response.data.filter((plan: BNPLPlan) => 
        orderAmount >= plan.min_amount && orderAmount <= plan.max_amount
      ));
    } catch (error) {
      console.error('Failed to fetch BNPL plans:', error);
      toast.error('Failed to load payment plans');
    } finally {
      setLoadingPlans(false);
    }
  };

  const calculateInstallment = (plan: BNPLPlan) => {
    const total = orderAmount + plan.processing_fee;
    const withInterest = total * (1 + plan.interest_rate / 100);
    return (withInterest / plan.installments).toFixed(2);
  };

  const calculateTotal = (plan: BNPLPlan) => {
    const total = orderAmount + plan.processing_fee;
    return (total * (1 + plan.interest_rate / 100)).toFixed(2);
  };

  const handleConfirm = async () => {
    if (!selectedPlan) {
      toast.error('Please select a payment plan');
      return;
    }

    setLoading(true);
    try {
      await onPlanSelected(selectedPlan);
      toast.success('Payment plan selected!');
    } catch (error) {
      console.error('Failed to select plan:', error);
      toast.error('Failed to set up payment plan');
    } finally {
      setLoading(false);
    }
  };

  if (loadingPlans) {
    return (
      <div className="bg-white rounded-lg p-8">
        <div className="animate-pulse space-y-4">
          <div className="h-4 bg-gray-200 rounded w-3/4"></div>
          <div className="h-32 bg-gray-200 rounded"></div>
          <div className="h-32 bg-gray-200 rounded"></div>
        </div>
      </div>
    );
  }

  if (plans.length === 0) {
    return (
      <div className="bg-white rounded-lg p-8 text-center">
        <p className="text-gray-600 mb-4">
          No payment plans available for this order amount.
        </p>
        <button onClick={onCancel} className="btn-secondary">
          Go Back
        </button>
      </div>
    );
  }

  return (
    <div className="bg-white rounded-lg p-6">
      <h3 className="text-2xl font-bold text-gray-900 mb-2">
        Choose Your Payment Plan
      </h3>
      <p className="text-gray-600 mb-6">
        Split your ${orderAmount.toFixed(2)} purchase into easy installments
      </p>

      <div className="space-y-4 mb-6">
        {plans.map((plan) => (
          <div
            key={plan.id}
            onClick={() => setSelectedPlan(plan.id)}
            className={`border-2 rounded-lg p-6 cursor-pointer transition-all ${
              selectedPlan === plan.id
                ? 'border-primary-500 bg-primary-50'
                : 'border-gray-200 hover:border-gray-300'
            }`}
          >
            <div className="flex items-start justify-between mb-4">
              <div>
                <h4 className="text-lg font-semibold text-gray-900 flex items-center gap-2">
                  {plan.name}
                  {selectedPlan === plan.id && (
                    <CheckCircleIcon className="w-5 h-5 text-primary-600" />
                  )}
                </h4>
                <p className="text-sm text-gray-600 mt-1">
                  {plan.installments} payments every {plan.interval}
                </p>
              </div>
              <div className="text-right">
                <div className="text-2xl font-bold text-gray-900">
                  ${calculateInstallment(plan)}
                </div>
                <div className="text-sm text-gray-500">per payment</div>
              </div>
            </div>

            <div className="flex items-center gap-4 text-sm">
              <div className="flex items-center gap-1 text-gray-600">
                <ClockIcon className="w-4 h-4" />
                <span>First payment today</span>
              </div>
              {plan.interest_rate > 0 && (
                <div className="text-gray-600">
                  {plan.interest_rate}% interest
                </div>
              )}
              {plan.processing_fee > 0 && (
                <div className="text-gray-600">
                  ${plan.processing_fee.toFixed(2)} processing fee
                </div>
              )}
            </div>

            <div className="mt-4 pt-4 border-t border-gray-200">
              <div className="flex justify-between text-sm">
                <span className="text-gray-600">Total amount:</span>
                <span className="font-semibold text-gray-900">
                  ${calculateTotal(plan)}
                </span>
              </div>
            </div>
          </div>
        ))}
      </div>

      <div className="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
        <div className="flex gap-3">
          <CreditCardIcon className="w-5 h-5 text-blue-600 flex-shrink-0 mt-0.5" />
          <div className="text-sm text-blue-900">
            <p className="font-semibold mb-1">How it works:</p>
            <ul className="space-y-1 text-blue-800">
              <li>• Pay your first installment today at checkout</li>
              <li>• Remaining payments charged automatically</li>
              <li>• No impact on credit score</li>
              <li>• Cancel anytime after full payment</li>
            </ul>
          </div>
        </div>
      </div>

      <div className="flex gap-3">
        <button
          onClick={onCancel}
          className="flex-1 btn-secondary"
          disabled={loading}
        >
          Cancel
        </button>
        <button
          onClick={handleConfirm}
          disabled={!selectedPlan || loading}
          className="flex-1 btn-primary"
        >
          {loading ? 'Processing...' : 'Continue with Selected Plan'}
        </button>
      </div>
    </div>
  );
}
