import React, { useState, useEffect } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { 
  TrendingUp, 
  TrendingDown, 
  DollarSign, 
  Percent,
  BarChart3,
  PieChart,
  Users,
  Calendar
} from 'lucide-react';
import {
  LineChart,
  Line,
  AreaChart,
  Area,
  BarChart,
  Bar,
  PieChart as RechartsPieChart,
  Cell,
  XAxis,
  YAxis,
  CartesianGrid,
  Tooltip,
  Legend,
  ResponsiveContainer
} from 'recharts';

interface ProfitabilityMetric {
  title: string;
  value: string;
  change: string;
  trend: 'up' | 'down' | 'neutral';
  icon: React.ReactNode;
}

interface ClientProfitability {
  id: string;
  name: string;
  revenue: number;
  costs: number;
  profit: number;
  margin: number;
  trend: 'up' | 'down' | 'neutral';
}

interface ProfitabilityDashboardProps {
  className?: string;
}

export const ProfitabilityDashboard: React.FC<ProfitabilityDashboardProps> = ({ className }) => {
  const [metrics, setMetrics] = useState<ProfitabilityMetric[]>([]);
  const [monthlyData, setMonthlyData] = useState<any[]>([]);
  const [clientData, setClientData] = useState<ClientProfitability[]>([]);
  const [costAllocationData, setCostAllocationData] = useState<any[]>([]);
  const [selectedPeriod, setSelectedPeriod] = useState('monthly');
  const [selectedAllocationMethod, setSelectedAllocationMethod] = useState('traditional');

  useEffect(() => {
    loadProfitabilityData();
  }, [selectedPeriod, selectedAllocationMethod]);

  const loadProfitabilityData = async () => {
    // Load overall metrics
    const mockMetrics: ProfitabilityMetric[] = [
      {
        title: 'Total Revenue',
        value: '$2,456,789',
        change: '+12.5%',
        trend: 'up',
        icon: <DollarSign className="h-4 w-4" />
      },
      {
        title: 'Gross Profit',
        value: '$1,234,567',
        change: '+8.3%',
        trend: 'up',
        icon: <TrendingUp className="h-4 w-4" />
      },
      {
        title: 'Net Margin',
        value: '18.5%',
        change: '-2.1%',
        trend: 'down',
        icon: <Percent className="h-4 w-4" />
      },
      {
        title: 'ROI',
        value: '24.7%',
        change: '+5.2%',
        trend: 'up',
        icon: <BarChart3 className="h-4 w-4" />
      }
    ];
    setMetrics(mockMetrics);

    // Load monthly profitability data
    const mockMonthlyData = [
      { month: 'Jan', revenue: 180000, costs: 120000, profit: 60000, margin: 33.3 },
      { month: 'Feb', revenue: 195000, costs: 125000, profit: 70000, margin: 35.9 },
      { month: 'Mar', revenue: 210000, costs: 135000, profit: 75000, margin: 35.7 },
      { month: 'Apr', revenue: 225000, costs: 145000, profit: 80000, margin: 35.6 },
      { month: 'May', revenue: 240000, costs: 155000, profit: 85000, margin: 35.4 },
      { month: 'Jun', revenue: 255000, costs: 165000, profit: 90000, margin: 35.3 }
    ];
    setMonthlyData(mockMonthlyData);

    // Load client profitability data
    const mockClientData: ClientProfitability[] = [
      { id: '1', name: 'Client A', revenue: 450000, costs: 280000, profit: 170000, margin: 37.8, trend: 'up' },
      { id: '2', name: 'Client B', revenue: 380000, costs: 250000, profit: 130000, margin: 34.2, trend: 'up' },
      { id: '3', name: 'Client C', revenue: 320000, costs: 220000, profit: 100000, margin: 31.3, trend: 'down' },
      { id: '4', name: 'Client D', revenue: 280000, costs: 200000, profit: 80000, margin: 28.6, trend: 'neutral' },
      { id: '5', name: 'Client E', revenue: 250000, costs: 180000, profit: 70000, margin: 28.0, trend: 'up' }
    ];
    setClientData(mockClientData);

    // Load cost allocation data
    const mockCostAllocationData = [
      { category: 'Storage', traditional: 35, abc: 28, direct: 32 },
      { category: 'Handling', traditional: 25, abc: 32, direct: 28 },
      { category: 'Transportation', traditional: 20, abc: 22, direct: 21 },
      { category: 'Administration', traditional: 15, abc: 12, direct: 14 },
      { category: 'Other', traditional: 5, abc: 6, direct: 5 }
    ];
    setCostAllocationData(mockCostAllocationData);
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

  const getTrendIcon = (trend: string) => {
    switch (trend) {
      case 'up':
        return <TrendingUp className="h-4 w-4 text-green-600" />;
      case 'down':
        return <TrendingDown className="h-4 w-4 text-red-600" />;
      default:
        return <div className="h-4 w-4" />;
    }
  };

  const COLORS = ['#0088FE', '#00C49F', '#FFBB28', '#FF8042', '#8884D8'];

  return (
    <div className={`space-y-6 ${className}`}>
      {/* Header */}
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold">Profitability Analysis</h1>
          <p className="text-gray-600">Monitor and analyze your warehouse profitability</p>
        </div>
        <div className="flex items-center space-x-4">
          <select
            value={selectedPeriod}
            onChange={(e) => setSelectedPeriod(e.target.value)}
            className="px-3 py-2 border border-gray-300 rounded-md"
          >
            <option value="monthly">Monthly</option>
            <option value="quarterly">Quarterly</option>
            <option value="yearly">Yearly</option>
          </select>
          <Button>
            <Calendar className="h-4 w-4 mr-2" />
            Export Report
          </Button>
        </div>
      </div>

      {/* Key Metrics */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        {metrics.map((metric, index) => (
          <Card key={index}>
            <CardContent className="p-6">
              <div className="flex items-center justify-between mb-4">
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

      {/* Monthly Profitability Charts */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {/* Revenue vs Costs */}
        <Card>
          <CardHeader>
            <CardTitle>Revenue vs Costs Trend</CardTitle>
          </CardHeader>
          <CardContent>
            <ResponsiveContainer width="100%" height={300}>
              <AreaChart data={monthlyData}>
                <CartesianGrid strokeDasharray="3 3" />
                <XAxis dataKey="month" />
                <YAxis />
                <Tooltip formatter={(value) => [`$${value.toLocaleString()}`, '']} />
                <Legend />
                <Area
                  type="monotone"
                  dataKey="revenue"
                  stackId="1"
                  stroke="#8884d8"
                  fill="#8884d8"
                  name="Revenue"
                />
                <Area
                  type="monotone"
                  dataKey="costs"
                  stackId="2"
                  stroke="#82ca9d"
                  fill="#82ca9d"
                  name="Costs"
                />
              </AreaChart>
            </ResponsiveContainer>
          </CardContent>
        </Card>

        {/* Profit Margin Trend */}
        <Card>
          <CardHeader>
            <CardTitle>Profit Margin Trend</CardTitle>
          </CardHeader>
          <CardContent>
            <ResponsiveContainer width="100%" height={300}>
              <LineChart data={monthlyData}>
                <CartesianGrid strokeDasharray="3 3" />
                <XAxis dataKey="month" />
                <YAxis />
                <Tooltip formatter={(value) => [`${value}%`, 'Margin']} />
                <Line
                  type="monotone"
                  dataKey="margin"
                  stroke="#ff7300"
                  strokeWidth={3}
                  dot={{ fill: '#ff7300', strokeWidth: 2, r: 6 }}
                />
              </LineChart>
            </ResponsiveContainer>
          </CardContent>
        </Card>
      </div>

      {/* Client-wise Profitability */}
      <Card>
        <CardHeader>
          <CardTitle className="flex items-center">
            <Users className="h-5 w-5 mr-2" />
            Client-wise Profitability
          </CardTitle>
        </CardHeader>
        <CardContent>
          <div className="overflow-x-auto">
            <table className="w-full">
              <thead>
                <tr className="border-b">
                  <th className="text-left py-3 px-4">Client</th>
                  <th className="text-right py-3 px-4">Revenue</th>
                  <th className="text-right py-3 px-4">Costs</th>
                  <th className="text-right py-3 px-4">Profit</th>
                  <th className="text-right py-3 px-4">Margin</th>
                  <th className="text-center py-3 px-4">Trend</th>
                </tr>
              </thead>
              <tbody>
                {clientData.map((client) => (
                  <tr key={client.id} className="border-b hover:bg-gray-50">
                    <td className="py-3 px-4 font-medium">{client.name}</td>
                    <td className="py-3 px-4 text-right">${client.revenue.toLocaleString()}</td>
                    <td className="py-3 px-4 text-right">${client.costs.toLocaleString()}</td>
                    <td className="py-3 px-4 text-right font-semibold text-green-600">
                      ${client.profit.toLocaleString()}
                    </td>
                    <td className="py-3 px-4 text-right">{client.margin.toFixed(1)}%</td>
                    <td className="py-3 px-4 text-center">{getTrendIcon(client.trend)}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </CardContent>
      </Card>

      {/* Cost Allocation Comparison */}
      <Card>
        <CardHeader>
          <CardTitle className="flex items-center justify-between">
            <span className="flex items-center">
              <PieChart className="h-5 w-5 mr-2" />
              Cost Allocation Methods
            </span>
            <select
              value={selectedAllocationMethod}
              onChange={(e) => setSelectedAllocationMethod(e.target.value)}
              className="px-3 py-1 border border-gray-300 rounded text-sm"
            >
              <option value="traditional">Traditional Costing</option>
              <option value="abc">Activity-Based Costing</option>
              <option value="direct">Direct Allocation</option>
            </select>
          </CardTitle>
        </CardHeader>
        <CardContent>
          <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {/* Bar Chart Comparison */}
            <div>
              <h4 className="text-lg font-semibold mb-4">Method Comparison</h4>
              <ResponsiveContainer width="100%" height={300}>
                <BarChart data={costAllocationData}>
                  <CartesianGrid strokeDasharray="3 3" />
                  <XAxis dataKey="category" />
                  <YAxis />
                  <Tooltip formatter={(value) => [`${value}%`, '']} />
                  <Legend />
                  <Bar dataKey="traditional" fill="#8884d8" name="Traditional" />
                  <Bar dataKey="abc" fill="#82ca9d" name="ABC" />
                  <Bar dataKey="direct" fill="#ffc658" name="Direct" />
                </BarChart>
              </ResponsiveContainer>
            </div>

            {/* Pie Chart for Selected Method */}
            <div>
              <h4 className="text-lg font-semibold mb-4">
                {selectedAllocationMethod.charAt(0).toUpperCase() + selectedAllocationMethod.slice(1)} Method
              </h4>
              <ResponsiveContainer width="100%" height={300}>
                <RechartsPieChart>
                  <Pie
                    data={costAllocationData}
                    cx="50%"
                    cy="50%"
                    labelLine={false}
                    label={({ category, [selectedAllocationMethod]: value }) => `${category}: ${value}%`}
                    outerRadius={80}
                    fill="#8884d8"
                    dataKey={selectedAllocationMethod}
                  >
                    {costAllocationData.map((entry, index) => (
                      <Cell key={`cell-${index}`} fill={COLORS[index % COLORS.length]} />
                    ))}
                  </Pie>
                  <Tooltip />
                </RechartsPieChart>
              </ResponsiveContainer>
            </div>
          </div>
        </CardContent>
      </Card>

      {/* Action Items */}
      <Card>
        <CardHeader>
          <CardTitle>Profitability Insights & Recommendations</CardTitle>
        </CardHeader>
        <CardContent>
          <div className="space-y-4">
            <div className="flex items-start space-x-3 p-4 bg-green-50 border border-green-200 rounded-lg">
              <TrendingUp className="h-5 w-5 text-green-600 mt-0.5" />
              <div>
                <h4 className="font-semibold text-green-800">High Performing Client</h4>
                <p className="text-green-700">Client A shows the highest profit margin at 37.8%. Consider expanding services for this client.</p>
              </div>
            </div>
            
            <div className="flex items-start space-x-3 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
              <TrendingDown className="h-5 w-5 text-yellow-600 mt-0.5" />
              <div>
                <h4 className="font-semibold text-yellow-800">Cost Optimization Opportunity</h4>
                <p className="text-yellow-700">Storage costs are 35% of total costs. Consider implementing ABC costing for better allocation.</p>
              </div>
            </div>
            
            <div className="flex items-start space-x-3 p-4 bg-blue-50 border border-blue-200 rounded-lg">
              <BarChart3 className="h-5 w-5 text-blue-600 mt-0.5" />
              <div>
                <h4 className="font-semibold text-blue-800">Revenue Growth</h4>
                <p className="text-blue-700">Monthly revenue has grown consistently by 8-12%. Maintain current growth strategies.</p>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  );
};