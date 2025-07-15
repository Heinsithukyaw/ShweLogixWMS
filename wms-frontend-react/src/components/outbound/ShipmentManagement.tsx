import React, { useState, useEffect } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '../ui/card';
import { Button } from '../ui/button';
import { Input } from '../ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '../ui/select';
import { Badge } from '../ui/badge';
import { Truck, Package, Clock, DollarSign, Send, Eye } from 'lucide-react';
import { toast } from '../../utils/toast';

interface Shipment {
  id: number;
  shipment_number: string;
  carrier: {
    name: string;
    code: string;
  };
  shipment_type: string;
  service_level: string;
  status: 'created' | 'ready_to_ship' | 'shipped' | 'in_transit' | 'delivered' | 'cancelled';
  tracking_number?: string;
  ship_date: string;
  actual_ship_date?: string;
  weight: number;
  declared_value: number;
  shipping_cost?: number;
  ship_to_address: {
    street: string;
    city: string;
    state: string;
    postal_code: string;
    country: string;
  };
  orders: Array<{
    order_number: string;
    customer: {
      name: string;
    };
  }>;
  created_at: string;
}

interface ShipmentFormData {
  warehouse_id: number;
  carrier_id: number;
  shipment_type: string;
  service_level: string;
  ship_date: string;
  ship_from_address: {
    street: string;
    city: string;
    state: string;
    postal_code: string;
    country: string;
  };
  ship_to_address: {
    street: string;
    city: string;
    state: string;
    postal_code: string;
    country: string;
  };
  orders: number[];
  package_type: string;
  dimensions: {
    length: number;
    width: number;
    height: number;
  };
  weight: number;
  declared_value: number;
  insurance_required: boolean;
  signature_required: boolean;
}

