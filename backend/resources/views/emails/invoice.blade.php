<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background: #3b82f6;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 5px 5px 0 0;
        }
        .content {
            background: #f9fafb;
            padding: 30px;
            border: 1px solid #e5e7eb;
        }
        .button {
            display: inline-block;
            padding: 12px 24px;
            background: #3b82f6;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
        }
        .footer {
            text-align: center;
            padding: 20px;
            color: #6b7280;
            font-size: 12px;
        }
        .invoice-details {
            background: white;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Invoice from {{ config('app.name') }}</h1>
        </div>
        
        <div class="content">
            <p>Dear {{ $invoice->billing_name }},</p>
            
            <p>Thank you for your order! Please find your invoice attached to this email.</p>
            
            <div class="invoice-details">
                <strong>Invoice Number:</strong> {{ $invoice->invoice_number }}<br>
                <strong>Invoice Date:</strong> {{ $invoice->created_at->format('M d, Y') }}<br>
                <strong>Order ID:</strong> #{{ $invoice->order_id }}<br>
                <strong>Total Amount:</strong> {{ $invoice->currency }} {{ number_format($invoice->total_amount, 2) }}<br>
                @if($invoice->due_date)
                    <strong>Due Date:</strong> {{ $invoice->due_date->format('M d, Y') }}<br>
                @endif
                <strong>Status:</strong> <span style="color: {{ $invoice->status === 'paid' ? '#059669' : '#dc2626' }}">
                    {{ ucfirst($invoice->status) }}
                </span>
            </div>
            
            @if($invoice->status !== 'paid')
            <p>
                <strong>Amount Due:</strong> {{ $invoice->currency }} {{ number_format($invoice->getBalanceDue(), 2) }}
            </p>
            @endif
            
            <center>
                <a href="{{ $downloadUrl }}" class="button">Download Invoice PDF</a>
            </center>
            
            @if($invoice->notes)
            <p style="background: #fef3c7; padding: 15px; border-left: 4px solid #f59e0b;">
                <strong>Note:</strong> {{ $invoice->notes }}
            </p>
            @endif
            
            <p>If you have any questions about this invoice, please don't hesitate to contact us.</p>
            
            <p>Best regards,<br>{{ config('app.name') }} Team</p>
        </div>
        
        <div class="footer">
            © {{ date('Y') }} {{ config('app.name') }}. All rights reserved.<br>
            This email was sent to {{ $invoice->billing_email }}
        </div>
    </div>
</body>
</html>
