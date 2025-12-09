import { useState, useEffect } from 'react';
import { 
  Star, 
  Search, 
  Filter,
  TrendingUp,
  Eye,
  EyeOff,
  Calendar,
  DollarSign,
  ShoppingBag,
  Package,
  Image as ImageIcon,
  X
} from 'lucide-react';

interface Product {
  id: number;
  name: string;
  slug: string;
  price: number;
  image: string;
  category: string;
  seller_name: string;
  is_featured: boolean;
  featured_slot: number | null;
  views: number;
  sales: number;
  revenue: number;
  featured_since: string | null;
}

interface FeaturedProductManagerProps {
  apiToken: string;
}

export default function FeaturedProductManager({ apiToken }: FeaturedProductManagerProps) {
  const [products, setProducts] = useState<Product[]>([]);
  const [featuredProducts, setFeaturedProducts] = useState<Product[]>([]);
  const [loading, setLoading] = useState(true);
  const [searchTerm, setSearchTerm] = useState('');
  const [filterCategory, setFilterCategory] = useState<string>('all');
  const [selectedSlot, setSelectedSlot] = useState<number>(1);
  const [showAddModal, setShowAddModal] = useState(false);

  const API_BASE = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000/api';
  const TOTAL_SLOTS = 12; // Number of featured product slots

  useEffect(() => {
    fetchProducts();
    fetchFeaturedProducts();
  }, []);

  const fetchProducts = async () => {
    try {
      const response = await fetch(`${API_BASE}/admin/products/available-for-featuring`, {
        headers: {
          'Authorization': `Bearer ${apiToken}`,
          'Accept': 'application/json',
        },
      });
      
      if (response.ok) {
        const data = await response.json();
        setProducts(data.data || []);
      }
    } catch (error) {
      console.error('Error fetching products:', error);
    } finally {
      setLoading(false);
    }
  };

  const fetchFeaturedProducts = async () => {
    try {
      const response = await fetch(`${API_BASE}/admin/products/featured`, {
        headers: {
          'Authorization': `Bearer ${apiToken}`,
          'Accept': 'application/json',
        },
      });
      
      if (response.ok) {
        const data = await response.json();
        setFeaturedProducts(data.data || []);
      }
    } catch (error) {
      console.error('Error fetching featured products:', error);
    }
  };

  const handleFeatureProduct = async (productId: number, slot: number) => {
    try {
      const response = await fetch(`${API_BASE}/admin/products/${productId}/feature`, {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${apiToken}`,
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        body: JSON.stringify({ slot }),
      });

      if (response.ok) {
        alert('Product featured successfully!');
        setShowAddModal(false);
        fetchProducts();
        fetchFeaturedProducts();
      }
    } catch (error) {
      console.error('Error featuring product:', error);
      alert('Failed to feature product');
    }
  };

  const handleUnfeatureProduct = async (productId: number) => {
    if (!confirm('Are you sure you want to remove this product from featured?')) {
      return;
    }

    try {
      const response = await fetch(`${API_BASE}/admin/products/${productId}/unfeature`, {
        method: 'DELETE',
        headers: {
          'Authorization': `Bearer ${apiToken}`,
          'Accept': 'application/json',
        },
      });

      if (response.ok) {
        alert('Product removed from featured!');
        fetchProducts();
        fetchFeaturedProducts();
      }
    } catch (error) {
      console.error('Error unfeaturing product:', error);
      alert('Failed to remove product from featured');
    }
  };

  const filteredProducts = products.filter(product => {
    const matchesSearch = product.name.toLowerCase().includes(searchTerm.toLowerCase());
    const matchesCategory = filterCategory === 'all' || product.category === filterCategory;
    return matchesSearch && matchesCategory && !product.is_featured;
  });

  const categories = Array.from(new Set(products.map(p => p.category)));
  const availableSlots = Array.from({ length: TOTAL_SLOTS }, (_, i) => i + 1)
    .filter(slot => !featuredProducts.some(p => p.featured_slot === slot));

  if (loading) {
    return (
      <div className="flex items-center justify-center min-h-screen">
        <div className="text-center">
          <div className="animate-spin rounded-full h-16 w-16 border-b-2 border-purple-500 mx-auto mb-4"></div>
          <p className="text-gray-600">Loading products...</p>
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
                <Star className="w-8 h-8 text-purple-600" />
                Featured Products Manager
              </h1>
              <p className="text-gray-600 mt-1">Manage homepage and category featured products</p>
            </div>
            <button
              onClick={() => setShowAddModal(true)}
              disabled={availableSlots.length === 0}
              className="flex items-center gap-2 px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 disabled:bg-gray-300 disabled:cursor-not-allowed transition-colors"
            >
              <Star className="w-4 h-4" />
              Add Featured Product
            </button>
          </div>
        </div>

        {/* Stats */}
        <div className="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
          <div className="bg-white rounded-lg shadow-sm p-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-gray-600">Featured Products</p>
                <p className="text-2xl font-bold text-purple-600">{featuredProducts.length}/{TOTAL_SLOTS}</p>
              </div>
              <Star className="w-8 h-8 text-purple-500" />
            </div>
          </div>

          <div className="bg-white rounded-lg shadow-sm p-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-gray-600">Available Slots</p>
                <p className="text-2xl font-bold text-green-600">{availableSlots.length}</p>
              </div>
              <Package className="w-8 h-8 text-green-500" />
            </div>
          </div>

          <div className="bg-white rounded-lg shadow-sm p-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-gray-600">Total Views</p>
                <p className="text-2xl font-bold text-blue-600">
                  {featuredProducts.reduce((sum, p) => sum + p.views, 0).toLocaleString()}
                </p>
              </div>
              <Eye className="w-8 h-8 text-blue-500" />
            </div>
          </div>

          <div className="bg-white rounded-lg shadow-sm p-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-gray-600">Featured Revenue</p>
                <p className="text-2xl font-bold text-green-600">
                  ${featuredProducts.reduce((sum, p) => sum + p.revenue, 0).toFixed(2)}
                </p>
              </div>
              <DollarSign className="w-8 h-8 text-green-500" />
            </div>
          </div>
        </div>

        {/* Featured Products Grid */}
        <div className="bg-white rounded-lg shadow-sm p-6 mb-6">
          <h2 className="text-xl font-bold text-gray-900 mb-4">Current Featured Products</h2>
          
          <div className="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-4">
            {Array.from({ length: TOTAL_SLOTS }, (_, i) => i + 1).map(slot => {
              const product = featuredProducts.find(p => p.featured_slot === slot);
              
              return (
                <div
                  key={slot}
                  className={`border-2 border-dashed rounded-lg p-4 ${
                    product ? 'border-purple-300 bg-purple-50' : 'border-gray-300 bg-gray-50'
                  }`}
                >
                  <div className="flex items-center justify-between mb-3">
                    <span className="text-sm font-medium text-gray-600">Slot {slot}</span>
                    {product && (
                      <button
                        onClick={() => handleUnfeatureProduct(product.id)}
                        className="text-red-600 hover:text-red-700"
                      >
                        <X className="w-4 h-4" />
                      </button>
                    )}
                  </div>

                  {product ? (
                    <div>
                      <div className="aspect-square bg-gray-200 rounded-lg mb-3 overflow-hidden">
                        {product.image ? (
                          <img src={product.image} alt={product.name} className="w-full h-full object-cover" />
                        ) : (
                          <div className="w-full h-full flex items-center justify-center">
                            <ImageIcon className="w-12 h-12 text-gray-400" />
                          </div>
                        )}
                      </div>
                      <h3 className="font-medium text-gray-900 text-sm mb-1 line-clamp-2">{product.name}</h3>
                      <p className="text-sm text-gray-600 mb-2">${product.price.toFixed(2)}</p>
                      <div className="flex items-center justify-between text-xs text-gray-500">
                        <span className="flex items-center gap-1">
                          <Eye className="w-3 h-3" />
                          {product.views}
                        </span>
                        <span className="flex items-center gap-1">
                          <ShoppingBag className="w-3 h-3" />
                          {product.sales}
                        </span>
                      </div>
                      {product.featured_since && (
                        <p className="text-xs text-gray-500 mt-2">
                          Since: {new Date(product.featured_since).toLocaleDateString()}
                        </p>
                      )}
                    </div>
                  ) : (
                    <div className="text-center py-8">
                      <Star className="w-12 h-12 text-gray-400 mx-auto mb-2" />
                      <p className="text-sm text-gray-500">Empty Slot</p>
                    </div>
                  )}
                </div>
              );
            })}
          </div>
        </div>

        {/* Add Featured Product Modal */}
        {showAddModal && (
          <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
            <div className="bg-white rounded-lg shadow-xl w-full max-w-4xl max-h-[90vh] overflow-y-auto">
              <div className="p-6">
                <div className="flex items-center justify-between mb-6">
                  <h3 className="text-2xl font-bold text-gray-900">Add Featured Product</h3>
                  <button
                    onClick={() => setShowAddModal(false)}
                    className="text-gray-400 hover:text-gray-600"
                  >
                    <X className="w-6 h-6" />
                  </button>
                </div>

                {/* Slot Selector */}
                <div className="mb-6">
                  <label className="block text-sm font-medium text-gray-700 mb-2">
                    Select Featured Slot
                  </label>
                  <select
                    value={selectedSlot}
                    onChange={(e) => setSelectedSlot(parseInt(e.target.value))}
                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                  >
                    {availableSlots.map(slot => (
                      <option key={slot} value={slot}>Slot {slot}</option>
                    ))}
                  </select>
                </div>

                {/* Filters */}
                <div className="mb-6 grid grid-cols-2 gap-4">
                  <div className="relative">
                    <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400" />
                    <input
                      type="text"
                      placeholder="Search products..."
                      value={searchTerm}
                      onChange={(e) => setSearchTerm(e.target.value)}
                      className="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                    />
                  </div>

                  <select
                    value={filterCategory}
                    onChange={(e) => setFilterCategory(e.target.value)}
                    className="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                  >
                    <option value="all">All Categories</option>
                    {categories.map(cat => (
                      <option key={cat} value={cat}>{cat}</option>
                    ))}
                  </select>
                </div>

                {/* Products Grid */}
                <div className="grid grid-cols-2 md:grid-cols-3 gap-4 max-h-96 overflow-y-auto">
                  {filteredProducts.map(product => (
                    <div
                      key={product.id}
                      className="border border-gray-200 rounded-lg p-3 hover:border-purple-300 hover:shadow-md transition-all cursor-pointer"
                      onClick={() => handleFeatureProduct(product.id, selectedSlot)}
                    >
                      <div className="aspect-square bg-gray-200 rounded-lg mb-2 overflow-hidden">
                        {product.image ? (
                          <img src={product.image} alt={product.name} className="w-full h-full object-cover" />
                        ) : (
                          <div className="w-full h-full flex items-center justify-center">
                            <ImageIcon className="w-8 h-8 text-gray-400" />
                          </div>
                        )}
                      </div>
                      <h4 className="font-medium text-gray-900 text-sm mb-1 line-clamp-2">{product.name}</h4>
                      <p className="text-sm text-gray-600 mb-1">${product.price.toFixed(2)}</p>
                      <div className="flex items-center justify-between text-xs text-gray-500">
                        <span>{product.category}</span>
                        <span>{product.sales} sales</span>
                      </div>
                    </div>
                  ))}
                </div>

                {filteredProducts.length === 0 && (
                  <div className="text-center py-8">
                    <Package className="w-12 h-12 text-gray-400 mx-auto mb-3" />
                    <p className="text-gray-500">No products available</p>
                  </div>
                )}
              </div>
            </div>
          </div>
        )}
      </div>
    </div>
  );
}
