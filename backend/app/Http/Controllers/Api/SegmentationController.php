<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\CustomerSegment;
use App\Models\RfmScore;
use App\Models\ChurnPrediction;
use App\Models\CustomerLifetimeValue;
use App\Models\NextPurchasePrediction;
use App\Services\SegmentationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SegmentationController extends Controller
{
    protected $segmentationService;

    public function __construct(SegmentationService $segmentationService)
    {
        $this->segmentationService = $segmentationService;
    }

    /**
     * Get customer profile with all segmentation data
     * GET /api/segmentation/customer/{userId}
     */
    public function getCustomerProfile($userId)
    {
        $profile = $this->segmentationService->getCustomerProfile($userId);

        return response()->json([
            'success' => true,
            'profile' => $profile
        ]);
    }

    /**
     * Get segmentation analytics
     * GET /api/segmentation/analytics
     */
    public function getAnalytics()
    {
        $analytics = $this->segmentationService->getAnalytics();

        return response()->json([
            'success' => true,
            'analytics' => $analytics
        ]);
    }

    /**
     * Calculate RFM score for user
     * POST /api/segmentation/rfm/calculate/{userId}
     */
    public function calculateRfm($userId)
    {
        $rfmScore = RfmScore::calculateForUser($userId);

        return response()->json([
            'success' => true,
            'rfm_score' => $rfmScore
        ]);
    }

    /**
     * Get RFM scores
     * GET /api/segmentation/rfm
     */
    public function getRfmScores(Request $request)
    {
        $query = RfmScore::with('user');

        if ($request->has('segment')) {
            $query->bySegment($request->segment);
        }

        if ($request->boolean('high_value_only')) {
            $query->highValue();
        }

        $perPage = $request->get('per_page', 50);
        $scores = $query->orderBy('monetary_value', 'desc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'rfm_scores' => $scores
        ]);
    }

    /**
     * Get RFM segment distribution
     * GET /api/segmentation/rfm/distribution
     */
    public function getRfmDistribution()
    {
        $distribution = RfmScore::getSegmentDistribution();

        return response()->json([
            'success' => true,
            'distribution' => $distribution
        ]);
    }

    /**
     * Predict churn for user
     * POST /api/segmentation/churn/predict/{userId}
     */
    public function predictChurn($userId)
    {
        $prediction = ChurnPrediction::predictForUser($userId);

        return response()->json([
            'success' => true,
            'churn_prediction' => $prediction
        ]);
    }

    /**
     * Get churn predictions
     * GET /api/segmentation/churn
     */
    public function getChurnPredictions(Request $request)
    {
        $query = ChurnPrediction::with('user');

        if ($request->has('risk')) {
            $query->byRisk($request->risk);
        }

        if ($request->boolean('high_risk_only')) {
            $query->highRisk();
        }

        if ($request->boolean('needs_intervention')) {
            $query->needsIntervention();
        }

        $perPage = $request->get('per_page', 50);
        $predictions = $query->orderBy('churn_probability', 'desc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'churn_predictions' => $predictions
        ]);
    }

    /**
     * Trigger churn interventions
     * POST /api/segmentation/churn/trigger-interventions
     */
    public function triggerChurnInterventions()
    {
        $triggered = $this->segmentationService->triggerChurnInterventions();

        return response()->json([
            'success' => true,
            'message' => "Triggered {$triggered} churn interventions"
        ]);
    }

    /**
     * Calculate CLV for user
     * POST /api/segmentation/clv/calculate/{userId}
     */
    public function calculateClv($userId)
    {
        $clv = CustomerLifetimeValue::calculateForUser($userId);

        return response()->json([
            'success' => true,
            'lifetime_value' => $clv
        ]);
    }

    /**
     * Get CLV data
     * GET /api/segmentation/clv
     */
    public function getClvData(Request $request)
    {
        $query = CustomerLifetimeValue::with('user');

        if ($request->has('tier')) {
            $query->byTier($request->tier);
        }

        if ($request->boolean('high_value_only')) {
            $query->highValue();
        }

        $perPage = $request->get('per_page', 50);
        $clvData = $query->orderBy('predicted_value', 'desc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'lifetime_values' => $clvData
        ]);
    }

    /**
     * Get CLV statistics
     * GET /api/segmentation/clv/statistics
     */
    public function getClvStatistics()
    {
        $stats = CustomerLifetimeValue::getStatistics();

        return response()->json([
            'success' => true,
            'statistics' => $stats
        ]);
    }

    /**
     * Predict next purchase for user
     * POST /api/segmentation/next-purchase/predict/{userId}
     */
    public function predictNextPurchase($userId)
    {
        $prediction = NextPurchasePrediction::predictForUser($userId);

        return response()->json([
            'success' => true,
            'next_purchase_prediction' => $prediction
        ]);
    }

    /**
     * Get next purchase predictions
     * GET /api/segmentation/next-purchase
     */
    public function getNextPurchasePredictions(Request $request)
    {
        $query = NextPurchasePrediction::with('user');

        $days = $request->get('days', 7);
        $query->upcoming($days);

        if ($request->boolean('high_confidence_only')) {
            $query->highConfidence();
        }

        if ($request->boolean('not_notified')) {
            $query->notNotified();
        }

        $perPage = $request->get('per_page', 50);
        $predictions = $query->orderBy('predicted_date', 'asc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'next_purchase_predictions' => $predictions
        ]);
    }

    /**
     * Send next purchase notifications
     * POST /api/segmentation/next-purchase/send-notifications
     */
    public function sendNextPurchaseNotifications(Request $request)
    {
        $daysBeforeDate = $request->get('days_before', 3);
        $sent = $this->segmentationService->sendNextPurchaseNotifications($daysBeforeDate);

        return response()->json([
            'success' => true,
            'message' => "Sent {$sent} next purchase notifications"
        ]);
    }

    /**
     * List customer segments
     * GET /api/segmentation/segments
     */
    public function listSegments(Request $request)
    {
        $query = CustomerSegment::query();

        if ($request->has('type')) {
            $query->byType($request->type);
        }

        if ($request->boolean('active_only', true)) {
            $query->active();
        }

        $segments = $query->orderBy('customer_count', 'desc')->get();

        return response()->json([
            'success' => true,
            'segments' => $segments
        ]);
    }

    /**
     * Create customer segment
     * POST /api/segmentation/segments
     */
    public function createSegment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:customer_segments',
            'description' => 'nullable|string',
            'segment_type' => 'required|in:rfm,behavioral,demographic,predictive,custom',
            'criteria' => 'required|array',
            'is_active' => 'nullable|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $segment = CustomerSegment::create($validator->validated());

        // Calculate membership
        $segment->calculateMembership();

        return response()->json([
            'success' => true,
            'message' => 'Segment created',
            'segment' => $segment->fresh()
        ], 201);
    }

    /**
     * Recalculate segment membership
     * POST /api/segmentation/segments/{id}/recalculate
     */
    public function recalculateSegment($id)
    {
        $segment = CustomerSegment::findOrFail($id);
        $count = $segment->calculateMembership();

        return response()->json([
            'success' => true,
            'message' => "Segment recalculated with {$count} members",
            'segment' => $segment->fresh()
        ]);
    }

    /**
     * Initialize default segments
     * POST /api/segmentation/segments/initialize-defaults
     */
    public function initializeDefaultSegments()
    {
        $rfmSegments = $this->segmentationService->createDefaultRfmSegments();
        $churnSegments = $this->segmentationService->createChurnRiskSegments();
        $clvSegments = $this->segmentationService->createClvSegments();

        // Calculate membership for all
        $results = $this->segmentationService->recalculateAllSegments();

        return response()->json([
            'success' => true,
            'message' => 'Default segments initialized',
            'rfm_segments' => count($rfmSegments),
            'churn_segments' => count($churnSegments),
            'clv_segments' => count($clvSegments),
            'membership_counts' => $results
        ]);
    }

    /**
     * Run complete analysis for user
     * POST /api/segmentation/analyze/{userId}
     */
    public function analyzeUser($userId)
    {
        $results = $this->segmentationService->completeUserAnalysis($userId);

        return response()->json([
            'success' => true,
            'results' => $results
        ]);
    }

    /**
     * Run complete segmentation for all customers
     * POST /api/segmentation/run-complete
     */
    public function runCompleteSegmentation()
    {
        $results = $this->segmentationService->runCompleteSegmentation();

        return response()->json([
            'success' => true,
            'message' => 'Complete segmentation run finished',
            'results' => $results
        ]);
    }
}
