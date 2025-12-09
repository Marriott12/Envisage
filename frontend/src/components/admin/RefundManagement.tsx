import { useState, useEffect } from 'react';
import { 
  DollarSign, 
  Search, 
  Filter,
  CheckCircle,
  XCircle,
  Clock,
  AlertCircle,
  CreditCard,
  User,
  Calendar,
  FileText
} from 'lucide-react';

interface Refund {
  id: number;
  order_id: number;
  user_id: number;
  user_name: string;
  user_email: string;
  amount: number;
  reason: string;
  description: string;
  status: 'pending' | 'approved' | 'rejected' | 'processing' | 'completed';
  payment_method: string;
  requested_at: string;
  processed_at: string | null;
  admin_notes: string;
  attachments?: string[];
}

interface RefundManagementProps {
  apiToken: string;
}

export default function RefundManagement({ apiToken }: RefundManagementProps) {
  const [refunds, setRefunds] = useState<Refund[]>([]);
  const [loading, setLoading] = useState(true);
  const [searchTerm, setSearchTerm] = useState('');
  const [filterStatus, setFilterStatus] = useState<string>('all');
  const [selectedRefund, setSelectedRefund] = useState<Refund | null>(null);
  const [showModal, setShowModal] = useState(false);
  const [adminNotes, setAdminNotes] = useState('');
  const [processing, setProcessing] = useState(false);

  const API_BASE = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000/api';

  useEffect(() => {
    fetchRefunds();
  }, [filterStatus]);

  const fetchRefunds = async () => {
    setLoading(true);
    try {
      const params = new URLSearchParams();
      if (filterStatus !== 'all') params.append('status', filterStatus);

      const response = await fetch(`${API_BASE}/admin/refunds?${params.toString()}`, {
        headers: {
          'Authorization': `Bearer ${apiToken}`,
          'Accept': 'application/json',
        },
      });
      
      if (response.ok) {
        const data = await response.json();
        setRefunds(data.data || []);
      }
    } catch (error) {
      console.error('Error fetching refunds:', error);
    } finally {
      setLoading(false);
    }
  };

  const handleProcessRefund = async (refundId: number, newStatus: string) => {
    if (!adminNotes && newStatus === 'rejected') {
      alert('Please provide a reason for rejection');
      return;
    }

    setProcessing(true);
    try {
      const response = await fetch(`${API_BASE}/admin/refunds/${refundId}/process`, {
        method: 'PUT',
        headers: {
          'Authorization': `Bearer ${apiToken}`,
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        body: JSON.stringify({
          status: newStatus,
          admin_notes: adminNotes,
        }),
      });

      if (response.ok) {
        alert('Refund processed successfully!');
        setShowModal(false);
        setSelectedRefund(null);
        setAdminNotes('');
        fetchRefunds();
      }
    } catch (error) {
      console.error('Error processing refund:', error);
      alert('Failed to process refund');
    } finally {
      setProcessing(false);
    }
  };

  const filteredRefunds = refunds.filter(refund => {
    const matchesSearch = refund.user_name.toLowerCase().includes(searchTerm.toLowerCase()) ||
                         refund.user_email.toLowerCase().includes(searchTerm.toLowerCase()) ||
                         refund.order_id.toString().includes(searchTerm);
    return matchesSearch;
  });

  const getStatusBadge = (status: string) => {
    const configs = {
      pending: { color: 'yellow', icon: Clock, label: 'Pending' },
      approved: { color: 'blue', icon: CheckCircle, label: 'Approved' },
      processing: { color: 'purple', icon: Clock, label: 'Processing' },
      completed: { color: 'green', icon: CheckCircle, label: 'Completed' },
      rejected: { color: 'red', icon: XCircle, label: 'Rejected' },
    };
    
    const config = configs[status as keyof typeof configs] || configs.pending;
    const Icon = config.icon;
    
    return (
      <span className={`inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-${config.color}-100 text-${config.color}-800`}>
        <Icon className="w-3 h-3" />
        {config.label}
      </span>
    );
  };

  const openRefundModal = (refund: Refund) => {
    setSelectedRefund(refund);
    setAdminNotes(refund.admin_notes || '');
    setShowModal(true);
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center min-h-screen">
        <div className="text-center">
          <div className="animate-spin rounded-full h-16 w-16 border-b-2 border-purple-500 mx-auto mb-4"></div>
          <p className="text-gray-600">Loading refunds...</p>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gray-50 p-8">
      <div className="max-w-7xl mx-auto">
        {/* Header */}
        <div className="mb-8">
          <h1 className="text-3xl font-bold text-gray-900 flex items-center gap-3">
            <DollarSign className="w-8 h-8 text-purple-600" />
            Refund Management
          </h1>
          <p className="text-gray-600 mt-1">Process and manage customer refund requests</p>
        </div>

        {/* Stats Cards */}
        <div className="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
          <div className="bg-white rounded-lg shadow-sm p-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-gray-600">Total Refunds</p>
                <p className="text-2xl font-bold text-gray-900">{refunds.length}</p>
              </div>
              <DollarSign className="w-8 h-8 text-blue-500" />
            </div>
          </div>

          <div className="bg-white rounded-lg shadow-sm p-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-gray-600">Pending</p>
                <p className="text-2xl font-bold text-yellow-600">
                  {refunds.filter(r => r.status === 'pending').length}
                </p>
              </div>
              <Clock className="w-8 h-8 text-yellow-500" />
            </div>
          </div>

          <div className="bg-white rounded-lg shadow-sm p-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-gray-600">Completed</p>
                <p className="text-2xl font-bold text-green-600">
                  {refunds.filter(r => r.status === 'completed').length}
                </p>
              </div>
              <CheckCircle className="w-8 h-8 text-green-500" />
            </div>
          </div>

          <div className="bg-white rounded-lg shadow-sm p-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-gray-600">Total Amount</p>
                <p className="text-2xl font-bold text-purple-600">
                  ${refunds.reduce((sum, r) => sum + r.amount, 0).toFixed(2)}
                </p>
              </div>
              <CreditCard className="w-8 h-8 text-purple-500" />
            </div>
          </div>
        </div>

        {/* Filters */}
        <div className="bg-white rounded-lg shadow-sm p-6 mb-6">
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div className="relative">
              <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400" />
              <input
                type="text"
                placeholder="Search by customer name, email, or order ID..."
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
                className="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
              />
            </div>

            <select
              value={filterStatus}
              onChange={(e) => setFilterStatus(e.target.value)}
              className="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
            >
              <option value="all">All Status</option>
              <option value="pending">Pending</option>
              <option value="approved">Approved</option>
              <option value="processing">Processing</option>
              <option value="completed">Completed</option>
              <option value="rejected">Rejected</option>
            </select>
          </div>
        </div>

        {/* Refunds Table */}
        <div className="bg-white rounded-lg shadow-sm overflow-hidden">
          <div className="overflow-x-auto">
            <table className="w-full">
              <thead className="bg-gray-50 border-b border-gray-200">
                <tr>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order ID</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reason</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Requested</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
              </thead>
              <tbody className="bg-white divide-y divide-gray-200">
                {filteredRefunds.map((refund) => (
                  <tr key={refund.id} className="hover:bg-gray-50">
                    <td className="px-6 py-4">
                      <div className="text-sm font-medium text-gray-900">#{refund.order_id}</div>
                      <div className="text-xs text-gray-500">{refund.payment_method}</div>
                    </td>
                    <td className="px-6 py-4">
                      <div className="flex items-center">
                        <div className="flex-shrink-0 h-8 w-8">
                          <div className="h-8 w-8 rounded-full bg-purple-100 flex items-center justify-center">
                            <User className="w-4 h-4 text-purple-600" />
                          </div>
                        </div>
                        <div className="ml-3">
                          <div className="text-sm font-medium text-gray-900">{refund.user_name}</div>
                          <div className="text-xs text-gray-500">{refund.user_email}</div>
                        </div>
                      </div>
                    </td>
                    <td className="px-6 py-4">
                      <div className="text-sm font-semibold text-gray-900">${refund.amount.toFixed(2)}</div>
                    </td>
                    <td className="px-6 py-4">
                      <div className="text-sm text-gray-900">{refund.reason}</div>
                      {refund.description && (
                        <div className="text-xs text-gray-500 mt-1 line-clamp-2">{refund.description}</div>
                      )}
                    </td>
                    <td className="px-6 py-4">
                      {getStatusBadge(refund.status)}
                    </td>
                    <td className="px-6 py-4 text-sm text-gray-500">
                      <div className="flex items-center gap-1">
                        <Calendar className="w-4 h-4" />
                        {new Date(refund.requested_at).toLocaleDateString()}
                      </div>
                    </td>
                    <td className="px-6 py-4 text-sm font-medium">
                      <button
                        onClick={() => openRefundModal(refund)}
                        className="text-purple-600 hover:text-purple-900 flex items-center gap-1"
                      >
                        <FileText className="w-4 h-4" />
                        Review
                      </button>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>

          {filteredRefunds.length === 0 && (
            <div className="text-center py-12">
              <DollarSign className="w-12 h-12 text-gray-400 mx-auto mb-4" />
              <p className="text-gray-500">No refund requests found</p>
            </div>
          )}
        </div>

        {/* Refund Detail Modal */}
        {showModal && selectedRefund && (
          <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
            <div className="bg-white rounded-lg shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
              <div className="p-6">
                <div className="flex items-center justify-between mb-6">
                  <h3 className="text-2xl font-bold text-gray-900">Refund Request Details</h3>
                  {getStatusBadge(selectedRefund.status)}
                </div>

                <div className="space-y-4 mb-6">
                  <div className="grid grid-cols-2 gap-4">
                    <div>
                      <label className="text-sm text-gray-600">Order ID</label>
                      <p className="font-medium text-gray-900">#{selectedRefund.order_id}</p>
                    </div>
                    <div>
                      <label className="text-sm text-gray-600">Refund Amount</label>
                      <p className="font-medium text-gray-900 text-xl">${selectedRefund.amount.toFixed(2)}</p>
                    </div>
                  </div>

                  <div>
                    <label className="text-sm text-gray-600">Customer</label>
                    <p className="font-medium text-gray-900">{selectedRefund.user_name}</p>
                    <p className="text-sm text-gray-500">{selectedRefund.user_email}</p>
                  </div>

                  <div>
                    <label className="text-sm text-gray-600">Reason</label>
                    <p className="font-medium text-gray-900">{selectedRefund.reason}</p>
                  </div>

                  {selectedRefund.description && (
                    <div>
                      <label className="text-sm text-gray-600">Description</label>
                      <p className="text-gray-900">{selectedRefund.description}</p>
                    </div>
                  )}

                  <div>
                    <label className="text-sm text-gray-600">Payment Method</label>
                    <p className="font-medium text-gray-900">{selectedRefund.payment_method}</p>
                  </div>

                  <div>
                    <label className="text-sm text-gray-600">Requested Date</label>
                    <p className="font-medium text-gray-900">
                      {new Date(selectedRefund.requested_at).toLocaleString()}
                    </p>
                  </div>

                  {selectedRefund.admin_notes && (
                    <div className="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                      <label className="text-sm text-gray-600 flex items-center gap-1">
                        <AlertCircle className="w-4 h-4" />
                        Admin Notes
                      </label>
                      <p className="text-gray-900 mt-1">{selectedRefund.admin_notes}</p>
                    </div>
                  )}

                  {selectedRefund.status === 'pending' && (
                    <div>
                      <label className="block text-sm font-medium text-gray-700 mb-2">
                        Admin Notes / Decision Reason
                      </label>
                      <textarea
                        value={adminNotes}
                        onChange={(e) => setAdminNotes(e.target.value)}
                        rows={4}
                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                        placeholder="Enter notes or reason for your decision..."
                      />
                    </div>
                  )}
                </div>

                <div className="flex gap-3">
                  {selectedRefund.status === 'pending' && (
                    <>
                      <button
                        onClick={() => handleProcessRefund(selectedRefund.id, 'approved')}
                        disabled={processing}
                        className="flex-1 bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 disabled:bg-gray-300 transition-colors flex items-center justify-center gap-2"
                      >
                        <CheckCircle className="w-4 h-4" />
                        Approve Refund
                      </button>
                      <button
                        onClick={() => handleProcessRefund(selectedRefund.id, 'rejected')}
                        disabled={processing}
                        className="flex-1 bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 disabled:bg-gray-300 transition-colors flex items-center justify-center gap-2"
                      >
                        <XCircle className="w-4 h-4" />
                        Reject Refund
                      </button>
                    </>
                  )}
                  <button
                    onClick={() => {
                      setShowModal(false);
                      setSelectedRefund(null);
                      setAdminNotes('');
                    }}
                    className="flex-1 bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300 transition-colors"
                  >
                    Close
                  </button>
                </div>
              </div>
            </div>
          </div>
        )}
      </div>
    </div>
  );
}
