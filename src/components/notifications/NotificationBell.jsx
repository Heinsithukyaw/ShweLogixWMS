import React, { useState, useEffect, useRef } from 'react';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faBell, faCheck, faExclamationCircle, faExclamationTriangle, faCheckCircle, faInfoCircle, faTasks, faTruckLoading, faBoxes, faBox } from '@fortawesome/free-solid-svg-icons';
import { useNavigate } from 'react-router-dom';
import notificationService from './NotificationService';
import './NotificationBell.css';

const NotificationBell = () => {
  const [isOpen, setIsOpen] = useState(false);
  const [notifications, setNotifications] = useState([]);
  const [unreadCount, setUnreadCount] = useState(0);
  const dropdownRef = useRef(null);
  const navigate = useNavigate();

  useEffect(() => {
    // Initialize notification service
    notificationService.init();
    
    // Load initial notifications
    loadNotifications();
    
    // Add listener for notification changes
    const removeListener = notificationService.addListener(({ notifications: newNotifications, unreadCount: newCount }) => {
      setNotifications(newNotifications.slice(0, 5)); // Show only the 5 most recent
      setUnreadCount(newCount);
    });
    
    // Add click event listener to close dropdown when clicking outside
    const handleClickOutside = (event) => {
      if (dropdownRef.current && !dropdownRef.current.contains(event.target)) {
        setIsOpen(false);
      }
    };
    
    document.addEventListener('mousedown', handleClickOutside);
    
    // Cleanup
    return () => {
      removeListener();
      document.removeEventListener('mousedown', handleClickOutside);
    };
  }, []);

  const loadNotifications = async () => {
    await notificationService.fetchNotifications({ per_page: 5 });
    await notificationService.fetchUnreadCount();
  };

  const toggleDropdown = (e) => {
    e.stopPropagation();
    setIsOpen(!isOpen);
    
    // Reload notifications when opening dropdown
    if (!isOpen) {
      loadNotifications();
    }
  };

  const markAsRead = async (id) => {
    await notificationService.markAsRead(id);
  };

  const markAllAsRead = async () => {
    await notificationService.markAllAsRead();
  };

  const viewAllNotifications = () => {
    navigate('/notifications');
    setIsOpen(false);
  };

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
      return date.toLocaleDateString();
    }
  };

  // Format the unread count for display
  const displayCount = unreadCount > 99 ? '99+' : unreadCount;

  return (
    <div className="notification-bell" ref={dropdownRef}>
      <button onClick={toggleDropdown} className="notification-button">
        <FontAwesomeIcon icon={faBell} />
        {unreadCount > 0 && <span className="notification-badge">{displayCount}</span>}
      </button>
      
      {isOpen && (
        <div className="notification-dropdown">
          <div className="notification-header">
            <h3>Notifications</h3>
            {unreadCount > 0 && (
              <button onClick={markAllAsRead} className="mark-all-read">
                Mark all as read
              </button>
            )}
          </div>
          
          <div className="notification-list">
            {notifications.length === 0 ? (
              <div className="no-notifications">
                No notifications
              </div>
            ) : (
              notifications.map(notification => (
                <div 
                  key={notification.id} 
                  className={`notification-item ${!notification.is_read ? 'unread' : ''}`}
                  onClick={() => markAsRead(notification.id)}
                >
                  <div className={`notification-icon ${notification.type}`}>
                    <FontAwesomeIcon icon={getIcon(notification.type)} />
                  </div>
                  <div className="notification-content">
                    <div className="notification-message">{notification.message}</div>
                    <div className="notification-time">{formatTime(notification.created_at)}</div>
                  </div>
                </div>
              ))
            )}
          </div>
          
          <div className="notification-footer">
            <button onClick={viewAllNotifications} className="view-all">
              View all notifications
            </button>
          </div>
        </div>
      )}
    </div>
  );
};

export default NotificationBell;