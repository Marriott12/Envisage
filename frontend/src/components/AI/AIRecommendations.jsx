import React, { useState, useEffect } from 'react';
import axios from 'axios';
import { Box, Grid, Typography, Card, CardMedia, CardContent, CardActions, Button, Chip, CircularProgress, Alert, Tab, Tabs } from '@mui/material';
import { AutoAwesome, TrendingUp, Psychology, Speed, Explore } from '@mui/icons-material';

/**
 * Envisage AI - Neural Recommendation Engine
 * Advanced ML-powered product recommendations
 */
const AIRecommendations = ({ userId, productId, limit = 8 }) => {
  const [products, setProducts] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [algorithm, setAlgorithm] = useState('neural');
  const [activeTab, setActiveTab] = useState(0);

  const algorithms = [
    { 
      id: 'neural', 
      name: 'Neural AI', 
      icon: <Psychology />, 
      description: 'Deep learning predictions',
      endpoint: '/api/recommendations/neural'
    },
    { 
      id: 'bandit', 
      name: 'Smart Explore', 
      icon: <Explore />, 
      description: 'AI-powered discovery',
      endpoint: '/api/recommendations/bandit'
    },
    { 
      id: 'session', 
      name: 'Session Based', 
      icon: <Speed />, 
      description: 'Real-time learning',
      endpoint: '/api/recommendations/session'
    },
    { 
      id: 'context', 
      name: 'Context Aware', 
      icon: <AutoAwesome />, 
      description: 'Personalized timing',
      endpoint: '/api/recommendations/context-aware'
    },
  ];

  useEffect(() => {
    fetchRecommendations();
  }, [algorithm, userId]);

  const fetchRecommendations = async () => {
    setLoading(true);
    setError(null);

    try {
      const selectedAlgorithm = algorithms[activeTab];
      let endpoint = selectedAlgorithm.endpoint;
      let method = 'GET';
      let data = null;

      if (algorithm === 'session') {
        method = 'POST';
        const viewedProducts = JSON.parse(sessionStorage.getItem('viewed_products') || '[]');
        data = {
          viewed_products: viewedProducts,
          limit: limit
        };
      } else {
        endpoint += `?limit=${limit}`;
      }

      const response = method === 'GET' 
        ? await axios.get(endpoint)
        : await axios.post(endpoint, data);

      setProducts(response.data.data || []);
    } catch (err) {
      setError('Unable to load recommendations. Please try again.');
      console.error('Envisage AI Error:', err);
    } finally {
      setLoading(false);
    }
  };

  const handleTabChange = (event, newValue) => {
    setActiveTab(newValue);
    setAlgorithm(algorithms[newValue].id);
  };

  const trackClick = async (productId, recommendationId) => {
    try {
      await axios.post('/api/recommendations/feedback', {
        product_id: productId,
        action: 'click',
        context: {
          algorithm: algorithm,
          recommendation_id: recommendationId
        }
      });
    } catch (err) {
      console.error('Tracking error:', err);
    }
  };

  return (
    <Box sx={{ py: 4, px: 2 }}>
      {/* Header */}
      <Box sx={{ mb: 3, textAlign: 'center' }}>
        <Typography variant="h4" gutterBottom sx={{ 
          fontWeight: 700, 
          background: 'linear-gradient(45deg, #667eea 30%, #764ba2 90%)',
          WebkitBackgroundClip: 'text',
          WebkitTextFillColor: 'transparent',
        }}>
          <AutoAwesome sx={{ mr: 1, verticalAlign: 'middle' }} />
          Envisage AI Recommendations
        </Typography>
        <Typography variant="subtitle1" color="text.secondary">
          Powered by Advanced Neural Networks
        </Typography>
      </Box>

      {/* Algorithm Selector */}
      <Box sx={{ mb: 3, borderBottom: 1, borderColor: 'divider' }}>
        <Tabs 
          value={activeTab} 
          onChange={handleTabChange}
          centered
          sx={{
            '& .MuiTab-root': {
              minHeight: 80,
              textTransform: 'none',
            }
          }}
        >
          {algorithms.map((algo, index) => (
            <Tab 
              key={algo.id}
              icon={algo.icon}
              label={
                <Box>
                  <Typography variant="subtitle2" fontWeight={600}>
                    {algo.name}
                  </Typography>
                  <Typography variant="caption" color="text.secondary">
                    {algo.description}
                  </Typography>
                </Box>
              }
            />
          ))}
        </Tabs>
      </Box>

      {/* Content */}
      {loading ? (
        <Box sx={{ display: 'flex', justifyContent: 'center', py: 8 }}>
          <CircularProgress size={60} />
        </Box>
      ) : error ? (
        <Alert severity="error" sx={{ mb: 3 }}>
          {error}
        </Alert>
      ) : (
        <Grid container spacing={3}>
          {products.map((product, index) => (
            <Grid item xs={12} sm={6} md={3} key={product.id}>
              <Card 
                sx={{ 
                  height: '100%',
                  display: 'flex',
                  flexDirection: 'column',
                  transition: 'transform 0.3s, box-shadow 0.3s',
                  '&:hover': {
                    transform: 'translateY(-8px)',
                    boxShadow: 8,
                  }
                }}
              >
                {/* AI Confidence Badge */}
                {product.score && (
                  <Chip
                    label={`${(product.score * 100).toFixed(0)}% Match`}
                    size="small"
                    color="primary"
                    sx={{
                      position: 'absolute',
                      top: 10,
                      right: 10,
                      zIndex: 1,
                      fontWeight: 600,
                      background: 'linear-gradient(45deg, #667eea 30%, #764ba2 90%)',
                    }}
                  />
                )}

                <CardMedia
                  component="img"
                  height="200"
                  image={product.image_url || product.image || '/placeholder.jpg'}
                  alt={product.name}
                  sx={{ objectFit: 'cover' }}
                />

                <CardContent sx={{ flexGrow: 1 }}>
                  <Typography variant="h6" gutterBottom noWrap>
                    {product.name}
                  </Typography>
                  
                  {product.reason && (
                    <Typography variant="caption" color="text.secondary" sx={{ 
                      display: 'block',
                      mb: 1,
                      fontStyle: 'italic'
                    }}>
                      <AutoAwesome sx={{ fontSize: 14, mr: 0.5, verticalAlign: 'middle' }} />
                      {product.reason}
                    </Typography>
                  )}

                  <Typography variant="h6" color="primary" fontWeight={700}>
                    ${product.price?.toFixed(2) || '0.00'}
                  </Typography>

                  {product.rating && (
                    <Box sx={{ display: 'flex', alignItems: 'center', mt: 1 }}>
                      <Typography variant="body2" color="text.secondary">
                        ⭐ {product.rating.toFixed(1)} ({product.reviews_count || 0} reviews)
                      </Typography>
                    </Box>
                  )}
                </CardContent>

                <CardActions>
                  <Button 
                    fullWidth 
                    variant="contained"
                    onClick={() => {
                      trackClick(product.id, `${algorithm}_${index}`);
                      window.location.href = `/products/${product.id}`;
                    }}
                    sx={{
                      background: 'linear-gradient(45deg, #667eea 30%, #764ba2 90%)',
                      '&:hover': {
                        background: 'linear-gradient(45deg, #764ba2 30%, #667eea 90%)',
                      }
                    }}
                  >
                    View Details
                  </Button>
                </CardActions>
              </Card>
            </Grid>
          ))}
        </Grid>
      )}

      {/* Powered By Badge */}
      <Box sx={{ mt: 4, textAlign: 'center' }}>
        <Chip 
          icon={<Psychology />}
          label="Powered by Envisage AI Neural Engine v2.0"
          variant="outlined"
          sx={{ 
            borderColor: '#667eea',
            color: '#667eea',
            fontWeight: 600
          }}
        />
      </Box>
    </Box>
  );
};

export default AIRecommendations;
