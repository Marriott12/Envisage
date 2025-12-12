# Dynamic Pricing Engine - API Documentation

## Overview
The Dynamic Pricing Engine provides AI-powered price optimization, competitor monitoring, demand forecasting, A/B testing, and surge pricing capabilities.

---

## Authentication
All endpoints require authentication except where marked as **Public**.

Admin-only endpoints require `role:admin` middleware.

---

## 📊 **PRICE RECOMMENDATIONS**

### Get Price Recommendation
Get AI-powered optimal price recommendation for a product.

**Endpoint:** `GET /api/pricing/recommend/{productId}`  
**Auth:** Required  
**Response:**
```json
{
  "product": {
    "id": 123,
    "name": "Premium Headphones",
    "current_price": 99.99
  },
  "recommendation": {
    "type": "optimized",
    "original_price": 99.99,
    "recommended_price": 104.99,
    "change_amount": 5.00,
    "change_percentage": 5.00,
    "reasons": [
      "Applied demand_based rule: High Demand Pricing",
      "High demand forecast (high): 150 units"
    ],
    "context": {
      "stock_level": 45,
      "sales_today": 12,
      "competitor_avg_price": 109.99
    }
  }
}
```

---

## 💰 **PRICE MANAGEMENT**

### Apply Price Change
Apply a price change to a product (Admin only).

**Endpoint:** `POST /api/pricing/apply`  
**Auth:** Admin  
**Body:**
```json
{
  "product_id": 123,
  "new_price": 104.99,
  "reason": "demand",
  "notes": "High demand detected"
}
```

**Response:**
```json
{
  "message": "Price updated successfully",
  "product_id": 123,
  "new_price": 104.99
}
```

### Bulk Optimize Prices
Run bulk price optimization across products (Admin only).

**Endpoint:** `POST /api/pricing/bulk-optimize`  
**Auth:** Admin  
**Body:**
```json
{
  "category_id": 5,
  "dry_run": true
}
```

**Response:**
```json
{
  "total_products": 150,
  "products_optimized": 23,
  "dry_run": true,
  "results": [
    {
      "product_id": 123,
      "product_name": "Premium Headphones",
      "old_price": 99.99,
      "new_price": 104.99,
      "change": 5.00,
      "change_percentage": 5.00,
      "reasons": ["High demand forecast"],
      "applied": false
    }
  ]
}
```

---

## 📜 **PRICING RULES**

### List Rules
Get all pricing rules with filtering.

**Endpoint:** `GET /api/pricing/rules`  
**Auth:** Admin  
**Query Params:**
- `product_id` (optional)
- `category_id` (optional)
- `type` (optional): demand_based, competitor_based, time_based, inventory_based
- `active_only` (optional): true/false
- `per_page` (optional): default 20

### Create Rule
Create a new pricing rule.

**Endpoint:** `POST /api/pricing/rules`  
**Auth:** Admin  
**Body:**
```json
{
  "name": "Weekend Premium Pricing",
  "product_id": null,
  "category_id": 5,
  "rule_type": "time_based",
  "min_price": 80.00,
  "max_price": 150.00,
  "priority": 10,
  "is_active": true,
  "adjustments": {
    "time_multipliers": {
      "days": {
        "6": 1.1,
        "7": 1.1
      }
    }
  }
}
```

### Update Rule
Update an existing pricing rule.

**Endpoint:** `PUT /api/pricing/rules/{id}`  
**Auth:** Admin

### Delete Rule
Delete a pricing rule.

**Endpoint:** `DELETE /api/pricing/rules/{id}`  
**Auth:** Admin

---

## 📈 **PRICE HISTORY**

### Get Price History
Get price change history for a product.

**Endpoint:** `GET /api/pricing/history/{productId}`  
**Auth:** Required  
**Query Params:**
- `days` (optional): default 30
- `reason` (optional): manual, rule_based, demand, competitor, surge
- `per_page` (optional): default 50

**Response:**
```json
{
  "product": {
    "id": 123,
    "name": "Premium Headphones",
    "current_price": 104.99
  },
  "history": {
    "data": [
      {
        "id": 456,
        "old_price": 99.99,
        "new_price": 104.99,
        "change_percentage": 5.00,
        "change_reason": "demand",
        "changed_at": "2024-12-12 10:30:00",
        "rule": { "name": "High Demand Pricing" },
        "user": { "name": "Admin User" }
      }
    ],
    "current_page": 1,
    "total": 45
  },
  "stats": {
    "total_changes": 45,
    "increases": 28,
    "decreases": 17,
    "avg_change_percentage": 3.25
  },
  "volatility_score": 42.5
}
```

---

## 🏆 **COMPETITOR PRICES**

