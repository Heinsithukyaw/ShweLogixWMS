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
  Input,
  FormGroup,
  Label,
  Alert
} from 'reactstrap';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import {
  faPlay,
  faTimes,
  faCheck,
  faTruck,
  faBoxes,
  faOptimize,
  faEdit,
  faBarcode,
  faExclamationTriangle,
  faInfoCircle
} from '@fortawesome/free-solid-svg-icons';
import { shippingApi } from '../../../services/outboundApi';
import { toast } from 'react-toastify';

interface LoadPlanDetailsModalProps {
  isOpen: boolean;
  toggle: () => void;
  loadPlan: any;
  onOptimize: (loadId: number) => void;
  onConfirmLoading: (loadId: number, data: any) => void;
  onDispatch: (loadId: number) => void;
  onRefresh: () => void;
}

const LoadPlanDetailsModal: React.FC<LoadPlanDetailsModalProps> = ({
  isOpen,
  toggle,
  loadPlan,
  onOptimize,
  onConfirmLoading,
  onDispatch,
  onRefresh
}) => {
  const [activeTab, setActiveTab] = useState<string>('summary');
  const [shipments, setShipments] = useState<any[]>([]);
  const [loadingDetails, setLoadingDetails] = useState<any | null>(null);
  const [loading, setLoading] = useState<boolean>(true);
  const [selectedShipments, setSelectedShipments] = useState<number[]>([]);
  const [actualWeight, setActualWeight] = useState<string>('');
  const [loadingNotes, setLoadingNotes] = useState<string>('');
  const [scannedBarcode, setScannedBarcode] = useState<string>('');
  
  // Mock 3D visualization data
  const [visualizationData, setVisualizationData] = useState<any | null>(null);

  useEffect(() => {
    if (isOpen && loadPlan) {
      fetchLoadPlanDetails();
    }
  }, [isOpen, loadPlan, activeTab]);

  const fetchLoadPlanDetails = async () => {
    setLoading(true);
    try {
      // Simulate API call to fetch load plan details
      setTimeout(() => {
        // Mock shipments data
        const mockShipments = [
          { id: 201, shipment_number: 'SH-20001', customer_name: 'Acme Corp', total_weight_kg: 450, total_volume_m3: 8.5, status: 'ready', ship_date: '2025-07-16', is_loaded: false },
          { id: 202, shipment_number: 'SH-20002', customer_name: 'Globex Industries', total_weight_kg: 320, total_volume_m3: 5.2, status: 'ready', ship_date: '2025-07-16', is_loaded: false },
          { id: 203, shipment_number: 'SH-20003', customer_name: 'Initech LLC', total_weight_kg: 580, total_volume_m3: 10.8, status: 'ready', ship_date: '2025-07-16', is_loaded: false },
          { id: 204, shipment_number: 'SH-20004', customer_name: 'Umbrella Corporation', total_weight_kg: 210, total_volume_m3: 3.5, status: 'ready', ship_date: '2025-07-17', is_loaded: false },
          { id: 205, shipment_number: 'SH-20005', customer_name: 'Stark Industries', total_weight_kg: 390, total_volume_m3: 7.2, status: 'ready', ship_date: '2025-07-17', is_loaded: false }
        ];
        
        // Mock loading details
        const mockLoadingDetails = {
          dock_door: loadPlan.dock_door,
          scheduled_start: '2025-07-16 08:00:00',
          scheduled_end: '2025-07-16 10:00:00',
          actual_start: loadPlan.status === 'in_progress' || loadPlan.status === 'loaded' ? '2025-07-16 08:15:00' : null,
          actual_end: loadPlan.status === 'loaded' ? '2025-07-16 09:45:00' : null,
          loaded_by: 'John Smith',
          loading_equipment: 'Forklift #3',
          loading_sequence: [201, 203, 205, 202, 204]
        };
        
        // Mock 3D visualization data
        const mockVisualizationData = {
          container_dimensions: { length: 13.6, width: 2.4, height: 2.6 },
          items: [
            { id: 1, length: 1.2, width: 0.8, height: 1.0, weight: 450, position: { x: 0, y: 0, z: 0 } },
            { id: 2, length: 1.0, width: 1.0, height: 0.8, weight: 320, position: { x: 1.2, y: 0, z: 0 } },
            { id: 3, length: 1.5, width: 1.2, height: 1.2, weight: 580, position: { x: 2.2, y: 0, z: 0 } },
            { id: 4, length: 0.8, width: 0.6, height: 0.7, weight: 210, position: { x: 0, y: 0.8, z: 0 } },
            { id: 5, length: 1.2, width: 1.0, height: 0.9, weight: 390, position: { x: 3.7, y: 0, z: 0 } }
          ]
        };
        
        setShipments(mockShipments);
        setLoadingDetails(mockLoadingDetails);
        setVisualizationData(mockVisualizationData);
        
        // Pre-select all shipments for loading if in progress
        if (loadPlan.status === 'in_progress') {
          setSelectedShipments(mockShipments.map(shipment => shipment.id));
        }
        
        setLoading(false);
      }, 500);
    } catch (error) {
      console.error('Error fetching load plan details:', error);
      toast.error('Failed to load details');
      setLoading(false);
    }
  };

  const handleOptimizeLoad = () => {
    onOptimize(loadPlan.id);
  };

  const handleConfirmLoading = () => {
    if (selectedShipments.length === 0) {
      toast.error('Please select at least one shipment to confirm loading');
      return;
    }
    
    const data = {
      loaded_shipments: selectedShipments,
      actual_weight_kg: actualWeight ? parseFloat(actualWeight) : undefined,
      loading_notes: loadingNotes || undefined
    };
    
    onConfirmLoading(loadPlan.id, data);
    toggle();
  };

  const handleDispatchLoad = () => {
    onDispatch(loadPlan.id);
    toggle();
  };

  const toggleShipmentSelection = (shipmentId: number) => {
    if (selectedShipments.includes(shipmentId)) {
      setSelectedShipments(selectedShipments.filter(id => id !== shipmentId));
    } else {
      setSelectedShipments([...selectedShipments, shipmentId]);
    }
  };

  const handleScanBarcode = () => {
    if (!scannedBarcode) {
      toast.error('Please enter a barcode to scan');
      return;
    }
    
    // Simulate barcode scanning
    const shipmentId = shipments.find(shipment => 
      shipment.shipment_number === scannedBarcode
    )?.id;
    
    if (shipmentId) {
      if (!selectedShipments.includes(shipmentId)) {
        setSelectedShipments([...selectedShipments, shipmentId]);
        toast.success(`Shipment ${scannedBarcode} added to loading list`);
      } else {
        toast.info(`Shipment ${scannedBarcode} is already in the loading list`);
      }
    } else {
      toast.error(`No shipment found with barcode ${scannedBarcode}`);
    }
    
    setScannedBarcode('');
  };

  const getStatusBadge = (status: string) => {
    switch (status) {
      case 'planned':
        return <Badge color="info">Planned</Badge>;
      case 'in_progress':
        return <Badge color="warning">Loading</Badge>;
      case 'loaded':
        return <Badge color="primary">Loaded</Badge>;
      case 'dispatched':
        return <Badge color="success">Dispatched</Badge>;
      case 'completed':
        return <Badge color="success">Completed</Badge>;
      case 'cancelled':
        return <Badge color="danger">Cancelled</Badge>;
      default:
        return <Badge color="secondary">{status}</Badge>;
    }
  };

  return (
    <Modal isOpen={isOpen} toggle={toggle} size="xl">
      <ModalHeader toggle={toggle}>
        Load Plan: {loadPlan.load_number}
      </ModalHeader>
      <ModalBody>
        <div className="d-flex justify-content-between align-items-center mb-3">
          <div>
            <h5 className="mb-0">
              {loadPlan.shipping_carrier} - {loadPlan.vehicle_type} {getStatusBadge(loadPlan.status)}
            </h5>
            <small>Departure: {loadPlan.planned_departure_date} {loadPlan.planned_departure_time}</small>
          </div>
          <div>
            {loadPlan.status === 'planned' && (
              <Button color="primary" onClick={handleOptimizeLoad}>
                <FontAwesomeIcon icon={faOptimize} className="me-1" /> Optimize Load
              </Button>
            )}
            
            {loadPlan.status === 'in_progress' && (
              <Button color="success" onClick={handleConfirmLoading}>
                <FontAwesomeIcon icon={faCheck} className="me-1" /> Confirm Loading
              </Button>
            )}
            
            {loadPlan.status === 'loaded' && (
              <Button color="success" onClick={handleDispatchLoad}>
                <FontAwesomeIcon icon={faTruck} className="me-1" /> Dispatch Load
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
              className={activeTab === 'shipments' ? 'active' : ''}
              onClick={() => setActiveTab('shipments')}
              style={{ cursor: 'pointer' }}
            >
              Shipments
            </NavLink>
          </NavItem>
          <NavItem>
            <NavLink
              className={activeTab === 'loading' ? 'active' : ''}
              onClick={() => setActiveTab('loading')}
              style={{ cursor: 'pointer' }}
            >
              Loading
            </NavLink>
          </NavItem>
          <NavItem>
            <NavLink
              className={activeTab === 'visualization' ? 'active' : ''}
              onClick={() => setActiveTab('visualization')}
              style={{ cursor: 'pointer' }}
            >
              3D Visualization
            </NavLink>
          </NavItem>
        </Nav>

        {loading ? (
          <div className="text-center py-5">
            <Spinner color="primary" />
          </div>
        ) : (
          <TabContent activeTab={activeTab}>
            <TabPane tabId="summary">
              <Row>
                <Col md={6}>
                  <Card className="mb-3">
                    <CardHeader>Load Plan Information</CardHeader>
                    <CardBody>
                      <table className="table table-sm table-borderless">
                        <tbody>
                          <tr>
                            <th style={{ width: '40%' }}>Load Number:</th>
                            <td>{loadPlan.load_number}</td>
                          </tr>
                          <tr>
                            <th>Shipping Carrier:</th>
                            <td>{loadPlan.shipping_carrier}</td>
                          </tr>
                          <tr>
                            <th>Vehicle Type:</th>
                            <td>{loadPlan.vehicle_type}</td>
                          </tr>
                          <tr>
                            <th>Status:</th>
                            <td>{getStatusBadge(loadPlan.status)}</td>
                          </tr>
                          <tr>
                            <th>Dock Door:</th>
                            <td>{loadPlan.dock_door}</td>
                          </tr>
                          <tr>
                            <th>Planned Departure:</th>
                            <td>{loadPlan.planned_departure_date} {loadPlan.planned_departure_time}</td>
                          </tr>
                        </tbody>
                      </table>
                    </CardBody>
                  </Card>
                </Col>
                <Col md={6}>
                  <Card className="mb-3">
                    <CardHeader>Load Statistics</CardHeader>
                    <CardBody>
                      <table className="table table-sm table-borderless">
                        <tbody>
                          <tr>
                            <th style={{ width: '40%' }}>Shipment Count:</th>
                            <td>{loadPlan.shipment_count}</td>
                          </tr>
                          <tr>
                            <th>Total Weight:</th>
                            <td>{loadPlan.total_weight_kg} kg</td>
                          </tr>
                          <tr>
                            <th>Total Volume:</th>
                            <td>{loadPlan.total_volume_m3} m³</td>
                          </tr>
                          <tr>
                            <th>Utilization:</th>
                            <td>
                              <div className="d-flex align-items-center">
                                <Progress 
                                  value={loadPlan.utilization_percentage} 
                                  className="me-2" 
                                  style={{ width: '100px', height: '8px' }}
                                  color={
                                    loadPlan.utilization_percentage > 90 ? 'success' :
                                    loadPlan.utilization_percentage > 70 ? 'info' :
                                    loadPlan.utilization_percentage > 50 ? 'warning' : 'danger'
                                  }
                                />
                                <span>{loadPlan.utilization_percentage}%</span>
                              </div>
                            </td>
                          </tr>
                        </tbody>
                      </table>
                    </CardBody>
                  </Card>
                </Col>
              </Row>

              <Card>
                <CardHeader>Shipments Summary</CardHeader>
                <CardBody>
                  {shipments.length > 0 ? (
                    <Table responsive striped size="sm">
                      <thead>
                        <tr>
                          <th>Shipment #</th>
                          <th>Customer</th>
                          <th>Weight (kg)</th>
                          <th>Volume (m³)</th>
                          <th>Status</th>
                          <th>Ship Date</th>
                        </tr>
                      </thead>
                      <tbody>
                        {shipments.map((shipment) => (
                          <tr key={shipment.id}>
                            <td>{shipment.shipment_number}</td>
                            <td>{shipment.customer_name}</td>
                            <td>{shipment.total_weight_kg}</td>
                            <td>{shipment.total_volume_m3}</td>
                            <td>
                              <Badge color="success">Ready</Badge>
                            </td>
                            <td>{shipment.ship_date}</td>
                          </tr>
                        ))}
                      </tbody>
                    </Table>
                  ) : (
                    <div className="text-center py-3">No shipments in this load plan</div>
                  )}
                </CardBody>
              </Card>
            </TabPane>

            <TabPane tabId="shipments">
              <Card>
                <CardHeader>Shipments</CardHeader>
                <CardBody>
                  {shipments.length > 0 ? (
                    <Table responsive striped>
                      <thead>
                        <tr>
                          <th>Shipment #</th>
                          <th>Customer</th>
                          <th>Weight (kg)</th>
                          <th>Volume (m³)</th>
                          <th>Status</th>
                          <th>Ship Date</th>
                          <th>Actions</th>
                        </tr>
                      </thead>
                      <tbody>
                        {shipments.map((shipment) => (
                          <tr key={shipment.id}>
                            <td>{shipment.shipment_number}</td>
                            <td>{shipment.customer_name}</td>
                            <td>{shipment.total_weight_kg}</td>
                            <td>{shipment.total_volume_m3}</td>
                            <td>
                              <Badge color="success">Ready</Badge>
                            </td>
                            <td>{shipment.ship_date}</td>
                            <td>
                              <Button color="info" size="sm">
                                <FontAwesomeIcon icon={faEdit} /> Details
                              </Button>
                            </td>
                          </tr>
                        ))}
                      </tbody>
                    </Table>
                  ) : (
                    <div className="text-center py-3">No shipments in this load plan</div>
                  )}
                </CardBody>
              </Card>
            </TabPane>

            <TabPane tabId="loading">
              {loadPlan.status === 'in_progress' ? (
                <div>
                  <Card className="mb-3">
                    <CardHeader>Loading Confirmation</CardHeader>
                    <CardBody>
                      <Row>
                        <Col md={6}>
                          <FormGroup>
                            <Label for="scannedBarcode">Scan Shipment Barcode</Label>
                            <div className="d-flex mb-3">
                              <Input
                                type="text"
                                id="scannedBarcode"
                                value={scannedBarcode}
                                onChange={(e) => setScannedBarcode(e.target.value)}
                                placeholder="Scan or enter shipment barcode"
                                className="me-2"
                              />
                              <Button color="secondary" onClick={handleScanBarcode}>
                                <FontAwesomeIcon icon={faBarcode} />
                              </Button>
                            </div>
                          </FormGroup>
                          
                          <FormGroup>
                            <Label for="actualWeight">Actual Weight (kg)</Label>
                            <Input
                              type="number"
                              id="actualWeight"
                              value={actualWeight}
                              onChange={(e) => setActualWeight(e.target.value)}
                              placeholder="Enter actual weight"
                            />
                          </FormGroup>
                          
                          <FormGroup>
                            <Label for="loadingNotes">Loading Notes</Label>
                            <Input
                              type="textarea"
                              id="loadingNotes"
                              value={loadingNotes}
                              onChange={(e) => setLoadingNotes(e.target.value)}
                              rows={3}
                              placeholder="Enter any notes about the loading process"
                            />
                          </FormGroup>
                        </Col>
                        <Col md={6}>
                          <Label>Select Loaded Shipments</Label>
                          <div className="border rounded p-2" style={{ maxHeight: '250px', overflowY: 'auto' }}>
                            {shipments.map((shipment) => (
                              <FormGroup check key={shipment.id}>
                                <Label check>
                                  <Input
                                    type="checkbox"
                                    checked={selectedShipments.includes(shipment.id)}
                                    onChange={() => toggleShipmentSelection(shipment.id)}
                                  />{' '}
                                  {shipment.shipment_number} - {shipment.customer_name}
                                </Label>
                              </FormGroup>
                            ))}
                          </div>
                          
                          <div className="mt-3">
                            <Button color="success" onClick={handleConfirmLoading}>
                              <FontAwesomeIcon icon={faCheck} className="me-1" /> Confirm Loading
                            </Button>
                          </div>
                        </Col>
                      </Row>
                    </CardBody>
                  </Card>
                </div>
              ) : (
                <div>
                  <Card className="mb-3">
                    <CardHeader>Loading Details</CardHeader>
                    <CardBody>
                      {loadingDetails ? (
                        <Row>
                          <Col md={6}>
                            <table className="table table-sm table-borderless">
                              <tbody>
                                <tr>
                                  <th style={{ width: '40%' }}>Dock Door:</th>
                                  <td>{loadingDetails.dock_door}</td>
                                </tr>
                                <tr>
                                  <th>Scheduled Start:</th>
                                  <td>{loadingDetails.scheduled_start}</td>
                                </tr>
                                <tr>
                                  <th>Scheduled End:</th>
                                  <td>{loadingDetails.scheduled_end}</td>
                                </tr>
                                <tr>
                                  <th>Actual Start:</th>
                                  <td>{loadingDetails.actual_start || 'Not started'}</td>
                                </tr>
                                <tr>
                                  <th>Actual End:</th>
                                  <td>{loadingDetails.actual_end || 'Not completed'}</td>
                                </tr>
                              </tbody>
                            </table>
                          </Col>
                          <Col md={6}>
                            <table className="table table-sm table-borderless">
                              <tbody>
                                <tr>
                                  <th style={{ width: '40%' }}>Loaded By:</th>
                                  <td>{loadingDetails.loaded_by || 'Not assigned'}</td>
                                </tr>
                                <tr>
                                  <th>Loading Equipment:</th>
                                  <td>{loadingDetails.loading_equipment || 'Not assigned'}</td>
                                </tr>
                              </tbody>
                            </table>
                          </Col>
                        </Row>
                      ) : (
                        <div className="text-center py-3">No loading details available</div>
                      )}
                    </CardBody>
                  </Card>
                  
                  {loadPlan.status === 'planned' && (
                    <div className="text-center mb-3">
                      <Alert color="info">
                        <FontAwesomeIcon icon={faInfoCircle} className="me-2" />
                        Loading process has not started yet. Start loading to record details.
                      </Alert>
                    </div>
                  )}
                  
                  {(loadPlan.status === 'loaded' || loadPlan.status === 'dispatched' || loadPlan.status === 'completed') && (
                    <Card>
                      <CardHeader>Loaded Shipments</CardHeader>
                      <CardBody>
                        <Table responsive striped>
                          <thead>
                            <tr>
                              <th>Loading Sequence</th>
                              <th>Shipment #</th>
                              <th>Customer</th>
                              <th>Weight (kg)</th>
                              <th>Volume (m³)</th>
                            </tr>
                          </thead>
                          <tbody>
                            {loadingDetails && loadingDetails.loading_sequence ? (
                              loadingDetails.loading_sequence.map((shipmentId: number, index: number) => {
                                const shipment = shipments.find(s => s.id === shipmentId);
                                return shipment ? (
                                  <tr key={shipment.id}>
                                    <td>{index + 1}</td>
                                    <td>{shipment.shipment_number}</td>
                                    <td>{shipment.customer_name}</td>
                                    <td>{shipment.total_weight_kg}</td>
                                    <td>{shipment.total_volume_m3}</td>
                                  </tr>
                                ) : null;
                              })
                            ) : (
                              <tr>
                                <td colSpan={5} className="text-center py-3">No loading sequence available</td>
                              </tr>
                            )}
                          </tbody>
                        </Table>
                      </CardBody>
                    </Card>
                  )}
                </div>
              )}
            </TabPane>

            <TabPane tabId="visualization">
              <Card>
                <CardHeader>3D Load Visualization</CardHeader>
                <CardBody>
                  {visualizationData ? (
                    <div>
                      <div className="text-center mb-3">
                        <Alert color="info">
                          <FontAwesomeIcon icon={faInfoCircle} className="me-2" />
                          3D visualization would be rendered here using a library like Three.js
                        </Alert>
                        
                        <div className="border p-3 mb-3" style={{ height: '400px', backgroundColor: '#f8f9fa' }}>
                          <div className="d-flex justify-content-center align-items-center h-100">
                            <div className="text-center">
                              <h5>3D Load Visualization</h5>
                              <p>Container Dimensions: {visualizationData.container_dimensions.length}m x {visualizationData.container_dimensions.width}m x {visualizationData.container_dimensions.height}m</p>
                              <p>Total Items: {visualizationData.items.length}</p>
                              <p>Utilization: {loadPlan.utilization_percentage}%</p>
                            </div>
                          </div>
                        </div>
                      </div>
                      
                      <h6>Load Items</h6>
                      <Table responsive striped size="sm">
                        <thead>
                          <tr>
                            <th>Item #</th>
                            <th>Dimensions (m)</th>
                            <th>Weight (kg)</th>
                            <th>Position (x, y, z)</th>
                          </tr>
                        </thead>
                        <tbody>
                          {visualizationData.items.map((item: any) => (
                            <tr key={item.id}>
                              <td>{item.id}</td>
                              <td>{item.length} x {item.width} x {item.height}</td>
                              <td>{item.weight}</td>
                              <td>{item.position.x}, {item.position.y}, {item.position.z}</td>
                            </tr>
                          ))}
                        </tbody>
                      </Table>
                    </div>
                  ) : (
                    <div className="text-center py-3">No visualization data available</div>
                  )}
                </CardBody>
              </Card>
            </TabPane>
          </TabContent>
        )}
      </ModalBody>
      <ModalFooter>
        <Button color="secondary" onClick={toggle}>Close</Button>
      </ModalFooter>
    </Modal>
  );
};

export default LoadPlanDetailsModal;