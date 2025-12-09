<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>⚡ Flash Sale Alert!</title>
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
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: #ffffff;
            padding: 30px 20px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        .header::before {
            content: "⚡";
            position: absolute;
            font-size: 120px;
            opacity: 0.1;
            top: -20px;
            right: -20px;
        }
        .header h1 {
            margin: 0;
            font-size: 32px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        .urgency-banner {
            background-color: #fef2f2;
            border-top: 3px solid #ef4444;
            border-bottom: 3px solid #ef4444;
            padding: 15px;
            text-align: center;
            font-weight: 700;
            color: #dc2626;
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.8; }
        }
        .content {
            padding: 40px 30px;
        }
        .countdown-box {
            background: linear-gradient(135deg, #1f2937 0%, #111827 100%);
            color: #ffffff;
            border-radius: 8px;
            padding: 25px;
            text-align: center;
            margin: 25px 0;
        }
        .countdown {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin: 20px 0;
        }
        .countdown-item {
            text-align: center;
        }
        .countdown-value {
            font-size: 36px;
            font-weight: 700;
            display: block;
            background-color: #ef4444;
            border-radius: 8px;
            padding: 10px 15px;
            min-width: 60px;
        }
        .countdown-label {
            font-size: 12px;
            opacity: 0.8;
            margin-top: 5px;
            text-transform: uppercase;
        }
        .discount-badge {
            background-color: #ef4444;
            color: #ffffff;
            font-size: 48px;
            font-weight: 700;
            padding: 20px;
            border-radius: 50%;
            display: inline-block;
            width: 120px;
            height: 120px;
            line-height: 80px;
            text-align: center;
            margin: 20px 0;
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.4);
        }
        .product-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin: 25px 0;
        }
        .product-card {
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            overflow: hidden;
            transition: transform 0.2s;
        }
        .product-image {
            width: 100%;
            height: 150px;
            object-fit: cover;
        }
        .product-info {
            padding: 12px;
        }
        .product-name {
            font-weight: 600;
            font-size: 14px;
            margin-bottom: 8px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .price-row {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .original-price {
            text-decoration: line-through;
            color: #6c757d;
            font-size: 14px;
        }
        .sale-price {
            color: #ef4444;
            font-weight: 700;
            font-size: 18px;
        }
        .stock-warning {
            background-color: #fff3cd;
            color: #856404;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            margin-top: 5px;
            display: inline-block;
        }
        .cta-button {
            display: inline-block;
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: #ffffff;
            padding: 18px 50px;
            text-decoration: none;
            border-radius: 50px;
            font-weight: 700;
            font-size: 18px;
            margin: 20px 0;
            text-transform: uppercase;
            letter-spacing: 1px;
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.4);
        }
        .features {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin: 25px 0;
        }
        .feature-item {
            display: flex;
            align-items: center;
            padding: 8px 0;
        }
        .feature-item::before {
            content: "✓";
            color: #10b981;
            font-weight: 700;
            margin-right: 10px;
            font-size: 18px;
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
            .countdown {
                gap: 8px;
            }
            .countdown-value {
                font-size: 28px;
                min-width: 50px;
                padding: 8px 10px;
            }
            .product-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>⚡ Flash Sale ⚡</h1>
            <div style="font-size: 18px; margin-top: 10px;">{{ $flashSale->name }}</div>
        </div>
        
        <div class="urgency-banner">
            🔥 LIMITED TIME OFFER - HURRY, WHILE SUPPLIES LAST! 🔥
        </div>
        
        <div class="content">
            <div style="text-align: center;">
                <div class="discount-badge">
                    {{ $flashSale->discount_percentage }}%<br>
                    <span style="font-size: 20px;">OFF</span>
                </div>
            </div>
            
            <p style="font-size: 18px; text-align: center; margin: 20px 0;">
                Don't miss out on incredible savings! This flash sale ends soon.
            </p>
            
            <div class="countdown-box">
                <div style="font-size: 16px; opacity: 0.9; margin-bottom: 10px;">Sale Ends In:</div>
                <div class="countdown">
                    @php
                        $now = now();
                        $end = $flashSale->end_time;
                        $diff = $now->diff($end);
                    @endphp
                    <div class="countdown-item">
                        <span class="countdown-value">{{ $diff->d }}</span>
                        <div class="countdown-label">Days</div>
                    </div>
                    <div class="countdown-item">
                        <span class="countdown-value">{{ $diff->h }}</span>
                        <div class="countdown-label">Hours</div>
                    </div>
                    <div class="countdown-item">
                        <span class="countdown-value">{{ $diff->i }}</span>
                        <div class="countdown-label">Minutes</div>
                    </div>
                </div>
                <div style="font-size: 14px; opacity: 0.8;">
                    Ends: {{ $flashSale->end_time->format('F j, Y \a\t g:i A') }}
                </div>
            </div>
            
            <h2 style="text-align: center;">Featured Deals</h2>
            
            <div class="product-grid">
                @foreach($flashSale->products->take(4) as $saleProduct)
                    <div class="product-card">
                        @if($saleProduct->product->images)
                            <img src="{{ json_decode($saleProduct->product->images)[0] ?? '' }}" alt="{{ $saleProduct->product->name }}" class="product-image">
                        @endif
                        <div class="product-info">
                            <div class="product-name">{{ $saleProduct->product->name }}</div>
                            <div class="price-row">
                                <span class="original-price">${{ number_format($saleProduct->product->price, 2) }}</span>
                                <span class="sale-price">${{ number_format($saleProduct->sale_price, 2) }}</span>
                            </div>
                            @if($saleProduct->quantity_available < 10)
                                <span class="stock-warning">Only {{ $saleProduct->quantity_available }} left!</span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
            
            @if($flashSale->products->count() > 4)
                <p style="text-align: center; color: #6c757d; font-size: 14px;">
                    + {{ $flashSale->products->count() - 4 }} more incredible deals!
                </p>
            @endif
            
            <div style="text-align: center; margin: 30px 0;">
                <a href="{{ url('/flash-sales/' . $flashSale->id) }}" class="cta-button">
                    Shop Now
                </a>
            </div>
            
            <div class="features">
                <h3 style="margin-top: 0;">Why Shop This Sale?</h3>
                <div class="feature-item">Lowest prices of the year</div>
                <div class="feature-item">Free shipping on all orders over $50</div>
                <div class="feature-item">Easy returns within 30 days</div>
                <div class="feature-item">Limited quantities - while supplies last</div>
            </div>
            
            <div style="background-color: #fef2f2; border-left: 4px solid #ef4444; padding: 15px; margin: 20px 0; border-radius: 4px;">
                <strong>⏰ Don't Wait!</strong><br>
                Items are selling fast and quantities are limited. Some products have purchase limits per customer.
            </div>
        </div>
        
        <div class="footer">
            <p><strong>Share the Savings!</strong></p>
            <p>Forward this email to friends and family</p>
            <p style="margin-top: 15px;">© {{ date('Y') }} Envisage Marketplace. All rights reserved.</p>
            <p style="margin-top: 10px; font-size: 12px;">
                <a href="{{ url('/unsubscribe') }}">Unsubscribe from sale notifications</a>
            </p>
        </div>
    </div>
</body>
</html>
