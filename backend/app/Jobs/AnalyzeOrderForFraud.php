<?php

namespace App\Jobs;

use App\Models\Order;
use App\Models\FraudScore;
use App\Services\FraudDetectionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class AnalyzeOrderForFraud implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $orderId;

    /**
     * Create a new job instance.
     *
     * @param int $orderId
     * @return void
     */
    public function __construct($orderId)
    {
        $this->orderId = $orderId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(FraudDetectionService $fraudService)
    {
        try {
            $order = Order::find($this->orderId);
            
            if (!$order) {
                Log::warning('Order not found for fraud analysis', [
                    'order_id' => $this->orderId
                ]);
                return;
            }

            // Check if already analyzed
            $existing = FraudScore::where('order_id', $order->id)->first();
            if ($existing) {
                Log::info('Order already analyzed, skipping', [
                    'order_id' => $order->id
                ]);
                return;
            }

            // Analyze order
            $fraudScore = $fraudService->analyzeOrder($order);
            
            Log::info('Order analyzed for fraud', [
                'order_id' => $order->id,
                'risk_level' => $fraudScore->risk_level,
                'score' => $fraudScore->total_score,
                'status' => $fraudScore->status
            ]);

        } catch (\Exception $e) {
            Log::error('Error analyzing order for fraud', [
                'order_id' => $this->orderId,
                'error' => $e->getMessage()
            ]);
        }
    }
}
