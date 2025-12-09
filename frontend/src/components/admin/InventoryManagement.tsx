import { useState, useEffect } from 'react';
import { 
  Package, 
  AlertTriangle, 
  TrendingDown, 
  Search, 
  Filter,
  Edit,
  RefreshCw,
  Download,
  Bell,
  CheckCircle,
  XCircle
} from 'lucide-react';

interface Product {
  id: number;
  name: string;
  sku: string;
  stock: number;
  low_stock_threshold: number;
  price: number;
  category: string;
  status: 'active' | 'inactive' | 'out_of_stock';
  last_restock_date: string;
  supplier: string;
  location: string;
}

interface StockAlert {
  id: number;
  product_id: number;
  product_name: string;
  current_stock: number;
  threshold: number;
  severity: 'critical' | 'warning' | 'info';
  created_at: string;
}

interface InventoryManagementProps {
  apiToken: string;
}

export default function InventoryManagement({ apiToken }: InventoryManagementProps) {
  const [products, setProducts] = useState<Product[]>([]);
  const [stockAlerts, setStockAlerts] = useState<StockAlert[]>([]);
  const [loading, setLoading] = useState(true);
  const [searchTerm, setSearchTerm] = useState('');
  const [filterStatus, setFilterStatus] = useState<string>('all');
  const [filterStock, setFilterStock] = useState<string>('all');
  const [showAlerts, setShowAlerts] = useState(true);
  const [editingProduct, setEditingProduct] = useState<Product | null>(null);
  const [restockQuantity, setRestockQuantity] = useState<number>(0);

  const API_BASE = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000/api';

  useEffect(() => {
    fetchInventoryData();
    fetchStockAlerts();
  }, []);

  const fetchInventoryData = async () => {
    try {
      const response = await fetch(`${API_BASE}/admin/inventory`, {
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
      console.error('Error fetching inventory:', error);
    } finally {
      setLoading(false);
    }
  };

  const fetchStockAlerts = async () => {
    try {
      const response = await fetch(`${API_BASE}/admin/inventory/alerts`, {
        headers: {
          'Authorization': `Bearer ${apiToken}`,
          'Accept': 'application/json',
        },
      });
      
      if (response.ok) {
        const data = await response.json();
        setStockAlerts(data.data || []);
      }
    } catch (error) {
      console.error('Error fetching alerts:', error);
    }
  };

  const handleRestock = async (productId: number) => {
    if (restockQuantity <= 0) {
      alert('Please enter a valid quantity');
      return;
    }

    try {
      const response = await fetch(`${API_BASE}/admin/inventory/${productId}/restock`, {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${apiToken}`,
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        body: JSON.stringify({ quantity: restockQuantity }),
      });

      if (response.ok) {
        alert('Product restocked successfully!');
        setEditingProduct(null);
        setRestockQuantity(0);
        fetchInventoryData();
        fetchStockAlerts();
      }
    } catch (error) {
      console.error('Error restocking product:', error);
      alert('Failed to restock product');
    }
  };

  const handleUpdateThreshold = async (productId: number, threshold: number) => {
    try {
      const response = await fetch(`${API_BASE}/admin/inventory/${productId}/threshold`, {
        method: 'PUT',
        headers: {
          'Authorization': `Bearer ${apiToken}`,
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        body: JSON.stringify({ low_stock_threshold: threshold }),
      });

      if (response.ok) {
        alert('Threshold updated successfully!');
        fetchInventoryData();
      }
    } catch (error) {
      console.error('Error updating threshold:', error);
    }
  };

  const exportInventory = () => {
    const csv = [
      ['SKU', 'Product Name', 'Stock', 'Threshold', 'Price', 'Category', 'Status', 'Supplier', 'Location'],
      ...filteredProducts.map(p => [
        p.sku,
        p.name,
        p.stock.toString(),
        p.low_stock_threshold.toString(),
        p.price.toString(),
        p.category,
        p.status,
        p.supplier,
        p.location
      ])
    ].map(row => row.join(',')).join('\n');

    const blob = new Blob([csv], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `inventory-${new Date().toISOString().split('T')[0]}.csv`;
    a.click();
  };

  const filteredProducts = products.filter(product => {
    const matchesSearch = product.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
                         product.sku.toLowerCase().includes(searchTerm.toLowerCase());
    
    const matchesStatus = filterStatus === 'all' || product.status === filterStatus;
    
    let matchesStock = true;
    if (filterStock === 'low') {
      matchesStock = product.stock <= product.low_stock_threshold;
    } else if (filterStock === 'out') {
      matchesStock = product.stock === 0;
    } else if (filterStock === 'normal') {
      matchesStock = product.stock > product.low_stock_threshold;
    }

    return matchesSearch && matchesStatus && matchesStock;
  });

  const getStockStatus = (product: Product) => {
    if (product.stock === 0) return { label: 'Out of Stock', color: 'red', icon: XCircle };
    if (product.stock <= product.low_stock_threshold) return { label: 'Low Stock', color: 'yellow', icon: AlertTriangle };
    return { label: 'In Stock', color: 'green', icon: CheckCircle };
  };

  const getAlertSeverityColor = (severity: string) => {
    switch (severity) {
      case 'critical': return 'red';
      case 'warning': return 'yellow';
      case 'info': return 'blue';
      default: return 'gray';
    }
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center min-h-screen">
        <div className="text-center">
          <div className="animate-spin rounded-full h-16 w-16 border-b-2 border-purple-500 mx-auto mb-4"></div>
          <p className="text-gray-600">Loading inventory data...</p>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gray-50 p-8">
      <div className="max-w-7xl mx-auto">
        {/* Header */}
        <div className="mb-8">
          <div className="flex items-center justify-between mb-4">
            <div>
              <h1 className="text-3xl font-bold text-gray-900 flex items-center gap-3">
                <Package className="w-8 h-8 text-purple-600" />
                Inventory Management
              </h1>
              <p className="text-gray-600 mt-1">Monitor stock levels and manage inventory</p>
            </div>
            <div className="flex gap-3">
              <button
                onClick={() => fetchInventoryData()}
                className="flex items-center gap-2 px-4 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
              >
                <RefreshCw className="w-4 h-4" />
                Refresh
              </button>
              <button
                onClick={exportInventory}
                className="flex items-center gap-2 px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors"
              >
                <Download className="w-4 h-4" />
                Export CSV
              </button>
            </div>
          </div>

          {/* Stock Alerts Banner */}
          {showAlerts && stockAlerts.length > 0 && (
            <div className="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
              <div className="flex items-start justify-between">
                <div className="flex items-start gap-3">
                  <Bell className="w-5 h-5 text-yellow-600 mt-0.5" />
                  <div>
                    <h3 className="font-semibold text-yellow-900">Stock Alerts ({stockAlerts.length})</h3>
                    <div className="mt-2 space-y-1">
                      {stockAlerts.slice(0, 3).map(alert => (
                        <p key={alert.id} className="text-sm text-yellow-800">
                          <span className="font-medium">{alert.product_name}</span> - Only {alert.current_stock} left (threshold: {alert.threshold})
                        </p>
                      ))}
                      {stockAlerts.length > 3 && (
                        <p className="text-sm text-yellow-700">+ {stockAlerts.length - 3} more alerts</p>
                      )}
                    </div>
                  </div>
                </div>
                <button
                  onClick={() => setShowAlerts(false)}
                  className="text-yellow-600 hover:text-yellow-700"
                >
                  ×
                </button>
              </div>
            </div>
          )}
        </div>

        {/* Filters */}
        <div className="bg-white rounded-lg shadow-sm p-6 mb-6">
          <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div className="relative">
              <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400" />
              <input
                type="text"
                placeholder="Search products or SKU..."
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
              <option value="active">Active</option>
              <option value="inactive">Inactive</option>
              <option value="out_of_stock">Out of Stock</option>
            </select>

            <select
              value={filterStock}
              onChange={(e) => setFilterStock(e.target.value)}
              className="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
            >
              <option value="all">All Stock Levels</option>
              <option value="normal">Normal Stock</option>
              <option value="low">Low Stock</option>
              <option value="out">Out of Stock</option>
            </select>

            <div className="flex items-center gap-2 text-sm text-gray-600">
              <Filter className="w-4 h-4" />
              <span>{filteredProducts.length} products</span>
            </div>
          </div>
        </div>

        {/* Inventory Table */}
        <div className="bg-white rounded-lg shadow-sm overflow-hidden">
          <div className="overflow-x-auto">
            <table className="w-full">
              <thead className="bg-gray-50 border-b border-gray-200">
                <tr>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">SKU</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stock</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Supplier</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Location</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
              </thead>
              <tbody className="bg-white divide-y divide-gray-200">
                {filteredProducts.map((product) => {
                  const stockStatus = getStockStatus(product);
                  const StatusIcon = stockStatus.icon;

                  return (
                    <tr key={product.id} className="hover:bg-gray-50">
                      <td className="px-6 py-4">
                        <div className="text-sm font-medium text-gray-900">{product.name}</div>
                        <div className="text-sm text-gray-500">${product.price.toFixed(2)}</div>
                      </td>
                      <td className="px-6 py-4 text-sm text-gray-500">{product.sku}</td>
                      <td className="px-6 py-4">
                        <div className="text-sm font-medium text-gray-900">{product.stock} units</div>
                        <div className="text-xs text-gray-500">Threshold: {product.low_stock_threshold}</div>
                      </td>
                      <td className="px-6 py-4">
                        <span className={`inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-${stockStatus.color}-100 text-${stockStatus.color}-800`}>
                          <StatusIcon className="w-3 h-3" />
                          {stockStatus.label}
                        </span>
                      </td>
                      <td className="px-6 py-4 text-sm text-gray-500">{product.category}</td>
                      <td className="px-6 py-4 text-sm text-gray-500">{product.supplier}</td>
                      <td className="px-6 py-4 text-sm text-gray-500">{product.location}</td>
                      <td className="px-6 py-4 text-sm font-medium">
                        <button
                          onClick={() => setEditingProduct(product)}
                          className="text-purple-600 hover:text-purple-900 flex items-center gap-1"
                        >
                          <Edit className="w-4 h-4" />
                          Restock
                        </button>
                      </td>
                    </tr>
                  );
                })}
              </tbody>
            </table>
          </div>

          {filteredProducts.length === 0 && (
            <div className="text-center py-12">
              <Package className="w-12 h-12 text-gray-400 mx-auto mb-4" />
              <p className="text-gray-500">No products found</p>
            </div>
          )}
        </div>

        {/* Restock Modal */}
        {editingProduct && (
          <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div className="bg-white rounded-lg shadow-xl p-6 w-full max-w-md">
              <h3 className="text-xl font-bold mb-4">Restock Product</h3>
              <div className="mb-4">
                <p className="text-sm text-gray-600 mb-2">Product: <span className="font-medium text-gray-900">{editingProduct.name}</span></p>
                <p className="text-sm text-gray-600 mb-2">Current Stock: <span className="font-medium text-gray-900">{editingProduct.stock} units</span></p>
                <p className="text-sm text-gray-600 mb-4">SKU: <span className="font-medium text-gray-900">{editingProduct.sku}</span></p>
                
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  Quantity to Add
                </label>
                <input
                  type="number"
                  min="1"
                  value={restockQuantity}
                  onChange={(e) => setRestockQuantity(parseInt(e.target.value) || 0)}
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                  placeholder="Enter quantity"
                />
                <p className="text-xs text-gray-500 mt-1">
                  New stock level will be: {editingProduct.stock + restockQuantity} units
                </p>
              </div>

              <div className="flex gap-3">
                <button
                  onClick={() => handleRestock(editingProduct.id)}
                  className="flex-1 bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700 transition-colors"
                >
                  Confirm Restock
                </button>
                <button
                  onClick={() => {
                    setEditingProduct(null);
                    setRestockQuantity(0);
                  }}
                  className="flex-1 bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300 transition-colors"
                >
                  Cancel
                </button>
              </div>
            </div>
          </div>
        )}
      </div>
    </div>
  );
}
