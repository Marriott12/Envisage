<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            color: #333;
            line-height: 1.6;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 40px;
        }
        .header {
            display: table;
            width: 100%;
            margin-bottom: 40px;
            border-bottom: 3px solid #3b82f6;
            padding-bottom: 20px;
        }
        .header-left {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }
        .header-right {
            display: table-cell;
            width: 50%;
            text-align: right;
            vertical-align: top;
        }
        .company-name {
            font-size: 24px;
            font-weight: bold;
            color: #3b82f6;
            margin-bottom: 5px;
        }
        .invoice-title {
            font-size: 32px;
            font-weight: bold;
            color: #1e40af;
        }
        .invoice-number {
            font-size: 16px;
            color: #666;
            margin-top: 5px;
        }
        .info-section {
            display: table;
            width: 100%;
            margin-bottom: 30px;
        }
        .info-left, .info-right {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }
        .info-box {
            background: #f9fafb;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 10px;
        }
        .info-title {
            font-weight: bold;
            font-size: 14px;
            color: #1f2937;
            margin-bottom: 8px;
            text-transform: uppercase;
        }
        .info-content {
            color: #4b5563;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table thead {
            background: #3b82f6;
            color: white;
        }
        table th {
            padding: 12px 8px;
            text-align: left;
            font-weight: 600;
            font-size: 11px;
            text-transform: uppercase;
        }
        table td {
            padding: 12px 8px;
            border-bottom: 1px solid #e5e7eb;
        }
        table tbody tr:hover {
            background: #f9fafb;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .totals {
            margin-left: auto;
            width: 300px;
            margin-top: 20px;
        }
        .totals table {
            margin-bottom: 0;
        }
        .totals td {
            padding: 8px;
            border: none;
        }
        .totals .total-row {
            background: #3b82f6;
            color: white;
            font-weight: bold;
            font-size: 16px;
        }
        .totals .subtotal-row {
            background: #f3f4f6;
        }
        .footer {
            margin-top: 50px;
            padding-top: 20px;
            border-top: 2px solid #e5e7eb;
            text-align: center;
            color: #6b7280;
            font-size: 10px;
        }
        .notes {
            background: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 15px;
            margin: 20px 0;
        }
        .notes-title {
            font-weight: bold;
            color: #92400e;
            margin-bottom: 5px;
        }
        .status-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .status-paid {
            background: #d1fae5;
            color: #065f46;
        }
        .status-unpaid {
            background: #fee2e2;
            color: #991b1b;
        }
        .status-overdue {
            background: #fecaca;
            color: #7f1d1d;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="header-left">
                <div class="company-name">{{ config('app.name') }}</div>
                <div style="color: #6b7280; margin-top: 5px;">
                    {{ config('app.url') }}<br>
                    Email: {{ config('mail.from.address') }}
                </div>
            </div>
            <div class="header-right">
                <div class="invoice-title">INVOICE</div>
                <div class="invoice-number">#{{ $invoice->invoice_number }}</div>
                <div style="margin-top: 10px;">
                    <span class="status-badge status-{{ strtolower($invoice->status) }}">
                        {{ ucfirst($invoice->status) }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Invoice Info -->
        <div class="info-section">
            <div class="info-left">
                <div class="info-box">
                    <div class="info-title">Bill To</div>
                    <div class="info-content">
                        <strong>{{ $invoice->billing_name }}</strong><br>
                        @if($invoice->company_name)
                            {{ $invoice->company_name }}<br>
                        @endif
                        {{ $invoice->billing_address }}<br>
                        {{ $invoice->billing_city }}, {{ $invoice->billing_state }} {{ $invoice->billing_zip }}<br>
                        {{ $invoice->billing_country }}<br>
                        <br>
                        Email: {{ $invoice->billing_email }}<br>
                        @if($invoice->billing_phone)
                            Phone: {{ $invoice->billing_phone }}<br>
                        @endif
                        @if($invoice->tax_id)
                            Tax ID: {{ $invoice->tax_id }}
                        @endif
                    </div>
                </div>
            </div>
            <div class="info-right">
                <div class="info-box">
                    <div class="info-title">Invoice Details</div>
                    <div class="info-content">
                        <table style="width: 100%; border: none;">
                            <tr>
                                <td style="border: none; padding: 4px 0;"><strong>Invoice Date:</strong></td>
                                <td style="border: none; padding: 4px 0; text-align: right;">
                                    {{ $invoice->created_at->format('M d, Y') }}
                                </td>
                            </tr>
                            @if($invoice->due_date)
                            <tr>
                                <td style="border: none; padding: 4px 0;"><strong>Due Date:</strong></td>
                                <td style="border: none; padding: 4px 0; text-align: right;">
                                    {{ $invoice->due_date->format('M d, Y') }}
                                </td>
                            </tr>
                            @endif
                            <tr>
                                <td style="border: none; padding: 4px 0;"><strong>Order ID:</strong></td>
                                <td style="border: none; padding: 4px 0; text-align: right;">
                                    #{{ $invoice->order_id }}
                                </td>
                            </tr>
                            <tr>
                                <td style="border: none; padding: 4px 0;"><strong>Currency:</strong></td>
                                <td style="border: none; padding: 4px 0; text-align: right;">
                                    {{ strtoupper($invoice->currency) }}
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Items Table -->
        <table>
            <thead>
                <tr>
                    <th style="width: 50%;">Description</th>
                    <th class="text-center">Qty</th>
                    <th class="text-right">Unit Price</th>
                    <th class="text-right">Tax</th>
                    <th class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice->items as $item)
                <tr>
                    <td>
                        <strong>{{ $item->description }}</strong>
                        @if($item->sku)
                            <br><small style="color: #6b7280;">SKU: {{ $item->sku }}</small>
                        @endif
                    </td>
                    <td class="text-center">{{ $item->quantity }}</td>
                    <td class="text-right">{{ number_format($item->unit_price, 2) }}</td>
                    <td class="text-right">{{ number_format($item->tax_amount, 2) }}</td>
                    <td class="text-right"><strong>{{ number_format($item->line_total, 2) }}</strong></td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Totals -->
        <div class="totals">
            <table>
                <tr class="subtotal-row">
                    <td><strong>Subtotal:</strong></td>
                    <td class="text-right">{{ $invoice->currency }} {{ number_format($invoice->subtotal, 2) }}</td>
                </tr>
                @if($invoice->discount_amount > 0)
                <tr>
                    <td>Discount:</td>
                    <td class="text-right">-{{ $invoice->currency }} {{ number_format($invoice->discount_amount, 2) }}</td>
                </tr>
                @endif
                @if($invoice->shipping_amount > 0)
                <tr>
                    <td>Shipping:</td>
                    <td class="text-right">{{ $invoice->currency }} {{ number_format($invoice->shipping_amount, 2) }}</td>
                </tr>
                @endif
                @if($invoice->tax_amount > 0)
                <tr>
                    <td>Tax:</td>
                    <td class="text-right">{{ $invoice->currency }} {{ number_format($invoice->tax_amount, 2) }}</td>
                </tr>
                @endif
                <tr class="total-row">
                    <td><strong>TOTAL:</strong></td>
                    <td class="text-right"><strong>{{ $invoice->currency }} {{ number_format($invoice->total_amount, 2) }}</strong></td>
                </tr>
                @if($invoice->paid_amount > 0)
                <tr style="background: #d1fae5;">
                    <td>Paid:</td>
                    <td class="text-right">-{{ $invoice->currency }} {{ number_format($invoice->paid_amount, 2) }}</td>
                </tr>
                <tr style="background: #fee2e2;">
                    <td><strong>Balance Due:</strong></td>
                    <td class="text-right"><strong>{{ $invoice->currency }} {{ number_format($invoice->getBalanceDue(), 2) }}</strong></td>
                </tr>
                @endif
            </table>
        </div>

        <!-- Notes -->
        @if($invoice->notes)
        <div class="notes">
            <div class="notes-title">Notes</div>
            {{ $invoice->notes }}
        </div>
        @endif

        <!-- Terms -->
        @if($invoice->terms)
        <div style="margin: 20px 0; padding: 15px; background: #f9fafb; border-radius: 5px;">
            <div style="font-weight: bold; margin-bottom: 5px;">Payment Terms</div>
            {{ $invoice->terms }}
        </div>
        @endif

        <!-- Footer -->
        <div class="footer">
            Thank you for your business!<br>
            This is a computer-generated invoice and does not require a signature.<br>
            {{ config('app.name') }} © {{ date('Y') }}
        </div>
    </div>
</body>
</html>
