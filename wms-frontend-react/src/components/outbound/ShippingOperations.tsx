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
  DatePicker,
  message,
  Tabs,
  Row,
  Col,
  Statistic,
  Descriptions,
  Badge,
  Steps,
  Timeline
} from 'antd';
import {
  TruckOutlined,
  PlusOutlined,
  EditOutlined,
  CheckCircleOutlined,
  ClockCircleOutlined,
  WarningOutlined,
  ShoppingOutlined,
  CarOutlined,
  FileTextOutlined,
  PrinterOutlined
} from '@ant-design/icons';
import { shippingService } from '../../services/outbound/outboundService';
import { Shipment, CreateShipmentForm } from '../../type/outbound';
import moment from 'moment';

const { TabPane } = Tabs;
const { Option } = Select;
const { Step } = Steps;

const ShippingOperations: React.FC = () => {
  const [loading, setLoading] = useState(false);
  const [shipments, setShipments] = useState<Shipment[]>([]);
  const [selectedShipment, setSelectedShipment] = useState<Shipment | null>(null);
  
  // Modal states
  const [shipmentModalVisible, setShipmentModalVisible] = useState(false);
  const [detailModalVisible, setDetailModalVisible] = useState(false);
  const [rateShoppingModalVisible, setRateShoppingModalVisible] = useState(false);
  const [editingShipment, setEditingShipment] = useState<Shipment | null>(null);
  
  // Forms
  const [shipmentForm] = Form.useForm();
  const [rateShoppingForm] = Form.useForm();

  useEffect(() => {
    loadShipments();
  }, []);

  const loadShipments = async () => {
    setLoading(true);
    try {
      const response = await shippingService.getShipments();
      if (response.success) {
        setShipments(response.data);
      }
    } catch (error) {
      message.error('Failed to load shipments');
    } finally {
      setLoading(false);
    }
  };

  const handleCreateShipment = async (values: CreateShipmentForm) => {
    try {
      const response = await shippingService.createShipment(values);
      if (response.success) {
        message.success('Shipment created successfully');
        setShipmentModalVisible(false);
        shipmentForm.resetFields();
        loadShipments();
      }
    } catch (error) {
      message.error('Failed to create shipment');
    }
  };

  const handleUpdateShipment = async (values: Partial<CreateShipmentForm>) => {
    if (!editingShipment) return;
    
    try {
      const response = await shippingService.updateShipment(editingShipment.id, values);
      if (response.success) {
        message.success('Shipment updated successfully');
        setShipmentModalVisible(false);
        setEditingShipment(null);
        shipmentForm.resetFields();
        loadShipments();
      }
    } catch (error) {
      message.error('Failed to update shipment');
    }
  };

  const handleRateShopping = async (values: any) => {
    try {
      const response = await shippingService.performRateShopping(values);
      if (response.success) {
        message.success('Rate shopping completed');
        setRateShoppingModalVisible(false);
        rateShoppingForm.resetFields();
        // Show rate comparison results
      }
    } catch (error) {
      message.error('Failed to perform rate shopping');
    }
  };

  const handleGenerateLabel = async (shipmentId: number) => {
    try {
      const response = await shippingService.generateShippingLabel({ shipment_id: shipmentId });
      if (response.success) {
        message.success('Shipping label generated successfully');
        loadShipments();
      }
    } catch (error) {
      message.error('Failed to generate shipping label');
    }
  };

  const handleGenerateDocument = async (shipmentId: number, documentType: string) => {
    try {
      const response = await shippingService.generateShippingDocument({
        shipment_id: shipmentId,
        document_type: documentType
      });
      if (response.success) {
        message.success(`${documentType} generated successfully`);
        loadShipments();
      }
    } catch (error) {
      message.error(`Failed to generate ${documentType}`);
    }
  };

  const openShipmentModal = (shipment?: Shipment) => {
    if (shipment) {
      setEditingShipment(shipment);
      shipmentForm.setFieldsValue({
        ...shipment,
        ship_date: moment(shipment.ship_date),
        expected_delivery_date: shipment.expected_delivery_date ? moment(shipment.expected_delivery_date) : null,
      });
    } else {
      setEditingShipment(null);
      shipmentForm.resetFields();
    }
    setShipmentModalVisible(true);
  };

  const showShipmentDetails = (shipment: Shipment) => {
    setSelectedShipment(shipment);
    setDetailModalVisible(true);
  };

  const getStatusStep = (status: string) => {
    const statusMap: Record<string, number> = {
      planned: 0,
      ready: 1,
      picked_up: 2,
      in_transit: 3,
      delivered: 4,
      exception: -1,
    };
    return statusMap[status] || 0;
  };

  const shipmentColumns = [
    {
      title: 'Shipment Number',
      dataIndex: 'shipment_number',
      key: 'shipment_number',
      render: (text: string, record: Shipment) => (
        <Button type="link" onClick={() => showShipmentDetails(record)}>
          {text}
        </Button>
      ),
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
      title: 'Service Level',
      dataIndex: 'service_level',
      key: 'service_level',
      render: (service: string) => <Tag color="blue">{service}</Tag>,
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
      title: 'Weight (kg)',
      dataIndex: 'total_weight_kg',
      key: 'total_weight_kg',
      render: (weight: number) => weight.toFixed(2),
    },
    {
      title: 'Cartons',
      dataIndex: 'total_cartons',
      key: 'total_cartons',
    },
    {
      title: 'Ship Date',
      dataIndex: 'ship_date',
      key: 'ship_date',
      render: (date: string) => moment(date).format('YYYY-MM-DD'),
    },
    {
      title: 'Tracking',
      dataIndex: 'tracking_number',
      key: 'tracking_number',
      render: (tracking: string) => tracking || 'Not assigned',
    },
    {
      title: 'Actions',
      key: 'actions',
      render: (_, record: Shipment) => (
        <Space>
          <Button size="small" icon={<EditOutlined />} onClick={() => openShipmentModal(record)}>
            Edit
          </Button>
          <Button size="small" icon={<PrinterOutlined />} onClick={() => handleGenerateLabel(record.id)}>
            Label
          </Button>
          <Button size="small" icon={<FileTextOutlined />} onClick={() => handleGenerateDocument(record.id, 'packing_slip')}>
            Docs
          </Button>
        </Space>
      ),
    },
  ];

  const getShipmentStats = () => {
    return {
      total: shipments.length,
      planned: shipments.filter(s => s.shipment_status === 'planned').length,
      ready: shipments.filter(s => s.shipment_status === 'ready').length,
      inTransit: shipments.filter(s => s.shipment_status === 'in_transit').length,
      delivered: shipments.filter(s => s.shipment_status === 'delivered').length,
      exceptions: shipments.filter(s => s.shipment_status === 'exception').length,
    };
  };

  const stats = getShipmentStats();

  return (
    <div style={{ padding: '24px' }}>
      <Row gutter={16} style={{ marginBottom: '24px' }}>
        <Col span={4}>
          <Card>
            <Statistic
              title="Total Shipments"
              value={stats.total}
              prefix={<TruckOutlined />}
              valueStyle={{ color: '#1890ff' }}
            />
          </Card>
        </Col>
        <Col span={4}>
          <Card>
            <Statistic
              title="Planned"
              value={stats.planned}
              prefix={<ClockCircleOutlined />}
              valueStyle={{ color: '#faad14' }}
            />
          </Card>
        </Col>
        <Col span={4}>
          <Card>
            <Statistic
              title="Ready"
              value={stats.ready}
              prefix={<CheckCircleOutlined />}
              valueStyle={{ color: '#52c41a' }}
            />
          </Card>
        </Col>
        <Col span={4}>
          <Card>
            <Statistic
              title="In Transit"
              value={stats.inTransit}
              prefix={<CarOutlined />}
              valueStyle={{ color: '#722ed1' }}
            />
          </Card>
        </Col>
        <Col span={4}>
          <Card>
            <Statistic
              title="Delivered"
              value={stats.delivered}
              prefix={<CheckCircleOutlined />}
              valueStyle={{ color: '#52c41a' }}
            />
          </Card>
        </Col>
        <Col span={4}>
          <Card>
            <Statistic
              title="Exceptions"
              value={stats.exceptions}
              prefix={<WarningOutlined />}
              valueStyle={{ color: '#ff4d4f' }}
            />
          </Card>
        </Col>
      </Row>

      <Card
        title="Shipments"
        extra={
          <Space>
            <Button
              icon={<ShoppingOutlined />}
              onClick={() => setRateShoppingModalVisible(true)}
            >
              Rate Shopping
            </Button>
            <Button
              type="primary"
              icon={<PlusOutlined />}
              onClick={() => openShipmentModal()}
            >
              Create Shipment
            </Button>
          </Space>
        }
      >
        <Table
          columns={shipmentColumns}
          dataSource={shipments}
          loading={loading}
          rowKey="id"
          pagination={{ pageSize: 10 }}
        />
      </Card>

      {/* Shipment Modal */}
      <Modal
        title={editingShipment ? 'Edit Shipment' : 'Create Shipment'}
        visible={shipmentModalVisible}
        onCancel={() => {
          setShipmentModalVisible(false);
          setEditingShipment(null);
          shipmentForm.resetFields();
        }}
        footer={null}
        width={800}
      >
        <Form
          form={shipmentForm}
          layout="vertical"
          onFinish={editingShipment ? handleUpdateShipment : handleCreateShipment}
        >
          <Row gutter={16}>
            <Col span={12}>
              <Form.Item
                name="customer_id"
                label="Customer"
                rules={[{ required: true, message: 'Please select customer' }]}
              >
                <Select placeholder="Select customer">
                  <Option value={1}>Customer A</Option>
                  <Option value={2}>Customer B</Option>
                </Select>
              </Form.Item>
            </Col>
            <Col span={12}>
              <Form.Item
                name="shipping_carrier_id"
                label="Shipping Carrier"
                rules={[{ required: true, message: 'Please select carrier' }]}
              >
                <Select placeholder="Select carrier">
                  <Option value={1}>FedEx</Option>
                  <Option value={2}>UPS</Option>
                  <Option value={3}>DHL</Option>
                </Select>
              </Form.Item>
            </Col>
          </Row>

          <Row gutter={16}>
            <Col span={12}>
              <Form.Item
                name="service_level"
                label="Service Level"
                rules={[{ required: true, message: 'Please enter service level' }]}
              >
                <Select placeholder="Select service level">
                  <Option value="standard">Standard</Option>
                  <Option value="express">Express</Option>
                  <Option value="overnight">Overnight</Option>
                </Select>
              </Form.Item>
            </Col>
            <Col span={12}>
              <Form.Item
                name="shipment_type"
                label="Shipment Type"
                rules={[{ required: true, message: 'Please select shipment type' }]}
              >
                <Select placeholder="Select shipment type">
                  <Option value="standard">Standard</Option>
                  <Option value="express">Express</Option>
                  <Option value="freight">Freight</Option>
                  <Option value="ltl">LTL</Option>
                  <Option value="parcel">Parcel</Option>
                </Select>
              </Form.Item>
            </Col>
          </Row>

          <Row gutter={16}>
            <Col span={8}>
              <Form.Item
                name="total_weight_kg"
                label="Total Weight (kg)"
                rules={[{ required: true, message: 'Please enter weight' }]}
              >
                <InputNumber
                  min={0}
                  step={0.1}
                  placeholder="Enter weight"
                  style={{ width: '100%' }}
                />
              </Form.Item>
            </Col>
            <Col span={8}>
              <Form.Item
                name="total_volume_cm3"
                label="Total Volume (cm³)"
                rules={[{ required: true, message: 'Please enter volume' }]}
              >
                <InputNumber
                  min={0}
                  placeholder="Enter volume"
                  style={{ width: '100%' }}
                />
              </Form.Item>
            </Col>
            <Col span={8}>
              <Form.Item
                name="total_cartons"
                label="Total Cartons"
                rules={[{ required: true, message: 'Please enter carton count' }]}
              >
                <InputNumber
                  min={1}
                  placeholder="Enter carton count"
                  style={{ width: '100%' }}
                />
              </Form.Item>
            </Col>
          </Row>

          <Row gutter={16}>
            <Col span={12}>
              <Form.Item
                name="ship_date"
                label="Ship Date"
                rules={[{ required: true, message: 'Please select ship date' }]}
              >
                <DatePicker style={{ width: '100%' }} />
              </Form.Item>
            </Col>
            <Col span={12}>
              <Form.Item name="expected_delivery_date" label="Expected Delivery Date">
                <DatePicker style={{ width: '100%' }} />
              </Form.Item>
            </Col>
          </Row>

          <Form.Item name="tracking_number" label="Tracking Number">
            <Input placeholder="Enter tracking number" />
          </Form.Item>

          <Form.Item name="shipping_notes" label="Shipping Notes">
            <Input.TextArea rows={3} placeholder="Enter shipping notes" />
          </Form.Item>

          <Form.Item>
            <Space>
              <Button type="primary" htmlType="submit">
                {editingShipment ? 'Update' : 'Create'}
              </Button>
              <Button onClick={() => {
                setShipmentModalVisible(false);
                setEditingShipment(null);
                shipmentForm.resetFields();
              }}>
                Cancel
              </Button>
            </Space>
          </Form.Item>
        </Form>
      </Modal>

      {/* Shipment Details Modal */}
      <Modal
        title="Shipment Details"
        visible={detailModalVisible}
        onCancel={() => setDetailModalVisible(false)}
        footer={null}
        width={800}
      >
        {selectedShipment && (
          <div>
            <Steps current={getStatusStep(selectedShipment.shipment_status)} style={{ marginBottom: '24px' }}>
              <Step title="Planned" />
              <Step title="Ready" />
              <Step title="Picked Up" />
              <Step title="In Transit" />
              <Step title="Delivered" />
            </Steps>

            <Descriptions bordered column={2}>
              <Descriptions.Item label="Shipment Number">
                {selectedShipment.shipment_number}
              </Descriptions.Item>
              <Descriptions.Item label="Status">
                <Badge
                  status={selectedShipment.shipment_status === 'delivered' ? 'success' : 'processing'}
                  text={selectedShipment.shipment_status.replace('_', ' ').toUpperCase()}
                />
              </Descriptions.Item>
              <Descriptions.Item label="Customer">
                {selectedShipment.customer?.name || 'N/A'}
              </Descriptions.Item>
              <Descriptions.Item label="Carrier">
                {selectedShipment.carrier?.name || 'N/A'}
              </Descriptions.Item>
              <Descriptions.Item label="Service Level">
                {selectedShipment.service_level}
              </Descriptions.Item>
              <Descriptions.Item label="Shipment Type">
                {selectedShipment.shipment_type}
              </Descriptions.Item>
              <Descriptions.Item label="Weight">
                {selectedShipment.total_weight_kg} kg
              </Descriptions.Item>
              <Descriptions.Item label="Volume">
                {selectedShipment.total_volume_cm3.toLocaleString()} cm³
              </Descriptions.Item>
              <Descriptions.Item label="Cartons">
                {selectedShipment.total_cartons}
              </Descriptions.Item>
              <Descriptions.Item label="Tracking Number">
                {selectedShipment.tracking_number || 'Not assigned'}
              </Descriptions.Item>
              <Descriptions.Item label="Ship Date">
                {moment(selectedShipment.ship_date).format('YYYY-MM-DD')}
              </Descriptions.Item>
              <Descriptions.Item label="Expected Delivery">
                {selectedShipment.expected_delivery_date
                  ? moment(selectedShipment.expected_delivery_date).format('YYYY-MM-DD')
                  : 'N/A'}
              </Descriptions.Item>
              <Descriptions.Item label="Shipping Cost" span={2}>
                ${selectedShipment.shipping_cost?.toFixed(2) || 'N/A'}
              </Descriptions.Item>
              <Descriptions.Item label="Notes" span={2}>
                {selectedShipment.shipping_notes || 'No notes'}
              </Descriptions.Item>
            </Descriptions>

            {selectedShipment.shipment_status !== 'planned' && (
              <div style={{ marginTop: '24px' }}>
                <h4>Tracking Timeline</h4>
                <Timeline>
                  <Timeline.Item color="green">
                    Shipment created - {moment(selectedShipment.created_at).format('YYYY-MM-DD HH:mm')}
                  </Timeline.Item>
                  {selectedShipment.shipment_status !== 'planned' && (
                    <Timeline.Item color="blue">
                      Ready for pickup - {moment(selectedShipment.ship_date).format('YYYY-MM-DD')}
                    </Timeline.Item>
                  )}
                  {['picked_up', 'in_transit', 'delivered'].includes(selectedShipment.shipment_status) && (
                    <Timeline.Item color="orange">
                      Picked up by carrier
                    </Timeline.Item>
                  )}
                  {['in_transit', 'delivered'].includes(selectedShipment.shipment_status) && (
                    <Timeline.Item color="purple">
                      In transit
                    </Timeline.Item>
                  )}
                  {selectedShipment.shipment_status === 'delivered' && (
                    <Timeline.Item color="green">
                      Delivered - {selectedShipment.actual_delivery_date
                        ? moment(selectedShipment.actual_delivery_date).format('YYYY-MM-DD HH:mm')
                        : 'Date not recorded'}
                    </Timeline.Item>
                  )}
                </Timeline>
              </div>
            )}
          </div>
        )}
      </Modal>

      {/* Rate Shopping Modal */}
      <Modal
        title="Rate Shopping"
        visible={rateShoppingModalVisible}
        onCancel={() => {
          setRateShoppingModalVisible(false);
          rateShoppingForm.resetFields();
        }}
        footer={null}
        width={600}
      >
        <Form
          form={rateShoppingForm}
          layout="vertical"
          onFinish={handleRateShopping}
        >
          <Row gutter={16}>
            <Col span={12}>
              <Form.Item
                name="origin_zip"
                label="Origin ZIP"
                rules={[{ required: true, message: 'Please enter origin ZIP' }]}
              >
                <Input placeholder="Enter origin ZIP" />
              </Form.Item>
            </Col>
            <Col span={12}>
              <Form.Item
                name="destination_zip"
                label="Destination ZIP"
                rules={[{ required: true, message: 'Please enter destination ZIP' }]}
              >
                <Input placeholder="Enter destination ZIP" />
              </Form.Item>
            </Col>
          </Row>

          <Row gutter={16}>
            <Col span={12}>
              <Form.Item
                name="weight_kg"
                label="Weight (kg)"
                rules={[{ required: true, message: 'Please enter weight' }]}
              >
                <InputNumber
                  min={0}
                  step={0.1}
                  placeholder="Enter weight"
                  style={{ width: '100%' }}
                />
              </Form.Item>
            </Col>
            <Col span={12}>
              <Form.Item
                name="volume_cm3"
                label="Volume (cm³)"
                rules={[{ required: true, message: 'Please enter volume' }]}
              >
                <InputNumber
                  min={0}
                  placeholder="Enter volume"
                  style={{ width: '100%' }}
                />
              </Form.Item>
            </Col>
          </Row>

          <Form.Item
            name="service_types"
            label="Service Types"
            rules={[{ required: true, message: 'Please select service types' }]}
          >
            <Select mode="multiple" placeholder="Select service types">
              <Option value="standard">Standard</Option>
              <Option value="express">Express</Option>
              <Option value="overnight">Overnight</Option>
              <Option value="ground">Ground</Option>
            </Select>
          </Form.Item>

          <Form.Item>
            <Space>
              <Button type="primary" htmlType="submit">
                Get Rates
              </Button>
              <Button onClick={() => {
                setRateShoppingModalVisible(false);
                rateShoppingForm.resetFields();
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

export default ShippingOperations;