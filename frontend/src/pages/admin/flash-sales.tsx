import { useEffect, useState } from 'react';
import { useRouter } from 'next/router';
import AdminFlashSaleCreator from '../../components/admin/AdminFlashSaleCreator';

export default function AdminFlashSalesPage() {
  const router = useRouter();
  const [token, setToken] = useState<string>('');
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const userToken = localStorage.getItem('authToken');
    const userRole = localStorage.getItem('userRole');

    if (!userToken || userRole !== 'admin') {
      router.push('/login?redirect=/admin/flash-sales');
      return;
    }

    setToken(userToken);
    setLoading(false);
  }, [router]);

  if (loading) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-gray-50">
        <div className="text-center">
          <div className="animate-spin rounded-full h-16 w-16 border-b-2 border-red-500 mx-auto mb-4"></div>
          <p className="text-gray-600">Loading flash sales...</p>
        </div>
      </div>
    );
  }

  return <AdminFlashSaleCreator apiToken={token} />;
}
