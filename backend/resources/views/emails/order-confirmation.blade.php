<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation - #{{ $order->order_number }}</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 20px auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .header {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: #ffffff;
            padding: 30px 20px;
            text-align: center;
        }
        .success-icon {
            font-size: 48px;
            margin-bottom: 10px;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 600;
        }
        .order-number {
            font-size: 16px;
            opacity: 0.9;
            margin-top: 5px;
        }
        .content {
            padding: 40px 30px;
        }
        .info-box {
            background-color: #f8f9fa;
            border-radius: 6px;
            padding: 20px;
            margin: 20px 0;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #e0e0e0;
        }
        .info-row:last-child {
            border-bottom: none;
        }
        .info-label {
            font-weight: 600;
            color: #6c757d;
        }
        .order-items {
            margin: 30px 0;
        }
        .item {
            display: flex;
            padding: 15px 0;
            border-bottom: 1px solid #e0e0e0;
        }
        .item:last-child {
            border-bottom: none;
        }
        .item-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 4px;
            margin-right: 15px;
        }
        .item-details {
            flex: 1;
        }
        .item-name {
            font-weight: 600;
            margin-bottom: 5px;
        }
        .item-price {
            color: #10b981;
            font-weight: 600;
        }
        .totals {
            background-color: #f8f9fa;
            border-radius: 6px;
            padding: 20px;
            margin: 20px 0;
        }
        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
        }
        .total-row.grand-total {
            font-size: 20px;
            font-weight: 700;
            color: #10b981;
            border-top: 2px solid #10b981;
            padding-top: 15px;
            margin-top: 10px;
        }
        .cta-button {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #ffffff;
            padding: 16px 40px;
            text-decoration: none;
            border-radius: 50px;
            font-weight: 600;
            font-size: 16px;
            margin: 20px 0;
        }
        .shipping-address {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .footer {
            background-color: #f8f9fa;
            padding: 20px 30px;
            text-align: center;
            font-size: 14px;
            color: #6c757d;
        }
        @media only screen and (max-width: 600px) {
            .container {
                margin: 0;
                border-radius: 0;
            }
            .content {
                padding: 30px 20px;
            }
            .item {
                flex-direction: column;
            }
            .item-image {
                margin-bottom: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="success-icon">✓</div>
            <h1>Order Confirmed!</h1>
            <div class="order-number">Order #{{ $order->order_number }}</div>
        </div>
        
        <div class="content">
            <p style="font-size: 18px; margin-bottom: 20px;">
                Thank you, <strong>{{ $order->user->name }}</strong>! Your order has been received and is being processed.
            </p>
            
            <div class="info-box">
                <div class="info-row">
                    <span class="info-label">Order Date:</span>
                    <span>{{ $order->created_at->format('F j, Y') }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Payment Method:</span>
                    <span>{{ ucfirst($order->payment_method) }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Order Status:</span>
                    <span style="color: #10b981; font-weight: 600;">{{ ucfirst($order->status) }}</span>
                </div>
            </div>
            
            <h2 style="margin-top: 30px;">Order Items</h2>
            <div class="order-items">
                @foreach($order->items as $item)
                    <div class="item">
                        @if($item->product->images)
                            <img src="{{ json_decode($item->product->images)[0] ?? '' }}" alt="{{ $item->product->name }}" class="item-image">
                        @endif
                        <div class="item-details">
                            <div class="item-name">{{ $item->product->name }}</div>
                            <div style="color: #6c757d; font-size: 14px;">Quantity: {{ $item->quantity }}</div>
                            <div class="item-price">${{ number_format($item->price, 2) }} each</div>
                        </div>
                        <div style="font-weight: 600;">${{ number_format($item->price * $item->quantity, 2) }}</div>
                    </div>
                @endforeach
            </div>
            
            <div class="totals">
                <div class="total-row">
                    <span>Subtotal:</span>
                    <span>${{ number_format($order->total_amount - $order->shipping_amount, 2) }}</span>
                </div>
                <div class="total-row">
                    <span>Shipping:</span>
                    <span>${{ number_format($order->shipping_amount, 2) }}</span>
                </div>
                @if($order->discount_amount > 0)
                    <div class="total-row" style="color: #10b981;">
                        <span>Discount:</span>
                        <span>-${{ number_format($order->discount_amount, 2) }}</span>
                    </div>
                @endif
                <div class="total-row grand-total">
                    <span>Total:</span>
                    <span>${{ number_format($order->total_amount, 2) }}</span>
                </div>
            </div>
            
            <div class="shipping-address">
                <strong>📦 Shipping Address:</strong><br>
                {{ $order->shipping_address }}<br>
                {{ $order->shipping_city }}, {{ $order->shipping_state }} {{ $order->shipping_zip }}<br>
                {{ $order->shipping_country }}
            </div>
            
            <div style="text-align: center; margin-top: 30px;">
                <a href="{{ url('/orders/' . $order->id) }}" class="cta-button">
                    Track Your Order
                </a>
            </div>
            
            <p style="margin-top: 30px; color: #6c757d; font-size: 14px; text-align: center;">
                We'll send you a shipping confirmation email with tracking information once your order ships.
            </p>
        </div>
        
        <div class="footer">
            <p><strong>Need Help?</strong></p>
            <p>Contact us at <a href="mailto:support@envisage.com">support@envisage.com</a></p>
            <p style="margin-top: 15px;">© {{ date('Y') }} Envisage Marketplace. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
