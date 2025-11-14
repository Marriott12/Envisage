'use client';

import React, { useState } from 'react';
import { Dialog, Transition } from '@headlessui/react';
import { Fragment } from 'react';
import { motion } from 'framer-motion';
import { useForm, SubmitHandler } from 'react-hook-form';
import { toast } from 'react-hot-toast';
import {
  XMarkIcon,
  CreditCardIcon,
  TruckIcon,
  ShieldCheckIcon,
  MapPinIcon
} from '@heroicons/react/24/outline';
import { formatPrice } from '@/lib/utils';
import { marketplaceApi, type Listing, type OrderData } from '@/lib/api';
import { useAuthStore } from '@/lib/store';

interface CheckoutModalProps {
  isOpen: boolean;
  onClose: () => void;
  listing: Listing;
  onOrderSuccess: (orderId: number, paymentUrl?: string) => void;
}

interface FormData {
  // Shipping Address
  shipping_name: string;
  shipping_address_line_1: string;
  shipping_address_line_2?: string;
  shipping_city: string;
  shipping_state: string;
  shipping_postal_code: string;
  shipping_country: string;
  shipping_phone: string;

  // Billing Address
  billing_same_as_shipping: boolean;
  billing_name?: string;
  billing_address_line_1?: string;
  billing_address_line_2?: string;
  billing_city?: string;
  billing_state?: string;
  billing_postal_code?: string;
  billing_country?: string;

  // Payment and Options
  payment_method: string;
  notes?: string;
  agree_to_terms: boolean;
}

const PAYMENT_METHODS = [
  { value: 'stripe', label: 'Credit/Debit Card', icon: CreditCardIcon },
  { value: 'flutterwave', label: 'Bank Transfer & Mobile Money', icon: CreditCardIcon },
];

