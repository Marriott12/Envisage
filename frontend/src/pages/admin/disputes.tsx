import { useEffect, useState } from 'react';
import { useRouter } from 'next/router';
import AdminDisputeManagement from '../../components/admin/AdminDisputeManagement';

export default function AdminDisputesPage() {
  const router = useRouter();
  const [token, setToken] = useState<string>('');
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const userToken = localStorage.getItem('authToken');
    const userRole = localStorage.getItem('userRole');

    if (!userToken || userRole !== 'admin') {
      router.push('/login?redirect=/admin/disputes');
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
          <p className="text-gray-600">Loading disputes...</p>
        </div>
      </div>
    );
  }

  return <AdminDisputeManagement apiToken={token} />;
}
