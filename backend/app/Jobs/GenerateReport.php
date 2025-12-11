<?php

namespace App\Jobs;

use App\Models\Report;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class GenerateReport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $report;

    public function __construct(Report $report)
    {
        $this->report = $report;
    }

    public function handle()
    {
        // Generate report based on type
        $data = $this->generateReportData();

        // Save report file
        $filename = "reports/{$this->report->id}_{$this->report->type}_" . time() . ".{$this->report->format}";
        
        if ($this->report->format === 'json') {
            $content = json_encode($data, JSON_PRETTY_PRINT);
        } elseif ($this->report->format === 'csv') {
            $content = $this->arrayToCsv($data);
        } else {
            $content = json_encode($data);
        }

        Storage::put($filename, $content);

        // Update report record
        $this->report->update([
            'file_path' => $filename,
            'file_size' => strlen($content),
            'data' => $data,
            'status' => 'completed',
        ]);
    }

    protected function generateReportData()
    {
        switch ($this->report->type) {
            case 'sales':
                return $this->generateSalesReport();
            case 'revenue':
                return $this->generateRevenueReport();
            case 'users':
                return $this->generateUsersReport();
            case 'products':
                return $this->generateProductsReport();
            default:
                return [];
        }
    }

    protected function generateSalesReport()
    {
        $orders = \App\Models\Order::whereBetween('created_at', [
            $this->report->start_date,
            $this->report->end_date
        ])->get();

        return [
            'total_orders' => $orders->count(),
            'total_revenue' => $orders->sum('total_amount'),
            'average_order_value' => $orders->avg('total_amount'),
            'orders_by_status' => $orders->groupBy('status')->map->count(),
        ];
    }

    protected function generateRevenueReport()
    {
        // Implement revenue report logic
        return [];
    }

    protected function generateUsersReport()
    {
        // Implement users report logic
        return [];
    }

    protected function generateProductsReport()
    {
        // Implement products report logic
        return [];
    }

    protected function arrayToCsv($data)
    {
        // Convert array to CSV format
        $csv = '';
        if (!empty($data) && is_array($data)) {
            $headers = array_keys($data[0] ?? $data);
            $csv .= implode(',', $headers) . "\n";
            
            foreach ($data as $row) {
                $csv .= implode(',', array_values($row)) . "\n";
            }
        }
        return $csv;
    }
}
