import React, { useState, useEffect } from 'react';
import {
  Card,
  Table,
  Button,
  Space,
  Tag,
  Modal,
  Form,
  Input,
  Select,
  InputNumber,
  message,
  Tabs,
  Row,
  Col,
  Statistic,
  Progress,
  Descriptions,
  Badge
} from 'antd';
import {
  PackageOutlined,
  PlusOutlined,
  EditOutlined,
  CheckCircleOutlined,
  ClockCircleOutlined,
  WarningOutlined,
  BarChartOutlined
} from '@ant-design/icons';
import { packingService } from '../../services/outbound/outboundService';
import {
  PackingStation,
  CartonType,
  PackOrder,
  PackedCarton,
  CreatePackingStationForm,
  CreatePackOrderForm
} from '../../type/outbound';

const { TabPane } = Tabs;
const { Option } = Select;

const PackingOperations: React.FC = () => {
  const [loading, setLoading] = useState(false);
  const [packingStations, setPackingStations] = useState<PackingStation[]>([]);
  const [cartonTypes, setCartonTypes] = useState<CartonType[]>([]);
  const [packOrders, setPackOrders] = useState<PackOrder[]>([]);
  const [packedCartons, setPackedCartons] = useState<PackedCarton[]>([]);
  
  // Modal states
  const [stationModalVisible, setStationModalVisible] = useState(false);
  const [packOrderModalVisible, setPackOrderModalVisible] = useState(false);
  const [editingStation, setEditingStation] = useState<PackingStation | null>(null);
  
  // Forms
  const [stationForm] = Form.useForm();
  const [packOrderForm] = Form.useForm();

  useEffect(() => {
    loadData();
  }, []);

  const loadData = async () => {
    setLoading(true);
    try {
      const [stationsRes, cartonsRes, ordersRes] = await Promise.all([
        packingService.getPackingStations(),
        packingService.getCartonTypes(),
        packingService.getPendingPackOrders()
      ]);

      if (stationsRes.success) setPackingStations(stationsRes.data);
      if (cartonsRes.success) setCartonTypes(cartonsRes.data);
      if (ordersRes.success) setPackOrders(ordersRes.data);
    } catch (error) {
      message.error('Failed to load packing data');
    } finally {
      setLoading(false);
    }
  };

  const handleCreateStation = async (values: CreatePackingStationForm) => {
    try {
      const response = await packingService.createPackingStation(values);
      if (response.success) {
        message.success('Packing station created successfully');
        setStationModalVisible(false);
        stationForm.resetFields();
        loadData();
      }
    } catch (error) {
      message.error('Failed to create packing station');
    }
  };

  const handleUpdateStation = async (values: CreatePackingStationForm) => {
    if (!editingStation) return;
    
    try {
      const response = await packingService.updatePackingStation(editingStation.id, values);
      if (response.success) {
        message.success('Packing station updated successfully');
        setStationModalVisible(false);
        setEditingStation(null);
        stationForm.resetFields();
        loadData();
      }
    } catch (error) {
      message.error('Failed to update packing station');
    }
  };

  const handleCreatePackOrder = async (values: CreatePackOrderForm) => {
    try {
      const response = await packingService.createPackOrder(values);
      if (response.success) {
        message.success('Pack order created successfully');
        setPackOrderModalVisible(false);
        packOrderForm.resetFields();
        loadData();
      }
    } catch (error) {
      message.error('Failed to create pack order');
    }
  };

  const handleStartPacking = async (orderId: number) => {
    try {
      const response = await packingService.startPacking(orderId);
      if (response.success) {
        message.success('Packing started successfully');
        loadData();
      }
    } catch (error) {
      message.error('Failed to start packing');
    }
  };

  const openStationModal = (station?: PackingStation) => {
    if (station) {
      setEditingStation(station);
      stationForm.setFieldsValue(station);
    } else {
      setEditingStation(null);
      stationForm.resetFields();
    }
    setStationModalVisible(true);
  };

  const stationColumns = [
    {
      title: 'Station Code',
      dataIndex: 'station_code',
      key: 'station_code',
    },
    {
      title: 'Station Name',
      dataIndex: 'station_name',
      key: 'station_name',
    },
    {
      title: 'Type',
      dataIndex: 'station_type',
      key: 'station_type',
      render: (type: string) => <Tag color="blue">{type.replace('_', ' ').toUpperCase()}</Tag>,
    },
    {
      title: 'Status',
      dataIndex: 'station_status',
      key: 'station_status',
      render: (status: string) => {
        const color = status === 'active' ? 'green' : status === 'inactive' ? 'red' : 'orange';
        return <Tag color={color}>{status.toUpperCase()}</Tag>;
      },
    },
    {
      title: 'Assigned To',
      dataIndex: ['employee', 'name'],
      key: 'assigned_to',
      render: (name: string) => name || 'Unassigned',
    },
    {
      title: 'Max Weight (kg)',
      dataIndex: 'max_weight_kg',
      key: 'max_weight_kg',
    },
    {
      title: 'Automated',
      dataIndex: 'is_automated',
      key: 'is_automated',
      render: (automated: boolean) => automated ? <Badge status="success" text="Yes" /> : <Badge status="default" text="No" />,
    },
    {
      title: 'Actions',
      key: 'actions',
      render: (_, record: PackingStation) => (
        <Space>
          <Button size="small" icon={<EditOutlined />} onClick={() => openStationModal(record)}>
            Edit
          </Button>
        </Space>
      ),
    },
  ];

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
        const colorMap: Record<string, string> = {
          pending: 'orange',
          assigned: 'blue',
          in_progress: 'purple',
          packed: 'green',
          verified: 'cyan',
          cancelled: 'red',
        };
        return <Tag color={colorMap[status]}>{status.replace('_', ' ').toUpperCase()}</Tag>;
      },
    },
    {
      title: 'Items',
      key: 'items',
      render: (_, record: PackOrder) => `${record.packed_items}/${record.total_items}`,
    },
    {
      title: 'Station',
      dataIndex: ['packingStation', 'station_name'],
      key: 'station',
    },
    {
      title: 'Assigned To',
      dataIndex: ['employee', 'name'],
      key: 'assigned_to',
      render: (name: string) => name || 'Unassigned',
    },
    {
      title: 'Actions',
      key: 'actions',
      render: (_, record: PackOrder) => (
        <Space>
          {record.pack_status === 'pending' && (
            <Button
              size="small"
              type="primary"
              icon={<CheckCircleOutlined />}
              onClick={() => handleStartPacking(record.id)}
            >
              Start
            </Button>
          )}
        </Space>
      ),
    },
  ];

  const cartonColumns = [
    {
      title: 'Carton Code',
      dataIndex: 'carton_code',
      key: 'carton_code',
    },
    {
      title: 'Name',
      dataIndex: 'carton_name',
      key: 'carton_name',
    },
    {
      title: 'Dimensions (L×W×H cm)',
      key: 'dimensions',
      render: (_, record: CartonType) => 
        `${record.length_cm}×${record.width_cm}×${record.height_cm}`,
    },
    {
      title: 'Max Weight (kg)',
      dataIndex: 'max_weight_kg',
      key: 'max_weight_kg',
    },
    {
      title: 'Volume (cm³)',
      dataIndex: 'volume_cm3',
      key: 'volume_cm3',
      render: (volume: number) => volume.toLocaleString(),
    },
    {
      title: 'Material',
      dataIndex: 'carton_material',
      key: 'carton_material',
      render: (material: string) => <Tag>{material.toUpperCase()}</Tag>,
    },
    {
      title: 'Cost per Unit',
      dataIndex: 'cost_per_unit',
      key: 'cost_per_unit',
      render: (cost: number) => `$${cost.toFixed(2)}`,
    },
    {
      title: 'Status',
      dataIndex: 'is_active',
      key: 'is_active',
      render: (active: boolean) => (
        <Badge status={active ? 'success' : 'default'} text={active ? 'Active' : 'Inactive'} />
      ),
    },
  ];

  return (
    <div style={{ padding: '24px' }}>
      <Row gutter={16} style={{ marginBottom: '24px' }}>
        <Col span={6}>
          <Card>
            <Statistic
              title="Active Stations"
              value={packingStations.filter(s => s.station_status === 'active').length}
              prefix={<PackageOutlined />}
              valueStyle={{ color: '#52c41a' }}
            />
          </Card>
        </Col>
        <Col span={6}>
          <Card>
            <Statistic
              title="Pending Orders"
              value={packOrders.filter(o => o.pack_status === 'pending').length}
              prefix={<ClockCircleOutlined />}
              valueStyle={{ color: '#faad14' }}
            />
          </Card>
        </Col>
        <Col span={6}>
          <Card>
            <Statistic
              title="In Progress"
              value={packOrders.filter(o => o.pack_status === 'in_progress').length}
              prefix={<BarChartOutlined />}
              valueStyle={{ color: '#1890ff' }}
            />
          </Card>
        </Col>
        <Col span={6}>
          <Card>
            <Statistic
              title="Completed Today"
              value={packOrders.filter(o => o.pack_status === 'packed').length}
              prefix={<CheckCircleOutlined />}
              valueStyle={{ color: '#52c41a' }}
            />
          </Card>
        </Col>
      </Row>

      <Tabs defaultActiveKey="1">
        <TabPane tab="Packing Stations" key="1">
          <Card
            title="Packing Stations"
            extra={
              <Button
                type="primary"
                icon={<PlusOutlined />}
                onClick={() => openStationModal()}
              >
                Add Station
              </Button>
            }
          >
            <Table
              columns={stationColumns}
              dataSource={packingStations}
              loading={loading}
              rowKey="id"
              pagination={{ pageSize: 10 }}
            />
          </Card>
        </TabPane>

        <TabPane tab="Pack Orders" key="2">
          <Card
            title="Pack Orders"
            extra={
              <Button
                type="primary"
                icon={<PlusOutlined />}
                onClick={() => setPackOrderModalVisible(true)}
              >
                Create Pack Order
              </Button>
            }
          >
            <Table
              columns={packOrderColumns}
              dataSource={packOrders}
              loading={loading}
              rowKey="id"
              pagination={{ pageSize: 10 }}
            />
          </Card>
        </TabPane>

        <TabPane tab="Carton Types" key="3">
          <Card title="Carton Types">
            <Table
              columns={cartonColumns}
              dataSource={cartonTypes}
              loading={loading}
              rowKey="id"
              pagination={{ pageSize: 10 }}
            />
          </Card>
        </TabPane>
      </Tabs>

      {/* Packing Station Modal */}
      <Modal
        title={editingStation ? 'Edit Packing Station' : 'Create Packing Station'}
        visible={stationModalVisible}
        onCancel={() => {
          setStationModalVisible(false);
          setEditingStation(null);
          stationForm.resetFields();
        }}
        footer={null}
        width={600}
      >
        <Form
          form={stationForm}
          layout="vertical"
          onFinish={editingStation ? handleUpdateStation : handleCreateStation}
        >
          <Form.Item
            name="station_name"
            label="Station Name"
            rules={[{ required: true, message: 'Please enter station name' }]}
          >
            <Input placeholder="Enter station name" />
          </Form.Item>

          <Row gutter={16}>
            <Col span={12}>
              <Form.Item
                name="warehouse_id"
                label="Warehouse"
                rules={[{ required: true, message: 'Please select warehouse' }]}
              >
                <Select placeholder="Select warehouse">
                  <Option value={1}>Main Warehouse</Option>
                  <Option value={2}>Secondary Warehouse</Option>
                </Select>
              </Form.Item>
            </Col>
            <Col span={12}>
              <Form.Item name="zone_id" label="Zone">
                <Select placeholder="Select zone" allowClear>
                  <Option value={1}>Zone A</Option>
                  <Option value={2}>Zone B</Option>
                </Select>
              </Form.Item>
            </Col>
          </Row>

          <Row gutter={16}>
            <Col span={12}>
              <Form.Item
                name="station_type"
                label="Station Type"
                rules={[{ required: true, message: 'Please select station type' }]}
              >
                <Select placeholder="Select station type">
                  <Option value="standard">Standard</Option>
                  <Option value="express">Express</Option>
                  <Option value="fragile">Fragile</Option>
                  <Option value="oversized">Oversized</Option>
                  <Option value="multi_order">Multi Order</Option>
                </Select>
              </Form.Item>
            </Col>
            <Col span={12}>
              <Form.Item
                name="station_status"
                label="Status"
                rules={[{ required: true, message: 'Please select status' }]}
              >
                <Select placeholder="Select status">
                  <Option value="active">Active</Option>
                  <Option value="inactive">Inactive</Option>
                  <Option value="maintenance">Maintenance</Option>
                </Select>
              </Form.Item>
            </Col>
          </Row>

          <Row gutter={16}>
            <Col span={12}>
              <Form.Item name="max_weight_kg" label="Max Weight (kg)">
                <InputNumber
                  min={0}
                  placeholder="Enter max weight"
                  style={{ width: '100%' }}
                />
              </Form.Item>
            </Col>
            <Col span={12}>
              <Form.Item name="assigned_to" label="Assigned To">
                <Select placeholder="Select employee" allowClear>
                  <Option value={1}>John Doe</Option>
                  <Option value={2}>Jane Smith</Option>
                </Select>
              </Form.Item>
            </Col>
          </Row>

          <Form.Item name="is_automated" label="Automated" valuePropName="checked">
            <Select placeholder="Select automation">
              <Option value={true}>Yes</Option>
              <Option value={false}>No</Option>
            </Select>
          </Form.Item>

          <Form.Item>
            <Space>
              <Button type="primary" htmlType="submit">
                {editingStation ? 'Update' : 'Create'}
              </Button>
              <Button onClick={() => {
                setStationModalVisible(false);
                setEditingStation(null);
                stationForm.resetFields();
              }}>
                Cancel
              </Button>
            </Space>
          </Form.Item>
        </Form>
      </Modal>

      {/* Pack Order Modal */}
      <Modal
        title="Create Pack Order"
        visible={packOrderModalVisible}
        onCancel={() => {
          setPackOrderModalVisible(false);
          packOrderForm.resetFields();
        }}
        footer={null}
        width={600}
      >
        <Form
          form={packOrderForm}
          layout="vertical"
          onFinish={handleCreatePackOrder}
        >
          <Form.Item
            name="sales_order_id"
            label="Sales Order"
            rules={[{ required: true, message: 'Please select sales order' }]}
          >
            <Select placeholder="Select sales order">
              <Option value={1}>SO-001</Option>
              <Option value={2}>SO-002</Option>
            </Select>
          </Form.Item>

          <Form.Item
            name="packing_station_id"
            label="Packing Station"
            rules={[{ required: true, message: 'Please select packing station' }]}
          >
            <Select placeholder="Select packing station">
              {packingStations.map(station => (
                <Option key={station.id} value={station.id}>
                  {station.station_name}
                </Option>
              ))}
            </Select>
          </Form.Item>

          <Row gutter={16}>
            <Col span={12}>
              <Form.Item
                name="pack_priority"
                label="Priority"
                rules={[{ required: true, message: 'Please select priority' }]}
              >
                <Select placeholder="Select priority">
                  <Option value="low">Low</Option>
                  <Option value="normal">Normal</Option>
                  <Option value="high">High</Option>
                  <Option value="urgent">Urgent</Option>
                </Select>
              </Form.Item>
            </Col>
            <Col span={12}>
              <Form.Item
                name="total_items"
                label="Total Items"
                rules={[{ required: true, message: 'Please enter total items' }]}
              >
                <InputNumber
                  min={1}
                  placeholder="Enter total items"
                  style={{ width: '100%' }}
                />
              </Form.Item>
            </Col>
          </Row>

          <Form.Item name="assigned_to" label="Assigned To">
            <Select placeholder="Select employee" allowClear>
              <Option value={1}>John Doe</Option>
              <Option value={2}>Jane Smith</Option>
            </Select>
          </Form.Item>

          <Form.Item name="estimated_time" label="Estimated Time (minutes)">
            <InputNumber
              min={1}
              placeholder="Enter estimated time"
              style={{ width: '100%' }}
            />
          </Form.Item>

          <Form.Item name="packing_notes" label="Packing Notes">
            <Input.TextArea
              rows={3}
              placeholder="Enter packing notes"
            />
          </Form.Item>

          <Form.Item>
            <Space>
              <Button type="primary" htmlType="submit">
                Create
              </Button>
              <Button onClick={() => {
                setPackOrderModalVisible(false);
                packOrderForm.resetFields();
              }}>
                Cancel
              </Button>
            </Space>
          </Form.Item>
        </Form>
      </Modal>
    </div>
  );
};

export default PackingOperations;