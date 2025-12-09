<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subscription Renewal Reminder</title>
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
            background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
            color: #ffffff;
            padding: 30px 20px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 600;
        }
        .icon {
            font-size: 48px;
            margin-bottom: 10px;
        }
        .content {
            padding: 40px 30px;
        }
        .renewal-box {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #ffffff;
            border-radius: 8px;
            padding: 25px;
            text-align: center;
            margin: 25px 0;
        }
        .renewal-date {
            font-size: 32px;
            font-weight: 700;
            margin: 15px 0;
        }
        .plan-details {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin: 25px 0;
        }
        .plan-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #e0e0e0;
        }
        .plan-row:last-child {
            border-bottom: none;
            font-size: 20px;
            font-weight: 700;
            color: #8b5cf6;
            margin-top: 10px;
        }
        .features-list {
            background-color: #faf5ff;
            border-radius: 6px;
            padding: 20px;
            margin: 20px 0;
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
        .action-button {
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
        .secondary-button {
            display: inline-block;
            background-color: transparent;
            color: #667eea;
            border: 2px solid #667eea;
            padding: 14px 38px;
            text-decoration: none;
            border-radius: 50px;
            font-weight: 600;
            font-size: 16px;
            margin: 10px;
        }
        .info-box {
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
            .renewal-date {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="icon">🔔</div>
            <h1>Subscription Renewal Reminder</h1>
        </div>
        
        <div class="content">
            <p style="font-size: 18px; margin-bottom: 20px;">
                Hi <strong>{{ $subscription->seller->name }}</strong>,
            </p>
            
            <p>Your <strong>{{ $subscription->plan->name }}</strong> subscription will automatically renew soon!</p>
            
            <div class="renewal-box">
                <div style="font-size: 16px; opacity: 0.9;">Renewal Date</div>
                <div class="renewal-date">{{ $subscription->end_date->format('M j, Y') }}</div>
                <div style="font-size: 14px; opacity: 0.9;">
                    {{ $daysUntilRenewal }} day{{ $daysUntilRenewal != 1 ? 's' : '' }} from now
                </div>
            </div>
            
            <div class="plan-details">
                <h3 style="margin-top: 0;">Your Plan Details</h3>
                <div class="plan-row">
                    <span style="color: #6c757d;">Plan:</span>
                    <span style="font-weight: 600;">{{ $subscription->plan->name }}</span>
                </div>
                <div class="plan-row">
                    <span style="color: #6c757d;">Billing Cycle:</span>
                    <span>{{ ucfirst($subscription->plan->billing_cycle) }}</span>
                </div>
                <div class="plan-row">
                    <span style="color: #6c757d;">Current Period:</span>
                    <span>{{ $subscription->start_date->format('M j') }} - {{ $subscription->end_date->format('M j, Y') }}</span>
                </div>
                <div class="plan-row">
                    <span>Renewal Amount:</span>
                    <span>${{ number_format($subscription->plan->price, 2) }}</span>
                </div>
            </div>
            
            <div class="features-list">
                <h3 style="margin-top: 0; color: #8b5cf6;">Your Plan Includes:</h3>
                <div class="feature-item">List up to {{ $subscription->plan->product_limit }} products</div>
                <div class="feature-item">{{ $subscription->plan->commission_rate }}% commission rate</div>
                <div class="feature-item">{{ $subscription->plan->featured_slots }} featured product slots</div>
                @if($subscription->plan->features)
                    @foreach(json_decode($subscription->plan->features, true) as $feature)
                        <div class="feature-item">{{ $feature }}</div>
                    @endforeach
                @endif
            </div>
            
            <div class="info-box">
                <strong>💳 Payment Information</strong><br>
                Your subscription will automatically renew using the payment method on file. You'll be charged <strong>${{ number_format($subscription->plan->price, 2) }}</strong> on {{ $subscription->end_date->format('F j, Y') }}.
            </div>
            
            <div style="text-align: center; margin: 30px 0;">
                <a href="{{ url('/seller/subscription') }}" class="action-button">
                    Manage Subscription
                </a>
                <br>
                <a href="{{ url('/seller/subscription/cancel') }}" class="secondary-button">
                    Cancel Auto-Renewal
                </a>
            </div>
            
            <p style="margin-top: 30px; color: #6c757d; font-size: 14px; text-align: center;">
                Want to upgrade or change your plan? Visit your subscription settings anytime.
            </p>
        </div>
        
        <div class="footer">
            <p><strong>Questions About Your Subscription?</strong></p>
            <p>Contact us at <a href="mailto:billing@envisage.com">billing@envisage.com</a></p>
            <p style="margin-top: 15px;">© {{ date('Y') }} Envisage Marketplace. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
