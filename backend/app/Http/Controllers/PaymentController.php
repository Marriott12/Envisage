<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\OrderConfirmation;

class PaymentController extends Controller
{
    protected $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    public function createPaymentIntent(Request $request)
    {
        $validated = $request->validate([
            'order_id' => 'required|exists:orders,id',
        ]);

        try {
            $order = Order::with('items.product')->findOrFail($validated['order_id']);

            if ($order->user_id !== auth()->id()) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            if ($order->payment_status === 'paid') {
                return response()->json(['error' => 'Order already paid'], 400);
            }

            $result = $this->paymentService->createPaymentIntent(
                $order->total_amount,
                'usd',
                [
                    'order_id' => $order->id,
                    'user_id' => $order->user_id,
                    'user_email' => auth()->user()->email,
                ]
            );

            if (!$result['success']) {
                return response()->json(['error' => $result['error']], 500);
            }

            $payment = Payment::create([
                'order_id' => $order->id,
                'user_id' => $order->user_id,
                'amount' => $order->total_amount,
                'currency' => 'USD',
                'payment_method' => 'stripe',
                'payment_intent_id' => $result['payment_intent_id'],
                'status' => 'pending',
            ]);

            return response()->json([
                'client_secret' => $result['client_secret'],
                'payment_id' => $payment->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Payment intent creation failed: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to create payment intent'], 500);
        }
    }

    public function confirmPayment(Request $request)
    {
        $validated = $request->validate([
            'payment_intent_id' => 'required|string',
        ]);

        try {
            $payment = Payment::where('payment_intent_id', $validated['payment_intent_id'])->first();

            if (!$payment) {
                return response()->json(['error' => 'Payment not found'], 404);
            }

            $result = $this->paymentService->retrievePaymentIntent($validated['payment_intent_id']);

            if (!$result['success']) {
                return response()->json(['error' => $result['error']], 500);
            }

            $payment->update([
                'status' => $result['status'] === 'succeeded' ? 'completed' : $result['status'],
                'payment_method' => $result['payment_method'],
            ]);

            if ($result['status'] === 'succeeded') {
                $order = $payment->order;
                $order->update([
                    'payment_status' => 'paid',
                    'status' => 'processing',
                ]);

                try {
                    Mail::to($order->user->email)->send(new OrderConfirmation($order));
                } catch (\Exception $e) {
                    Log::error('Order confirmation email failed: ' . $e->getMessage());
                }
            }

            return response()->json([
                'success' => true,
                'payment_status' => $payment->status,
                'order_status' => $payment->order->status,
            ]);
        } catch (\Exception $e) {
            Log::error('Payment confirmation failed: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to confirm payment'], 500);
        }
    }

    public function handleWebhook(Request $request)
    {
        $payload = $request->getContent();
        $signature = $request->header('Stripe-Signature');

        if (!$this->paymentService->verifyWebhookSignature($payload, $signature)) {
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        $event = json_decode($payload, true);

        try {
            switch ($event['type']) {
                case 'payment_intent.succeeded':
                    $this->handlePaymentSuccess($event['data']['object']);
                    break;
                case 'payment_intent.payment_failed':
                    $this->handlePaymentFailed($event['data']['object']);
                    break;
                case 'charge.refunded':
                    $this->handleRefund($event['data']['object']);
                    break;
            }

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error('Webhook handling failed: ' . $e->getMessage());
            return response()->json(['error' => 'Webhook processing failed'], 500);
        }
    }

    public function requestRefund(Request $request, $paymentId)
    {
        try {
            $payment = Payment::findOrFail($paymentId);

            if ($payment->user_id !== auth()->id() && auth()->user()->role !== 'admin') {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            if ($payment->status !== 'completed') {
                return response()->json(['error' => 'Payment cannot be refunded'], 400);
            }

            if ($payment->refund_status === 'refunded') {
                return response()->json(['error' => 'Payment already refunded'], 400);
            }

            $result = $this->paymentService->createRefund(
                $payment->payment_intent_id,
                $request->input('amount'),
                $request->input('reason', 'requested_by_customer')
            );

            if (!$result['success']) {
                return response()->json(['error' => $result['error']], 500);
            }

            $payment->update([
                'refund_status' => 'refunded',
                'refund_amount' => $result['amount'],
            ]);

            $payment->order->update([
                'payment_status' => 'refunded',
                'status' => 'cancelled',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Refund processed successfully',
                'refund_id' => $result['refund_id'],
            ]);
        } catch (\Exception $e) {
            Log::error('Refund request failed: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to process refund'], 500);
        }
    }

    public function myPayments(Request $request)
    {
        $query = Payment::with('order')
            ->where('user_id', auth()->id())
            ->orderBy('created_at', 'desc');

        $perPage = $request->get('per_page', 20);
        return response()->json($query->paginate($perPage));
    }

    private function handlePaymentSuccess($paymentIntent)
    {
        $payment = Payment::where('payment_intent_id', $paymentIntent['id'])->first();

        if ($payment) {
            $payment->update(['status' => 'completed']);
            
            $order = $payment->order;
            $order->update([
                'payment_status' => 'paid',
                'status' => 'processing',
            ]);

            Log::info('Payment succeeded for order: ' . $order->id);
        }
    }

    private function handlePaymentFailed($paymentIntent)
    {
        $payment = Payment::where('payment_intent_id', $paymentIntent['id'])->first();

        if ($payment) {
            $payment->update(['status' => 'failed']);
            $payment->order->update(['payment_status' => 'failed']);

            Log::warning('Payment failed for order: ' . $payment->order_id);
        }
    }

    private function handleRefund($charge)
    {
        $payment = Payment::where('payment_intent_id', $charge['payment_intent'])->first();

        if ($payment) {
            $payment->update([
                'refund_status' => 'refunded',
                'refund_amount' => $charge['amount_refunded'] / 100,
            ]);

            $payment->order->update([
                'payment_status' => 'refunded',
                'status' => 'cancelled',
            ]);

            Log::info('Refund processed for order: ' . $payment->order_id);
        }
    }
}
