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
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import {
  BarChart,
  Bar,
  XAxis,
  YAxis,
  CartesianGrid,
  Tooltip,
  ResponsiveContainer,
  LineChart,
  Line,
  PieChart,
  Pie,
  Cell
} from 'recharts';
import {
  Users,
  Clock,
  TrendingUp,
  Calendar,
  CheckCircle,
  AlertCircle,
  UserCheck,
  UserX,
  Timer,
  DollarSign,
  Target,
  Award
} from 'lucide-react';

interface LaborSchedule {
  id: number;
  employee: {
    id: number;
    name: string;
    employee_code: string;
  };
  shift: {
    id: number;
    shift_name: string;
    start_time: string;
    end_time: string;
  };
  schedule_date: string;
  scheduled_start: string;
  scheduled_end: string;
  actual_start?: string;
  actual_end?: string;
  status: string;
  scheduled_hours: number;
  actual_hours: number;
  overtime_hours: number;
  warehouse: {
    id: number;
    name: string;
  };
}

interface LaborAnalytics {
  total_schedules: number;
  attendance_rate: number;
  by_status: Record<string, number>;
  total_scheduled_hours: number;
  total_actual_hours: number;
  total_overtime_hours: number;
  productivity_rate: number;
  daily_trend: Array<{
    schedule_date: string;
    total_schedules: number;
    total_hours: number;
    overtime_hours: number;
  }>;
}

interface LaborTask {
  id: number;
  task_number: string;
  task_type: string;
  priority: string;
  status: string;
  assigned_to?: {
    id: number;
    name: string;
  };
  description: string;
  estimated_minutes: number;
  actual_minutes: number;
  scheduled_start?: string;
  scheduled_end?: string;
  quality_score?: number;
}

