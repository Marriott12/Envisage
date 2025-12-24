import React, { useState } from 'react';
import axios from 'axios';
import {
  Box,
  Card,
  CardContent,
  Typography,
  TextField,
  Button,
  Select,
  MenuItem,
  FormControl,
  InputLabel,
  Grid,
  Chip,
  IconButton,
  CircularProgress,
  Alert,
  Paper,
  Divider,
} from '@mui/material';
import {
  Psychology,
  ContentCopy,
  CheckCircle,
  AutoAwesome,
  Description,
  Email,
  Campaign,
  Article,
} from '@mui/icons-material';

/**
 * Envisage AI - GPT-4 Powered Content Generator
 * Automated copywriting for products, emails, ads & more
 */
const ContentGenerator = () => {
  const [productName, setProductName] = useState('');
  const [features, setFeatures] = useState('');
  const [contentType, setContentType] = useState('description');
  const [length, setLength] = useState('medium');
  const [tone, setTone] = useState('professional');
  const [generatedContent, setGeneratedContent] = useState('');
  const [loading, setLoading] = useState(false);
  const [copied, setCopied] = useState(false);
  const [error, setError] = useState('');

  const contentTypes = [
    { value: 'description', label: 'Product Description', icon: <Description /> },
    { value: 'email', label: 'Marketing Email', icon: <Email /> },
    { value: 'ad', label: 'Ad Copy', icon: <Campaign /> },
    { value: 'blog', label: 'Blog Post', icon: <Article /> },
  ];

  const lengths = [
    { value: 'short', label: 'Short (50-100 words)' },
    { value: 'medium', label: 'Medium (150-250 words)' },
    { value: 'long', label: 'Long (300-500 words)' },
  ];

  const tones = [
    { value: 'professional', label: 'Professional' },
    { value: 'casual', label: 'Casual & Friendly' },
    { value: 'luxury', label: 'Luxury & Premium' },
    { value: 'playful', label: 'Playful & Fun' },
  ];

  const handleGenerate = async () => {
    if (!productName.trim()) {
      setError('Please enter a product name');
      return;
    }

    setLoading(true);
    setError('');
    setCopied(false);

    try {
      const response = await axios.post('/api/ai/generate/description', {
        product_name: productName,
        features: features.split(',').map(f => f.trim()).filter(f => f),
        content_type: contentType,
        length,
        tone,
      });

      setGeneratedContent(response.data.data.content);
    } catch (err) {
      setError(err.response?.data?.message || 'Failed to generate content');
      console.error('Envisage AI Generation Error:', err);
    } finally {
      setLoading(false);
    }
  };

  const handleCopy = () => {
    navigator.clipboard.writeText(generatedContent);
    setCopied(true);
    setTimeout(() => setCopied(false), 3000);
  };

  return (
    <Box sx={{ p: 3, maxWidth: 1200, mx: 'auto' }}>
      {/* Header */}
      <Box sx={{ mb: 4, textAlign: 'center' }}>
        <Typography variant="h4" gutterBottom sx={{ fontWeight: 700 }}>
          <AutoAwesome sx={{ mr: 1, verticalAlign: 'middle', color: '#667eea' }} />
          Envisage AI Content Generator
        </Typography>
        <Typography variant="body2" color="text.secondary">
          GPT-4 powered copywriting in seconds
        </Typography>
      </Box>

      <Grid container spacing={3}>
        {/* Input Form */}
        <Grid item xs={12} md={6}>
          <Card>
            <CardContent>
              <Typography variant="h6" gutterBottom>
                Input Details
              </Typography>
              
              <TextField
                fullWidth
                label="Product/Topic Name"
                value={productName}
                onChange={(e) => setProductName(e.target.value)}
                margin="normal"
                required
                placeholder="e.g., Wireless Headphones Pro"
              />

              <TextField
                fullWidth
                label="Key Features (comma separated)"
                value={features}
                onChange={(e) => setFeatures(e.target.value)}
                margin="normal"
                multiline
                rows={3}
                placeholder="e.g., Noise cancellation, 30hr battery, Premium sound"
              />

              <FormControl fullWidth margin="normal">
                <InputLabel>Content Type</InputLabel>
                <Select
                  value={contentType}
                  onChange={(e) => setContentType(e.target.value)}
                  label="Content Type"
                >
                  {contentTypes.map((type) => (
                    <MenuItem key={type.value} value={type.value}>
                      <Box sx={{ display: 'flex', alignItems: 'center' }}>
                        {type.icon}
                        <Typography sx={{ ml: 1 }}>{type.label}</Typography>
                      </Box>
                    </MenuItem>
                  ))}
                </Select>
              </FormControl>

              <Grid container spacing={2}>
                <Grid item xs={6}>
                  <FormControl fullWidth margin="normal">
                    <InputLabel>Length</InputLabel>
                    <Select
                      value={length}
                      onChange={(e) => setLength(e.target.value)}
                      label="Length"
                    >
                      {lengths.map((l) => (
                        <MenuItem key={l.value} value={l.value}>
                          {l.label}
                        </MenuItem>
                      ))}
                    </Select>
                  </FormControl>
                </Grid>
                <Grid item xs={6}>
                  <FormControl fullWidth margin="normal">
                    <InputLabel>Tone</InputLabel>
                    <Select
                      value={tone}
                      onChange={(e) => setTone(e.target.value)}
                      label="Tone"
                    >
                      {tones.map((t) => (
                        <MenuItem key={t.value} value={t.value}>
                          {t.label}
                        </MenuItem>
                      ))}
                    </Select>
                  </FormControl>
                </Grid>
              </Grid>

              <Button
                fullWidth
                variant="contained"
                onClick={handleGenerate}
                disabled={loading}
                sx={{
                  mt: 3,
                  py: 1.5,
                  background: 'linear-gradient(45deg, #667eea 30%, #764ba2 90%)',
                  '&:hover': {
                    background: 'linear-gradient(45deg, #5568d3 30%, #6a4290 90%)',
                  },
                }}
              >
                {loading ? (
                  <>
                    <CircularProgress size={20} sx={{ mr: 1, color: 'white' }} />
                    Generating with GPT-4...
                  </>
                ) : (
                  <>
                    <Psychology sx={{ mr: 1 }} />
                    Generate Content
                  </>
                )}
              </Button>

              {error && (
                <Alert severity="error" sx={{ mt: 2 }}>
                  {error}
                </Alert>
              )}
            </CardContent>
          </Card>
        </Grid>

        {/* Generated Output */}
        <Grid item xs={12} md={6}>
          <Card sx={{ height: '100%' }}>
            <CardContent>
              <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', mb: 2 }}>
                <Typography variant="h6">
                  Generated Content
                </Typography>
                {generatedContent && (
                  <IconButton 
                    onClick={handleCopy}
                    color={copied ? 'success' : 'default'}
                    size="small"
                  >
                    {copied ? <CheckCircle /> : <ContentCopy />}
                  </IconButton>
                )}
              </Box>

              {generatedContent ? (
                <>
                  <Paper
                    sx={{
                      p: 3,
                      minHeight: 300,
                      maxHeight: 500,
                      overflow: 'auto',
                      bgcolor: '#f9f9f9',
                      border: '1px solid #e0e0e0',
                      borderRadius: 2,
                    }}
                  >
                    <Typography variant="body1" sx={{ whiteSpace: 'pre-wrap', lineHeight: 1.8 }}>
                      {generatedContent}
                    </Typography>
                  </Paper>
                  
                  {copied && (
                    <Alert severity="success" sx={{ mt: 2 }}>
                      Content copied to clipboard!
                    </Alert>
                  )}

                  <Divider sx={{ my: 2 }} />

                  <Box sx={{ display: 'flex', gap: 1, flexWrap: 'wrap' }}>
                    <Chip 
                      label={`${generatedContent.split(' ').length} words`}
                      size="small"
                      variant="outlined"
                    />
                    <Chip 
                      label={contentTypes.find(t => t.value === contentType)?.label}
                      size="small"
                      variant="outlined"
                    />
                    <Chip 
                      label={tones.find(t => t.value === tone)?.label}
                      size="small"
                      variant="outlined"
                    />
                  </Box>
                </>
              ) : (
                <Box
                  sx={{
                    minHeight: 300,
                    display: 'flex',
                    flexDirection: 'column',
                    alignItems: 'center',
                    justifyContent: 'center',
                    color: 'text.secondary',
                  }}
                >
                  <Psychology sx={{ fontSize: 64, mb: 2, opacity: 0.3 }} />
                  <Typography variant="body2">
                    Your generated content will appear here
                  </Typography>
                </Box>
              )}
            </CardContent>
          </Card>
        </Grid>

        {/* Quick Templates */}
        <Grid item xs={12}>
          <Card>
            <CardContent>
              <Typography variant="h6" gutterBottom>
                Quick Templates
              </Typography>
              <Grid container spacing={2} sx={{ mt: 1 }}>
                {[
                  { name: 'Fashion Product', features: 'Premium fabric, Modern design, Comfortable fit' },
                  { name: 'Tech Gadget', features: 'Latest technology, Long battery, Fast charging' },
                  { name: 'Home Decor', features: 'Elegant design, Durable material, Easy maintenance' },
                  { name: 'Sports Equipment', features: 'Professional grade, Lightweight, Weather resistant' },
                ].map((template, idx) => (
                  <Grid item xs={12} sm={6} md={3} key={idx}>
                    <Paper
                      sx={{
                        p: 2,
                        cursor: 'pointer',
                        transition: 'all 0.3s',
                        '&:hover': {
                          transform: 'translateY(-4px)',
                          boxShadow: 4,
                        },
                      }}
                      onClick={() => {
                        setProductName(template.name);
                        setFeatures(template.features);
                      }}
                    >
                      <Typography variant="subtitle2" fontWeight={600}>
                        {template.name}
                      </Typography>
                      <Typography variant="caption" color="text.secondary">
                        {template.features}
                      </Typography>
                    </Paper>
                  </Grid>
                ))}
              </Grid>
            </CardContent>
          </Card>
        </Grid>
      </Grid>

      {/* Footer */}
      <Box sx={{ mt: 4, textAlign: 'center' }}>
        <Chip
          icon={<Psychology />}
          label="Powered by Envisage AI Content Engine v2.0 (GPT-4 Turbo)"
          variant="outlined"
          sx={{ borderColor: '#667eea', color: '#667eea', fontWeight: 600 }}
        />
      </Box>
    </Box>
  );
};

export default ContentGenerator;
