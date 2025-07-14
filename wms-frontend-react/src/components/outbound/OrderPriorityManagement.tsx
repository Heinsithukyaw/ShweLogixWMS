import React, { useState, useEffect } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '../ui/card';
import { Button } from '../ui/button';
import { Input } from '../ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '../ui/select';
import { Badge } from '../ui/badge';
import { AlertCircle, Clock, TrendingUp, Users } from 'lucide-react';
import { toast } from '../../utils/toast';

interface OrderPriority {
  id: number;
  sales_order_id: number;
  priority_level: 'low' | 'medium' | 'high' | 'urgent' | 'rush';
  priority_score: number;
  priority_reason: string;
  effective_date: string;
  expires_at: string;
  status: 'active' | 'expired' | 'cancelled';
  sales_order: {
    order_number: string;
    customer: {
      name: string;
    };
  };
}

interface OrderPriorityFormData {
  sales_order_id: number;
  warehouse_id: number;
  priority_level: string;
  priority_reason: string;
  requested_ship_date: string;
  customer_priority: string;
  business_impact: string;
  special_instructions: string;
}

const OrderPriorityManagement: React.FC = () => {
  const [priorities, setPriorities] = useState<OrderPriority[]>([]);
  const [loading, setLoading] = useState(false);
  const [showForm, setShowForm] = useState(false);
  const [analytics, setAnalytics] = useState<any>(null);
  const [formData, setFormData] = useState<OrderPriorityFormData>({
    sales_order_id: 0,
    warehouse_id: 1,
    priority_level: 'medium',
    priority_reason: '',
    requested_ship_date: '',
    customer_priority: 'standard',
    business_impact: '',
    special_instructions: ''
  });

  useEffect(() => {
    fetchPriorities();
    fetchAnalytics();
  }, []);

  const fetchPriorities = async () => {
    setLoading(true);
    try {
      const response = await fetch('/api/outbound/order-priorities', {
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('token')}`,
          'Content-Type': 'application/json'
        }
      });
      const data = await response.json();
      if (data.success) {
        setPriorities(data.data.data);
      }
    } catch (error) {
      toast.error('Failed to fetch order priorities');
    } finally {
      setLoading(false);
    }
  };

  const fetchAnalytics = async () => {
    try {
      const response = await fetch('/api/outbound/order-priorities/analytics', {
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('token')}`,
          'Content-Type': 'application/json'
        }
      });
      const data = await response.json();
      if (data.success) {
        setAnalytics(data.data);
      }
    } catch (error) {
      console.error('Failed to fetch analytics');
    }
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setLoading(true);

    try {
      const response = await fetch('/api/outbound/order-priorities', {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('token')}`,
          'Content-Type': 'application/json'
        },
        body: JSON.stringify(formData)
      });

      const data = await response.json();
      if (data.success) {
        toast.success('Order priority created successfully');
        setShowForm(false);
        fetchPriorities();
        fetchAnalytics();
        resetForm();
      } else {
        toast.error(data.message || 'Failed to create order priority');
      }
    } catch (error) {
      toast.error('Failed to create order priority');
    } finally {
      setLoading(false);
    }
  };

  const handleBulkUpdate = async (updates: any[]) => {
    setLoading(true);
    try {
      const response = await fetch('/api/outbound/order-priorities/bulk-update', {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('token')}`,
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          updates,
          apply_immediately: true
        })
      });

      const data = await response.json();
      if (data.success) {
        toast.success(`Updated ${data.data.successful_updates} priorities`);
        fetchPriorities();
        fetchAnalytics();
      } else {
        toast.error(data.message || 'Failed to update priorities');
      }
    } catch (error) {
      toast.error('Failed to update priorities');
    } finally {
      setLoading(false);
    }
  };

  const resetForm = () => {
    setFormData({
      sales_order_id: 0,
      warehouse_id: 1,
      priority_level: 'medium',
      priority_reason: '',
      requested_ship_date: '',
      customer_priority: 'standard',
      business_impact: '',
      special_instructions: ''
    });
  };

  const getPriorityColor = (level: string) => {
    switch (level) {
      case 'rush': return 'bg-red-600 text-white';
      case 'urgent': return 'bg-red-500 text-white';
      case 'high': return 'bg-orange-500 text-white';
      case 'medium': return 'bg-yellow-500 text-black';
      case 'low': return 'bg-green-500 text-white';
      default: return 'bg-gray-500 text-white';
    }
  };

  const getScoreColor = (score: number) => {
    if (score >= 80) return 'text-red-600';
    if (score >= 60) return 'text-orange-600';
    if (score >= 40) return 'text-yellow-600';
    return 'text-green-600';
  };

  return (
    <div className="space-y-6">
      {/* Analytics Cards */}
      {analytics && (
        <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
          <Card>
            <CardContent className="p-4">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-sm text-gray-600">Total Priorities</p>
                  <p className="text-2xl font-bold">{analytics.total_priorities}</p>
                </div>
                <AlertCircle className="h-8 w-8 text-blue-600" />
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardContent className="p-4">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-sm text-gray-600">Average Score</p>
                  <p className="text-2xl font-bold">{analytics.average_score?.toFixed(1)}</p>
                </div>
                <TrendingUp className="h-8 w-8 text-green-600" />
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardContent className="p-4">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-sm text-gray-600">On-Time Rate</p>
                  <p className="text-2xl font-bold">{analytics.fulfillment_performance?.on_time_rate}%</p>
                </div>
                <Clock className="h-8 w-8 text-purple-600" />
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardContent className="p-4">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-sm text-gray-600">High Priority</p>
                  <p className="text-2xl font-bold">{analytics.by_level?.high || 0}</p>
                </div>
                <Users className="h-8 w-8 text-orange-600" />
              </div>
            </CardContent>
          </Card>
        </div>
      )}

      {/* Main Content */}
      <Card>
        <CardHeader>
          <div className="flex justify-between items-center">
            <CardTitle>Order Priority Management</CardTitle>
            <Button onClick={() => setShowForm(!showForm)}>
              {showForm ? 'Cancel' : 'Create Priority'}
            </Button>
          </div>
        </CardHeader>
        <CardContent>
          {showForm && (
            <form onSubmit={handleSubmit} className="space-y-4 mb-6 p-4 border rounded-lg">
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label className="block text-sm font-medium mb-1">Sales Order ID</label>
                  <Input
                    type="number"
                    value={formData.sales_order_id}
                    onChange={(e) => setFormData({...formData, sales_order_id: parseInt(e.target.value)})}
                    required
                  />
                </div>

                <div>
                  <label className="block text-sm font-medium mb-1">Priority Level</label>
                  <Select value={formData.priority_level} onValueChange={(value) => setFormData({...formData, priority_level: value})}>
                    <SelectTrigger>
                      <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="low">Low</SelectItem>
                      <SelectItem value="medium">Medium</SelectItem>
                      <SelectItem value="high">High</SelectItem>
                      <SelectItem value="urgent">Urgent</SelectItem>
                      <SelectItem value="rush">Rush</SelectItem>
                    </SelectContent>
                  </Select>
                </div>

                <div>
                  <label className="block text-sm font-medium mb-1">Priority Reason</label>
                  <Input
                    value={formData.priority_reason}
                    onChange={(e) => setFormData({...formData, priority_reason: e.target.value})}
                    placeholder="Reason for priority assignment"
                    required
                  />
                </div>

                <div>
                  <label className="block text-sm font-medium mb-1">Requested Ship Date</label>
                  <Input
                    type="date"
                    value={formData.requested_ship_date}
                    onChange={(e) => setFormData({...formData, requested_ship_date: e.target.value})}
                  />
                </div>

                <div>
                  <label className="block text-sm font-medium mb-1">Customer Priority</label>
                  <Select value={formData.customer_priority} onValueChange={(value) => setFormData({...formData, customer_priority: value})}>
                    <SelectTrigger>
                      <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="standard">Standard</SelectItem>
                      <SelectItem value="expedited">Expedited</SelectItem>
                      <SelectItem value="premium">Premium</SelectItem>
                    </SelectContent>
                  </Select>
                </div>

                <div>
                  <label className="block text-sm font-medium mb-1">Business Impact</label>
                  <Input
                    value={formData.business_impact}
                    onChange={(e) => setFormData({...formData, business_impact: e.target.value})}
                    placeholder="Business impact description"
                  />
                </div>
              </div>

              <div>
                <label className="block text-sm font-medium mb-1">Special Instructions</label>
                <textarea
                  className="w-full p-2 border rounded-md"
                  rows={3}
                  value={formData.special_instructions}
                  onChange={(e) => setFormData({...formData, special_instructions: e.target.value})}
                  placeholder="Special handling instructions"
                />
              </div>

              <Button type="submit" disabled={loading}>
                {loading ? 'Creating...' : 'Create Priority'}
              </Button>
            </form>
          )}

          {/* Priorities List */}
          <div className="space-y-4">
            {loading ? (
              <div className="text-center py-8">Loading...</div>
            ) : priorities.length === 0 ? (
              <div className="text-center py-8 text-gray-500">No order priorities found</div>
            ) : (
              priorities.map((priority) => (
                <div key={priority.id} className="border rounded-lg p-4">
                  <div className="flex justify-between items-start">
                    <div className="flex-1">
                      <div className="flex items-center gap-2 mb-2">
                        <h3 className="font-semibold">
                          Order #{priority.sales_order.order_number}
                        </h3>
                        <Badge className={getPriorityColor(priority.priority_level)}>
                          {priority.priority_level.toUpperCase()}
                        </Badge>
                        <Badge variant="outline" className={getScoreColor(priority.priority_score)}>
                          Score: {priority.priority_score}
                        </Badge>
                      </div>
                      
                      <p className="text-sm text-gray-600 mb-1">
                        Customer: {priority.sales_order.customer.name}
                      </p>
                      
                      <p className="text-sm text-gray-600 mb-2">
                        Reason: {priority.priority_reason}
                      </p>
                      
                      <div className="flex gap-4 text-xs text-gray-500">
                        <span>Effective: {new Date(priority.effective_date).toLocaleDateString()}</span>
                        <span>Expires: {new Date(priority.expires_at).toLocaleDateString()}</span>
                        <span>Status: {priority.status}</span>
                      </div>
                    </div>
                    
                    <div className="flex gap-2">
                      <Button size="sm" variant="outline">
                        Edit
                      </Button>
                      <Button size="sm" variant="outline">
                        History
                      </Button>
                    </div>
                  </div>
                </div>
              ))
            )}
          </div>
        </CardContent>
      </Card>
    </div>
  );
};

export default OrderPriorityManagement;