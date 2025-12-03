'use client';

import React from 'react';
import Link from 'next/link';
import { motion } from 'framer-motion';
import { HomeIcon, MagnifyingGlassIcon } from '@heroicons/react/24/outline';
import Header from '@/components/Header';

export default function NotFound() {
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
            <h1 className="text-9xl font-bold text-primary-600">404</h1>
            <h2 className="text-3xl font-semibold text-gray-900 mt-4">Page Not Found</h2>
            <p className="text-gray-600 mt-4">
              Sorry, we couldn't find the page you're looking for. The page may have been moved or deleted.
            </p>
          </div>

          <div className="flex flex-col sm:flex-row gap-3 justify-center">
            <Link
              href="/"
              className="btn-primary inline-flex items-center justify-center gap-2"
            >
              <HomeIcon className="h-5 w-5" />
              Go Home
            </Link>
            <Link
              href="/marketplace"
              className="btn-secondary inline-flex items-center justify-center gap-2"
            >
              <MagnifyingGlassIcon className="h-5 w-5" />
              Browse Marketplace
            </Link>
          </div>

          <div className="mt-8 text-sm text-gray-600">
            <p>Looking for something specific?</p>
            <div className="mt-2 space-y-1">
              <Link href="/marketplace" className="block text-primary-600 hover:underline">
                Marketplace
              </Link>
              <Link href="/orders" className="block text-primary-600 hover:underline">
                My Orders
              </Link>
              <Link href="/profile" className="block text-primary-600 hover:underline">
                My Profile
              </Link>
            </div>
          </div>
        </motion.div>
      </div>
    </>
  );
}
