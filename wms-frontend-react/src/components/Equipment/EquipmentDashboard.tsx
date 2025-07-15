import React, { useState, useEffect } from 'react';
import {
  Card,
  CardContent,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import {
  BarChart,
  Bar,
  XAxis,
  YAxis,
  CartesianGrid,
  Tooltip,
  ResponsiveContainer,
  PieChart,
  Pie,
  Cell,
  LineChart,
  Line
} from 'recharts';
import {
  Search,
  Plus,
  Eye,
  Settings,
  AlertTriangle,
  CheckCircle,
  Clock,
  Wrench,
  Activity,
  DollarSign,
  TrendingUp,
  MapPin,
  User,
  Calendar,
  Zap
} from 'lucide-react';

interface Equipment {
  id: number;
  equipment_code: string;
  name: string;
  category: {
    id: number;
    category_name: string;
  };
  warehouse: {
    id: number;
    name: string;
  };
  current_location?: {
    id: number;
    location_code: string;
  };
  assigned_operator?: {
    id: number;
    name: string;
  };
  status: string;
  condition: string;
  manufacturer?: string;
  model?: string;
  purchase_date?: string;
  current_value: number;
  next_maintenance_due?: string;
}

interface EquipmentAnalytics {
  total_equipment: number;
  by_status: Record<string, number>;
  by_condition: Record<string, number>;
  total_value: number;
  average_age: number;
  maintenance_due: {
    overdue: number;
    due_today: number;
    due_this_week: number;
  };
  active_alerts: {
    total: number;
    critical: number;
    warning: number;
  };
  utilization_summary: {
    average_utilization: number;
    average_efficiency: number;
    average_availability: number;
  };
}

interface EquipmentAlert {
  id: number;
  equipment: {
    equipment_code: string;
    name: string;
  };
  alert_type: string;
  severity: string;
  title: string;
  message: string;
  triggered_at: string;
  status: string;
}

const EquipmentDashboard: React.FC = () => {
  const [equipment, setEquipment] = useState<Equipment[]>([]);
  const [alerts, setAlerts] = useState<EquipmentAlert[]>([]);
  const [analytics, setAnalytics] = useState<EquipmentAnalytics | null>(null);
  const [loading, setLoading] = useState(true);
  const [searchTerm, setSearchTerm] = useState('');
  const [statusFilter, setStatusFilter] = useState('all');
  const [warehouseFilter, setWarehouseFilter] = useState('all');
  const [categoryFilter, setCategoryFilter] = useState('all');
  const [selectedEquipment, setSelectedEquipment] = useState<Equipment | null>(null);
  const [showCreateDialog, setShowCreateDialog] = useState(false);

  useEffect(() => {
    fetchEquipment();
    fetchAlerts();
    fetchAnalytics();
  }, [statusFilter, warehouseFilter, categoryFilter]);

  const fetchEquipment = async () => {
    try {
      const params = new URLSearchParams();
      if (statusFilter !== 'all') params.append('status', statusFilter);
      if (warehouseFilter !== 'all') params.append('warehouse_id', warehouseFilter);
      if (categoryFilter !== 'all') params.append('category_id', categoryFilter);
      if (searchTerm) params.append('search', searchTerm);

      const response = await fetch(`/api/equipment/registry?${params}`);
      const data = await response.json();
      
      if (data.success) {
        setEquipment(data.data.data);
      }
    } catch (error) {
      console.error('Error fetching equipment:', error);
    } finally {
      setLoading(false);
    }
  };

  const fetchAlerts = async () => {
    try {
      const response = await fetch('/api/equipment/alerts/active');
      const data = await response.json();
      
      if (data.success) {
        setAlerts(data.data.data);
      }
    } catch (error) {
      console.error('Error fetching alerts:', error);
    }
  };

  const fetchAnalytics = async () => {
    try {
      const params = new URLSearchParams();
      if (warehouseFilter !== 'all') params.append('warehouse_id', warehouseFilter);
      if (categoryFilter !== 'all') params.append('category_id', categoryFilter);

      const response = await fetch(`/api/equipment/analytics/dashboard?${params}`);
      const data = await response.json();
      
      if (data.success) {
        setAnalytics(data.data);
      }
    } catch (error) {
      console.error('Error fetching analytics:', error);
    }
  };

  const getStatusBadge = (status: string) => {
    const statusConfig = {
      active: { color: 'bg-green-100 text-green-800', label: 'Active', icon: CheckCircle },
      maintenance: { color: 'bg-yellow-100 text-yellow-800', label: 'Maintenance', icon: Wrench },
      repair: { color: 'bg-red-100 text-red-800', label: 'Repair', icon: AlertTriangle },
      retired: { color: 'bg-gray-100 text-gray-800', label: 'Retired', icon: Clock },
      disposed: { color: 'bg-black text-white', label: 'Disposed', icon: Clock },
      lost: { color: 'bg-red-100 text-red-800', label: 'Lost', icon: AlertTriangle }
    };

    const config = statusConfig[status as keyof typeof statusConfig] || statusConfig.active;
    const IconComponent = config.icon;
    
    return (
      <Badge className={config.color}>
        <IconComponent className="h-3 w-3 mr-1" />
        {config.label}
      </Badge>
    );
  };

  const getConditionBadge = (condition: string) => {
    const conditionConfig = {
      excellent: { color: 'bg-green-100 text-green-800', label: 'Excellent' },
      good: { color: 'bg-blue-100 text-blue-800', label: 'Good' },
      fair: { color: 'bg-yellow-100 text-yellow-800', label: 'Fair' },
      poor: { color: 'bg-orange-100 text-orange-800', label: 'Poor' },
      critical: { color: 'bg-red-100 text-red-800', label: 'Critical' }
    };

    const config = conditionConfig[condition as keyof typeof conditionConfig] || conditionConfig.good;
    return <Badge className={config.color}>{config.label}</Badge>;
  };

  const getAlertSeverityBadge = (severity: string) => {
    const severityConfig = {
      info: { color: 'bg-blue-100 text-blue-800', label: 'Info' },
      warning: { color: 'bg-yellow-100 text-yellow-800', label: 'Warning' },
      critical: { color: 'bg-red-100 text-red-800', label: 'Critical' },
      emergency: { color: 'bg-red-600 text-white', label: 'Emergency' }
    };

    const config = severityConfig[severity as keyof typeof severityConfig] || severityConfig.info;
    return <Badge className={config.color}>{config.label}</Badge>;
  };

  const handleAcknowledgeAlert = async (alertId: number) => {
    try {
      const response = await fetch(`/api/equipment/alerts/${alertId}/acknowledge`, {
        method: 'POST'
      });

      if (response.ok) {
        fetchAlerts();
      }
    } catch (error) {
      console.error('Error acknowledging alert:', error);
    }
  };

  const COLORS = ['#0088FE', '#00C49F', '#FFBB28', '#FF8042', '#8884D8'];

  if (loading) {
    return (
      <div className="flex items-center justify-center h-64">
        <div className="animate-spin rounded-full h-32 w-32 border-b-2 border-blue-600"></div>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex justify-between items-center">
        <div>
          <h1 className="text-3xl font-bold text-gray-900">Equipment Management</h1>
          <p className="text-gray-600">Monitor and manage warehouse equipment lifecycle</p>
        </div>
        <Button onClick={() => setShowCreateDialog(true)} className="flex items-center gap-2">
          <Plus className="h-4 w-4" />
          Register Equipment
        </Button>
      </div>

      {/* Analytics Cards */}
      {analytics && (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
          <Card>
            <CardContent className="p-6">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-sm font-medium text-gray-600">Total Equipment</p>
                  <p className="text-2xl font-bold text-gray-900">{analytics.total_equipment}</p>
                </div>
                <Settings className="h-8 w-8 text-blue-600" />
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardContent className="p-6">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-sm font-medium text-gray-600">Total Value</p>
                  <p className="text-2xl font-bold text-gray-900">
                    ${analytics.total_value.toLocaleString()}
                  </p>
                </div>
                <DollarSign className="h-8 w-8 text-green-600" />
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardContent className="p-6">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-sm font-medium text-gray-600">Avg Utilization</p>
                  <p className="text-2xl font-bold text-gray-900">
                    {analytics.utilization_summary.average_utilization.toFixed(1)}%
                  </p>
                </div>
                <Activity className="h-8 w-8 text-orange-600" />
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardContent className="p-6">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-sm font-medium text-gray-600">Active Alerts</p>
                  <p className="text-2xl font-bold text-gray-900">{analytics.active_alerts.total}</p>
                  <p className="text-sm text-red-600">{analytics.active_alerts.critical} critical</p>
                </div>
                <AlertTriangle className="h-8 w-8 text-red-600" />
              </div>
            </CardContent>
          </Card>
        </div>
      )}

      {/* Maintenance Due Alert */}
      {analytics && analytics.maintenance_due.overdue > 0 && (
        <Card className="border-red-200 bg-red-50">
          <CardContent className="p-4">
            <div className="flex items-center gap-3">
              <AlertTriangle className="h-5 w-5 text-red-600" />
              <div>
                <p className="font-medium text-red-800">Maintenance Overdue</p>
                <p className="text-sm text-red-600">
                  {analytics.maintenance_due.overdue} equipment items have overdue maintenance
                </p>
              </div>
            </div>
          </CardContent>
        </Card>
      )}

      <Tabs defaultValue="equipment" className="space-y-6">
        <TabsList>
          <TabsTrigger value="equipment">Equipment Registry</TabsTrigger>
          <TabsTrigger value="alerts">Active Alerts</TabsTrigger>
          <TabsTrigger value="analytics">Analytics</TabsTrigger>
        </TabsList>

        <TabsContent value="equipment" className="space-y-6">
          {/* Filters */}
          <Card>
            <CardContent className="p-6">
              <div className="flex flex-col md:flex-row gap-4">
                <div className="flex-1">
                  <div className="relative">
                    <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 h-4 w-4" />
                    <Input
                      placeholder="Search by code, name, manufacturer..."
                      value={searchTerm}
                      onChange={(e) => setSearchTerm(e.target.value)}
                      className="pl-10"
                    />
                  </div>
                </div>
                <Select value={statusFilter} onValueChange={setStatusFilter}>
                  <SelectTrigger className="w-48">
                    <SelectValue placeholder="Filter by status" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="all">All Statuses</SelectItem>
                    <SelectItem value="active">Active</SelectItem>
                    <SelectItem value="maintenance">Maintenance</SelectItem>
                    <SelectItem value="repair">Repair</SelectItem>
                    <SelectItem value="retired">Retired</SelectItem>
                  </SelectContent>
                </Select>
                <Select value={warehouseFilter} onValueChange={setWarehouseFilter}>
                  <SelectTrigger className="w-48">
                    <SelectValue placeholder="Filter by warehouse" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="all">All Warehouses</SelectItem>
                    <SelectItem value="1">Main Warehouse</SelectItem>
                    <SelectItem value="2">Distribution Center</SelectItem>
                  </SelectContent>
                </Select>
                <Button variant="outline" onClick={fetchEquipment}>
                  Apply Filters
                </Button>
              </div>
            </CardContent>
          </Card>

          {/* Equipment Table */}
          <Card>
            <CardHeader>
              <CardTitle>Equipment Registry</CardTitle>
            </CardHeader>
            <CardContent>
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Equipment Code</TableHead>
                    <TableHead>Name</TableHead>
                    <TableHead>Category</TableHead>
                    <TableHead>Status</TableHead>
                    <TableHead>Condition</TableHead>
                    <TableHead>Location</TableHead>
                    <TableHead>Operator</TableHead>
                    <TableHead>Value</TableHead>
                    <TableHead>Next Maintenance</TableHead>
                    <TableHead>Actions</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {equipment.map((item) => (
                    <TableRow key={item.id}>
                      <TableCell className="font-medium">
                        {item.equipment_code}
                      </TableCell>
                      <TableCell>
                        <div>
                          <p className="font-medium">{item.name}</p>
                          {item.manufacturer && item.model && (
                            <p className="text-sm text-gray-500">
                              {item.manufacturer} {item.model}
                            </p>
                          )}
                        </div>
                      </TableCell>
                      <TableCell>{item.category.category_name}</TableCell>
                      <TableCell>{getStatusBadge(item.status)}</TableCell>
                      <TableCell>{getConditionBadge(item.condition)}</TableCell>
                      <TableCell>
                        <div className="flex items-center gap-1">
                          <MapPin className="h-4 w-4 text-gray-400" />
                          {item.current_location?.location_code || 'Unassigned'}
                        </div>
                      </TableCell>
                      <TableCell>
                        <div className="flex items-center gap-1">
                          <User className="h-4 w-4 text-gray-400" />
                          {item.assigned_operator?.name || 'Unassigned'}
                        </div>
                      </TableCell>
                      <TableCell>${item.current_value.toLocaleString()}</TableCell>
                      <TableCell>
                        {item.next_maintenance_due ? (
                          <div className="flex items-center gap-1">
                            <Calendar className="h-4 w-4 text-gray-400" />
                            {new Date(item.next_maintenance_due).toLocaleDateString()}
                          </div>
                        ) : '-'}
                      </TableCell>
                      <TableCell>
                        <div className="flex items-center gap-2">
                          <Button
                            variant="outline"
                            size="sm"
                            onClick={() => setSelectedEquipment(item)}
                          >
                            <Eye className="h-4 w-4" />
                          </Button>
                          <Button
                            variant="outline"
                            size="sm"
                            className="text-blue-600 hover:text-blue-700"
                          >
                            <Settings className="h-4 w-4" />
                          </Button>
                        </div>
                      </TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
            </CardContent>
          </Card>
        </TabsContent>

        <TabsContent value="alerts" className="space-y-6">
          {/* Active Alerts */}
          <Card>
            <CardHeader>
              <CardTitle>Active Equipment Alerts</CardTitle>
            </CardHeader>
            <CardContent>
              <div className="space-y-4">
                {alerts.map((alert) => (
                  <div key={alert.id} className="flex items-start justify-between p-4 border rounded-lg">
                    <div className="flex-1">
                      <div className="flex items-center gap-2 mb-2">
                        <AlertTriangle className="h-4 w-4 text-red-500" />
                        <span className="font-medium">{alert.title}</span>
                        {getAlertSeverityBadge(alert.severity)}
                      </div>
                      <p className="text-sm text-gray-600 mb-2">{alert.message}</p>
                      <div className="flex items-center gap-4 text-xs text-gray-500">
                        <span>Equipment: {alert.equipment.equipment_code}</span>
                        <span>Triggered: {new Date(alert.triggered_at).toLocaleString()}</span>
                      </div>
                    </div>
                    <div className="flex items-center gap-2">
                      <Button
                        variant="outline"
                        size="sm"
                        onClick={() => handleAcknowledgeAlert(alert.id)}
                      >
                        Acknowledge
                      </Button>
                    </div>
                  </div>
                ))}
                {alerts.length === 0 && (
                  <div className="text-center py-8 text-gray-500">
                    <CheckCircle className="h-12 w-12 mx-auto mb-4 text-green-500" />
                    <p>No active alerts</p>
                  </div>
                )}
              </div>
            </CardContent>
          </Card>
        </TabsContent>

        <TabsContent value="analytics" className="space-y-6">
          {analytics && (
            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
              {/* Status Distribution */}
              <Card>
                <CardHeader>
                  <CardTitle>Equipment by Status</CardTitle>
                </CardHeader>
                <CardContent>
                  <ResponsiveContainer width="100%" height={300}>
                    <PieChart>
                      <Pie
                        data={Object.entries(analytics.by_status).map(([status, count]) => ({
                          name: status,
                          value: count
                        }))}
                        cx="50%"
                        cy="50%"
                        labelLine={false}
                        label={({ name, percent }) => `${name} ${(percent * 100).toFixed(0)}%`}
                        outerRadius={80}
                        fill="#8884d8"
                        dataKey="value"
                      >
                        {Object.entries(analytics.by_status).map((entry, index) => (
                          <Cell key={`cell-${index}`} fill={COLORS[index % COLORS.length]} />
                        ))}
                      </Pie>
                      <Tooltip />
                    </PieChart>
                  </ResponsiveContainer>
                </CardContent>
              </Card>

              {/* Condition Distribution */}
              <Card>
                <CardHeader>
                  <CardTitle>Equipment by Condition</CardTitle>
                </CardHeader>
                <CardContent>
                  <ResponsiveContainer width="100%" height={300}>
                    <BarChart data={Object.entries(analytics.by_condition).map(([condition, count]) => ({
                      condition,
                      count
                    }))}>
                      <CartesianGrid strokeDasharray="3 3" />
                      <XAxis dataKey="condition" />
                      <YAxis />
                      <Tooltip />
                      <Bar dataKey="count" fill="#8884d8" />
                    </BarChart>
                  </ResponsiveContainer>
                </CardContent>
              </Card>

              {/* Utilization Metrics */}
              <Card>
                <CardHeader>
                  <CardTitle>Performance Metrics</CardTitle>
                </CardHeader>
                <CardContent>
                  <div className="space-y-4">
                    <div className="flex justify-between items-center">
                      <span className="text-sm font-medium">Utilization Rate</span>
                      <span className="text-sm font-bold">
                        {analytics.utilization_summary.average_utilization.toFixed(1)}%
                      </span>
                    </div>
                    <div className="w-full bg-gray-200 rounded-full h-2">
                      <div 
                        className="bg-blue-600 h-2 rounded-full" 
                        style={{ width: `${analytics.utilization_summary.average_utilization}%` }}
                      ></div>
                    </div>
                    
                    <div className="flex justify-between items-center">
                      <span className="text-sm font-medium">Efficiency Rate</span>
                      <span className="text-sm font-bold">
                        {analytics.utilization_summary.average_efficiency.toFixed(1)}%
                      </span>
                    </div>
                    <div className="w-full bg-gray-200 rounded-full h-2">
                      <div 
                        className="bg-green-600 h-2 rounded-full" 
                        style={{ width: `${analytics.utilization_summary.average_efficiency}%` }}
                      ></div>
                    </div>
                    
                    <div className="flex justify-between items-center">
                      <span className="text-sm font-medium">Availability Rate</span>
                      <span className="text-sm font-bold">
                        {analytics.utilization_summary.average_availability.toFixed(1)}%
                      </span>
                    </div>
                    <div className="w-full bg-gray-200 rounded-full h-2">
                      <div 
                        className="bg-orange-600 h-2 rounded-full" 
                        style={{ width: `${analytics.utilization_summary.average_availability}%` }}
                      ></div>
                    </div>
                  </div>
                </CardContent>
              </Card>

              {/* Maintenance Summary */}
              <Card>
                <CardHeader>
                  <CardTitle>Maintenance Summary</CardTitle>
                </CardHeader>
                <CardContent>
                  <div className="space-y-4">
                    <div className="flex justify-between items-center p-3 bg-red-50 rounded-lg">
                      <div className="flex items-center gap-2">
                        <AlertTriangle className="h-4 w-4 text-red-600" />
                        <span className="text-sm font-medium">Overdue</span>
                      </div>
                      <span className="text-lg font-bold text-red-600">
                        {analytics.maintenance_due.overdue}
                      </span>
                    </div>
                    
                    <div className="flex justify-between items-center p-3 bg-yellow-50 rounded-lg">
                      <div className="flex items-center gap-2">
                        <Clock className="h-4 w-4 text-yellow-600" />
                        <span className="text-sm font-medium">Due Today</span>
                      </div>
                      <span className="text-lg font-bold text-yellow-600">
                        {analytics.maintenance_due.due_today}
                      </span>
                    </div>
                    
                    <div className="flex justify-between items-center p-3 bg-blue-50 rounded-lg">
                      <div className="flex items-center gap-2">
                        <Calendar className="h-4 w-4 text-blue-600" />
                        <span className="text-sm font-medium">Due This Week</span>
                      </div>
                      <span className="text-lg font-bold text-blue-600">
                        {analytics.maintenance_due.due_this_week}
                      </span>
                    </div>
                  </div>
                </CardContent>
              </Card>
            </div>
          )}
        </TabsContent>
      </Tabs>

      {/* Equipment Details Dialog */}
      {selectedEquipment && (
        <Dialog open={!!selectedEquipment} onOpenChange={() => setSelectedEquipment(null)}>
          <DialogContent className="max-w-4xl">
            <DialogHeader>
              <DialogTitle>Equipment Details - {selectedEquipment.equipment_code}</DialogTitle>
            </DialogHeader>
            <div className="space-y-4">
              <div className="grid grid-cols-2 gap-4">
                <div>
                  <label className="text-sm font-medium text-gray-600">Name</label>
                  <p className="text-sm text-gray-900">{selectedEquipment.name}</p>
                </div>
                <div>
                  <label className="text-sm font-medium text-gray-600">Category</label>
                  <p className="text-sm text-gray-900">{selectedEquipment.category.category_name}</p>
                </div>
                <div>
                  <label className="text-sm font-medium text-gray-600">Status</label>
                  <div className="mt-1">{getStatusBadge(selectedEquipment.status)}</div>
                </div>
                <div>
                  <label className="text-sm font-medium text-gray-600">Condition</label>
                  <div className="mt-1">{getConditionBadge(selectedEquipment.condition)}</div>
                </div>
                <div>
                  <label className="text-sm font-medium text-gray-600">Manufacturer</label>
                  <p className="text-sm text-gray-900">{selectedEquipment.manufacturer || 'N/A'}</p>
                </div>
                <div>
                  <label className="text-sm font-medium text-gray-600">Model</label>
                  <p className="text-sm text-gray-900">{selectedEquipment.model || 'N/A'}</p>
                </div>
                <div>
                  <label className="text-sm font-medium text-gray-600">Current Value</label>
                  <p className="text-sm text-gray-900">${selectedEquipment.current_value.toLocaleString()}</p>
                </div>
                <div>
                  <label className="text-sm font-medium text-gray-600">Purchase Date</label>
                  <p className="text-sm text-gray-900">
                    {selectedEquipment.purchase_date 
                      ? new Date(selectedEquipment.purchase_date).toLocaleDateString()
                      : 'N/A'
                    }
                  </p>
                </div>
              </div>
            </div>
          </DialogContent>
        </Dialog>
      )}
    </div>
  );
};

export default EquipmentDashboard;