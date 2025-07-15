import React, { useState, useEffect } from 'react';
import {
  Modal,
  ModalHeader,
  ModalBody,
  ModalFooter,
  Button,
  Table,
  Badge,
  Spinner,
  Progress,
  Input,
  FormGroup,
  Label,
  Row,
  Col,
  Card,
  CardHeader,
  CardBody,
  Alert,
  Form
} from 'reactstrap';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import {
  faPlay,
  faTimes,
  faCheck,
  faExclamationTriangle,
  faBarcode,
  faUser,
  faEdit
} from '@fortawesome/free-solid-svg-icons';
import { PickTask, PickTaskItem } from '../../../type/outbound/picking';
import { pickTaskApi } from '../../../services/waveManagementApi';
import { toast } from 'react-toastify';

interface PickTaskModalProps {
  isOpen: boolean;
  toggle: () => void;
  task: PickTask;
  onAssign: (taskId: number, employeeId: number) => void;
  onStart: (taskId: number) => void;
  onComplete: (taskId: number) => void;
  onRefresh: () => void;
}

const PickTaskModal: React.FC<PickTaskModalProps> = ({
  isOpen,
  toggle,
  task,
  onAssign,
  onStart,
  onComplete,
  onRefresh
}) => {
  const [taskItems, setTaskItems] = useState<PickTaskItem[]>([]);
  const [loading, setLoading] = useState<boolean>(true);
  const [selectedEmployeeId, setSelectedEmployeeId] = useState<number>(0);
  const [currentItemIndex, setCurrentItemIndex] = useState<number>(0);
  const [scannedBarcode, setScannedBarcode] = useState<string>('');
  const [pickQuantity, setPickQuantity] = useState<number>(0);
  const [pickNotes, setPickNotes] = useState<string>('');
  const [exceptionType, setExceptionType] = useState<string>('');
  const [exceptionDescription, setExceptionDescription] = useState<string>('');
  const [showExceptionForm, setShowExceptionForm] = useState<boolean>(false);
  
  // Mock employee list
  const employees = [
    { id: 1, name: 'John Smith' },
    { id: 2, name: 'Jane Doe' },
    { id: 3, name: 'Robert Johnson' },
    { id: 4, name: 'Emily Williams' },
    { id: 5, name: 'Michael Brown' }
  ];

  useEffect(() => {
    if (isOpen && task) {
      fetchTaskItems();
    }
  }, [isOpen, task]);

  const fetchTaskItems = async () => {
    setLoading(true);
    try {
      const response = await pickTaskApi.getTaskItems(task.id);
      setTaskItems(response.data);
      
      // Set current item to the first non-picked item
      const firstNonPickedIndex = response.data.findIndex(item => item.status === 'pending');
      setCurrentItemIndex(firstNonPickedIndex >= 0 ? firstNonPickedIndex : 0);
      
      // Set default pick quantity to the quantity to pick for the current item
      if (firstNonPickedIndex >= 0) {
        setPickQuantity(response.data[firstNonPickedIndex].quantity_to_pick);
      }
    } catch (error) {
      console.error('Error fetching task items:', error);
      toast.error('Failed to load task items');
    } finally {
      setLoading(false);
    }
  };

  const handleAssignTask = () => {
    if (selectedEmployeeId > 0) {
      onAssign(task.id, selectedEmployeeId);
      toggle();
    } else {
      toast.error('Please select an employee');
    }
  };

  const handlePickItem = async () => {
    if (currentItemIndex < 0 || currentItemIndex >= taskItems.length) {
      toast.error('Invalid item selection');
      return;
    }
    
    const currentItem = taskItems[currentItemIndex];
    
    if (pickQuantity <= 0) {
      toast.error('Pick quantity must be greater than zero');
      return;
    }
    
    if (pickQuantity > currentItem.quantity_to_pick) {
      toast.error('Pick quantity cannot exceed quantity to pick');
      return;
    }
    
    try {
      await pickTaskApi.pickItem(task.id, currentItem.id, {
        quantity_picked: pickQuantity,
        confirmation_method: scannedBarcode ? 'barcode' : 'manual',
        barcode_scanned: scannedBarcode || undefined,
        notes: pickNotes || undefined
      });
      
      toast.success('Item picked successfully');
      
      // Refresh task items
      fetchTaskItems();
      onRefresh();
      
      // Clear form
      setScannedBarcode('');
      setPickNotes('');
      
      // Move to next item
      const nextNonPickedIndex = taskItems.findIndex((item, index) => 
        index > currentItemIndex && item.status === 'pending'
      );
      
      if (nextNonPickedIndex >= 0) {
        setCurrentItemIndex(nextNonPickedIndex);
        setPickQuantity(taskItems[nextNonPickedIndex].quantity_to_pick);
      } else {
        // If all items are picked, ask to complete the task
        if (taskItems.every(item => item.status !== 'pending')) {
          if (window.confirm('All items have been picked. Complete the task?')) {
            onComplete(task.id);
            toggle();
          }
        }
      }
    } catch (error) {
      console.error('Error picking item:', error);
      toast.error('Failed to pick item');
    }
  };

  const handleReportException = async () => {
    if (currentItemIndex < 0 || currentItemIndex >= taskItems.length) {
      toast.error('Invalid item selection');
      return;
    }
    
    const currentItem = taskItems[currentItemIndex];
    
    if (!exceptionType) {
      toast.error('Please select an exception type');
      return;
    }
    
    if (!exceptionDescription) {
      toast.error('Please provide an exception description');
      return;
    }
    
    try {
      await pickTaskApi.reportException(task.id, currentItem.id, {
        exception_type: exceptionType as any,
        description: exceptionDescription
      });
      
      toast.success('Exception reported successfully');
      
      // Refresh task items
      fetchTaskItems();
      onRefresh();
      
      // Clear form
      setExceptionType('');
      setExceptionDescription('');
      setShowExceptionForm(false);
    } catch (error) {
      console.error('Error reporting exception:', error);
      toast.error('Failed to report exception');
    }
  };

  const getStatusBadge = (status: string) => {
    switch (status) {
      case 'pending':
        return <Badge color="secondary">Pending</Badge>;
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

  const getCurrentItem = () => {
    if (taskItems.length > 0 && currentItemIndex >= 0 && currentItemIndex < taskItems.length) {
      return taskItems[currentItemIndex];
    }
    return null;
  };

  return (
    <Modal isOpen={isOpen} toggle={toggle} size="xl">
      <ModalHeader toggle={toggle}>
        Pick Task: {task.task_number}
      </ModalHeader>
      <ModalBody>
        <Row className="mb-3">
          <Col md={6}>
            <Card>
              <CardHeader>Task Information</CardHeader>
              <CardBody>
                <table className="table table-sm table-borderless">
                  <tbody>
                    <tr>
                      <th style={{ width: '40%' }}>Task Number:</th>
                      <td>{task.task_number}</td>
                    </tr>
                    <tr>
                      <th>Type:</th>
                      <td>{task.task_type.charAt(0).toUpperCase() + task.task_type.slice(1)}</td>
                    </tr>
                    <tr>
                      <th>Status:</th>
                      <td>
                        <Badge
                          color={
                            task.task_status === 'completed' ? 'success' :
                            task.task_status === 'in_progress' ? 'warning' :
                            task.task_status === 'assigned' ? 'info' :
                            task.task_status === 'cancelled' ? 'danger' : 'secondary'
                          }
                        >
                          {task.task_status.replace('_', ' ').charAt(0).toUpperCase() + task.task_status.replace('_', ' ').slice(1)}
                        </Badge>
                      </td>
                    </tr>
                    <tr>
                      <th>Priority:</th>
                      <td>
                        <Badge
                          color={
                            task.priority === 'urgent' ? 'danger' :
                            task.priority === 'high' ? 'warning' :
                            task.priority === 'normal' ? 'info' : 'secondary'
                          }
                        >
                          {task.priority.charAt(0).toUpperCase() + task.priority.slice(1)}
                        </Badge>
                      </td>
                    </tr>
                    <tr>
                      <th>Assigned To:</th>
                      <td>{task.employee_name || 'Unassigned'}</td>
                    </tr>
                  </tbody>
                </table>
              </CardBody>
            </Card>
          </Col>
          <Col md={6}>
            <Card>
              <CardHeader>Task Progress</CardHeader>
              <CardBody>
                <div className="mb-3">
                  <div className="d-flex justify-content-between mb-1">
                    <span>Items Picked</span>
                    <span>{task.completed_items} / {task.total_items}</span>
                  </div>
                  <Progress value={(task.completed_items / task.total_items) * 100} />
                </div>
                
                <table className="table table-sm table-borderless">
                  <tbody>
                    <tr>
                      <th style={{ width: '40%' }}>Estimated Time:</th>
                      <td>{task.estimated_time_minutes ? `${task.estimated_time_minutes} minutes` : 'Not estimated'}</td>
                    </tr>
                    <tr>
                      <th>Actual Time:</th>
                      <td>{task.actual_time_minutes ? `${task.actual_time_minutes} minutes` : 'Not completed'}</td>
                    </tr>
                    <tr>
                      <th>Started At:</th>
                      <td>{task.started_at ? new Date(task.started_at).toLocaleString() : 'Not started'}</td>
                    </tr>
                    <tr>
                      <th>Completed At:</th>
                      <td>{task.completed_at ? new Date(task.completed_at).toLocaleString() : 'Not completed'}</td>
                    </tr>
                  </tbody>
                </table>
              </CardBody>
            </Card>
          </Col>
        </Row>

        {task.task_status === 'pending' && (
          <Card className="mb-3">
            <CardHeader>Assign Task</CardHeader>
            <CardBody>
              <Row>
                <Col md={6}>
                  <FormGroup>
                    <Label for="employeeSelect">Select Employee</Label>
                    <Input
                      type="select"
                      id="employeeSelect"
                      value={selectedEmployeeId}
                      onChange={(e) => setSelectedEmployeeId(parseInt(e.target.value))}
                    >
                      <option value={0}>-- Select Employee --</option>
                      {employees.map(employee => (
                        <option key={employee.id} value={employee.id}>{employee.name}</option>
                      ))}
                    </Input>
                  </FormGroup>
                </Col>
                <Col md={6} className="d-flex align-items-end">
                  <Button color="primary" onClick={handleAssignTask}>
                    <FontAwesomeIcon icon={faUser} className="me-1" /> Assign Task
                  </Button>
                </Col>
              </Row>
            </CardBody>
          </Card>
        )}

        {task.task_status === 'assigned' && (
          <div className="mb-3 d-flex justify-content-center">
            <Button color="success" size="lg" onClick={() => onStart(task.id)}>
              <FontAwesomeIcon icon={faPlay} className="me-1" /> Start Picking
            </Button>
          </div>
        )}

        {task.task_status === 'in_progress' && (
          <>
            <Card className="mb-3">
              <CardHeader className="d-flex justify-content-between align-items-center">
                <span>Current Pick</span>
                <div>
                  <Button
                    color="warning"
                    size="sm"
                    className="me-2"
                    onClick={() => setShowExceptionForm(!showExceptionForm)}
                  >
                    <FontAwesomeIcon icon={faExclamationTriangle} className="me-1" /> 
                    {showExceptionForm ? 'Hide Exception Form' : 'Report Exception'}
                  </Button>
                  <Button
                    color="success"
                    size="sm"
                    onClick={() => onComplete(task.id)}
                    disabled={task.completed_items < task.total_items}
                  >
                    <FontAwesomeIcon icon={faCheck} className="me-1" /> Complete Task
                  </Button>
                </div>
              </CardHeader>
              <CardBody>
                {getCurrentItem() ? (
                  <div>
                    <Row className="mb-3">
                      <Col md={6}>
                        <h5>Item Details</h5>
                        <table className="table table-sm">
                          <tbody>
                            <tr>
                              <th>Product:</th>
                              <td>{getCurrentItem()?.product_name}</td>
                            </tr>
                            <tr>
                              <th>SKU:</th>
                              <td>{getCurrentItem()?.product_sku}</td>
                            </tr>
                            <tr>
                              <th>Location:</th>
                              <td>{getCurrentItem()?.location_code}</td>
                            </tr>
                            <tr>
                              <th>Zone:</th>
                              <td>{getCurrentItem()?.zone_name}</td>
                            </tr>
                            <tr>
                              <th>Quantity to Pick:</th>
                              <td>{getCurrentItem()?.quantity_to_pick}</td>
                            </tr>
                            {getCurrentItem()?.lot_number && (
                              <tr>
                                <th>Lot Number:</th>
                                <td>{getCurrentItem()?.lot_number}</td>
                              </tr>
                            )}
                            {getCurrentItem()?.serial_number && (
                              <tr>
                                <th>Serial Number:</th>
                                <td>{getCurrentItem()?.serial_number}</td>
                              </tr>
                            )}
                            {getCurrentItem()?.expiration_date && (
                              <tr>
                                <th>Expiration Date:</th>
                                <td>{new Date(getCurrentItem()?.expiration_date || '').toLocaleDateString()}</td>
                              </tr>
                            )}
                          </tbody>
                        </table>
                      </Col>
                      <Col md={6}>
                        {!showExceptionForm ? (
                          <div>
                            <h5>Pick Confirmation</h5>
                            <FormGroup className="mb-3">
                              <Label for="scannedBarcode">Scan Barcode</Label>
                              <div className="d-flex">
                                <Input
                                  type="text"
                                  id="scannedBarcode"
                                  value={scannedBarcode}
                                  onChange={(e) => setScannedBarcode(e.target.value)}
                                  placeholder="Scan or enter barcode"
                                  className="me-2"
                                />
                                <Button color="secondary">
                                  <FontAwesomeIcon icon={faBarcode} />
                                </Button>
                              </div>
                            </FormGroup>
                            <FormGroup className="mb-3">
                              <Label for="pickQuantity">Pick Quantity</Label>
                              <Input
                                type="number"
                                id="pickQuantity"
                                value={pickQuantity}
                                onChange={(e) => setPickQuantity(parseInt(e.target.value))}
                                min={1}
                                max={getCurrentItem()?.quantity_to_pick}
                              />
                            </FormGroup>
                            <FormGroup className="mb-3">
                              <Label for="pickNotes">Notes (Optional)</Label>
                              <Input
                                type="textarea"
                                id="pickNotes"
                                value={pickNotes}
                                onChange={(e) => setPickNotes(e.target.value)}
                                rows={3}
                              />
                            </FormGroup>
                            <Button color="success" onClick={handlePickItem}>
                              <FontAwesomeIcon icon={faCheck} className="me-1" /> Confirm Pick
                            </Button>
                          </div>
                        ) : (
                          <div>
                            <h5>Report Exception</h5>
                            <FormGroup className="mb-3">
                              <Label for="exceptionType">Exception Type</Label>
                              <Input
                                type="select"
                                id="exceptionType"
                                value={exceptionType}
                                onChange={(e) => setExceptionType(e.target.value)}
                              >
                                <option value="">-- Select Exception Type --</option>
                                <option value="inventory_shortage">Inventory Shortage</option>
                                <option value="location_mismatch">Location Mismatch</option>
                                <option value="product_damage">Product Damage</option>
                                <option value="barcode_issue">Barcode Issue</option>
                                <option value="other">Other</option>
                              </Input>
                            </FormGroup>
                            <FormGroup className="mb-3">
                              <Label for="exceptionDescription">Description</Label>
                              <Input
                                type="textarea"
                                id="exceptionDescription"
                                value={exceptionDescription}
                                onChange={(e) => setExceptionDescription(e.target.value)}
                                rows={4}
                              />
                            </FormGroup>
                            <div className="d-flex">
                              <Button color="danger" className="me-2" onClick={handleReportException}>
                                <FontAwesomeIcon icon={faExclamationTriangle} className="me-1" /> Submit Exception
                              </Button>
                              <Button color="secondary" onClick={() => setShowExceptionForm(false)}>
                                <FontAwesomeIcon icon={faTimes} className="me-1" /> Cancel
                              </Button>
                            </div>
                          </div>
                        )}
                      </Col>
                    </Row>
                  </div>
                ) : (
                  <Alert color="info">
                    No items to pick or all items have been picked.
                  </Alert>
                )}
              </CardBody>
            </Card>
          </>
        )}

        <Card>
          <CardHeader>Pick Items</CardHeader>
          <CardBody>
            {loading ? (
              <div className="text-center py-3">
                <Spinner color="primary" />
              </div>
            ) : (
              <Table responsive striped>
                <thead>
                  <tr>
                    <th style={{ width: '40px' }}>#</th>
                    <th>Product</th>
                    <th>SKU</th>
                    <th>Location</th>
                    <th>Qty to Pick</th>
                    <th>Qty Picked</th>
                    <th>Status</th>
                    {task.task_status === 'in_progress' && <th>Action</th>}
                  </tr>
                </thead>
                <tbody>
                  {taskItems.length > 0 ? (
                    taskItems.map((item, index) => (
                      <tr key={item.id} className={currentItemIndex === index ? 'table-primary' : ''}>
                        <td>{index + 1}</td>
                        <td>{item.product_name}</td>
                        <td>{item.product_sku}</td>
                        <td>{item.location_code}</td>
                        <td>{item.quantity_to_pick}</td>
                        <td>{item.quantity_picked}</td>
                        <td>{getStatusBadge(item.status)}</td>
                        {task.task_status === 'in_progress' && (
                          <td>
                            {item.status === 'pending' && (
                              <Button
                                color="primary"
                                size="sm"
                                onClick={() => {
                                  setCurrentItemIndex(index);
                                  setPickQuantity(item.quantity_to_pick);
                                  setShowExceptionForm(false);
                                }}
                                disabled={currentItemIndex === index}
                              >
                                <FontAwesomeIcon icon={faEdit} /> Select
                              </Button>
                            )}
                          </td>
                        )}
                      </tr>
                    ))
                  ) : (
                    <tr>
                      <td colSpan={task.task_status === 'in_progress' ? 8 : 7} className="text-center py-3">
                        No items found for this task
                      </td>
                    </tr>
                  )}
                </tbody>
              </Table>
            )}
          </CardBody>
        </Card>
      </ModalBody>
      <ModalFooter>
        <Button color="secondary" onClick={toggle}>Close</Button>
      </ModalFooter>
    </Modal>
  );
};

export default PickTaskModal;