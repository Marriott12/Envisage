<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Low Stock Alert - {{ $product->name }}</title>
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
            background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
            color: #ffffff;
            padding: 30px 20px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 600;
        }
        .warning-icon {
            font-size: 48px;
            margin-bottom: 10px;
        }
        .content {
            padding: 40px 30px;
        }
        .alert-box {
            background-color: #fee2e2;
            border-left: 4px solid #dc2626;
            padding: 20px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .stock-level {
            font-size: 42px;
            font-weight: 700;
            color: #dc2626;
            text-align: center;
            margin: 20px 0;
        }
        .product-card {
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            overflow: hidden;
            margin: 25px 0;
        }
        .product-header {
            display: flex;
            padding: 20px;
            gap: 15px;
        }
        .product-image {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 6px;
        }
        .product-details {
            flex: 1;
        }
        .product-name {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 8px;
        }
        .product-info-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            color: #6c757d;
            font-size: 14px;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 6px;
            margin: 20px 0;
        }
        .stat-item {
            text-align: center;
        }
        .stat-value {
            font-size: 28px;
            font-weight: 700;
            color: #dc2626;
        }
        .stat-label {
            font-size: 14px;
            color: #6c757d;
            margin-top: 5px;
        }
        .action-buttons {
            display: flex;
            gap: 10px;
            margin: 25px 0;
        }
        .action-button {
            flex: 1;
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #ffffff;
            padding: 16px 30px;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
            text-align: center;
        }
        .secondary-button {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        }
        .recommendations {
            background-color: #dbeafe;
            border-left: 4px solid #3b82f6;
            padding: 20px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .recommendation-item {
            padding: 8px 0;
        }
        .recommendation-item::before {
            content: "•";
            color: #3b82f6;
            font-weight: 700;
            margin-right: 10px;
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
            .product-header {
                flex-direction: column;
            }
            .action-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="warning-icon">⚠️</div>
            <h1>Low Stock Alert</h1>
        </div>
        
        <div class="content">
            <p style="font-size: 18px; margin-bottom: 20px;">
                Hi <strong>{{ $product->seller->name }}</strong>,
            </p>
            
            <div class="alert-box">
                <strong style="font-size: 16px;">⚠️ Action Required:</strong><br>
                One of your products is running low on inventory. Restock soon to avoid missing out on sales!
            </div>
            
            <div style="text-align: center; margin: 25px 0;">
                <div style="font-size: 16px; color: #6c757d; margin-bottom: 10px;">Current Stock Level</div>
                <div class="stock-level">{{ $product->inventory_count }} units</div>
                <div style="color: #dc2626; font-weight: 600;">
                    Below your threshold of {{ $alert->threshold_quantity }} units
                </div>
            </div>
            
            <div class="product-card">
                <div class="product-header">
                    @if($product->images)
                        <img src="{{ json_decode($product->images)[0] ?? '' }}" alt="{{ $product->name }}" class="product-image">
                    @endif
                    <div class="product-details">
                        <div class="product-name">{{ $product->name }}</div>
                        <div class="product-info-row">
                            <span>SKU:</span>
                            <span style="font-weight: 600;">{{ $product->sku ?? 'N/A' }}</span>
                        </div>
                        <div class="product-info-row">
                            <span>Price:</span>
                            <span style="font-weight: 600; color: #10b981;">${{ number_format($product->price, 2) }}</span>
                        </div>
                        <div class="product-info-row">
                            <span>Category:</span>
                            <span>{{ $product->category->name ?? 'Uncategorized' }}</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-value">{{ $product->inventory_count }}</div>
                    <div class="stat-label">Units Left</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value">{{ $alert->threshold_quantity }}</div>
                    <div class="stat-label">Alert Threshold</div>
                </div>
            </div>
            
            <div class="action-buttons">
                <a href="{{ url('/seller/products/' . $product->id . '/inventory') }}" class="action-button">
                    Update Stock
                </a>
                <a href="{{ url('/seller/products/' . $product->id . '/edit') }}" class="action-button secondary-button">
                    Edit Product
                </a>
            </div>
            
            <div class="recommendations">
                <strong style="color: #1e40af;">💡 Recommendations:</strong>
                <div class="recommendation-item">
                    Review your sales velocity to determine optimal restock quantity
                </div>
                <div class="recommendation-item">
                    Consider using bulk import for faster inventory updates
                </div>
                <div class="recommendation-item">
                    Adjust your low stock threshold if you're getting too many alerts
                </div>
                <div class="recommendation-item">
                    Set up automatic reordering with your suppliers to prevent stockouts
                </div>
            </div>
            
            <div style="background-color: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0; border-radius: 4px;">
                <strong>📊 Sales Impact:</strong><br>
                Products that go out of stock lose an average of 30% of potential customers. Keep your inventory stocked to maximize sales!
            </div>
            
            <p style="margin-top: 30px; color: #6c757d; font-size: 14px;">
                <strong>Note:</strong> You can adjust your low stock alert threshold in your product settings. This alert will not be sent again for this product until after you update the inventory.
            </p>
        </div>
        
        <div class="footer">
            <p><strong>Inventory Management Tips</strong></p>
            <p>Visit our <a href="{{ url('/seller/help/inventory') }}">Seller Help Center</a> for best practices</p>
            <p style="margin-top: 15px;">© {{ date('Y') }} Envisage Marketplace. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
