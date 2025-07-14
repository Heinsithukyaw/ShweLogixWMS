import axios from 'axios';
import { toast } from 'react-toastify';

class NotificationService {
  constructor() {
    this.notifications = [];
    this.unreadCount = 0;
    this.listeners = [];
    this.isInitialized = false;
  }

  /**
   * Initialize the notification service
   */
  async init() {
    if (this.isInitialized) {
      return;
    }

    try {
      // Get initial unread count
      await this.fetchUnreadCount();
      
      // Subscribe to notification channels if using a real-time service
      this.subscribeToNotifications();
      
      this.isInitialized = true;
    } catch (error) {
      console.error('Failed to initialize notification service', error);
    }
  }

  /**
   * Subscribe to notification channels
   * This would use a WebSocket or similar real-time service
   */
  subscribeToNotifications() {
    // Implementation would depend on your real-time solution
    // For example, if using Socket.io:
    /*
    const socket = io('/notifications');
    
    socket.on('notification', (event) => {
      this.handleNewNotification(event);
    });
    
    const userId = this.getCurrentUserId();
    if (userId) {
      socket.on(`notification.user.${userId}`, (event) => {
        this.handleNewNotification(event);
      });
    }
    */
  }

  /**
   * Handle a new notification
   * 
   * @param {Object} event The notification event
   */
  handleNewNotification(event) {
    // Add the notification to the list
    this.notifications.unshift({
      id: Date.now(), // Temporary ID until we fetch from API
      type: event.type,
      message: event.message,
      data: event.data,
      is_read: false,
      created_at: new Date().toISOString()
    });

    // Increment unread count
    this.unreadCount++;

    // Notify listeners
    this.notifyListeners();

    // Show toast notification
    this.showToast(event.type, event.message);
  }

  /**
   * Show a toast notification
   * 
   * @param {string} type The notification type
   * @param {string} message The notification message
   */
  showToast(type, message) {
    const toastType = this.mapNotificationTypeToToastType(type);
    toast[toastType](message);
  }

  /**
   * Map notification type to toast type
   * 
   * @param {string} notificationType The notification type
   * @returns {string} The toast type
   */
  mapNotificationTypeToToastType(notificationType) {
    const typeMap = {
      'error': 'error',
      'warning': 'warning',
      'success': 'success',
      'info': 'info'
    };

    return typeMap[notificationType] || 'info';
  }

  /**
   * Fetch notifications from the API
   * 
   * @param {Object} params Query parameters
   * @returns {Promise<Array>} The notifications
   */
  async fetchNotifications(params = {}) {
    try {
      const response = await axios.get('/api/admin/v1/notifications', { params });
      this.notifications = response.data.data.data; // Pagination data structure
      this.notifyListeners();
      return this.notifications;
    } catch (error) {
      console.error('Failed to fetch notifications', error);
      return [];
    }
  }

  /**
   * Fetch unread notification count from the API
   * 
   * @returns {Promise<number>} The unread count
   */
  async fetchUnreadCount() {
    try {
      const response = await axios.get('/api/admin/v1/notifications/unread-count');
      this.unreadCount = response.data.data.count;
      this.notifyListeners();
      return this.unreadCount;
    } catch (error) {
      console.error('Failed to fetch unread count', error);
      return 0;
    }
  }

  /**
   * Mark a notification as read
   * 
   * @param {number} id The notification ID
   * @returns {Promise<boolean>} Whether the operation was successful
   */
  async markAsRead(id) {
    try {
      await axios.post(`/api/admin/v1/notifications/${id}/mark-as-read`);
      
      // Update local state
      const notification = this.notifications.find(n => n.id === id);
      if (notification && !notification.is_read) {
        notification.is_read = true;
        notification.read_at = new Date().toISOString();
        this.unreadCount = Math.max(0, this.unreadCount - 1);
        this.notifyListeners();
      }
      
      return true;
    } catch (error) {
      console.error('Failed to mark notification as read', error);
      return false;
    }
  }

  /**
   * Mark all notifications as read
   * 
   * @param {Object} params Query parameters
   * @returns {Promise<boolean>} Whether the operation was successful
   */
  async markAllAsRead(params = {}) {
    try {
      await axios.post('/api/admin/v1/notifications/mark-all-as-read', params);
      
      // Update local state
      this.notifications.forEach(notification => {
        notification.is_read = true;
        notification.read_at = new Date().toISOString();
      });
      
      this.unreadCount = 0;
      this.notifyListeners();
      
      return true;
    } catch (error) {
      console.error('Failed to mark all notifications as read', error);
      return false;
    }
  }

  /**
   * Add a listener for notification changes
   * 
   * @param {Function} listener The listener function
   * @returns {Function} A function to remove the listener
   */
  addListener(listener) {
    this.listeners.push(listener);
    
    // Return a function to remove the listener
    return () => {
      this.listeners = this.listeners.filter(l => l !== listener);
    };
  }

  /**
   * Notify all listeners of changes
   */
  notifyListeners() {
    this.listeners.forEach(listener => {
      listener({
        notifications: this.notifications,
        unreadCount: this.unreadCount
      });
    });
  }

  /**
   * Get the current user ID
   * 
   * @returns {number|null} The user ID
   */
  getCurrentUserId() {
    // This should be implemented based on your authentication system
    // For example, you might get it from localStorage or a Redux store
    return localStorage.getItem('user_id');
  }
}

// Create a singleton instance
const notificationService = new NotificationService();

export default notificationService;