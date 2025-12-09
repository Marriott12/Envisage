import { useEffect, useState } from 'react';
import { useRouter } from 'next/router';
import AdminAnalyticsDashboard from '../../components/admin/AdminAnalyticsDashboard';

export default function AdminDashboardPage() {
  const router = useRouter();
  const [token, setToken] = useState<string>('');
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    // Get token from localStorage or cookie
    const userToken = localStorage.getItem('authToken');
    const userRole = localStorage.getItem('userRole');

    if (!userToken || userRole !== 'admin') {
      router.push('/login?redirect=/admin/dashboard');
      return;
    }

    setToken(userToken);
    setLoading(false);
  }, [router]);

  if (loading) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-gray-50">
        <div className="text-center">
          <div className="animate-spin rounded-full h-16 w-16 border-b-2 border-purple-500 mx-auto mb-4"></div>
          <p className="text-gray-600">Loading dashboard...</p>
        </div>
      </div>
    );
  }

  return <AdminAnalyticsDashboard apiToken={token} />;
}
