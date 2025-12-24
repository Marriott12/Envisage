# Quick Reference - Enterprise AI Infrastructure

## 🚀 Immediate Usage Guide

### API Authentication

**Get API Token:**
```php
$token = $user->createToken('api-token')->plainTextToken;
```

**Use Token in Request:**
```bash
curl -H "Authorization: Bearer {token}" \
     -X POST http://yoursite.com/api/ai/recommendations
```

### Protect Routes

**In routes/api.php:**
```php
// Require authentication only
Route::middleware(['auth:sanctum'])
    ->post('/ai/chat', [AIController::class, 'chat']);

// Require specific role
Route::middleware(['auth:sanctum', 'has.role:admin'])
    ->post('/ai/fraud/review', [FraudController::class, 'review']);

// Require specific permission
Route::middleware(['auth:sanctum', 'has.permission:generate-content'])
    ->post('/ai/content/generate', [ContentController::class, 'generate']);

// Apply AI rate limiting
Route::middleware(['ai.ratelimit:recommendations'])
    ->get('/ai/recommendations', [AIController::class, 'recommendations']);
```

### Queue Jobs

**Dispatch Jobs:**
```php
use App\Jobs\AI\GenerateRecommendationsJob;
use App\Jobs\AI\AnalyzeSentimentJob;

// Generate recommendations in background
GenerateRecommendationsJob::dispatch($userId, $context, 'neural', 20);

// Analyze product sentiment
AnalyzeSentimentJob::dispatch($productId);
```

**Start Queue Worker:**
```bash
# Development
php artisan queue:work

# Production (use Supervisor)
php artisan queue:work redis --tries=3 --timeout=90
```

### A/B Testing

**Create Experiment:**
```php
use App\Services\ABTestService;

$abTest = app(ABTestService::class);

$experiment = $abTest->createExperiment([
    'name' => 'recommendation_algorithm_test',
    'type' => 'algorithm',
    'description' => 'Test neural vs bandit algorithm',
    'variants' => ['control', 'treatment_a', 'treatment_b'],
    'traffic_split' => ['control' => 40, 'treatment_a' => 30, 'treatment_b' => 30],
    'primary_metric' => 'click_through_rate',
]);

$abTest->startExperiment($experiment->id);
```

**Assign & Track:**
```php
// Assign user to variant
$variant = $abTest->assignVariant('recommendation_algorithm_test', $user);

// Track metric
$abTest->trackMetric(
    'recommendation_algorithm_test',
    'click_through_rate',
    0.15,
    $user
);

// Get results
$results = $abTest->getResults('recommendation_algorithm_test');
```

### User Permissions

**Assign Role:**
```php
$user->assignRole('customer');
$user->assignRole(['customer', 'seller']);
```

**Check Permission:**
```php
if ($user->hasPermissionTo('generate-content')) {
    // User can generate content
}

if ($user->hasRole('admin')) {
    // User is admin
}

if ($user->hasAnyRole(['admin', 'moderator'])) {
    // User has at least one role
}
```

**Direct Permission Grant:**
```php
use Spatie\Permission\Models\Permission;

$permission = Permission::findByName('use-chatbot');
$user->givePermissionTo($permission);
```

### Caching

**Use AI Cache:**
```php
use App\Services\AICacheService;

$cache = app(AICacheService::class);

// Cache AI response
$recommendations = $cache->remember(
    'recommendations',
    "user_{$userId}_algo_neural",
    function() {
        return $this->generateRecommendations();
    },
    300 // TTL in seconds (optional)
);

// Clear cache
$cache->forget('recommendations', "user_{$userId}_algo_neural");

// Clear all service cache
$cache->flushService('recommendations');
```

### Metrics Tracking

**Track Request:**
```php
use App\Services\AIMetricsService;

$metrics = app(AIMetricsService::class);

$metrics->trackRequest(
    'visual_search',
    '/api/ai/visual-search',
    $responseTimeMs,
    $success,
    [
        'user_id' => $userId,
        'tokens_used' => 150,
        'cost_usd' => 0.005,
        'image_size' => '2MB',
    ]
);
```

**Get Metrics:**
```php
// Service performance (last 7 days)
$performance = $metrics->getServiceMetrics('recommendations', 7);

// Cost summary
$costs = $metrics->getCostSummary(now()->subMonth(), now());

// Budget alerts
$alerts = $metrics->checkBudgetAlerts();
```

### Form Validation

**Use in Controller:**
```php
use App\Http\Requests\AI\VisualSearchRequest;
use App\Http\Requests\AI\ChatRequest;
use App\Http\Requests\AI\GenerateContentRequest;

public function visualSearch(VisualSearchRequest $request)
{
    $validated = $request->validated();
    // $validated['image'] is validated file
    // $validated['max_results'] is int
    // $validated['similarity_threshold'] is float
}

public function chat(ChatRequest $request)
{
    $validated = $request->validated();
    // $validated['message'] is sanitized string
    // $validated['conversation_id'] is UUID
}

public function generateContent(GenerateContentRequest $request)
{
    $validated = $request->validated();
    // $validated['content_type'] is one of: product_description, email, blog_post, social_media
    // $validated['prompt'] is sanitized
    // $validated['tone'] is validated
}
```

---

## 🔑 Environment Variables (Critical)

