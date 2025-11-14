<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Status Update</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; background-color: #f4f4f4; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 20px auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header { text-align: center; margin-bottom: 30px; }
        .header h1 { color: #4F46E5; margin: 0; }
        .status-badge { display: inline-block; padding: 8px 16px; border-radius: 20px; font-weight: bold; margin: 10px 0; }
        .status-pending { background: #FEF3C7; color: #92400E; }
        .status-processing { background: #DBEAFE; color: #1E40AF; }
        .status-shipped { background: #D1FAE5; color: #065F46; }
        .status-delivered { background: #D1FAE5; color: #065F46; }
        .status-cancelled { background: #FEE2E2; color: #991B1B; }
        .order-info { background: #F9FAFB; padding: 20px; border-radius: 8px; margin: 20px 0; }
        .order-info p { margin: 10px 0; }
        .order-info strong { color: #4F46E5; }
        .tracking { background: #EEF2FF; padding: 15px; border-radius: 8px; margin: 20px 0; }
        .btn { display: inline-block; padding: 12px 30px; background: #4F46E5; color: white; text-decoration: none; border-radius: 5px; margin: 20px 0; }
        .btn:hover { background: #4338CA; }
        .footer { text-align: center; margin-top: 30px; color: #6B7280; font-size: 14px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Order Status Update</h1>
        </div>

        <p>Hello {{ $order->user->name }},</p>
        
        <p>Your order status has been updated:</p>

        <div class="order-info">
            <p><strong>Order Number:</strong> #{{ $order->order_number }}</p>
            <p><strong>Order Date:</strong> {{ $order->created_at->format('M d, Y') }}</p>
            <p><strong>Current Status:</strong> 
                <span class="status-badge status-{{ $order->status }}">
                    {{ strtoupper($order->status) }}
                </span>
            </p>
            <p><strong>Total Amount:</strong> ${{ number_format($order->total_amount, 2) }}</p>
        </div>

        @if($order->tracking_number)
        <div class="tracking">
            <p><strong>Tracking Number:</strong> {{ $order->tracking_number }}</p>
            <p style="margin: 5px 0 0 0; font-size: 14px; color: #6B7280;">Use this tracking number to monitor your shipment</p>
        </div>
        @endif

        @if($order->notes)
        <div style="background: #FEF3C7; padding: 15px; border-radius: 8px; margin: 20px 0;">
            <p style="margin: 0;"><strong>Note from seller:</strong></p>
            <p style="margin: 10px 0 0 0;">{{ $order->notes }}</p>
        </div>
        @endif

        <div style="text-align: center;">
            <a href="{{ env('FRONTEND_URL') }}/orders/{{ $order->id }}" class="btn">View Order Details</a>
        </div>

        <div class="footer">
            <p>Thank you for shopping with us!</p>
            <p>If you have any questions, please contact our support team.</p>
        </div>
    </div>
</body>
</html>
