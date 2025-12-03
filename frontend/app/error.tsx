'use client';

import React, { useEffect } from 'react';
import Link from 'next/link';
import { motion } from 'framer-motion';
import { ExclamationTriangleIcon, HomeIcon, ArrowPathIcon } from '@heroicons/react/24/outline';
import Header from '@/components/Header';

export default function Error({
  error,
  reset,
}: {
  error: Error & { digest?: string };
  reset: () => void;
}) {
  useEffect(() => {
    // Log the error to an error reporting service
    console.error('Application error:', error);
  }, [error]);

  return (
    <>
      <Header />
      <div className="min-h-screen bg-gray-50 flex items-center justify-center px-4">
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          className="text-center max-w-md"
        >
          <div className="mb-8">
            <div className="mx-auto w-24 h-24 bg-red-50 rounded-full flex items-center justify-center mb-6">
              <ExclamationTriangleIcon className="h-12 w-12 text-red-600" />
            </div>
            <h1 className="text-3xl font-bold text-gray-900">Oops! Something went wrong</h1>
            <p className="text-gray-600 mt-4">
              We're sorry, but something unexpected happened. Please try again.
            </p>
            {error.message && (
              <div className="mt-4 p-4 bg-gray-100 rounded-lg">
                <p className="text-sm text-gray-700 font-mono">{error.message}</p>
              </div>
            )}
          </div>

          <div className="flex flex-col sm:flex-row gap-3 justify-center">
            <button
              onClick={reset}
              className="btn-primary inline-flex items-center justify-center gap-2"
            >
              <ArrowPathIcon className="h-5 w-5" />
              Try Again
            </button>
            <Link
              href="/"
              className="btn-secondary inline-flex items-center justify-center gap-2"
            >
              <HomeIcon className="h-5 w-5" />
              Go Home
            </Link>
          </div>

          <div className="mt-8 text-sm text-gray-600">
            <p>If this problem persists, please contact support.</p>
          </div>
        </motion.div>
      </div>
    </>
  );
}
