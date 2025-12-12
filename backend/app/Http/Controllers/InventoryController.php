<?php

namespace App\Http\Controllers;

use App\Services\InventoryForecastingService;
use App\Models\StockForecast;
use App\Models\ReorderPoint;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
use App\Models\SupplierPerformance;
use App\Models\StockAlert;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class InventoryController extends Controller
{
    protected $service;

    public function __construct(InventoryForecastingService $service)
    {
        $this->service = $service;
    }

    /**
     * Get complete inventory analytics
     * GET /api/inventory/analytics
     */
    public function getAnalytics(Request $request)
    {
        $days = $request->input('days', 30);
        $analytics = $this->service->getAnalytics($days);
        $healthScore = $this->service->getInventoryHealthScore();

        return response()->json([
            'health_score' => $healthScore,
            'analytics' => $analytics,
        ]);
    }

    /**
     * Get inventory health score
     * GET /api/inventory/health-score
     */
    public function getHealthScore()
    {
        $score = $this->service->getInventoryHealthScore();
        return response()->json(['health_score' => $score]);
    }

    // ==================== FORECASTING ====================

    /**
     * Generate forecast for product
     * POST /api/inventory/forecasts/generate/{productId}
     */
    public function generateForecast(Request $request, $productId)
    {
        $validator = Validator::make($request->all(), [
            'days' => 'integer|min:1|max:90',
            'algorithm' => 'string|in:auto,moving_average,exponential_smoothing,trend_seasonal',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $days = $request->input('days', 30);
        $algorithm = $request->input('algorithm', 'auto');

        $forecasts = $this->service->generateForecast($productId, $days, $algorithm);

        return response()->json([
            'forecasts_generated' => count($forecasts),
            'forecasts' => $forecasts,
        ]);
    }

    /**
     * Get forecasts for product
     * GET /api/inventory/forecasts/{productId}
     */
    public function getForecasts(Request $request, $productId)
    {
        $days = $request->input('days', 30);
        $startDate = Carbon::now();
        $endDate = Carbon::now()->addDays($days);

        $forecasts = StockForecast::where('product_id', $productId)
            ->dateRange($startDate, $endDate)
            ->orderBy('forecast_date')
            ->get();

        return response()->json($forecasts);
    }

    /**
     * Get forecast accuracy report
     * GET /api/inventory/forecasts/accuracy-report
     */
    public function getForecastAccuracy(Request $request)
    {
        $days = $request->input('days', 30);
        $report = $this->service->getForecastAccuracyReport($days);
        return response()->json($report);
    }

    /**
     * Bulk generate forecasts
     * POST /api/inventory/forecasts/generate-all
     */
    public function generateAllForecasts(Request $request)
    {
        $days = $request->input('days', 30);
        $totalGenerated = $this->service->generateAllForecasts($days);

        return response()->json([
            'forecasts_generated' => $totalGenerated,
        ]);
    }

    // ==================== REORDER POINTS ====================

    /**
     * List reorder points
     * GET /api/inventory/reorder-points
     */
    public function listReorderPoints(Request $request)
    {
        $query = ReorderPoint::with('product', 'supplier');

        if ($request->has('needs_reorder')) {
            $query->needsTrigger();
        }

        if ($request->has('active_only')) {
            $query->active();
        }

        $reorderPoints = $query->paginate($request->input('per_page', 20));

        // Add calculated fields
        $reorderPoints->getCollection()->transform(function($rp) {
            $rp->needs_reorder = $rp->needsReorder();
            $rp->days_of_stock = $rp->getDaysOfStockRemaining();
            $rp->stockout_risk = $rp->getStockoutRisk();
            $rp->recommended_quantity = $rp->getRecommendedOrderQuantity();
            return $rp;
        });

        return response()->json($reorderPoints);
    }

    /**
     * Get reorder point details
     * GET /api/inventory/reorder-points/{id}
     */
    public function getReorderPoint($id)
    {
        $rp = ReorderPoint::with('product', 'supplier')->findOrFail($id);

        return response()->json([
            'reorder_point' => $rp,
            'needs_reorder' => $rp->needsReorder(),
            'days_of_stock' => $rp->getDaysOfStockRemaining(),
            'stockout_risk' => $rp->getStockoutRisk(),
            'recommended_quantity' => $rp->getRecommendedOrderQuantity(),
        ]);
    }

    /**
     * Create reorder point
     * POST /api/inventory/reorder-points
     */
    public function createReorderPoint(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'supplier_id' => 'required|exists:suppliers,id',
            'service_level' => 'numeric|min:80|max:99.9',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $serviceLevel = $request->input('service_level', 95);
        $rp = ReorderPoint::createFromProduct(
            $request->product_id,
            $request->supplier_id,
            $serviceLevel
        );

        return response()->json($rp, 201);
    }

    /**
     * Update reorder point from history
     * POST /api/inventory/reorder-points/{id}/update-from-history
     */
    public function updateReorderPointFromHistory(Request $request, $id)
    {
        $rp = ReorderPoint::findOrFail($id);
        $days = $request->input('days', 60);
        
        $rp->updateFromHistory($days);

        return response()->json($rp);
    }

    /**
     * Update reorder point from forecasts
     * POST /api/inventory/reorder-points/{id}/update-from-forecast
     */
    public function updateReorderPointFromForecast($id)
    {
        $rp = $this->service->updateReorderPointFromForecast($id);

        if (!$rp) {
            return response()->json(['error' => 'Failed to update reorder point'], 400);
        }

        return response()->json($rp);
    }

    /**
     * Update all reorder points
     * POST /api/inventory/reorder-points/update-all
     */
    public function updateAllReorderPoints()
    {
        $updated = $this->service->updateAllReorderPoints();

        return response()->json([
            'reorder_points_updated' => $updated,
        ]);
    }

    /**
     * Check reorder needs
     * POST /api/inventory/reorder-points/check-needs
     */
    public function checkReorderNeeds()
    {
        $posCreated = $this->service->checkReorderNeeds();

        return response()->json([
            'pos_created' => count($posCreated),
            'purchase_orders' => $posCreated,
        ]);
    }

    // ==================== STOCKOUT PREDICTION ====================

    /**
     * Predict stockout risk for product
     * GET /api/inventory/stockout-risk/{productId}
     */
    public function getStockoutRisk(Request $request, $productId)
    {
        $days = $request->input('days', 30);
        $risk = $this->service->predictStockoutRisk($productId, $days);

        if (!$risk) {
            return response()->json(['error' => 'Unable to calculate stockout risk'], 400);
        }

        return response()->json($risk);
    }

    /**
     * Get all products at risk
     * GET /api/inventory/stockout-risks
     */
    public function getStockoutRisks(Request $request)
    {
        $days = $request->input('days', 30);
        $minRiskLevel = $request->input('min_risk_level', 'medium');

        $risks = $this->service->getStockoutRisks($days, $minRiskLevel);

        return response()->json($risks);
    }

    // ==================== PURCHASE ORDERS ====================

    /**
     * List purchase orders
     * GET /api/inventory/purchase-orders
     */
    public function listPurchaseOrders(Request $request)
    {
        $query = PurchaseOrder::with('supplier', 'items.product');

        if ($request->has('status')) {
            $query->status($request->status);
        }

        if ($request->has('pending_only')) {
            $query->pending();
        }

        if ($request->has('overdue_only')) {
            $query->overdue();
        }

        if ($request->has('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        $orders = $query->orderBy('order_date', 'desc')
            ->paginate($request->input('per_page', 20));

        return response()->json($orders);
    }

    /**
     * Get purchase order details
     * GET /api/inventory/purchase-orders/{id}
     */
    public function getPurchaseOrder($id)
    {
        $po = PurchaseOrder::with('supplier', 'items.product', 'reorderPoint')->findOrFail($id);

        return response()->json([
            'purchase_order' => $po,
            'is_overdue' => $po->isOverdue(),
            'days_until_delivery' => $po->getDaysUntilDelivery(),
            'actual_lead_time' => $po->getActualLeadTimeDays(),
            'was_on_time' => $po->wasOnTime(),
        ]);
    }

    /**
     * Create purchase order
     * POST /api/inventory/purchase-orders
     */
    public function createPurchaseOrder(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'supplier_id' => 'required|exists:suppliers,id',
            'reorder_point_id' => 'nullable|exists:reorder_points,id',
            'expected_delivery_date' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_cost' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $po = PurchaseOrder::create([
            'supplier_id' => $request->supplier_id,
            'reorder_point_id' => $request->reorder_point_id,
            'po_number' => PurchaseOrder::generatePoNumber(),
            'order_date' => Carbon::now(),
            'expected_delivery_date' => $request->expected_delivery_date,
            'status' => PurchaseOrder::STATUS_DRAFT,
            'notes' => $request->notes,
            'created_by' => auth()->id(),
        ]);

        foreach ($request->items as $item) {
            PurchaseOrderItem::create([
                'purchase_order_id' => $po->id,
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'unit_cost' => $item['unit_cost'],
                'received_quantity' => 0,
            ]);
        }

        $po->calculateTotalCost();

        return response()->json($po->load('items.product'), 201);
    }

    /**
     * Update purchase order status
     * PUT /api/inventory/purchase-orders/{id}/status
     */
    public function updatePurchaseOrderStatus(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:sent,confirmed,cancelled',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $po = PurchaseOrder::findOrFail($id);

        switch ($request->status) {
            case 'sent':
                $po->markAsSent();
                break;
            case 'confirmed':
                $po->markAsConfirmed();
                break;
            case 'cancelled':
                $po->cancel($request->input('reason'));
                break;
        }

        return response()->json($po);
    }

    /**
     * Receive purchase order items
     * POST /api/inventory/purchase-orders/{id}/receive
     */
    public function receivePurchaseOrder(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'items' => 'required|array',
            'items.*' => 'integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $po = PurchaseOrder::findOrFail($id);
        $po->receiveItems($request->items);

        return response()->json($po->load('items.product'));
    }

    /**
     * Mark purchase order as fully received
     * POST /api/inventory/purchase-orders/{id}/mark-received
     */
    public function markPurchaseOrderReceived($id)
    {
        $po = PurchaseOrder::findOrFail($id);
        $po->markAsReceived();

        return response()->json($po);
    }

    // ==================== SUPPLIERS ====================

    /**
     * List suppliers
     * GET /api/inventory/suppliers
     */
    public function listSuppliers(Request $request)
    {
        $query = Supplier::query();

        if ($request->has('active_only')) {
            $query->active();
        }

        if ($request->has('fast_delivery')) {
            $query->fastDelivery($request->input('max_lead_days', 7));
        }

        $suppliers = $query->paginate($request->input('per_page', 20));

        // Add calculated fields
        $suppliers->getCollection()->transform(function($supplier) {
            $supplier->average_rating = $supplier->average_rating;
            $supplier->on_time_delivery_rate = $supplier->on_time_delivery_rate;
            $supplier->reliability_score = $supplier->getReliabilityScore();
            return $supplier;
        });

        return response()->json($suppliers);
    }

    /**
     * Get supplier details
     * GET /api/inventory/suppliers/{id}
     */
    public function getSupplier($id)
    {
        $supplier = Supplier::with('latestPerformance')->findOrFail($id);

        return response()->json([
            'supplier' => $supplier,
            'average_rating' => $supplier->average_rating,
            'on_time_delivery_rate' => $supplier->on_time_delivery_rate,
            'actual_lead_time_days' => $supplier->actual_lead_time_days,
            'reliability_score' => $supplier->getReliabilityScore(),
            'meets_standards' => $supplier->meetsPerformanceStandards(),
        ]);
    }

    /**
     * Create supplier
     * POST /api/inventory/suppliers
     */
    public function createSupplier(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'country' => 'nullable|string|max:100',
            'lead_time_days' => 'required|integer|min:1',
            'minimum_order_quantity' => 'nullable|integer|min:1',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $supplier = Supplier::create($request->all());

        return response()->json($supplier, 201);
    }

    /**
     * Update supplier
     * PUT /api/inventory/suppliers/{id}
     */
    public function updateSupplier(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'string|max:255',
            'email' => 'email|max:255',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'country' => 'nullable|string|max:100',
            'lead_time_days' => 'integer|min:1',
            'minimum_order_quantity' => 'nullable|integer|min:1',
            'is_active' => 'boolean',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $supplier = Supplier::findOrFail($id);
        $supplier->update($request->all());

        return response()->json($supplier);
    }

    /**
     * Recommend supplier for product
     * GET /api/inventory/suppliers/recommend/{productId}
     */
    public function recommendSupplier(Request $request, $productId)
    {
        $quantity = $request->input('quantity', 100);
        $supplier = $this->service->recommendSupplier($productId, $quantity);

        if (!$supplier) {
            return response()->json(['error' => 'No suitable supplier found'], 404);
        }

        return response()->json($supplier);
    }

    // ==================== SUPPLIER PERFORMANCE ====================

    /**
     * List supplier performance records
     * GET /api/inventory/supplier-performance
     */
    public function listSupplierPerformance(Request $request)
    {
        $query = SupplierPerformance::with('supplier');

        if ($request->has('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        if ($request->has('recent_months')) {
            $query->recent($request->recent_months);
        }

        if ($request->has('high_performers')) {
            $query->highPerformers();
        }

        if ($request->has('poor_performers')) {
            $query->poorPerformers();
        }

        $performance = $query->orderBy('evaluation_period_end', 'desc')
            ->paginate($request->input('per_page', 20));

        return response()->json($performance);
    }

    /**
     * Evaluate supplier performance
     * POST /api/inventory/supplier-performance/evaluate/{supplierId}
     */
    public function evaluateSupplier(Request $request, $supplierId)
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $performance = $this->service->evaluateSupplierPerformance(
            $supplierId,
            $request->start_date,
            $request->end_date
        );

        if (!$performance) {
            return response()->json(['error' => 'No data available for evaluation'], 400);
        }

        return response()->json($performance);
    }

    /**
     * Evaluate all suppliers
     * POST /api/inventory/supplier-performance/evaluate-all
     */
    public function evaluateAllSuppliers(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $evaluated = $this->service->evaluateAllSuppliers($startDate, $endDate);

        return response()->json([
            'suppliers_evaluated' => $evaluated,
        ]);
    }

    // ==================== STOCK ALERTS ====================

    /**
     * List stock alerts
     * GET /api/inventory/alerts
     */
    public function listStockAlerts(Request $request)
    {
        $query = StockAlert::with('product');

        if ($request->has('unresolved_only')) {
            $query->unresolved();
        }

        if ($request->has('alert_type')) {
            $query->type($request->alert_type);
        }

        if ($request->has('severity')) {
            $query->severity($request->severity);
        }

        if ($request->has('critical_only')) {
            $query->critical();
        }

        if ($request->has('high_priority')) {
            $query->highPriority();
        }

        $alerts = $query->orderBy('severity', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 20));

        return response()->json($alerts);
    }

    /**
     * Resolve alert
     * POST /api/inventory/alerts/{id}/resolve
     */
    public function resolveAlert(Request $request, $id)
    {
        $alert = StockAlert::findOrFail($id);
        $alert->resolve(auth()->id(), $request->input('notes'));

        return response()->json($alert);
    }

    /**
     * Generate all alerts
     * POST /api/inventory/alerts/generate
     */
    public function generateAlerts()
    {
        $alertsCreated = $this->service->generateStockAlerts();

        return response()->json([
            'alerts_created' => $alertsCreated,
        ]);
    }

    /**
     * Auto-resolve invalid alerts
     * POST /api/inventory/alerts/auto-resolve
     */
    public function autoResolveAlerts()
    {
        $alertsResolved = $this->service->autoResolveAlerts();

        return response()->json([
            'alerts_resolved' => $alertsResolved,
        ]);
    }

    // ==================== OPTIMIZATION ====================

    /**
     * Run complete inventory optimization
     * POST /api/inventory/optimize
     */
    public function runOptimization()
    {
        $results = $this->service->runCompleteOptimization();

        return response()->json($results);
    }
}
