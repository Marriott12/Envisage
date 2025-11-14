<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background: #1e40af;
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
        .order-summary {
            background: white;
            padding: 20px;
            margin: 20px 0;
            border-radius: 5px;
            border: 1px solid #e5e7eb;
        }
        .item {
            padding: 10px 0;
            border-bottom: 1px solid #e5e7eb;
        }
        .item:last-child {
            border-bottom: none;
        }
        .total {
            font-size: 1.2em;
            font-weight: bold;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 2px solid #1e40af;
        }
        .button {
            display: inline-block;
            padding: 12px 24px;
            background: #1e40af;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            color: #6b7280;
            font-size: 0.9em;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ config('app.name') }}</h1>
    </div>
    
    <div class="content">
        <h2>Thank You for Your Order!</h2>
        
        <p>Hello {{ $user->name }},</p>
        
        <p>We've received your order and we're getting it ready. We'll send you a shipping confirmation email as soon as your order ships.</p>
        
        <div class="order-summary">
            <h3>Order Summary</h3>
            <p><strong>Order Number:</strong> #{{ $orderNumber }}</p>
            <p><strong>Order Date:</strong> {{ $orderDate }}</p>
            <p><strong>Shipping Address:</strong><br>{{ $shippingAddress }}</p>
            
            <h4 style="margin-top: 20px;">Order Items:</h4>
            @foreach($items as $item)
            <div class="item">
                <strong>{{ $item->product->name }}</strong><br>
                Quantity: {{ $item->quantity }} × ${{ number_format($item->price, 2) }}
                <div style="float: right;">${{ number_format($item->quantity * $item->price, 2) }}</div>
                <div style="clear: both;"></div>
            </div>
            @endforeach
            
            <div class="total">
                Total: ${{ $totalAmount }}
            </div>
        </div>
        
        <center>
            <a href="{{ $orderUrl }}" class="button">View Order Details</a>
        </center>
        
        <p>If you have any questions about your order, please don't hesitate to contact us.</p>
    </div>
    
    <div class="footer">
        <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
        <p>This is an automated email. Please do not reply to this message.</p>
    </div>
</body>
</html>
