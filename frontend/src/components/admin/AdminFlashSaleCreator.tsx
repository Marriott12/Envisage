import React, { useState, useEffect } from 'react';
import {
  Zap,
  Plus,
  Edit,
  Trash2,
  Calendar,
  Clock,
  DollarSign,
  TrendingUp,
  Package,
  Users,
  Save,
  X,
} from 'lucide-react';

interface FlashSale {
  id: number;
  name: string;
  description: string;
  start_time: string;
  end_time: string;
  discount_percentage: number;
  is_active: boolean;
  products: FlashSaleProduct[];
  created_at: string;
}

interface FlashSaleProduct {
  id: number;
  product_id: number;
  product: {
    id: number;
    name: string;
    price: number;
    images: string;
  };
  sale_price: number;
  quantity_available: number;
  quantity_sold: number;
  per_user_limit: number;
}

interface AdminFlashSaleCreatorProps {
  apiToken: string;
}

export default function AdminFlashSaleCreator({ apiToken }: AdminFlashSaleCreatorProps) {
  const [flashSales, setFlashSales] = useState<FlashSale[]>([]);
  const [selectedSale, setSelectedSale] = useState<FlashSale | null>(null);
  const [isCreating, setIsCreating] = useState(false);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);

  const [formData, setFormData] = useState({
    name: '',
    description: '',
    start_time: '',
    end_time: '',
    discount_percentage: 10,
    is_active: true,
  });

  const API_BASE = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000/api';

  useEffect(() => {
    fetchFlashSales();
  }, []);

  const fetchFlashSales = async () => {
    setLoading(true);
    try {
      const response = await fetch(`${API_BASE}/admin/flash-sales`, {
        headers: {
          'Authorization': `Bearer ${apiToken}`,
          'Accept': 'application/json',
        },
      });
      const data = await response.json();
      if (data.success) {
        setFlashSales(data.data);
      }
    } catch (error) {
      console.error('Failed to fetch flash sales:', error);
    } finally {
      setLoading(false);
    }
  };

  const createFlashSale = async () => {
    if (!formData.name || !formData.start_time || !formData.end_time) {
      alert('Please fill in all required fields');
      return;
    }

    setSaving(true);
    try {
      const response = await fetch(`${API_BASE}/flash-sales`, {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${apiToken}`,
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        body: JSON.stringify(formData),
      });
      const data = await response.json();
      if (data.success) {
        setFlashSales([data.data, ...flashSales]);
        resetForm();
        setIsCreating(false);
        alert('Flash sale created successfully!');
      }
    } catch (error) {
      console.error('Failed to create flash sale:', error);
      alert('Failed to create flash sale');
    } finally {
      setSaving(false);
    }
  };

  const endFlashSale = async (saleId: number) => {
    if (!confirm('Are you sure you want to end this flash sale?')) return;

    try {
      const response = await fetch(`${API_BASE}/flash-sales/${saleId}/end`, {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${apiToken}`,
          'Accept': 'application/json',
        },
      });
      const data = await response.json();
      if (data.success) {
        fetchFlashSales();
        alert('Flash sale ended successfully');
      }
    } catch (error) {
      console.error('Failed to end flash sale:', error);
      alert('Failed to end flash sale');
    }
  };

  const resetForm = () => {
    setFormData({
      name: '',
      description: '',
      start_time: '',
      end_time: '',
      discount_percentage: 10,
      is_active: true,
    });
  };

  const getStatusBadge = (sale: FlashSale) => {
    const now = new Date();
    const start = new Date(sale.start_time);
    const end = new Date(sale.end_time);

    if (now < start) {
      return <span className="bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-xs font-medium">Scheduled</span>;
    } else if (now >= start && now <= end && sale.is_active) {
      return <span className="bg-green-100 text-green-800 px-3 py-1 rounded-full text-xs font-medium">Active</span>;
    } else {
      return <span className="bg-gray-100 text-gray-800 px-3 py-1 rounded-full text-xs font-medium">Ended</span>;
    }
  };

  const stats = {
    total: flashSales.length,
    active: flashSales.filter(s => {
      const now = new Date();
      const start = new Date(s.start_time);
      const end = new Date(s.end_time);
      return now >= start && now <= end && s.is_active;
    }).length,
    scheduled: flashSales.filter(s => new Date(s.start_time) > new Date()).length,
    ended: flashSales.filter(s => new Date(s.end_time) < new Date() || !s.is_active).length,
  };

  return (
    <div className="min-h-screen bg-gray-50 p-6">
      <div className="max-w-7xl mx-auto">
        {/* Header */}
        <div className="flex items-center justify-between mb-8">
          <div>
            <h1 className="text-3xl font-bold text-gray-900 mb-2">Flash Sale Management</h1>
            <p className="text-gray-600">Create and manage limited-time sales events</p>
          </div>
          <button
            onClick={() => setIsCreating(true)}
            className="bg-gradient-to-r from-red-500 to-orange-500 text-white px-6 py-3 rounded-lg font-medium hover:opacity-90 flex items-center gap-2"
          >
            <Plus size={20} />
            Create Flash Sale
          </button>
        </div>

        {/* Stats */}
        <div className="grid md:grid-cols-4 gap-6 mb-8">
          <div className="bg-white rounded-lg p-6 shadow-sm border">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-gray-600">Total Sales</p>
                <p className="text-3xl font-bold text-gray-900">{stats.total}</p>
              </div>
              <Zap className="text-orange-400" size={32} />
            </div>
          </div>
          <div className="bg-white rounded-lg p-6 shadow-sm border">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-gray-600">Active Now</p>
                <p className="text-3xl font-bold text-green-600">{stats.active}</p>
              </div>
              <TrendingUp className="text-green-400" size={32} />
            </div>
          </div>
          <div className="bg-white rounded-lg p-6 shadow-sm border">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-gray-600">Scheduled</p>
                <p className="text-3xl font-bold text-blue-600">{stats.scheduled}</p>
              </div>
              <Calendar className="text-blue-400" size={32} />
            </div>
          </div>
          <div className="bg-white rounded-lg p-6 shadow-sm border">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-gray-600">Ended</p>
                <p className="text-3xl font-bold text-gray-600">{stats.ended}</p>
              </div>
              <Clock className="text-gray-400" size={32} />
            </div>
          </div>
        </div>

        {/* Create Form Modal */}
        {isCreating && (
          <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
            <div className="bg-white rounded-lg p-8 max-w-2xl w-full max-h-[90vh] overflow-y-auto">
              <div className="flex items-center justify-between mb-6">
                <h2 className="text-2xl font-bold">Create New Flash Sale</h2>
                <button
                  onClick={() => {
                    setIsCreating(false);
                    resetForm();
                  }}
                  className="text-gray-500 hover:text-gray-700"
                >
                  <X size={24} />
                </button>
              </div>

              <div className="space-y-4">
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">
                    Sale Name *
                  </label>
                  <input
                    type="text"
                    value={formData.name}
                    onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                    placeholder="e.g., Black Friday Sale"
                    className="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500"
                  />
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">
                    Description
                  </label>
                  <textarea
                    value={formData.description}
                    onChange={(e) => setFormData({ ...formData, description: e.target.value })}
                    placeholder="Describe your flash sale..."
                    rows={3}
                    className="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500"
                  />
                </div>

                <div className="grid md:grid-cols-2 gap-4">
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">
                      Start Time *
                    </label>
                    <input
                      type="datetime-local"
                      value={formData.start_time}
                      onChange={(e) => setFormData({ ...formData, start_time: e.target.value })}
                      className="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500"
                    />
                  </div>
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">
                      End Time *
                    </label>
                    <input
                      type="datetime-local"
                      value={formData.end_time}
                      onChange={(e) => setFormData({ ...formData, end_time: e.target.value })}
                      className="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500"
                    />
                  </div>
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">
                    Discount Percentage: {formData.discount_percentage}%
                  </label>
                  <input
                    type="range"
                    min="5"
                    max="90"
                    step="5"
                    value={formData.discount_percentage}
                    onChange={(e) => setFormData({ ...formData, discount_percentage: parseInt(e.target.value) })}
                    className="w-full"
                  />
                  <div className="flex justify-between text-xs text-gray-500 mt-1">
                    <span>5%</span>
                    <span>90%</span>
                  </div>
                </div>

                <div className="flex items-center gap-2">
                  <input
                    type="checkbox"
                    id="is_active"
                    checked={formData.is_active}
                    onChange={(e) => setFormData({ ...formData, is_active: e.target.checked })}
                    className="w-4 h-4 text-orange-600 focus:ring-orange-500 rounded"
                  />
                  <label htmlFor="is_active" className="text-sm text-gray-700">
                    Activate sale immediately (if start time has passed)
                  </label>
                </div>
              </div>

              <div className="flex gap-3 mt-6">
                <button
                  onClick={createFlashSale}
                  disabled={saving}
                  className="flex-1 bg-gradient-to-r from-red-500 to-orange-500 text-white px-6 py-3 rounded-lg font-medium hover:opacity-90 disabled:opacity-50 flex items-center justify-center gap-2"
                >
                  <Save size={20} />
                  {saving ? 'Creating...' : 'Create Sale'}
                </button>
                <button
                  onClick={() => {
                    setIsCreating(false);
                    resetForm();
                  }}
                  className="px-6 py-3 border rounded-lg font-medium hover:bg-gray-50"
                >
                  Cancel
                </button>
              </div>
            </div>
          </div>
        )}

        {/* Flash Sales List */}
        <div className="bg-white rounded-lg shadow-sm border">
          <div className="p-6 border-b">
            <h2 className="text-xl font-bold">All Flash Sales</h2>
          </div>

          {loading ? (
            <div className="text-center py-12">
              <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-orange-500 mx-auto"></div>
            </div>
          ) : flashSales.length === 0 ? (
            <div className="text-center py-12 text-gray-500">
              <Zap size={48} className="mx-auto mb-3 text-gray-300" />
              <p className="text-xl mb-2">No flash sales yet</p>
              <p className="text-sm">Create your first flash sale to boost sales!</p>
            </div>
          ) : (
            <div className="divide-y">
              {flashSales.map((sale) => (
                <div key={sale.id} className="p-6 hover:bg-gray-50 transition">
                  <div className="flex items-start justify-between mb-4">
                    <div className="flex-1">
                      <div className="flex items-center gap-3 mb-2">
                        <h3 className="text-xl font-bold">{sale.name}</h3>
                        {getStatusBadge(sale)}
                        <span className="bg-red-100 text-red-800 px-3 py-1 rounded-full text-xs font-bold">
                          {sale.discount_percentage}% OFF
                        </span>
                      </div>
                      {sale.description && (
                        <p className="text-gray-600 mb-3">{sale.description}</p>
                      )}
                      <div className="flex flex-wrap items-center gap-4 text-sm text-gray-600">
                        <div className="flex items-center gap-1">
                          <Calendar size={16} />
                          <span>Starts: {new Date(sale.start_time).toLocaleString()}</span>
                        </div>
                        <div className="flex items-center gap-1">
                          <Clock size={16} />
                          <span>Ends: {new Date(sale.end_time).toLocaleString()}</span>
                        </div>
                        <div className="flex items-center gap-1">
                          <Package size={16} />
                          <span>{sale.products?.length || 0} products</span>
                        </div>
                      </div>
                    </div>
                    <div className="flex gap-2">
                      <button
                        onClick={() => setSelectedSale(sale)}
                        className="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 flex items-center gap-2"
                      >
                        <Edit size={16} />
                        Manage
                      </button>
                      {sale.is_active && new Date(sale.end_time) > new Date() && (
                        <button
                          onClick={() => endFlashSale(sale.id)}
                          className="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700"
                        >
                          End Sale
                        </button>
                      )}
                    </div>
                  </div>

                  {/* Products Preview */}
                  {sale.products && sale.products.length > 0 && (
                    <div className="mt-4 pt-4 border-t">
                      <h4 className="text-sm font-semibold mb-3">Products in this sale:</h4>
                      <div className="grid md:grid-cols-3 gap-4">
                        {sale.products.slice(0, 3).map((sp) => (
                          <div key={sp.id} className="flex items-center gap-3 bg-gray-50 p-3 rounded-lg">
                            {sp.product.images && (
                              <img
                                src={JSON.parse(sp.product.images)[0]}
                                alt={sp.product.name}
                                className="w-12 h-12 object-cover rounded"
                              />
                            )}
                            <div className="flex-1 min-w-0">
                              <p className="text-sm font-medium truncate">{sp.product.name}</p>
                              <div className="flex items-center gap-2 text-xs">
                                <span className="line-through text-gray-500">${sp.product.price}</span>
                                <span className="text-red-600 font-bold">${sp.sale_price}</span>
                              </div>
                            </div>
                          </div>
                        ))}
                        {sale.products.length > 3 && (
                          <div className="flex items-center justify-center bg-gray-50 p-3 rounded-lg text-sm text-gray-600">
                            +{sale.products.length - 3} more
                          </div>
                        )}
                      </div>
                    </div>
                  )}
                </div>
              ))}
            </div>
          )}
        </div>
      </div>
    </div>
  );
}
