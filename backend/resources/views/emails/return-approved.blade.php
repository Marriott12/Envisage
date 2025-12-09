<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Return Approved - Order #{{ $return->order->order_number }}</title>
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
        .header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 600;
        }
        .success-icon {
            font-size: 48px;
            margin-bottom: 10px;
        }
        .content {
            padding: 40px 30px;
        }
        .info-box {
            background-color: #d1fae5;
            border-left: 4px solid #10b981;
            padding: 20px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .steps {
            margin: 30px 0;
        }
        .step {
            display: flex;
            margin: 20px 0;
            align-items: flex-start;
        }
        .step-number {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #ffffff;
            width: 35px;
            height: 35px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            flex-shrink: 0;
            margin-right: 15px;
        }
        .step-content h3 {
            margin: 0 0 5px 0;
            font-size: 16px;
        }
        .step-content p {
            margin: 0;
            color: #6c757d;
            font-size: 14px;
        }
        .shipping-label-box {
            background-color: #f8f9fa;
            border: 2px dashed #667eea;
            border-radius: 8px;
            padding: 25px;
            text-align: center;
            margin: 25px 0;
        }
        .download-button {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #ffffff;
            padding: 16px 40px;
            text-decoration: none;
            border-radius: 50px;
            font-weight: 600;
            font-size: 16px;
            margin: 15px 0;
        }
        .return-details {
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            padding: 20px;
            margin: 20px 0;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #e0e0e0;
        }
        .detail-row:last-child {
            border-bottom: none;
        }
        .warning-box {
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
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="success-icon">✓</div>
            <h1>Return Approved!</h1>
        </div>
        
        <div class="content">
            <p style="font-size: 18px; margin-bottom: 20px;">
                Good news, <strong>{{ $return->order->user->name }}</strong>! Your return request has been approved.
            </p>
            
            <div class="info-box">
                <strong>💰 Refund Amount: ${{ number_format($return->refund_amount, 2) }}</strong><br>
                You'll receive your refund within 5-7 business days after we receive and inspect the returned item.
            </div>
            
            <h2>Next Steps</h2>
            <div class="steps">
                <div class="step">
                    <div class="step-number">1</div>
                    <div class="step-content">
                        <h3>Download Your Return Label</h3>
                        <p>Print the prepaid shipping label below. No need to pay for return shipping!</p>
                    </div>
                </div>
                
                <div class="step">
                    <div class="step-number">2</div>
                    <div class="step-content">
                        <h3>Package the Item</h3>
                        <p>Securely pack the item in its original packaging if possible. Include all accessories and documentation.</p>
                    </div>
                </div>
                
                <div class="step">
                    <div class="step-number">3</div>
                    <div class="step-content">
                        <h3>Attach Label & Ship</h3>
                        <p>Attach the return label to the package and drop it off at any {{ ucfirst($carrier ?? 'carrier') }} location.</p>
                    </div>
                </div>
                
                <div class="step">
                    <div class="step-number">4</div>
                    <div class="step-content">
                        <h3>Track Your Return</h3>
                        <p>We'll email you when we receive your return and process your refund.</p>
                    </div>
                </div>
            </div>
            
            <div class="shipping-label-box">
                <div style="font-size: 18px; font-weight: 600; margin-bottom: 10px;">
                    📄 Return Shipping Label
                </div>
                <p style="color: #6c757d; margin: 10px 0;">
                    Click below to download your prepaid return label
                </p>
                <a href="{{ $labelUrl }}" class="download-button">
                    Download Label
                </a>
            </div>
            
            <div class="return-details">
                <h3 style="margin-top: 0;">Return Details</h3>
                <div class="detail-row">
                    <span style="color: #6c757d;">Return ID:</span>
                    <span style="font-weight: 600;">#{{ $return->id }}</span>
                </div>
                <div class="detail-row">
                    <span style="color: #6c757d;">Order Number:</span>
                    <span>{{ $return->order->order_number }}</span>
                </div>
                <div class="detail-row">
                    <span style="color: #6c757d;">Reason:</span>
                    <span>{{ ucwords(str_replace('_', ' ', $return->reason)) }}</span>
                </div>
                <div class="detail-row">
                    <span style="color: #6c757d;">Approved Date:</span>
                    <span>{{ $return->updated_at->format('F j, Y') }}</span>
                </div>
            </div>
            
            <div class="warning-box">
                <strong>⚠️ Important:</strong> Please ship your return within 7 days. Returns received after 30 days from approval may not be eligible for a refund.
            </div>
            
            <p style="margin-top: 30px; color: #6c757d; font-size: 14px;">
                Have questions? We're here to help! Contact our support team anytime.
            </p>
        </div>
        
        <div class="footer">
            <p><strong>Need Assistance?</strong></p>
            <p>Email: <a href="mailto:returns@envisage.com">returns@envisage.com</a></p>
            <p>Phone: 1-800-ENVISAGE</p>
            <p style="margin-top: 15px;">© {{ date('Y') }} Envisage Marketplace. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