### Get Competitor Prices
Get competitor price data for a product.

**Endpoint:** `GET /api/pricing/competitors/{productId}`  
**Auth:** Required  
**Query Params:**
- `in_stock_only` (optional): true/false
- `high_quality_only` (optional): true/false
- `hours` (optional): default 24

**Response:**
```json
{
  "product": {
    "id": 123,
    "name": "Premium Headphones",
    "current_price": 104.99
  },
  "competitors": [
    {
      "competitor_name": "Amazon",
      "competitor_price": 109.99,
      "our_price": 104.99,
      "price_difference": -5.00,
      "price_diff_percentage": -4.55,
      "product_match_quality": "high",
      "in_stock": true,
      "scraped_at": "2024-12-12 09:00:00"
    }
  ],
  "competitive_position": {
    "total_competitors": 5,
    "cheaper_than": 3,
    "more_expensive_than": 2,
    "our_price": 104.99,
    "avg_competitor_price": 108.50,
    "price_position": "competitive",
    "suggested_price": 106.33
  }
}
```

---

## 🔮 **DEMAND FORECASTING**

### Get Demand Forecast
Get AI-powered demand forecast for a product.

**Endpoint:** `GET /api/pricing/forecast/{productId}`  
**Auth:** Required  
**Query Params:**
- `days` (optional): default 7

**Response:**
```json
{
  "product": {
    "id": 123,
    "name": "Premium Headphones",
    "current_price": 104.99
  },
  "forecasts": [
    {
      "forecast_date": "2024-12-13",
      "predicted_demand": 45,
      "confidence_score": 0.82,
      "demand_level": "high",
      "recommended_price": 109.99,
      "factors": {
        "avg_daily_sales": 38.5,
        "trend": "8.5%",
        "seasonality_factor": 1.15,
        "historical_days": 30
      }
    }
  ],
  "accuracy_stats": {
    "total_forecasts": 30,
    "avg_accuracy": 87.5,
    "accuracy_rate": 83.33
  }
}
```

---

## 🧪 **PRICE EXPERIMENTS (A/B TESTING)**

### Start Experiment
Start a price A/B test.

**Endpoint:** `POST /api/pricing/experiments`  
**Auth:** Admin  
**Body:**
```json
{
  "product_id": 123,
  "name": "Premium Pricing Test",
  "variant_price": 109.99,
  "control_price": 99.99
}
```

### Get Experiment Results
Get results for a price experiment.

**Endpoint:** `GET /api/pricing/experiments/{id}`  
**Auth:** Admin  
**Response:**
```json
{
  "experiment_name": "Premium Pricing Test",
  "status": "active",
  "duration_days": 5,
  "control": {
    "price": 99.99,
    "impressions": 1250,
    "sales": 45,
    "revenue": 4499.55,
    "conversion_rate": "3.60%",
    "revenue_per_visitor": 3.60
  },
  "variant": {
    "price": 109.99,
    "impressions": 1180,
    "sales": 38,
    "revenue": 4179.62,
    "conversion_rate": "3.22%",
    "revenue_per_visitor": 3.54
  },
  "winner": "control",
  "confidence_level": "97.5%",
  "improvement": "-1.67%",
  "is_significant": true,
  "recommendation": "Control wins! Keep current price of $99.99."
}
```

### List Experiments
Get all price experiments.

**Endpoint:** `GET /api/pricing/experiments`  
**Auth:** Admin  
**Query Params:**
- `product_id` (optional)
- `status` (optional): active, paused, completed

### Complete Experiment
Complete a running experiment.

**Endpoint:** `POST /api/pricing/experiments/{id}/complete`  
**Auth:** Admin

---

## 🔥 **SURGE PRICING**

### Activate Surge Pricing
Manually activate surge pricing.

**Endpoint:** `POST /api/pricing/surge`  
**Auth:** Admin  
**Body:**
```json
{
  "product_id": 123,
  "event_type": "high_traffic",
  "surge_multiplier": 1.15,
  "duration_minutes": 120
}
```

**Event Types:**
- `flash_sale`
- `holiday`
- `stock_low`
- `high_traffic`

### Get Surge Pricing Status
Get active surge pricing for a product.

**Endpoint:** `GET /api/pricing/surge/{productId}`  
**Auth:** Public  
**Response:**
```json
{
  "product": {
    "id": 123,
    "name": "Premium Headphones"
  },
  "surge": {
    "has_surge": true,
    "event_type": "high_traffic",
    "base_price": 104.99,
    "surge_price": 120.74,
    "surge_multiplier": 1.15,
    "increase_percentage": 15.00,
    "time_remaining": 87,
    "message": "High demand - surge pricing active"
  }
}
```

