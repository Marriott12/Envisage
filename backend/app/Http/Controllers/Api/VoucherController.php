<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Voucher;
use App\Models\VoucherUsage;
use Illuminate\Http\Request;

class VoucherController extends Controller
{
    /**
     * Validate voucher code
     */
    public function validateCode(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
            'order_amount' => 'required|numeric|min:0',
            'user_id' => 'nullable|exists:users,id',
        ]);

        $voucher = Voucher::where('code', $request->code)->first();

        if (!$voucher) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid voucher code',
            ], 404);
        }

        if (!$voucher->isValid()) {
            return response()->json([
                'success' => false,
                'message' => 'Voucher is not active or has expired',
            ], 400);
        }

        $userId = $request->user_id ?? auth()->id();
        if ($userId && !$voucher->canBeUsedBy($userId)) {
            return response()->json([
                'success' => false,
                'message' => 'You have reached the usage limit for this voucher',
            ], 400);
        }

        $discount = $voucher->calculateDiscount($request->order_amount);

        if ($discount === 0) {
            return response()->json([
                'success' => false,
                'message' => 'Minimum purchase amount not met',
            ], 400);
        }

        return response()->json([
            'success' => true,
            'voucher' => [
                'id' => $voucher->id,
                'code' => $voucher->code,
                'name' => $voucher->name,
                'type' => $voucher->type,
                'discount_amount' => $discount === 'free_shipping' ? 0 : $discount,
                'free_shipping' => $discount === 'free_shipping',
            ],
        ]);
    }

    /**
     * Apply voucher to order
     */
    public function apply(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
            'order_id' => 'required|exists:orders,id',
            'order_amount' => 'required|numeric|min:0',
        ]);

        $voucher = Voucher::where('code', $request->code)->firstOrFail();
        $userId = auth()->id();

        $discount = $voucher->calculateDiscount($request->order_amount);

        // Create usage record
        VoucherUsage::create([
            'voucher_id' => $voucher->id,
            'user_id' => $userId,
            'order_id' => $request->order_id,
            'discount_amount' => $discount === 'free_shipping' ? 0 : $discount,
        ]);

        // Update voucher usage count
        $voucher->increment('usage_count');

        return response()->json([
            'success' => true,
            'message' => 'Voucher applied successfully',
            'discount' => $discount,
        ]);
    }

    /**
     * Get all vouchers (admin)
     */
    public function index(Request $request)
    {
        $query = Voucher::query();

        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        $vouchers = $query->orderBy('created_at', 'desc')
                         ->paginate($request->per_page ?? 15);

        return response()->json([
            'success' => true,
            'data' => $vouchers,
        ]);
    }

    /**
     * Create voucher (admin)
     */
    public function store(Request $request)
    {
        $request->validate([
            'code' => 'required|string|unique:vouchers,code',
            'name' => 'required|string',
            'description' => 'nullable|string',
            'type' => 'required|in:percentage,fixed,free_shipping',
            'value' => 'required|numeric|min:0',
            'min_purchase_amount' => 'nullable|numeric|min:0',
            'max_discount' => 'nullable|numeric|min:0',
            'usage_limit' => 'nullable|integer|min:1',
            'per_user_limit' => 'nullable|integer|min:1',
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after:starts_at',
            'applicable_products' => 'nullable|array',
            'applicable_categories' => 'nullable|array',
        ]);

        $voucher = Voucher::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Voucher created successfully',
            'data' => $voucher,
        ], 201);
    }

    /**
     * Update voucher (admin)
     */
    public function update(Request $request, $id)
    {
        $voucher = Voucher::findOrFail($id);

        $request->validate([
            'code' => 'sometimes|string|unique:vouchers,code,' . $id,
            'name' => 'sometimes|string',
            'type' => 'sometimes|in:percentage,fixed,free_shipping',
            'value' => 'sometimes|numeric|min:0',
            'is_active' => 'sometimes|boolean',
        ]);

        $voucher->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Voucher updated successfully',
            'data' => $voucher,
        ]);
    }

    /**
     * Delete voucher (admin)
     */
    public function destroy($id)
    {
        $voucher = Voucher::findOrFail($id);
        $voucher->delete();

        return response()->json([
            'success' => true,
            'message' => 'Voucher deleted successfully',
        ]);
    }
}
