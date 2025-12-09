<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subject }}</title>
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #ffffff;
            padding: 30px 20px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 600;
        }
        .content {
            padding: 40px 30px;
        }
        .greeting {
            font-size: 18px;
            margin-bottom: 20px;
        }
        .cart-items {
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            margin: 20px 0;
        }
        .cart-item {
            display: flex;
            align-items: center;
            padding: 15px;
            border-bottom: 1px solid #e0e0e0;
        }
        .cart-item:last-child {
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
            font-size: 16px;
            margin-bottom: 5px;
        }
        .item-price {
            color: #667eea;
            font-size: 18px;
            font-weight: 700;
        }
        .discount-badge {
            background-color: #ff6b6b;
            color: #ffffff;
            padding: 8px 16px;
            border-radius: 20px;
            display: inline-block;
            margin: 20px 0;
            font-weight: 600;
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
            transition: transform 0.2s;
        }
        .cta-button:hover {
            transform: translateY(-2px);
        }
        .urgency {
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
        .social-links {
            margin: 15px 0;
        }
        .social-links a {
            display: inline-block;
            margin: 0 10px;
            color: #667eea;
            text-decoration: none;
        }
        .unsubscribe {
            margin-top: 15px;
            font-size: 12px;
        }
        .unsubscribe a {
            color: #6c757d;
            text-decoration: underline;
        }
        @media only screen and (max-width: 600px) {
            .container {
                margin: 0;
                border-radius: 0;
            }
            .content {
                padding: 30px 20px;
            }
            .cart-item {
                flex-direction: column;
                text-align: center;
            }
            .item-image {
                margin-right: 0;
                margin-bottom: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🛒 Your Cart is Waiting!</h1>
        </div>
        
        <div class="content">
            <p class="greeting">Hi {{ $cart->user->name }},</p>
            
            @if($emailType === '1_hour')
                <p>We noticed you left some amazing items in your cart. Don't worry, we've saved them for you!</p>
            @elseif($emailType === '24_hour')
                <p>Your cart is still waiting! These popular items are selling fast, and we'd hate for you to miss out.</p>
                <div class="urgency">
                    <strong>⚠️ Hurry!</strong> Some items in your cart are in high demand and stock is limited.
                </div>
            @else
                <p>This is your last reminder! We're holding your cart for just a little longer, but these items won't last forever.</p>
                @if($discountCode)
                    <div class="discount-badge">
                        🎉 SPECIAL OFFER: Use code <strong>{{ $discountCode }}</strong> for 10% OFF!
                    </div>
                @endif
            @endif
            
            <div class="cart-items">
                @foreach(json_decode($cart->items_json, true) as $item)
                    <div class="cart-item">
                        @if(isset($item['image']))
                            <img src="{{ $item['image'] }}" alt="{{ $item['name'] }}" class="item-image">
                        @endif
                        <div class="item-details">
                            <div class="item-name">{{ $item['name'] }}</div>
                            <div class="item-price">${{ number_format($item['price'], 2) }}</div>
                            @if(isset($item['quantity']) && $item['quantity'] > 1)
                                <div style="color: #6c757d; font-size: 14px;">Quantity: {{ $item['quantity'] }}</div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
            
            <div style="text-align: center;">
                <p style="font-size: 20px; font-weight: 600; margin: 20px 0;">
                    Total: <span style="color: #667eea;">${{ number_format($cart->total_amount, 2) }}</span>
                </p>
                
                <a href="{{ $recoveryUrl }}" class="cta-button">
                    Complete Your Purchase
                </a>
                
                @if($emailType === '3_day' && $discountCode)
                    <p style="font-size: 14px; color: #6c757d;">
                        Discount code expires in 24 hours!
                    </p>
                @endif
            </div>
            
            <p style="margin-top: 30px; color: #6c757d; font-size: 14px;">
                Need help? Our customer support team is here for you 24/7.
            </p>
        </div>
        
        <div class="footer">
            <div class="social-links">
                <a href="#">Facebook</a> | 
                <a href="#">Twitter</a> | 
                <a href="#">Instagram</a>
            </div>
            
            <p>© {{ date('Y') }} Envisage Marketplace. All rights reserved.</p>
            
            <div class="unsubscribe">
                <a href="{{ url('/unsubscribe') }}">Unsubscribe from cart reminders</a>
            </div>
        </div>
    </div>
</body>
</html>