const LaborDashboard: React.FC = () => {
  const [schedules, setSchedules] = useState<LaborSchedule[]>([]);
  const [tasks, setTasks] = useState<LaborTask[]>([]);
  const [analytics, setAnalytics] = useState<LaborAnalytics | null>(null);
  const [loading, setLoading] = useState(true);
  const [selectedDate, setSelectedDate] = useState(new Date().toISOString().split('T')[0]);
  const [warehouseFilter, setWarehouseFilter] = useState('all');
  const [statusFilter, setStatusFilter] = useState('all');

  useEffect(() => {
    fetchSchedules();
    fetchTasks();
    fetchAnalytics();
  }, [selectedDate, warehouseFilter, statusFilter]);

  const fetchSchedules = async () => {
    try {
      const params = new URLSearchParams();
      params.append('date', selectedDate);
      if (warehouseFilter !== 'all') params.append('warehouse_id', warehouseFilter);
      if (statusFilter !== 'all') params.append('status', statusFilter);

      const response = await fetch(`/api/labor/schedules?${params}`);
      const data = await response.json();
      
      if (data.success) {
        setSchedules(data.data.data);
      }
    } catch (error) {
      console.error('Error fetching schedules:', error);
    }
  };

  const fetchTasks = async () => {
    try {
      const params = new URLSearchParams();
      if (warehouseFilter !== 'all') params.append('warehouse_id', warehouseFilter);

      const response = await fetch(`/api/labor/tasks?${params}`);
      const data = await response.json();
      
      if (data.success) {
        setTasks(data.data.data);
      }
    } catch (error) {
      console.error('Error fetching tasks:', error);
    }
  };

  const fetchAnalytics = async () => {
    try {
      const params = new URLSearchParams();
      if (warehouseFilter !== 'all') params.append('warehouse_id', warehouseFilter);

      const response = await fetch(`/api/labor/analytics/dashboard?${params}`);
      const data = await response.json();
      
      if (data.success) {
        setAnalytics(data.data);
      }
    } catch (error) {
      console.error('Error fetching analytics:', error);
    } finally {
      setLoading(false);
    }
  };

  const handleCheckIn = async (scheduleId: number) => {
    try {
      const response = await fetch(`/api/labor/schedules/${scheduleId}/check-in`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          location: 'Dashboard',
          device_id: 'web-dashboard'
        })
      });

      if (response.ok) {
        fetchSchedules();
      }
    } catch (error) {
      console.error('Error checking in employee:', error);
    }
  };

  const handleCheckOut = async (scheduleId: number) => {
    try {
      const response = await fetch(`/api/labor/schedules/${scheduleId}/check-out`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          location: 'Dashboard',
          device_id: 'web-dashboard'
        })
      });

      if (response.ok) {
        fetchSchedules();
      }
    } catch (error) {
      console.error('Error checking out employee:', error);
    }
  };

  const getStatusBadge = (status: string) => {
    const statusConfig = {
      scheduled: { color: 'bg-blue-100 text-blue-800', label: 'Scheduled', icon: Calendar },
      checked_in: { color: 'bg-green-100 text-green-800', label: 'Checked In', icon: UserCheck },
      on_break: { color: 'bg-yellow-100 text-yellow-800', label: 'On Break', icon: Timer },
      checked_out: { color: 'bg-gray-100 text-gray-800', label: 'Checked Out', icon: UserX },
      absent: { color: 'bg-red-100 text-red-800', label: 'Absent', icon: UserX },
      late: { color: 'bg-orange-100 text-orange-800', label: 'Late', icon: AlertCircle },
      overtime: { color: 'bg-purple-100 text-purple-800', label: 'Overtime', icon: Clock }
    };

    const config = statusConfig[status as keyof typeof statusConfig] || statusConfig.scheduled;
    const IconComponent = config.icon;
    
    return (
      <Badge className={config.color}>
        <IconComponent className="h-3 w-3 mr-1" />
        {config.label}
      </Badge>
    );
  };

  const getPriorityBadge = (priority: string) => {
    const priorityConfig = {
      low: { color: 'bg-green-100 text-green-800', label: 'Low' },
      medium: { color: 'bg-yellow-100 text-yellow-800', label: 'Medium' },
      high: { color: 'bg-orange-100 text-orange-800', label: 'High' },
      urgent: { color: 'bg-red-100 text-red-800', label: 'Urgent' }
    };

    const config = priorityConfig[priority as keyof typeof priorityConfig] || priorityConfig.medium;
    return <Badge className={config.color}>{config.label}</Badge>;
  };

  const getTaskTypeBadge = (taskType: string) => {
    const typeConfig = {
      picking: { color: 'bg-blue-100 text-blue-800', label: 'Picking' },
      packing: { color: 'bg-green-100 text-green-800', label: 'Packing' },
      receiving: { color: 'bg-purple-100 text-purple-800', label: 'Receiving' },
      put_away: { color: 'bg-indigo-100 text-indigo-800', label: 'Put Away' },
      cycle_count: { color: 'bg-yellow-100 text-yellow-800', label: 'Cycle Count' },
      loading: { color: 'bg-orange-100 text-orange-800', label: 'Loading' },
      unloading: { color: 'bg-red-100 text-red-800', label: 'Unloading' },
      maintenance: { color: 'bg-gray-100 text-gray-800', label: 'Maintenance' },
      cleaning: { color: 'bg-teal-100 text-teal-800', label: 'Cleaning' },
      other: { color: 'bg-gray-100 text-gray-800', label: 'Other' }
    };

    const config = typeConfig[taskType as keyof typeof typeConfig] || typeConfig.other;
    return <Badge className={config.color}>{config.label}</Badge>;
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
          <h1 className="text-3xl font-bold text-gray-900">Labor Management</h1>
          <p className="text-gray-600">Monitor workforce scheduling, attendance, and productivity</p>
        </div>
        <div className="flex items-center gap-4">
          <Input
            type="date"
            value={selectedDate}
            onChange={(e) => setSelectedDate(e.target.value)}
            className="w-48"
          />
          <Select value={warehouseFilter} onValueChange={setWarehouseFilter}>
            <SelectTrigger className="w-48">
              <SelectValue placeholder="Select warehouse" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="all">All Warehouses</SelectItem>
              <SelectItem value="1">Main Warehouse</SelectItem>
              <SelectItem value="2">Distribution Center</SelectItem>
            </SelectContent>
          </Select>
        </div>
      </div>

      {/* Analytics Cards */}
      {analytics && (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
          <Card>
            <CardContent className="p-6">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-sm font-medium text-gray-600">Total Employees</p>
                  <p className="text-2xl font-bold text-gray-900">{analytics.total_schedules}</p>
                </div>
                <Users className="h-8 w-8 text-blue-600" />
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardContent className="p-6">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-sm font-medium text-gray-600">Attendance Rate</p>
                  <p className="text-2xl font-bold text-gray-900">
                    {analytics.attendance_rate.toFixed(1)}%
                  </p>
                </div>
                <UserCheck className="h-8 w-8 text-green-600" />
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardContent className="p-6">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-sm font-medium text-gray-600">Productivity Rate</p>
                  <p className="text-2xl font-bold text-gray-900">
                    {analytics.productivity_rate.toFixed(1)}%
                  </p>
                </div>
                <Target className="h-8 w-8 text-orange-600" />
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardContent className="p-6">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-sm font-medium text-gray-600">Overtime Hours</p>
                  <p className="text-2xl font-bold text-gray-900">
                    {analytics.total_overtime_hours}
                  </p>
                </div>
                <Clock className="h-8 w-8 text-red-600" />
              </div>
            </CardContent>
          </Card>
        </div>
      )}

      <Tabs defaultValue="schedules" className="space-y-6">
        <TabsList>
          <TabsTrigger value="schedules">Today's Schedule</TabsTrigger>
          <TabsTrigger value="tasks">Active Tasks</TabsTrigger>
          <TabsTrigger value="analytics">Analytics</TabsTrigger>
        </TabsList>

        <TabsContent value="schedules" className="space-y-6">
          {/* Schedule Filters */}
          <Card>
            <CardContent className="p-6">
              <div className="flex items-center gap-4">
                <Select value={statusFilter} onValueChange={setStatusFilter}>
                  <SelectTrigger className="w-48">
                    <SelectValue placeholder="Filter by status" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="all">All Statuses</SelectItem>
                    <SelectItem value="scheduled">Scheduled</SelectItem>
                    <SelectItem value="checked_in">Checked In</SelectItem>
                    <SelectItem value="on_break">On Break</SelectItem>
                    <SelectItem value="checked_out">Checked Out</SelectItem>
                    <SelectItem value="absent">Absent</SelectItem>
                  </SelectContent>
                </Select>
              </div>
            </CardContent>
          </Card>

          {/* Schedules Table */}
          <Card>
            <CardHeader>
              <CardTitle>Employee Schedules - {new Date(selectedDate).toLocaleDateString()}</CardTitle>
            </CardHeader>
            <CardContent>
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Employee</TableHead>
                    <TableHead>Shift</TableHead>
                    <TableHead>Scheduled Time</TableHead>
                    <TableHead>Actual Time</TableHead>
                    <TableHead>Status</TableHead>
                    <TableHead>Hours</TableHead>
                    <TableHead>Overtime</TableHead>
                    <TableHead>Warehouse</TableHead>
                    <TableHead>Actions</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {schedules.map((schedule) => (
                    <TableRow key={schedule.id}>
                      <TableCell>
                        <div>
                          <p className="font-medium">{schedule.employee.name}</p>
                          <p className="text-sm text-gray-500">{schedule.employee.employee_code}</p>
                        </div>
                      </TableCell>
                      <TableCell>{schedule.shift.shift_name}</TableCell>
                      <TableCell>
                        {schedule.scheduled_start} - {schedule.scheduled_end}
                      </TableCell>
                      <TableCell>
                        {schedule.actual_start && schedule.actual_end
                          ? `${schedule.actual_start} - ${schedule.actual_end}`
                          : schedule.actual_start
                          ? `${schedule.actual_start} - Active`
                          : '-'
                        }
                      </TableCell>
                      <TableCell>{getStatusBadge(schedule.status)}</TableCell>
                      <TableCell>
                        {schedule.actual_hours > 0 
                          ? `${schedule.actual_hours}h` 
                          : `${schedule.scheduled_hours}h (scheduled)`
                        }
                      </TableCell>
                      <TableCell>
                        {schedule.overtime_hours > 0 ? `${schedule.overtime_hours}h` : '-'}
                      </TableCell>
                      <TableCell>{schedule.warehouse.name}</TableCell>
                      <TableCell>
                        <div className="flex items-center gap-2">
                          {schedule.status === 'scheduled' && (
                            <Button
                              variant="outline"
                              size="sm"
                              onClick={() => handleCheckIn(schedule.id)}
                              className="text-green-600 hover:text-green-700"
                            >
                              Check In
                            </Button>
                          )}
                          {['checked_in', 'on_break', 'late'].includes(schedule.status) && (
                            <Button
                              variant="outline"
                              size="sm"
                              onClick={() => handleCheckOut(schedule.id)}
                              className="text-red-600 hover:text-red-700"
                            >
                              Check Out
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

        <TabsContent value="tasks" className="space-y-6">
          {/* Tasks Table */}
          <Card>
            <CardHeader>
              <CardTitle>Active Labor Tasks</CardTitle>
            </CardHeader>
            <CardContent>
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Task Number</TableHead>
                    <TableHead>Type</TableHead>
                    <TableHead>Priority</TableHead>
                    <TableHead>Assigned To</TableHead>
                    <TableHead>Description</TableHead>
                    <TableHead>Progress</TableHead>
                    <TableHead>Quality Score</TableHead>
                    <TableHead>Status</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {tasks.map((task) => (
                    <TableRow key={task.id}>
                      <TableCell className="font-medium">{task.task_number}</TableCell>
                      <TableCell>{getTaskTypeBadge(task.task_type)}</TableCell>
                      <TableCell>{getPriorityBadge(task.priority)}</TableCell>
                      <TableCell>
                        {task.assigned_to ? task.assigned_to.name : 'Unassigned'}
                      </TableCell>
                      <TableCell className="max-w-xs truncate">
                        {task.description}
                      </TableCell>
                      <TableCell>
                        {task.actual_minutes > 0 && task.estimated_minutes > 0
                          ? `${Math.round((task.actual_minutes / task.estimated_minutes) * 100)}%`
                          : '-'
                        }
                      </TableCell>
                      <TableCell>
                        {task.quality_score ? (
                          <div className="flex items-center gap-1">
                            <Award className="h-4 w-4 text-yellow-500" />
                            {task.quality_score.toFixed(1)}
                          </div>
                        ) : '-'}
                      </TableCell>
                      <TableCell>{getStatusBadge(task.status)}</TableCell>
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
                  <CardTitle>Employee Status Distribution</CardTitle>
                </CardHeader>
                <CardContent>
                  <ResponsiveContainer width="100%" height={300}>
                    <PieChart>
                      <Pie
                        data={Object.entries(analytics.by_status).map(([status, count]) => ({
                          name: status.replace('_', ' '),
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

              {/* Hours Comparison */}
              <Card>
                <CardHeader>
                  <CardTitle>Scheduled vs Actual Hours</CardTitle>
                </CardHeader>
                <CardContent>
                  <ResponsiveContainer width="100%" height={300}>
                    <BarChart data={[
                      { name: 'Scheduled', hours: analytics.total_scheduled_hours },
                      { name: 'Actual', hours: analytics.total_actual_hours },
                      { name: 'Overtime', hours: analytics.total_overtime_hours }
                    ]}>
                      <CartesianGrid strokeDasharray="3 3" />
                      <XAxis dataKey="name" />
                      <YAxis />
                      <Tooltip />
                      <Bar dataKey="hours" fill="#8884d8" />
                    </BarChart>
                  </ResponsiveContainer>
                </CardContent>
              </Card>

              {/* Daily Trend */}
              <Card className="lg:col-span-2">
                <CardHeader>
                  <CardTitle>Daily Labor Trend</CardTitle>
                </CardHeader>
                <CardContent>
                  <ResponsiveContainer width="100%" height={300}>
                    <LineChart data={analytics.daily_trend}>
                      <CartesianGrid strokeDasharray="3 3" />
                      <XAxis dataKey="schedule_date" />
                      <YAxis yAxisId="left" />
                      <YAxis yAxisId="right" orientation="right" />
                      <Tooltip />
                      <Bar yAxisId="left" dataKey="total_schedules" fill="#8884d8" name="Employees" />
                      <Line yAxisId="right" type="monotone" dataKey="total_hours" stroke="#82ca9d" name="Hours" />
                      <Line yAxisId="right" type="monotone" dataKey="overtime_hours" stroke="#ff7300" name="Overtime" />
                    </LineChart>
                  </ResponsiveContainer>
                </CardContent>
              </Card>
            </div>
          )}
        </TabsContent>
      </Tabs>
    </div>
  );
};

export default LaborDashboard;