'use client';

import { useEffect, useState } from 'react';
import { useRouter } from 'next/navigation';
import LoyaltyTierEditor from '../../components/admin/LoyaltyTierEditor';

export default function LoyaltyTiersPage() {
  const router = useRouter();
  const [apiToken, setApiToken] = useState<string>('');
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const token = localStorage.getItem('token');
    const userRole = localStorage.getItem('userRole');

    if (!token || userRole !== 'admin') {
      router.push('/login?redirect=/admin/loyalty-tiers');
      return;
    }

    setApiToken(token);
    setLoading(false);
  }, [router]);

  if (loading) {
    return (
      <div className="flex items-center justify-center min-h-screen">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-purple-500"></div>
      </div>
    );
  }

  return <LoyaltyTierEditor apiToken={apiToken} />;
}
