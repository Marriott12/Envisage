import React, { useState, useEffect, useRef } from 'react';
import axios from 'axios';
import {
  Box,
  Paper,
  TextField,
  IconButton,
  Typography,
  Avatar,
  Chip,
  Fab,
  Slide,
  Divider,
  CircularProgress,
  Card,
  CardMedia,
  CardContent,
  Grid,
} from '@mui/material';
import {
  Send,
  SmartToy,
  Close,
  ChatBubble,
  Psychology,
  ShoppingCart,
  LocalShipping,
  Help,
} from '@mui/icons-material';

/**
 * Envisage AI - Intelligent Shopping Assistant
 * NLP-powered conversational commerce
 */
const AIChatbot = () => {
  const [open, setOpen] = useState(false);
  const [messages, setMessages] = useState([
    {
      role: 'assistant',
      content: "👋 Hi! I'm Envisage AI, your intelligent shopping assistant. How can I help you find the perfect product today?",
      timestamp: new Date(),
    }
  ]);
  const [input, setInput] = useState('');
  const [loading, setLoading] = useState(false);
  const [conversationId, setConversationId] = useState(null);
  const [intent, setIntent] = useState(null);
  const messagesEndRef = useRef(null);

  const scrollToBottom = () => {
    messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
  };

  useEffect(() => {
    scrollToBottom();
  }, [messages]);

  const quickActions = [
    { icon: <ShoppingCart />, label: 'Product Search', text: 'Help me find a product' },
    { icon: <LocalShipping />, label: 'Track Order', text: 'Where is my order?' },
    { icon: <Help />, label: 'Get Help', text: 'I need assistance' },
  ];

  const sendMessage = async (messageText = input) => {
    if (!messageText.trim()) return;

    const userMessage = {
      role: 'user',
      content: messageText,
      timestamp: new Date(),
    };

    setMessages(prev => [...prev, userMessage]);
    setInput('');
    setLoading(true);

    try {
      // Extract intent first
      const intentResponse = await axios.post('/api/ai/extract-intent', {
        query: messageText
      });
      
      setIntent(intentResponse.data.data.intent);

      // Chat with AI
      const chatResponse = await axios.post('/api/ai/chat', {
        message: messageText,
        conversation_id: conversationId
      });

      const aiResponse = chatResponse.data.data;

      // Add AI response
      const assistantMessage = {
        role: 'assistant',
        content: aiResponse.message,
        timestamp: new Date(),
        products: aiResponse.products || [],
        intent: aiResponse.intent,
        entities: aiResponse.entities,
      };

      setMessages(prev => [...prev, assistantMessage]);
      setConversationId(aiResponse.conversation_id);

    } catch (err) {
      const errorMessage = {
        role: 'assistant',
        content: "I apologize, but I'm having trouble processing your request. Please try again or contact our support team.",
        timestamp: new Date(),
        error: true,
      };
      setMessages(prev => [...prev, errorMessage]);
      console.error('Envisage AI Chat Error:', err);
    } finally {
      setLoading(false);
    }
  };

  const handleKeyPress = (e) => {
    if (e.key === 'Enter' && !e.shiftKey) {
      e.preventDefault();
      sendMessage();
    }
  };

  return (
    <>
      {/* Floating Action Button */}
      <Fab
        color="primary"
        onClick={() => setOpen(true)}
        sx={{
          position: 'fixed',
          bottom: 24,
          right: 24,
          zIndex: 1000,
          background: 'linear-gradient(45deg, #667eea 30%, #764ba2 90%)',
          '&:hover': {
            background: 'linear-gradient(45deg, #764ba2 30%, #667eea 90%)',
          }
        }}
      >
        <ChatBubble />
      </Fab>

      {/* Chat Window */}
      <Slide direction="up" in={open} mountOnEnter unmountOnExit>
        <Paper
          elevation={8}
          sx={{
            position: 'fixed',
            bottom: 24,
            right: 24,
            width: { xs: '100%', sm: 400 },
            height: { xs: '100%', sm: 600 },
            zIndex: 1000,
            display: 'flex',
            flexDirection: 'column',
            borderRadius: { xs: 0, sm: 2 },
          }}
        >
          {/* Header */}
          <Box
            sx={{
              background: 'linear-gradient(45deg, #667eea 30%, #764ba2 90%)',
              color: 'white',
              p: 2,
              display: 'flex',
              alignItems: 'center',
              justifyContent: 'space-between',
            }}
          >
            <Box sx={{ display: 'flex', alignItems: 'center' }}>
              <Avatar sx={{ bgcolor: 'white', color: '#667eea', mr: 2 }}>
                <SmartToy />
              </Avatar>
              <Box>
                <Typography variant="h6" fontWeight={700}>
                  Envisage AI
                </Typography>
                <Typography variant="caption">
                  <Psychology sx={{ fontSize: 12, mr: 0.5, verticalAlign: 'middle' }} />
                  Neural Assistant Online
                </Typography>
              </Box>
            </Box>
            <IconButton onClick={() => setOpen(false)} sx={{ color: 'white' }}>
              <Close />
            </IconButton>
          </Box>

          {/* Quick Actions */}
          {messages.length <= 1 && (
            <Box sx={{ p: 2, borderBottom: '1px solid #eee' }}>
              <Typography variant="caption" color="text.secondary" gutterBottom>
                Quick Actions:
              </Typography>
              <Box sx={{ display: 'flex', gap: 1, mt: 1, flexWrap: 'wrap' }}>
                {quickActions.map((action, index) => (
                  <Chip
                    key={index}
                    icon={action.icon}
                    label={action.label}
                    onClick={() => sendMessage(action.text)}
                    clickable
                    size="small"
                    sx={{ 
                      '&:hover': { 
                        background: 'linear-gradient(45deg, #667eea 30%, #764ba2 90%)',
                        color: 'white'
                      }
                    }}
                  />
                ))}
              </Box>
            </Box>
          )}

          {/* Messages */}
          <Box
            sx={{
              flexGrow: 1,
              overflow: 'auto',
              p: 2,
              bgcolor: '#f5f5f5',
            }}
          >
            {messages.map((message, index) => (
              <Box key={index} sx={{ mb: 2 }}>
                <Box
                  sx={{
                    display: 'flex',
                    justifyContent: message.role === 'user' ? 'flex-end' : 'flex-start',
                    mb: 1,
                  }}
                >
                  {message.role === 'assistant' && (
                    <Avatar sx={{ bgcolor: '#667eea', mr: 1, width: 32, height: 32 }}>
                      <SmartToy fontSize="small" />
                    </Avatar>
                  )}
                  <Paper
                    sx={{
                      p: 1.5,
                      maxWidth: '75%',
                      bgcolor: message.role === 'user' ? '#667eea' : 'white',
                      color: message.role === 'user' ? 'white' : 'text.primary',
                      borderRadius: 2,
                    }}
                  >
                    <Typography variant="body2">{message.content}</Typography>
                    {message.entities && Object.keys(message.entities).length > 0 && (
                      <Box sx={{ mt: 1, display: 'flex', gap: 0.5, flexWrap: 'wrap' }}>
                        {Object.entries(message.entities).map(([key, value]) => (
                          <Chip
                            key={key}
                            label={`${key}: ${value}`}
                            size="small"
                            sx={{ fontSize: 10 }}
                          />
                        ))}
                      </Box>
                    )}
                  </Paper>
                  {message.role === 'user' && (
                    <Avatar sx={{ bgcolor: '#764ba2', ml: 1, width: 32, height: 32 }}>
                      U
                    </Avatar>
                  )}
                </Box>

                {/* Product Recommendations */}
                {message.products && message.products.length > 0 && (
                  <Box sx={{ ml: 5, mt: 1 }}>
                    <Typography variant="caption" color="text.secondary" gutterBottom>
                      Recommended Products:
                    </Typography>
                    <Grid container spacing={1} sx={{ mt: 0.5 }}>
                      {message.products.slice(0, 3).map((product) => (
                        <Grid item xs={12} key={product.id}>
                          <Card 
                            sx={{ 
                              display: 'flex',
                              cursor: 'pointer',
                              '&:hover': { boxShadow: 3 }
                            }}
                            onClick={() => window.location.href = `/products/${product.id}`}
                          >
                            <CardMedia
                              component="img"
                              sx={{ width: 60, height: 60, objectFit: 'cover' }}
                              image={product.image_url || '/placeholder.jpg'}
                              alt={product.name}
                            />
                            <CardContent sx={{ p: 1, '&:last-child': { pb: 1 } }}>
                              <Typography variant="caption" noWrap>
                                {product.name}
                              </Typography>
                              <Typography variant="body2" color="primary" fontWeight={600}>
                                ${product.price?.toFixed(2)}
                              </Typography>
                            </CardContent>
                          </Card>
                        </Grid>
                      ))}
                    </Grid>
                  </Box>
                )}

                <Typography
                  variant="caption"
                  color="text.secondary"
                  sx={{ display: 'block', textAlign: message.role === 'user' ? 'right' : 'left', mt: 0.5 }}
                >
                  {message.timestamp.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}
                </Typography>
              </Box>
            ))}

            {loading && (
              <Box sx={{ display: 'flex', justifyContent: 'flex-start', mb: 2 }}>
                <Avatar sx={{ bgcolor: '#667eea', mr: 1, width: 32, height: 32 }}>
                  <SmartToy fontSize="small" />
                </Avatar>
                <Paper sx={{ p: 1.5, bgcolor: 'white', borderRadius: 2 }}>
                  <CircularProgress size={20} />
                </Paper>
              </Box>
            )}

            <div ref={messagesEndRef} />
          </Box>

          {/* Input */}
          <Box sx={{ p: 2, borderTop: '1px solid #eee', bgcolor: 'white' }}>
            <Box sx={{ display: 'flex', gap: 1 }}>
              <TextField
                fullWidth
                placeholder="Type your message..."
                value={input}
                onChange={(e) => setInput(e.target.value)}
                onKeyPress={handleKeyPress}
                disabled={loading}
                size="small"
                multiline
                maxRows={3}
              />
              <IconButton
                color="primary"
                onClick={() => sendMessage()}
                disabled={!input.trim() || loading}
                sx={{
                  background: 'linear-gradient(45deg, #667eea 30%, #764ba2 90%)',
                  color: 'white',
                  '&:hover': {
                    background: 'linear-gradient(45deg, #764ba2 30%, #667eea 90%)',
                  },
                  '&:disabled': {
                    background: '#ccc',
                  }
                }}
              >
                <Send />
              </IconButton>
            </Box>
            <Typography variant="caption" color="text.secondary" sx={{ mt: 1, display: 'block', textAlign: 'center' }}>
              Powered by Envisage AI NLP Engine v2.0
            </Typography>
          </Box>
        </Paper>
      </Slide>
    </>
  );
};

export default AIChatbot;
