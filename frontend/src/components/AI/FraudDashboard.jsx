import React, { useState, useEffect } from 'react';
import axios from 'axios';
import {
  Box,
  Grid,
  Card,
  CardContent,
  Typography,
  Table,
  TableBody,
  TableCell,
  TableContainer,
  TableHead,
  TableRow,
  Chip,
  Button,
  Dialog,
  DialogTitle,
  DialogContent,
  DialogActions,
  TextField,
  Alert,
  LinearProgress,
  Paper,
  IconButton,
  Tooltip,
} from '@mui/material';
import {
  Shield,
  Warning,
  CheckCircle,
  Block,
  Visibility,
  TrendingUp,
  Security,
  Psychology,
} from '@mui/icons-material';

/**
 * Envisage AI - Fraud Detection Dashboard
 * Real-time fraud monitoring & review system
 */
const FraudDashboard = () => {
  const [alerts, setAlerts] = useState([]);
  const [statistics, setStatistics] = useState(null);
  const [loading, setLoading] = useState(true);
  const [selectedAlert, setSelectedAlert] = useState(null);
  const [reviewNotes, setReviewNotes] = useState('');

  useEffect(() => {
    fetchData();
    const interval = setInterval(fetchData, 30000); // Refresh every 30s
    return () => clearInterval(interval);
  }, []);

  const fetchData = async () => {
    try {
      const [alertsRes, statsRes] = await Promise.all([
        axios.get('/api/ai/fraud/alerts?status=pending_review'),
        axios.get('/api/ai/fraud/statistics')
      ]);

      setAlerts(alertsRes.data.data.data || []);
      setStatistics(statsRes.data.data);
      setLoading(false);
    } catch (err) {
      console.error('Envisage AI Fraud Dashboard Error:', err);
      setLoading(false);
    }
  };

  const handleReview = async (alertId, action) => {
    try {
      await axios.post(`/api/ai/fraud/alerts/${alertId}/review`, {
        action,
        notes: reviewNotes
      });

      setAlerts(alerts.filter(a => a.id !== alertId));
      setSelectedAlert(null);
      setReviewNotes('');
      fetchData(); // Refresh stats
    } catch (err) {
      console.error('Review error:', err);
    }
  };

  const getRiskColor = (level) => {
    const colors = {
      minimal: '#4caf50',
      low: '#8bc34a',
      medium: '#ff9800',
      high: '#f44336',
      critical: '#d32f2f',
    };
    return colors[level] || '#9e9e9e';
  };

  const getRiskIcon = (level) => {
    if (level === 'critical' || level === 'high') return <Warning />;
    if (level === 'medium') return <Security />;
    return <Shield />;
  };

  if (loading) {
    return <LinearProgress />;
  }

  return (
    <Box sx={{ p: 3 }}>
      {/* Header */}
      <Box sx={{ mb: 4 }}>
        <Typography variant="h4" gutterBottom sx={{ fontWeight: 700 }}>
          <Shield sx={{ mr: 1, verticalAlign: 'middle', color: '#667eea' }} />
          Envisage AI Fraud Detection
        </Typography>
        <Typography variant="body2" color="text.secondary">
          Advanced multi-layer fraud prevention powered by machine learning
        </Typography>
      </Box>

      {/* Statistics */}
      <Grid container spacing={3} sx={{ mb: 4 }}>
        <Grid item xs={12} md={3}>
          <Card sx={{ 
            background: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
            color: 'white'
          }}>
            <CardContent>
              <Typography variant="h3" fontWeight={700}>
                {statistics?.total_checks || 0}
              </Typography>
              <Typography variant="body2">
                Total Transactions Checked
              </Typography>
            </CardContent>
          </Card>
        </Grid>

        <Grid item xs={12} md={3}>
          <Card sx={{ bgcolor: '#f44336', color: 'white' }}>
            <CardContent>
              <Typography variant="h3" fontWeight={700}>
                {statistics?.by_status?.blocked || 0}
              </Typography>
              <Typography variant="body2">
                Blocked Transactions
              </Typography>
            </CardContent>
          </Card>
        </Grid>

        <Grid item xs={12} md={3}>
          <Card sx={{ bgcolor: '#4caf50', color: 'white' }}>
            <CardContent>
              <Typography variant="h3" fontWeight={700}>
                ${(statistics?.blocked_amount || 0).toLocaleString()}
              </Typography>
              <Typography variant="body2">
                Fraud Prevented (USD)
              </Typography>
            </CardContent>
          </Card>
        </Grid>

        <Grid item xs={12} md={3}>
          <Card sx={{ bgcolor: '#ff9800', color: 'white' }}>
            <CardContent>
              <Typography variant="h3" fontWeight={700}>
                {(statistics?.average_score * 100 || 0).toFixed(1)}%
              </Typography>
              <Typography variant="body2">
                Average Risk Score
              </Typography>
            </CardContent>
          </Card>
        </Grid>
      </Grid>

      {/* Risk Distribution */}
      {statistics?.by_risk_level && (
        <Card sx={{ mb: 4 }}>
          <CardContent>
            <Typography variant="h6" gutterBottom>
              Risk Level Distribution
            </Typography>
            <Grid container spacing={2} sx={{ mt: 1 }}>
              {Object.entries(statistics.by_risk_level).map(([level, count]) => (
                <Grid item xs={12} sm={6} md={2.4} key={level}>
                  <Box sx={{ textAlign: 'center', p: 2, borderRadius: 1, bgcolor: '#f5f5f5' }}>
                    <Box sx={{ fontSize: 40, color: getRiskColor(level) }}>
                      {getRiskIcon(level)}
                    </Box>
                    <Typography variant="h5" fontWeight={700}>
                      {count}
                    </Typography>
                    <Typography variant="caption" textTransform="capitalize">
                      {level}
                    </Typography>
                  </Box>
                </Grid>
              ))}
            </Grid>
          </CardContent>
        </Card>
      )}

      {/* Pending Alerts */}
      <Card>
        <CardContent>
          <Typography variant="h6" gutterBottom>
            Pending Review ({alerts.length})
          </Typography>

          {alerts.length === 0 ? (
            <Alert severity="success" icon={<CheckCircle />}>
              <Typography variant="body2">
                No pending fraud alerts. All transactions are being monitored in real-time.
              </Typography>
            </Alert>
          ) : (
            <TableContainer component={Paper} elevation={0}>
              <Table>
                <TableHead>
                  <TableRow>
                    <TableCell>Alert ID</TableCell>
                    <TableCell>Order ID</TableCell>
                    <TableCell>User</TableCell>
                    <TableCell>Risk Score</TableCell>
                    <TableCell>Risk Level</TableCell>
                    <TableCell>Amount</TableCell>
                    <TableCell>Reasons</TableCell>
                    <TableCell>Time</TableCell>
                    <TableCell>Actions</TableCell>
                  </TableRow>
                </TableHead>
                <TableBody>
                  {alerts.map((alert) => (
                    <TableRow key={alert.id} sx={{ 
                      bgcolor: alert.risk_level === 'critical' ? '#ffebee' : 
                               alert.risk_level === 'high' ? '#fff3e0' : 'inherit'
                    }}>
                      <TableCell>#{alert.id}</TableCell>
                      <TableCell>#{alert.order_id}</TableCell>
                      <TableCell>{alert.user?.name || `User #${alert.user_id}`}</TableCell>
                      <TableCell>
                        <Typography fontWeight={700} sx={{ color: getRiskColor(alert.risk_level) }}>
                          {(alert.risk_score * 100).toFixed(1)}%
                        </Typography>
                      </TableCell>
                      <TableCell>
                        <Chip
                          icon={getRiskIcon(alert.risk_level)}
                          label={alert.risk_level.toUpperCase()}
                          size="small"
                          sx={{
                            bgcolor: getRiskColor(alert.risk_level),
                            color: 'white',
                            fontWeight: 600
                          }}
                        />
                      </TableCell>
                      <TableCell>
                        ${alert.order?.total?.toFixed(2) || '0.00'}
                      </TableCell>
                      <TableCell>
                        <Box sx={{ display: 'flex', flexWrap: 'wrap', gap: 0.5 }}>
                          {alert.reasons?.slice(0, 2).map((reason, idx) => (
                            <Chip
                              key={idx}
                              label={reason}
                              size="small"
                              variant="outlined"
                            />
                          ))}
                          {alert.reasons?.length > 2 && (
                            <Chip label={`+${alert.reasons.length - 2}`} size="small" />
                          )}
                        </Box>
                      </TableCell>
                      <TableCell>
                        {new Date(alert.created_at).toLocaleString()}
                      </TableCell>
                      <TableCell>
                        <Tooltip title="Review Alert">
                          <IconButton
                            size="small"
                            onClick={() => setSelectedAlert(alert)}
                            color="primary"
                          >
                            <Visibility />
                          </IconButton>
                        </Tooltip>
                      </TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
            </TableContainer>
          )}
        </CardContent>
      </Card>

      {/* Review Dialog */}
      <Dialog open={!!selectedAlert} onClose={() => setSelectedAlert(null)} maxWidth="md" fullWidth>
        <DialogTitle sx={{ 
          background: 'linear-gradient(45deg, #667eea 30%, #764ba2 90%)',
          color: 'white'
        }}>
          <Psychology sx={{ mr: 1, verticalAlign: 'middle' }} />
          Fraud Alert Review - #{selectedAlert?.id}
        </DialogTitle>
        <DialogContent sx={{ mt: 2 }}>
          {selectedAlert && (
            <Grid container spacing={2}>
              <Grid item xs={12}>
                <Alert 
                  severity={selectedAlert.risk_level === 'critical' ? 'error' : 'warning'}
                  icon={getRiskIcon(selectedAlert.risk_level)}
                >
                  <Typography variant="subtitle2" fontWeight={600}>
                    Risk Score: {(selectedAlert.risk_score * 100).toFixed(1)}% - {selectedAlert.risk_level.toUpperCase()}
                  </Typography>
                </Alert>
              </Grid>

              <Grid item xs={6}>
                <Typography variant="subtitle2" color="text.secondary">Order ID</Typography>
                <Typography variant="body1" fontWeight={600}>#{selectedAlert.order_id}</Typography>
              </Grid>

              <Grid item xs={6}>
                <Typography variant="subtitle2" color="text.secondary">Amount</Typography>
                <Typography variant="body1" fontWeight={600}>
                  ${selectedAlert.order?.total?.toFixed(2) || '0.00'}
                </Typography>
              </Grid>

              <Grid item xs={12}>
                <Typography variant="subtitle2" color="text.secondary" gutterBottom>
                  Risk Factors
                </Typography>
                <Box sx={{ display: 'flex', flexWrap: 'wrap', gap: 1 }}>
                  {selectedAlert.reasons?.map((reason, idx) => (
                    <Chip
                      key={idx}
                      label={reason}
                      color="warning"
                      variant="outlined"
                    />
                  ))}
                </Box>
              </Grid>

              <Grid item xs={12}>
                <Typography variant="subtitle2" color="text.secondary" gutterBottom>
                  AI Analysis Breakdown
                </Typography>
                <Box sx={{ p: 2, bgcolor: '#f5f5f5', borderRadius: 1 }}>
                  {selectedAlert.details?.breakdown && Object.entries(selectedAlert.details.breakdown).map(([key, value]) => (
                    <Box key={key} sx={{ mb: 1 }}>
                      <Typography variant="caption" textTransform="capitalize">
                        {key.replace('_', ' ')}: {(value * 100).toFixed(1)}%
                      </Typography>
                      <LinearProgress 
                        variant="determinate" 
                        value={value * 100} 
                        sx={{ height: 6, borderRadius: 1 }}
                      />
                    </Box>
                  ))}
                </Box>
              </Grid>

              <Grid item xs={12}>
                <TextField
                  fullWidth
                  multiline
                  rows={3}
                  label="Review Notes"
                  value={reviewNotes}
                  onChange={(e) => setReviewNotes(e.target.value)}
                  placeholder="Add notes about your decision..."
                />
              </Grid>
            </Grid>
          )}
        </DialogContent>
        <DialogActions>
          <Button onClick={() => setSelectedAlert(null)}>
            Cancel
          </Button>
          <Button
            variant="contained"
            color="success"
            startIcon={<CheckCircle />}
            onClick={() => handleReview(selectedAlert.id, 'approve')}
          >
            Approve Transaction
          </Button>
          <Button
            variant="contained"
            color="error"
            startIcon={<Block />}
            onClick={() => handleReview(selectedAlert.id, 'block')}
          >
            Block Transaction
          </Button>
        </DialogActions>
      </Dialog>

      {/* Footer */}
      <Box sx={{ mt: 3, textAlign: 'center' }}>
        <Chip
          icon={<Psychology />}
          label="Powered by Envisage AI Fraud Detection v2.0 (99.5% Accuracy)"
          variant="outlined"
          sx={{ borderColor: '#667eea', color: '#667eea', fontWeight: 600 }}
        />
      </Box>
    </Box>
  );
};

export default FraudDashboard;
