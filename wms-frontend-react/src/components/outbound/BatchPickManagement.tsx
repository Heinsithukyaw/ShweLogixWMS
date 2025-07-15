import React, { useState, useEffect } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '../ui/card';
import { Button } from '../ui/button';
import { Input } from '../ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '../ui/select';
import { Badge } from '../ui/badge';
import { Package, Clock, TrendingUp, Users, Play, CheckCircle } from 'lucide-react';
import { toast } from '../../utils/toast';

interface BatchPick {
  id: number;
  batch_number: string;
  pick_type: string;
  batch_strategy: string;
  total_orders: number;
  total_items: number;
  total_locations: number;
  estimated_pick_time: number;
  actual_pick_time?: number;
  assigned_picker_id?: number;
  assigned_picker?: {
    name: string;
  };
  status: 'created' | 'assigned' | 'in_progress' | 'completed' | 'cancelled';
  optimization_score: number;
  created_at: string;
}

interface BatchPickFormData {
  warehouse_id: number;
  pick_type: string;
  batch_strategy: string;
  max_orders: number;
  max_items: number;
  max_weight: number;
  assigned_picker_id?: number;
  auto_assign: boolean;
}

const BatchPickManagement: React.FC = () => {
  const [batchPicks, setBatchPicks] = useState<BatchPick[]>([]);
  const [loading, setLoading] = useState(false);
  const [showForm, setShowForm] = useState(false);
  const [analytics, setAnalytics] = useState<any>(null);
  const [formData, setFormData] = useState<BatchPickFormData>({
    warehouse_id: 1,
    pick_type: 'multi_order',
    batch_strategy: 'priority',
    max_orders: 5,
    max_items: 50,
    max_weight: 25.0,
    assigned_picker_id: undefined,
    auto_assign: true
  });

  useEffect(() => {
    fetchBatchPicks();
    fetchAnalytics();
  }, []);

  const fetchBatchPicks = async () => {
    setLoading(true);
    try {
      const response = await fetch('/api/outbound/batch-picks', {
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('token')}`,
          'Content-Type': 'application/json'
        }
      });
      const data = await response.json();
      if (data.success) {
        setBatchPicks(data.data.data);
      }
    } catch (error) {
      toast.error('Failed to fetch batch picks');
    } finally {
      setLoading(false);
    }
  };

  const fetchAnalytics = async () => {
    try {
      const response = await fetch('/api/outbound/batch-picks/analytics', {
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
      const response = await fetch('/api/outbound/batch-picks', {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('token')}`,
          'Content-Type': 'application/json'
        },
        body: JSON.stringify(formData)
      });

      const data = await response.json();
      if (data.success) {
        toast.success('Batch pick created successfully');
        setShowForm(false);
        fetchBatchPicks();
        fetchAnalytics();
        resetForm();
      } else {
        toast.error(data.message || 'Failed to create batch pick');
      }
    } catch (error) {
      toast.error('Failed to create batch pick');
    } finally {
      setLoading(false);
    }
  };

  const handleStartBatch = async (id: number) => {
    try {
      const response = await fetch(`/api/outbound/batch-picks/${id}/start`, {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('token')}`,
          'Content-Type': 'application/json'
        }
      });

      const data = await response.json();
      if (data.success) {
        toast.success('Batch pick started');
        fetchBatchPicks();
      } else {
        toast.error(data.message || 'Failed to start batch pick');
      }
    } catch (error) {
      toast.error('Failed to start batch pick');
    }
  };

  const handleCompleteBatch = async (id: number) => {
    try {
      const response = await fetch(`/api/outbound/batch-picks/${id}/complete`, {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('token')}`,
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          completion_notes: 'Batch pick completed successfully',
          actual_pick_time: 45,
          items_picked: 28,
          items_short: 0,
          quality_issues: []
        })
      });

      const data = await response.json();
      if (data.success) {
        toast.success('Batch pick completed');
        fetchBatchPicks();
        fetchAnalytics();
      } else {
        toast.error(data.message || 'Failed to complete batch pick');
      }
    } catch (error) {
      toast.error('Failed to complete batch pick');
    }
  };

  const resetForm = () => {
    setFormData({
      warehouse_id: 1,
      pick_type: 'multi_order',
      batch_strategy: 'priority',
      max_orders: 5,
      max_items: 50,
      max_weight: 25.0,
      assigned_picker_id: undefined,
      auto_assign: true
    });
  };

  const getStatusColor = (status: string) => {
    switch (status) {
      case 'completed': return 'bg-green-500 text-white';
      case 'in_progress': return 'bg-blue-500 text-white';
      case 'assigned': return 'bg-yellow-500 text-black';
      case 'created': return 'bg-gray-500 text-white';
      case 'cancelled': return 'bg-red-500 text-white';
      default: return 'bg-gray-500 text-white';
    }
  };

  const getOptimizationColor = (score: number) => {
    if (score >= 90) return 'text-green-600';
    if (score >= 70) return 'text-yellow-600';
    return 'text-red-600';
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
                  <p className="text-sm text-gray-600">Total Batches</p>
                  <p className="text-2xl font-bold">{analytics.total_batch_picks}</p>
                </div>
                <Package className="h-8 w-8 text-blue-600" />
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardContent className="p-4">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-sm text-gray-600">Avg Pick Time</p>
                  <p className="text-2xl font-bold">{analytics.average_pick_time?.toFixed(1)}m</p>
                </div>
                <Clock className="h-8 w-8 text-green-600" />
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardContent className="p-4">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-sm text-gray-600">Efficiency</p>
                  <p className="text-2xl font-bold">{analytics.efficiency_score?.toFixed(1)}%</p>
                </div>
                <TrendingUp className="h-8 w-8 text-purple-600" />
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardContent className="p-4">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-sm text-gray-600">Active Pickers</p>
                  <p className="text-2xl font-bold">{analytics.active_pickers || 0}</p>
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
            <CardTitle>Batch Pick Management</CardTitle>
            <Button onClick={() => setShowForm(!showForm)}>
              {showForm ? 'Cancel' : 'Create Batch Pick'}
            </Button>
          </div>
        </CardHeader>
        <CardContent>
          {showForm && (
            <form onSubmit={handleSubmit} className="space-y-4 mb-6 p-4 border rounded-lg">
              <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                  <label className="block text-sm font-medium mb-1">Pick Type</label>
                  <Select value={formData.pick_type} onValueChange={(value) => setFormData({...formData, pick_type: value})}>
                    <SelectTrigger>
                      <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="single_order">Single Order</SelectItem>
                      <SelectItem value="multi_order">Multi Order</SelectItem>
                      <SelectItem value="wave">Wave</SelectItem>
                    </SelectContent>
                  </Select>
                </div>

                <div>
                  <label className="block text-sm font-medium mb-1">Batch Strategy</label>
                  <Select value={formData.batch_strategy} onValueChange={(value) => setFormData({...formData, batch_strategy: value})}>
                    <SelectTrigger>
                      <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="priority">Priority</SelectItem>
                      <SelectItem value="location">Location</SelectItem>
                      <SelectItem value="customer">Customer</SelectItem>
                      <SelectItem value="product">Product</SelectItem>
                    </SelectContent>
                  </Select>
                </div>

                <div>
                  <label className="block text-sm font-medium mb-1">Max Orders</label>
                  <Input
                    type="number"
                    value={formData.max_orders}
                    onChange={(e) => setFormData({...formData, max_orders: parseInt(e.target.value)})}
                    min="1"
                    max="20"
                  />
                </div>

                <div>
                  <label className="block text-sm font-medium mb-1">Max Items</label>
                  <Input
                    type="number"
                    value={formData.max_items}
                    onChange={(e) => setFormData({...formData, max_items: parseInt(e.target.value)})}
                    min="1"
                    max="100"
                  />
                </div>

                <div>
                  <label className="block text-sm font-medium mb-1">Max Weight (kg)</label>
                  <Input
                    type="number"
                    step="0.1"
                    value={formData.max_weight}
                    onChange={(e) => setFormData({...formData, max_weight: parseFloat(e.target.value)})}
                    min="0.1"
                  />
                </div>

                <div>
                  <label className="block text-sm font-medium mb-1">Picker ID (Optional)</label>
                  <Input
                    type="number"
                    value={formData.assigned_picker_id || ''}
                    onChange={(e) => setFormData({...formData, assigned_picker_id: e.target.value ? parseInt(e.target.value) : undefined})}
                    placeholder="Auto-assign if empty"
                  />
                </div>
              </div>

              <div className="flex items-center gap-2">
                <input
                  type="checkbox"
                  id="auto_assign"
                  checked={formData.auto_assign}
                  onChange={(e) => setFormData({...formData, auto_assign: e.target.checked})}
                />
                <label htmlFor="auto_assign" className="text-sm">Auto-assign picker</label>
              </div>

              <Button type="submit" disabled={loading}>
                {loading ? 'Creating...' : 'Create Batch Pick'}
              </Button>
            </form>
          )}

          {/* Batch Picks List */}
          <div className="space-y-4">
            {loading ? (
              <div className="text-center py-8">Loading...</div>
            ) : batchPicks.length === 0 ? (
              <div className="text-center py-8 text-gray-500">No batch picks found</div>
            ) : (
              batchPicks.map((batch) => (
                <div key={batch.id} className="border rounded-lg p-4">
                  <div className="flex justify-between items-start">
                    <div className="flex-1">
                      <div className="flex items-center gap-2 mb-2">
                        <h3 className="font-semibold">
                          Batch #{batch.batch_number}
                        </h3>
                        <Badge className={getStatusColor(batch.status)}>
                          {batch.status.toUpperCase()}
                        </Badge>
                        <Badge variant="outline" className={getOptimizationColor(batch.optimization_score)}>
                          Score: {batch.optimization_score}
                        </Badge>
                      </div>
                      
                      <div className="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm text-gray-600 mb-2">
                        <div>
                          <span className="font-medium">Orders:</span> {batch.total_orders}
                        </div>
                        <div>
                          <span className="font-medium">Items:</span> {batch.total_items}
                        </div>
                        <div>
                          <span className="font-medium">Locations:</span> {batch.total_locations}
                        </div>
                        <div>
                          <span className="font-medium">Est. Time:</span> {batch.estimated_pick_time}m
                        </div>
                      </div>
                      
                      <div className="flex gap-4 text-xs text-gray-500">
                        <span>Type: {batch.pick_type}</span>
                        <span>Strategy: {batch.batch_strategy}</span>
                        {batch.assigned_picker && (
                          <span>Picker: {batch.assigned_picker.name}</span>
                        )}
                        <span>Created: {new Date(batch.created_at).toLocaleDateString()}</span>
                      </div>
                    </div>
                    
                    <div className="flex gap-2">
                      {batch.status === 'assigned' && (
                        <Button 
                          size="sm" 
                          onClick={() => handleStartBatch(batch.id)}
                          className="bg-blue-600 hover:bg-blue-700"
                        >
                          <Play className="h-4 w-4 mr-1" />
                          Start
                        </Button>
                      )}
                      
                      {batch.status === 'in_progress' && (
                        <Button 
                          size="sm" 
                          onClick={() => handleCompleteBatch(batch.id)}
                          className="bg-green-600 hover:bg-green-700"
                        >
                          <CheckCircle className="h-4 w-4 mr-1" />
                          Complete
                        </Button>
                      )}
                      
                      <Button size="sm" variant="outline">
                        View Details
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

export default BatchPickManagement;