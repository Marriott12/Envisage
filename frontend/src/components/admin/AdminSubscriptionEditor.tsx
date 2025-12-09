import React, { useState, useEffect } from 'react';
import {
  Crown,
  Zap,
  Star,
  Plus,
  Edit,
  Trash2,
  Check,
  X,
  Save,
  DollarSign,
  Package,
  TrendingUp,
} from 'lucide-react';

interface SubscriptionPlan {
  id: number;
  name: string;
  slug: string;
  description: string;
  monthly_price: number;
  yearly_price: number;
  features: string[];
  max_products: number | null;
  max_featured_products: number;
  commission_rate: number;
  is_popular: boolean;
  is_active: boolean;
  created_at: string;
}

interface AdminSubscriptionEditorProps {
  apiToken: string;
}

export default function AdminSubscriptionEditor({ apiToken }: AdminSubscriptionEditorProps) {
  const [plans, setPlans] = useState<SubscriptionPlan[]>([]);
  const [editingPlan, setEditingPlan] = useState<SubscriptionPlan | null>(null);
  const [isCreating, setIsCreating] = useState(false);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);

  const [formData, setFormData] = useState<Partial<SubscriptionPlan>>({
    name: '',
    slug: '',
    description: '',
    monthly_price: 0,
    yearly_price: 0,
    features: [],
    max_products: null,
    max_featured_products: 0,
    commission_rate: 10,
    is_popular: false,
    is_active: true,
  });

  const [newFeature, setNewFeature] = useState('');

  const API_BASE = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000/api';

  useEffect(() => {
    fetchPlans();
  }, []);

  const fetchPlans = async () => {
    setLoading(true);
    try {
      const response = await fetch(`${API_BASE}/admin/subscription-plans`, {
        headers: {
          'Authorization': `Bearer ${apiToken}`,
          'Accept': 'application/json',
        },
      });
      const data = await response.json();
      if (data.success) {
        setPlans(data.data);
      }
    } catch (error) {
      console.error('Failed to fetch plans:', error);
    } finally {
      setLoading(false);
    }
  };

  const savePlan = async () => {
    if (!formData.name || !formData.slug || formData.monthly_price === undefined) {
      alert('Please fill in all required fields');
      return;
    }

    setSaving(true);
    try {
      const url = editingPlan
        ? `${API_BASE}/admin/subscription-plans/${editingPlan.id}`
        : `${API_BASE}/admin/subscription-plans`;
      
      const method = editingPlan ? 'PUT' : 'POST';

      const response = await fetch(url, {
        method,
        headers: {
          'Authorization': `Bearer ${apiToken}`,
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        body: JSON.stringify(formData),
      });
      const data = await response.json();
      
      if (data.success) {
        fetchPlans();
        resetForm();
        alert(editingPlan ? 'Plan updated successfully!' : 'Plan created successfully!');
      }
    } catch (error) {
      console.error('Failed to save plan:', error);
      alert('Failed to save plan');
    } finally {
      setSaving(false);
    }
  };

  const deletePlan = async (planId: number) => {
    if (!confirm('Are you sure you want to delete this plan? This cannot be undone.')) return;

    try {
      const response = await fetch(`${API_BASE}/admin/subscription-plans/${planId}`, {
        method: 'DELETE',
        headers: {
          'Authorization': `Bearer ${apiToken}`,
          'Accept': 'application/json',
        },
      });
      const data = await response.json();
      if (data.success) {
        fetchPlans();
        alert('Plan deleted successfully');
      }
    } catch (error) {
      console.error('Failed to delete plan:', error);
      alert('Failed to delete plan');
    }
  };

  const resetForm = () => {
    setFormData({
      name: '',
      slug: '',
      description: '',
      monthly_price: 0,
      yearly_price: 0,
      features: [],
      max_products: null,
      max_featured_products: 0,
      commission_rate: 10,
      is_popular: false,
      is_active: true,
    });
    setEditingPlan(null);
    setIsCreating(false);
    setNewFeature('');
  };

  const startEdit = (plan: SubscriptionPlan) => {
    setEditingPlan(plan);
    setFormData(plan);
    setIsCreating(true);
  };

  const addFeature = () => {
    if (!newFeature.trim()) return;
    setFormData({
      ...formData,
      features: [...(formData.features || []), newFeature.trim()],
    });
    setNewFeature('');
  };

  const removeFeature = (index: number) => {
    setFormData({
      ...formData,
      features: formData.features?.filter((_, i) => i !== index) || [],
    });
  };

  const getPlanIcon = (planName: string) => {
    const name = planName.toLowerCase();
    if (name.includes('basic') || name.includes('starter')) return Crown;
    if (name.includes('pro') || name.includes('premium')) return Zap;
    if (name.includes('enterprise') || name.includes('business')) return Star;
    return Package;
  };

  const stats = {
    total: plans.length,
    active: plans.filter(p => p.is_active).length,
    popular: plans.filter(p => p.is_popular).length,
  };

  return (
    <div className="min-h-screen bg-gray-50 p-6">
      <div className="max-w-7xl mx-auto">
        {/* Header */}
        <div className="flex items-center justify-between mb-8">
          <div>
            <h1 className="text-3xl font-bold text-gray-900 mb-2">Subscription Plans</h1>
            <p className="text-gray-600">Manage pricing tiers and features</p>
          </div>
          <button
            onClick={() => setIsCreating(true)}
            className="bg-purple-600 text-white px-6 py-3 rounded-lg font-medium hover:bg-purple-700 flex items-center gap-2"
          >
            <Plus size={20} />
            Create Plan
          </button>
        </div>

        {/* Stats */}
        <div className="grid md:grid-cols-3 gap-6 mb-8">
          <div className="bg-white rounded-lg p-6 shadow-sm border">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-gray-600">Total Plans</p>
                <p className="text-3xl font-bold text-gray-900">{stats.total}</p>
              </div>
              <Package className="text-purple-400" size={32} />
            </div>
          </div>
          <div className="bg-white rounded-lg p-6 shadow-sm border">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-gray-600">Active Plans</p>
                <p className="text-3xl font-bold text-green-600">{stats.active}</p>
              </div>
              <Check className="text-green-400" size={32} />
            </div>
          </div>
          <div className="bg-white rounded-lg p-6 shadow-sm border">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-gray-600">Popular Plans</p>
                <p className="text-3xl font-bold text-yellow-600">{stats.popular}</p>
              </div>
              <Star className="text-yellow-400" size={32} />
            </div>
          </div>
        </div>

        {/* Create/Edit Form Modal */}
        {isCreating && (
          <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
            <div className="bg-white rounded-lg p-8 max-w-3xl w-full max-h-[90vh] overflow-y-auto">
              <div className="flex items-center justify-between mb-6">
                <h2 className="text-2xl font-bold">
                  {editingPlan ? 'Edit Plan' : 'Create New Plan'}
                </h2>
                <button onClick={resetForm} className="text-gray-500 hover:text-gray-700">
                  <X size={24} />
                </button>
              </div>

              <div className="space-y-6">
                {/* Basic Info */}
                <div className="grid md:grid-cols-2 gap-4">
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">
                      Plan Name *
                    </label>
                    <input
                      type="text"
                      value={formData.name}
                      onChange={(e) => {
                        const name = e.target.value;
                        setFormData({
                          ...formData,
                          name,
                          slug: name.toLowerCase().replace(/\s+/g, '-').replace(/[^a-z0-9-]/g, ''),
                        });
                      }}
                      placeholder="e.g., Professional"
                      className="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
                    />
                  </div>
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">
                      Slug (auto-generated)
                    </label>
                    <input
                      type="text"
                      value={formData.slug}
                      readOnly
                      className="w-full px-4 py-2 border rounded-lg bg-gray-50 text-gray-600"
                    />
                  </div>
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">
                    Description
                  </label>
                  <textarea
                    value={formData.description}
                    onChange={(e) => setFormData({ ...formData, description: e.target.value })}
                    placeholder="Describe the plan benefits..."
                    rows={3}
                    className="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
                  />
                </div>

                {/* Pricing */}
                <div className="grid md:grid-cols-2 gap-4">
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">
                      Monthly Price ($) *
                    </label>
                    <div className="relative">
                      <DollarSign className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400" size={20} />
                      <input
                        type="number"
                        min="0"
                        step="0.01"
                        value={formData.monthly_price}
                        onChange={(e) => setFormData({ ...formData, monthly_price: parseFloat(e.target.value) })}
                        className="w-full pl-10 pr-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
                      />
                    </div>
                  </div>
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">
                      Yearly Price ($)
                    </label>
                    <div className="relative">
                      <DollarSign className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400" size={20} />
                      <input
                        type="number"
                        min="0"
                        step="0.01"
                        value={formData.yearly_price}
                        onChange={(e) => setFormData({ ...formData, yearly_price: parseFloat(e.target.value) })}
                        className="w-full pl-10 pr-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
                      />
                    </div>
                    {formData.monthly_price && formData.yearly_price && (
                      <p className="text-xs text-green-600 mt-1">
                        Saves ${((formData.monthly_price * 12) - formData.yearly_price).toFixed(2)}/year
                      </p>
                    )}
                  </div>
                </div>

                {/* Limits */}
                <div className="grid md:grid-cols-3 gap-4">
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">
                      Max Products
                    </label>
                    <input
                      type="number"
                      min="0"
                      value={formData.max_products || ''}
                      onChange={(e) => setFormData({ ...formData, max_products: e.target.value ? parseInt(e.target.value) : null })}
                      placeholder="Unlimited"
                      className="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
                    />
                    <p className="text-xs text-gray-500 mt-1">Leave empty for unlimited</p>
                  </div>
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">
                      Featured Slots
                    </label>
                    <input
                      type="number"
                      min="0"
                      value={formData.max_featured_products}
                      onChange={(e) => setFormData({ ...formData, max_featured_products: parseInt(e.target.value) })}
                      className="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
                    />
                  </div>
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">
                      Commission Rate (%)
                    </label>
                    <input
                      type="number"
                      min="0"
                      max="100"
                      step="0.1"
                      value={formData.commission_rate}
                      onChange={(e) => setFormData({ ...formData, commission_rate: parseFloat(e.target.value) })}
                      className="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
                    />
                  </div>
                </div>

                {/* Features */}
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-2">
                    Features
                  </label>
                  <div className="space-y-2 mb-3">
                    {formData.features?.map((feature, index) => (
                      <div key={index} className="flex items-center gap-2 bg-purple-50 px-4 py-2 rounded-lg">
                        <Check className="text-purple-600" size={16} />
                        <span className="flex-1 text-sm">{feature}</span>
                        <button
                          onClick={() => removeFeature(index)}
                          className="text-red-600 hover:text-red-700"
                        >
                          <Trash2 size={16} />
                        </button>
                      </div>
                    ))}
                  </div>
                  <div className="flex gap-2">
                    <input
                      type="text"
                      value={newFeature}
                      onChange={(e) => setNewFeature(e.target.value)}
                      onKeyPress={(e) => e.key === 'Enter' && addFeature()}
                      placeholder="Add a feature..."
                      className="flex-1 px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
                    />
                    <button
                      onClick={addFeature}
                      className="bg-purple-100 text-purple-600 px-4 py-2 rounded-lg hover:bg-purple-200"
                    >
                      <Plus size={20} />
                    </button>
                  </div>
                </div>

                {/* Toggles */}
                <div className="space-y-3">
                  <div className="flex items-center gap-2">
                    <input
                      type="checkbox"
                      id="is_popular"
                      checked={formData.is_popular}
                      onChange={(e) => setFormData({ ...formData, is_popular: e.target.checked })}
                      className="w-4 h-4 text-purple-600 focus:ring-purple-500 rounded"
                    />
                    <label htmlFor="is_popular" className="text-sm text-gray-700">
                      Mark as popular (add "Most Popular" badge)
                    </label>
                  </div>
                  <div className="flex items-center gap-2">
                    <input
                      type="checkbox"
                      id="is_active"
                      checked={formData.is_active}
                      onChange={(e) => setFormData({ ...formData, is_active: e.target.checked })}
                      className="w-4 h-4 text-purple-600 focus:ring-purple-500 rounded"
                    />
                    <label htmlFor="is_active" className="text-sm text-gray-700">
                      Active (visible to customers)
                    </label>
                  </div>
                </div>
              </div>

              <div className="flex gap-3 mt-6">
                <button
                  onClick={savePlan}
                  disabled={saving}
                  className="flex-1 bg-purple-600 text-white px-6 py-3 rounded-lg font-medium hover:bg-purple-700 disabled:opacity-50 flex items-center justify-center gap-2"
                >
                  <Save size={20} />
                  {saving ? 'Saving...' : editingPlan ? 'Update Plan' : 'Create Plan'}
                </button>
                <button
                  onClick={resetForm}
                  className="px-6 py-3 border rounded-lg font-medium hover:bg-gray-50"
                >
                  Cancel
                </button>
              </div>
            </div>
          </div>
        )}

        {/* Plans Grid */}
        <div className="grid md:grid-cols-3 gap-6">
          {loading ? (
            <div className="col-span-3 text-center py-12">
              <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-purple-500 mx-auto"></div>
            </div>
          ) : plans.length === 0 ? (
            <div className="col-span-3 text-center py-12 text-gray-500">
              <Package size={48} className="mx-auto mb-3 text-gray-300" />
              <p className="text-xl mb-2">No subscription plans yet</p>
              <p className="text-sm">Create your first plan to start monetizing!</p>
            </div>
          ) : (
            plans.map((plan) => {
              const Icon = getPlanIcon(plan.name);
              return (
                <div
                  key={plan.id}
                  className={`bg-white rounded-lg shadow-sm border-2 p-6 ${
                    plan.is_popular ? 'border-purple-500 relative' : 'border-gray-200'
                  }`}
                >
                  {plan.is_popular && (
                    <div className="absolute -top-3 left-1/2 transform -translate-x-1/2">
                      <span className="bg-gradient-to-r from-purple-600 to-pink-600 text-white px-4 py-1 rounded-full text-xs font-bold">
                        MOST POPULAR
                      </span>
                    </div>
                  )}

                  <div className="text-center mb-6">
                    <div className={`inline-flex p-3 rounded-full mb-3 ${
                      plan.is_popular ? 'bg-purple-100' : 'bg-gray-100'
                    }`}>
                      <Icon className={plan.is_popular ? 'text-purple-600' : 'text-gray-600'} size={32} />
                    </div>
                    <h3 className="text-2xl font-bold mb-2">{plan.name}</h3>
                    <p className="text-gray-600 text-sm mb-4">{plan.description}</p>
                    <div className="flex items-baseline justify-center gap-1">
                      <span className="text-4xl font-bold">${plan.monthly_price}</span>
                      <span className="text-gray-600">/month</span>
                    </div>
                    {plan.yearly_price > 0 && (
                      <p className="text-sm text-green-600 mt-2">
                        ${plan.yearly_price}/year (save ${((plan.monthly_price * 12) - plan.yearly_price).toFixed(2)})
                      </p>
                    )}
                  </div>

                  <div className="space-y-3 mb-6">
                    <div className="text-sm">
                      <span className="text-gray-600">Products: </span>
                      <span className="font-semibold">{plan.max_products || 'Unlimited'}</span>
                    </div>
                    <div className="text-sm">
                      <span className="text-gray-600">Featured Slots: </span>
                      <span className="font-semibold">{plan.max_featured_products}</span>
                    </div>
                    <div className="text-sm">
                      <span className="text-gray-600">Commission: </span>
                      <span className="font-semibold">{plan.commission_rate}%</span>
                    </div>
                  </div>

                  <div className="space-y-2 mb-6 border-t pt-4">
                    {plan.features.map((feature, index) => (
                      <div key={index} className="flex items-start gap-2 text-sm">
                        <Check className="text-green-500 mt-0.5 flex-shrink-0" size={16} />
                        <span className="text-gray-700">{feature}</span>
                      </div>
                    ))}
                  </div>

                  <div className="flex gap-2">
                    <button
                      onClick={() => startEdit(plan)}
                      className="flex-1 bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 flex items-center justify-center gap-2"
                    >
                      <Edit size={16} />
                      Edit
                    </button>
                    <button
                      onClick={() => deletePlan(plan.id)}
                      className="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700"
                    >
                      <Trash2 size={16} />
                    </button>
                  </div>

                  {!plan.is_active && (
                    <div className="mt-3 text-center">
                      <span className="text-xs text-red-600 bg-red-50 px-3 py-1 rounded-full">
                        Inactive
                      </span>
                    </div>
                  )}
                </div>
              );
            })
          )}
        </div>
      </div>
    </div>
  );
}