### Deactivate Surge Pricing
Deactivate surge pricing and revert to optimal price.

**Endpoint:** `DELETE /api/pricing/surge/{productId}`  
**Auth:** Admin

### Check Surge Conditions
Check if surge pricing should be activated.

**Endpoint:** `GET /api/pricing/check-surge/{productId}`  
**Auth:** Required  
**Response:**
```json
{
  "should_activate": true,
  "surge": {
    "product_id": 123,
    "event_type": "high_traffic",
    "surge_multiplier": 1.15,
    "demand_spike": 45,
    "stock_level": 30
  }
}
```

---

## 📊 **ANALYTICS**

### Get Pricing Analytics
Get comprehensive pricing analytics dashboard (Admin only).

**Endpoint:** `GET /api/pricing/analytics`  
**Auth:** Admin  
**Query Params:**
- `days` (optional): default 30

**Response:**
```json
{
  "summary": {
    "total_price_changes": 1250,
    "price_increases": 720,
    "price_decreases": 530,
    "avg_change_percentage": 3.45,
    "active_rules": 15,
    "active_experiments": 3,
    "active_surges": 8
  },
  "changes_by_reason": [
    { "change_reason": "demand", "count": 450 },
    { "change_reason": "rule_based", "count": 380 },
    { "change_reason": "surge", "count": 280 },
    { "change_reason": "competitor", "count": 140 }
  ],
  "top_volatile_products": [
    {
      "product_id": 123,
      "change_count": 45,
      "volatility": 12.5
    }
  ]
}
```

---

## 🔄 **AUTOMATED TASKS**

The following tasks run automatically:

### Demand Forecasts
**Schedule:** Every 6 hours  
**Job:** `CalculateDemandForecasts`  
Generates 7-day ahead demand predictions for all active products using historical sales data, seasonality, and trends.

### Apply Pricing Rules
**Schedule:** Daily at 2 AM  
**Job:** `ApplyPricingRules`  
Automatically applies active pricing rules based on conditions (demand, competitor prices, inventory, time).

### Monitor Surge Conditions
**Schedule:** Every hour  
**Job:** `MonitorSurgePricingConditions`  
Detects surge pricing conditions (low stock, high demand, high traffic) and auto-activates surge pricing.

### Analyze Experiments
**Schedule:** Daily at 6 AM  
**Job:** `AnalyzePriceExperiments`  
Calculates statistical significance, determines winners, and auto-completes experiments with 95%+ confidence.

### Deactivate Expired Surges
**Schedule:** Every 10 minutes  
**Job:** `DeactivateExpiredSurges`  
Deactivates expired surge pricing events and reverts products to optimal prices.

---

## 🎯 **PRICING ALGORITHMS**

### 1. Demand-Based Pricing
Adjusts prices based on predicted demand levels:
- **Low demand** (< 10 units): -5% price decrease
- **Normal demand** (10-50 units): No change
- **High demand** (50-100 units): +8% price increase
- **Surge demand** (100+ units): +15% price increase

### 2. Competitor-Based Pricing
Matches or undercuts competitor prices:
- **Undercut strategy**: Competitor price - offset
- **Match strategy**: Equal to competitor price
- **Premium strategy**: Competitor price + offset

### 3. Inventory-Based Pricing
Adjusts based on stock levels:
- **Critical stock** (<5): +15% increase
- **Low stock** (5-20): +10% increase
- **Normal stock** (20-100): No change
- **High stock** (>100): -5% clearance discount

### 4. Time-Based Pricing
Dynamic pricing by hour/day:
- Weekend premium (+10%)
- Peak hours (+5%)
- Off-hours (-5%)

### 5. AI Demand Forecasting
Uses machine learning to predict future demand:
- Linear regression for trend analysis
- Seasonality factors (day of week)
- Confidence scoring (0-1)
- Price elasticity modeling

---

## 💡 **BEST PRACTICES**

1. **Start with Rules**: Create pricing rules before bulk optimization
2. **Monitor Experiments**: Run A/B tests for 7+ days with 100+ impressions
3. **Set Min/Max Prices**: Always define price boundaries in rules
4. **Use Confidence Scores**: Only trust forecasts with >70% confidence
5. **Test Surge Pricing**: Start with 10-15% multipliers, monitor conversion
6. **Review Analytics**: Check pricing analytics weekly for optimization opportunities

---

## 🚨 **ERROR CODES**

- `404`: Product/Resource not found
- `400`: Invalid request (check validation)
- `401`: Unauthenticated
- `403`: Unauthorized (admin only)
- `500`: Server error (check logs)

---

**API Version:** 1.0  
**Last Updated:** December 12, 2024
