'use client';

import { useEffect, useState } from 'react';
import { useRouter } from 'next/navigation';
import ReportingCenter from '../../components/admin/ReportingCenter';

export default function ReportsPage() {
  const router = useRouter();
  const [apiToken, setApiToken] = useState<string>('');
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const token = localStorage.getItem('token');
    const userRole = localStorage.getItem('userRole');

    if (!token || userRole !== 'admin') {
      router.push('/login?redirect=/admin/reports');
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

  return <ReportingCenter apiToken={apiToken} />;
}