**Required for Production:**
```env
# OpenAI
OPENAI_API_KEY=sk-...
OPENAI_MODEL=gpt-4-turbo
OPENAI_TEMPERATURE=0.7
OPENAI_MAX_TOKENS=2000

# Sentry Error Tracking
SENTRY_LARAVEL_DSN=https://...@sentry.io/...
SENTRY_TRACES_SAMPLE_RATE=0.2

# Redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
REDIS_CACHE_DB=1
REDIS_SESSION_DB=2
REDIS_QUEUE_DB=3

# Queue
QUEUE_CONNECTION=redis

# Cache
CACHE_DRIVER=redis

# Session
SESSION_DRIVER=redis

# Broadcasting (WebSocket)
BROADCAST_DRIVER=pusher
PUSHER_APP_ID=
PUSHER_APP_KEY=
PUSHER_APP_SECRET=
PUSHER_APP_CLUSTER=

# AI Budget
AI_DAILY_BUDGET=100
AI_MONTHLY_BUDGET=2000
AI_BUDGET_ALERT_THRESHOLD=80

# AI Features
AI_ENABLED=true
AI_CACHE_ENABLED=true
AI_METRICS_ENABLED=true
```

---

## 📋 Common Commands

**Setup:**
```bash
# Install dependencies
composer install

# Run migrations
php artisan migrate --force

# Seed roles and permissions
php artisan db:seed --class=RolesAndPermissionsSeeder

# Clear caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
```

**Queue Management:**
```bash
# Start worker
php artisan queue:work redis

# Check failed jobs
php artisan queue:failed

# Retry failed job
php artisan queue:retry {id}

# Retry all failed jobs
php artisan queue:retry all

# Clear failed jobs
php artisan queue:flush
```

**Monitoring:**
```bash
# Monitor queue in real-time
php artisan queue:listen

# Check migrations status
php artisan migrate:status

# Database statistics
php artisan tinker
>>> DB::table('ai_metrics')->count();
>>> DB::table('ai_costs')->sum('total_cost_usd');
```

**Redis:**
```bash
# Connect to Redis CLI
redis-cli

# Check AI cache keys
KEYS ai:cache:*

# Check rate limit keys
KEYS ai:ratelimit:*

# Check experiment assignments
KEYS ab_test:*

# Clear all AI cache
redis-cli --scan --pattern 'ai:cache:*' | xargs redis-cli DEL
```

---

## 🎯 Rate Limits by Service

| Service | Guest | Customer | Premium | Admin |
|---------|-------|----------|---------|-------|
| Recommendations | 10/min | 20/min | 50/min | 200/min |
| Visual Search | 5/min | 15/min | 40/min | 100/min |
| Chatbot | 3/min | 10/min | 30/min | 100/min |
| Content Gen | - | 5/min | 20/min | 50/min |
| Sentiment | 10/min | 30/min | 100/min | 500/min |
| Fraud Detection | - | - | - | 50/min |

---

## 🔒 Permission Matrix

| Permission | Customer | Premium | Seller | Moderator | Analyst | Admin |
|------------|----------|---------|--------|-----------|---------|-------|
| use-visual-search | ✅ | ✅ | ✅ | ❌ | ❌ | ✅ |
| view-recommendations | ✅ | ✅ | ✅ | ❌ | ❌ | ✅ |
| use-chatbot | ✅ | ✅ | ✅ | ✅ | ❌ | ✅ |
| generate-content | ❌ | ✅ | ✅ | ❌ | ❌ | ✅ |
| view-sentiment | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| review-fraud-alerts | ❌ | ❌ | ❌ | ✅ | ❌ | ✅ |
| view-ai-metrics | ❌ | ❌ | ✅ | ❌ | ✅ | ✅ |
| manage-ab-experiments | ❌ | ❌ | ❌ | ❌ | ✅ | ✅ |

---

## 📊 Database Tables Quick Reference

**Analytics:**
- `ai_metrics` - All AI requests
- `ai_costs` - Daily aggregates
- `recommendation_clicks` - CTR tracking
- `chatbot_conversations` - Bot sessions
- `visual_searches` - Image searches
- `generated_content` - Content history
- `sentiment_cache` - Cached analysis

**A/B Testing:**
- `ab_experiments` - Test configs
- `ab_test_results` - Test data

**Fraud:**
- `fraud_alerts` - Detected fraud
- `fraud_decisions` - Admin reviews

**Permissions:**
- `permissions` - All permissions
- `roles` - User roles
- `model_has_permissions` - User permissions
- `model_has_roles` - User roles
- `role_has_permissions` - Role permissions

---

## 🐛 Troubleshooting

**Queue not processing:**
```bash
# Check queue connection
php artisan queue:work --once

# Verify Redis
redis-cli ping

# Check failed jobs
php artisan queue:failed
```

**Rate limiting not working:**
```bash
# Verify Redis connection
php artisan tinker
>>> Cache::get('test');

# Check middleware registration
php artisan route:list
```

**Permissions not working:**
```bash
# Clear permission cache
php artisan permission:cache-reset

# Verify user has role
php artisan tinker
>>> $user = User::find(1);
>>> $user->roles;
>>> $user->permissions;
```

**High AI costs:**
```sql
-- Check daily costs
SELECT service, SUM(total_cost_usd) as cost
FROM ai_costs
WHERE date >= DATE_SUB(NOW(), INTERVAL 7 DAY)
GROUP BY service;

-- Check expensive requests
SELECT * FROM ai_metrics
WHERE cost_usd > 1
ORDER BY created_at DESC
LIMIT 10;
```

---

*Quick Reference Guide - Envisage AI Platform v2.0*
