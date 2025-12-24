<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Envisage AI Platform Configuration v2.0
    |--------------------------------------------------------------------------
    |
    | Enterprise-grade AI/ML service configuration
    |
    */

    'enabled' => env('AI_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | OpenAI Configuration (GPT-4, DALL-E)
    |--------------------------------------------------------------------------
    */
    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'model' => env('OPENAI_MODEL', 'gpt-4-turbo'),
        'temperature' => env('OPENAI_TEMPERATURE', 0.7),
        'max_tokens' => env('OPENAI_MAX_TOKENS', 2000),
        'timeout' => env('OPENAI_TIMEOUT', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Recommendation Engine Configuration
    |--------------------------------------------------------------------------
    */
    'recommendations' => [
        'cache_ttl' => env('RECOMMENDATION_CACHE_TTL', 300), // 5 minutes
        'default_algorithm' => env('RECOMMENDATION_ALGORITHM', 'neural'),
        'max_results' => env('RECOMMENDATION_MAX_RESULTS', 12),
        'min_confidence' => env('RECOMMENDATION_MIN_CONFIDENCE', 0.3),
        
        'algorithms' => [
            'neural' => [
                'enabled' => true,
                'weight' => 0.4,
                'min_interactions' => 3,
            ],
            'bandit' => [
                'enabled' => true,
                'epsilon' => env('BANDIT_EPSILON', 0.1),
                'weight' => 0.3,
            ],
            'session' => [
                'enabled' => true,
                'weight' => 0.2,
                'lookback_hours' => 24,
            ],
            'context' => [
                'enabled' => true,
                'weight' => 0.1,
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Computer Vision Configuration
    |--------------------------------------------------------------------------
    */
    'vision' => [
        'model' => env('VISION_MODEL', 'efficientnet-b3'),
        'cache_ttl' => env('VISION_CACHE_TTL', 600), // 10 minutes
        'max_file_size' => env('VISION_MAX_FILE_SIZE', 10240), // 10MB in KB
        'allowed_types' => ['jpeg', 'jpg', 'png', 'webp'],
        'min_dimensions' => [
            'width' => 100,
            'height' => 100,
        ],
        'max_dimensions' => [
            'width' => 4096,
            'height' => 4096,
        ],
        'similarity_threshold' => env('VISION_SIMILARITY_THRESHOLD', 0.7),
        'color_palette_size' => 5,
    ],

    /*
    |--------------------------------------------------------------------------
    | Chatbot Configuration
    |--------------------------------------------------------------------------
    */
    'chatbot' => [
        'model' => env('CHATBOT_MODEL', 'gpt-4-turbo'),
        'max_context_messages' => env('CHATBOT_MAX_CONTEXT', 10),
        'intent_confidence_threshold' => env('CHATBOT_INTENT_THRESHOLD', 0.6),
        'cache_ttl' => env('CHATBOT_CACHE_TTL', 0), // No caching for conversations
        'rate_limit' => env('CHATBOT_RATE_LIMIT', 20), // messages per minute
        
        'intents' => [
            'product_search', 'order_status', 'return_request',
            'support', 'complaint', 'feedback', 'recommendation', 'general'
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Sentiment Analysis Configuration
    |--------------------------------------------------------------------------
    */
    'sentiment' => [
        'model' => env('SENTIMENT_MODEL', 'bert-base-uncased'),
        'cache_ttl' => env('SENTIMENT_CACHE_TTL', 600), // 10 minutes
        'batch_size' => env('SENTIMENT_BATCH_SIZE', 100),
        'fake_detection_threshold' => env('SENTIMENT_FAKE_THRESHOLD', 0.7),
        'summary_model' => env('SENTIMENT_SUMMARY_MODEL', 'bart-large-cnn'),
        
        'aspects' => [
            'quality', 'price', 'shipping', 'customer_service', 'packaging'
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Fraud Detection Configuration
    |--------------------------------------------------------------------------
    */
    'fraud' => [
        'enabled' => env('FRAUD_DETECTION_ENABLED', true),
        'cache_ttl' => env('FRAUD_CACHE_TTL', 60), // 1 minute
        'auto_block_threshold' => env('FRAUD_AUTO_BLOCK_THRESHOLD', 85),
        'review_threshold' => env('FRAUD_REVIEW_THRESHOLD', 60),
        'refresh_interval' => env('FRAUD_DASHBOARD_REFRESH', 30), // seconds
        
        'models' => [
            'ml' => [
                'enabled' => true,
                'model' => 'xgboost',
                'weight' => 0.40,
            ],
            'rules' => [
                'enabled' => true,
                'weight' => 0.25,
            ],
            'anomaly' => [
                'enabled' => true,
                'model' => 'isolation_forest',
                'weight' => 0.20,
            ],
            'graph' => [
                'enabled' => true,
                'model' => 'gnn',
                'weight' => 0.15,
            ],
        ],
        
        'risk_levels' => [
            'minimal' => ['min' => 0, 'max' => 20, 'action' => 'approve'],
            'low' => ['min' => 21, 'max' => 40, 'action' => 'approve'],
            'medium' => ['min' => 41, 'max' => 60, 'action' => 'review'],
            'high' => ['min' => 61, 'max' => 80, 'action' => 'review'],
            'critical' => ['min' => 81, 'max' => 100, 'action' => 'block'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Predictive Analytics Configuration
    |--------------------------------------------------------------------------
    */
    'predictive' => [
        'cache_ttl' => env('PREDICTIVE_CACHE_TTL', 3600), // 1 hour
        
        'demand_forecast' => [
            'model' => env('FORECAST_MODEL', 'prophet'),
            'days_ahead' => env('FORECAST_DAYS', 30),
            'confidence_interval' => 0.95,
        ],
        
        'churn_prediction' => [
            'model' => 'xgboost',
            'threshold' => env('CHURN_THRESHOLD', 0.7),
            'lookback_days' => 90,
        ],
        
        'clv_calculation' => [
            'method' => 'rfm_plus_ml',
            'discount_rate' => 0.1,
        ],
        
        'trending' => [
            'timeframe_days' => 7,
            'min_momentum_score' => 0.5,
            'max_results' => 20,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Content Generation Configuration
    |--------------------------------------------------------------------------
    */
    'content' => [
        'model' => env('CONTENT_MODEL', 'gpt-4-turbo'),
        'cache_ttl' => env('CONTENT_CACHE_TTL', 300), // 5 minutes
        'max_tokens' => env('CONTENT_MAX_TOKENS', 1000),
        
        'lengths' => [
            'short' => ['min' => 50, 'max' => 100],
            'medium' => ['min' => 150, 'max' => 250],
            'long' => ['min' => 300, 'max' => 500],
        ],
        
        'tones' => [
            'professional' => ['temperature' => 0.5, 'top_p' => 0.9],
            'casual' => ['temperature' => 0.7, 'top_p' => 0.9],
            'luxury' => ['temperature' => 0.6, 'top_p' => 0.85],
            'playful' => ['temperature' => 0.8, 'top_p' => 0.95],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Dynamic Pricing Configuration
    |--------------------------------------------------------------------------
    */
    'pricing' => [
        'enabled' => env('DYNAMIC_PRICING_ENABLED', true),
        'cache_ttl' => env('PRICING_CACHE_TTL', 300), // 5 minutes
        'model' => 'q_learning',
        'learning_rate' => 0.1,
        'discount_factor' => 0.95,
        
        'constraints' => [
            'min_margin_percent' => env('PRICING_MIN_MARGIN', 10),
            'max_discount_percent' => env('PRICING_MAX_DISCOUNT', 30),
            'competitor_weight' => 0.4,
            'demand_weight' => 0.6,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting Configuration
    |--------------------------------------------------------------------------
    */
    'rate_limits' => [
        'guest' => [
            'recommendations' => '10,1', // 10 per minute
            'chat' => '5,1',
            'visual_search' => '3,1',
            'content_generation' => '2,1',
        ],
        'customer' => [
            'recommendations' => '30,1',
            'chat' => '20,1',
            'visual_search' => '10,1',
            'content_generation' => '5,1',
        ],
        'premium' => [
            'recommendations' => '100,1',
            'chat' => '50,1',
            'visual_search' => '30,1',
            'content_generation' => '20,1',
        ],
        'admin' => [
            'recommendations' => '1000,1',
            'chat' => '500,1',
            'visual_search' => '100,1',
            'content_generation' => '100,1',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Monitoring
    |--------------------------------------------------------------------------
    */
    'monitoring' => [
        'enabled' => env('AI_MONITORING_ENABLED', true),
        'log_slow_queries' => env('AI_LOG_SLOW_QUERIES', true),
        'slow_query_threshold' => env('AI_SLOW_QUERY_MS', 1000), // ms
        'track_token_usage' => env('AI_TRACK_TOKENS', true),
        'track_costs' => env('AI_TRACK_COSTS', true),
        
        'sentry' => [
            'enabled' => env('SENTRY_ENABLED', false),
            'dsn' => env('SENTRY_LARAVEL_DSN'),
            'environment' => env('APP_ENV', 'production'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | A/B Testing Configuration
    |--------------------------------------------------------------------------
    */
    'ab_testing' => [
        'enabled' => env('AB_TESTING_ENABLED', true),
        'default_variant' => 'control',
        'assignment_method' => 'user_id_hash', // or 'random'
        
        'experiments' => [
            'recommendation_algorithm' => [
                'variants' => ['neural', 'bandit', 'hybrid'],
                'traffic_split' => [0.34, 0.33, 0.33],
            ],
            'fraud_threshold' => [
                'variants' => [80, 85, 90],
                'traffic_split' => [0.33, 0.34, 0.33],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Model Versioning
    |--------------------------------------------------------------------------
    */
    'versioning' => [
        'enabled' => env('AI_VERSIONING_ENABLED', true),
        'default_version' => 'v2',
        
        'models' => [
            'recommendations' => [
                'v1' => ['status' => 'stable', 'traffic' => 0.2],
                'v2' => ['status' => 'canary', 'traffic' => 0.8],
            ],
            'fraud_detection' => [
                'v1' => ['status' => 'stable', 'traffic' => 0.1],
                'v2' => ['status' => 'production', 'traffic' => 0.9],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Explainability Configuration
    |--------------------------------------------------------------------------
    */
    'explainability' => [
        'enabled' => env('AI_EXPLAINABILITY_ENABLED', true),
        'max_reasons' => 5,
        'min_confidence_to_explain' => 0.5,
    ],

    /*
    |--------------------------------------------------------------------------
    | Cost Management
    |--------------------------------------------------------------------------
    */
    'costs' => [
        'openai_pricing' => [
            'gpt-4-turbo' => ['input' => 0.01, 'output' => 0.03], // per 1k tokens
            'gpt-3.5-turbo' => ['input' => 0.0005, 'output' => 0.0015],
        ],
        'budget_alerts' => [
            'daily_limit' => env('AI_DAILY_BUDGET', 100),
            'monthly_limit' => env('AI_MONTHLY_BUDGET', 2000),
            'alert_threshold' => 0.8, // 80% of limit
        ],
    ],
];
