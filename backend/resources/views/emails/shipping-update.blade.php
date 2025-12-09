<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shipping Update - Order #{{ $order->order_number }}</title>
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
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: #ffffff;
            padding: 30px 20px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 600;
        }
        .shipping-icon {
            font-size: 48px;
            margin-bottom: 10px;
        }
        .content {
            padding: 40px 30px;
        }
        .tracking-box {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #ffffff;
            border-radius: 8px;
            padding: 25px;
            text-align: center;
            margin: 30px 0;
        }
        .tracking-number {
            font-size: 24px;
            font-weight: 700;
            letter-spacing: 2px;
            margin: 15px 0;
            word-break: break-all;
        }
        .track-button {
            display: inline-block;
            background-color: #ffffff;
            color: #667eea;
            padding: 14px 35px;
            text-decoration: none;
            border-radius: 50px;
            font-weight: 600;
            font-size: 16px;
            margin-top: 15px;
        }
        .status-timeline {
            margin: 30px 0;
            padding: 20px;
            background-color: #f8f9fa;
            border-radius: 6px;
        }
        .status-item {
            display: flex;
            align-items: center;
            padding: 10px 0;
        }
        .status-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background-color: #10b981;
            margin-right: 15px;
        }
        .status-dot.pending {
            background-color: #e0e0e0;
        }
        .order-summary {
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            padding: 20px;
            margin: 20px 0;
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
        }
        .delivery-estimate {
            background-color: #dbeafe;
            border-left: 4px solid #3b82f6;
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
            .tracking-number {
                font-size: 18px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="shipping-icon">📦</div>
            <h1>Your Order is On the Way!</h1>
        </div>
        
        <div class="content">
            <p style="font-size: 18px; margin-bottom: 20px;">
                Great news, <strong>{{ $order->user->name }}</strong>! Your order has shipped and is heading your way.
            </p>
            
            <div class="tracking-box">
                <div style="font-size: 14px; opacity: 0.9;">Tracking Number</div>
                <div class="tracking-number">{{ $trackingNumber }}</div>
                <div style="font-size: 14px; opacity: 0.9; margin-bottom: 10px;">
                    Carrier: <strong>{{ ucfirst($carrier) }}</strong>
                </div>
                <a href="{{ $trackingUrl }}" class="track-button">
                    Track Your Package
                </a>
            </div>
            
            @if($estimatedDelivery)
                <div class="delivery-estimate">
                    <strong>📅 Estimated Delivery:</strong> {{ $estimatedDelivery->format('l, F j, Y') }}
                </div>
            @endif
            
            <div class="status-timeline">
                <h3 style="margin-top: 0;">Shipping Progress</h3>
                <div class="status-item">
                    <span class="status-dot"></span>
                    <span>Order Confirmed</span>
                </div>
                <div class="status-item">
                    <span class="status-dot"></span>
                    <span>Package Prepared</span>
                </div>
                <div class="status-item">
                    <span class="status-dot"></span>
                    <span>Shipped - In Transit</span>
                </div>
                <div class="status-item">
                    <span class="status-dot pending"></span>
                    <span style="color: #6c757d;">Out for Delivery</span>
                </div>
                <div class="status-item">
                    <span class="status-dot pending"></span>
                    <span style="color: #6c757d;">Delivered</span>
                </div>
            </div>
            
            <div class="order-summary">
                <h3 style="margin-top: 0;">Order Summary</h3>
                <div class="summary-row">
                    <span style="color: #6c757d;">Order Number:</span>
                    <span style="font-weight: 600;">{{ $order->order_number }}</span>
                </div>
                <div class="summary-row">
                    <span style="color: #6c757d;">Order Date:</span>
                    <span>{{ $order->created_at->format('F j, Y') }}</span>
                </div>
                <div class="summary-row">
                    <span style="color: #6c757d;">Items:</span>
                    <span>{{ $order->items->count() }} item(s)</span>
                </div>
                <div class="summary-row">
                    <span style="color: #6c757d;">Total:</span>
                    <span style="font-weight: 600; color: #10b981;">${{ number_format($order->total_amount, 2) }}</span>
                </div>
            </div>
            
            <div style="background-color: #f8f9fa; padding: 15px; border-radius: 6px; margin: 20px 0;">
                <strong>📍 Shipping To:</strong><br>
                {{ $order->shipping_address }}<br>
                {{ $order->shipping_city }}, {{ $order->shipping_state }} {{ $order->shipping_zip }}
            </div>
            
            <p style="margin-top: 30px; color: #6c757d; font-size: 14px;">
                <strong>💡 Pro Tip:</strong> Make sure someone is available to receive the package. If you're not home, the carrier may leave a notice with pickup instructions.
            </p>
        </div>
        
        <div class="footer">
            <p><strong>Questions about your shipment?</strong></p>
            <p>Contact us at <a href="mailto:support@envisage.com">support@envisage.com</a></p>
            <p style="margin-top: 15px;">© {{ date('Y') }} Envisage Marketplace. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
