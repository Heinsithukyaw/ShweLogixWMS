import React, { useState, useEffect } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { 
  Package, 
  TrendingUp, 
  AlertTriangle, 
  CheckCircle,
  BarChart3,
  Scan,
  Menu,
  Bell,
  User
} from 'lucide-react';
import { BarcodeScanner } from '@/components/BarcodeScanner';

interface DashboardMetric {
  title: string;
  value: string;
  change: string;
  trend: 'up' | 'down' | 'neutral';
  icon: React.ReactNode;
}

interface MobileDashboardProps {
  className?: string;
}

export const MobileDashboard: React.FC<MobileDashboardProps> = ({ className }) => {
  const [isMenuOpen, setIsMenuOpen] = useState(false);
  const [showScanner, setShowScanner] = useState(false);
  const [metrics, setMetrics] = useState<DashboardMetric[]>([]);
  const [alerts, setAlerts] = useState<any[]>([]);

  useEffect(() => {
    // Load dashboard metrics
    loadDashboardMetrics();
    loadAlerts();
  }, []);

  const loadDashboardMetrics = async () => {
    // This would fetch from your API
    const mockMetrics: DashboardMetric[] = [
      {
        title: 'Total Inventory',
        value: '12,543',
        change: '+2.5%',
        trend: 'up',
        icon: <Package className="h-4 w-4" />
      },
      {
        title: 'Orders Today',
        value: '89',
        change: '+12%',
        trend: 'up',
        icon: <TrendingUp className="h-4 w-4" />
      },
      {
        title: 'Pending Tasks',
        value: '23',
        change: '-5%',
        trend: 'down',
        icon: <AlertTriangle className="h-4 w-4" />
      },
      {
        title: 'Completed',
        value: '156',
        change: '+8%',
        trend: 'up',
        icon: <CheckCircle className="h-4 w-4" />
      }
    ];
    setMetrics(mockMetrics);
  };

  const loadAlerts = async () => {
    // Mock alerts data
    const mockAlerts = [
      {
        id: 1,
        type: 'warning',
        message: 'Low stock alert for Product ABC',
        time: '5 min ago'
      },
      {
        id: 2,
        type: 'info',
        message: 'New order received #12345',
        time: '10 min ago'
      }
    ];
    setAlerts(mockAlerts);
  };

  const handleScanResult = (result: string) => {
    console.log('Scanned:', result);
    setShowScanner(false);
    // Handle the scanned result
  };

  const getTrendColor = (trend: string) => {
    switch (trend) {
      case 'up':
        return 'text-green-600';
      case 'down':
        return 'text-red-600';
      default:
        return 'text-gray-600';
    }
  };

  const getAlertColor = (type: string) => {
    switch (type) {
      case 'warning':
        return 'border-l-yellow-500 bg-yellow-50';
      case 'error':
        return 'border-l-red-500 bg-red-50';
      case 'info':
        return 'border-l-blue-500 bg-blue-50';
      default:
        return 'border-l-gray-500 bg-gray-50';
    }
  };

  return (
    <div className={`min-h-screen bg-gray-50 ${className}`}>
      {/* Mobile Header */}
      <div className="bg-white shadow-sm border-b sticky top-0 z-40">
        <div className="flex items-center justify-between p-4">
          <Button
            variant="ghost"
            size="sm"
            onClick={() => setIsMenuOpen(!isMenuOpen)}
          >
            <Menu className="h-5 w-5" />
          </Button>
          
          <h1 className="text-lg font-semibold">WMS Dashboard</h1>
          
          <div className="flex items-center space-x-2">
            <Button variant="ghost" size="sm">
              <Bell className="h-5 w-5" />
              {alerts.length > 0 && (
                <Badge className="ml-1 h-2 w-2 p-0 bg-red-500" />
              )}
            </Button>
            <Button variant="ghost" size="sm">
              <User className="h-5 w-5" />
            </Button>
          </div>
        </div>
      </div>

      {/* Quick Actions Bar */}
      <div className="bg-white border-b p-4">
        <div className="flex space-x-3 overflow-x-auto">
          <Button
            onClick={() => setShowScanner(true)}
            className="flex-shrink-0 bg-blue-600 hover:bg-blue-700"
            size="sm"
          >
            <Scan className="h-4 w-4 mr-2" />
            Scan
          </Button>
          <Button variant="outline" size="sm" className="flex-shrink-0">
            <Package className="h-4 w-4 mr-2" />
            Inventory
          </Button>
          <Button variant="outline" size="sm" className="flex-shrink-0">
            <BarChart3 className="h-4 w-4 mr-2" />
            Reports
          </Button>
        </div>
      </div>

      {/* Main Content */}
      <div className="p-4 space-y-4">
        {/* Metrics Grid */}
        <div className="grid grid-cols-2 gap-4">
          {metrics.map((metric, index) => (
            <Card key={index} className="p-3">
              <CardContent className="p-0">
                <div className="flex items-center justify-between mb-2">
                  <div className="text-blue-600">{metric.icon}</div>
                  <span className={`text-sm font-medium ${getTrendColor(metric.trend)}`}>
                    {metric.change}
                  </span>
                </div>
                <div className="space-y-1">
                  <p className="text-2xl font-bold">{metric.value}</p>
                  <p className="text-sm text-gray-600">{metric.title}</p>
                </div>
              </CardContent>
            </Card>
          ))}
        </div>

        {/* Alerts Section */}
        {alerts.length > 0 && (
          <Card>
            <CardHeader className="pb-3">
              <CardTitle className="text-lg">Recent Alerts</CardTitle>
            </CardHeader>
            <CardContent className="space-y-3">
              {alerts.map((alert) => (
                <div
                  key={alert.id}
                  className={`p-3 border-l-4 rounded-r ${getAlertColor(alert.type)}`}
                >
                  <p className="text-sm font-medium">{alert.message}</p>
                  <p className="text-xs text-gray-500 mt-1">{alert.time}</p>
                </div>
              ))}
            </CardContent>
          </Card>
        )}

        {/* Quick Stats */}
        <Card>
          <CardHeader className="pb-3">
            <CardTitle className="text-lg">Today's Overview</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="space-y-3">
              <div className="flex justify-between items-center">
                <span className="text-sm text-gray-600">Orders Processed</span>
                <span className="font-semibold">156</span>
              </div>
              <div className="flex justify-between items-center">
                <span className="text-sm text-gray-600">Items Picked</span>
                <span className="font-semibold">1,234</span>
              </div>
              <div className="flex justify-between items-center">
                <span className="text-sm text-gray-600">Shipments Out</span>
                <span className="font-semibold">89</span>
              </div>
              <div className="flex justify-between items-center">
                <span className="text-sm text-gray-600">Efficiency Rate</span>
                <span className="font-semibold text-green-600">94.5%</span>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>

      {/* Barcode Scanner Modal */}
      {showScanner && (
        <div className="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
          <div className="bg-white rounded-lg w-full max-w-md">
            <div className="p-4 border-b">
              <div className="flex items-center justify-between">
                <h3 className="text-lg font-semibold">Scan Barcode</h3>
                <Button
                  variant="ghost"
                  size="sm"
                  onClick={() => setShowScanner(false)}
                >
                  ×
                </Button>
              </div>
            </div>
            <div className="p-4">
              <BarcodeScanner
                onScan={handleScanResult}
                onError={(error) => console.error('Scanner error:', error)}
              />
            </div>
          </div>
        </div>
      )}

      {/* Mobile Menu Overlay */}
      {isMenuOpen && (
        <div className="fixed inset-0 bg-black bg-opacity-50 z-50">
          <div className="bg-white w-64 h-full shadow-lg">
            <div className="p-4 border-b">
              <div className="flex items-center justify-between">
                <h2 className="text-lg font-semibold">Menu</h2>
                <Button
                  variant="ghost"
                  size="sm"
                  onClick={() => setIsMenuOpen(false)}
                >
                  ×
                </Button>
              </div>
            </div>
            <nav className="p-4 space-y-2">
              <Button variant="ghost" className="w-full justify-start">
                <Package className="h-4 w-4 mr-3" />
                Inventory
              </Button>
              <Button variant="ghost" className="w-full justify-start">
                <TrendingUp className="h-4 w-4 mr-3" />
                Orders
              </Button>
              <Button variant="ghost" className="w-full justify-start">
                <BarChart3 className="h-4 w-4 mr-3" />
                Analytics
              </Button>
              <Button variant="ghost" className="w-full justify-start">
                <User className="h-4 w-4 mr-3" />
                Profile
              </Button>
            </nav>
          </div>
        </div>
      )}
    </div>
  );
};