import { useEffect, useState } from 'react';
import { useRouter } from 'next/router';
import Link from 'next/link';
import {
  LayoutDashboard,
  AlertTriangle,
  Zap,
  CreditCard,
  Users,
  Package,
  Settings,
  LogOut,
} from 'lucide-react';

interface AdminLayoutProps {
  children: React.ReactNode;
  currentPage?: string;
}

export default function AdminLayout({ children, currentPage = 'dashboard' }: AdminLayoutProps) {
  const router = useRouter();
  const [userName, setUserName] = useState('Admin');

  useEffect(() => {
    const name = localStorage.getItem('userName');
    if (name) setUserName(name);
  }, []);

  const handleLogout = () => {
    localStorage.removeItem('authToken');
    localStorage.removeItem('userRole');
    localStorage.removeItem('userName');
    router.push('/login');
  };

  const menuItems = [
    { id: 'dashboard', label: 'Dashboard', icon: LayoutDashboard, href: '/admin/dashboard' },
    { id: 'disputes', label: 'Disputes', icon: AlertTriangle, href: '/admin/disputes' },
    { id: 'flash-sales', label: 'Flash Sales', icon: Zap, href: '/admin/flash-sales' },
    { id: 'subscriptions', label: 'Subscriptions', icon: CreditCard, href: '/admin/subscriptions' },
    { id: 'users', label: 'Users', icon: Users, href: '/admin/users' },
    { id: 'products', label: 'Products', icon: Package, href: '/admin/products' },
    { id: 'settings', label: 'Settings', icon: Settings, href: '/admin/settings' },
  ];

  return (
    <div className="min-h-screen bg-gray-50 flex">
      {/* Sidebar */}
      <aside className="w-64 bg-white border-r border-gray-200 fixed h-full">
        <div className="p-6 border-b border-gray-200">
          <h1 className="text-2xl font-bold bg-gradient-to-r from-purple-600 to-pink-600 bg-clip-text text-transparent">
            Envisage Admin
          </h1>
          <p className="text-sm text-gray-600 mt-1">Welcome, {userName}</p>
        </div>

        <nav className="p-4">
          {menuItems.map((item) => {
            const Icon = item.icon;
            const isActive = currentPage === item.id;
            return (
              <Link
                key={item.id}
                href={item.href}
                className={`flex items-center gap-3 px-4 py-3 rounded-lg mb-2 transition ${
                  isActive
                    ? 'bg-purple-100 text-purple-700 font-medium'
                    : 'text-gray-700 hover:bg-gray-100'
                }`}
              >
                <Icon size={20} />
                <span>{item.label}</span>
              </Link>
            );
          })}
        </nav>

        <div className="absolute bottom-0 w-64 p-4 border-t border-gray-200">
          <button
            onClick={handleLogout}
            className="flex items-center gap-3 px-4 py-3 rounded-lg w-full text-red-600 hover:bg-red-50 transition"
          >
            <LogOut size={20} />
            <span>Logout</span>
          </button>
        </div>
      </aside>

      {/* Main Content */}
      <main className="ml-64 flex-1">
        {children}
      </main>
    </div>
  );
}
