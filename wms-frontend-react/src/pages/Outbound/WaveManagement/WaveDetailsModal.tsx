import React, { useState, useEffect } from 'react';
import {
  Modal,
  ModalHeader,
  ModalBody,
  ModalFooter,
  Button,
  Nav,
  NavItem,
  NavLink,
  TabContent,
  TabPane,
  Row,
  Col,
  Table,
  Badge,
  Card,
  CardHeader,
  CardBody,
  Spinner,
  Progress,
  Alert
} from 'reactstrap';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import {
  faPlay,
  faTimes,
  faUsers,
  faList,
  faChartLine,
  faPlus,
  faTrash,
  faEdit,
  faCheck
} from '@fortawesome/free-solid-svg-icons';
import { PickWave, PickWaveOrder, PickWaveItem } from '../../../type/outbound/picking';
import { waveManagementApi } from '../../../services/waveManagementApi';
import { toast } from 'react-toastify';

interface WaveDetailsModalProps {
  isOpen: boolean;
  toggle: () => void;
  wave: PickWave;
  onRefresh: () => void;
}

const WaveDetailsModal: React.FC<WaveDetailsModalProps> = ({ isOpen, toggle, wave, onRefresh }) => {
  const [activeTab, setActiveTab] = useState<string>('summary');
  const [waveOrders, setWaveOrders] = useState<PickWaveOrder[]>([]);
  const [waveItems, setWaveItems] = useState<PickWaveItem[]>([]);
  const [loading, setLoading] = useState<boolean>(false);
  const [performanceData, setPerformanceData] = useState<any>(null);

  useEffect(() => {
    if (isOpen && wave) {
      fetchWaveDetails();
    }
  }, [isOpen, wave, activeTab]);

  const fetchWaveDetails = async () => {
    setLoading(true);
    try {
      if (activeTab === 'orders' || activeTab === 'summary') {
        const ordersResponse = await waveManagementApi.getWaveOrders(wave.id);
        setWaveOrders(ordersResponse.data);
      }
      
      if (activeTab === 'items') {
        const itemsResponse = await waveManagementApi.getWaveItems(wave.id);
        setWaveItems(itemsResponse.data);
      }
      
      if (activeTab === 'performance') {
        const performanceResponse = await waveManagementApi.getWavePerformance(wave.id);
        setPerformanceData(performanceResponse.data);
      }
    } catch (error) {
      console.error('Error fetching wave details:', error);
      toast.error('Failed to load wave details');
    } finally {
      setLoading(false);
    }
  };

  const handleReleaseWave = async () => {
    try {
      await waveManagementApi.releaseWave(wave.id);
      toast.success('Wave released for picking');
      onRefresh();
      toggle();
    } catch (error) {
      console.error('Error releasing wave:', error);
      toast.error('Failed to release wave');
    }
  };

  const handleCancelWave = async () => {
    if (window.confirm('Are you sure you want to cancel this wave?')) {
      try {
        await waveManagementApi.cancelWave(wave.id);
        toast.success('Wave cancelled successfully');
        onRefresh();
        toggle();
      } catch (error) {
        console.error('Error cancelling wave:', error);
        toast.error('Failed to cancel wave');
      }
    }
  };

  const handleGeneratePickLists = async () => {
    try {
      await waveManagementApi.generatePickLists(wave.id, {
        pick_type: 'single',
        pick_method: 'discrete'
      });
      toast.success('Pick lists generated successfully');
      onRefresh();
    } catch (error) {
      console.error('Error generating pick lists:', error);
      toast.error('Failed to generate pick lists');
    }
  };

  const getStatusBadge = (status: string) => {
    switch (status) {
      case 'planned':
        return <Badge color="info">Planned</Badge>;
      case 'released':
        return <Badge color="primary">Released</Badge>;
      case 'in_progress':
        return <Badge color="warning">In Progress</Badge>;
      case 'completed':
        return <Badge color="success">Completed</Badge>;
      case 'cancelled':
        return <Badge color="danger">Cancelled</Badge>;
      default:
        return <Badge color="secondary">{status}</Badge>;
    }
  };

  const getPickStatusBadge = (status: string) => {
    switch (status) {
      case 'pending':
        return <Badge color="secondary">Pending</Badge>;
      case 'allocated':
        return <Badge color="info">Allocated</Badge>;
      case 'picked':
        return <Badge color="success">Picked</Badge>;
      case 'short_picked':
        return <Badge color="warning">Short Picked</Badge>;
      case 'exception':
        return <Badge color="danger">Exception</Badge>;
      default:
        return <Badge color="secondary">{status}</Badge>;
    }
  };

  return (
    <Modal isOpen={isOpen} toggle={toggle} size="xl">
      <ModalHeader toggle={toggle}>
        Wave Details: {wave.wave_number}
      </ModalHeader>
      <ModalBody>
        <div className="d-flex justify-content-between align-items-center mb-3">
          <div>
            <h5 className="mb-0">
              {wave.wave_type.charAt(0).toUpperCase() + wave.wave_type.slice(1)} Wave {getStatusBadge(wave.wave_status)}
            </h5>
            <small>Created: {new Date(wave.created_at).toLocaleString()}</small>
          </div>
          <div>
            {wave.wave_status === 'planned' && (
              <>
                <Button color="success" className="me-2" onClick={handleReleaseWave}>
                  <FontAwesomeIcon icon={faPlay} className="me-1" /> Release Wave
                </Button>
                <Button color="danger" onClick={handleCancelWave}>
                  <FontAwesomeIcon icon={faTimes} className="me-1" /> Cancel Wave
                </Button>
              </>
            )}
            {wave.wave_status === 'released' && (
              <Button color="primary" onClick={handleGeneratePickLists}>
                <FontAwesomeIcon icon={faList} className="me-1" /> Generate Pick Lists
              </Button>
            )}
          </div>
        </div>

        <Nav tabs className="mb-3">
          <NavItem>
            <NavLink
              className={activeTab === 'summary' ? 'active' : ''}
              onClick={() => setActiveTab('summary')}
              style={{ cursor: 'pointer' }}
            >
              Summary
            </NavLink>
          </NavItem>
          <NavItem>
            <NavLink
              className={activeTab === 'orders' ? 'active' : ''}
              onClick={() => setActiveTab('orders')}
              style={{ cursor: 'pointer' }}
            >
              Orders
            </NavLink>
          </NavItem>
          <NavItem>
            <NavLink
              className={activeTab === 'items' ? 'active' : ''}
              onClick={() => setActiveTab('items')}
              style={{ cursor: 'pointer' }}
            >
              Items
            </NavLink>
          </NavItem>
          <NavItem>
            <NavLink
              className={activeTab === 'performance' ? 'active' : ''}
              onClick={() => setActiveTab('performance')}
              style={{ cursor: 'pointer' }}
            >
              Performance
            </NavLink>
          </NavItem>
        </Nav>

        <TabContent activeTab={activeTab}>
          <TabPane tabId="summary">
            <Row>
              <Col md={6}>
                <Card className="mb-3">
                  <CardHeader>Wave Information</CardHeader>
                  <CardBody>
                    <table className="table table-sm table-borderless">
                      <tbody>
                        <tr>
                          <th style={{ width: '40%' }}>Wave Number:</th>
                          <td>{wave.wave_number}</td>
                        </tr>
                        <tr>
                          <th>Type:</th>
                          <td>{wave.wave_type.charAt(0).toUpperCase() + wave.wave_type.slice(1)}</td>
                        </tr>
                        <tr>
                          <th>Status:</th>
                          <td>{getStatusBadge(wave.wave_status)}</td>
                        </tr>
                        <tr>
                          <th>Created By:</th>
                          <td>User ID: {wave.created_by}</td>
                        </tr>
                        <tr>
                          <th>Created At:</th>
                          <td>{new Date(wave.created_at).toLocaleString()}</td>
                        </tr>
                        <tr>
                          <th>Planned Start:</th>
                          <td>{wave.planned_start_time ? new Date(wave.planned_start_time).toLocaleString() : 'Not scheduled'}</td>
                        </tr>
                        <tr>
                          <th>Planned Completion:</th>
                          <td>{wave.planned_completion_time ? new Date(wave.planned_completion_time).toLocaleString() : 'Not scheduled'}</td>
                        </tr>
                      </tbody>
                    </table>
                  </CardBody>
                </Card>
              </Col>
              <Col md={6}>
                <Card className="mb-3">
                  <CardHeader>Wave Statistics</CardHeader>
                  <CardBody>
                    <table className="table table-sm table-borderless">
                      <tbody>
                        <tr>
                          <th style={{ width: '40%' }}>Total Orders:</th>
                          <td>{wave.total_orders}</td>
                        </tr>
                        <tr>
                          <th>Total Lines:</th>
                          <td>{wave.total_lines}</td>
                        </tr>
                        <tr>
                          <th>Total Units:</th>
                          <td>{wave.total_units}</td>
                        </tr>
                        <tr>
                          <th>Assigned To:</th>
                          <td>{wave.assigned_to && wave.assigned_to.length > 0 ? wave.assigned_to.join(', ') : 'Not assigned'}</td>
                        </tr>
                        <tr>
                          <th>Actual Start:</th>
                          <td>{wave.actual_start_time ? new Date(wave.actual_start_time).toLocaleString() : 'Not started'}</td>
                        </tr>
                        <tr>
                          <th>Actual Completion:</th>
                          <td>{wave.actual_completion_time ? new Date(wave.actual_completion_time).toLocaleString() : 'Not completed'}</td>
                        </tr>
                      </tbody>
                    </table>
                  </CardBody>
                </Card>
              </Col>
            </Row>

            {loading ? (
              <div className="text-center py-3">
                <Spinner color="primary" />
              </div>
            ) : (
              <>
                <Card className="mb-3">
                  <CardHeader>Orders Summary</CardHeader>
                  <CardBody>
                    {waveOrders.length > 0 ? (
                      <Table responsive striped size="sm">
                        <thead>
                          <tr>
                            <th>Order #</th>
                            <th>Customer</th>
                            <th>Priority</th>
                            <th>Lines</th>
                            <th>Units</th>
                            <th>Ship By</th>
                          </tr>
                        </thead>
                        <tbody>
                          {waveOrders.slice(0, 5).map((order) => (
                            <tr key={order.id}>
                              <td>{order.sales_order_number}</td>
                              <td>{order.customer_name}</td>
                              <td>
                                <Badge color={
                                  order.order_priority === 'urgent' ? 'danger' :
                                  order.order_priority === 'high' ? 'warning' :
                                  order.order_priority === 'normal' ? 'info' : 'secondary'
                                }>
                                  {order.order_priority}
                                </Badge>
                              </td>
                              <td>{order.total_lines}</td>
                              <td>{order.total_units}</td>
                              <td>{new Date(order.ship_by_date).toLocaleDateString()}</td>
                            </tr>
                          ))}
                        </tbody>
                      </Table>
                    ) : (
                      <div className="text-center py-3">No orders in this wave</div>
                    )}
                    {waveOrders.length > 5 && (
                      <div className="text-center mt-2">
                        <Button color="link" size="sm" onClick={() => setActiveTab('orders')}>
                          View all {waveOrders.length} orders
                        </Button>
                      </div>
                    )}
                  </CardBody>
                </Card>

                <Card>
                  <CardHeader>Wave Progress</CardHeader>
                  <CardBody>
                    <div className="mb-3">
                      <div className="d-flex justify-content-between mb-1">
                        <span>Overall Progress</span>
                        <span>
                          {wave.wave_status === 'completed' ? '100%' : 
                           wave.wave_status === 'in_progress' ? '50%' : 
                           wave.wave_status === 'released' ? '25%' : 
                           wave.wave_status === 'planned' ? '0%' : '0%'}
                        </span>
                      </div>
                      <Progress value={
                        wave.wave_status === 'completed' ? 100 : 
                        wave.wave_status === 'in_progress' ? 50 : 
                        wave.wave_status === 'released' ? 25 : 
                        wave.wave_status === 'planned' ? 0 : 0
                      } />
                    </div>
                  </CardBody>
                </Card>
              </>
            )}
          </TabPane>

          <TabPane tabId="orders">
            {loading ? (
              <div className="text-center py-3">
                <Spinner color="primary" />
              </div>
            ) : (
              <Card>
                <CardHeader className="d-flex justify-content-between align-items-center">
                  <span>Wave Orders</span>
                  {wave.wave_status === 'planned' && (
                    <Button color="primary" size="sm">
                      <FontAwesomeIcon icon={faPlus} className="me-1" /> Add Orders
                    </Button>
                  )}
                </CardHeader>
                <CardBody>
                  {waveOrders.length > 0 ? (
                    <Table responsive striped>
                      <thead>
                        <tr>
                          <th>Order #</th>
                          <th>Customer</th>
                          <th>Order Type</th>
                          <th>Priority</th>
                          <th>Lines</th>
                          <th>Units</th>
                          <th>Ship By</th>
                          {wave.wave_status === 'planned' && <th>Actions</th>}
                        </tr>
                      </thead>
                      <tbody>
                        {waveOrders.map((order) => (
                          <tr key={order.id}>
                            <td>{order.sales_order_number}</td>
                            <td>{order.customer_name}</td>
                            <td>{order.order_type}</td>
                            <td>
                              <Badge color={
                                order.order_priority === 'urgent' ? 'danger' :
                                order.order_priority === 'high' ? 'warning' :
                                order.order_priority === 'normal' ? 'info' : 'secondary'
                              }>
                                {order.order_priority}
                              </Badge>
                            </td>
                            <td>{order.total_lines}</td>
                            <td>{order.total_units}</td>
                            <td>{new Date(order.ship_by_date).toLocaleDateString()}</td>
                            {wave.wave_status === 'planned' && (
                              <td>
                                <Button color="danger" size="sm" title="Remove from Wave">
                                  <FontAwesomeIcon icon={faTrash} />
                                </Button>
                              </td>
                            )}
                          </tr>
                        ))}
                      </tbody>
                    </Table>
                  ) : (
                    <div className="text-center py-3">No orders in this wave</div>
                  )}
                </CardBody>
              </Card>
            )}
          </TabPane>

          <TabPane tabId="items">
            {loading ? (
              <div className="text-center py-3">
                <Spinner color="primary" />
              </div>
            ) : (
              <Card>
                <CardHeader>Wave Items</CardHeader>
                <CardBody>
                  {waveItems.length > 0 ? (
                    <Table responsive striped>
                      <thead>
                        <tr>
                          <th>Order #</th>
                          <th>Product</th>
                          <th>SKU</th>
                          <th>Location</th>
                          <th>Zone</th>
                          <th>Qty Ordered</th>
                          <th>Qty Allocated</th>
                          <th>Qty Picked</th>
                          <th>Status</th>
                        </tr>
                      </thead>
                      <tbody>
                        {waveItems.map((item) => (
                          <tr key={item.id}>
                            <td>{item.sales_order_id}</td>
                            <td>{item.product_name}</td>
                            <td>{item.product_sku}</td>
                            <td>{item.location_code || 'Not assigned'}</td>
                            <td>{item.zone_name || 'Not assigned'}</td>
                            <td>{item.quantity_ordered}</td>
                            <td>{item.quantity_allocated}</td>
                            <td>{item.quantity_picked}</td>
                            <td>{getPickStatusBadge(item.pick_status)}</td>
                          </tr>
                        ))}
                      </tbody>
                    </Table>
                  ) : (
                    <div className="text-center py-3">No items in this wave</div>
                  )}
                </CardBody>
              </Card>
            )}
          </TabPane>

          <TabPane tabId="performance">
            {loading ? (
              <div className="text-center py-3">
                <Spinner color="primary" />
              </div>
            ) : (
              <>
                {wave.wave_status === 'in_progress' || wave.wave_status === 'completed' ? (
                  <div>
                    <Row>
                      <Col md={6}>
                        <Card className="mb-3">
                          <CardHeader>Picking Progress</CardHeader>
                          <CardBody>
                            <div className="mb-3">
                              <div className="d-flex justify-content-between mb-1">
                                <span>Orders Picked</span>
                                <span>
                                  {performanceData ? 
                                    `${performanceData.orders_picked}/${performanceData.total_orders} (${Math.round(performanceData.orders_picked / performanceData.total_orders * 100)}%)` : 
                                    '0/0 (0%)'}
                                </span>
                              </div>
                              <Progress value={performanceData ? Math.round(performanceData.orders_picked / performanceData.total_orders * 100) : 0} />
                            </div>
                            <div className="mb-3">
                              <div className="d-flex justify-content-between mb-1">
                                <span>Lines Picked</span>
                                <span>
                                  {performanceData ? 
                                    `${performanceData.lines_picked}/${performanceData.total_lines} (${Math.round(performanceData.lines_picked / performanceData.total_lines * 100)}%)` : 
                                    '0/0 (0%)'}
                                </span>
                              </div>
                              <Progress value={performanceData ? Math.round(performanceData.lines_picked / performanceData.total_lines * 100) : 0} />
                            </div>
                            <div className="mb-3">
                              <div className="d-flex justify-content-between mb-1">
                                <span>Units Picked</span>
                                <span>
                                  {performanceData ? 
                                    `${performanceData.units_picked}/${performanceData.total_units} (${Math.round(performanceData.units_picked / performanceData.total_units * 100)}%)` : 
                                    '0/0 (0%)'}
                                </span>
                              </div>
                              <Progress value={performanceData ? Math.round(performanceData.units_picked / performanceData.total_units * 100) : 0} />
                            </div>
                          </CardBody>
                        </Card>
                      </Col>
                      <Col md={6}>
                        <Card className="mb-3">
                          <CardHeader>Performance Metrics</CardHeader>
                          <CardBody>
                            <table className="table table-sm table-borderless">
                              <tbody>
                                <tr>
                                  <th style={{ width: '60%' }}>Pick Rate (units/hour):</th>
                                  <td>{performanceData ? performanceData.pick_rate_units_per_hour : 'N/A'}</td>
                                </tr>
                                <tr>
                                  <th>Average Pick Time (seconds/unit):</th>
                                  <td>{performanceData ? performanceData.avg_pick_time_seconds_per_unit : 'N/A'}</td>
                                </tr>
                                <tr>
                                  <th>Pick Accuracy:</th>
                                  <td>{performanceData ? `${performanceData.pick_accuracy}%` : 'N/A'}</td>
                                </tr>
                                <tr>
                                  <th>Exception Rate:</th>
                                  <td>{performanceData ? `${performanceData.exception_rate}%` : 'N/A'}</td>
                                </tr>
                                <tr>
                                  <th>Total Exceptions:</th>
                                  <td>{performanceData ? performanceData.total_exceptions : 'N/A'}</td>
                                </tr>
                                <tr>
                                  <th>Total Pickers:</th>
                                  <td>{performanceData ? performanceData.total_pickers : 'N/A'}</td>
                                </tr>
                              </tbody>
                            </table>
                          </CardBody>
                        </Card>
                      </Col>
                    </Row>
                    
                    {performanceData && performanceData.picker_performance && (
                      <Card>
                        <CardHeader>Picker Performance</CardHeader>
                        <CardBody>
                          <Table responsive striped>
                            <thead>
                              <tr>
                                <th>Picker</th>
                                <th>Units Picked</th>
                                <th>Lines Picked</th>
                                <th>Pick Rate</th>
                                <th>Accuracy</th>
                                <th>Exceptions</th>
                              </tr>
                            </thead>
                            <tbody>
                              {performanceData.picker_performance.map((picker: any) => (
                                <tr key={picker.employee_id}>
                                  <td>{picker.employee_name}</td>
                                  <td>{picker.units_picked}</td>
                                  <td>{picker.lines_picked}</td>
                                  <td>{picker.pick_rate_units_per_hour} units/hour</td>
                                  <td>{picker.pick_accuracy}%</td>
                                  <td>{picker.exceptions_count}</td>
                                </tr>
                              ))}
                            </tbody>
                          </Table>
                        </CardBody>
                      </Card>
                    )}
                  </div>
                ) : (
                  <Alert color="info">
                    Performance metrics will be available once the wave is in progress or completed.
                  </Alert>
                )}
              </>
            )}
          </TabPane>
        </TabContent>
      </ModalBody>
      <ModalFooter>
        <Button color="secondary" onClick={toggle}>Close</Button>
      </ModalFooter>
    </Modal>
  );
};

export default WaveDetailsModal;