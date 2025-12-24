import React, { useState, useEffect } from 'react';
import axios from 'axios';
import {
  Box,
  Paper,
  Typography,
  LinearProgress,
  Chip,
  Grid,
  Card,
  CardContent,
  Alert,
  CircularProgress,
  Divider,
} from '@mui/material';
import {
  SentimentVerySatisfied,
  SentimentSatisfied,
  SentimentNeutral,
  SentimentDissatisfied,
  SentimentVeryDissatisfied,
  Warning,
  TrendingUp,
  Psychology,
  Shield,
} from '@mui/icons-material';

/**
 * Envisage AI - Sentiment Analysis Display
 * Advanced review intelligence & fake detection
 */
const SentimentDisplay = ({ productId, reviews }) => {
  const [sentiment, setSentiment] = useState(null);
  const [aspectSentiments, setAspectSentiments] = useState({});
  const [summary, setSummary] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    if (productId) {
      fetchSentimentAnalysis();
    }
  }, [productId]);

  const fetchSentimentAnalysis = async () => {
    setLoading(true);
    try {
      // Get review summary with sentiment
      const response = await axios.get(`/api/ai/sentiment/summarize/${productId}`);
      setSummary(response.data.data);
    } catch (err) {
      console.error('Envisage AI Sentiment Error:', err);
    } finally {
      setLoading(false);
    }
  };

  const getSentimentIcon = (label) => {
    switch (label) {
      case 'positive':
        return <SentimentVerySatisfied sx={{ color: '#4caf50' }} />;
      case 'negative':
        return <SentimentVeryDissatisfied sx={{ color: '#f44336' }} />;
      default:
        return <SentimentNeutral sx={{ color: '#ff9800' }} />;
    }
  };

  const getSentimentColor = (score) => {
    if (score >= 0.6) return '#4caf50';
    if (score <= -0.6) return '#f44336';
    return '#ff9800';
  };

  const getEmotionIcon = (emotion) => {
    const icons = {
      joy: '😊',
      anger: '😠',
      sadness: '😢',
      surprise: '😮',
      fear: '😨',
      trust: '🤝',
    };
    return icons[emotion] || '😐';
  };

  if (loading) {
    return (
      <Box sx={{ display: 'flex', justifyContent: 'center', py: 4 }}>
        <CircularProgress />
      </Box>
    );
  }

  return (
    <Box sx={{ py: 3 }}>
      {/* Header */}
      <Box sx={{ mb: 3 }}>
        <Typography variant="h5" gutterBottom sx={{ fontWeight: 700 }}>
          <Psychology sx={{ mr: 1, verticalAlign: 'middle', color: '#667eea' }} />
          Envisage AI Review Intelligence
        </Typography>
        <Typography variant="body2" color="text.secondary">
          Advanced sentiment analysis powered by BERT neural networks
        </Typography>
      </Box>

      <Grid container spacing={3}>
        {/* Overall Sentiment */}
        <Grid item xs={12} md={6}>
          <Card elevation={3}>
            <CardContent>
              <Typography variant="h6" gutterBottom>
                Overall Sentiment
              </Typography>
              <Box sx={{ textAlign: 'center', py: 3 }}>
                <Box sx={{ fontSize: 80 }}>
                  {summary?.overall_sentiment >= 0.6 ? '😊' :
                   summary?.overall_sentiment <= -0.6 ? '😞' : '😐'}
                </Box>
                <Typography variant="h4" sx={{ 
                  color: getSentimentColor(summary?.overall_sentiment),
                  fontWeight: 700,
                  mt: 2
                }}>
                  {summary?.overall_sentiment >= 0 ? '+' : ''}
                  {(summary?.overall_sentiment * 100).toFixed(0)}%
                </Typography>
                <Typography variant="body2" color="text.secondary">
                  {summary?.overall_sentiment >= 0.6 ? 'Very Positive' :
                   summary?.overall_sentiment >= 0.2 ? 'Positive' :
                   summary?.overall_sentiment >= -0.2 ? 'Neutral' :
                   summary?.overall_sentiment >= -0.6 ? 'Negative' : 'Very Negative'}
                </Typography>
              </Box>
            </CardContent>
          </Card>
        </Grid>

        {/* Sentiment Distribution */}
        <Grid item xs={12} md={6}>
          <Card elevation={3}>
            <CardContent>
              <Typography variant="h6" gutterBottom>
                Sentiment Distribution
              </Typography>
              {summary?.sentiment_distribution && (
                <Box sx={{ mt: 2 }}>
                  {/* Positive */}
                  <Box sx={{ mb: 2 }}>
                    <Box sx={{ display: 'flex', justifyContent: 'space-between', mb: 1 }}>
                      <Typography variant="body2">
                        <SentimentVerySatisfied sx={{ fontSize: 16, verticalAlign: 'middle', color: '#4caf50' }} />
                        {' '}Positive
                      </Typography>
                      <Typography variant="body2" fontWeight={600}>
                        {summary.sentiment_distribution.positive}%
                      </Typography>
                    </Box>
                    <LinearProgress 
                      variant="determinate" 
                      value={summary.sentiment_distribution.positive} 
                      sx={{ 
                        height: 8, 
                        borderRadius: 1,
                        bgcolor: '#e0e0e0',
                        '& .MuiLinearProgress-bar': { bgcolor: '#4caf50' }
                      }}
                    />
                  </Box>

                  {/* Neutral */}
                  <Box sx={{ mb: 2 }}>
                    <Box sx={{ display: 'flex', justifyContent: 'space-between', mb: 1 }}>
                      <Typography variant="body2">
                        <SentimentNeutral sx={{ fontSize: 16, verticalAlign: 'middle', color: '#ff9800' }} />
                        {' '}Neutral
                      </Typography>
                      <Typography variant="body2" fontWeight={600}>
                        {summary.sentiment_distribution.neutral}%
                      </Typography>
                    </Box>
                    <LinearProgress 
                      variant="determinate" 
                      value={summary.sentiment_distribution.neutral} 
                      sx={{ 
                        height: 8, 
                        borderRadius: 1,
                        bgcolor: '#e0e0e0',
                        '& .MuiLinearProgress-bar': { bgcolor: '#ff9800' }
                      }}
                    />
                  </Box>

                  {/* Negative */}
                  <Box sx={{ mb: 2 }}>
                    <Box sx={{ display: 'flex', justifyContent: 'space-between', mb: 1 }}>
                      <Typography variant="body2">
                        <SentimentVeryDissatisfied sx={{ fontSize: 16, verticalAlign: 'middle', color: '#f44336' }} />
                        {' '}Negative
                      </Typography>
                      <Typography variant="body2" fontWeight={600}>
                        {summary.sentiment_distribution.negative}%
                      </Typography>
                    </Box>
                    <LinearProgress 
                      variant="determinate" 
                      value={summary.sentiment_distribution.negative} 
                      sx={{ 
                        height: 8, 
                        borderRadius: 1,
                        bgcolor: '#e0e0e0',
                        '& .MuiLinearProgress-bar': { bgcolor: '#f44336' }
                      }}
                    />
                  </Box>
                </Box>
              )}
            </CardContent>
          </Card>
        </Grid>

        {/* AI Summary */}
        {summary?.summary && (
          <Grid item xs={12}>
            <Card elevation={3} sx={{ 
              background: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
              color: 'white'
            }}>
              <CardContent>
                <Typography variant="h6" gutterBottom>
                  <Psychology sx={{ mr: 1, verticalAlign: 'middle' }} />
                  AI-Generated Summary
                </Typography>
                <Typography variant="body1">
                  {summary.summary}
                </Typography>
              </CardContent>
            </Card>
          </Grid>
        )}

        {/* Key Themes */}
        {summary?.key_themes && summary.key_themes.length > 0 && (
          <Grid item xs={12}>
            <Card elevation={3}>
              <CardContent>
                <Typography variant="h6" gutterBottom>
                  <TrendingUp sx={{ mr: 1, verticalAlign: 'middle' }} />
                  Key Themes
                </Typography>
                <Box sx={{ display: 'flex', gap: 1, flexWrap: 'wrap', mt: 2 }}>
                  {summary.key_themes.map((theme, index) => (
                    <Chip
                      key={index}
                      label={theme}
                      sx={{
                        background: 'linear-gradient(45deg, #667eea 30%, #764ba2 90%)',
                        color: 'white',
                        fontWeight: 600
                      }}
                    />
                  ))}
                </Box>
              </CardContent>
            </Card>
          </Grid>
        )}

        {/* Fake Review Alert */}
        {reviews && reviews.some(r => r.is_fake) && (
          <Grid item xs={12}>
            <Alert 
              severity="warning"
              icon={<Shield />}
              sx={{ borderLeft: '4px solid #ff9800' }}
            >
              <Typography variant="subtitle2" fontWeight={600}>
                Envisage AI Fake Review Detection
              </Typography>
              <Typography variant="body2">
                Our AI has flagged {reviews.filter(r => r.is_fake).length} review(s) as potentially fake. 
                These reviews are excluded from our analysis to ensure accuracy.
              </Typography>
            </Alert>
          </Grid>
        )}
      </Grid>

      {/* Footer */}
      <Box sx={{ mt: 3, textAlign: 'center' }}>
        <Chip
          icon={<Psychology />}
          label="Powered by Envisage AI Sentiment Engine v2.0 (BERT + BART)"
          variant="outlined"
          sx={{ borderColor: '#667eea', color: '#667eea', fontWeight: 600 }}
        />
      </Box>
    </Box>
  );
};

export default SentimentDisplay;
