import axios from 'axios';

const API_BASE_URL = process.env.REACT_APP_API_BASE_URL || 'http://localhost:8000';
const API_PREFIX = '/api/admin/v1/events';

class EventMonitoringService {
  constructor() {
    this.apiClient = axios.create({
      baseURL: API_BASE_URL,
      timeout: 30000,
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
    });

    // Add request interceptor for authentication
    this.apiClient.interceptors.request.use(
      (config) => {
        const token = localStorage.getItem('auth_token');
        if (token) {
          config.headers.Authorization = `Bearer ${token}`;
        }
        return config;
      },
      (error) => {
        return Promise.reject(error);
      }
    );

    // Add response interceptor for error handling
    this.apiClient.interceptors.response.use(
      (response) => response,
      (error) => {
        if (error.response?.status === 401) {
          // Handle unauthorized access
          localStorage.removeItem('auth_token');
          window.location.href = '/login';
        }
        return Promise.reject(error);
      }
    );
  }

  /**
   * Get event statistics
   * @param {Object} params - Query parameters
   * @param {string} params.event_name - Filter by event name
   * @param {string} params.period - Time period (hourly, daily, monthly)
   * @param {string} params.start_date - Start date filter
   * @param {string} params.end_date - End date filter
   * @returns {Promise<Object>} Event statistics data
   */
  async getStatistics(params = {}) {
    try {
      const response = await this.apiClient.get(`${API_PREFIX}/statistics`, {
        params: this.cleanParams(params),
      });
      return response.data;
    } catch (error) {
      throw this.handleError(error, 'Failed to fetch event statistics');
    }
  }

  /**
   * Get event performance metrics
   * @param {Object} params - Query parameters
   * @param {string} params.event_name - Filter by event name
   * @param {string} params.start_date - Start date filter
   * @param {string} params.end_date - End date filter
   * @returns {Promise<Object>} Event performance data
   */
  async getPerformance(params = {}) {
    try {
      const response = await this.apiClient.get(`${API_PREFIX}/performance`, {
        params: this.cleanParams(params),
      });
      return response.data;
    } catch (error) {
      throw this.handleError(error, 'Failed to fetch event performance metrics');
    }
  }

  /**
   * Get event backlog information
   * @returns {Promise<Object>} Event backlog data
   */
  async getBacklog() {
    try {
      const response = await this.apiClient.get(`${API_PREFIX}/backlog`);
      return response.data;
    } catch (error) {
      throw this.handleError(error, 'Failed to fetch event backlog information');
    }
  }

  /**
   * Get event logs
   * @param {Object} params - Query parameters
   * @param {string} params.event_name - Filter by event name
   * @param {string} params.event_source - Filter by event source
   * @param {string} params.start_date - Start date filter
   * @param {string} params.end_date - End date filter
   * @param {number} params.page - Page number
   * @param {number} params.per_page - Items per page
   * @returns {Promise<Object>} Event logs data
   */
  async getLogs(params = {}) {
    try {
      const response = await this.apiClient.get(`${API_PREFIX}/logs`, {
        params: this.cleanParams(params),
      });
      return response.data;
    } catch (error) {
      throw this.handleError(error, 'Failed to fetch event logs');
    }
  }

  /**
   * Get idempotency statistics
   * @returns {Promise<Object>} Idempotency statistics data
   */
  async getIdempotencyStatistics() {
    try {
      const response = await this.apiClient.get(`${API_PREFIX}/idempotency-statistics`);
      return response.data;
    } catch (error) {
      throw this.handleError(error, 'Failed to fetch idempotency statistics');
    }
  }

  /**
   * Get dashboard summary
   * @returns {Promise<Object>} Dashboard summary data
   */
  async getDashboardSummary() {
    try {
      const response = await this.apiClient.get(`${API_PREFIX}/dashboard-summary`);
      return response.data;
    } catch (error) {
      throw this.handleError(error, 'Failed to fetch dashboard summary');
    }
  }

  /**
   * Clean parameters by removing null/undefined values
   * @param {Object} params - Parameters to clean
   * @returns {Object} Cleaned parameters
   */
  cleanParams(params) {
    const cleaned = {};
    Object.keys(params).forEach(key => {
      if (params[key] !== null && params[key] !== undefined && params[key] !== '') {
        cleaned[key] = params[key];
      }
    });
    return cleaned;
  }

  /**
   * Handle API errors
   * @param {Error} error - The error object
   * @param {string} defaultMessage - Default error message
   * @returns {Error} Formatted error
   */
  handleError(error, defaultMessage) {
    if (error.response) {
      // Server responded with error status
      const message = error.response.data?.message || defaultMessage;
      const status = error.response.status;
      const details = error.response.data?.errors || null;
      
      const formattedError = new Error(message);
      formattedError.status = status;
      formattedError.details = details;
      formattedError.originalError = error;
      
      return formattedError;
    } else if (error.request) {
      // Request was made but no response received
      const networkError = new Error('Network error: Unable to connect to the server');
      networkError.status = 0;
      networkError.originalError = error;
      
      return networkError;
    } else {
      // Something else happened
      const genericError = new Error(defaultMessage);
      genericError.originalError = error;
      
      return genericError;
    }
  }

  /**
   * Format date for API requests
   * @param {Date|string} date - Date to format
   * @returns {string} Formatted date string
   */
  formatDate(date) {
    if (!date) return null;
    
    const dateObj = date instanceof Date ? date : new Date(date);
    return dateObj.toISOString().split('T')[0];
  }

  /**
   * Format datetime for API requests
   * @param {Date|string} datetime - Datetime to format
   * @returns {string} Formatted datetime string
   */
  formatDateTime(datetime) {
    if (!datetime) return null;
    
    const dateObj = datetime instanceof Date ? datetime : new Date(datetime);
    return dateObj.toISOString();
  }

  /**
   * Get real-time event statistics (with polling)
   * @param {Function} callback - Callback function to handle updates
   * @param {Object} params - Query parameters
   * @param {number} interval - Polling interval in milliseconds (default: 30000)
   * @returns {Function} Function to stop polling
   */
  subscribeToStatistics(callback, params = {}, interval = 30000) {
    let isActive = true;
    
    const poll = async () => {
      if (!isActive) return;
      
      try {
        const data = await this.getStatistics(params);
        callback(null, data);
      } catch (error) {
        callback(error, null);
      }
      
      if (isActive) {
        setTimeout(poll, interval);
      }
    };
    
    // Start polling immediately
    poll();
    
    // Return function to stop polling
    return () => {
      isActive = false;
    };
  }

  /**
   * Get real-time dashboard summary (with polling)
   * @param {Function} callback - Callback function to handle updates
   * @param {number} interval - Polling interval in milliseconds (default: 60000)
   * @returns {Function} Function to stop polling
   */
  subscribeToDashboardSummary(callback, interval = 60000) {
    let isActive = true;
    
    const poll = async () => {
      if (!isActive) return;
      
      try {
        const data = await this.getDashboardSummary();
        callback(null, data);
      } catch (error) {
        callback(error, null);
      }
      
      if (isActive) {
        setTimeout(poll, interval);
      }
    };
    
    // Start polling immediately
    poll();
    
    // Return function to stop polling
    return () => {
      isActive = false;
    };
  }
}

// Create and export a singleton instance
const eventMonitoringService = new EventMonitoringService();
export default eventMonitoringService;