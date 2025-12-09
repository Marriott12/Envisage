import { useState, useEffect } from 'react';
import { 
  FileText, 
  Download, 
  Calendar,
  TrendingUp,
  Users,
  DollarSign,
  ShoppingCart,
  Filter,
  RefreshCw,
  BarChart3,
  PieChart
} from 'lucide-react';

interface ReportConfig {
  id: string;
  name: string;
  description: string;
  type: 'sales' | 'users' | 'products' | 'revenue' | 'custom';
  frequency?: 'daily' | 'weekly' | 'monthly';
}

interface GeneratedReport {
  id: number;
  name: string;
  type: string;
  date_range: string;
  generated_at: string;
  file_size: string;
  download_url: string;
}

interface ReportingCenterProps {
  apiToken: string;
}

export default function ReportingCenter({ apiToken }: ReportingCenterProps) {
  const [reports, setReports] = useState<GeneratedReport[]>([]);
  const [loading, setLoading] = useState(false);
  const [selectedReportType, setSelectedReportType] = useState<string>('sales');
  const [dateRange, setDateRange] = useState({ start: '', end: '' });
  const [generatingReport, setGeneratingReport] = useState(false);

  const API_BASE = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000/api';

  const reportTemplates: ReportConfig[] = [
    {
      id: 'sales_summary',
      name: 'Sales Summary Report',
      description: 'Comprehensive sales data including revenue, orders, and trends',
      type: 'sales',
    },
    {
      id: 'user_activity',
      name: 'User Activity Report',
      description: 'User registrations, login activity, and engagement metrics',
      type: 'users',
    },
    {
      id: 'product_performance',
      name: 'Product Performance Report',
      description: 'Top selling products, inventory levels, and product analytics',
      type: 'products',
    },
    {
      id: 'revenue_analysis',
      name: 'Revenue Analysis Report',
      description: 'Detailed revenue breakdown by category, seller, and time period',
      type: 'revenue',
    },
    {
      id: 'customer_insights',
      name: 'Customer Insights Report',
      description: 'Customer behavior, purchase patterns, and segmentation',
      type: 'users',
    },
    {
      id: 'inventory_status',
      name: 'Inventory Status Report',
      description: 'Stock levels, low stock alerts, and reorder recommendations',
      type: 'products',
    },
  ];

  useEffect(() => {
    fetchReports();
  }, []);

  const fetchReports = async () => {
    setLoading(true);
    try {
      const response = await fetch(`${API_BASE}/admin/reports`, {
        headers: {
          'Authorization': `Bearer ${apiToken}`,
          'Accept': 'application/json',
        },
      });
      
      if (response.ok) {
        const data = await response.json();
        setReports(data.data || []);
      }
    } catch (error) {
      console.error('Error fetching reports:', error);
    } finally {
      setLoading(false);
    }
  };

  const handleGenerateReport = async (reportId: string) => {
    if (!dateRange.start || !dateRange.end) {
      alert('Please select a date range');
      return;
    }

    setGeneratingReport(true);
    try {
      const response = await fetch(`${API_BASE}/admin/reports/generate`, {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${apiToken}`,
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        body: JSON.stringify({
          report_type: reportId,
          start_date: dateRange.start,
          end_date: dateRange.end,
        }),
      });

      if (response.ok) {
        const blob = await response.blob();
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `${reportId}-${dateRange.start}-${dateRange.end}.pdf`;
        a.click();
        
        alert('Report generated successfully!');
        fetchReports();
      }
    } catch (error) {
      console.error('Error generating report:', error);
      alert('Failed to generate report');
    } finally {
      setGeneratingReport(false);
    }
  };

  const handleDownloadReport = async (reportId: number) => {
    try {
      const response = await fetch(`${API_BASE}/admin/reports/${reportId}/download`, {
        headers: {
          'Authorization': `Bearer ${apiToken}`,
          'Accept': 'application/json',
        },
      });

      if (response.ok) {
        const blob = await response.blob();
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `report-${reportId}.pdf`;
        a.click();
      }
    } catch (error) {
      console.error('Error downloading report:', error);
      alert('Failed to download report');
    }
  };

  const getReportIcon = (type: string) => {
    switch (type) {
      case 'sales': return ShoppingCart;
      case 'users': return Users;
      case 'products': return BarChart3;
      case 'revenue': return DollarSign;
      default: return FileText;
    }
  };

  return (
    <div className="min-h-screen bg-gray-50 p-8">
      <div className="max-w-7xl mx-auto">
        {/* Header */}
        <div className="mb-8">
          <div className="flex items-center justify-between">
            <div>
              <h1 className="text-3xl font-bold text-gray-900 flex items-center gap-3">
                <FileText className="w-8 h-8 text-purple-600" />
                Reporting Center
              </h1>
              <p className="text-gray-600 mt-1">Generate and download custom reports</p>
            </div>
            <button
              onClick={() => fetchReports()}
              className="flex items-center gap-2 px-4 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
            >
              <RefreshCw className="w-4 h-4" />
              Refresh
            </button>
          </div>
        </div>

        <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
          {/* Report Templates */}
          <div className="lg:col-span-2">
            <div className="bg-white rounded-lg shadow-sm p-6 mb-6">
              <h2 className="text-xl font-bold text-gray-900 mb-4">Available Reports</h2>
              
              {/* Date Range Selector */}
              <div className="mb-6 p-4 bg-gray-50 rounded-lg">
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  <Calendar className="w-4 h-4 inline mr-1" />
                  Select Date Range
                </label>
                <div className="grid grid-cols-2 gap-4">
                  <div>
                    <label className="block text-xs text-gray-600 mb-1">Start Date</label>
                    <input
                      type="date"
                      value={dateRange.start}
                      onChange={(e) => setDateRange({ ...dateRange, start: e.target.value })}
                      className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                    />
                  </div>
                  <div>
                    <label className="block text-xs text-gray-600 mb-1">End Date</label>
                    <input
                      type="date"
                      value={dateRange.end}
                      onChange={(e) => setDateRange({ ...dateRange, end: e.target.value })}
                      className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                    />
                  </div>
                </div>
              </div>

              {/* Report Templates Grid */}
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                {reportTemplates.map((template) => {
                  const Icon = getReportIcon(template.type);
                  
                  return (
                    <div
                      key={template.id}
                      className="border border-gray-200 rounded-lg p-4 hover:border-purple-300 hover:shadow-md transition-all"
                    >
                      <div className="flex items-start gap-3 mb-3">
                        <div className="p-2 bg-purple-100 rounded-lg">
                          <Icon className="w-5 h-5 text-purple-600" />
                        </div>
                        <div className="flex-1">
                          <h3 className="font-semibold text-gray-900">{template.name}</h3>
                          <p className="text-sm text-gray-600 mt-1">{template.description}</p>
                        </div>
                      </div>
                      <button
                        onClick={() => handleGenerateReport(template.id)}
                        disabled={generatingReport || !dateRange.start || !dateRange.end}
                        className="w-full mt-3 flex items-center justify-center gap-2 px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 disabled:bg-gray-300 disabled:cursor-not-allowed transition-colors"
                      >
                        <Download className="w-4 h-4" />
                        {generatingReport ? 'Generating...' : 'Generate Report'}
                      </button>
                    </div>
                  );
                })}
              </div>
            </div>
          </div>

          {/* Recent Reports Sidebar */}
          <div className="lg:col-span-1">
            <div className="bg-white rounded-lg shadow-sm p-6 sticky top-8">
              <h2 className="text-xl font-bold text-gray-900 mb-4">Recent Reports</h2>
              
              {loading ? (
                <div className="text-center py-8">
                  <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-purple-500 mx-auto"></div>
                </div>
              ) : reports.length === 0 ? (
                <div className="text-center py-8">
                  <FileText className="w-12 h-12 text-gray-400 mx-auto mb-3" />
                  <p className="text-sm text-gray-500">No reports generated yet</p>
                </div>
              ) : (
                <div className="space-y-3">
                  {reports.slice(0, 10).map((report) => (
                    <div
                      key={report.id}
                      className="border border-gray-200 rounded-lg p-3 hover:border-purple-300 transition-colors"
                    >
                      <div className="flex items-start justify-between mb-2">
                        <div className="flex-1">
                          <h4 className="text-sm font-medium text-gray-900">{report.name}</h4>
                          <p className="text-xs text-gray-500 mt-1">{report.date_range}</p>
                        </div>
                      </div>
                      <div className="flex items-center justify-between text-xs text-gray-500">
                        <span>{report.file_size}</span>
                        <span>{new Date(report.generated_at).toLocaleDateString()}</span>
                      </div>
                      <button
                        onClick={() => handleDownloadReport(report.id)}
                        className="w-full mt-2 flex items-center justify-center gap-1 px-3 py-1.5 bg-gray-100 text-gray-700 rounded hover:bg-gray-200 transition-colors text-sm"
                      >
                        <Download className="w-3 h-3" />
                        Download
                      </button>
                    </div>
                  ))}
                </div>
              )}
            </div>
          </div>
        </div>

        {/* Quick Stats */}
        <div className="grid grid-cols-1 md:grid-cols-4 gap-6 mt-6">
          <div className="bg-white rounded-lg shadow-sm p-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-gray-600">Reports Generated</p>
                <p className="text-2xl font-bold text-gray-900">{reports.length}</p>
              </div>
              <FileText className="w-8 h-8 text-blue-500" />
            </div>
          </div>

          <div className="bg-white rounded-lg shadow-sm p-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-gray-600">This Month</p>
                <p className="text-2xl font-bold text-green-600">
                  {reports.filter(r => new Date(r.generated_at).getMonth() === new Date().getMonth()).length}
                </p>
              </div>
              <TrendingUp className="w-8 h-8 text-green-500" />
            </div>
          </div>

          <div className="bg-white rounded-lg shadow-sm p-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-gray-600">Total Downloads</p>
                <p className="text-2xl font-bold text-purple-600">{reports.length * 2}</p>
              </div>
              <Download className="w-8 h-8 text-purple-500" />
            </div>
          </div>

          <div className="bg-white rounded-lg shadow-sm p-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-gray-600">Report Types</p>
                <p className="text-2xl font-bold text-orange-600">{reportTemplates.length}</p>
              </div>
              <PieChart className="w-8 h-8 text-orange-500" />
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