const ShipmentManagement: React.FC = () => {
  const [shipments, setShipments] = useState<Shipment[]>([]);
  const [loading, setLoading] = useState(false);
  const [showForm, setShowForm] = useState(false);
  const [analytics, setAnalytics] = useState<any>(null);
  const [carriers, setCarriers] = useState<any[]>([]);
  const [formData, setFormData] = useState<ShipmentFormData>({
    warehouse_id: 1,
    carrier_id: 1,
    shipment_type: 'standard',
    service_level: 'ground',
    ship_date: new Date().toISOString().split('T')[0],
    ship_from_address: {
      street: '123 Warehouse St',
      city: 'Warehouse City',
      state: 'WS',
      postal_code: '12345',
      country: 'US'
    },
    ship_to_address: {
      street: '',
      city: '',
      state: '',
      postal_code: '',
      country: 'US'
    },
    orders: [],
    package_type: 'box',
    dimensions: {
      length: 30,
      width: 20,
      height: 15
    },
    weight: 2.5,
    declared_value: 100.00,
    insurance_required: false,
    signature_required: false
  });

  useEffect(() => {
    fetchShipments();
    fetchAnalytics();
    fetchCarriers();
  }, []);

  const fetchShipments = async () => {
    setLoading(true);
    try {
      const response = await fetch('/api/outbound/shipments', {
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('token')}`,
          'Content-Type': 'application/json'
        }
      });
      const data = await response.json();
      if (data.success) {
        setShipments(data.data.data);
      }
    } catch (error) {
      toast.error('Failed to fetch shipments');
    } finally {
      setLoading(false);
    }
  };

  const fetchAnalytics = async () => {
    try {
      const response = await fetch('/api/outbound/shipments/analytics', {
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

  const fetchCarriers = async () => {
    try {
      const response = await fetch('/api/shipping-carriers', {
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('token')}`,
          'Content-Type': 'application/json'
        }
      });
      const data = await response.json();
      if (data.success) {
        setCarriers(data.data.data || []);
      }
    } catch (error) {
      console.error('Failed to fetch carriers');
    }
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setLoading(true);

    try {
      const response = await fetch('/api/outbound/shipments', {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('token')}`,
          'Content-Type': 'application/json'
        },
        body: JSON.stringify(formData)
      });

      const data = await response.json();
      if (data.success) {
        toast.success('Shipment created successfully');
        setShowForm(false);
        fetchShipments();
        fetchAnalytics();
        resetForm();
      } else {
        toast.error(data.message || 'Failed to create shipment');
      }
    } catch (error) {
      toast.error('Failed to create shipment');
    } finally {
      setLoading(false);
    }
  };

  const handleShipShipment = async (id: number) => {
    try {
      const response = await fetch(`/api/outbound/shipments/${id}/ship`, {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('token')}`,
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          actual_ship_date: new Date().toISOString(),
          shipped_by: 1, // Current user ID
          shipping_notes: 'Shipment dispatched successfully',
          generate_label: true,
          send_notifications: true
        })
      });

      const data = await response.json();
      if (data.success) {
        toast.success('Shipment dispatched successfully');
        fetchShipments();
        fetchAnalytics();
      } else {
        toast.error(data.message || 'Failed to ship shipment');
      }
    } catch (error) {
      toast.error('Failed to ship shipment');
    }
  };

  const resetForm = () => {
    setFormData({
      warehouse_id: 1,
      carrier_id: 1,
      shipment_type: 'standard',
      service_level: 'ground',
      ship_date: new Date().toISOString().split('T')[0],
      ship_from_address: {
        street: '123 Warehouse St',
        city: 'Warehouse City',
        state: 'WS',
        postal_code: '12345',
        country: 'US'
      },
      ship_to_address: {
        street: '',
        city: '',
        state: '',
        postal_code: '',
        country: 'US'
      },
      orders: [],
      package_type: 'box',
      dimensions: {
        length: 30,
        width: 20,
        height: 15
      },
      weight: 2.5,
      declared_value: 100.00,
      insurance_required: false,
      signature_required: false
    });
  };

  const getStatusColor = (status: string) => {
    switch (status) {
      case 'delivered': return 'bg-green-500 text-white';
      case 'in_transit': return 'bg-blue-500 text-white';
      case 'shipped': return 'bg-purple-500 text-white';
      case 'ready_to_ship': return 'bg-yellow-500 text-black';
      case 'created': return 'bg-gray-500 text-white';
      case 'cancelled': return 'bg-red-500 text-white';
      default: return 'bg-gray-500 text-white';
    }
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
                  <p className="text-sm text-gray-600">Total Shipments</p>
                  <p className="text-2xl font-bold">{analytics.total_shipments}</p>
                </div>
                <Package className="h-8 w-8 text-blue-600" />
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardContent className="p-4">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-sm text-gray-600">In Transit</p>
                  <p className="text-2xl font-bold">{analytics.by_status?.in_transit || 0}</p>
                </div>
                <Truck className="h-8 w-8 text-green-600" />
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardContent className="p-4">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-sm text-gray-600">On-Time Rate</p>
                  <p className="text-2xl font-bold">{analytics.on_time_delivery_rate?.toFixed(1)}%</p>
                </div>
                <Clock className="h-8 w-8 text-purple-600" />
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardContent className="p-4">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-sm text-gray-600">Avg Cost</p>
                  <p className="text-2xl font-bold">${analytics.average_shipping_cost?.toFixed(2)}</p>
                </div>
                <DollarSign className="h-8 w-8 text-orange-600" />
              </div>
            </CardContent>
          </Card>
        </div>
      )}

      {/* Main Content */}
      <Card>
        <CardHeader>
          <div className="flex justify-between items-center">
            <CardTitle>Shipment Management</CardTitle>
            <Button onClick={() => setShowForm(!showForm)}>
              {showForm ? 'Cancel' : 'Create Shipment'}
            </Button>
          </div>
        </CardHeader>
        <CardContent>
          {showForm && (
            <form onSubmit={handleSubmit} className="space-y-4 mb-6 p-4 border rounded-lg">
              <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                  <label className="block text-sm font-medium mb-1">Carrier</label>
                  <Select value={formData.carrier_id.toString()} onValueChange={(value) => setFormData({...formData, carrier_id: parseInt(value)})}>
                    <SelectTrigger>
                      <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                      {carriers.map((carrier) => (
                        <SelectItem key={carrier.id} value={carrier.id.toString()}>
                          {carrier.name}
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                </div>

                <div>
                  <label className="block text-sm font-medium mb-1">Shipment Type</label>
                  <Select value={formData.shipment_type} onValueChange={(value) => setFormData({...formData, shipment_type: value})}>
                    <SelectTrigger>
                      <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="standard">Standard</SelectItem>
                      <SelectItem value="express">Express</SelectItem>
                      <SelectItem value="overnight">Overnight</SelectItem>
                      <SelectItem value="freight">Freight</SelectItem>
                    </SelectContent>
                  </Select>
                </div>

                <div>
                  <label className="block text-sm font-medium mb-1">Service Level</label>
                  <Select value={formData.service_level} onValueChange={(value) => setFormData({...formData, service_level: value})}>
                    <SelectTrigger>
                      <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="ground">Ground</SelectItem>
                      <SelectItem value="air">Air</SelectItem>
                      <SelectItem value="express">Express</SelectItem>
                      <SelectItem value="overnight">Overnight</SelectItem>
                    </SelectContent>
                  </Select>
                </div>

                <div>
                  <label className="block text-sm font-medium mb-1">Ship Date</label>
                  <Input
                    type="date"
                    value={formData.ship_date}
                    onChange={(e) => setFormData({...formData, ship_date: e.target.value})}
                    required
                  />
                </div>

                <div>
                  <label className="block text-sm font-medium mb-1">Weight (kg)</label>
                  <Input
                    type="number"
                    step="0.1"
                    value={formData.weight}
                    onChange={(e) => setFormData({...formData, weight: parseFloat(e.target.value)})}
                    required
                  />
                </div>

                <div>
                  <label className="block text-sm font-medium mb-1">Declared Value</label>
                  <Input
                    type="number"
                    step="0.01"
                    value={formData.declared_value}
                    onChange={(e) => setFormData({...formData, declared_value: parseFloat(e.target.value)})}
                    required
                  />
                </div>
              </div>

              {/* Ship To Address */}
              <div className="space-y-2">
                <h4 className="font-medium">Ship To Address</h4>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <label className="block text-sm font-medium mb-1">Street</label>
                    <Input
                      value={formData.ship_to_address.street}
                      onChange={(e) => setFormData({
                        ...formData,
                        ship_to_address: {...formData.ship_to_address, street: e.target.value}
                      })}
                      required
                    />
                  </div>
                  <div>
                    <label className="block text-sm font-medium mb-1">City</label>
                    <Input
                      value={formData.ship_to_address.city}
                      onChange={(e) => setFormData({
                        ...formData,
                        ship_to_address: {...formData.ship_to_address, city: e.target.value}
                      })}
                      required
                    />
                  </div>
                  <div>
                    <label className="block text-sm font-medium mb-1">State</label>
                    <Input
                      value={formData.ship_to_address.state}
                      onChange={(e) => setFormData({
                        ...formData,
                        ship_to_address: {...formData.ship_to_address, state: e.target.value}
                      })}
                      required
                    />
                  </div>
                  <div>
                    <label className="block text-sm font-medium mb-1">Postal Code</label>
                    <Input
                      value={formData.ship_to_address.postal_code}
                      onChange={(e) => setFormData({
                        ...formData,
                        ship_to_address: {...formData.ship_to_address, postal_code: e.target.value}
                      })}
                      required
                    />
                  </div>
                </div>
              </div>

              {/* Dimensions */}
              <div className="space-y-2">
                <h4 className="font-medium">Package Dimensions (cm)</h4>
                <div className="grid grid-cols-3 gap-4">
                  <div>
                    <label className="block text-sm font-medium mb-1">Length</label>
                    <Input
                      type="number"
                      value={formData.dimensions.length}
                      onChange={(e) => setFormData({
                        ...formData,
                        dimensions: {...formData.dimensions, length: parseFloat(e.target.value)}
                      })}
                      required
                    />
                  </div>
                  <div>
                    <label className="block text-sm font-medium mb-1">Width</label>
                    <Input
                      type="number"
                      value={formData.dimensions.width}
                      onChange={(e) => setFormData({
                        ...formData,
                        dimensions: {...formData.dimensions, width: parseFloat(e.target.value)}
                      })}
                      required
                    />
                  </div>
                  <div>
                    <label className="block text-sm font-medium mb-1">Height</label>
                    <Input
                      type="number"
                      value={formData.dimensions.height}
                      onChange={(e) => setFormData({
                        ...formData,
                        dimensions: {...formData.dimensions, height: parseFloat(e.target.value)}
                      })}
                      required
                    />
                  </div>
                </div>
              </div>

              {/* Options */}
              <div className="flex gap-4">
                <div className="flex items-center gap-2">
                  <input
                    type="checkbox"
                    id="insurance_required"
                    checked={formData.insurance_required}
                    onChange={(e) => setFormData({...formData, insurance_required: e.target.checked})}
                  />
                  <label htmlFor="insurance_required" className="text-sm">Insurance Required</label>
                </div>
                <div className="flex items-center gap-2">
                  <input
                    type="checkbox"
                    id="signature_required"
                    checked={formData.signature_required}
                    onChange={(e) => setFormData({...formData, signature_required: e.target.checked})}
                  />
                  <label htmlFor="signature_required" className="text-sm">Signature Required</label>
                </div>
              </div>

              <Button type="submit" disabled={loading}>
                {loading ? 'Creating...' : 'Create Shipment'}
              </Button>
            </form>
          )}

          {/* Shipments List */}
          <div className="space-y-4">
            {loading ? (
              <div className="text-center py-8">Loading...</div>
            ) : shipments.length === 0 ? (
              <div className="text-center py-8 text-gray-500">No shipments found</div>
            ) : (
              shipments.map((shipment) => (
                <div key={shipment.id} className="border rounded-lg p-4">
                  <div className="flex justify-between items-start">
                    <div className="flex-1">
                      <div className="flex items-center gap-2 mb-2">
                        <h3 className="font-semibold">
                          Shipment #{shipment.shipment_number}
                        </h3>
                        <Badge className={getStatusColor(shipment.status)}>
                          {shipment.status.toUpperCase()}
                        </Badge>
                        {shipment.tracking_number && (
                          <Badge variant="outline">
                            {shipment.tracking_number}
                          </Badge>
                        )}
                      </div>
                      
                      <div className="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm text-gray-600 mb-2">
                        <div>
                          <span className="font-medium">Carrier:</span> {shipment.carrier.name}
                        </div>
                        <div>
                          <span className="font-medium">Service:</span> {shipment.service_level}
                        </div>
                        <div>
                          <span className="font-medium">Weight:</span> {shipment.weight}kg
                        </div>
                        <div>
                          <span className="font-medium">Value:</span> ${shipment.declared_value}
                        </div>
                      </div>
                      
                      <div className="text-sm text-gray-600 mb-2">
                        <span className="font-medium">Ship To:</span> {shipment.ship_to_address.street}, {shipment.ship_to_address.city}, {shipment.ship_to_address.state} {shipment.ship_to_address.postal_code}
                      </div>
                      
                      <div className="flex gap-4 text-xs text-gray-500">
                        <span>Ship Date: {new Date(shipment.ship_date).toLocaleDateString()}</span>
                        {shipment.actual_ship_date && (
                          <span>Shipped: {new Date(shipment.actual_ship_date).toLocaleDateString()}</span>
                        )}
                        <span>Orders: {shipment.orders.length}</span>
                      </div>
                    </div>
                    
                    <div className="flex gap-2">
                      {shipment.status === 'ready_to_ship' && (
                        <Button 
                          size="sm" 
                          onClick={() => handleShipShipment(shipment.id)}
                          className="bg-blue-600 hover:bg-blue-700"
                        >
                          <Send className="h-4 w-4 mr-1" />
                          Ship
                        </Button>
                      )}
                      
                      <Button size="sm" variant="outline">
                        <Eye className="h-4 w-4 mr-1" />
                        Details
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

export default ShipmentManagement;