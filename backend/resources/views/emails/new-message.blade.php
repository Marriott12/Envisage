<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Message from {{ $message->sender->name }}</title>
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
        .message-icon {
            font-size: 48px;
            margin-bottom: 10px;
        }
        .content {
            padding: 40px 30px;
        }
        .sender-info {
            display: flex;
            align-items: center;
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .sender-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            font-weight: 700;
            margin-right: 15px;
            flex-shrink: 0;
        }
        .sender-details h3 {
            margin: 0 0 5px 0;
            font-size: 18px;
        }
        .sender-details p {
            margin: 0;
            color: #6c757d;
            font-size: 14px;
        }
        .message-box {
            background-color: #ffffff;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            padding: 25px;
            margin: 25px 0;
            position: relative;
        }
        .message-box::before {
            content: """;
            position: absolute;
            top: -10px;
            left: 20px;
            font-size: 60px;
            color: #e0e0e0;
            line-height: 1;
        }
        .message-text {
            font-size: 16px;
            color: #333;
            padding-top: 20px;
        }
        .message-meta {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-top: 1px solid #e0e0e0;
            margin-top: 15px;
            font-size: 14px;
            color: #6c757d;
        }
        .product-context {
            background-color: #faf5ff;
            border-left: 4px solid #8b5cf6;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .product-mini {
            display: flex;
            align-items: center;
            margin-top: 10px;
        }
        .product-mini-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 4px;
            margin-right: 12px;
        }
        .product-mini-name {
            font-weight: 600;
            font-size: 14px;
        }
        .product-mini-price {
            color: #667eea;
            font-weight: 600;
        }
        .attachments {
            background-color: #f8f9fa;
            border-radius: 6px;
            padding: 15px;
            margin: 20px 0;
        }
        .attachment-item {
            display: flex;
            align-items: center;
            padding: 8px 0;
        }
        .attachment-icon {
            font-size: 20px;
            margin-right: 10px;
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
        .quick-replies {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin: 25px 0;
        }
        .quick-reply {
            display: block;
            background-color: #ffffff;
            border: 2px solid #e0e0e0;
            padding: 12px 15px;
            margin: 8px 0;
            border-radius: 6px;
            text-decoration: none;
            color: #333;
            transition: all 0.2s;
        }
        .quick-reply:hover {
            border-color: #667eea;
            background-color: #faf5ff;
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
            .sender-info {
                flex-direction: column;
                text-align: center;
            }
            .sender-avatar {
                margin-right: 0;
                margin-bottom: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="message-icon">💬</div>
            <h1>New Message</h1>
        </div>
        
        <div class="content">
            <p style="font-size: 18px; margin-bottom: 20px;">
                You have a new message!
            </p>
            
            <div class="sender-info">
                <div class="sender-avatar">
                    {{ strtoupper(substr($message->sender->name, 0, 1)) }}
                </div>
                <div class="sender-details">
                    <h3>{{ $message->sender->name }}</h3>
                    <p>{{ $message->created_at->diffForHumans() }}</p>
                </div>
            </div>
            
            @if($conversation->product)
                <div class="product-context">
                    <strong>📦 Regarding this product:</strong>
                    <div class="product-mini">
                        @if($conversation->product->images)
                            <img src="{{ json_decode($conversation->product->images)[0] ?? '' }}" alt="{{ $conversation->product->name }}" class="product-mini-image">
                        @endif
                        <div>
                            <div class="product-mini-name">{{ $conversation->product->name }}</div>
                            <div class="product-mini-price">${{ number_format($conversation->product->price, 2) }}</div>
                        </div>
                    </div>
                </div>
            @endif
            
            <div class="message-box">
                <div class="message-text">
                    {!! nl2br(e($message->message)) !!}
                </div>
                <div class="message-meta">
                    <span>Conversation ID: #{{ $conversation->id }}</span>
                    <span>{{ $message->created_at->format('M j, Y g:i A') }}</span>
                </div>
            </div>
            
            @if($message->attachments && count(json_decode($message->attachments, true)) > 0)
                <div class="attachments">
                    <strong>📎 Attachments ({{ count(json_decode($message->attachments, true)) }}):</strong>
                    @foreach(json_decode($message->attachments, true) as $attachment)
                        <div class="attachment-item">
                            <span class="attachment-icon">📄</span>
                            <a href="{{ $attachment['url'] ?? '#' }}" style="color: #667eea;">
                                {{ $attachment['name'] ?? 'Attachment' }}
                            </a>
                        </div>
                    @endforeach
                </div>
            @endif
            
            <div style="text-align: center; margin: 30px 0;">
                <a href="{{ url('/messages/' . $conversation->id) }}" class="action-button">
                    Reply Now
                </a>
            </div>
            
            <div class="quick-replies">
                <strong>Quick Actions:</strong>
                <a href="{{ url('/messages/' . $conversation->id) }}" class="quick-reply">
                    💬 View Full Conversation
                </a>
                @if($conversation->product)
                    <a href="{{ url('/products/' . $conversation->product->id) }}" class="quick-reply">
                        👁️ View Product Details
                    </a>
                @endif
                <a href="{{ url('/messages') }}" class="quick-reply">
                    📥 View All Messages
                </a>
            </div>
            
            <div style="background-color: #dbeafe; border-left: 4px solid #3b82f6; padding: 15px; margin: 20px 0; border-radius: 4px;">
                <strong>💡 Tips for Great Communication:</strong><br>
                • Respond promptly to build trust with {{ $message->sender->name }}<br>
                • Be professional and courteous<br>
                • Answer all questions clearly<br>
                • Use the messaging system for all communication to stay protected
            </div>
            
            <p style="margin-top: 30px; color: #6c757d; font-size: 14px; text-align: center;">
                You can manage your notification preferences in your account settings.
            </p>
        </div>
        
        <div class="footer">
            <p><strong>Stay Connected</strong></p>
            <p>Download our mobile app for instant message notifications</p>
            <p style="margin-top: 15px;">© {{ date('Y') }} Envisage Marketplace. All rights reserved.</p>
            <p style="margin-top: 10px; font-size: 12px;">
                <a href="{{ url('/settings/notifications') }}">Manage Notification Preferences</a>
            </p>
        </div>
    </div>
</body>
</html>
