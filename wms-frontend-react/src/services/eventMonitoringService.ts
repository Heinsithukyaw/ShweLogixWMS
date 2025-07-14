import axios, { AxiosInstance, AxiosResponse } from 'axios';

const API_BASE_URL = process.env.REACT_APP_API_BASE_URL || 'http://localhost:12000';
const API_PREFIX = '/api/admin/v1/events';

interface EventStatisticsParams {
  event_name?: string;
  period?: string;
  start_date?: string;
  end_date?: string;
}

interface EventPerformanceParams {
  event_name?: string;
  start_date?: string;
  end_date?: string;
}

interface EventLogsParams {
  event_name?: string;
  event_source?: string;
  start_date?: string;
  end_date?: string;
  page?: number;
  per_page?: number;
}

interface ApiResponse<T> {
  data: T;
  message?: string;
  status?: string;
}

class EventMonitoringService {
  private apiClient: AxiosInstance;

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
          window.location.href = '/signin';
        }
        return Promise.reject(error);
      }
    );
  }

  /**
   * Get event statistics
   */
  async getStatistics(params: EventStatisticsParams = {}): Promise<ApiResponse<any>> {
    try {
      const response: AxiosResponse<ApiResponse<any>> = await this.apiClient.get(`${API_PREFIX}/statistics`, {
        params: this.cleanParams(params),
      });
      return response.data;
    } catch (error) {
      throw this.handleError(error, 'Failed to fetch event statistics');
    }
  }

  /**
   * Get event performance metrics
   */
  async getPerformance(params: EventPerformanceParams = {}): Promise<ApiResponse<any>> {
    try {
      const response: AxiosResponse<ApiResponse<any>> = await this.apiClient.get(`${API_PREFIX}/performance`, {
        params: this.cleanParams(params),
      });
      return response.data;
    } catch (error) {
      throw this.handleError(error, 'Failed to fetch event performance metrics');
    }
  }

  /**
   * Get event backlog information
   */
  async getBacklog(): Promise<ApiResponse<any>> {
    try {
      const response: AxiosResponse<ApiResponse<any>> = await this.apiClient.get(`${API_PREFIX}/backlog`);
      return response.data;
    } catch (error) {
      throw this.handleError(error, 'Failed to fetch event backlog information');
    }
  }

  /**
   * Get event logs
   */
  async getLogs(params: EventLogsParams = {}): Promise<ApiResponse<any>> {
    try {
      const response: AxiosResponse<ApiResponse<any>> = await this.apiClient.get(`${API_PREFIX}/logs`, {
        params: this.cleanParams(params),
      });
      return response.data;
    } catch (error) {
      throw this.handleError(error, 'Failed to fetch event logs');
    }
  }

  /**
   * Get idempotency statistics
   */
  async getIdempotencyStatistics(): Promise<ApiResponse<any>> {
    try {
      const response: AxiosResponse<ApiResponse<any>> = await this.apiClient.get(`${API_PREFIX}/idempotency-statistics`);
      return response.data;
    } catch (error) {
      throw this.handleError(error, 'Failed to fetch idempotency statistics');
    }
  }

  /**
   * Get dashboard summary
   */
  async getDashboardSummary(): Promise<ApiResponse<any>> {
    try {
      const response: AxiosResponse<ApiResponse<any>> = await this.apiClient.get(`${API_PREFIX}/dashboard-summary`);
      return response.data;
    } catch (error) {
      throw this.handleError(error, 'Failed to fetch dashboard summary');
    }
  }

  /**
   * Clean parameters by removing null/undefined values
   */
  private cleanParams(params: Record<string, any>): Record<string, any> {
    const cleaned: Record<string, any> = {};
    Object.keys(params).forEach(key => {
      if (params[key] !== null && params[key] !== undefined && params[key] !== '') {
        cleaned[key] = params[key];
      }
    });
    return cleaned;
  }

  /**
   * Handle API errors
   */
  private handleError(error: any, defaultMessage: string): Error {
    if (error.response) {
      // Server responded with error status
      const message = error.response.data?.message || defaultMessage;
      const status = error.response.status;
      const details = error.response.data?.errors || null;
      
      const formattedError = new Error(message) as any;
      formattedError.status = status;
      formattedError.details = details;
      formattedError.originalError = error;
      
      return formattedError;
    } else if (error.request) {
      // Request was made but no response received
      const networkError = new Error('Network error: Unable to connect to the server') as any;
      networkError.status = 0;
      networkError.originalError = error;
      
      return networkError;
    } else {
      // Something else happened
      const genericError = new Error(defaultMessage) as any;
      genericError.originalError = error;
      
      return genericError;
    }
  }

  /**
   * Format date for API requests
   */
  formatDate(date: Date | string | null): string | null {
    if (!date) return null;
    
    const dateObj = date instanceof Date ? date : new Date(date);
    return dateObj.toISOString().split('T')[0];
  }

  /**
   * Format datetime for API requests
   */
  formatDateTime(datetime: Date | string | null): string | null {
    if (!datetime) return null;
    
    const dateObj = datetime instanceof Date ? datetime : new Date(datetime);
    return dateObj.toISOString();
  }

  /**
   * Get real-time event statistics (with polling)
   */
  subscribeToStatistics(
    callback: (error: Error | null, data: any) => void, 
    params: EventStatisticsParams = {}, 
    interval: number = 30000
  ): () => void {
    let isActive = true;
    
    const poll = async () => {
      if (!isActive) return;
      
      try {
        const data = await this.getStatistics(params);
        callback(null, data);
      } catch (error) {
        callback(error as Error, null);
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
   */
  subscribeToDashboardSummary(
    callback: (error: Error | null, data: any) => void, 
    interval: number = 60000
  ): () => void {
    let isActive = true;
    
    const poll = async () => {
      if (!isActive) return;
      
      try {
        const data = await this.getDashboardSummary();
        callback(null, data);
      } catch (error) {
        callback(error as Error, null);
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