'use client';

import React from 'react';
import { useRouter } from 'next/navigation';
import Link from 'next/link';
import { motion } from 'framer-motion';
import {
  CameraIcon,
  CurrencyDollarIcon,
  ShieldCheckIcon,
  TruckIcon,
} from '@heroicons/react/24/outline';
import Header from '@/components/Header';
import { useAuth } from '@/hooks/useAuth';

export default function SellPage() {
  const { isAuthenticated, user } = useAuth();
  const router = useRouter();

  const features = [
    {
      icon: CameraIcon,
      title: 'Easy Listing',
      description: 'Upload photos and describe your item in minutes'
    },
    {
      icon: ShieldCheckIcon,
      title: 'Secure Payments',
      description: 'Get paid securely with escrow protection'
    },
    {
      icon: TruckIcon,
      title: 'Tracked Shipping',
      description: 'Ship with confidence using tracked delivery'
    },
    {
      icon: CurrencyDollarIcon,
      title: 'Competitive Fees',
      description: 'Keep more of your earnings with low fees'
    }
  ];

  const handleStartSelling = () => {
    if (!isAuthenticated) {
      router.push('/register?redirect=/dashboard');
    } else {
      router.push('/dashboard');
    }
  };

  return (
    <>
      <Header />
      <div className="min-h-screen bg-gray-50">
        {/* Hero Section */}
        <div className="bg-gradient-to-br from-primary-600 to-primary-800 text-white">
          <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24">
            <motion.div
              initial={{ opacity: 0, y: 20 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ duration: 0.5 }}
              className="text-center"
            >
              <h1 className="text-4xl md:text-5xl font-bold mb-6">
                Start Selling Today
              </h1>
              <p className="text-xl mb-8 text-primary-100 max-w-2xl mx-auto">
                Turn your items into cash with Envisage. List for free and reach thousands of buyers.
              </p>
              <button
                onClick={handleStartSelling}
                className="bg-white text-primary-600 px-8 py-3 rounded-lg font-semibold hover:bg-primary-50 transition-colors text-lg"
              >
                {isAuthenticated ? 'Go to Dashboard' : 'Create Your Account'}
              </button>
            </motion.div>
          </div>
        </div>

        {/* Features Grid */}
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
          <div className="text-center mb-12">
            <h2 className="text-3xl font-bold text-gray-900 mb-4">
              Why Sell on Envisage?
            </h2>
            <p className="text-lg text-gray-600">
              Everything you need to sell successfully
            </p>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
            {features.map((feature, index) => (
              <motion.div
                key={feature.title}
                initial={{ opacity: 0, y: 20 }}
                animate={{ opacity: 1, y: 0 }}
                transition={{ duration: 0.5, delay: index * 0.1 }}
                className="bg-white p-6 rounded-xl shadow-sm hover:shadow-md transition-shadow"
              >
                <div className="w-12 h-12 bg-primary-100 rounded-lg flex items-center justify-center mb-4">
                  <feature.icon className="w-6 h-6 text-primary-600" />
                </div>
                <h3 className="text-lg font-semibold text-gray-900 mb-2">
                  {feature.title}
                </h3>
                <p className="text-gray-600">
                  {feature.description}
                </p>
              </motion.div>
            ))}
          </div>
        </div>

        {/* How It Works */}
        <div className="bg-white py-16">
          <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div className="text-center mb-12">
              <h2 className="text-3xl font-bold text-gray-900 mb-4">
                How It Works
              </h2>
              <p className="text-lg text-gray-600">
                Start selling in three simple steps
              </p>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
              <div className="text-center">
                <div className="w-16 h-16 bg-primary-600 text-white rounded-full flex items-center justify-center text-2xl font-bold mx-auto mb-4">
                  1
                </div>
                <h3 className="text-xl font-semibold text-gray-900 mb-2">
                  Create Your Listing
                </h3>
                <p className="text-gray-600">
                  Upload photos, set your price, and add a detailed description
                </p>
              </div>

              <div className="text-center">
                <div className="w-16 h-16 bg-primary-600 text-white rounded-full flex items-center justify-center text-2xl font-bold mx-auto mb-4">
                  2
                </div>
                <h3 className="text-xl font-semibold text-gray-900 mb-2">
                  Get Buyer Inquiries
                </h3>
                <p className="text-gray-600">
                  Interested buyers will contact you and make offers
                </p>
              </div>

              <div className="text-center">
                <div className="w-16 h-16 bg-primary-600 text-white rounded-full flex items-center justify-center text-2xl font-bold mx-auto mb-4">
                  3
                </div>
                <h3 className="text-xl font-semibold text-gray-900 mb-2">
                  Ship & Get Paid
                </h3>
                <p className="text-gray-600">
                  Ship your item and receive payment securely through escrow
                </p>
              </div>
            </div>
          </div>
        </div>

        {/* CTA Section */}
        <div className="bg-gray-900 text-white py-16">
          <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 className="text-3xl font-bold mb-4">
              Ready to Start Selling?
            </h2>
            <p className="text-xl text-gray-300 mb-8 max-w-2xl mx-auto">
              Join thousands of sellers already making money on Envisage
            </p>
            <button
              onClick={handleStartSelling}
              className="bg-primary-600 text-white px-8 py-3 rounded-lg font-semibold hover:bg-primary-700 transition-colors text-lg"
            >
              {isAuthenticated ? 'Create a Listing' : 'Sign Up Now'}
            </button>
            {!isAuthenticated && (
              <p className="mt-4 text-gray-400">
                Already have an account?{' '}
                <Link href="/login" className="text-primary-400 hover:text-primary-300">
                  Sign in
                </Link>
              </p>
            )}
          </div>
        </div>
      </div>
    </>
  );
}
