import React, { useState, useEffect } from 'react';
import { 
  LineChart, Line, BarChart, Bar, PieChart, Pie, 
  XAxis, YAxis, CartesianGrid, Tooltip, Legend, 
  ResponsiveContainer, Cell
} from 'recharts';
import eventMonitoringService from '../../services/eventMonitoringService';
import './EventMonitoringDashboard.css';

const EventMonitoringDashboard = () => {
  const [statistics, setStatistics] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [timeframe, setTimeframe] = useState('day');
  const [refreshInterval, setRefreshInterval] = useState(60); // seconds
  const [autoRefresh, setAutoRefresh] = useState(true);

  const COLORS = ['#0088FE', '#00C49F', '#FFBB28', '#FF8042', '#8884D8', '#82CA9D', '#FFC658', '#8DD1E1'];

  // Load event statistics
  useEffect(() => {
    const fetchStatistics = async () => {
      try {
        setLoading(true);
        const response = await eventMonitoringService.getDashboardSummary();
        setStatistics(response.data);
        setError(null);
      } catch (err) {
        setError('Failed to load event statistics');
        console.error('Error fetching event statistics:', err);
      } finally {
        setLoading(false);
      }
    };

    fetchStatistics();

    // Set up auto-refresh
    let intervalId;
    if (autoRefresh) {
      intervalId = setInterval(fetchStatistics, refreshInterval * 1000);
    }

    return () => {
      if (intervalId) clearInterval(intervalId);
    };
  }, [timeframe, refreshInterval, autoRefresh]);

  // Handle timeframe change
  const handleTimeframeChange = (e) => {
    setTimeframe(e.target.value);
  };

  // Handle refresh interval change
  const handleRefreshIntervalChange = (e) => {
    setRefreshInterval(parseInt(e.target.value, 10));
  };

  // Toggle auto-refresh
  const toggleAutoRefresh = () => {
    setAutoRefresh(!autoRefresh);
  };

  // Manual refresh
  const handleManualRefresh = async () => {
    try {
      setLoading(true);
      const response = await axios.get(`/api/admin/v1/events/statistics?timeframe=${timeframe}`);
      setStatistics(response.data.data);
      setError(null);
    } catch (err) {
      setError('Failed to load event statistics');
      console.error('Error fetching event statistics:', err);
    } finally {
      setLoading(false);
    }
  };

  // Format timestamp for display
  const formatTimestamp = (timestamp) => {
    if (!timestamp) return '';
    
    const date = new Date(timestamp);
    
    switch (timeframe) {
      case 'hour':
        return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
      case 'day':
        return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
      case 'week':
      case 'month':
        return date.toLocaleDateString();
      default:
        return date.toLocaleString();
    }
  };

  if (loading && !statistics) {
    return (
      <div className="event-monitoring-dashboard loading">
        <div className="spinner"></div>
        <p>Loading event statistics...</p>
      </div>
    );
  }

  if (error && !statistics) {
    return (
      <div className="event-monitoring-dashboard error">
        <div className="error-message">
          <h3>Error</h3>
          <p>{error}</p>
          <button onClick={handleManualRefresh}>Retry</button>
        </div>
      </div>
    );
  }

  return (
    <div className="event-monitoring-dashboard">
      <div className="dashboard-header">
        <h1>Event Monitoring Dashboard</h1>
        
        <div className="dashboard-controls">
          <div className="control-group">
            <label htmlFor="timeframe">Timeframe:</label>
            <select 
              id="timeframe" 
              value={timeframe} 
              onChange={handleTimeframeChange}
            >
              <option value="hour">Last Hour</option>
              <option value="day">Last 24 Hours</option>
              <option value="week">Last Week</option>
              <option value="month">Last Month</option>
            </select>
          </div>
          
          <div className="control-group">
            <label htmlFor="refresh-interval">Refresh Every:</label>
            <select 
              id="refresh-interval" 
              value={refreshInterval} 
              onChange={handleRefreshIntervalChange}
              disabled={!autoRefresh}
            >
              <option value="30">30 seconds</option>
              <option value="60">1 minute</option>
              <option value="300">5 minutes</option>
              <option value="600">10 minutes</option>
            </select>
          </div>
          
          <div className="control-group">
            <label>
              <input 
                type="checkbox" 
                checked={autoRefresh} 
                onChange={toggleAutoRefresh} 
              />
              Auto-refresh
            </label>
          </div>
          
          <button 
            className="refresh-button" 
            onClick={handleManualRefresh}
            disabled={loading}
          >
            {loading ? 'Refreshing...' : 'Refresh Now'}
          </button>
        </div>
      </div>
      
      {loading && (
        <div className="overlay-loading">
          <div className="spinner"></div>
        </div>
      )}
      
      <div className="dashboard-metrics">
        <div className="metric-card">
          <h3>Total Events</h3>
          <div className="metric-value">{statistics?.total_count.toLocaleString()}</div>
          <div className="metric-period">in the selected period</div>
        </div>
        
        <div className="metric-card">
          <h3>Error Rate</h3>
          <div className="metric-value">{statistics?.error_rate.toFixed(2)}%</div>
          <div className="metric-period">of all events</div>
        </div>
        
        <div className="metric-card">
          <h3>Error Count</h3>
          <div className="metric-value">{statistics?.error_count.toLocaleString()}</div>
          <div className="metric-period">in the selected period</div>
        </div>
      </div>
      
      <div className="dashboard-charts">
        <div className="chart-container">
          <h3>Events Over Time</h3>
          <ResponsiveContainer width="100%" height={300}>
            <LineChart
              data={statistics?.events_by_time}
              margin={{ top: 5, right: 30, left: 20, bottom: 5 }}
            >
              <CartesianGrid strokeDasharray="3 3" />
              <XAxis 
                dataKey="date_format" 
                tickFormatter={formatTimestamp} 
              />
              <YAxis />
              <Tooltip 
                formatter={(value) => [value.toLocaleString(), 'Events']}
                labelFormatter={formatTimestamp}
              />
              <Legend />
              <Line 
                type="monotone" 
                dataKey="count" 
                name="Event Count" 
                stroke="#8884d8" 
                activeDot={{ r: 8 }} 
              />
            </LineChart>
          </ResponsiveContainer>
        </div>
        
        <div className="chart-container">
          <h3>Events by Type</h3>
          <ResponsiveContainer width="100%" height={300}>
            <BarChart
              data={statistics?.events_by_type.slice(0, 10)}
              margin={{ top: 5, right: 30, left: 20, bottom: 5 }}
              layout="vertical"
            >
              <CartesianGrid strokeDasharray="3 3" />
              <XAxis type="number" />
              <YAxis 
                dataKey="event_type" 
                type="category" 
                width={150}
                tick={{ fontSize: 12 }}
              />
              <Tooltip 
                formatter={(value) => [value.toLocaleString(), 'Events']}
              />
              <Legend />
              <Bar 
                dataKey="count" 
                name="Event Count" 
                fill="#82ca9d" 
              />
            </BarChart>
          </ResponsiveContainer>
        </div>
        
        <div className="chart-container">
          <h3>Top Error Types</h3>
          <ResponsiveContainer width="100%" height={300}>
            <PieChart>
              <Pie
                data={statistics?.top_errors}
                dataKey="count"
                nameKey="event_type"
                cx="50%"
                cy="50%"
                outerRadius={100}
                fill="#8884d8"
                label={({ event_type, percent }) => 
                  `${event_type}: ${(percent * 100).toFixed(0)}%`
                }
              >
                {statistics?.top_errors.map((entry, index) => (
                  <Cell key={`cell-${index}`} fill={COLORS[index % COLORS.length]} />
                ))}
              </Pie>
              <Tooltip 
                formatter={(value) => [value.toLocaleString(), 'Errors']}
              />
              <Legend />
            </PieChart>
          </ResponsiveContainer>
        </div>
      </div>
      
      <div className="dashboard-footer">
        <p>
          Last updated: {new Date().toLocaleString()}
          {autoRefresh && ` • Auto-refreshing every ${refreshInterval} seconds`}
        </p>
      </div>
    </div>
  );
};

export default EventMonitoringDashboard;