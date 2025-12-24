import React from 'react';
import { BrowserRouter as Router, Routes, Route } from 'react-router-dom';
import EnvisageAISuite from './components/AI/EnvisageAISuite';
import AIRecommendations from './components/AI/AIRecommendations';
import VisualSearchWidget from './components/AI/VisualSearchWidget';
import AIChatbot from './components/AI/AIChatbot';
import SentimentDisplay from './components/AI/SentimentDisplay';
import FraudDashboard from './components/AI/FraudDashboard';
import PredictiveInsights from './components/AI/PredictiveInsights';
import ContentGenerator from './components/AI/ContentGenerator';

/**
 * Envisage AI Platform - Integration Examples
 * 
 * This file demonstrates various ways to integrate the Envisage AI components
 * into your application.
 */

// ============================================
// EXAMPLE 1: Full AI Suite (All Components)
// ============================================
export const FullAISuite = () => (
  <EnvisageAISuite 
    mode="demo" 
    productId={123} 
    userId={456} 
  />
);

// ============================================
// EXAMPLE 2: E-commerce Product Page
// ============================================
export const ProductPage = ({ productId, userId }) => (
  <div>
    {/* Your existing product details */}
    <div className="product-info">
      {/* Product images, price, description, etc. */}
    </div>

    {/* AI-Powered Review Sentiment Analysis */}
    <SentimentDisplay productId={productId} />

    {/* AI Recommendations */}
    <AIRecommendations userId={userId} />

    {/* Floating Widgets (Visual Search + Chatbot) */}
    <VisualSearchWidget />
    <AIChatbot userId={userId} />
  </div>
);

// ============================================
// EXAMPLE 3: Admin Dashboard
// ============================================
export const AdminDashboard = () => (
  <div>
    {/* Fraud Detection Monitoring */}
    <FraudDashboard />

    {/* Predictive Business Analytics */}
    <PredictiveInsights />

    {/* AI Content Generator */}
    <ContentGenerator />
  </div>
);

// ============================================
// EXAMPLE 4: Homepage with Recommendations
// ============================================
export const Homepage = ({ userId }) => (
  <div>
    {/* Hero section, featured products, etc. */}
    
    {/* Personalized AI Recommendations */}
    <section className="recommendations-section">
      <AIRecommendations userId={userId} />
    </section>

    {/* Floating AI Chat Assistant */}
    <AIChatbot userId={userId} />
  </div>
);

// ============================================
// EXAMPLE 5: Individual Component Imports
// ============================================

// Customer-facing pages
export const CustomerPages = () => (
  <Router>
    <Routes>
      {/* Home - Recommendations + Chat */}
      <Route path="/" element={
        <>
          <AIRecommendations userId={getCurrentUserId()} />
          <AIChatbot userId={getCurrentUserId()} />
        </>
      } />

      {/* Product Details - Sentiment + Recommendations + Search + Chat */}
      <Route path="/product/:id" element={
        <>
          <SentimentDisplay productId={getProductId()} />
          <AIRecommendations userId={getCurrentUserId()} />
          <VisualSearchWidget />
          <AIChatbot userId={getCurrentUserId()} />
        </>
      } />

      {/* Search - Visual Search Widget */}
      <Route path="/search" element={
        <VisualSearchWidget />
      } />
    </Routes>
  </Router>
);

// Admin pages
export const AdminPages = () => (
  <Router>
    <Routes>
      {/* Fraud Monitoring */}
      <Route path="/admin/fraud" element={<FraudDashboard />} />

      {/* Predictive Analytics */}
      <Route path="/admin/analytics" element={<PredictiveInsights />} />

      {/* Content Generation */}
      <Route path="/admin/content" element={<ContentGenerator />} />
    </Routes>
  </Router>
);

// ============================================
// EXAMPLE 6: Conditional Component Loading
// ============================================
export const SmartComponentLoader = ({ userRole, page, productId, userId }) => {
  // Customer mode
  if (userRole === 'customer') {
    if (page === 'product') {
      return (
        <>
          <SentimentDisplay productId={productId} />
          <AIRecommendations userId={userId} />
          <VisualSearchWidget />
          <AIChatbot userId={userId} />
        </>
      );
    }
    if (page === 'home') {
      return (
        <>
          <AIRecommendations userId={userId} />
          <AIChatbot userId={userId} />
        </>
      );
    }
  }

  // Admin mode
  if (userRole === 'admin') {
    return (
      <>
        <FraudDashboard />
        <PredictiveInsights productId={productId} />
        <ContentGenerator />
      </>
    );
  }

  // Demo/showcase mode
  if (userRole === 'demo') {
    return <EnvisageAISuite mode="demo" productId={productId} userId={userId} />;
  }

  return null;
};