export default function CheckoutModal({ 
  isOpen, 
  onClose, 
  listing, 
  onOrderSuccess 
}: CheckoutModalProps) {
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [currentStep, setCurrentStep] = useState(1); // 1: Shipping, 2: Payment, 3: Review
  const { user } = useAuthStore();

  const {
    register,
    handleSubmit,
    watch,
    reset,
    formState: { errors, isValid }
  } = useForm<FormData>({
    mode: 'onChange',
    defaultValues: {
      shipping_name: user?.name || '',
      shipping_country: 'United States',
      billing_same_as_shipping: true,
      payment_method: 'stripe',
      agree_to_terms: false,
    }
  });

  const watchBillingSame = watch('billing_same_as_shipping');
  const watchAllFields = watch();

  // Calculate totals
  const itemPrice = listing.price;
  const shippingCost = 0; // Free shipping for now
  const taxAmount = 0; // No tax calculation for now
  const totalAmount = itemPrice + shippingCost + taxAmount;

  const onSubmit: SubmitHandler<FormData> = async (data) => {
    if (!user) {
      toast.error('Please login to continue');
      return;
    }

    setIsSubmitting(true);

    try {
      const orderData: OrderData = {
        shipping_address: {
          name: data.shipping_name,
          address_line_1: data.shipping_address_line_1,
          address_line_2: data.shipping_address_line_2 || '',
          city: data.shipping_city,
          state: data.shipping_state,
          postal_code: data.shipping_postal_code,
          country: data.shipping_country,
          phone: data.shipping_phone,
        },
        payment_method: data.payment_method,
        notes: data.notes || '',
      };

      // Add billing address if different from shipping
      if (!data.billing_same_as_shipping) {
        orderData.billing_address = {
          name: data.billing_name || '',
          address_line_1: data.billing_address_line_1 || '',
          address_line_2: data.billing_address_line_2 || '',
          city: data.billing_city || '',
          state: data.billing_state || '',
          postal_code: data.billing_postal_code || '',
          country: data.billing_country || '',
        };
      }

      const response = await marketplaceApi.buyListing(listing.id, orderData);

      if (response.status === 'success') {
        toast.success('Order placed successfully!');
        onOrderSuccess(response.data.order_id, response.data.payment_url);
        reset();
        onClose();
      } else {
        throw new Error(response.message || 'Failed to place order');
      }
    } catch (error: any) {
      console.error('Order creation error:', error);
      toast.error(error.message || 'Failed to place order. Please try again.');
    } finally {
      setIsSubmitting(false);
    }
  };

  const handleClose = () => {
    if (!isSubmitting) {
      reset();
      setCurrentStep(1);
      onClose();
    }
  };

  const nextStep = () => {
    if (currentStep < 3) {
      setCurrentStep(currentStep + 1);
    }
  };

  const prevStep = () => {
    if (currentStep > 1) {
      setCurrentStep(currentStep - 1);
    }
  };

  return (
    <Transition appear show={isOpen} as={Fragment}>
      <Dialog as="div" className="relative z-50" onClose={handleClose}>
        <Transition.Child
          as={Fragment}
          enter="ease-out duration-300"
          enterFrom="opacity-0"
          enterTo="opacity-100"
          leave="ease-in duration-200"
          leaveFrom="opacity-100"
          leaveTo="opacity-0"
        >
          <div className="fixed inset-0 bg-black/25 backdrop-blur-sm" />
        </Transition.Child>

        <div className="fixed inset-0 overflow-y-auto">
          <div className="flex min-h-full items-center justify-center p-4 text-center">
            <Transition.Child
              as={Fragment}
              enter="ease-out duration-300"
              enterFrom="opacity-0 scale-95"
              enterTo="opacity-100 scale-100"
              leave="ease-in duration-200"
              leaveFrom="opacity-100 scale-100"
              leaveTo="opacity-0 scale-95"
            >
              <Dialog.Panel className="w-full max-w-4xl transform overflow-hidden rounded-2xl bg-white text-left align-middle shadow-xl transition-all">
                <form onSubmit={handleSubmit(onSubmit)}>
                  {/* Header */}
                  <div className="flex items-center justify-between p-6 border-b">
                    <div>
                      <Dialog.Title as="h3" className="text-lg font-semibold text-gray-900">
                        Complete Your Purchase
                      </Dialog.Title>
                      <p className="text-sm text-gray-600 mt-1">
                        Step {currentStep} of 3
                      </p>
                    </div>
                    <button
                      type="button"
                      onClick={handleClose}
                      disabled={isSubmitting}
                      className="p-2 hover:bg-gray-100 rounded-lg transition-colors"
                    >
                      <XMarkIcon className="h-6 w-6" />
                    </button>
                  </div>

                  {/* Progress Bar */}
                  <div className="px-6 py-3 bg-gray-50">
                    <div className="flex items-center">
                      {[1, 2, 3].map((step) => (
                        <div key={step} className="flex items-center">
                          <div className={`flex items-center justify-center w-8 h-8 rounded-full text-sm font-medium ${
                            currentStep >= step 
                              ? 'bg-primary-600 text-white' 
                              : 'bg-gray-200 text-gray-600'
                          }`}>
                            {step}
                          </div>
                          <div className={`text-sm ml-2 ${
                            currentStep >= step ? 'text-primary-600' : 'text-gray-500'
                          }`}>
                            {step === 1 && 'Shipping'}
                            {step === 2 && 'Payment'}
                            {step === 3 && 'Review'}
                          </div>
                          {step < 3 && (
                            <div className={`h-0.5 w-16 mx-4 ${
                              currentStep > step ? 'bg-primary-600' : 'bg-gray-200'
                            }`} />
                          )}
                        </div>
                      ))}
                    </div>
                  </div>

                  <div className="flex">
                    {/* Main Content */}
                    <div className="flex-1 p-6">
                      {/* Step 1: Shipping Information */}
                      {currentStep === 1 && (
                        <motion.div
                          initial={{ opacity: 0, x: 20 }}
                          animate={{ opacity: 1, x: 0 }}
                          exit={{ opacity: 0, x: -20 }}
                          className="space-y-6"
                        >
                          <div className="flex items-center gap-2 mb-4">
                            <TruckIcon className="h-5 w-5 text-primary-600" />
                            <h4 className="font-medium text-gray-900">Shipping Information</h4>
                          </div>

                          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                              <label className="block text-sm font-medium text-gray-700 mb-1">
                                Full Name *
                              </label>
                              <input
                                {...register('shipping_name', { required: 'Full name is required' })}
                                className="input-field"
                                placeholder="Enter your full name"
                              />
                              {errors.shipping_name && (
                                <p className="text-red-600 text-sm mt-1">{errors.shipping_name.message}</p>
                              )}
                            </div>

                            <div>
                              <label className="block text-sm font-medium text-gray-700 mb-1">
                                Phone Number *
                              </label>
                              <input
                                {...register('shipping_phone', { 
                                  required: 'Phone number is required',
                                  pattern: {
                                    value: /^[+]?[\d\s\-\(\)]{10,}$/,
                                    message: 'Invalid phone number'
                                  }
                                })}
                                className="input-field"
                                placeholder="+1 (555) 123-4567"
                              />
                              {errors.shipping_phone && (
                                <p className="text-red-600 text-sm mt-1">{errors.shipping_phone.message}</p>
                              )}
                            </div>

                            <div className="md:col-span-2">
                              <label className="block text-sm font-medium text-gray-700 mb-1">
                                Address Line 1 *
                              </label>
                              <input
                                {...register('shipping_address_line_1', { required: 'Address is required' })}
                                className="input-field"
                                placeholder="Street address, P.O. box, company name"
                              />
                              {errors.shipping_address_line_1 && (
                                <p className="text-red-600 text-sm mt-1">{errors.shipping_address_line_1.message}</p>
                              )}
                            </div>

                            <div className="md:col-span-2">
                              <label className="block text-sm font-medium text-gray-700 mb-1">
                                Address Line 2
                              </label>
                              <input
                                {...register('shipping_address_line_2')}
                                className="input-field"
                                placeholder="Apartment, suite, unit, building, floor, etc."
                              />
                            </div>

                            <div>
                              <label className="block text-sm font-medium text-gray-700 mb-1">
                                City *
                              </label>
                              <input
                                {...register('shipping_city', { required: 'City is required' })}
                                className="input-field"
                                placeholder="Enter city"
                              />
                              {errors.shipping_city && (
                                <p className="text-red-600 text-sm mt-1">{errors.shipping_city.message}</p>
                              )}
                            </div>

                            <div>
                              <label className="block text-sm font-medium text-gray-700 mb-1">
                                State/Province *
                              </label>
                              <input
                                {...register('shipping_state', { required: 'State is required' })}
                                className="input-field"
                                placeholder="Enter state/province"
                              />
                              {errors.shipping_state && (
                                <p className="text-red-600 text-sm mt-1">{errors.shipping_state.message}</p>
                              )}
                            </div>

                            <div>
                              <label className="block text-sm font-medium text-gray-700 mb-1">
                                Postal Code *
                              </label>
                              <input
                                {...register('shipping_postal_code', { required: 'Postal code is required' })}
                                className="input-field"
                                placeholder="Enter postal code"
                              />
                              {errors.shipping_postal_code && (
                                <p className="text-red-600 text-sm mt-1">{errors.shipping_postal_code.message}</p>
                              )}
                            </div>

                            <div>
                              <label className="block text-sm font-medium text-gray-700 mb-1">
                                Country *
                              </label>
                              <select
                                {...register('shipping_country', { required: 'Country is required' })}
                                className="input-field"
                              >
                                <option value="United States">United States</option>
                                <option value="Canada">Canada</option>
                                <option value="United Kingdom">United Kingdom</option>
                                <option value="Nigeria">Nigeria</option>
                                <option value="Kenya">Kenya</option>
                                <option value="South Africa">South Africa</option>
                                <option value="Other">Other</option>
                              </select>
                              {errors.shipping_country && (
                                <p className="text-red-600 text-sm mt-1">{errors.shipping_country.message}</p>
                              )}
                            </div>
                          </div>
                        </motion.div>
                      )}

                      {/* Step 2: Payment Method */}
                      {currentStep === 2 && (
                        <motion.div
                          initial={{ opacity: 0, x: 20 }}
                          animate={{ opacity: 1, x: 0 }}
                          exit={{ opacity: 0, x: -20 }}
                          className="space-y-6"
                        >
                          <div className="flex items-center gap-2 mb-4">
                            <CreditCardIcon className="h-5 w-5 text-primary-600" />
                            <h4 className="font-medium text-gray-900">Payment Method</h4>
                          </div>

                          <div className="space-y-4">
                            {PAYMENT_METHODS.map((method) => (
                              <label
                                key={method.value}
                                className={`flex items-center p-4 border rounded-lg cursor-pointer transition-colors ${
                                  watchAllFields.payment_method === method.value
                                    ? 'border-primary-500 bg-primary-50'
                                    : 'border-gray-300 hover:border-gray-400'
                                }`}
                              >
                                <input
                                  type="radio"
                                  {...register('payment_method', { required: 'Payment method is required' })}
                                  value={method.value}
                                  className="sr-only"
                                />
                                <method.icon className="h-6 w-6 text-gray-400 mr-3" />
                                <span className="font-medium text-gray-900">{method.label}</span>
                              </label>
                            ))}
                          </div>

                          {/* Billing Address */}
                          <div className="mt-6">
                            <div className="flex items-center">
                              <input
                                type="checkbox"
                                {...register('billing_same_as_shipping')}
                                className="rounded border-gray-300 text-primary-600 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                              />
                              <label className="ml-2 text-sm text-gray-900">
                                Billing address same as shipping
                              </label>
                            </div>

                            {!watchBillingSame && (
                              <div className="mt-4 p-4 border rounded-lg bg-gray-50">
                                <h5 className="font-medium text-gray-900 mb-4">Billing Address</h5>
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                  <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">
                                      Full Name *
                                    </label>
                                    <input
                                      {...register('billing_name', { 
                                        required: !watchBillingSame ? 'Billing name is required' : false 
                                      })}
                                      className="input-field"
                                      placeholder="Enter billing name"
                                    />
                                  </div>
                                  {/* Add more billing address fields as needed */}
                                </div>
                              </div>
                            )}
                          </div>

                          {/* Order Notes */}
                          <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">
                              Order Notes (Optional)
                            </label>
                            <textarea
                              {...register('notes')}
                              rows={3}
                              className="input-field"
                              placeholder="Any special instructions or notes for the seller..."
                            />
                          </div>
                        </motion.div>
                      )}

                      {/* Step 3: Review & Confirm */}
                      {currentStep === 3 && (
                        <motion.div
                          initial={{ opacity: 0, x: 20 }}
                          animate={{ opacity: 1, x: 0 }}
                          exit={{ opacity: 0, x: -20 }}
                          className="space-y-6"
                        >
                          <div className="flex items-center gap-2 mb-4">
                            <ShieldCheckIcon className="h-5 w-5 text-primary-600" />
                            <h4 className="font-medium text-gray-900">Review Your Order</h4>
                          </div>

                          {/* Shipping Address Review */}
                          <div className="border rounded-lg p-4">
                            <div className="flex items-center gap-2 mb-2">
                              <MapPinIcon className="h-4 w-4 text-gray-500" />
                              <span className="font-medium text-gray-900">Shipping Address</span>
                            </div>
                            <div className="text-sm text-gray-600">
                              <p>{watchAllFields.shipping_name}</p>
                              <p>{watchAllFields.shipping_address_line_1}</p>
                              {watchAllFields.shipping_address_line_2 && (
                                <p>{watchAllFields.shipping_address_line_2}</p>
                              )}
                              <p>
                                {watchAllFields.shipping_city}, {watchAllFields.shipping_state} {watchAllFields.shipping_postal_code}
                              </p>
                              <p>{watchAllFields.shipping_country}</p>
                              <p>Phone: {watchAllFields.shipping_phone}</p>
                            </div>
                          </div>

                          {/* Payment Method Review */}
                          <div className="border rounded-lg p-4">
                            <div className="flex items-center gap-2 mb-2">
                              <CreditCardIcon className="h-4 w-4 text-gray-500" />
                              <span className="font-medium text-gray-900">Payment Method</span>
                            </div>
                            <div className="text-sm text-gray-600">
                              {PAYMENT_METHODS.find(m => m.value === watchAllFields.payment_method)?.label}
                            </div>
                          </div>

                          {/* Terms and Conditions */}
                          <div className="flex items-start">
                            <input
                              type="checkbox"
                              {...register('agree_to_terms', { 
                                required: 'You must agree to the terms and conditions' 
                              })}
                              className="mt-1 rounded border-gray-300 text-primary-600 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                            />
                            <label className="ml-2 text-sm text-gray-900">
                              I agree to the{' '}
                              <a href="#" className="text-primary-600 hover:text-primary-700">
                                Terms and Conditions
                              </a>{' '}
                              and{' '}
                              <a href="#" className="text-primary-600 hover:text-primary-700">
                                Privacy Policy
                              </a>
                            </label>
                          </div>
                          {errors.agree_to_terms && (
                            <p className="text-red-600 text-sm">{errors.agree_to_terms.message}</p>
                          )}
                        </motion.div>
                      )}
                    </div>

                    {/* Sidebar - Order Summary */}
                    <div className="w-80 bg-gray-50 p-6 border-l">
                      <h4 className="font-semibold text-gray-900 mb-4">Order Summary</h4>
                      
                      {/* Product */}
                      <div className="border rounded-lg p-3 bg-white mb-4">
                        <div className="flex gap-3">
                          <div className="w-16 h-16 bg-gray-200 rounded-lg flex-shrink-0">
                            {listing.images && listing.images[0] && (
                              <img
                                src={listing.images[0]}
                                alt={listing.title}
                                className="w-full h-full object-cover rounded-lg"
                              />
                            )}
                          </div>
                          <div className="flex-1 min-w-0">
                            <h5 className="font-medium text-gray-900 truncate">
                              {listing.title}
                            </h5>
                            <p className="text-sm text-gray-600">
                              Sold by {listing.seller_name}
                            </p>
                            <p className="font-semibold text-primary-600">
                              {formatPrice(listing.price, listing.currency)}
                            </p>
                          </div>
                        </div>
                      </div>

                      {/* Price Breakdown */}
                      <div className="space-y-2 text-sm">
                        <div className="flex justify-between">
                          <span className="text-gray-600">Item price</span>
                          <span className="text-gray-900">{formatPrice(itemPrice, listing.currency)}</span>
                        </div>
                        <div className="flex justify-between">
                          <span className="text-gray-600">Shipping</span>
                          <span className="text-gray-900">
                            {shippingCost > 0 ? formatPrice(shippingCost, listing.currency) : 'Free'}
                          </span>
                        </div>
                        {taxAmount > 0 && (
                          <div className="flex justify-between">
                            <span className="text-gray-600">Tax</span>
                            <span className="text-gray-900">{formatPrice(taxAmount, listing.currency)}</span>
                          </div>
                        )}
                        <hr />
                        <div className="flex justify-between font-semibold text-base">
                          <span className="text-gray-900">Total</span>
                          <span className="text-gray-900">{formatPrice(totalAmount, listing.currency)}</span>
                        </div>
                      </div>

                      {/* Security Notice */}
                      <div className="mt-6 p-3 bg-green-50 border border-green-200 rounded-lg">
                        <div className="flex items-center gap-2">
                          <ShieldCheckIcon className="h-4 w-4 text-green-600" />
                          <span className="text-sm font-medium text-green-800">Secure Transaction</span>
                        </div>
                        <p className="text-xs text-green-700 mt-1">
                          Your payment information is encrypted and secure. Funds are held in escrow until order completion.
                        </p>
                      </div>
                    </div>
                  </div>

                  {/* Footer */}
                  <div className="flex items-center justify-between p-6 border-t bg-gray-50">
                    <div className="flex gap-3">
                      {currentStep > 1 && (
                        <button
                          type="button"
                          onClick={prevStep}
                          className="btn-secondary"
                          disabled={isSubmitting}
                        >
                          Back
                        </button>
                      )}
                    </div>

                    <div className="flex gap-3">
                      <button
                        type="button"
                        onClick={handleClose}
                        className="btn-ghost"
                        disabled={isSubmitting}
                      >
                        Cancel
                      </button>
                      
                      {currentStep < 3 ? (
                        <button
                          type="button"
                          onClick={nextStep}
                          className="btn-primary"
                          disabled={isSubmitting}
                        >
                          Continue
                        </button>
                      ) : (
                        <button
                          type="submit"
                          className="btn-primary px-8"
                          disabled={isSubmitting || !isValid}
                        >
                          {isSubmitting ? (
                            <>
                              <div className="loading-spinner mr-2" />
                              Processing...
                            </>
                          ) : (
                            `Place Order • ${formatPrice(totalAmount, listing.currency)}`
                          )}
                        </button>
                      )}
                    </div>
                  </div>
                </form>
              </Dialog.Panel>
            </Transition.Child>
          </div>
        </div>
      </Dialog>
    </Transition>
  );
}
