'use client';

import SellerAnalyticsDashboard from '@/components/seller/SellerAnalyticsDashboard';
import ProtectedRoute from '@/components/ProtectedRoute';
import Header from '@/components/Header';

export default function SellerAnalyticsPage() {
  return (
    <ProtectedRoute requiredRoles={['seller', 'admin']}>
      <Header />
      <SellerAnalyticsDashboard />
    </ProtectedRoute>
  );
}
