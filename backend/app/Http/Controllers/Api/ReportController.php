<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReportController extends Controller
{
    /**
     * Get all generated reports
     */
    public function index()
    {
        // Mock report data - in production, store in database
        $reports = [
            [
                'id' => 1,
                'name' => 'Sales Summary Report',
                'type' => 'sales',
                'date_range' => '2024-11-01 to 2024-11-30',
                'generated_at' => '2024-12-01 10:30:00',
                'file_size' => '245 KB',
            ],
            [
                'id' => 2,
                'name' => 'User Activity Report',
                'type' => 'users',
                'date_range' => '2024-11-01 to 2024-11-30',
                'generated_at' => '2024-12-01 11:45:00',
                'file_size' => '189 KB',
            ],
        ];

        return response()->json([
            'success' => true,
            'data' => $reports,
        ]);
    }

    /**
     * Generate a new report
     */
    public function generate(Request $request)
    {
        $request->validate([
            'type' => 'required|in:sales,users,products,revenue,customers,inventory',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $type = $request->input('type');
        $startDate = Carbon::parse($request->input('start_date'));
        $endDate = Carbon::parse($request->input('end_date'));

        // Generate report based on type
        $reportData = $this->generateReportData($type, $startDate, $endDate);

        // In production, save to database and generate PDF
        $report = [
            'id' => rand(100, 999),
            'name' => ucfirst($type) . ' Report',
            'type' => $type,
            'date_range' => $startDate->format('Y-m-d') . ' to ' . $endDate->format('Y-m-d'),
            'generated_at' => now()->format('Y-m-d H:i:s'),
            'file_size' => rand(100, 500) . ' KB',
            'data' => $reportData,
        ];

        return response()->json([
            'success' => true,
            'message' => 'Report generated successfully',
            'data' => $report,
        ]);
    }

    /**
     * Download a report
     */
    public function download($id)
    {
        // In production, retrieve from storage and return file
        return response()->json([
            'success' => true,
            'message' => 'Report download initiated',
            'download_url' => '/storage/reports/report_' . $id . '.pdf',
        ]);
    }

    /**
     * Generate report data based on type
     */
    private function generateReportData($type, $startDate, $endDate)
    {
        switch ($type) {
            case 'sales':
                return [
                    'total_sales' => DB::table('orders')
                        ->whereBetween('created_at', [$startDate, $endDate])
                        ->sum('total_amount'),
                    'order_count' => DB::table('orders')
                        ->whereBetween('created_at', [$startDate, $endDate])
                        ->count(),
                ];
            case 'users':
                return [
                    'new_users' => DB::table('users')
                        ->whereBetween('created_at', [$startDate, $endDate])
                        ->count(),
                    'active_users' => DB::table('users')
                        ->where('status', 'active')
                        ->count(),
                ];
            case 'products':
                return [
                    'total_products' => DB::table('products')->count(),
                    'low_stock' => DB::table('products')
                        ->whereRaw('stock <= low_stock_threshold')
                        ->count(),
                ];
            default:
                return [];
        }
    }
}
