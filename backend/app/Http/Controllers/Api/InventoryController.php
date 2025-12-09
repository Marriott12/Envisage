<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\InventoryLog;
use App\Models\LowStockAlert;
use App\Models\ProductImport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class InventoryController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Get all products with inventory details for admin
     */
    public function index(Request $request)
    {
        $query = Product::query();

        // Search by name or SKU
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if ($request->has('status')) {
            $status = $request->input('status');
            if ($status === 'out_of_stock') {
                $query->where('stock', 0);
            } elseif ($status === 'active') {
                $query->where('stock', '>', 0);
            }
        }

        // Filter by stock level
        if ($request->has('stock_level')) {
            $level = $request->input('stock_level');
            if ($level === 'low') {
                $query->whereRaw('stock > 0 AND stock <= low_stock_threshold');
            } elseif ($level === 'out') {
                $query->where('stock', 0);
            } elseif ($level === 'normal') {
                $query->whereRaw('stock > low_stock_threshold');
            }
        }

        $products = $query->orderBy('created_at', 'desc')->get()->map(function($product) {
            return [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku ?? 'N/A',
                'stock' => $product->stock ?? 0,
                'low_stock_threshold' => $product->low_stock_threshold ?? 10,
                'price' => $product->price,
                'status' => ($product->stock ?? 0) > 0 ? 'active' : 'out_of_stock',
                'stock_status' => ($product->stock ?? 0) === 0 ? 'out' : 
                                 (($product->stock ?? 0) <= ($product->low_stock_threshold ?? 10) ? 'low' : 'normal'),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $products,
        ]);
    }

    /**
     * Get low stock alerts for admin
     */
    public function alerts()
    {
        $alerts = Product::whereRaw('stock > 0 AND stock <= low_stock_threshold')
            ->orWhere('stock', 0)
            ->orderBy('stock', 'asc')
            ->get()
            ->map(function($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'sku' => $product->sku ?? 'N/A',
                    'stock' => $product->stock ?? 0,
                    'threshold' => $product->low_stock_threshold ?? 10,
                    'severity' => ($product->stock ?? 0) === 0 ? 'critical' : 'warning',
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $alerts,
        ]);
    }

    /**
     * Restock a product (admin endpoint)
     */
    public function restock(Request $request, $id)
    {
        $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        $product = Product::findOrFail($id);
        $product->stock = ($product->stock ?? 0) + $request->input('quantity');
        $product->save();

        return response()->json([
            'success' => true,
            'message' => 'Product restocked successfully',
            'data' => [
                'id' => $product->id,
                'name' => $product->name,
                'stock' => $product->stock,
            ],
        ]);
    }

    /**
     * Update low stock threshold (admin endpoint)
     */
    public function updateThreshold(Request $request, $id)
    {
        $request->validate([
            'threshold' => 'required|integer|min:0',
        ]);

        $product = Product::findOrFail($id);
        $product->low_stock_threshold = $request->input('threshold');
        $product->save();

        return response()->json([
            'success' => true,
            'message' => 'Threshold updated successfully',
            'data' => [
                'id' => $product->id,
                'name' => $product->name,
                'low_stock_threshold' => $product->low_stock_threshold,
            ],
        ]);
    }

    public function updateStock(Request $request, $productId)
    {
        $request->validate([
            'quantity' => 'required|integer',
            'type' => 'required|in:restock,sale,adjustment,return,damaged',
            'notes' => 'nullable|string|max:500',
        ]);

        $product = Product::findOrFail($productId);
        
        if ($product->seller_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $quantityBefore = $product->inventory_count ?? 0;
        $quantityAfter = $quantityBefore + $request->quantity;

        if ($quantityAfter < 0) {
            return response()->json([
                'message' => 'Insufficient inventory',
            ], 400);
        }

        $product->update(['inventory_count' => $quantityAfter]);

        InventoryLog::create([
            'product_id' => $product->id,
            'type' => $request->type,
            'quantity' => $request->quantity,
            'quantity_before' => $quantityBefore,
            'quantity_after' => $quantityAfter,
            'user_id' => Auth::id(),
            'notes' => $request->notes,
        ]);

        // Check low stock alert
        $this->checkLowStock($product);

        return response()->json([
            'product' => $product,
            'message' => 'Inventory updated successfully',
        ]);
    }

    public function inventoryHistory($productId)
    {
        $product = Product::findOrFail($productId);
        
        if ($product->seller_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $logs = InventoryLog::where('product_id', $productId)
            ->with('user')
            ->orderByDesc('created_at')
            ->paginate(50);

        return response()->json($logs);
    }

    public function lowStockAlerts()
    {
        $userId = Auth::id();
        
        $alerts = LowStockAlert::whereHas('product', function($query) use ($userId) {
                $query->where('seller_id', $userId);
            })
            ->where('is_active', true)
            ->where('current_quantity', '<=', 'threshold_quantity')
            ->with('product')
            ->orderBy('current_quantity')
            ->get();

        return response()->json(['alerts' => $alerts]);
    }

    public function setLowStockThreshold(Request $request, $productId)
    {
        $request->validate([
            'threshold' => 'required|integer|min:0',
        ]);

        $product = Product::findOrFail($productId);
        
        if ($product->seller_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $alert = LowStockAlert::updateOrCreate(
            ['product_id' => $productId],
            [
                'threshold_quantity' => $request->threshold,
                'current_quantity' => $product->inventory_count ?? 0,
                'is_active' => true,
            ]
        );

        return response()->json(['alert' => $alert]);
    }

    public function importProducts(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,xlsx,xls|max:10240',
        ]);

        $userId = Auth::id();
        $file = $request->file('file');
        $filename = time() . '_' . $file->getClientOriginalName();
        $path = $file->storeAs('imports', $filename, 'local');

        $import = ProductImport::create([
            'user_id' => $userId,
            'filename' => $filename,
            'total_rows' => 0,
            'processed_rows' => 0,
            'successful_rows' => 0,
            'failed_rows' => 0,
            'status' => 'pending',
        ]);

        // TODO: Dispatch job to process import
        // \App\Jobs\ProcessProductImport::dispatch($import->id, $path);

        return response()->json([
            'import' => $import,
            'message' => 'Import started. You will be notified when complete.',
        ], 201);
    }

    public function importStatus($importId)
    {
        $import = ProductImport::findOrFail($importId);
        
        if ($import->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json(['import' => $import]);
    }

    public function exportProducts(Request $request)
    {
        $userId = Auth::id();
        
        $products = Product::where('seller_id', $userId)
            ->with(['category', 'images'])
            ->get();

        $data = $products->map(function($product) {
            return [
                'ID' => $product->id,
                'Name' => $product->name,
                'Description' => $product->description,
                'SKU' => $product->sku,
                'Price' => $product->price,
                'Category' => $product->category->name ?? '',
                'Brand' => $product->brand,
                'Condition' => $product->condition,
                'Inventory' => $product->inventory_count,
                'Status' => $product->status,
            ];
        });

        $filename = 'products_export_' . time() . '.csv';
        
        // Create CSV
        $csv = fopen('php://temp', 'w');
        
        // Headers
        fputcsv($csv, array_keys($data->first()));
        
        // Data
        foreach ($data as $row) {
            fputcsv($csv, $row);
        }
        
        rewind($csv);
        $csvContent = stream_get_contents($csv);
        fclose($csv);

        return response($csvContent)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    public function bulkUpdatePrices(Request $request)
    {
        $request->validate([
            'products' => 'required|array',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.price' => 'required|numeric|min:0',
        ]);

        $userId = Auth::id();
        $updated = 0;

        foreach ($request->products as $productData) {
            $product = Product::where('id', $productData['product_id'])
                ->where('seller_id', $userId)
                ->first();

            if ($product) {
                $product->update(['price' => $productData['price']]);
                $updated++;
            }
        }

        return response()->json([
            'message' => "$updated products updated successfully",
        ]);
    }

    protected function checkLowStock($product)
    {
        $alert = LowStockAlert::where('product_id', $product->id)
            ->where('is_active', true)
            ->first();

        if ($alert) {
            $alert->update(['current_quantity' => $product->inventory_count]);

            if ($alert->shouldAlert()) {
                // TODO: Send low stock notification
                $alert->update(['last_alerted_at' => now()]);
            }
        }
    }
}
