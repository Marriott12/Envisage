<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Order;
use App\Services\InvoiceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class InvoiceController extends Controller
{
    protected $invoiceService;

    public function __construct(InvoiceService $invoiceService)
    {
        $this->invoiceService = $invoiceService;
    }

    /**
     * Get all invoices for authenticated user
     * GET /api/invoices
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        $query = Invoice::with(['order', 'items.product'])
            ->where(function($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhere('seller_id', $user->id);
            });

        // Filters
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('overdue') && $request->overdue) {
            $query->overdue();
        }

        if ($request->has('unpaid') && $request->unpaid) {
            $query->unpaid();
        }

        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $invoices = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 20));

        return response()->json([
            'success' => true,
            'invoices' => $invoices,
        ]);
    }

    /**
     * Get single invoice
     * GET /api/invoices/{id}
     */
    public function show($id)
    {
        $invoice = Invoice::with(['order', 'items.product', 'user', 'seller'])
            ->findOrFail($id);

        // Check authorization
        $user = request()->user();
        if ($invoice->user_id !== $user->id && $invoice->seller_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'invoice' => $invoice,
            'is_overdue' => $invoice->isOverdue(),
            'balance_due' => $invoice->getBalanceDue(),
        ]);
    }

    /**
     * Generate invoice from order
     * POST /api/invoices/generate/{orderId}
     */
    public function generate($orderId)
    {
        $order = Order::findOrFail($orderId);

        // Check authorization
        $user = request()->user();
        if ($order->user_id !== $user->id && (!$order->seller_id || $order->seller_id !== $user->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        try {
            $invoice = $this->invoiceService->generateFromOrder($order);

            return response()->json([
                'success' => true,
                'message' => 'Invoice generated successfully',
                'invoice' => $invoice->load(['items.product']),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate invoice: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download invoice PDF
     * GET /api/invoices/{id}/download
     */
    public function download($id)
    {
        $invoice = Invoice::findOrFail($id);

        // Check authorization
        $user = request()->user();
        if ($invoice->user_id !== $user->id && $invoice->seller_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        if (!$invoice->pdf_path || !Storage::disk('public')->exists($invoice->pdf_path)) {
            $this->invoiceService->generatePDF($invoice);
        }

        $downloadUrl = $this->invoiceService->getDownloadUrl($invoice);

        return response()->json([
            'success' => true,
            'download_url' => $downloadUrl,
            'filename' => basename($invoice->pdf_path),
        ]);
    }

    /**
     * Email invoice to customer
     * POST /api/invoices/{id}/email
     */
    public function email($id)
    {
        $invoice = Invoice::findOrFail($id);

        // Check authorization (seller only)
        $user = request()->user();
        if ($invoice->seller_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        try {
            $this->invoiceService->emailInvoice($invoice);

            return response()->json([
                'success' => true,
                'message' => 'Invoice emailed successfully to ' . $invoice->billing_email,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send email: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get invoice by order ID
     * GET /api/invoices/order/{orderId}
     */
    public function getByOrder($orderId)
    {
        $invoice = Invoice::with(['items.product'])
            ->where('order_id', $orderId)
            ->first();

        if (!$invoice) {
            return response()->json([
                'success' => false,
                'message' => 'Invoice not found'
            ], 404);
        }

        // Check authorization
        $user = request()->user();
        if ($invoice->user_id !== $user->id && $invoice->seller_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'invoice' => $invoice,
        ]);
    }

    /**
     * Bulk generate invoices
     * POST /api/invoices/bulk-generate
     */
    public function bulkGenerate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_ids' => 'required|array',
            'order_ids.*' => 'required|integer|exists:orders,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $invoices = $this->invoiceService->bulkGenerate($request->order_ids);

            return response()->json([
                'success' => true,
                'message' => count($invoices) . ' invoices generated successfully',
                'invoices' => $invoices,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate invoices: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mark invoice as paid
     * PUT /api/invoices/{id}/mark-paid
     */
    public function markPaid(Request $request, $id)
    {
        $invoice = Invoice::findOrFail($id);

        // Check authorization (seller only)
        $user = $request->user();
        if ($invoice->seller_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'amount' => 'nullable|numeric|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $this->invoiceService->markAsPaid($invoice, $request->amount);

        return response()->json([
            'success' => true,
            'message' => 'Invoice marked as paid',
            'invoice' => $invoice->fresh(),
        ]);
    }

    /**
     * Cancel invoice
     * PUT /api/invoices/{id}/cancel
     */
    public function cancel(Request $request, $id)
    {
        $invoice = Invoice::findOrFail($id);

        // Check authorization (seller only)
        $user = $request->user();
        if ($invoice->seller_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $this->invoiceService->cancel($invoice, $request->reason);

        return response()->json([
            'success' => true,
            'message' => 'Invoice cancelled',
            'invoice' => $invoice->fresh(),
        ]);
    }

    /**
     * Get invoice statistics
     * GET /api/invoices/stats
     */
    public function stats(Request $request)
    {
        $user = $request->user();
        
        $query = Invoice::where(function($q) use ($user) {
            $q->where('user_id', $user->id)
              ->orWhere('seller_id', $user->id);
        });

        $stats = [
            'total_invoices' => (clone $query)->count(),
            'paid_invoices' => (clone $query)->paid()->count(),
            'unpaid_invoices' => (clone $query)->unpaid()->count(),
            'overdue_invoices' => (clone $query)->overdue()->count(),
            'total_amount' => (clone $query)->sum('total_amount'),
            'paid_amount' => (clone $query)->sum('paid_amount'),
            'outstanding_amount' => (clone $query)->unpaid()->sum('total_amount'),
        ];

        return response()->json([
            'success' => true,
            'stats' => $stats,
        ]);
    }
}
