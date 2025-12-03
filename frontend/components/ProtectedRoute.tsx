'use client';

import React, { useEffect } from 'react';
import { useRouter } from 'next/navigation';
import { useAuth } from '@/hooks/useAuth';

interface ProtectedRouteProps {
  children: React.ReactNode;
  redirectTo?: string;
  fallback?: React.ReactNode;
  requiredRoles?: string[]; // e.g., ['admin', 'seller']
}

export default function ProtectedRoute({ 
  children, 
  redirectTo = '/login',
  fallback,
  requiredRoles = []
}: ProtectedRouteProps) {
  const { isAuthenticated, isLoading, user } = useAuth();
  const router = useRouter();

  useEffect(() => {
    if (!isLoading) {
      // Not authenticated - redirect to login
      if (!isAuthenticated) {
        const currentPath = window.location.pathname;
        router.push(`${redirectTo}?redirect=${encodeURIComponent(currentPath)}`);
        return;
      }

      // Check role requirements
      if (requiredRoles.length > 0 && user) {
        // Backend returns 'role' as a string, not 'roles' array
        const userRole = (user as any).role || '';
        const hasRequiredRole = requiredRoles.includes(userRole);
        
        if (!hasRequiredRole) {
          // User doesn't have required role - redirect to marketplace or home
          router.push('/marketplace');
          return;
        }
      }
    }
  }, [isAuthenticated, isLoading, user, router, redirectTo, requiredRoles]);

  // Show loading while checking auth
  if (isLoading) {
    return (
      fallback || (
        <div className="min-h-screen bg-gray-50 flex items-center justify-center">
          <div className="text-center">
            <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600 mx-auto"></div>
            <p className="mt-4 text-gray-600">Verifying access...</p>
          </div>
        </div>
      )
    );
  }

  // Not authenticated
  if (!isAuthenticated) {
    return null;
  }

  // Check role requirements
  if (requiredRoles.length > 0 && user) {
    // Backend returns 'role' as a string, not 'roles' array
    const userRole = (user as any).role || '';
    const hasRequiredRole = requiredRoles.includes(userRole);
    
    if (!hasRequiredRole) {
      return (
        <div className="min-h-screen bg-gray-50 flex items-center justify-center">
          <div className="text-center max-w-md mx-auto p-6">
            <div className="bg-yellow-50 border border-yellow-200 rounded-lg p-6">
              <h2 className="text-xl font-semibold text-yellow-800 mb-2">Access Denied</h2>
              <p className="text-yellow-700 mb-4">
                You don't have permission to access this page.
              </p>
              <button
                onClick={() => router.push('/marketplace')}
                className="btn-primary px-6 py-2"
              >
                Go to Marketplace
              </button>
            </div>
          </div>
        </div>
      );
    }
  }

  // Show children if authenticated and has required role
  return <>{children}</>;
}
