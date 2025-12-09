import { useState, useEffect } from 'react';
import { 
  Award, 
  TrendingUp,
  Gift,
  Users,
  DollarSign,
  Edit,
  Save,
  X,
  Plus,
  Trash2
} from 'lucide-react';

interface LoyaltyTier {
  id: number;
  name: string;
  slug: string;
  min_points: number;
  max_points: number | null;
  benefits: string[];
  point_multiplier: number;
  discount_percentage: number;
  color: string;
  icon: string;
  welcome_bonus: number;
  birthday_bonus: number;
  referral_bonus: number;
}

interface LoyaltyTierEditorProps {
  apiToken: string;
}

export default function LoyaltyTierEditor({ apiToken }: LoyaltyTierEditorProps) {
  const [tiers, setTiers] = useState<LoyaltyTier[]>([]);
  const [loading, setLoading] = useState(true);
  const [editingTier, setEditingTier] = useState<LoyaltyTier | null>(null);
  const [isCreating, setIsCreating] = useState(false);
  const [formData, setFormData] = useState<Partial<LoyaltyTier>>({
    name: '',
    slug: '',
    min_points: 0,
    max_points: null,
    benefits: [],
    point_multiplier: 1.0,
    discount_percentage: 0,
    color: '#8B5CF6',
    icon: '🏅',
    welcome_bonus: 0,
    birthday_bonus: 0,
    referral_bonus: 0,
  });
  const [newBenefit, setNewBenefit] = useState('');

  const API_BASE = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000/api';

  const defaultTiers: LoyaltyTier[] = [
    {
      id: 1,
      name: 'Bronze',
      slug: 'bronze',
      min_points: 0,
      max_points: 4999,
      benefits: ['Earn 1 point per $1 spent', 'Birthday bonus points', 'Exclusive member-only sales'],
      point_multiplier: 1.0,
      discount_percentage: 0,
      color: '#CD7F32',
      icon: '🥉',
      welcome_bonus: 100,
      birthday_bonus: 50,
      referral_bonus: 200,
    },
    {
      id: 2,
      name: 'Silver',
      slug: 'silver',
      min_points: 5000,
      max_points: 9999,
      benefits: ['Earn 1.2x points per $1 spent', '5% discount on all purchases', 'Free shipping on orders $50+', 'Priority customer support', 'Early access to sales'],
      point_multiplier: 1.2,
      discount_percentage: 5,
      color: '#C0C0C0',
      icon: '🥈',
      welcome_bonus: 0,
      birthday_bonus: 100,
      referral_bonus: 300,
    },
    {
      id: 3,
      name: 'Gold',
      slug: 'gold',
      min_points: 10000,
      max_points: 24999,
      benefits: ['Earn 1.5x points per $1 spent', '10% discount on all purchases', 'Free shipping on all orders', 'Dedicated account manager', 'Exclusive product previews', 'VIP customer support'],
      point_multiplier: 1.5,
      discount_percentage: 10,
      color: '#FFD700',
      icon: '🥇',
      welcome_bonus: 0,
      birthday_bonus: 200,
      referral_bonus: 500,
    },
    {
      id: 4,
      name: 'Platinum',
      slug: 'platinum',
      min_points: 25000,
      max_points: 49999,
      benefits: ['Earn 2x points per $1 spent', '15% discount on all purchases', 'Free expedited shipping', 'Personal shopping assistant', 'Invitation to exclusive events', 'Quarterly gift vouchers', 'Premium packaging'],
      point_multiplier: 2.0,
      discount_percentage: 15,
      color: '#E5E4E2',
      icon: '💎',
      welcome_bonus: 0,
      birthday_bonus: 500,
      referral_bonus: 1000,
    },
    {
      id: 5,
      name: 'Diamond',
      slug: 'diamond',
      min_points: 50000,
      max_points: null,
      benefits: ['Earn 2.5x points per $1 spent', '20% discount on all purchases', 'Free overnight shipping', 'Concierge service', 'Annual VIP membership gift', 'First access to limited editions', 'Complimentary product insurance', 'Lifetime warranty on select items'],
      point_multiplier: 2.5,
      discount_percentage: 20,
      color: '#B9F2FF',
      icon: '💎',
      welcome_bonus: 0,
      birthday_bonus: 1000,
      referral_bonus: 2000,
    },
  ];

  useEffect(() => {
    fetchTiers();
  }, []);

  const fetchTiers = async () => {
    setLoading(true);
    try {
      const response = await fetch(`${API_BASE}/admin/loyalty-tiers`, {
        headers: {
          'Authorization': `Bearer ${apiToken}`,
          'Accept': 'application/json',
        },
      });
      
      if (response.ok) {
        const data = await response.json();
        setTiers(data.data && data.data.length > 0 ? data.data : defaultTiers);
      } else {
        setTiers(defaultTiers);
      }
    } catch (error) {
      console.error('Error fetching tiers:', error);
      setTiers(defaultTiers);
    } finally {
      setLoading(false);
    }
  };

  const handleSaveTier = async () => {
    if (!formData.name || !formData.slug || formData.min_points === undefined) {
      alert('Please fill in all required fields');
      return;
    }

    try {
      const url = editingTier 
        ? `${API_BASE}/admin/loyalty-tiers/${editingTier.id}`
        : `${API_BASE}/admin/loyalty-tiers`;
      
      const method = editingTier ? 'PUT' : 'POST';

      const response = await fetch(url, {
        method,
        headers: {
          'Authorization': `Bearer ${apiToken}`,
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        body: JSON.stringify(formData),
      });

      if (response.ok) {
        alert(editingTier ? 'Tier updated successfully!' : 'Tier created successfully!');
        resetForm();
        fetchTiers();
      }
    } catch (error) {
      console.error('Error saving tier:', error);
      alert('Failed to save tier');
    }
  };

  const handleDeleteTier = async (tierId: number) => {
    if (!confirm('Are you sure you want to delete this tier? This action cannot be undone.')) {
      return;
    }

    try {
      const response = await fetch(`${API_BASE}/admin/loyalty-tiers/${tierId}`, {
        method: 'DELETE',
        headers: {
          'Authorization': `Bearer ${apiToken}`,
          'Accept': 'application/json',
        },
      });

      if (response.ok) {
        alert('Tier deleted successfully!');
        fetchTiers();
      }
    } catch (error) {
      console.error('Error deleting tier:', error);
      alert('Failed to delete tier');
    }
  };

  const startEdit = (tier: LoyaltyTier) => {
    setEditingTier(tier);
    setFormData(tier);
    setIsCreating(false);
  };

  const startCreate = () => {
    setEditingTier(null);
    setFormData({
      name: '',
      slug: '',
      min_points: 0,
      max_points: null,
      benefits: [],
      point_multiplier: 1.0,
      discount_percentage: 0,
      color: '#8B5CF6',
      icon: '🏅',
      welcome_bonus: 0,
      birthday_bonus: 0,
      referral_bonus: 0,
    });
    setIsCreating(true);
  };

  const resetForm = () => {
    setEditingTier(null);
    setIsCreating(false);
    setFormData({
      name: '',
      slug: '',
      min_points: 0,
      max_points: null,
      benefits: [],
      point_multiplier: 1.0,
      discount_percentage: 0,
      color: '#8B5CF6',
      icon: '🏅',
      welcome_bonus: 0,
      birthday_bonus: 0,
      referral_bonus: 0,
    });
  };

  const addBenefit = () => {
    if (newBenefit.trim()) {
      setFormData({
        ...formData,
        benefits: [...(formData.benefits || []), newBenefit.trim()],
      });
      setNewBenefit('');
    }
  };

  const removeBenefit = (index: number) => {
    setFormData({
      ...formData,
      benefits: formData.benefits?.filter((_, i) => i !== index) || [],
    });
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center min-h-screen">
        <div className="text-center">
          <div className="animate-spin rounded-full h-16 w-16 border-b-2 border-purple-500 mx-auto mb-4"></div>
          <p className="text-gray-600">Loading loyalty tiers...</p>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gray-50 p-8">
      <div className="max-w-7xl mx-auto">
        {/* Header */}
        <div className="mb-8">
          <div className="flex items-center justify-between">
            <div>
              <h1 className="text-3xl font-bold text-gray-900 flex items-center gap-3">
                <Award className="w-8 h-8 text-purple-600" />
                Loyalty Tier Editor
              </h1>
              <p className="text-gray-600 mt-1">Configure loyalty program tiers and rewards</p>
            </div>
            <button
              onClick={startCreate}
              className="flex items-center gap-2 px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors"
            >
              <Plus className="w-4 h-4" />
              Create New Tier
            </button>
          </div>
        </div>

        <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
          {/* Tiers List */}
          <div className="lg:col-span-2 space-y-4">
            {tiers.map((tier) => (
              <div
                key={tier.id}
                className="bg-white rounded-lg shadow-sm p-6 border-l-4"
                style={{ borderLeftColor: tier.color }}
              >
                <div className="flex items-start justify-between mb-4">
                  <div className="flex items-center gap-3">
                    <span className="text-4xl">{tier.icon}</span>
                    <div>
                      <h3 className="text-xl font-bold text-gray-900">{tier.name}</h3>
                      <p className="text-sm text-gray-600">
                        {tier.min_points.toLocaleString()} - {tier.max_points ? tier.max_points.toLocaleString() : '∞'} points
                      </p>
                    </div>
                  </div>
                  <div className="flex gap-2">
                    <button
                      onClick={() => startEdit(tier)}
                      className="text-purple-600 hover:text-purple-700"
                    >
                      <Edit className="w-5 h-5" />
                    </button>
                    <button
                      onClick={() => handleDeleteTier(tier.id)}
                      className="text-red-600 hover:text-red-700"
                    >
                      <Trash2 className="w-5 h-5" />
                    </button>
                  </div>
                </div>

                <div className="grid grid-cols-3 gap-4 mb-4">
                  <div className="bg-gray-50 rounded-lg p-3">
                    <p className="text-xs text-gray-600">Point Multiplier</p>
                    <p className="text-lg font-semibold text-gray-900">{tier.point_multiplier}x</p>
                  </div>
                  <div className="bg-gray-50 rounded-lg p-3">
                    <p className="text-xs text-gray-600">Discount</p>
                    <p className="text-lg font-semibold text-gray-900">{tier.discount_percentage}%</p>
                  </div>
                  <div className="bg-gray-50 rounded-lg p-3">
                    <p className="text-xs text-gray-600">Birthday Bonus</p>
                    <p className="text-lg font-semibold text-gray-900">{tier.birthday_bonus} pts</p>
                  </div>
                </div>

                <div>
                  <h4 className="text-sm font-medium text-gray-700 mb-2">Benefits:</h4>
                  <ul className="space-y-1">
                    {tier.benefits.map((benefit, index) => (
                      <li key={index} className="text-sm text-gray-600 flex items-start gap-2">
                        <span className="text-green-500 mt-0.5">✓</span>
                        <span>{benefit}</span>
                      </li>
                    ))}
                  </ul>
                </div>
              </div>
            ))}
          </div>

          {/* Edit/Create Form */}
          {(isCreating || editingTier) && (
            <div className="lg:col-span-1">
              <div className="bg-white rounded-lg shadow-sm p-6 sticky top-8">
                <div className="flex items-center justify-between mb-6">
                  <h3 className="text-xl font-bold text-gray-900">
                    {editingTier ? 'Edit Tier' : 'Create New Tier'}
                  </h3>
                  <button onClick={resetForm} className="text-gray-400 hover:text-gray-600">
                    <X className="w-5 h-5" />
                  </button>
                </div>

                <div className="space-y-4">
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">Tier Name *</label>
                    <input
                      type="text"
                      value={formData.name}
                      onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                      className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                      placeholder="e.g., Bronze, Silver, Gold"
                    />
                  </div>

                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">Slug *</label>
                    <input
                      type="text"
                      value={formData.slug}
                      onChange={(e) => setFormData({ ...formData, slug: e.target.value })}
                      className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                      placeholder="e.g., bronze, silver, gold"
                    />
                  </div>

                  <div className="grid grid-cols-2 gap-4">
                    <div>
                      <label className="block text-sm font-medium text-gray-700 mb-1">Min Points *</label>
                      <input
                        type="number"
                        value={formData.min_points}
                        onChange={(e) => setFormData({ ...formData, min_points: parseInt(e.target.value) || 0 })}
                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                      />
                    </div>
                    <div>
                      <label className="block text-sm font-medium text-gray-700 mb-1">Max Points</label>
                      <input
                        type="number"
                        value={formData.max_points || ''}
                        onChange={(e) => setFormData({ ...formData, max_points: e.target.value ? parseInt(e.target.value) : null })}
                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                        placeholder="Leave empty for unlimited"
                      />
                    </div>
                  </div>

                  <div className="grid grid-cols-2 gap-4">
                    <div>
                      <label className="block text-sm font-medium text-gray-700 mb-1">Point Multiplier</label>
                      <input
                        type="number"
                        step="0.1"
                        value={formData.point_multiplier}
                        onChange={(e) => setFormData({ ...formData, point_multiplier: parseFloat(e.target.value) || 1.0 })}
                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                      />
                    </div>
                    <div>
                      <label className="block text-sm font-medium text-gray-700 mb-1">Discount %</label>
                      <input
                        type="number"
                        value={formData.discount_percentage}
                        onChange={(e) => setFormData({ ...formData, discount_percentage: parseInt(e.target.value) || 0 })}
                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                      />
                    </div>
                  </div>

                  <div className="grid grid-cols-2 gap-4">
                    <div>
                      <label className="block text-sm font-medium text-gray-700 mb-1">Color</label>
                      <input
                        type="color"
                        value={formData.color}
                        onChange={(e) => setFormData({ ...formData, color: e.target.value })}
                        className="w-full h-10 px-1 py-1 border border-gray-300 rounded-lg"
                      />
                    </div>
                    <div>
                      <label className="block text-sm font-medium text-gray-700 mb-1">Icon (emoji)</label>
                      <input
                        type="text"
                        value={formData.icon}
                        onChange={(e) => setFormData({ ...formData, icon: e.target.value })}
                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent text-center text-2xl"
                        maxLength={2}
                      />
                    </div>
                  </div>

                  <div className="grid grid-cols-3 gap-2">
                    <div>
                      <label className="block text-xs font-medium text-gray-700 mb-1">Welcome</label>
                      <input
                        type="number"
                        value={formData.welcome_bonus}
                        onChange={(e) => setFormData({ ...formData, welcome_bonus: parseInt(e.target.value) || 0 })}
                        className="w-full px-2 py-1 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                      />
                    </div>
                    <div>
                      <label className="block text-xs font-medium text-gray-700 mb-1">Birthday</label>
                      <input
                        type="number"
                        value={formData.birthday_bonus}
                        onChange={(e) => setFormData({ ...formData, birthday_bonus: parseInt(e.target.value) || 0 })}
                        className="w-full px-2 py-1 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                      />
                    </div>
                    <div>
                      <label className="block text-xs font-medium text-gray-700 mb-1">Referral</label>
                      <input
                        type="number"
                        value={formData.referral_bonus}
                        onChange={(e) => setFormData({ ...formData, referral_bonus: parseInt(e.target.value) || 0 })}
                        className="w-full px-2 py-1 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                      />
                    </div>
                  </div>

                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">Benefits</label>
                    <div className="flex gap-2 mb-2">
                      <input
                        type="text"
                        value={newBenefit}
                        onChange={(e) => setNewBenefit(e.target.value)}
                        onKeyPress={(e) => e.key === 'Enter' && addBenefit()}
                        className="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent text-sm"
                        placeholder="Add a benefit..."
                      />
                      <button
                        onClick={addBenefit}
                        className="px-3 py-2 bg-purple-100 text-purple-600 rounded-lg hover:bg-purple-200"
                      >
                        <Plus className="w-4 h-4" />
                      </button>
                    </div>
                    <div className="space-y-1 max-h-32 overflow-y-auto">
                      {formData.benefits?.map((benefit, index) => (
                        <div key={index} className="flex items-center justify-between bg-gray-50 px-2 py-1 rounded text-sm">
                          <span className="flex-1">{benefit}</span>
                          <button
                            onClick={() => removeBenefit(index)}
                            className="text-red-600 hover:text-red-700 ml-2"
                          >
                            <X className="w-4 h-4" />
                          </button>
                        </div>
                      ))}
                    </div>
                  </div>

                  <button
                    onClick={handleSaveTier}
                    className="w-full flex items-center justify-center gap-2 px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors"
                  >
                    <Save className="w-4 h-4" />
                    {editingTier ? 'Update Tier' : 'Create Tier'}
                  </button>
                </div>
              </div>
            </div>
          )}
        </div>
      </div>
    </div>
  );
}
