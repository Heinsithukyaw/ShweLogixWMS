import React, { useState, useEffect } from 'react';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { 
  faBell, 
  faBellSlash, 
  faCheck, 
  faChevronDown, 
  faSpinner,
  faExclamationCircle, 
  faExclamationTriangle, 
  faCheckCircle, 
  faInfoCircle, 
  faTasks, 
  faTruckLoading, 
  faBoxes, 
  faBox 
} from '@fortawesome/free-solid-svg-icons';
import axios from 'axios';
import notificationService from '../components/notifications/NotificationService';
import './NotificationsPage.css';

const NotificationsPage = () => {
  const [notifications, setNotifications] = useState([]);
  const [loading, setLoading] = useState(true);
  const [loadingMore, setLoadingMore] = useState(false);
  const [currentPage, setCurrentPage] = useState(1);
  const [lastPage, setLastPage] = useState(1);
  const [filter, setFilter] = useState('all');
  const [typeFilters, setTypeFilters] = useState([]);
  const [showFilterDropdown, setShowFilterDropdown] = useState(false);
  
  // Computed properties
  const hasMorePages = currentPage < lastPage;
  const hasUnread = notifications.some(n => !n.is_read);
  
  // Load notifications on mount
  useEffect(() => {
    loadNotifications();
  }, []);
  
  // Load notifications with current filters
  const loadNotifications = async (page = 1) => {
    try {
      if (page === 1) {
        setLoading(true);
      } else {
        setLoadingMore(true);
      }
      
      const params = {
        page,
        per_page: 20
      };
      
      // Add read status filter
      if (filter === 'unread') {
        params.is_read = false;
      } else if (filter === 'read') {
        params.is_read = true;
      }
      
      // Add type filters
      if (typeFilters.length > 0) {
        params.type = typeFilters;
      }
      
      const response = await axios.get('/api/admin/v1/notifications', { params });
      
      if (page === 1) {
        setNotifications(response.data.data.data);
      } else {
        setNotifications([...notifications, ...response.data.data.data]);
      }
      
      setCurrentPage(response.data.data.current_page);
      setLastPage(response.data.data.last_page);
    } catch (error) {
      console.error('Failed to load notifications', error);
    } finally {
      setLoading(false);
      setLoadingMore(false);
    }
  };
  
  // Load more notifications
  const loadMore = () => {
    loadNotifications(currentPage + 1);
  };
  
  // Mark a notification as read
  const markAsRead = async (id) => {
    await notificationService.markAsRead(id);
    
    // Update local state
    setNotifications(notifications.map(notification => 
      notification.id === id 
        ? { ...notification, is_read: true, read_at: new Date().toISOString() } 
        : notification
    ));
  };
  
  // Mark all notifications as read
  const markAllAsRead = async () => {
    const params = {};
    
    // Add type filters
    if (typeFilters.length > 0) {
      params.type = typeFilters;
    }
    
    await notificationService.markAllAsRead(params);
    
    // Update local state
    setNotifications(notifications.map(notification => ({
      ...notification,
      is_read: true,
      read_at: new Date().toISOString()
    })));
  };
  
  // Toggle filter dropdown
  const toggleFilterDropdown = () => {
    setShowFilterDropdown(!showFilterDropdown);
  };
  
  // Apply filters and reload notifications
  const applyFilter = () => {
    setCurrentPage(1);
    loadNotifications(1);
  };
  
  // Handle filter change
  const handleFilterChange = (e) => {
    setFilter(e.target.value);
    setTimeout(() => applyFilter(), 0);
  };
  
  // Handle type filter change
  const handleTypeFilterChange = (e) => {
    const value = e.target.value;
    
    if (e.target.checked) {
      setTypeFilters([...typeFilters, value]);
    } else {
      setTypeFilters(typeFilters.filter(type => type !== value));
    }
    
    setTimeout(() => applyFilter(), 0);
  };
  
  // Get icon based on notification type
  const getIcon = (type) => {
    switch (type) {
      case 'error':
        return faExclamationCircle;
      case 'warning':
        return faExclamationTriangle;
      case 'success':
        return faCheckCircle;
      case 'info':
        return faInfoCircle;
      case 'task_assigned':
        return faTasks;
      case 'asn_received':
        return faTruckLoading;
      case 'inventory_changed':
        return faBoxes;
      case 'product_created':
        return faBox;
      default:
        return faBell;
    }
  };
  
  // Format timestamp to relative time
  const formatTime = (timestamp) => {
    const date = new Date(timestamp);
    const now = new Date();
    const diffMs = now - date;
    const diffSec = Math.floor(diffMs / 1000);
    const diffMin = Math.floor(diffSec / 60);
    const diffHour = Math.floor(diffMin / 60);
    const diffDay = Math.floor(diffHour / 24);
    
    if (diffSec < 60) {
      return 'just now';
    } else if (diffMin < 60) {
      return `${diffMin} min ago`;
    } else if (diffHour < 24) {
      return `${diffHour} hr ago`;
    } else if (diffDay < 7) {
      return `${diffDay} day ago`;
    } else {
      return date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    }
  };
  
  return (
    <div className="notifications-page">
      <div className="notifications-header">
        <h1>Notifications</h1>
        <div className="notifications-actions">
          {hasUnread && (
            <button onClick={markAllAsRead} className="mark-all-read-btn">
              Mark all as read
            </button>
          )}
          <div className="filter-dropdown">
            <button onClick={toggleFilterDropdown} className="filter-btn">
              Filter <FontAwesomeIcon icon={faChevronDown} />
            </button>
            {showFilterDropdown && (
              <div className="filter-options">
                <div className="filter-option">
                  <input 
                    type="radio" 
                    id="filter-all" 
                    name="filter" 
                    value="all" 
                    checked={filter === 'all'}
                    onChange={handleFilterChange}
                  />
                  <label htmlFor="filter-all">All notifications</label>
                </div>
                <div className="filter-option">
                  <input 
                    type="radio" 
                    id="filter-unread" 
                    name="filter" 
                    value="unread" 
                    checked={filter === 'unread'}
                    onChange={handleFilterChange}
                  />
                  <label htmlFor="filter-unread">Unread only</label>
                </div>
                <div className="filter-option">
                  <input 
                    type="radio" 
                    id="filter-read" 
                    name="filter" 
                    value="read" 
                    checked={filter === 'read'}
                    onChange={handleFilterChange}
                  />
                  <label htmlFor="filter-read">Read only</label>
                </div>
                <div className="filter-divider"></div>
                <div className="filter-option">
                  <input 
                    type="checkbox" 
                    id="type-inventory" 
                    value="inventory_changed" 
                    checked={typeFilters.includes('inventory_changed')}
                    onChange={handleTypeFilterChange}
                  />
                  <label htmlFor="type-inventory">Inventory</label>
                </div>
                <div className="filter-option">
                  <input 
                    type="checkbox" 
                    id="type-task" 
                    value="task_assigned" 
                    checked={typeFilters.includes('task_assigned')}
                    onChange={handleTypeFilterChange}
                  />
                  <label htmlFor="type-task">Tasks</label>
                </div>
                <div className="filter-option">
                  <input 
                    type="checkbox" 
                    id="type-asn" 
                    value="asn_received" 
                    checked={typeFilters.includes('asn_received')}
                    onChange={handleTypeFilterChange}
                  />
                  <label htmlFor="type-asn">ASN</label>
                </div>
                <div className="filter-option">
                  <input 
                    type="checkbox" 
                    id="type-product" 
                    value="product_created" 
                    checked={typeFilters.includes('product_created')}
                    onChange={handleTypeFilterChange}
                  />
                  <label htmlFor="type-product">Products</label>
                </div>
              </div>
            )}
          </div>
        </div>
      </div>
      
      <div className="notifications-list">
        {loading ? (
          <div className="loading-spinner">
            <FontAwesomeIcon icon={faSpinner} spin />
            <span>Loading notifications...</span>
          </div>
        ) : notifications.length === 0 ? (
          <div className="no-notifications">
            <FontAwesomeIcon icon={faBellSlash} />
            <p>No notifications found</p>
          </div>
        ) : (
          <>
            {notifications.map(notification => (
              <div 
                key={notification.id} 
                className={`notification-item ${!notification.is_read ? 'unread' : ''}`}
              >
                <div className={`notification-icon ${notification.type}`}>
                  <FontAwesomeIcon icon={getIcon(notification.type)} />
                </div>
                <div className="notification-content">
                  <div className="notification-message">{notification.message}</div>
                  <div className="notification-time">{formatTime(notification.created_at)}</div>
                </div>
                <div className="notification-actions">
                  {!notification.is_read && (
                    <button 
                      onClick={() => markAsRead(notification.id)} 
                      className="mark-read-btn"
                      title="Mark as read"
                    >
                      <FontAwesomeIcon icon={faCheck} />
                    </button>
                  )}
                </div>
              </div>
            ))}
            
            {hasMorePages && (
              <div className="load-more">
                <button onClick={loadMore} disabled={loadingMore}>
                  {loadingMore ? (
                    <FontAwesomeIcon icon={faSpinner} spin />
                  ) : (
                    <span>Load more</span>
                  )}
                </button>
              </div>
            )}
          </>
        )}
      </div>
    </div>
  );
};

export default NotificationsPage;