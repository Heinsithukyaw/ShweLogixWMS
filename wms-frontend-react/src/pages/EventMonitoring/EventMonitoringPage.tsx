import React from 'react';
import EventMonitoringDashboard from '../../components/dashboard/EventMonitoringDashboard';
import './EventMonitoringPage.css';

const EventMonitoringPage: React.FC = () => {
  return (
    <div className="event-monitoring-page">
      <div className="page-header">
        <h1>Event Monitoring</h1>
        <p>Monitor system events, performance metrics, and event processing statistics</p>
      </div>
      
      <div className="page-content">
        <EventMonitoringDashboard />
      </div>
    </div>
  );
};

export default EventMonitoringPage;