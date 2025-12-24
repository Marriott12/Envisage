<?php

namespace App\Http\Controllers;

use App\Services\PredictiveAnalyticsService;
use Illuminate\Http\Request;

class PredictiveController extends Controller
{
    protected $predictiveService;

    public function __construct(PredictiveAnalyticsService $predictiveService)
    {
        $this->predictiveService = $predictiveService;
    }

    /**
     * Forecast product demand
     */
    public function forecastDemand(Request $request, $productId)
    {
        $days = $request->input('days', 30);

        $forecast = $this->predictiveService->forecastDemand($productId, $days);

        return response()->json([
            'success' => true,
            'data' => $forecast,
        ]);
    }

    /**
     * Predict customer churn
     */
    public function predictChurn(Request $request)
    {
        $userId = $request->input('user_id', $request->user()->id);

        $churnPrediction = $this->predictiveService->predictChurn($userId);

        return response()->json([
            'success' => true,
            'data' => $churnPrediction,
        ]);
    }

    /**
     * Predict customer lifetime value
     */
    public function predictCLV(Request $request)
    {
        $userId = $request->input('user_id', $request->user()->id);

        $clv = $this->predictiveService->predictCLV($userId);

        return response()->json([
            'success' => true,
            'data' => $clv,
        ]);
    }

    /**
     * Forecast sales
     */
    public function forecastSales(Request $request)
    {
        $days = $request->input('days', 30);
        $granularity = $request->input('granularity', 'daily');

        $forecast = $this->predictiveService->forecastSales($days, $granularity);

        return response()->json([
            'success' => true,
            'data' => $forecast,
        ]);
    }

    /**
     * Detect trending products
     */
    public function trendingProducts(Request $request)
    {
        $limit = $request->input('limit', 20);

        $trending = $this->predictiveService->detectTrendingProducts($limit);

        return response()->json([
            'success' => true,
            'data' => $trending,
        ]);
    }

    /**
     * Predict next purchase
     */
    public function predictNextPurchase(Request $request)
    {
        $userId = $request->input('user_id', $request->user()->id);

        $prediction = $this->predictiveService->predictNextPurchase($userId);

        return response()->json([
            'success' => true,
            'data' => $prediction,
        ]);
    }

    /**
     * Generate business insights
     */
    public function insights(Request $request)
    {
        $timeframe = $request->input('timeframe', 30);

        $insights = $this->predictiveService->generateInsights($timeframe);

        return response()->json([
            'success' => true,
            'data' => $insights,
        ]);
    }

    /**
     * Inventory optimization
     */
    public function optimizeInventory(Request $request)
    {
        $productId = $request->input('product_id');
        $days = $request->input('days', 30);

        if ($productId) {
            $forecast = $this->predictiveService->forecastDemand($productId, $days);
            
            // Calculate reorder point from forecast
            $avgDemand = collect($forecast['forecast'])->avg('predicted_demand');
            $maxDemand = collect($forecast['forecast'])->max('predicted_demand');
            $recommendation = [
                'product_id' => $productId,
                'forecast_period' => $days,
                'average_daily_demand' => round($avgDemand, 2),
                'peak_demand' => round($maxDemand, 2),
                'recommended_stock' => ceil($maxDemand * 1.2), // 20% safety buffer
                'reorder_point' => ceil($avgDemand * 7), // Week's worth
            ];
        } else {
            // Get recommendations for all products
            $recommendation = [];
        }

        return response()->json([
            'success' => true,
            'data' => $recommendation,
        ]);
    }
}
