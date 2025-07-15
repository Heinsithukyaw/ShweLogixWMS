import React, { useState, useEffect } from 'react';
import {
  Modal,
  ModalHeader,
  ModalBody,
  ModalFooter,
  Button,
  Form,
  FormGroup,
  Label,
  Input,
  Row,
  Col,
  Spinner,
  Alert,
  Card,
  CardBody,
  CardHeader,
  Table
} from 'reactstrap';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faPlus, faMinus, faSearch, faCheck, faTimes } from '@fortawesome/free-solid-svg-icons';
import { WaveCreationParams } from '../../../type/outbound/picking';
import { toast } from 'react-toastify';
import DatePicker from 'react-datepicker';
import 'react-datepicker/dist/react-datepicker.css';

interface CreateWaveModalProps {
  isOpen: boolean;
  toggle: () => void;
  onCreateWave: (waveData: WaveCreationParams) => void;
}

const CreateWaveModal: React.FC<CreateWaveModalProps> = ({ isOpen, toggle, onCreateWave }) => {
  const [activeTab, setActiveTab] = useState<string>('basic');
  const [waveType, setWaveType] = useState<'standard' | 'priority' | 'express' | 'batch' | 'zone'>('standard');
  const [orderSelectionMethod, setOrderSelectionMethod] = useState<'manual' | 'criteria'>('criteria');
  const [selectedOrderIds, setSelectedOrderIds] = useState<number[]>([]);
  const [searchOrderTerm, setSearchOrderTerm] = useState<string>('');
  const [searchResults, setSearchResults] = useState<any[]>([]);
  const [isSearching, setIsSearching] = useState<boolean>(false);
  
  // Order criteria
  const [shipByDateStart, setShipByDateStart] = useState<Date | null>(new Date());
  const [shipByDateEnd, setShipByDateEnd] = useState<Date | null>(new Date(Date.now() + 86400000)); // Tomorrow
  const [selectedOrderTypes, setSelectedOrderTypes] = useState<string[]>([]);
  const [selectedCustomers, setSelectedCustomers] = useState<number[]>([]);
  const [selectedPriorities, setSelectedPriorities] = useState<string[]>(['high', 'urgent']);
  const [selectedProductCategories, setSelectedProductCategories] = useState<string[]>([]);
  
  // Wave criteria
  const [maxOrders, setMaxOrders] = useState<number>(50);
  const [maxLines, setMaxLines] = useState<number>(200);
  const [maxUnits, setMaxUnits] = useState<number>(1000);
  const [maxVolume, setMaxVolume] = useState<number | undefined>(undefined);
  const [maxWeight, setMaxWeight] = useState<number | undefined>(undefined);
  
  // Assignment
  const [autoAssign, setAutoAssign] = useState<boolean>(false);
  const [selectedEmployees, setSelectedEmployees] = useState<number[]>([]);
  
  // Scheduling
  const [plannedStartTime, setPlannedStartTime] = useState<Date | null>(new Date());
  const [plannedCompletionTime, setPlannedCompletionTime] = useState<Date | null>(null);
  
  // Order types and customers for dropdown
  const [orderTypes, setOrderTypes] = useState<string[]>(['Standard', 'Rush', 'Backorder', 'Wholesale', 'Retail', 'E-commerce']);
  const [customers, setCustomers] = useState<{id: number, name: string}[]>([
    { id: 1, name: 'Acme Corp' },
    { id: 2, name: 'Globex Industries' },
    { id: 3, name: 'Initech LLC' },
    { id: 4, name: 'Umbrella Corporation' },
    { id: 5, name: 'Stark Industries' }
  ]);
  const [employees, setEmployees] = useState<{id: number, name: string}[]>([
    { id: 1, name: 'John Smith' },
    { id: 2, name: 'Jane Doe' },
    { id: 3, name: 'Robert Johnson' },
    { id: 4, name: 'Emily Williams' },
    { id: 5, name: 'Michael Brown' }
  ]);
  const [productCategories, setProductCategories] = useState<string[]>([
    'Electronics', 'Apparel', 'Home Goods', 'Sporting Goods', 'Automotive', 'Health & Beauty'
  ]);

  useEffect(() => {
    if (isOpen) {
      // Reset form when modal opens
      resetForm();
    }
  }, [isOpen]);

  const resetForm = () => {
    setActiveTab('basic');
    setWaveType('standard');
    setOrderSelectionMethod('criteria');
    setSelectedOrderIds([]);
    setSearchOrderTerm('');
    setSearchResults([]);
    setShipByDateStart(new Date());
    setShipByDateEnd(new Date(Date.now() + 86400000));
    setSelectedOrderTypes([]);
    setSelectedCustomers([]);
    setSelectedPriorities(['high', 'urgent']);
    setSelectedProductCategories([]);
    setMaxOrders(50);
    setMaxLines(200);
    setMaxUnits(1000);
    setMaxVolume(undefined);
    setMaxWeight(undefined);
    setAutoAssign(false);
    setSelectedEmployees([]);
    setPlannedStartTime(new Date());
    setPlannedCompletionTime(null);
  };

  const handleTabChange = (tab: string) => {
    setActiveTab(tab);
  };

  const handleSearchOrders = () => {
    setIsSearching(true);
    
    // Simulate API call to search orders
    setTimeout(() => {
      const mockResults = [
        { id: 101, order_number: 'SO-10001', customer_name: 'Acme Corp', order_date: '2025-07-14', ship_by_date: '2025-07-16', total_lines: 5, total_units: 25, priority: 'high' },
        { id: 102, order_number: 'SO-10002', customer_name: 'Globex Industries', order_date: '2025-07-14', ship_by_date: '2025-07-15', total_lines: 3, total_units: 12, priority: 'urgent' },
        { id: 103, order_number: 'SO-10003', customer_name: 'Initech LLC', order_date: '2025-07-14', ship_by_date: '2025-07-17', total_lines: 8, total_units: 40, priority: 'normal' },
        { id: 104, order_number: 'SO-10004', customer_name: 'Umbrella Corporation', order_date: '2025-07-14', ship_by_date: '2025-07-16', total_lines: 2, total_units: 10, priority: 'high' },
        { id: 105, order_number: 'SO-10005', customer_name: 'Stark Industries', order_date: '2025-07-14', ship_by_date: '2025-07-18', total_lines: 6, total_units: 30, priority: 'normal' }
      ];
      
      setSearchResults(mockResults.filter(order => 
        order.order_number.toLowerCase().includes(searchOrderTerm.toLowerCase()) ||
        order.customer_name.toLowerCase().includes(searchOrderTerm.toLowerCase())
      ));
      setIsSearching(false);
    }, 500);
  };

  const toggleOrderSelection = (orderId: number) => {
    if (selectedOrderIds.includes(orderId)) {
      setSelectedOrderIds(selectedOrderIds.filter(id => id !== orderId));
    } else {
      setSelectedOrderIds([...selectedOrderIds, orderId]);
    }
  };

  const toggleOrderType = (orderType: string) => {
    if (selectedOrderTypes.includes(orderType)) {
      setSelectedOrderTypes(selectedOrderTypes.filter(type => type !== orderType));
    } else {
      setSelectedOrderTypes([...selectedOrderTypes, orderType]);
    }
  };

  const toggleCustomer = (customerId: number) => {
    if (selectedCustomers.includes(customerId)) {
      setSelectedCustomers(selectedCustomers.filter(id => id !== customerId));
    } else {
      setSelectedCustomers([...selectedCustomers, customerId]);
    }
  };

  const togglePriority = (priority: string) => {
    if (selectedPriorities.includes(priority)) {
      setSelectedPriorities(selectedPriorities.filter(p => p !== priority));
    } else {
      setSelectedPriorities([...selectedPriorities, priority]);
    }
  };

  const toggleProductCategory = (category: string) => {
    if (selectedProductCategories.includes(category)) {
      setSelectedProductCategories(selectedProductCategories.filter(c => c !== category));
    } else {
      setSelectedProductCategories([...selectedProductCategories, category]);
    }
  };

  const toggleEmployee = (employeeId: number) => {
    if (selectedEmployees.includes(employeeId)) {
      setSelectedEmployees(selectedEmployees.filter(id => id !== employeeId));
    } else {
      setSelectedEmployees([...selectedEmployees, employeeId]);
    }
  };

  const handleSubmit = () => {
    const waveData: WaveCreationParams = {
      wave_type: waveType,
      order_ids: orderSelectionMethod === 'manual' ? selectedOrderIds : undefined,
      order_criteria: orderSelectionMethod === 'criteria' ? {
        ship_by_date_start: shipByDateStart ? shipByDateStart.toISOString() : undefined,
        ship_by_date_end: shipByDateEnd ? shipByDateEnd.toISOString() : undefined,
        order_types: selectedOrderTypes.length > 0 ? selectedOrderTypes : undefined,
        customer_ids: selectedCustomers.length > 0 ? selectedCustomers : undefined,
        priority_levels: selectedPriorities.length > 0 ? selectedPriorities : undefined,
        product_categories: selectedProductCategories.length > 0 ? selectedProductCategories : undefined
      } : undefined,
      wave_criteria: {
        max_orders: maxOrders,
        max_lines: maxLines,
        max_units: maxUnits,
        max_volume: maxVolume,
        max_weight: maxWeight
      },
      assignment: {
        auto_assign: autoAssign,
        employee_ids: selectedEmployees.length > 0 ? selectedEmployees : undefined
      },
      scheduling: {
        planned_start_time: plannedStartTime ? plannedStartTime.toISOString() : undefined,
        planned_completion_time: plannedCompletionTime ? plannedCompletionTime.toISOString() : undefined
      }
    };
    
    onCreateWave(waveData);
  };

  return (
    <Modal isOpen={isOpen} toggle={toggle} size="lg">
      <ModalHeader toggle={toggle}>Create Pick Wave</ModalHeader>
      <ModalBody>
        <div className="d-flex mb-3">
          <Button
            color={activeTab === 'basic' ? 'primary' : 'secondary'}
            onClick={() => handleTabChange('basic')}
            className="me-2"
          >
            Basic Info
          </Button>
          <Button
            color={activeTab === 'orders' ? 'primary' : 'secondary'}
            onClick={() => handleTabChange('orders')}
            className="me-2"
          >
            Order Selection
          </Button>
          <Button
            color={activeTab === 'criteria' ? 'primary' : 'secondary'}
            onClick={() => handleTabChange('criteria')}
            className="me-2"
          >
            Wave Criteria
          </Button>
          <Button
            color={activeTab === 'assignment' ? 'primary' : 'secondary'}
            onClick={() => handleTabChange('assignment')}
          >
            Assignment
          </Button>
        </div>

        {activeTab === 'basic' && (
          <div>
            <FormGroup>
              <Label for="waveType">Wave Type</Label>
              <Input
                type="select"
                id="waveType"
                value={waveType}
                onChange={(e) => setWaveType(e.target.value as any)}
              >
                <option value="standard">Standard</option>
                <option value="priority">Priority</option>
                <option value="express">Express</option>
                <option value="batch">Batch</option>
                <option value="zone">Zone</option>
              </Input>
            </FormGroup>
            
            <Row>
              <Col md={6}>
                <FormGroup>
                  <Label for="plannedStartTime">Planned Start Time</Label>
                  <DatePicker
                    selected={plannedStartTime}
                    onChange={(date) => setPlannedStartTime(date)}
                    showTimeSelect
                    timeFormat="HH:mm"
                    timeIntervals={15}
                    dateFormat="yyyy-MM-dd HH:mm"
                    className="form-control"
                    id="plannedStartTime"
                  />
                </FormGroup>
              </Col>
              <Col md={6}>
                <FormGroup>
                  <Label for="plannedCompletionTime">Planned Completion Time</Label>
                  <DatePicker
                    selected={plannedCompletionTime}
                    onChange={(date) => setPlannedCompletionTime(date)}
                    showTimeSelect
                    timeFormat="HH:mm"
                    timeIntervals={15}
                    dateFormat="yyyy-MM-dd HH:mm"
                    className="form-control"
                    id="plannedCompletionTime"
                  />
                </FormGroup>
              </Col>
            </Row>
          </div>
        )}

        {activeTab === 'orders' && (
          <div>
            <FormGroup tag="fieldset" className="mb-3">
              <legend className="col-form-label">Order Selection Method</legend>
              <FormGroup check>
                <Label check>
                  <Input
                    type="radio"
                    name="orderSelectionMethod"
                    checked={orderSelectionMethod === 'criteria'}
                    onChange={() => setOrderSelectionMethod('criteria')}
                  />{' '}
                  Select by Criteria
                </Label>
              </FormGroup>
              <FormGroup check>
                <Label check>
                  <Input
                    type="radio"
                    name="orderSelectionMethod"
                    checked={orderSelectionMethod === 'manual'}
                    onChange={() => setOrderSelectionMethod('manual')}
                  />{' '}
                  Select Orders Manually
                </Label>
              </FormGroup>
            </FormGroup>

            {orderSelectionMethod === 'manual' && (
              <div>
                <div className="d-flex mb-3">
                  <Input
                    type="text"
                    placeholder="Search orders by number or customer..."
                    value={searchOrderTerm}
                    onChange={(e) => setSearchOrderTerm(e.target.value)}
                    className="me-2"
                  />
                  <Button color="secondary" onClick={handleSearchOrders}>
                    <FontAwesomeIcon icon={faSearch} />
                  </Button>
                </div>

                {isSearching ? (
                  <div className="text-center py-3">
                    <Spinner color="primary" />
                  </div>
                ) : (
                  <Table responsive striped hover>
                    <thead>
                      <tr>
                        <th style={{ width: '40px' }}></th>
                        <th>Order #</th>
                        <th>Customer</th>
                        <th>Ship By</th>
                        <th>Lines</th>
                        <th>Units</th>
                        <th>Priority</th>
                      </tr>
                    </thead>
                    <tbody>
                      {searchResults.length > 0 ? (
                        searchResults.map((order) => (
                          <tr key={order.id}>
                            <td>
                              <Input
                                type="checkbox"
                                checked={selectedOrderIds.includes(order.id)}
                                onChange={() => toggleOrderSelection(order.id)}
                              />
                            </td>
                            <td>{order.order_number}</td>
                            <td>{order.customer_name}</td>
                            <td>{order.ship_by_date}</td>
                            <td>{order.total_lines}</td>
                            <td>{order.total_units}</td>
                            <td>
                              <span className={`badge bg-${order.priority === 'urgent' ? 'danger' : order.priority === 'high' ? 'warning' : 'info'}`}>
                                {order.priority}
                              </span>
                            </td>
                          </tr>
                        ))
                      ) : (
                        <tr>
                          <td colSpan={7} className="text-center py-3">
                            {searchOrderTerm ? 'No orders found matching your search' : 'Search for orders to add to the wave'}
                          </td>
                        </tr>
                      )}
                    </tbody>
                  </Table>
                )}

                {selectedOrderIds.length > 0 && (
                  <div className="mt-2">
                    <small>{selectedOrderIds.length} orders selected</small>
                  </div>
                )}
              </div>
            )}

            {orderSelectionMethod === 'criteria' && (
              <div>
                <Row>
                  <Col md={6}>
                    <FormGroup>
                      <Label for="shipByDateStart">Ship By Date (Start)</Label>
                      <DatePicker
                        selected={shipByDateStart}
                        onChange={(date) => setShipByDateStart(date)}
                        className="form-control"
                        id="shipByDateStart"
                      />
                    </FormGroup>
                  </Col>
                  <Col md={6}>
                    <FormGroup>
                      <Label for="shipByDateEnd">Ship By Date (End)</Label>
                      <DatePicker
                        selected={shipByDateEnd}
                        onChange={(date) => setShipByDateEnd(date)}
                        className="form-control"
                        id="shipByDateEnd"
                      />
                    </FormGroup>
                  </Col>
                </Row>

                <Row>
                  <Col md={6}>
                    <FormGroup>
                      <Label>Order Types</Label>
                      <div className="border rounded p-2" style={{ maxHeight: '150px', overflowY: 'auto' }}>
                        {orderTypes.map((type) => (
                          <FormGroup check key={type}>
                            <Label check>
                              <Input
                                type="checkbox"
                                checked={selectedOrderTypes.includes(type)}
                                onChange={() => toggleOrderType(type)}
                              />{' '}
                              {type}
                            </Label>
                          </FormGroup>
                        ))}
                      </div>
                    </FormGroup>
                  </Col>
                  <Col md={6}>
                    <FormGroup>
                      <Label>Customers</Label>
                      <div className="border rounded p-2" style={{ maxHeight: '150px', overflowY: 'auto' }}>
                        {customers.map((customer) => (
                          <FormGroup check key={customer.id}>
                            <Label check>
                              <Input
                                type="checkbox"
                                checked={selectedCustomers.includes(customer.id)}
                                onChange={() => toggleCustomer(customer.id)}
                              />{' '}
                              {customer.name}
                            </Label>
                          </FormGroup>
                        ))}
                      </div>
                    </FormGroup>
                  </Col>
                </Row>

                <Row>
                  <Col md={6}>
                    <FormGroup>
                      <Label>Priority Levels</Label>
                      <div className="border rounded p-2">
                        {['low', 'normal', 'high', 'urgent'].map((priority) => (
                          <FormGroup check key={priority}>
                            <Label check>
                              <Input
                                type="checkbox"
                                checked={selectedPriorities.includes(priority)}
                                onChange={() => togglePriority(priority)}
                              />{' '}
                              {priority.charAt(0).toUpperCase() + priority.slice(1)}
                            </Label>
                          </FormGroup>
                        ))}
                      </div>
                    </FormGroup>
                  </Col>
                  <Col md={6}>
                    <FormGroup>
                      <Label>Product Categories</Label>
                      <div className="border rounded p-2" style={{ maxHeight: '150px', overflowY: 'auto' }}>
                        {productCategories.map((category) => (
                          <FormGroup check key={category}>
                            <Label check>
                              <Input
                                type="checkbox"
                                checked={selectedProductCategories.includes(category)}
                                onChange={() => toggleProductCategory(category)}
                              />{' '}
                              {category}
                            </Label>
                          </FormGroup>
                        ))}
                      </div>
                    </FormGroup>
                  </Col>
                </Row>
              </div>
            )}
          </div>
        )}

        {activeTab === 'criteria' && (
          <div>
            <Row>
              <Col md={6}>
                <FormGroup>
                  <Label for="maxOrders">Maximum Orders</Label>
                  <Input
                    type="number"
                    id="maxOrders"
                    value={maxOrders}
                    onChange={(e) => setMaxOrders(parseInt(e.target.value))}
                    min={1}
                  />
                </FormGroup>
              </Col>
              <Col md={6}>
                <FormGroup>
                  <Label for="maxLines">Maximum Lines</Label>
                  <Input
                    type="number"
                    id="maxLines"
                    value={maxLines}
                    onChange={(e) => setMaxLines(parseInt(e.target.value))}
                    min={1}
                  />
                </FormGroup>
              </Col>
            </Row>
            <Row>
              <Col md={6}>
                <FormGroup>
                  <Label for="maxUnits">Maximum Units</Label>
                  <Input
                    type="number"
                    id="maxUnits"
                    value={maxUnits}
                    onChange={(e) => setMaxUnits(parseInt(e.target.value))}
                    min={1}
                  />
                </FormGroup>
              </Col>
              <Col md={6}>
                <FormGroup>
                  <Label for="maxVolume">Maximum Volume (cm³, optional)</Label>
                  <Input
                    type="number"
                    id="maxVolume"
                    value={maxVolume || ''}
                    onChange={(e) => setMaxVolume(e.target.value ? parseInt(e.target.value) : undefined)}
                    min={1}
                  />
                </FormGroup>
              </Col>
            </Row>
            <Row>
              <Col md={6}>
                <FormGroup>
                  <Label for="maxWeight">Maximum Weight (kg, optional)</Label>
                  <Input
                    type="number"
                    id="maxWeight"
                    value={maxWeight || ''}
                    onChange={(e) => setMaxWeight(e.target.value ? parseInt(e.target.value) : undefined)}
                    min={1}
                  />
                </FormGroup>
              </Col>
            </Row>
          </div>
        )}

        {activeTab === 'assignment' && (
          <div>
            <FormGroup check className="mb-3">
              <Label check>
                <Input
                  type="checkbox"
                  checked={autoAssign}
                  onChange={() => setAutoAssign(!autoAssign)}
                />{' '}
                Auto-assign to available employees
              </Label>
            </FormGroup>

            <FormGroup>
              <Label>Assign to Specific Employees</Label>
              <div className="border rounded p-2" style={{ maxHeight: '200px', overflowY: 'auto' }}>
                {employees.map((employee) => (
                  <FormGroup check key={employee.id}>
                    <Label check>
                      <Input
                        type="checkbox"
                        checked={selectedEmployees.includes(employee.id)}
                        onChange={() => toggleEmployee(employee.id)}
                        disabled={autoAssign}
                      />{' '}
                      {employee.name}
                    </Label>
                  </FormGroup>
                ))}
              </div>
            </FormGroup>
          </div>
        )}
      </ModalBody>
      <ModalFooter>
        <Button color="secondary" onClick={toggle}>Cancel</Button>
        <Button color="primary" onClick={handleSubmit}>Create Wave</Button>
      </ModalFooter>
    </Modal>
  );
};

export default CreateWaveModal;