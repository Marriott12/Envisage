import React, { useState, useRef } from 'react';
import axios from 'axios';
import {
  Box,
  Button,
  Card,
  CardMedia,
  CardContent,
  Grid,
  Typography,
  IconButton,
  Dialog,
  DialogTitle,
  DialogContent,
  Chip,
  CircularProgress,
  Alert,
  Divider,
  Paper,
} from '@mui/material';
import {
  CameraAlt,
  Close,
  CloudUpload,
  ImageSearch,
  Palette,
  Search,
} from '@mui/icons-material';

/**
 * Envisage AI - Visual Search Engine
 * Computer vision powered product discovery
 */
const VisualSearchWidget = () => {
  const [open, setOpen] = useState(false);
  const [imagePreview, setImagePreview] = useState(null);
  const [results, setResults] = useState([]);
  const [colors, setColors] = useState([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);
  const fileInputRef = useRef(null);

  const handleImageUpload = async (file) => {
    if (!file) return;

    // Preview
    const reader = new FileReader();
    reader.onload = (e) => setImagePreview(e.target.result);
    reader.readAsDataURL(file);

    setLoading(true);
    setError(null);

    try {
      // Visual Search
      const formData = new FormData();
      formData.append('image', file);
      formData.append('limit', '12');

      const searchResponse = await axios.post('/api/ai/visual-search', formData, {
        headers: { 'Content-Type': 'multipart/form-data' }
      });

      setResults(searchResponse.data.data || []);

      // Color Detection
      const colorFormData = new FormData();
      colorFormData.append('image', file);
      colorFormData.append('num_colors', '5');

      const colorResponse = await axios.post('/api/ai/detect-colors', colorFormData, {
        headers: { 'Content-Type': 'multipart/form-data' }
      });

      setColors(colorResponse.data.colors || []);

    } catch (err) {
      setError('Unable to process image. Please try another image.');
      console.error('Envisage AI Visual Search Error:', err);
    } finally {
      setLoading(false);
    }
  };

  const handleFileChange = (e) => {
    const file = e.target.files[0];
    if (file) {
      handleImageUpload(file);
    }
  };

  const handleDrop = (e) => {
    e.preventDefault();
    const file = e.dataTransfer.files[0];
    if (file && file.type.startsWith('image/')) {
      handleImageUpload(file);
    }
  };

  const handleDragOver = (e) => {
    e.preventDefault();
  };

  const resetSearch = () => {
    setImagePreview(null);
    setResults([]);
    setColors([]);
    setError(null);
  };

  return (
    <>
      {/* Trigger Button */}
      <Button
        variant="contained"
        startIcon={<ImageSearch />}
        onClick={() => setOpen(true)}
        sx={{
          background: 'linear-gradient(45deg, #667eea 30%, #764ba2 90%)',
          '&:hover': {
            background: 'linear-gradient(45deg, #764ba2 30%, #667eea 90%)',
          }
        }}
      >
        Search by Image
      </Button>

      {/* Main Dialog */}
      <Dialog 
        open={open} 
        onClose={() => setOpen(false)}
        maxWidth="lg"
        fullWidth
      >
        <DialogTitle sx={{ 
          background: 'linear-gradient(45deg, #667eea 30%, #764ba2 90%)',
          color: 'white',
          display: 'flex',
          justifyContent: 'space-between',
          alignItems: 'center'
        }}>
          <Box sx={{ display: 'flex', alignItems: 'center' }}>
            <ImageSearch sx={{ mr: 1 }} />
            <Typography variant="h6">Envisage AI Visual Search</Typography>
          </Box>
          <IconButton onClick={() => setOpen(false)} sx={{ color: 'white' }}>
            <Close />
          </IconButton>
        </DialogTitle>

        <DialogContent sx={{ mt: 2 }}>
          {!imagePreview ? (
            /* Upload Area */
            <Paper
              sx={{
                p: 6,
                textAlign: 'center',
                border: '2px dashed #667eea',
                borderRadius: 2,
                cursor: 'pointer',
                transition: 'all 0.3s',
                '&:hover': {
                  borderColor: '#764ba2',
                  backgroundColor: 'rgba(102, 126, 234, 0.05)',
                }
              }}
              onDrop={handleDrop}
              onDragOver={handleDragOver}
              onClick={() => fileInputRef.current?.click()}
            >
              <CameraAlt sx={{ fontSize: 80, color: '#667eea', mb: 2 }} />
              <Typography variant="h6" gutterBottom>
                Upload an Image to Find Similar Products
              </Typography>
              <Typography variant="body2" color="text.secondary" gutterBottom>
                Drag and drop or click to browse
              </Typography>
              <Typography variant="caption" color="text.secondary">
                Powered by EfficientNet-B3 Deep Learning Model
              </Typography>
              <input
                ref={fileInputRef}
                type="file"
                accept="image/*"
                style={{ display: 'none' }}
                onChange={handleFileChange}
              />
              <Button
                variant="outlined"
                startIcon={<CloudUpload />}
                sx={{ mt: 3 }}
              >
                Choose Image
              </Button>
            </Paper>
          ) : (
            /* Results */
            <Grid container spacing={3}>
              {/* Left Panel - Uploaded Image & Colors */}
              <Grid item xs={12} md={4}>
                <Card>
                  <CardMedia
                    component="img"
                    image={imagePreview}
                    alt="Uploaded"
                    sx={{ maxHeight: 300, objectFit: 'contain', p: 2 }}
                  />
                  <CardContent>
                    <Typography variant="h6" gutterBottom>
                      <Palette sx={{ mr: 1, verticalAlign: 'middle' }} />
                      Detected Colors
                    </Typography>
                    <Box sx={{ display: 'flex', flexWrap: 'wrap', gap: 1 }}>
                      {colors.map((color, index) => (
                        <Chip
                          key={index}
                          label={`${color.name} ${color.percentage}%`}
                          sx={{
                            backgroundColor: color.hex,
                            color: color.hex === '#FFFFFF' ? '#000' : '#FFF',
                            fontWeight: 600,
                            border: '1px solid #ccc'
                          }}
                        />
                      ))}
                    </Box>
                    <Button
                      fullWidth
                      variant="outlined"
                      onClick={resetSearch}
                      sx={{ mt: 2 }}
                    >
                      Search Another Image
                    </Button>
                  </CardContent>
                </Card>
              </Grid>

              {/* Right Panel - Similar Products */}
              <Grid item xs={12} md={8}>
                {loading ? (
                  <Box sx={{ display: 'flex', justifyContent: 'center', py: 8 }}>
                    <CircularProgress size={60} />
                  </Box>
                ) : error ? (
                  <Alert severity="error">{error}</Alert>
                ) : results.length === 0 ? (
                  <Alert severity="info">
                    No similar products found. Try a different image.
                  </Alert>
                ) : (
                  <>
                    <Typography variant="h6" gutterBottom>
                      <Search sx={{ mr: 1, verticalAlign: 'middle' }} />
                      Found {results.length} Similar Products
                    </Typography>
                    <Divider sx={{ mb: 2 }} />
                    <Grid container spacing={2}>
                      {results.map((product) => (
                        <Grid item xs={6} md={4} key={product.product_id}>
                          <Card sx={{ 
                            height: '100%',
                            cursor: 'pointer',
                            transition: 'transform 0.2s',
                            '&:hover': {
                              transform: 'scale(1.05)',
                            }
                          }}
                          onClick={() => window.location.href = `/products/${product.product_id}`}
                          >
                            <Chip
                              label={`${(product.similarity_score * 100).toFixed(0)}% Match`}
                              size="small"
                              color="primary"
                              sx={{
                                position: 'absolute',
                                top: 8,
                                right: 8,
                                zIndex: 1,
                                fontWeight: 600
                              }}
                            />
                            <CardMedia
                              component="img"
                              height="150"
                              image={product.image_url || '/placeholder.jpg'}
                              alt={product.name}
                            />
                            <CardContent>
                              <Typography variant="subtitle2" noWrap>
                                {product.name}
                              </Typography>
                              <Typography variant="h6" color="primary">
                                ${product.price?.toFixed(2)}
                              </Typography>
                            </CardContent>
                          </Card>
                        </Grid>
                      ))}
                    </Grid>
                  </>
                )}
              </Grid>
            </Grid>
          )}

          {/* Footer */}
          <Box sx={{ mt: 3, textAlign: 'center' }}>
            <Chip
              icon={<ImageSearch />}
              label="Powered by Envisage AI Computer Vision v2.0"
              variant="outlined"
              sx={{ borderColor: '#667eea', color: '#667eea' }}
            />
          </Box>
        </DialogContent>
      </Dialog>
    </>
  );
};

export default VisualSearchWidget;
