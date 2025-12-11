<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ShippingService;
use App\Models\Order;
use Illuminate\Http\Request;

class ShippingController extends Controller
{
    protected $shippingService;

    public function __construct(ShippingService $shippingService)
    {
        $this->shippingService = $shippingService;
    }

    /**
     * Validate shipping address
     */
    public function validateAddress(Request $request)
    {
        $request->validate([
            'street1' => 'required|string',
            'street2' => 'nullable|string',
            'city' => 'required|string',
            'state' => 'required|string',
            'zip' => 'required|string',
            'country' => 'required|string|size:2',
            'name' => 'nullable|string',
        ]);

        $result = $this->shippingService->validateAddress($request->all());

        return response()->json($result);
    }

    /**
     * Get shipping rates for order
     */
    public function getRates(Request $request)
    {
        $request->validate([
            'from_address' => 'required|array',
            'to_address' => 'required|array',
            'weight' => 'required|numeric',
            'length' => 'nullable|numeric',
            'width' => 'nullable|numeric',
            'height' => 'nullable|numeric',
        ]);

        $parcel = [
            'length' => $request->length ?? 10,
            'width' => $request->width ?? 10,
            'height' => $request->height ?? 10,
            'distance_unit' => 'in',
            'weight' => $request->weight,
            'mass_unit' => 'lb',
        ];

        $result = $this->shippingService->getShippingRates(
            $request->from_address,
            $request->to_address,
            $parcel
        );

        return response()->json($result);
    }

    /**
     * Purchase shipping label for order
     */
    public function purchaseLabel(Request $request)
    {
        $request->validate([
            'order_id' => 'required|integer',
            'rate_id' => 'required|string',
        ]);

        $order = Order::findOrFail($request->order_id);

        // Verify order belongs to authenticated user or is seller
        if ($order->user_id !== $request->user()->id && $order->seller_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        $result = $this->shippingService->purchaseLabel($request->rate_id);

        if ($result['success']) {
            // Update order with tracking information
            $order->update([
                'tracking_number' => $result['tracking_number'],
                'tracking_url' => $result['tracking_url'],
                'shipping_label_url' => $result['label_url'],
                'status' => 'shipped',
                'shipped_at' => now(),
            ]);
        }

        return response()->json($result);
    }

    /**
     * Get tracking information
     */
    public function getTracking(Request $request, $orderId)
    {
        $order = Order::findOrFail($orderId);

        // Verify order belongs to authenticated user
        if ($order->user_id !== $request->user()->id && $order->seller_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        if (!$order->tracking_number) {
            return response()->json([
                'message' => 'No tracking number available for this order'
            ], 400);
        }

        // Extract carrier from tracking number or use stored carrier
        $carrier = $order->shipping_carrier ?? 'usps';

        $result = $this->shippingService->getTrackingInfo($carrier, $order->tracking_number);

        return response()->json($result);
    }

    /**
     * Create return label
     */
    public function createReturnLabel(Request $request)
    {
        $request->validate([
            'order_id' => 'required|integer',
            'return_address' => 'required|array',
            'reason' => 'nullable|string',
        ]);

        $order = Order::with('shipping')->findOrFail($request->order_id);

        // Verify order belongs to authenticated user
        if ($order->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        // Check if order is eligible for return
        if ($order->status === 'delivered' || $order->status === 'completed') {
            $originalShipment = [
                'address_to' => $order->shipping->toArray(),
                'parcels' => [[
                    'length' => '10',
                    'width' => '10',
                    'height' => '10',
                    'distance_unit' => 'in',
                    'weight' => $order->total_weight ?? 1,
                    'mass_unit' => 'lb',
                ]],
            ];

            $result = $this->shippingService->createReturnLabel(
                $originalShipment,
                $request->return_address
            );

            if ($result['success']) {
                // Create refund record
                \App\Models\Refund::create([
                    'order_id' => $order->id,
                    'user_id' => $order->user_id,
                    'amount' => $order->total_amount,
                    'reason' => $request->reason ?? 'Return requested',
                    'status' => 'pending',
                    'return_tracking_number' => $result['tracking_number'],
                    'return_label_url' => $result['label_url'],
                ]);
            }

            return response()->json($result);
        }

        return response()->json([
            'message' => 'Order is not eligible for return'
        ], 400);
    }

    /**
     * Calculate shipping cost
     */
    public function calculateCost(Request $request)
    {
        $request->validate([
            'weight' => 'required|numeric',
            'from_zip' => 'required|string',
            'to_zip' => 'required|string',
            'country' => 'nullable|string|size:2',
        ]);

        $result = $this->shippingService->calculateShippingCost(
            $request->weight,
            $request->from_zip,
            $request->to_zip,
            $request->country ?? 'US'
        );

        return response()->json($result);
    }

    /**
     * Get all shipping methods
     */
    public function getMethods(Request $request)
    {
        // Return available shipping methods from database
        $methods = \App\Models\ShippingRate::where('is_active', true)
            ->orderBy('base_rate', 'asc')
            ->get();

        return response()->json([
            'methods' => $methods
        ]);
    }

    /**
     * Batch track multiple orders
     */
    public function batchTrack(Request $request)
    {
        $request->validate([
            'order_ids' => 'required|array',
            'order_ids.*' => 'integer',
        ]);

        $orders = Order::whereIn('id', $request->order_ids)
            ->where(function ($query) use ($request) {
                $query->where('user_id', $request->user()->id)
                      ->orWhere('seller_id', $request->user()->id);
            })
            ->get();

        $results = [];

        foreach ($orders as $order) {
            if ($order->tracking_number) {
                $carrier = $order->shipping_carrier ?? 'usps';
                $tracking = $this->shippingService->getTrackingInfo($carrier, $order->tracking_number);
                
                $results[] = [
                    'order_id' => $order->id,
                    'tracking' => $tracking,
                ];
            } else {
                $results[] = [
                    'order_id' => $order->id,
                    'tracking' => [
                        'success' => false,
                        'message' => 'No tracking number available',
                    ],
                ];
            }
        }

        return response()->json([
            'results' => $results
        ]);
    }
}
