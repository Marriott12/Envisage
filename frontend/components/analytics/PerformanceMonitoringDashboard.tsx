'use client';

import { useState, useEffect } from 'react';
import {
  Activity,
  TrendingUp,
  Users,
  ShoppingCart,
  AlertTriangle,
  Zap,
  Eye,
  MousePointer,
  Clock,
  BarChart3,
} from 'lucide-react';

interface PerformanceMetric {
  name: string;
  value: number;
  unit: string;
  status: 'good' | 'warning' | 'critical';
}

interface AnalyticsSummary {
  pageViews: number;
  uniqueVisitors: number;
  conversionRate: number;
  averageOrderValue: number;
  errorRate: number;
  avgResponseTime: number;
}

interface PerformanceMonitoringDashboardProps {
  className?: string;
}

export function PerformanceMonitoringDashboard({ className = '' }: PerformanceMonitoringDashboardProps) {
  const [metrics, setMetrics] = useState<PerformanceMetric[]>([]);
  const [analytics, setAnalytics] = useState<AnalyticsSummary>({
    pageViews: 0,
    uniqueVisitors: 0,
    conversionRate: 0,
    averageOrderValue: 0,
    errorRate: 0,
    avgResponseTime: 0,
  });
  const [isLoading, setIsLoading] = useState(true);

  useEffect(() => {
    loadPerformanceMetrics();
    loadAnalyticsSummary();

    // Auto-refresh every 30 seconds
    const interval = setInterval(() => {
      loadPerformanceMetrics();
      loadAnalyticsSummary();
    }, 30000);

    return () => clearInterval(interval);
  }, []);

  const loadPerformanceMetrics = async () => {
    try {
      // Web Vitals metrics
      const perfEntries = performance.getEntriesByType('navigation');
      const navEntry = perfEntries[0] as PerformanceNavigationTiming;

      const newMetrics: PerformanceMetric[] = [
        {
          name: 'First Contentful Paint (FCP)',
          value: Math.round(performance.getEntriesByName('first-contentful-paint')[0]?.startTime || 0),
          unit: 'ms',
          status: getStatus(performance.getEntriesByName('first-contentful-paint')[0]?.startTime || 0, 1800, 3000),
        },
        {
          name: 'Largest Contentful Paint (LCP)',
          value: Math.round(navEntry?.loadEventEnd - navEntry?.fetchStart || 0),
          unit: 'ms',
          status: getStatus(navEntry?.loadEventEnd - navEntry?.fetchStart || 0, 2500, 4000),
        },
        {
          name: 'Time to Interactive (TTI)',
          value: Math.round(navEntry?.domInteractive - navEntry?.fetchStart || 0),
          unit: 'ms',
          status: getStatus(navEntry?.domInteractive - navEntry?.fetchStart || 0, 3800, 7300),
        },
        {
          name: 'Total Blocking Time (TBT)',
          value: Math.round(navEntry?.domContentLoadedEventEnd - navEntry?.domContentLoadedEventStart || 0),
          unit: 'ms',
          status: getStatus(navEntry?.domContentLoadedEventEnd - navEntry?.domContentLoadedEventStart || 0, 200, 600),
        },
      ];

      setMetrics(newMetrics);
    } catch (error) {
      console.error('Failed to load performance metrics:', error);
    } finally {
      setIsLoading(false);
    }
  };

  const loadAnalyticsSummary = async () => {
    try {
      const response = await fetch('/api/analytics/summary');
      if (response.ok) {
        const data = await response.json();
        setAnalytics(data);
      }
    } catch (error) {
      console.error('Failed to load analytics summary:', error);
    }
  };

  const getStatus = (value: number, goodThreshold: number, warningThreshold: number): 'good' | 'warning' | 'critical' => {
    if (value <= goodThreshold) return 'good';
    if (value <= warningThreshold) return 'warning';
    return 'critical';
  };

  const getStatusColor = (status: string) => {
    switch (status) {
      case 'good':
        return 'text-green-600 bg-green-50';
      case 'warning':
        return 'text-yellow-600 bg-yellow-50';
      case 'critical':
        return 'text-red-600 bg-red-50';
      default:
        return 'text-gray-600 bg-gray-50';
    }
  };

  return (
    <div className={`space-y-6 ${className}`}>
      <div className="flex items-center justify-between">
        <h2 className="text-2xl font-bold">Performance Monitoring</h2>
        <div className="flex items-center gap-2 text-sm text-gray-500">
          <Activity className="w-4 h-4 animate-pulse" />
          Live
        </div>
      </div>

      {/* Analytics Summary Cards */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <SummaryCard
          icon={<Eye className="w-6 h-6" />}
          title="Page Views"
          value={analytics.pageViews.toLocaleString()}
          trend="+12.5%"
          trendUp={true}
        />
        <SummaryCard
          icon={<Users className="w-6 h-6" />}
          title="Unique Visitors"
          value={analytics.uniqueVisitors.toLocaleString()}
          trend="+8.3%"
          trendUp={true}
        />
        <SummaryCard
          icon={<ShoppingCart className="w-6 h-6" />}
          title="Conversion Rate"
          value={`${analytics.conversionRate.toFixed(2)}%`}
          trend="-2.1%"
          trendUp={false}
        />
        <SummaryCard
          icon={<TrendingUp className="w-6 h-6" />}
          title="Avg Order Value"
          value={`$${analytics.averageOrderValue.toFixed(2)}`}
          trend="+15.7%"
          trendUp={true}
        />
        <SummaryCard
          icon={<AlertTriangle className="w-6 h-6" />}
          title="Error Rate"
          value={`${analytics.errorRate.toFixed(2)}%`}
          trend="-0.5%"
          trendUp={false}
          isError={analytics.errorRate > 5}
        />
        <SummaryCard
          icon={<Clock className="w-6 h-6" />}
          title="Avg Response Time"
          value={`${analytics.avgResponseTime}ms`}
          trend="-25ms"
          trendUp={false}
        />
      </div>

      {/* Core Web Vitals */}
      <div className="bg-white rounded-lg shadow p-6">
        <div className="flex items-center gap-2 mb-4">
          <Zap className="w-5 h-5 text-blue-600" />
          <h3 className="text-lg font-semibold">Core Web Vitals</h3>
        </div>

        {isLoading ? (
          <div className="space-y-3">
            {[1, 2, 3, 4].map((i) => (
              <div key={i} className="h-16 bg-gray-100 rounded animate-pulse" />
            ))}
          </div>
        ) : (
          <div className="space-y-4">
            {metrics.map((metric, index) => (
              <div key={index} className="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                <div className="flex-1">
                  <p className="font-medium text-gray-900">{metric.name}</p>
                  <div className="flex items-center gap-2 mt-1">
                    <span className="text-2xl font-bold">{metric.value}</span>
                    <span className="text-sm text-gray-500">{metric.unit}</span>
                  </div>
                </div>
                <div className={`px-3 py-1 rounded-full text-sm font-medium ${getStatusColor(metric.status)}`}>
                  {metric.status}
                </div>
              </div>
            ))}
          </div>
        )}

        <div className="mt-6 p-4 bg-blue-50 rounded-lg">
          <div className="flex items-start gap-3">
            <Activity className="w-5 h-5 text-blue-600 mt-0.5" />
            <div className="text-sm">
              <p className="font-medium text-blue-900 mb-1">Performance Score</p>
              <p className="text-blue-700">
                Your site is performing{' '}
                {metrics.every(m => m.status === 'good') ? 'excellently' : 
                 metrics.some(m => m.status === 'critical') ? 'poorly' : 'moderately'}.
                {' '}Continue monitoring to maintain optimal user experience.
              </p>
            </div>
          </div>
        </div>
      </div>

      {/* Real-time Activity */}
      <div className="bg-white rounded-lg shadow p-6">
        <div className="flex items-center gap-2 mb-4">
          <MousePointer className="w-5 h-5 text-purple-600" />
          <h3 className="text-lg font-semibold">Real-time Activity</h3>
        </div>
        
        <div className="space-y-3">
          <ActivityItem
            event="Product Viewed"
            user="Anonymous User"
            time="2 seconds ago"
            details="iPhone 15 Pro Max"
          />
          <ActivityItem
            event="Added to Cart"
            user="John D."
            time="15 seconds ago"
            details="MacBook Pro M3"
          />
          <ActivityItem
            event="Checkout Started"
            user="Sarah M."
            time="1 minute ago"
            details="$1,299.00"
          />
          <ActivityItem
            event="Order Completed"
            user="Mike R."
            time="3 minutes ago"
            details="Order #12345"
          />
        </div>
      </div>
    </div>
  );
}

function SummaryCard({
  icon,
  title,
  value,
  trend,
  trendUp,
  isError = false,
}: {
  icon: React.ReactNode;
  title: string;
  value: string;
  trend: string;
  trendUp: boolean;
  isError?: boolean;
}) {
  return (
    <div className="bg-white rounded-lg shadow p-6">
      <div className="flex items-center justify-between mb-3">
        <div className={`p-2 rounded-lg ${isError ? 'bg-red-100 text-red-600' : 'bg-blue-100 text-blue-600'}`}>
          {icon}
        </div>
        <div className={`flex items-center gap-1 text-sm ${trendUp ? 'text-green-600' : 'text-red-600'}`}>
          <TrendingUp className={`w-4 h-4 ${!trendUp && 'rotate-180'}`} />
          {trend}
        </div>
      </div>
      <p className="text-sm text-gray-600 mb-1">{title}</p>
      <p className="text-2xl font-bold">{value}</p>
    </div>
  );
}

function ActivityItem({
  event,
  user,
  time,
  details,
}: {
  event: string;
  user: string;
  time: string;
  details: string;
}) {
  return (
    <div className="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
      <div className="w-2 h-2 rounded-full bg-green-500 animate-pulse" />
      <div className="flex-1 min-w-0">
        <p className="font-medium text-sm">{event}</p>
        <p className="text-xs text-gray-500 truncate">
          {user} • {details}
        </p>
      </div>
      <span className="text-xs text-gray-400">{time}</span>
    </div>
  );
}

// Compact dashboard for sidebars
export function CompactPerformanceDashboard({ className = '' }: { className?: string }) {
  return (
    <div className={`bg-white rounded-lg shadow p-4 ${className}`}>
      <div className="flex items-center gap-2 mb-4">
        <BarChart3 className="w-5 h-5 text-blue-600" />
        <h3 className="font-semibold">Performance</h3>
      </div>
      
      <div className="space-y-3">
        <div className="flex justify-between text-sm">
          <span className="text-gray-600">Response Time</span>
          <span className="font-medium text-green-600">125ms</span>
        </div>
        <div className="flex justify-between text-sm">
          <span className="text-gray-600">Error Rate</span>
          <span className="font-medium text-green-600">0.2%</span>
        </div>
        <div className="flex justify-between text-sm">
          <span className="text-gray-600">Active Users</span>
          <span className="font-medium">1,247</span>
        </div>
      </div>
    </div>
  );
}
