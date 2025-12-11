<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Jobs\UpdateProductCache;

class BulkOperationsController extends Controller
{
    /**
     * Bulk update products (seller only)
     */
    public function bulkUpdateProducts(Request $request)
    {
        $request->validate([
            'product_ids' => 'required|array',
            'product_ids.*' => 'integer',
            'updates' => 'required|array',
        ]);

        $user = $request->user();

        // Verify user owns all products
        $products = Product::whereIn('id', $request->product_ids)
            ->where('seller_id', $user->id)
            ->get();

        if ($products->count() !== count($request->product_ids)) {
            return response()->json([
                'message' => 'Some products not found or unauthorized'
            ], 403);
        }

        $allowedFields = ['price', 'stock', 'status', 'is_featured'];
        $updates = array_intersect_key($request->updates, array_flip($allowedFields));

        DB::transaction(function () use ($products, $updates) {
            foreach ($products as $product) {
                $product->update($updates);
                UpdateProductCache::dispatch($product->id);
            }
        });

        return response()->json([
            'message' => 'Products updated successfully',
            'updated_count' => $products->count()
        ]);
    }

    /**
     * Bulk delete products (seller only)
     */
    public function bulkDeleteProducts(Request $request)
    {
        $request->validate([
            'product_ids' => 'required|array',
            'product_ids.*' => 'integer',
        ]);

        $user = $request->user();

        $deleted = Product::whereIn('id', $request->product_ids)
            ->where('seller_id', $user->id)
            ->delete();

        return response()->json([
            'message' => 'Products deleted successfully',
            'deleted_count' => $deleted
        ]);
    }

    /**
     * Bulk export products (seller only)
     */
    public function bulkExportProducts(Request $request)
    {
        $user = $request->user();

        $products = Product::where('seller_id', $user->id)
            ->with(['category', 'images'])
            ->get();

        $csv = "ID,Name,SKU,Price,Stock,Status,Category,Created\n";
        
        foreach ($products as $product) {
            $csv .= sprintf(
                "%d,\"%s\",\"%s\",%s,%d,%s,\"%s\",%s\n",
                $product->id,
                str_replace('"', '""', $product->name),
                $product->sku ?? '',
                $product->price,
                $product->stock,
                $product->status,
                $product->category->name ?? '',
                $product->created_at->format('Y-m-d')
            );
        }

        return response($csv, 200)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="products_export_' . time() . '.csv"');
    }

    /**
     * Bulk import products (seller only)
     */
    public function bulkImportProducts(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt',
        ]);

        $user = $request->user();
        $file = $request->file('file');
        $csv = array_map('str_getcsv', file($file));
        $headers = array_shift($csv);

        $imported = 0;
        $errors = [];

        DB::transaction(function () use ($csv, $headers, $user, &$imported, &$errors) {
            foreach ($csv as $index => $row) {
                try {
                    $data = array_combine($headers, $row);
                    
                    Product::create([
                        'seller_id' => $user->id,
                        'name' => $data['Name'],
                        'sku' => $data['SKU'] ?? null,
                        'price' => $data['Price'],
                        'stock' => $data['Stock'] ?? 0,
                        'description' => $data['Description'] ?? '',
                        'status' => $data['Status'] ?? 'active',
                    ]);
                    
                    $imported++;
                } catch (\Exception $e) {
                    $errors[] = "Row " . ($index + 2) . ": " . $e->getMessage();
                }
            }
        });

        return response()->json([
            'message' => 'Import completed',
            'imported_count' => $imported,
            'errors' => $errors
        ]);
    }

    /**
     * Bulk update order status (seller/admin)
     */
    public function bulkUpdateOrders(Request $request)
    {
        $request->validate([
            'order_ids' => 'required|array',
            'order_ids.*' => 'integer',
            'status' => 'required|string|in:pending,processing,shipped,delivered,cancelled',
        ]);

        $user = $request->user();

        $query = Order::whereIn('id', $request->order_ids);
        
        if ($user->role !== 'admin') {
            $query->where('seller_id', $user->id);
        }

        $updated = $query->update([
            'status' => $request->status,
            'updated_at' => now(),
        ]);

        return response()->json([
            'message' => 'Orders updated successfully',
            'updated_count' => $updated
        ]);
    }
}
