import React from 'react';
import { Box, Container, Grid, Typography, Chip } from '@mui/material';
import { Psychology } from '@mui/icons-material';
import AIRecommendations from './AIRecommendations';
import VisualSearchWidget from './VisualSearchWidget';
import AIChatbot from './AIChatbot';
import SentimentDisplay from './SentimentDisplay';
import FraudDashboard from './FraudDashboard';
import PredictiveInsights from './PredictiveInsights';
import ContentGenerator from './ContentGenerator';

/**
 * Envisage AI Suite - Main Integration Component
 * Complete AI/ML platform powered by advanced neural networks
 * 
 * Features:
 * - Neural Recommendation Engine (Collaborative + Contextual + Multi-Armed Bandit)
 * - Computer Vision Search (EfficientNet-B3 + Color Analysis)
 * - Conversational AI (GPT-4 + NLP Intent Recognition)
 * - Sentiment Analysis (BERT + BART Summarization)
 * - Fraud Detection (XGBoost + Graph Neural Networks + Anomaly Detection)
 * - Predictive Analytics (Prophet + LSTM + XGBoost Forecasting)
 * - Content Generation (GPT-4 Turbo Copywriting)
 * - Dynamic Pricing (Reinforcement Learning Optimization)
 * 
 * @version 2.0
 * @author Envisage AI Team
 */
const EnvisageAISuite = ({ mode = 'customer', productId, userId }) => {
  /**
   * Render customer-facing AI components
   */
  const renderCustomerMode = () => (
    <Box>
      {/* AI Recommendations */}
      <Box sx={{ mb: 4 }}>
        <AIRecommendations userId={userId} />
      </Box>

      {/* Sentiment Display (Product Reviews) */}
      {productId && (
        <Box sx={{ mb: 4 }}>
          <SentimentDisplay productId={productId} />
        </Box>
      )}

      {/* Visual Search Widget (Floating) */}
      <VisualSearchWidget />

      {/* AI Chatbot (Floating) */}
      <AIChatbot userId={userId} />
    </Box>
  );

  /**
   * Render admin dashboard components
   */
  const renderAdminMode = () => (
    <Container maxWidth="xl">
      <Grid container spacing={3}>
        {/* Fraud Detection Dashboard */}
        <Grid item xs={12}>
          <FraudDashboard />
        </Grid>

        {/* Predictive Analytics */}
        <Grid item xs={12}>
          <PredictiveInsights productId={productId} />
        </Grid>

        {/* Content Generator */}
        <Grid item xs={12}>
          <ContentGenerator />
        </Grid>
      </Grid>
    </Container>
  );

  /**
   * Render analytics/demo mode (all components)
   */
  const renderDemoMode = () => (
    <Container maxWidth="xl">
      <Box sx={{ textAlign: 'center', mb: 4 }}>
        <Typography variant="h3" gutterBottom sx={{ fontWeight: 700 }}>
          <Psychology sx={{ fontSize: 48, mr: 2, verticalAlign: 'middle', color: '#667eea' }} />
          Envisage AI Platform v2.0
        </Typography>
        <Typography variant="h6" color="text.secondary" gutterBottom>
          Enterprise-Grade Artificial Intelligence & Machine Learning Suite
        </Typography>
        <Box sx={{ mt: 2, display: 'flex', justifyContent: 'center', gap: 1, flexWrap: 'wrap' }}>
          <Chip label="8 Advanced AI Systems" color="primary" />
          <Chip label="Neural Networks" color="primary" />
          <Chip label="Deep Learning" color="primary" />
          <Chip label="NLP" color="primary" />
          <Chip label="Computer Vision" color="primary" />
          <Chip label="Predictive Analytics" color="primary" />
        </Box>
      </Box>

      <Grid container spacing={4}>
        {/* Recommendations */}
        <Grid item xs={12}>
          <AIRecommendations userId={userId} />
        </Grid>

        {/* Sentiment Analysis */}
        {productId && (
          <Grid item xs={12} md={6}>
            <SentimentDisplay productId={productId} />
          </Grid>
        )}

        {/* Predictive Insights */}
        <Grid item xs={12}>
          <PredictiveInsights productId={productId} />
        </Grid>

        {/* Fraud Dashboard */}
        <Grid item xs={12}>
          <FraudDashboard />
        </Grid>

        {/* Content Generator */}
        <Grid item xs={12}>
          <ContentGenerator />
        </Grid>
      </Grid>

      {/* Floating Widgets */}
      <VisualSearchWidget />
      <AIChatbot userId={userId} />
    </Container>
  );

  return (
    <Box sx={{ minHeight: '100vh', bgcolor: '#f5f5f5', py: 4 }}>
      {mode === 'customer' && renderCustomerMode()}
      {mode === 'admin' && renderAdminMode()}
      {mode === 'demo' && renderDemoMode()}

      {/* Footer */}
      <Box sx={{ textAlign: 'center', mt: 6, pb: 4 }}>
        <Chip
          icon={<Psychology />}
          label="Powered by Envisage AI Platform v2.0 - 8 Advanced AI Systems"
          variant="outlined"
          sx={{
            borderColor: '#667eea',
            color: '#667eea',
            fontWeight: 600,
            fontSize: '0.9rem',
            py: 2.5,
          }}
        />
        <Typography variant="caption" display="block" sx={{ mt: 2, color: 'text.secondary' }}>
          Neural Recommendations • Computer Vision • NLP • Sentiment Analysis • Fraud Detection • Predictive Analytics • Content Generation • Dynamic Pricing
        </Typography>
      </Box>
    </Box>
  );
};

export default EnvisageAISuite;
