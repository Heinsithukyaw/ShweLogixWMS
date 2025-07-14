import React, { useState, useEffect } from 'react';
import { Card, Row, Col, Statistic, Progress, Table, Tag, Button, Space, Tabs } from 'antd';
import {
  ShoppingCartOutlined,
  PackageOutlined,
  TruckOutlined,
  CheckCircleOutlined,
  ClockCircleOutlined,
  ExclamationCircleOutlined,
  BarChartOutlined,
  DashboardOutlined
} from '@ant-design/icons';
import { packingService, shippingService, qualityControlService } from '../../services/outbound/outboundService';
import { PackOrder, Shipment, OutboundQualityCheck } from '../../type/outbound';

const { TabPane } = Tabs;

interface DashboardStats {
  totalOrders: number;
  pendingPacking: number;
  readyToShip: number;
  shipped: number;
  qualityIssues: number;
  packingEfficiency: number;
  shippingOnTime: number;
}

const OutboundDashboard: React.FC = () => {
  const [loading, setLoading] = useState(true);
  const [stats, setStats] = useState<DashboardStats>({
    totalOrders: 0,
    pendingPacking: 0,
    readyToShip: 0,
    shipped: 0,
    qualityIssues: 0,
    packingEfficiency: 0,
    shippingOnTime: 0,
  });
  const [pendingPackOrders, setPendingPackOrders] = useState<PackOrder[]>([]);
  const [recentShipments, setRecentShipments] = useState<Shipment[]>([]);
  const [qualityAlerts, setQualityAlerts] = useState<OutboundQualityCheck[]>([]);

  useEffect(() => {
    loadDashboardData();
  }, []);

  const loadDashboardData = async () => {
    try {
      setLoading(true);
      
      // Load pending pack orders
      const packOrdersResponse = await packingService.getPendingPackOrders();
      if (packOrdersResponse.success) {
        setPendingPackOrders(packOrdersResponse.data);
      }

      // Load recent shipments
      const shipmentsResponse = await shippingService.getShipments();
      if (shipmentsResponse.success) {
        setRecentShipments(shipmentsResponse.data.slice(0, 10)); // Get latest 10
      }

      // Load quality exceptions
      const qualityResponse = await qualityControlService.getQualityExceptions();
      if (qualityResponse.success) {
        setQualityAlerts(qualityResponse.data.slice(0, 5)); // Get latest 5
      }

      // Calculate stats (mock data for now)
      setStats({
        totalOrders: 156,
        pendingPacking: packOrdersResponse.success ? packOrdersResponse.data.length : 0,
        readyToShip: 23,
        shipped: 89,
        qualityIssues: qualityResponse.success ? qualityResponse.data.length : 0,
        packingEfficiency: 87.5,
        shippingOnTime: 94.2,
      });

    } catch (error) {
      console.error('Error loading dashboard data:', error);
    } finally {
      setLoading(false);
    }
  };

  const packOrderColumns = [
    {
      title: 'Order Number',
      dataIndex: 'pack_order_number',
      key: 'pack_order_number',
    },
    {
      title: 'Sales Order',
      dataIndex: ['salesOrder', 'order_number'],
      key: 'sales_order',
    },
    {
      title: 'Priority',
      dataIndex: 'pack_priority',
      key: 'pack_priority',
      render: (priority: string) => {
        const color = priority === 'urgent' ? 'red' : priority === 'high' ? 'orange' : 'blue';
        return <Tag color={color}>{priority.toUpperCase()}</Tag>;
      },
    },
    {
      title: 'Status',
      dataIndex: 'pack_status',
      key: 'pack_status',
      render: (status: string) => {
        const color = status === 'pending' ? 'orange' : status === 'in_progress' ? 'blue' : 'green';
        return <Tag color={color}>{status.replace('_', ' ').toUpperCase()}</Tag>;
      },
    },
    {
      title: 'Items',
      dataIndex: 'total_items',
      key: 'total_items',
    },
    {
      title: 'Station',
      dataIndex: ['packingStation', 'station_name'],
      key: 'station',
    },
  ];

  const shipmentColumns = [
    {
      title: 'Shipment Number',
      dataIndex: 'shipment_number',
      key: 'shipment_number',
    },
    {
      title: 'Customer',
      dataIndex: ['customer', 'name'],
      key: 'customer',
    },
    {
      title: 'Carrier',
      dataIndex: ['carrier', 'name'],
      key: 'carrier',
    },
    {
      title: 'Status',
      dataIndex: 'shipment_status',
      key: 'shipment_status',
      render: (status: string) => {
        const colorMap: Record<string, string> = {
          planned: 'blue',
          ready: 'orange',
          picked_up: 'purple',
          in_transit: 'cyan',
          delivered: 'green',
          exception: 'red',
        };
        return <Tag color={colorMap[status] || 'default'}>{status.replace('_', ' ').toUpperCase()}</Tag>;
      },
    },
    {
      title: 'Ship Date',
      dataIndex: 'ship_date',
      key: 'ship_date',
      render: (date: string) => new Date(date).toLocaleDateString(),
    },
    {
      title: 'Tracking',
      dataIndex: 'tracking_number',
      key: 'tracking_number',
    },
  ];

  return (
    <div style={{ padding: '24px' }}>
      <Row gutter={16} style={{ marginBottom: '24px' }}>
        <Col span={6}>
          <Card>
            <Statistic
              title="Total Orders"
              value={stats.totalOrders}
              prefix={<ShoppingCartOutlined />}
              valueStyle={{ color: '#1890ff' }}
            />
          </Card>
        </Col>
        <Col span={6}>
          <Card>
            <Statistic
              title="Pending Packing"
              value={stats.pendingPacking}
              prefix={<PackageOutlined />}
              valueStyle={{ color: '#faad14' }}
            />
          </Card>
        </Col>
        <Col span={6}>
          <Card>
            <Statistic
              title="Ready to Ship"
              value={stats.readyToShip}
              prefix={<TruckOutlined />}
              valueStyle={{ color: '#52c41a' }}
            />
          </Card>
        </Col>
        <Col span={6}>
          <Card>
            <Statistic
              title="Quality Issues"
              value={stats.qualityIssues}
              prefix={<ExclamationCircleOutlined />}
              valueStyle={{ color: '#ff4d4f' }}
            />
          </Card>
        </Col>
      </Row>

      <Row gutter={16} style={{ marginBottom: '24px' }}>
        <Col span={12}>
          <Card title="Performance Metrics">
            <div style={{ marginBottom: '16px' }}>
              <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: '8px' }}>
                <span>Packing Efficiency</span>
                <span>{stats.packingEfficiency}%</span>
              </div>
              <Progress percent={stats.packingEfficiency} status="active" />
            </div>
            <div>
              <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: '8px' }}>
                <span>On-Time Shipping</span>
                <span>{stats.shippingOnTime}%</span>
              </div>
              <Progress percent={stats.shippingOnTime} status="active" strokeColor="#52c41a" />
            </div>
          </Card>
        </Col>
        <Col span={12}>
          <Card title="Quick Actions">
            <Space direction="vertical" style={{ width: '100%' }}>
              <Button type="primary" icon={<PackageOutlined />} block>
                Create Pack Order
              </Button>
              <Button icon={<TruckOutlined />} block>
                Schedule Shipment
              </Button>
              <Button icon={<CheckCircleOutlined />} block>
                Quality Check
              </Button>
              <Button icon={<BarChartOutlined />} block>
                View Reports
              </Button>
            </Space>
          </Card>
        </Col>
      </Row>

      <Tabs defaultActiveKey="1">
        <TabPane tab="Pending Pack Orders" key="1">
          <Card>
            <Table
              columns={packOrderColumns}
              dataSource={pendingPackOrders}
              loading={loading}
              rowKey="id"
              pagination={{ pageSize: 10 }}
              size="small"
            />
          </Card>
        </TabPane>
        
        <TabPane tab="Recent Shipments" key="2">
          <Card>
            <Table
              columns={shipmentColumns}
              dataSource={recentShipments}
              loading={loading}
              rowKey="id"
              pagination={{ pageSize: 10 }}
              size="small"
            />
          </Card>
        </TabPane>
        
        <TabPane tab="Quality Alerts" key="3">
          <Card>
            <div style={{ padding: '16px' }}>
              {qualityAlerts.length === 0 ? (
                <div style={{ textAlign: 'center', color: '#999' }}>
                  <CheckCircleOutlined style={{ fontSize: '48px', marginBottom: '16px' }} />
                  <p>No quality issues found</p>
                </div>
              ) : (
                qualityAlerts.map((alert, index) => (
                  <div key={index} style={{ marginBottom: '16px', padding: '12px', border: '1px solid #f0f0f0', borderRadius: '6px' }}>
                    <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                      <span><strong>Check #{alert.check_number}</strong></span>
                      <Tag color={alert.overall_result === 'failed' ? 'red' : 'orange'}>
                        {alert.overall_result.toUpperCase()}
                      </Tag>
                    </div>
                    <p style={{ margin: '8px 0', color: '#666' }}>{alert.inspection_notes}</p>
                    <small style={{ color: '#999' }}>
                      Inspected: {new Date(alert.inspected_at).toLocaleString()}
                    </small>
                  </div>
                ))
              )}
            </div>
          </Card>
        </TabPane>
      </Tabs>
    </div>
  );
};

export default OutboundDashboard;