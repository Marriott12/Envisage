import React, { useState, useEffect } from 'react';
import axios from 'axios';
import {
  Box,
  Grid,
  Card,
  CardContent,
  Typography,
  LinearProgress,
  Chip,
  Alert,
  Paper,
  Tab,
  Tabs,
} from '@mui/material';
import {
  TrendingUp,
  ShowChart,
  Psychology,
  Warning,
  CheckCircle,
  AutoGraph,
  Insights,
} from '@mui/icons-material';
import { LineChart, Line, BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip, Legend, ResponsiveContainer } from 'recharts';

/**
 * Envisage AI - Predictive Analytics Dashboard
 * Advanced forecasting & business intelligence
 */
const PredictiveInsights = ({ productId }) => {
  const [insights, setInsights] = useState(null);
  const [forecast, setForecast] = useState(null);
  const [trending, setTrending] = useState([]);
  const [loading, setLoading] = useState(true);
  const [activeTab, setActiveTab] = useState(0);

  useEffect(() => {
    fetchInsights();
  }, [productId]);

  const fetchInsights = async () => {
    setLoading(true);
    try {
      const [insightsRes, trendingRes] = await Promise.all([
        axios.get('/api/ai/predict/insights?timeframe=30'),
        axios.get('/api/ai/predict/trending?limit=10')
      ]);

      setInsights(insightsRes.data.data);
      setTrending(trendingRes.data.data || []);

      // Fetch forecast if product ID provided
      if (productId) {
        const forecastRes = await axios.get(`/api/ai/predict/demand/${productId}?days=30`);
        setForecast(forecastRes.data.data);
      }

      setLoading(false);
    } catch (err) {
      console.error('Envisage AI Predictive Error:', err);
      setLoading(false);
    }
  };

  if (loading) {
    return <LinearProgress />;
  }

  return (
    <Box sx={{ p: 3 }}>
      {/* Header */}
      <Box sx={{ mb: 4 }}>
        <Typography variant="h4" gutterBottom sx={{ fontWeight: 700 }}>
          <AutoGraph sx={{ mr: 1, verticalAlign: 'middle', color: '#667eea' }} />
          Envisage AI Predictive Analytics
        </Typography>
        <Typography variant="body2" color="text.secondary">
          Advanced forecasting powered by Prophet, LSTM & XGBoost
        </Typography>
      </Box>

      {/* Tabs */}
      <Tabs value={activeTab} onChange={(e, v) => setActiveTab(v)} sx={{ mb: 3 }}>
        <Tab icon={<Insights />} label="Business Insights" />
        <Tab icon={<TrendingUp />} label="Trending Products" />
        {forecast && <Tab icon={<ShowChart />} label="Demand Forecast" />}
      </Tabs>

      {/* Business Insights */}
      {activeTab === 0 && insights && (
        <Grid container spacing={3}>
          {/* Sales Trend */}
          {insights.sales_change && (
            <Grid item xs={12}>
              <Alert 
                severity={insights.sales_change > 0 ? 'success' : 'warning'}
                icon={insights.sales_change > 0 ? <TrendingUp /> : <Warning />}
              >
                <Typography variant="subtitle2" fontWeight={600}>
                  Sales Trend Alert
                </Typography>
                <Typography variant="body2">
                  Sales have {insights.sales_change > 0 ? 'increased' : 'decreased'} by{' '}
                  {Math.abs(insights.sales_change).toFixed(1)}% compared to last period.
                </Typography>
              </Alert>
            </Grid>
          )}

          {/* Low Stock Alerts */}
          {insights.low_stock_products && insights.low_stock_products.length > 0 && (
            <Grid item xs={12} md={6}>
              <Card>
                <CardContent>
                  <Typography variant="h6" gutterBottom>
                    <Warning sx={{ mr: 1, verticalAlign: 'middle', color: '#ff9800' }} />
                    Low Stock Alerts ({insights.low_stock_products.length})
                  </Typography>
                  <Box sx={{ mt: 2 }}>
                    {insights.low_stock_products.slice(0, 5).map((product, idx) => (
                      <Box key={idx} sx={{ mb: 2 }}>
                        <Box sx={{ display: 'flex', justifyContent: 'space-between', mb: 1 }}>
                          <Typography variant="body2">{product.name}</Typography>
                          <Typography variant="body2" fontWeight={600}>
                            {product.stock} units
                          </Typography>
                        </Box>
                        <LinearProgress 
                          variant="determinate" 
                          value={(product.stock / product.reorder_point) * 100} 
                          color="warning"
                          sx={{ height: 6, borderRadius: 1 }}
                        />
                      </Box>
                    ))}
                  </Box>
                </CardContent>
              </Card>
            </Grid>
          )}

          {/* Churn Risk */}
          {insights.churn_risk_customers && insights.churn_risk_customers.length > 0 && (
            <Grid item xs={12} md={6}>
              <Card>
                <CardContent>
                  <Typography variant="h6" gutterBottom>
                    <Psychology sx={{ mr: 1, verticalAlign: 'middle', color: '#f44336' }} />
                    Churn Risk Customers ({insights.churn_risk_customers.length})
                  </Typography>
                  <Box sx={{ mt: 2 }}>
                    {insights.churn_risk_customers.slice(0, 5).map((customer, idx) => (
                      <Box key={idx} sx={{ 
                        p: 1.5, 
                        mb: 1, 
                        bgcolor: '#ffebee', 
                        borderRadius: 1,
                        border: '1px solid #f44336'
                      }}>
                        <Typography variant="body2" fontWeight={600}>
                          {customer.name}
                        </Typography>
                        <Typography variant="caption" color="text.secondary">
                          Last order: {customer.days_inactive} days ago
                        </Typography>
                        <Box sx={{ mt: 1 }}>
                          <Chip 
                            label={`${(customer.churn_probability * 100).toFixed(0)}% Risk`}
                            size="small"
                            color="error"
                          />
                        </Box>
                      </Box>
                    ))}
                  </Box>
                </CardContent>
              </Card>
            </Grid>
          )}

          {/* Underperforming Products */}
          {insights.underperforming_products && insights.underperforming_products.length > 0 && (
            <Grid item xs={12}>
              <Card>
                <CardContent>
                  <Typography variant="h6" gutterBottom>
                    <Warning sx={{ mr: 1, verticalAlign: 'middle' }} />
                    Underperforming Products
                  </Typography>
                  <Grid container spacing={2} sx={{ mt: 1 }}>
                    {insights.underperforming_products.slice(0, 4).map((product, idx) => (
                      <Grid item xs={12} sm={6} md={3} key={idx}>
                        <Paper sx={{ p: 2, textAlign: 'center' }}>
                          <Typography variant="subtitle2" noWrap>
                            {product.name}
                          </Typography>
                          <Typography variant="h5" color="error" fontWeight={700}>
                            {product.sales_count}
                          </Typography>
                          <Typography variant="caption" color="text.secondary">
                            sales/month
                          </Typography>
                        </Paper>
                      </Grid>
                    ))}
                  </Grid>
                </CardContent>
              </Card>
            </Grid>
          )}
        </Grid>
      )}

      {/* Trending Products */}
      {activeTab === 1 && (
        <Grid container spacing={3}>
          {trending.map((product, idx) => (
            <Grid item xs={12} sm={6} md={4} lg={3} key={idx}>
              <Card sx={{ 
                height: '100%',
                transition: 'transform 0.3s',
                '&:hover': { transform: 'translateY(-8px)' }
              }}>
                <CardContent>
                  <Box sx={{ display: 'flex', justifyContent: 'space-between', mb: 2 }}>
                    <Chip 
                      label={`#${idx + 1}`}
                      size="small"
                      sx={{ 
                        background: 'linear-gradient(45deg, #667eea 30%, #764ba2 90%)',
                        color: 'white',
                        fontWeight: 700
                      }}
                    />
                    <Chip
                      icon={<TrendingUp />}
                      label={`+${product.growth_rate}%`}
                      size="small"
                      color="success"
                    />
                  </Box>
                  <Typography variant="h6" gutterBottom noWrap>
                    {product.name}
                  </Typography>
                  <Typography variant="body2" color="text.secondary" gutterBottom>
                    Momentum Score
                  </Typography>
                  <LinearProgress 
                    variant="determinate" 
                    value={product.momentum_score * 100} 
                    sx={{ 
                      height: 8, 
                      borderRadius: 1,
                      bgcolor: '#e0e0e0',
                      '& .MuiLinearProgress-bar': { 
                        background: 'linear-gradient(45deg, #667eea 30%, #764ba2 90%)' 
                      }
                    }}
                  />
                  <Typography variant="body2" sx={{ mt: 1 }}>
                    Velocity: <strong>{product.velocity}</strong>
                  </Typography>
                </CardContent>
              </Card>
            </Grid>
          ))}
        </Grid>
      )}

      {/* Demand Forecast */}
      {activeTab === 2 && forecast && (
        <Grid container spacing={3}>
          <Grid item xs={12}>
            <Card>
              <CardContent>
                <Typography variant="h6" gutterBottom>
                  30-Day Demand Forecast
                </Typography>
                <ResponsiveContainer width="100%" height={400}>
                  <LineChart data={forecast.forecast}>
                    <CartesianGrid strokeDasharray="3 3" />
                    <XAxis dataKey="date" />
                    <YAxis />
                    <Tooltip />
                    <Legend />
                    <Line 
                      type="monotone" 
                      dataKey="predicted_demand" 
                      stroke="#667eea" 
                      strokeWidth={3}
                      name="Predicted Demand"
                    />
                  </LineChart>
                </ResponsiveContainer>
              </CardContent>
            </Card>
          </Grid>

          <Grid item xs={12} md={4}>
            <Card>
              <CardContent>
                <Typography variant="subtitle2" color="text.secondary">
                  Total Predicted Demand
                </Typography>
                <Typography variant="h3" fontWeight={700} color="primary">
                  {forecast.total_predicted}
                </Typography>
              </CardContent>
            </Card>
          </Grid>

          <Grid item xs={12} md={4}>
            <Card>
              <CardContent>
                <Typography variant="subtitle2" color="text.secondary">
                  Trend
                </Typography>
                <Typography variant="h3" fontWeight={700} color={forecast.trend === 'increasing' ? 'success.main' : 'error.main'}>
                  {forecast.trend === 'increasing' ? '↑' : '↓'} {forecast.trend}
                </Typography>
              </CardContent>
            </Card>
          </Grid>

          <Grid item xs={12} md={4}>
            <Card>
              <CardContent>
                <Typography variant="subtitle2" color="text.secondary">
                  Seasonality
                </Typography>
                <Typography variant="h3" fontWeight={700}>
                  {forecast.seasonality}
                </Typography>
              </CardContent>
            </Card>
          </Grid>
        </Grid>
      )}

      {/* Footer */}
      <Box sx={{ mt: 4, textAlign: 'center' }}>
        <Chip
          icon={<Psychology />}
          label="Powered by Envisage AI Predictive Engine v2.0 (85% MAPE Accuracy)"
          variant="outlined"
          sx={{ borderColor: '#667eea', color: '#667eea', fontWeight: 600 }}
        />
      </Box>
    </Box>
  );
};

export default PredictiveInsights;
