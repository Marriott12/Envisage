import React, { useState, useEffect } from 'react';
import {
  Search,
  Filter,
  CheckCircle,
  XCircle,
  Clock,
  AlertTriangle,
  MessageSquare,
  Package,
  DollarSign,
  Eye,
  Send,
} from 'lucide-react';

interface Dispute {
  id: number;
  order: {
    id: number;
    order_number: string;
    total_amount: number;
  };
  user: {
    id: number;
    name: string;
    email: string;
  };
  type: 'return' | 'refund' | 'complaint' | 'quality_issue' | 'not_received';
  status: 'pending' | 'approved' | 'rejected' | 'resolved' | 'escalated';
  amount: number;
  reason: string;
  description: string;
  evidence: string | null;
  admin_response: string | null;
  created_at: string;
  updated_at: string;
}

interface AdminDisputeManagementProps {
  apiToken: string;
}

export default function AdminDisputeManagement({ apiToken }: AdminDisputeManagementProps) {
  const [disputes, setDisputes] = useState<Dispute[]>([]);
  const [filteredDisputes, setFilteredDisputes] = useState<Dispute[]>([]);
  const [selectedDispute, setSelectedDispute] = useState<Dispute | null>(null);
  const [searchQuery, setSearchQuery] = useState('');
  const [statusFilter, setStatusFilter] = useState<string>('all');
  const [typeFilter, setTypeFilter] = useState<string>('all');
  const [adminResponse, setAdminResponse] = useState('');
  const [loading, setLoading] = useState(true);
  const [updating, setUpdating] = useState(false);

  const API_BASE = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000/api';

  useEffect(() => {
    fetchDisputes();
  }, []);

  useEffect(() => {
    filterDisputes();
  }, [disputes, searchQuery, statusFilter, typeFilter]);

  const fetchDisputes = async () => {
    setLoading(true);
    try {
      const response = await fetch(`${API_BASE}/admin/disputes`, {
        headers: {
          'Authorization': `Bearer ${apiToken}`,
          'Accept': 'application/json',
        },
      });
      const data = await response.json();
      if (data.success) {
        setDisputes(data.data);
      }
    } catch (error) {
      console.error('Failed to fetch disputes:', error);
    } finally {
      setLoading(false);
    }
  };

  const filterDisputes = () => {
    let filtered = [...disputes];

    if (searchQuery) {
      filtered = filtered.filter(
        (d) =>
          d.order.order_number.toLowerCase().includes(searchQuery.toLowerCase()) ||
          d.user.name.toLowerCase().includes(searchQuery.toLowerCase()) ||
          d.user.email.toLowerCase().includes(searchQuery.toLowerCase())
      );
    }

    if (statusFilter !== 'all') {
      filtered = filtered.filter((d) => d.status === statusFilter);
    }

    if (typeFilter !== 'all') {
      filtered = filtered.filter((d) => d.type === typeFilter);
    }

    setFilteredDisputes(filtered);
  };

  const updateDisputeStatus = async (disputeId: number, newStatus: string, response?: string) => {
    setUpdating(true);
    try {
      const res = await fetch(`${API_BASE}/admin/disputes/${disputeId}/update`, {
        method: 'PUT',
        headers: {
          'Authorization': `Bearer ${apiToken}`,
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        body: JSON.stringify({
          status: newStatus,
          admin_response: response || adminResponse,
        }),
      });
      const data = await res.json();
      if (data.success) {
        setDisputes(disputes.map((d) => (d.id === disputeId ? data.data : d)));
        setSelectedDispute(data.data);
        setAdminResponse('');
        alert('Dispute updated successfully');
      }
    } catch (error) {
      console.error('Failed to update dispute:', error);
      alert('Failed to update dispute');
    } finally {
      setUpdating(false);
    }
  };

  const getStatusColor = (status: string) => {
    const colors = {
      pending: 'bg-yellow-100 text-yellow-800',
      approved: 'bg-green-100 text-green-800',
      rejected: 'bg-red-100 text-red-800',
      resolved: 'bg-blue-100 text-blue-800',
      escalated: 'bg-purple-100 text-purple-800',
    };
    return colors[status as keyof typeof colors] || 'bg-gray-100 text-gray-800';
  };

  const getTypeIcon = (type: string) => {
    const icons = {
      return: Package,
      refund: DollarSign,
      complaint: MessageSquare,
      quality_issue: AlertTriangle,
      not_received: Clock,
    };
    const Icon = icons[type as keyof typeof icons] || AlertTriangle;
    return <Icon size={20} />;
  };

  const stats = {
    total: disputes.length,
    pending: disputes.filter((d) => d.status === 'pending').length,
    approved: disputes.filter((d) => d.status === 'approved').length,
    resolved: disputes.filter((d) => d.status === 'resolved').length,
  };

  return (
    <div className="min-h-screen bg-gray-50 p-6">
      <div className="max-w-7xl mx-auto">
        {/* Header */}
        <div className="mb-8">
          <h1 className="text-3xl font-bold text-gray-900 mb-2">Dispute Management</h1>
          <p className="text-gray-600">Review and resolve customer disputes</p>
        </div>

        {/* Stats */}
        <div className="grid md:grid-cols-4 gap-6 mb-8">
          <div className="bg-white rounded-lg p-6 shadow-sm border">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-gray-600">Total Disputes</p>
                <p className="text-3xl font-bold text-gray-900">{stats.total}</p>
              </div>
              <AlertTriangle className="text-gray-400" size={32} />
            </div>
          </div>
          <div className="bg-white rounded-lg p-6 shadow-sm border">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-gray-600">Pending</p>
                <p className="text-3xl font-bold text-yellow-600">{stats.pending}</p>
              </div>
              <Clock className="text-yellow-400" size={32} />
            </div>
          </div>
          <div className="bg-white rounded-lg p-6 shadow-sm border">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-gray-600">Approved</p>
                <p className="text-3xl font-bold text-green-600">{stats.approved}</p>
              </div>
              <CheckCircle className="text-green-400" size={32} />
            </div>
          </div>
          <div className="bg-white rounded-lg p-6 shadow-sm border">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-gray-600">Resolved</p>
                <p className="text-3xl font-bold text-blue-600">{stats.resolved}</p>
              </div>
              <CheckCircle className="text-blue-400" size={32} />
            </div>
          </div>
        </div>

        <div className="grid lg:grid-cols-3 gap-6">
          {/* Disputes List */}
          <div className="lg:col-span-1 bg-white rounded-lg shadow-sm border">
            <div className="p-4 border-b">
              <div className="relative mb-4">
                <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400" size={20} />
                <input
                  type="text"
                  placeholder="Search disputes..."
                  value={searchQuery}
                  onChange={(e) => setSearchQuery(e.target.value)}
                  className="w-full pl-10 pr-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
                />
              </div>
              <div className="grid grid-cols-2 gap-2">
                <select
                  value={statusFilter}
                  onChange={(e) => setStatusFilter(e.target.value)}
                  className="px-3 py-2 border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-purple-500"
                >
                  <option value="all">All Status</option>
                  <option value="pending">Pending</option>
                  <option value="approved">Approved</option>
                  <option value="rejected">Rejected</option>
                  <option value="resolved">Resolved</option>
                  <option value="escalated">Escalated</option>
                </select>
                <select
                  value={typeFilter}
                  onChange={(e) => setTypeFilter(e.target.value)}
                  className="px-3 py-2 border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-purple-500"
                >
                  <option value="all">All Types</option>
                  <option value="return">Return</option>
                  <option value="refund">Refund</option>
                  <option value="complaint">Complaint</option>
                  <option value="quality_issue">Quality Issue</option>
                  <option value="not_received">Not Received</option>
                </select>
              </div>
            </div>

            <div className="overflow-y-auto max-h-[600px]">
              {loading ? (
                <div className="text-center py-12">
                  <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-purple-500 mx-auto"></div>
                </div>
              ) : filteredDisputes.length === 0 ? (
                <div className="text-center py-12 text-gray-500">
                  <AlertTriangle size={48} className="mx-auto mb-3 text-gray-300" />
                  <p>No disputes found</p>
                </div>
              ) : (
                filteredDisputes.map((dispute) => (
                  <div
                    key={dispute.id}
                    onClick={() => setSelectedDispute(dispute)}
                    className={`p-4 border-b cursor-pointer hover:bg-gray-50 transition ${
                      selectedDispute?.id === dispute.id ? 'bg-purple-50 border-l-4 border-l-purple-500' : ''
                    }`}
                  >
                    <div className="flex items-start justify-between mb-2">
                      <div className="flex items-center gap-2">
                        {getTypeIcon(dispute.type)}
                        <span className="font-semibold text-sm">#{dispute.order.order_number}</span>
                      </div>
                      <span className={`text-xs px-2 py-1 rounded-full ${getStatusColor(dispute.status)}`}>
                        {dispute.status}
                      </span>
                    </div>
                    <p className="text-sm text-gray-900 font-medium mb-1">{dispute.user.name}</p>
                    <p className="text-xs text-gray-500 mb-2">{dispute.type.replace('_', ' ').toUpperCase()}</p>
                    <p className="text-xs text-gray-600 truncate">{dispute.reason}</p>
                    <p className="text-xs text-gray-400 mt-2">
                      {new Date(dispute.created_at).toLocaleDateString()}
                    </p>
                  </div>
                ))
              )}
            </div>
          </div>

          {/* Dispute Details */}
          <div className="lg:col-span-2 bg-white rounded-lg shadow-sm border">
            {selectedDispute ? (
              <div className="p-6">
                <div className="flex items-start justify-between mb-6">
                  <div>
                    <h2 className="text-2xl font-bold mb-2">Dispute #{selectedDispute.id}</h2>
                    <p className="text-gray-600">Order: {selectedDispute.order.order_number}</p>
                  </div>
                  <span className={`px-4 py-2 rounded-full font-medium ${getStatusColor(selectedDispute.status)}`}>
                    {selectedDispute.status.toUpperCase()}
                  </span>
                </div>

                {/* Customer Info */}
                <div className="bg-gray-50 rounded-lg p-4 mb-6">
                  <h3 className="font-semibold mb-3">Customer Information</h3>
                  <div className="grid md:grid-cols-2 gap-4 text-sm">
                    <div>
                      <span className="text-gray-600">Name:</span>
                      <p className="font-medium">{selectedDispute.user.name}</p>
                    </div>
                    <div>
                      <span className="text-gray-600">Email:</span>
                      <p className="font-medium">{selectedDispute.user.email}</p>
                    </div>
                    <div>
                      <span className="text-gray-600">Order Amount:</span>
                      <p className="font-medium">${selectedDispute.order.total_amount.toFixed(2)}</p>
                    </div>
                    <div>
                      <span className="text-gray-600">Dispute Amount:</span>
                      <p className="font-medium text-red-600">${selectedDispute.amount.toFixed(2)}</p>
                    </div>
                  </div>
                </div>

                {/* Dispute Details */}
                <div className="mb-6">
                  <h3 className="font-semibold mb-3">Dispute Details</h3>
                  <div className="space-y-3">
                    <div>
                      <label className="text-sm text-gray-600">Type:</label>
                      <p className="font-medium">{selectedDispute.type.replace('_', ' ').toUpperCase()}</p>
                    </div>
                    <div>
                      <label className="text-sm text-gray-600">Reason:</label>
                      <p className="font-medium">{selectedDispute.reason}</p>
                    </div>
                    <div>
                      <label className="text-sm text-gray-600">Description:</label>
                      <p className="text-gray-900 bg-gray-50 p-3 rounded">{selectedDispute.description}</p>
                    </div>
                    {selectedDispute.evidence && (
                      <div>
                        <label className="text-sm text-gray-600">Evidence:</label>
                        <div className="flex flex-wrap gap-2 mt-2">
                          {JSON.parse(selectedDispute.evidence).map((file: any, idx: number) => (
                            <a
                              key={idx}
                              href={file.url}
                              target="_blank"
                              rel="noopener noreferrer"
                              className="flex items-center gap-2 bg-blue-50 text-blue-600 px-3 py-2 rounded-lg text-sm hover:bg-blue-100"
                            >
                              <Eye size={16} />
                              View Evidence {idx + 1}
                            </a>
                          ))}
                        </div>
                      </div>
                    )}
                  </div>
                </div>

                {/* Admin Response */}
                {selectedDispute.admin_response && (
                  <div className="mb-6 bg-blue-50 border-l-4 border-blue-500 p-4 rounded">
                    <h3 className="font-semibold mb-2">Admin Response</h3>
                    <p className="text-gray-900">{selectedDispute.admin_response}</p>
                  </div>
                )}

                {/* Action Section */}
                {selectedDispute.status === 'pending' && (
                  <div className="border-t pt-6">
                    <h3 className="font-semibold mb-4">Take Action</h3>
                    <textarea
                      value={adminResponse}
                      onChange={(e) => setAdminResponse(e.target.value)}
                      placeholder="Enter your response to the customer..."
                      rows={4}
                      className="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 mb-4"
                    />
                    <div className="flex gap-3">
                      <button
                        onClick={() => updateDisputeStatus(selectedDispute.id, 'approved')}
                        disabled={updating || !adminResponse.trim()}
                        className="flex-1 bg-green-600 text-white px-6 py-3 rounded-lg font-medium hover:bg-green-700 disabled:opacity-50 flex items-center justify-center gap-2"
                      >
                        <CheckCircle size={20} />
                        Approve
                      </button>
                      <button
                        onClick={() => updateDisputeStatus(selectedDispute.id, 'rejected')}
                        disabled={updating || !adminResponse.trim()}
                        className="flex-1 bg-red-600 text-white px-6 py-3 rounded-lg font-medium hover:bg-red-700 disabled:opacity-50 flex items-center justify-center gap-2"
                      >
                        <XCircle size={20} />
                        Reject
                      </button>
                      <button
                        onClick={() => updateDisputeStatus(selectedDispute.id, 'escalated')}
                        disabled={updating}
                        className="bg-purple-600 text-white px-6 py-3 rounded-lg font-medium hover:bg-purple-700 disabled:opacity-50"
                      >
                        Escalate
                      </button>
                    </div>
                  </div>
                )}

                {selectedDispute.status !== 'pending' && selectedDispute.status !== 'resolved' && (
                  <div className="border-t pt-6">
                    <button
                      onClick={() => updateDisputeStatus(selectedDispute.id, 'resolved', 'Dispute has been resolved.')}
                      disabled={updating}
                      className="w-full bg-blue-600 text-white px-6 py-3 rounded-lg font-medium hover:bg-blue-700 disabled:opacity-50 flex items-center justify-center gap-2"
                    >
                      <CheckCircle size={20} />
                      Mark as Resolved
                    </button>
                  </div>
                )}
              </div>
            ) : (
              <div className="flex items-center justify-center h-full text-gray-500 py-24">
                <div className="text-center">
                  <AlertTriangle size={64} className="mx-auto mb-4 text-gray-300" />
                  <p className="text-xl">Select a dispute to view details</p>
                </div>
              </div>
            )}
          </div>
        </div>
      </div>
    </div>
  );
}
