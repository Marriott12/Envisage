'use client';

import { useState, useEffect } from 'react';
import { invoiceApi } from '../../../lib/highPriorityApi';
import { 
  DocumentTextIcon,
  BanknotesIcon,
  ExclamationTriangleIcon,
  ClockIcon 
} from '@heroicons/react/24/outline';
import { useCurrency } from '../../contexts/CurrencyContext';

interface InvoiceStats {
  total_invoices: number;
  pending_amount: number;
  paid_amount: number;
  overdue_count: number;
  overdue_amount: number;
  this_month: {
    count: number;
    amount: number;
  };
}

export default function InvoiceStats() {
  const [stats, setStats] = useState<InvoiceStats | null>(null);
  const [loading, setLoading] = useState(true);
  const { formatPrice } = useCurrency();

  useEffect(() => {
    fetchStats();
  }, []);

  const fetchStats = async () => {
    try {
      setLoading(true);
      const response = await invoiceApi.getStats();
      if (response.success) {
        setStats(response.data);
      }
    } catch (error) {
      console.error('Failed to fetch invoice stats:', error);
    } finally {
      setLoading(false);
    }
  };

  if (loading) {
    return (
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        {[1, 2, 3, 4].map(i => (
          <div key={i} className="bg-white rounded-lg shadow-sm p-6 animate-pulse">
            <div className="h-4 bg-gray-200 rounded w-1/2 mb-4"></div>
            <div className="h-8 bg-gray-200 rounded w-3/4"></div>
          </div>
        ))}
      </div>
    );
  }

  if (!stats) return null;

  const statCards = [
    {
      label: 'Total Invoices',
      value: stats.total_invoices,
      icon: DocumentTextIcon,
      color: 'blue',
      format: 'number',
    },
    {
      label: 'Pending Amount',
      value: stats.pending_amount,
      icon: ClockIcon,
      color: 'yellow',
      format: 'currency',
    },
    {
      label: 'Paid Amount',
      value: stats.paid_amount,
      icon: BanknotesIcon,
      color: 'green',
      format: 'currency',
    },
    {
      label: 'Overdue',
      value: stats.overdue_amount,
      count: stats.overdue_count,
      icon: ExclamationTriangleIcon,
      color: 'red',
      format: 'currency',
    },
  ];

  const colorClasses: Record<string, { bg: string; text: string; iconBg: string }> = {
    blue: { bg: 'bg-blue-50', text: 'text-blue-600', iconBg: 'bg-blue-100' },
    yellow: { bg: 'bg-yellow-50', text: 'text-yellow-600', iconBg: 'bg-yellow-100' },
    green: { bg: 'bg-green-50', text: 'text-green-600', iconBg: 'bg-green-100' },
    red: { bg: 'bg-red-50', text: 'text-red-600', iconBg: 'bg-red-100' },
  };

  return (
    <div className="space-y-6">
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        {statCards.map((stat, index) => {
          const colors = colorClasses[stat.color];
          const Icon = stat.icon;

          return (
            <div key={index} className={`${colors.bg} rounded-lg shadow-sm p-6 border border-${stat.color}-100`}>
              <div className="flex items-center justify-between">
                <div className="flex-1">
                  <p className="text-sm font-medium text-gray-600 mb-1">{stat.label}</p>
                  <p className={`text-2xl font-bold ${colors.text}`}>
                    {stat.format === 'currency' 
                      ? formatPrice(stat.value as number)
                      : stat.value.toLocaleString()
                    }
                  </p>
                  {stat.count !== undefined && stat.count > 0 && (
                    <p className="text-xs text-gray-500 mt-1">
                      {stat.count} invoice{stat.count !== 1 ? 's' : ''}
                    </p>
                  )}
                </div>
                <div className={`${colors.iconBg} p-3 rounded-lg`}>
                  <Icon className={`h-6 w-6 ${colors.text}`} />
                </div>
              </div>
            </div>
          );
        })}
      </div>

      {/* This Month Summary */}
      <div className="bg-gradient-to-r from-primary-500 to-primary-600 rounded-lg shadow-lg p-6 text-white">
        <h3 className="text-lg font-semibold mb-4">This Month</h3>
        <div className="grid grid-cols-2 gap-6">
          <div>
            <p className="text-sm text-primary-100 mb-1">Invoices Generated</p>
            <p className="text-3xl font-bold">{stats.this_month.count}</p>
          </div>
          <div>
            <p className="text-sm text-primary-100 mb-1">Total Amount</p>
            <p className="text-3xl font-bold">{formatPrice(stats.this_month.amount)}</p>
          </div>
        </div>
      </div>
    </div>
  );
}
