<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome</title>
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
            padding: 30px;
            text-align: center;
            border-radius: 5px 5px 0 0;
        }
        .content {
            background: #f9fafb;
            padding: 30px;
            border: 1px solid #e5e7eb;
        }
        .feature-box {
            background: white;
            padding: 15px;
            margin: 15px 0;
            border-radius: 5px;
            border-left: 4px solid #1e40af;
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
        <h1>Welcome to {{ config('app.name') }}!</h1>
    </div>
    
    <div class="content">
        <h2>Hi {{ $userName }},</h2>
        
        <p>Thank you for joining {{ config('app.name') }}! We're excited to have you as part of our community.</p>
        
        <p>You can now:</p>
        
        <div class="feature-box">
            <strong>🛍️ Browse Products</strong><br>
            Discover thousands of products from verified sellers
        </div>
        
        <div class="feature-box">
            <strong>❤️ Save Favorites</strong><br>
            Keep track of products you love
        </div>
        
        <div class="feature-box">
            <strong>🛒 Shop Securely</strong><br>
            Enjoy safe and secure checkout
        </div>
        
        <div class="feature-box">
            <strong>📦 Track Orders</strong><br>
            Monitor your orders from purchase to delivery
        </div>
        
        <center>
            <a href="{{ $productsUrl }}" class="button">Start Shopping</a>
        </center>
        
        <p>If you have any questions, our support team is here to help!</p>
        
        <p>Happy shopping!<br>
        The {{ config('app.name') }} Team</p>
    </div>
    
    <div class="footer">
        <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
        <p>This is an automated email. Please do not reply to this message.</p>
    </div>
</body>
</html>
