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
  Filter,
  Plus,
  Eye,
  CheckCircle,
  XCircle,
  Clock,
  TrendingUp,
  Package,
  DollarSign,
  Users,
  AlertTriangle
} from 'lucide-react';

interface ReturnAuthorization {
  id: number;
  rma_number: string;
  customer: {
    id: number;
    name: string;
  };
  return_type: string;
  status: string;
  requested_date: string;
  estimated_value: number;
  total_items: number;
  warehouse: {
    id: number;
    name: string;
  };
}

interface ReturnAnalytics {
  total_returns: number;
  by_status: Record<string, number>;
  by_type: Record<string, number>;
  total_value: number;
  total_refunds: number;
  average_processing_time: number;
  monthly_trend: Array<{
    month: string;
    count: number;
    value: number;
  }>;
}

const ReturnAuthorizationDashboard: React.FC = () => {
  const [returns, setReturns] = useState<ReturnAuthorization[]>([]);
  const [analytics, setAnalytics] = useState<ReturnAnalytics | null>(null);
  const [loading, setLoading] = useState(true);
  const [searchTerm, setSearchTerm] = useState('');
  const [statusFilter, setStatusFilter] = useState('all');
  const [warehouseFilter, setWarehouseFilter] = useState('all');
  const [selectedReturn, setSelectedReturn] = useState<ReturnAuthorization | null>(null);
  const [showCreateDialog, setShowCreateDialog] = useState(false);

  useEffect(() => {
    fetchReturns();
    fetchAnalytics();
  }, [statusFilter, warehouseFilter]);

  const fetchReturns = async () => {
    try {
      const params = new URLSearchParams();
      if (statusFilter !== 'all') params.append('status', statusFilter);
      if (warehouseFilter !== 'all') params.append('warehouse_id', warehouseFilter);
      if (searchTerm) params.append('search', searchTerm);

      const response = await fetch(`/api/returns/authorizations?${params}`);
      const data = await response.json();
      
      if (data.success) {
        setReturns(data.data.data);
      }
    } catch (error) {
      console.error('Error fetching returns:', error);
    } finally {
      setLoading(false);
    }
  };

  const fetchAnalytics = async () => {
    try {
      const params = new URLSearchParams();
      if (warehouseFilter !== 'all') params.append('warehouse_id', warehouseFilter);

      const response = await fetch(`/api/returns/authorizations/analytics/summary?${params}`);
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
      pending: { color: 'bg-yellow-100 text-yellow-800', label: 'Pending' },
      approved: { color: 'bg-blue-100 text-blue-800', label: 'Approved' },
      rejected: { color: 'bg-red-100 text-red-800', label: 'Rejected' },
      in_transit: { color: 'bg-purple-100 text-purple-800', label: 'In Transit' },
      received: { color: 'bg-green-100 text-green-800', label: 'Received' },
      processed: { color: 'bg-indigo-100 text-indigo-800', label: 'Processed' },
      completed: { color: 'bg-green-100 text-green-800', label: 'Completed' },
      cancelled: { color: 'bg-gray-100 text-gray-800', label: 'Cancelled' }
    };

    const config = statusConfig[status as keyof typeof statusConfig] || statusConfig.pending;
    return <Badge className={config.color}>{config.label}</Badge>;
  };

  const getReturnTypeLabel = (type: string) => {
    const typeLabels = {
      defective: 'Defective',
      damaged: 'Damaged',
      wrong_item: 'Wrong Item',
      customer_change: 'Customer Change',
      warranty: 'Warranty',
      recall: 'Recall'
    };
    return typeLabels[type as keyof typeof typeLabels] || type;
  };

  const handleApprove = async (returnId: number) => {
    try {
      const response = await fetch(`/api/returns/authorizations/${returnId}/approve`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          items: [], // This would be populated with approved quantities
          internal_notes: 'Approved via dashboard'
        })
      });

      if (response.ok) {
        fetchReturns();
      }
    } catch (error) {
      console.error('Error approving return:', error);
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
          <h1 className="text-3xl font-bold text-gray-900">Returns & Reverse Logistics</h1>
          <p className="text-gray-600">Manage return authorizations and reverse logistics operations</p>
        </div>
        <Button onClick={() => setShowCreateDialog(true)} className="flex items-center gap-2">
          <Plus className="h-4 w-4" />
          Create RMA
        </Button>
      </div>

      {/* Analytics Cards */}
      {analytics && (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
          <Card>
            <CardContent className="p-6">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-sm font-medium text-gray-600">Total Returns</p>
                  <p className="text-2xl font-bold text-gray-900">{analytics.total_returns}</p>
                </div>
                <Package className="h-8 w-8 text-blue-600" />
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
                  <p className="text-sm font-medium text-gray-600">Avg Processing Time</p>
                  <p className="text-2xl font-bold text-gray-900">
                    {analytics.average_processing_time} days
                  </p>
                </div>
                <Clock className="h-8 w-8 text-orange-600" />
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardContent className="p-6">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-sm font-medium text-gray-600">Total Refunds</p>
                  <p className="text-2xl font-bold text-gray-900">
                    ${analytics.total_refunds.toLocaleString()}
                  </p>
                </div>
                <TrendingUp className="h-8 w-8 text-red-600" />
              </div>
            </CardContent>
          </Card>
        </div>
      )}

      <Tabs defaultValue="list" className="space-y-6">
        <TabsList>
          <TabsTrigger value="list">Return List</TabsTrigger>
          <TabsTrigger value="analytics">Analytics</TabsTrigger>
        </TabsList>

        <TabsContent value="list" className="space-y-6">
          {/* Filters */}
          <Card>
            <CardContent className="p-6">
              <div className="flex flex-col md:flex-row gap-4">
                <div className="flex-1">
                  <div className="relative">
                    <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 h-4 w-4" />
                    <Input
                      placeholder="Search by RMA number, customer..."
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
                    <SelectItem value="pending">Pending</SelectItem>
                    <SelectItem value="approved">Approved</SelectItem>
                    <SelectItem value="received">Received</SelectItem>
                    <SelectItem value="completed">Completed</SelectItem>
                  </SelectContent>
                </Select>
                <Button variant="outline" onClick={fetchReturns}>
                  <Filter className="h-4 w-4 mr-2" />
                  Apply Filters
                </Button>
              </div>
            </CardContent>
          </Card>

          {/* Returns Table */}
          <Card>
            <CardHeader>
              <CardTitle>Return Authorizations</CardTitle>
            </CardHeader>
            <CardContent>
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>RMA Number</TableHead>
                    <TableHead>Customer</TableHead>
                    <TableHead>Type</TableHead>
                    <TableHead>Status</TableHead>
                    <TableHead>Requested Date</TableHead>
                    <TableHead>Items</TableHead>
                    <TableHead>Value</TableHead>
                    <TableHead>Warehouse</TableHead>
                    <TableHead>Actions</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {returns.map((returnAuth) => (
                    <TableRow key={returnAuth.id}>
                      <TableCell className="font-medium">
                        {returnAuth.rma_number}
                      </TableCell>
                      <TableCell>{returnAuth.customer.name}</TableCell>
                      <TableCell>{getReturnTypeLabel(returnAuth.return_type)}</TableCell>
                      <TableCell>{getStatusBadge(returnAuth.status)}</TableCell>
                      <TableCell>
                        {new Date(returnAuth.requested_date).toLocaleDateString()}
                      </TableCell>
                      <TableCell>{returnAuth.total_items}</TableCell>
                      <TableCell>${returnAuth.estimated_value.toLocaleString()}</TableCell>
                      <TableCell>{returnAuth.warehouse.name}</TableCell>
                      <TableCell>
                        <div className="flex items-center gap-2">
                          <Button
                            variant="outline"
                            size="sm"
                            onClick={() => setSelectedReturn(returnAuth)}
                          >
                            <Eye className="h-4 w-4" />
                          </Button>
                          {returnAuth.status === 'pending' && (
                            <Button
                              variant="outline"
                              size="sm"
                              onClick={() => handleApprove(returnAuth.id)}
                              className="text-green-600 hover:text-green-700"
                            >
                              <CheckCircle className="h-4 w-4" />
                            </Button>
                          )}
                        </div>
                      </TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
            </CardContent>
          </Card>
        </TabsContent>

        <TabsContent value="analytics" className="space-y-6">
          {analytics && (
            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
              {/* Status Distribution */}
              <Card>
                <CardHeader>
                  <CardTitle>Returns by Status</CardTitle>
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

              {/* Return Types */}
              <Card>
                <CardHeader>
                  <CardTitle>Returns by Type</CardTitle>
                </CardHeader>
                <CardContent>
                  <ResponsiveContainer width="100%" height={300}>
                    <BarChart data={Object.entries(analytics.by_type).map(([type, count]) => ({
                      type: getReturnTypeLabel(type),
                      count
                    }))}>
                      <CartesianGrid strokeDasharray="3 3" />
                      <XAxis dataKey="type" />
                      <YAxis />
                      <Tooltip />
                      <Bar dataKey="count" fill="#8884d8" />
                    </BarChart>
                  </ResponsiveContainer>
                </CardContent>
              </Card>

              {/* Monthly Trend */}
              <Card className="lg:col-span-2">
                <CardHeader>
                  <CardTitle>Monthly Return Trend</CardTitle>
                </CardHeader>
                <CardContent>
                  <ResponsiveContainer width="100%" height={300}>
                    <LineChart data={analytics.monthly_trend}>
                      <CartesianGrid strokeDasharray="3 3" />
                      <XAxis dataKey="month" />
                      <YAxis yAxisId="left" />
                      <YAxis yAxisId="right" orientation="right" />
                      <Tooltip />
                      <Bar yAxisId="left" dataKey="count" fill="#8884d8" name="Count" />
                      <Line yAxisId="right" type="monotone" dataKey="value" stroke="#82ca9d" name="Value" />
                    </LineChart>
                  </ResponsiveContainer>
                </CardContent>
              </Card>
            </div>
          )}
        </TabsContent>
      </Tabs>

      {/* Return Details Dialog */}
      {selectedReturn && (
        <Dialog open={!!selectedReturn} onOpenChange={() => setSelectedReturn(null)}>
          <DialogContent className="max-w-4xl">
            <DialogHeader>
              <DialogTitle>Return Authorization Details - {selectedReturn.rma_number}</DialogTitle>
            </DialogHeader>
            <div className="space-y-4">
              <div className="grid grid-cols-2 gap-4">
                <div>
                  <label className="text-sm font-medium text-gray-600">Customer</label>
                  <p className="text-sm text-gray-900">{selectedReturn.customer.name}</p>
                </div>
                <div>
                  <label className="text-sm font-medium text-gray-600">Status</label>
                  <div className="mt-1">{getStatusBadge(selectedReturn.status)}</div>
                </div>
                <div>
                  <label className="text-sm font-medium text-gray-600">Return Type</label>
                  <p className="text-sm text-gray-900">{getReturnTypeLabel(selectedReturn.return_type)}</p>
                </div>
                <div>
                  <label className="text-sm font-medium text-gray-600">Requested Date</label>
                  <p className="text-sm text-gray-900">
                    {new Date(selectedReturn.requested_date).toLocaleDateString()}
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

export default ReturnAuthorizationDashboard;