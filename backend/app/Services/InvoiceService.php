<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use App\Mail\InvoiceMail;

class InvoiceService
{
    /**
     * Generate invoice from order
     */
    public function generateFromOrder(Order $order)
    {
        // Check if invoice already exists
        $existingInvoice = Invoice::where('order_id', $order->id)->first();
        if ($existingInvoice) {
            return $existingInvoice;
        }

        // Create invoice
        $invoice = Invoice::create([
            'invoice_number' => Invoice::generateInvoiceNumber(),
            'order_id' => $order->id,
            'user_id' => $order->user_id,
            'seller_id' => $order->seller_id ?? $order->user_id,
            'subtotal' => $order->subtotal ?? $order->total_amount,
            'tax_amount' => $order->tax_amount ?? 0,
            'shipping_amount' => $order->shipping_cost ?? 0,
            'discount_amount' => $order->discount_amount ?? 0,
            'total_amount' => $order->total_amount,
            'currency' => $order->currency ?? 'USD',
            'billing_name' => $order->shipping_name ?? $order->user->name,
            'billing_address' => $order->shipping_address ?? '',
            'billing_city' => $order->shipping_city ?? '',
            'billing_state' => $order->shipping_state ?? '',
            'billing_country' => $order->shipping_country ?? '',
            'billing_zip' => $order->shipping_zip ?? '',
            'billing_email' => $order->user->email,
            'billing_phone' => $order->shipping_phone ?? $order->user->phone,
            'status' => $order->payment_status === 'paid' ? 'paid' : 'issued',
            'paid_amount' => $order->payment_status === 'paid' ? $order->total_amount : 0,
            'paid_at' => $order->payment_status === 'paid' ? now() : null,
            'due_date' => now()->addDays(30),
            'terms' => 'Payment is due within 30 days',
        ]);

        // Create invoice items from order items
        foreach ($order->items as $orderItem) {
            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'product_id' => $orderItem->product_id,
                'description' => $orderItem->product_name ?? $orderItem->product->name,
                'sku' => $orderItem->product->sku ?? null,
                'quantity' => $orderItem->quantity,
                'unit_price' => $orderItem->price,
                'tax_rate' => 0, // Will be calculated by tax service
                'tax_amount' => 0,
                'discount_amount' => 0,
                'line_total' => $orderItem->quantity * $orderItem->price,
            ]);
        }

        // Generate PDF
        $this->generatePDF($invoice);

        return $invoice;
    }

    /**
     * Generate PDF for invoice
     */
    public function generatePDF(Invoice $invoice)
    {
        $invoice->load(['items.product', 'user', 'seller', 'order']);

        $pdf = Pdf::loadView('invoices.template', [
            'invoice' => $invoice,
        ]);

        $filename = 'invoices/' . $invoice->invoice_number . '.pdf';
        Storage::disk('public')->put($filename, $pdf->output());

        $invoice->update(['pdf_path' => $filename]);

        return $filename;
    }

    /**
     * Email invoice to customer
     */
    public function emailInvoice(Invoice $invoice)
    {
        $invoice->load(['items.product', 'user', 'order']);

        Mail::to($invoice->billing_email)->send(new InvoiceMail($invoice));

        return true;
    }

    /**
     * Get invoice PDF download URL
     */
    public function getDownloadUrl(Invoice $invoice)
    {
        if (!$invoice->pdf_path) {
            $this->generatePDF($invoice);
        }

        return Storage::disk('public')->url($invoice->pdf_path);
    }

    /**
     * Bulk generate invoices
     */
    public function bulkGenerate(array $orderIds)
    {
        $invoices = [];
        
        foreach ($orderIds as $orderId) {
            $order = Order::find($orderId);
            if ($order) {
                $invoices[] = $this->generateFromOrder($order);
            }
        }

        return $invoices;
    }

    /**
     * Mark invoice as paid
     */
    public function markAsPaid(Invoice $invoice, $amount = null)
    {
        $invoice->markAsPaid($amount);
        
        // Update order payment status if fully paid
        if ($invoice->status === 'paid') {
            $invoice->order->update(['payment_status' => 'paid']);
        }

        return $invoice;
    }

    /**
     * Cancel invoice
     */
    public function cancel(Invoice $invoice, $reason = null)
    {
        $invoice->update([
            'status' => 'cancelled',
            'notes' => $reason ? "Cancelled: {$reason}" : 'Invoice cancelled'
        ]);

        return $invoice;
    }

    /**
     * Refund invoice
     */
    public function refund(Invoice $invoice, $amount = null)
    {
        $refundAmount = $amount ?? $invoice->total_amount;
        
        $invoice->update([
            'status' => 'refunded',
            'paid_amount' => max(0, $invoice->paid_amount - $refundAmount),
        ]);

        return $invoice;
    }
}
