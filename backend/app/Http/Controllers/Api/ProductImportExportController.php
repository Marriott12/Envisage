<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductImportExportController extends Controller
{
    /**
     * Download import template
     * GET /api/import/template
     */
    public function downloadTemplate(Request $request)
    {
        $type = $request->get('type', 'products');

        $templates = [
            'products' => [
                'headers' => ['name', 'description', 'price', 'stock', 'sku', 'category_id', 'brand', 'is_active'],
                'sample' => ['Sample Product', 'Product description here', '99.99', '100', 'SKU-001', '1', 'Brand Name', '1'],
            ],
            'orders' => [
                'headers' => ['order_id', 'status', 'total_amount', 'customer_email', 'shipping_address'],
                'sample' => ['ORD-001', 'pending', '150.00', 'customer@example.com', '123 Main St'],
            ],
        ];

        $template = $templates[$type] ?? $templates['products'];

        $csv = implode(',', $template['headers']) . "\n";
        $csv .= implode(',', $template['sample']) . "\n";

        return response($csv)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="' . $type . '_import_template.csv"');
    }

    /**
     * Validate import file
     * POST /api/import/validate
     */
    public function validateImport(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:csv,txt|max:10240', // 10MB max
            'type' => 'required|in:products,orders,customers',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $file = $request->file('file');
        $type = $request->type;

        try {
            $data = $this->parseCsv($file);
            
            $validation = $this->validateCsvData($data, $type);

            return response()->json([
                'success' => true,
                'message' => 'File validated successfully',
                'stats' => [
                    'total_rows' => count($data),
                    'valid_rows' => $validation['valid_count'],
                    'invalid_rows' => $validation['error_count'],
                ],
                'errors' => $validation['errors'],
                'preview' => array_slice($data, 0, 5), // First 5 rows
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * Import products from CSV
     * POST /api/import/products
     */
    public function importProducts(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:csv,txt|max:10240',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $file = $request->file('file');
        $user = $request->user();

        try {
            $data = $this->parseCsv($file);
            
            $imported = 0;
            $skipped = 0;
            $errors = [];

            foreach ($data as $index => $row) {
                try {
                    Product::create([
                        'name' => $row['name'] ?? 'Unnamed Product',
                        'description' => $row['description'] ?? '',
                        'price' => $row['price'] ?? 0,
                        'stock' => $row['stock'] ?? 0,
                        'sku' => $row['sku'] ?? 'SKU-' . Str::random(8),
                        'category_id' => $row['category_id'] ?? null,
                        'brand' => $row['brand'] ?? null,
                        'seller_id' => $user->id,
                        'is_active' => isset($row['is_active']) ? (bool)$row['is_active'] : true,
                    ]);
                    $imported++;
                } catch (\Exception $e) {
                    $skipped++;
                    $errors[] = "Row " . ($index + 2) . ": " . $e->getMessage();
                }
            }

            return response()->json([
                'success' => true,
                'message' => "{$imported} products imported successfully",
                'stats' => [
                    'imported' => $imported,
                    'skipped' => $skipped,
                    'total' => count($data),
                ],
                'errors' => $errors,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Import failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export products to CSV
     * POST /api/export/products
     */
    public function exportProducts(Request $request)
    {
        $user = $request->user();
        
        $query = Product::query();

        // Filters
        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->has('seller_id')) {
            $query->where('seller_id', $request->seller_id);
        } elseif (!$user->hasRole('admin')) {
            // Non-admin users can only export their own products
            $query->where('seller_id', $user->id);
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $products = $query->get();

        $csv = "ID,Name,Description,Price,Stock,SKU,Category ID,Brand,Is Active,Created At\n";

        foreach ($products as $product) {
            $csv .= implode(',', [
                $product->id,
                '"' . str_replace('"', '""', $product->name) . '"',
                '"' . str_replace('"', '""', $product->description ?? '') . '"',
                $product->price,
                $product->stock,
                $product->sku,
                $product->category_id ?? '',
                $product->brand ?? '',
                $product->is_active ? '1' : '0',
                $product->created_at->format('Y-m-d H:i:s'),
            ]) . "\n";
        }

        $filename = 'products_export_' . now()->format('Y-m-d_His') . '.csv';

        return response($csv)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    /**
     * Export orders to CSV
     * POST /api/export/orders
     */
    public function exportOrders(Request $request)
    {
        $user = $request->user();
        
        $query = Order::with(['user', 'items']);

        // Filters
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if (!$user->hasRole('admin')) {
            $query->where('user_id', $user->id);
        }

        $orders = $query->get();

        $csv = "Order ID,Customer Name,Customer Email,Status,Items Count,Total Amount,Currency,Created At\n";

        foreach ($orders as $order) {
            $csv .= implode(',', [
                $order->id,
                '"' . str_replace('"', '""', $order->user->name ?? 'N/A') . '"',
                $order->user->email ?? 'N/A',
                $order->status,
                $order->items->count(),
                $order->total_amount,
                $order->currency ?? 'USD',
                $order->created_at->format('Y-m-d H:i:s'),
            ]) . "\n";
        }

        $filename = 'orders_export_' . now()->format('Y-m-d_His') . '.csv';

        return response($csv)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    /**
     * Export customers to CSV (Admin only)
     * POST /api/export/customers
     */
    public function exportCustomers(Request $request)
    {
        $query = User::query();

        if ($request->has('role')) {
            $query->whereHas('roles', function($q) use ($request) {
                $q->where('name', $request->role);
            });
        }

        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        $users = $query->get();

        $csv = "ID,Name,Email,Phone,Role,Created At,Last Login\n";

        foreach ($users as $user) {
            $csv .= implode(',', [
                $user->id,
                '"' . str_replace('"', '""', $user->name) . '"',
                $user->email,
                $user->phone ?? '',
                $user->roles->pluck('name')->implode(';'),
                $user->created_at->format('Y-m-d H:i:s'),
                $user->last_login_at ? $user->last_login_at->format('Y-m-d H:i:s') : '',
            ]) . "\n";
        }

        $filename = 'customers_export_' . now()->format('Y-m-d_His') . '.csv';

        return response($csv)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    /**
     * Get import/export status
     * GET /api/import/status/{id}
     */
    public function getStatus($id)
    {
        // In production, track import jobs in database
        return response()->json([
            'success' => true,
            'status' => 'completed',
            'progress' => 100,
        ]);
    }

    /**
     * Helper: Parse CSV file
     */
    private function parseCsv($file)
    {
        $rows = [];
        $headers = [];

        if (($handle = fopen($file->getRealPath(), 'r')) !== false) {
            $lineNumber = 0;
            
            while (($data = fgetcsv($handle, 1000, ',')) !== false) {
                if ($lineNumber === 0) {
                    $headers = array_map('trim', $data);
                } else {
                    $row = [];
                    foreach ($data as $index => $value) {
                        $key = $headers[$index] ?? 'column_' . $index;
                        $row[$key] = trim($value);
                    }
                    $rows[] = $row;
                }
                $lineNumber++;
            }
            
            fclose($handle);
        }

        return $rows;
    }

    /**
     * Helper: Validate CSV data
     */
    private function validateCsvData($data, $type)
    {
        $validCount = 0;
        $errorCount = 0;
        $errors = [];

        foreach ($data as $index => $row) {
            $rowErrors = [];

            if ($type === 'products') {
                if (empty($row['name'])) {
                    $rowErrors[] = 'Name is required';
                }
                if (!isset($row['price']) || !is_numeric($row['price'])) {
                    $rowErrors[] = 'Valid price is required';
                }
            }

            if (empty($rowErrors)) {
                $validCount++;
            } else {
                $errorCount++;
                $errors[] = [
                    'row' => $index + 2,
                    'errors' => $rowErrors,
                ];
            }
        }

        return [
            'valid_count' => $validCount,
            'error_count' => $errorCount,
            'errors' => $errors,
        ];
    }
}
