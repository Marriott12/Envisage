<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BnplPlan;
use App\Models\BnplOrder;
use App\Models\BnplInstallment;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class BNPLController extends Controller
{
    /**
     * Get all available BNPL plans
     */
    public function getPlans(Request $request)
    {
        $amount = $request->query('amount');

        $plans = BnplPlan::active()
            ->orderBy('installments', 'asc')
            ->get()
            ->map(function ($plan) use ($amount) {
                $data = $plan->toArray();
                
                if ($amount) {
                    $data['is_valid'] = $plan->isAmountValid($amount);
                    $data['installment_amount'] = $plan->calculateInstallmentAmount($amount);
                    $data['total_with_interest'] = $amount * (1 + ($plan->interest_rate / 100));
                }
                
                return $data;
            });

        return response()->json([
            'success' => true,
            'plans' => $plans,
        ]);
    }

    /**
     * Create a new BNPL order
     */
    public function createOrder(Request $request)
    {
        $request->validate([
            'order_id' => 'required|exists:orders,id',
            'bnpl_plan_id' => 'required|exists:bnpl_plans,id',
            'down_payment' => 'nullable|numeric|min:0',
        ]);

        $order = Order::findOrFail($request->order_id);
        $plan = BnplPlan::findOrFail($request->bnpl_plan_id);

        // Verify order belongs to authenticated user
        if ($order->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        // Verify amount is within plan limits
        if (!$plan->isAmountValid($order->total)) {
            return response()->json([
                'success' => false,
                'message' => 'Order amount is not valid for this plan',
            ], 400);
        }

        DB::beginTransaction();
        try {
            $downPayment = $request->down_payment ?? 0;
            $remainingAmount = $order->total - $downPayment;
            $firstPaymentDate = Carbon::now()->addDays($plan->interval_days);

            // Create BNPL order
            $bnplOrder = BnplOrder::create([
                'order_id' => $order->id,
                'user_id' => Auth::id(),
                'bnpl_plan_id' => $plan->id,
                'total_amount' => $order->total,
                'down_payment' => $downPayment,
                'remaining_amount' => $remainingAmount,
                'installments_count' => $plan->installments,
                'installments_paid' => 0,
                'status' => 'active',
                'first_payment_date' => $firstPaymentDate,
                'next_payment_date' => $firstPaymentDate,
            ]);

            // Create installments
            $installmentAmount = $plan->calculateInstallmentAmount($order->total, $downPayment);
            for ($i = 1; $i <= $plan->installments; $i++) {
                $dueDate = Carbon::now()->addDays($plan->interval_days * $i);
                
                BnplInstallment::create([
                    'bnpl_order_id' => $bnplOrder->id,
                    'installment_number' => $i,
                    'amount' => $installmentAmount,
                    'due_date' => $dueDate,
                    'status' => 'pending',
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'BNPL order created successfully',
                'bnpl_order' => $bnplOrder->load(['bnplPlan', 'installments']),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create BNPL order: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get BNPL order details
     */
    public function getOrderDetails($id)
    {
        $bnplOrder = BnplOrder::with(['bnplPlan', 'installments', 'order'])
            ->where('user_id', Auth::id())
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'bnpl_order' => $bnplOrder,
            'progress' => $bnplOrder->getProgressPercentage(),
        ]);
    }

    /**
     * Get user's BNPL orders
     */
    public function getUserOrders()
    {
        $orders = BnplOrder::with(['bnplPlan', 'order'])
            ->where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($order) {
                return [
                    'id' => $order->id,
                    'order_id' => $order->order_id,
                    'plan_name' => $order->bnplPlan->name,
                    'total_amount' => $order->total_amount,
                    'installments_paid' => $order->installments_paid,
                    'installments_count' => $order->installments_count,
                    'status' => $order->status,
                    'next_payment_date' => $order->next_payment_date,
                    'progress' => $order->getProgressPercentage(),
                ];
            });

        return response()->json([
            'success' => true,
            'orders' => $orders,
        ]);
    }

    /**
     * Make an installment payment
     */
    public function makePayment(Request $request, $id)
    {
        $request->validate([
            'installment_id' => 'required|exists:bnpl_installments,id',
            'payment_method' => 'required|string',
            'transaction_id' => 'required|string',
        ]);

        $bnplOrder = BnplOrder::where('user_id', Auth::id())->findOrFail($id);
        $installment = BnplInstallment::where('bnpl_order_id', $bnplOrder->id)
            ->findOrFail($request->installment_id);

        if ($installment->isPaid()) {
            return response()->json([
                'success' => false,
                'message' => 'Installment already paid',
            ], 400);
        }

        DB::beginTransaction();
        try {
            // Mark installment as paid
            $installment->markAsPaid($request->payment_method, $request->transaction_id);

            // Update BNPL order
            $bnplOrder->increment('installments_paid');
            
            // Update next payment date
            $nextInstallment = BnplInstallment::where('bnpl_order_id', $bnplOrder->id)
                ->where('status', 'pending')
                ->orderBy('due_date', 'asc')
                ->first();

            if ($nextInstallment) {
                $bnplOrder->next_payment_date = $nextInstallment->due_date;
            } else {
                $bnplOrder->status = 'completed';
                $bnplOrder->next_payment_date = null;
            }

            $bnplOrder->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Payment processed successfully',
                'bnpl_order' => $bnplOrder->fresh(['installments']),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Payment failed: ' . $e->getMessage(),
            ], 500);
        }
    }
}