// ============================================
// EXAMPLE 7: Individual Features
// ============================================

// Just recommendations
export const RecommendationsOnly = ({ userId }) => (
  <AIRecommendations userId={userId} />
);

// Just chatbot
export const ChatbotOnly = ({ userId }) => (
  <AIChatbot userId={userId} />
);

// Just sentiment analysis
export const SentimentOnly = ({ productId }) => (
  <SentimentDisplay productId={productId} />
);

// Just visual search
export const VisualSearchOnly = () => (
  <VisualSearchWidget />
);

// Just fraud dashboard
export const FraudOnly = () => (
  <FraudDashboard />
);

// Just predictive analytics
export const AnalyticsOnly = ({ productId }) => (
  <PredictiveInsights productId={productId} />
);

// Just content generator
export const ContentGenOnly = () => (
  <ContentGenerator />
);

// ============================================
// EXAMPLE 8: Custom Styling
// ============================================
export const CustomStyledAI = ({ userId, productId }) => (
  <div style={{ 
    background: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
    padding: '40px',
    borderRadius: '20px'
  }}>
    <AIRecommendations userId={userId} />
    <SentimentDisplay productId={productId} />
  </div>
);

// ============================================
// HELPER FUNCTIONS (Implement based on your app)
// ============================================
const getCurrentUserId = () => {
  // Get from your auth system
  return localStorage.getItem('userId') || 'guest';
};

const getProductId = () => {
  // Get from URL params or props
  return window.location.pathname.split('/').pop();
};

// ============================================
// MAIN APP INTEGRATION
// ============================================
export default function App() {
  const userRole = 'customer'; // or 'admin', 'demo'
  const userId = getCurrentUserId();

  return (
    <Router>
      <Routes>
        {/* Full AI Suite Demo */}
        <Route path="/ai-demo" element={
          <EnvisageAISuite mode="demo" userId={userId} />
        } />

        {/* Customer Pages */}
        <Route path="/" element={
          <Homepage userId={userId} />
        } />
        
        <Route path="/product/:id" element={
          <ProductPage userId={userId} />
        } />

        {/* Admin Pages */}
        <Route path="/admin/fraud" element={
          <FraudDashboard />
        } />

        <Route path="/admin/analytics" element={
          <PredictiveInsights />
        } />

        <Route path="/admin/content" element={
          <ContentGenerator />
        } />
      </Routes>
    </Router>
  );
}

// ============================================
// USAGE NOTES
// ============================================

/**
 * INSTALLATION:
 * 
 * 1. Copy all component files to frontend/src/components/AI/
 * 2. Install dependencies:
 *    npm install @mui/material @mui/icons-material axios recharts
 * 
 * 3. Import components as needed (see examples above)
 * 
 * 4. Ensure backend API endpoints are running
 * 
 * 5. Configure axios baseURL if needed:
 *    axios.defaults.baseURL = 'https://your-api.com';
 */

/**
 * COMPONENT PROPS:
 * 
 * EnvisageAISuite:
 *   - mode: 'customer' | 'admin' | 'demo'
 *   - productId: number (optional)
 *   - userId: number (optional)
 * 
 * AIRecommendations:
 *   - userId: number (optional)
 * 
 * VisualSearchWidget:
 *   - No props needed (floating widget)
 * 
 * AIChatbot:
 *   - userId: number (optional)
 * 
 * SentimentDisplay:
 *   - productId: number (required)
 * 
 * FraudDashboard:
 *   - No props needed
 * 
 * PredictiveInsights:
 *   - productId: number (optional, enables demand forecast)
 * 
 * ContentGenerator:
 *   - No props needed
 */

/**
 * API CONFIGURATION:
 * 
 * Ensure these endpoints are available:
 * - /api/recommendations/*
 * - /api/ai/visual-search
 * - /api/ai/chat
 * - /api/ai/sentiment/*
 * - /api/ai/fraud/*
 * - /api/ai/predict/*
 * - /api/ai/generate/*
 */

/**
 * THEMING:
 * 
 * All components use Envisage AI branding:
 * - Primary: #667eea
 * - Secondary: #764ba2
 * - Gradient: linear-gradient(45deg, #667eea 30%, #764ba2 90%)
 * 
 * To customize, wrap in ThemeProvider:
 * 
 * import { ThemeProvider, createTheme } from '@mui/material/styles';
 * 
 * const theme = createTheme({
 *   palette: {
 *     primary: { main: '#667eea' },
 *     secondary: { main: '#764ba2' },
 *   },
 * });
 * 
 * <ThemeProvider theme={theme}>
 *   <AIRecommendations />
 * </ThemeProvider>
 */
