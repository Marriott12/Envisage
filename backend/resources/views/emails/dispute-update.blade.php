<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dispute Update - Case #{{ $dispute->id }}</title>
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
        .status-icon {
            font-size: 48px;
            margin-bottom: 10px;
        }
        .content {
            padding: 40px 30px;
        }
        .status-badge {
            display: inline-block;
            padding: 8px 20px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 14px;
            margin: 15px 0;
        }
        .status-pending {
            background-color: #fef3c7;
            color: #92400e;
        }
        .status-approved {
            background-color: #d1fae5;
            color: #065f46;
        }
        .status-rejected {
            background-color: #fee2e2;
            color: #991b1b;
        }
        .status-resolved {
            background-color: #dbeafe;
            color: #1e40af;
        }
        .dispute-details {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin: 25px 0;
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
        .message-box {
            background-color: #fff;
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            padding: 20px;
            margin: 20px 0;
        }
        .resolution-box {
            background-color: #d1fae5;
            border-left: 4px solid #10b981;
            padding: 20px;
            margin: 20px 0;
            border-radius: 4px;
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
        .timeline {
            margin: 30px 0;
        }
        .timeline-item {
            display: flex;
            margin: 15px 0;
        }
        .timeline-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background-color: #667eea;
            margin-right: 15px;
            margin-top: 5px;
            flex-shrink: 0;
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
            <div class="status-icon">🔍</div>
            <h1>Dispute Update</h1>
        </div>
        
        <div class="content">
            <p style="font-size: 18px; margin-bottom: 10px;">
                Hello <strong>{{ $dispute->user->name }}</strong>,
            </p>
            
            <p>There's an update on your dispute case. Here's the latest information:</p>
            
            <div style="text-align: center;">
                <div class="status-badge status-{{ $dispute->status }}">
                    Status: {{ ucfirst($dispute->status) }}
                </div>
            </div>
            
            <div class="dispute-details">
                <h3 style="margin-top: 0;">Dispute Information</h3>
                <div class="detail-row">
                    <span style="color: #6c757d;">Case ID:</span>
                    <span style="font-weight: 600;">#{{ $dispute->id }}</span>
                </div>
                <div class="detail-row">
                    <span style="color: #6c757d;">Order Number:</span>
                    <span>{{ $dispute->order->order_number }}</span>
                </div>
                <div class="detail-row">
                    <span style="color: #6c757d;">Type:</span>
                    <span>{{ ucwords(str_replace('_', ' ', $dispute->type)) }}</span>
                </div>
                <div class="detail-row">
                    <span style="color: #6c757d;">Filed Date:</span>
                    <span>{{ $dispute->created_at->format('F j, Y') }}</span>
                </div>
                <div class="detail-row">
                    <span style="color: #6c757d;">Last Updated:</span>
                    <span>{{ $dispute->updated_at->format('F j, Y g:i A') }}</span>
                </div>
            </div>
            
            @if($dispute->admin_response)
                <div class="message-box">
                    <h3 style="margin-top: 0;">📩 Message from Support Team</h3>
                    <p style="color: #333; margin: 0;">{{ $dispute->admin_response }}</p>
                </div>
            @endif
            
            @if($dispute->status === 'approved')
                <div class="resolution-box">
                    <strong>✓ Your dispute has been approved!</strong><br><br>
                    We've reviewed your case and determined it in your favor. 
                    @if($dispute->type === 'refund')
                        A refund of <strong>${{ number_format($dispute->refund_amount ?? 0, 2) }}</strong> will be processed to your original payment method within 5-7 business days.
                    @elseif($dispute->type === 'return')
                        You can now proceed with the return process. Check your email for return instructions.
                    @endif
                </div>
            @elseif($dispute->status === 'rejected')
                <div style="background-color: #fee2e2; border-left: 4px solid #ef4444; padding: 20px; margin: 20px 0; border-radius: 4px;">
                    <strong>Your dispute was not approved</strong><br><br>
                    After careful review, we were unable to approve your dispute request. If you believe this decision was made in error, you can provide additional information or evidence.
                </div>
            @elseif($dispute->status === 'resolved')
                <div class="resolution-box">
                    <strong>✓ Case Resolved</strong><br><br>
                    This dispute has been successfully resolved. Thank you for your patience throughout this process.
                </div>
            @endif
            
            <h3>Dispute Timeline</h3>
            <div class="timeline">
                <div class="timeline-item">
                    <div class="timeline-dot"></div>
                    <div>
                        <strong>Dispute Filed</strong><br>
                        <span style="color: #6c757d; font-size: 14px;">{{ $dispute->created_at->format('F j, Y g:i A') }}</span>
                    </div>
                </div>
                @if($dispute->status !== 'pending')
                    <div class="timeline-item">
                        <div class="timeline-dot"></div>
                        <div>
                            <strong>Under Review</strong><br>
                            <span style="color: #6c757d; font-size: 14px;">Our team is investigating your case</span>
                        </div>
                    </div>
                @endif
                @if(in_array($dispute->status, ['approved', 'rejected', 'resolved']))
                    <div class="timeline-item">
                        <div class="timeline-dot"></div>
                        <div>
                            <strong>{{ ucfirst($dispute->status) }}</strong><br>
                            <span style="color: #6c757d; font-size: 14px;">{{ $dispute->updated_at->format('F j, Y g:i A') }}</span>
                        </div>
                    </div>
                @endif
            </div>
            
            <div style="text-align: center; margin-top: 30px;">
                <a href="{{ url('/disputes/' . $dispute->id) }}" class="action-button">
                    View Full Details
                </a>
            </div>
            
            <p style="margin-top: 30px; color: #6c757d; font-size: 14px;">
                If you have additional questions or information to provide, please respond through your account dashboard or contact our support team.
            </p>
        </div>
        
        <div class="footer">
            <p><strong>Need More Help?</strong></p>
            <p>Contact Support: <a href="mailto:disputes@envisage.com">disputes@envisage.com</a></p>
            <p style="margin-top: 15px;">© {{ date('Y') }} Envisage Marketplace. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
