<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>You Earned Loyalty Points!</title>
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
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
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
        .points-box {
            background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
            color: #ffffff;
            border-radius: 8px;
            padding: 30px;
            text-align: center;
            margin: 25px 0;
            box-shadow: 0 4px 12px rgba(251, 191, 36, 0.3);
        }
        .points-earned {
            font-size: 48px;
            font-weight: 700;
            margin: 15px 0;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
        }
        .balance-row {
            display: flex;
            justify-content: space-around;
            background-color: #fef3c7;
            border-radius: 8px;
            padding: 20px;
            margin: 25px 0;
        }
        .balance-item {
            text-align: center;
        }
        .balance-label {
            font-size: 14px;
            color: #92400e;
            margin-bottom: 5px;
        }
        .balance-value {
            font-size: 28px;
            font-weight: 700;
            color: #d97706;
        }
        .tier-badge {
            display: inline-block;
            padding: 10px 25px;
            border-radius: 25px;
            font-weight: 700;
            font-size: 16px;
            margin: 15px 0;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .tier-bronze { background: linear-gradient(135deg, #cd7f32 0%, #b87333 100%); color: #fff; }
        .tier-silver { background: linear-gradient(135deg, #c0c0c0 0%, #a8a8a8 100%); color: #333; }
        .tier-gold { background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%); color: #333; }
        .tier-platinum { background: linear-gradient(135deg, #e5e4e2 0%, #b9b8b5 100%); color: #333; }
        .tier-diamond { background: linear-gradient(135deg, #b9f2ff 0%, #00d4ff 100%); color: #333; }
        .progress-bar {
            background-color: #f3f4f6;
            border-radius: 10px;
            height: 20px;
            margin: 20px 0;
            overflow: hidden;
        }
        .progress-fill {
            background: linear-gradient(90deg, #f59e0b 0%, #fbbf24 100%);
            height: 100%;
            border-radius: 10px;
            transition: width 0.3s ease;
        }
        .rewards-preview {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin: 25px 0;
        }
        .reward-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #e0e0e0;
        }
        .reward-item:last-child {
            border-bottom: none;
        }
        .reward-cost {
            background-color: #f59e0b;
            color: #ffffff;
            padding: 5px 12px;
            border-radius: 15px;
            font-weight: 600;
            font-size: 14px;
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
            .balance-row {
                flex-direction: column;
            }
            .balance-item {
                margin: 10px 0;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="icon">⭐</div>
            <h1>You Earned Points!</h1>
        </div>
        
        <div class="content">
            <p style="font-size: 18px; margin-bottom: 20px;">
                Congratulations, <strong>{{ $user->name }}</strong>! 🎉
            </p>
            
            <div class="points-box">
                <div style="font-size: 16px; opacity: 0.9;">You just earned</div>
                <div class="points-earned">+{{ number_format($pointsEarned) }} Points</div>
                <div style="font-size: 14px; opacity: 0.9;">{{ $reason }}</div>
            </div>
            
            <div class="balance-row">
                <div class="balance-item">
                    <div class="balance-label">Current Balance</div>
                    <div class="balance-value">{{ number_format($loyaltyPoints->current_balance) }}</div>
                </div>
                <div class="balance-item">
                    <div class="balance-label">Lifetime Points</div>
                    <div class="balance-value">{{ number_format($loyaltyPoints->lifetime_points) }}</div>
                </div>
            </div>
            
            <div style="text-align: center; margin: 25px 0;">
                <div style="font-size: 16px; color: #6c757d; margin-bottom: 10px;">Your Tier</div>
                <div class="tier-badge tier-{{ strtolower($loyaltyPoints->tier) }}">
                    {{ ucfirst($loyaltyPoints->tier) }} Member
                </div>
            </div>
            
            @php
                $nextTier = [
                    'bronze' => ['name' => 'Silver', 'required' => 500],
                    'silver' => ['name' => 'Gold', 'required' => 2000],
                    'gold' => ['name' => 'Platinum', 'required' => 5000],
                    'platinum' => ['name' => 'Diamond', 'required' => 10000],
                ];
                $currentTier = strtolower($loyaltyPoints->tier);
            @endphp
            
            @if(isset($nextTier[$currentTier]))
                @php
                    $target = $nextTier[$currentTier];
                    $progress = min(100, ($loyaltyPoints->lifetime_points / $target['required']) * 100);
                    $pointsNeeded = max(0, $target['required'] - $loyaltyPoints->lifetime_points);
                @endphp
                
                <div style="margin: 25px 0;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                        <span style="font-weight: 600;">Progress to {{ $target['name'] }}</span>
                        <span style="color: #f59e0b; font-weight: 600;">{{ number_format($pointsNeeded) }} points to go!</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: {{ $progress }}%;"></div>
                    </div>
                </div>
            @else
                <div style="background-color: #dbeafe; padding: 15px; border-radius: 6px; margin: 20px 0; text-align: center;">
                    <strong>🏆 You've reached the highest tier!</strong><br>
                    Thank you for being a valued member of our community.
                </div>
            @endif
            
            <div class="rewards-preview">
                <h3 style="margin-top: 0;">🎁 Redeem Your Points</h3>
                <p style="color: #6c757d; font-size: 14px;">Here's what you can get with your points:</p>
                
                <div class="reward-item">
                    <span>$5 Off Your Next Order</span>
                    <span class="reward-cost">500 pts</span>
                </div>
                <div class="reward-item">
                    <span>Free Shipping</span>
                    <span class="reward-cost">300 pts</span>
                </div>
                <div class="reward-item">
                    <span>$10 Gift Card</span>
                    <span class="reward-cost">1000 pts</span>
                </div>
                <div class="reward-item">
                    <span>$25 Gift Card</span>
                    <span class="reward-cost">2500 pts</span>
                </div>
            </div>
            
            <div style="text-align: center; margin: 30px 0;">
                <a href="{{ url('/loyalty/rewards') }}" class="action-button">
                    Browse All Rewards
                </a>
            </div>
            
            <div style="background-color: #fef3c7; border-left: 4px solid #f59e0b; padding: 15px; margin: 20px 0; border-radius: 4px;">
                <strong>💡 Earn More Points:</strong><br>
                • Make purchases (1 point per $1 spent)<br>
                • Refer friends (500 points per referral)<br>
                • Write product reviews (50 points each)<br>
                • Complete your profile (100 points)
            </div>
        </div>
        
        <div class="footer">
            <p><strong>Questions About Loyalty Points?</strong></p>
            <p>Visit our <a href="{{ url('/loyalty/faq') }}">FAQ page</a> or contact us at <a href="mailto:rewards@envisage.com">rewards@envisage.com</a></p>
            <p style="margin-top: 15px;">© {{ date('Y') }} Envisage Marketplace. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
