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
  Table,
  Badge
} from 'reactstrap';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faPlus, faMinus, faSearch, faCheck, faTimes } from '@fortawesome/free-solid-svg-icons';
import { toast } from 'react-toastify';
import DatePicker from 'react-datepicker';
import 'react-datepicker/dist/react-datepicker.css';

interface CreateLoadPlanModalProps {
  isOpen: boolean;
  toggle: () => void;
  onCreateLoadPlan: (loadPlanData: any) => void;
}

const CreateLoadPlanModal: React.FC<CreateLoadPlanModalProps> = ({ isOpen, toggle, onCreateLoadPlan }) => {
  const [shippingCarrierId, setShippingCarrierId] = useState<number>(0);
  const [vehicleType, setVehicleType] = useState<string>('');
  const [selectedShipmentIds, setSelectedShipmentIds] = useState<number[]>([]);
  const [plannedDepartureDate, setPlannedDepartureDate] = useState<Date | null>(new Date());
  const [plannedDepartureTime, setPlannedDepartureTime] = useState<Date | null>(new Date());
  const [dockDoorId, setDockDoorId] = useState<number>(0);
  const [searchShipmentTerm, setSearchShipmentTerm] = useState<string>('');
  const [searchResults, setSearchResults] = useState<any[]>([]);
  const [isSearching, setIsSearching] = useState<boolean>(false);
  
  // Mock data
  const carriers = [
    { id: 1, name: 'FedEx Freight' },
    { id: 2, name: 'UPS Freight' },
    { id: 3, name: 'XPO Logistics' },
    { id: 4, name: 'DHL Freight' },
    { id: 5, name: 'J.B. Hunt' }
  ];
  
  const vehicleTypes = [
    'Semi-Trailer',
    'Box Truck',
    'Flatbed',
    'Refrigerated Trailer',
    'Tanker',
    'Container'
  ];
  
  const dockDoors = [
    { id: 1, name: 'D-101' },
    { id: 2, name: 'D-102' },
    { id: 3, name: 'D-103' },
    { id: 4, name: 'D-104' },
    { id: 5, name: 'D-105' }
  ];

  useEffect(() => {
    if (isOpen) {
      resetForm();
    }
  }, [isOpen]);

  const resetForm = () => {
    setShippingCarrierId(0);
    setVehicleType('');
    setSelectedShipmentIds([]);
    setPlannedDepartureDate(new Date());
    setPlannedDepartureTime(new Date());
    setDockDoorId(0);
    setSearchShipmentTerm('');
    setSearchResults([]);
  };

  const handleSearchShipments = () => {
    setIsSearching(true);
    
    // Simulate API call to search shipments
    setTimeout(() => {
      const mockResults = [
        { id: 201, shipment_number: 'SH-20001', customer_name: 'Acme Corp', total_weight_kg: 450, total_volume_m3: 8.5, status: 'ready', ship_date: '2025-07-16' },
        { id: 202, shipment_number: 'SH-20002', customer_name: 'Globex Industries', total_weight_kg: 320, total_volume_m3: 5.2, status: 'ready', ship_date: '2025-07-16' },
        { id: 203, shipment_number: 'SH-20003', customer_name: 'Initech LLC', total_weight_kg: 580, total_volume_m3: 10.8, status: 'ready', ship_date: '2025-07-16' },
        { id: 204, shipment_number: 'SH-20004', customer_name: 'Umbrella Corporation', total_weight_kg: 210, total_volume_m3: 3.5, status: 'ready', ship_date: '2025-07-17' },
        { id: 205, shipment_number: 'SH-20005', customer_name: 'Stark Industries', total_weight_kg: 390, total_volume_m3: 7.2, status: 'ready', ship_date: '2025-07-17' }
      ];
      
      setSearchResults(mockResults.filter(shipment => 
        shipment.shipment_number.toLowerCase().includes(searchShipmentTerm.toLowerCase()) ||
        shipment.customer_name.toLowerCase().includes(searchShipmentTerm.toLowerCase())
      ));
      setIsSearching(false);
    }, 500);
  };

  const toggleShipmentSelection = (shipmentId: number) => {
    if (selectedShipmentIds.includes(shipmentId)) {
      setSelectedShipmentIds(selectedShipmentIds.filter(id => id !== shipmentId));
    } else {
      setSelectedShipmentIds([...selectedShipmentIds, shipmentId]);
    }
  };

  const handleSubmit = () => {
    // Validate form
    if (shippingCarrierId === 0) {
      toast.error('Please select a shipping carrier');
      return;
    }
    
    if (!vehicleType) {
      toast.error('Please select a vehicle type');
      return;
    }
    
    if (selectedShipmentIds.length === 0) {
      toast.error('Please select at least one shipment');
      return;
    }
    
    if (!plannedDepartureDate) {
      toast.error('Please select a planned departure date');
      return;
    }
    
    if (!plannedDepartureTime) {
      toast.error('Please select a planned departure time');
      return;
    }
    
    // Format date and time
    const formattedDate = plannedDepartureDate ? plannedDepartureDate.toISOString().split('T')[0] : '';
    const formattedTime = plannedDepartureTime ? 
      `${plannedDepartureTime.getHours().toString().padStart(2, '0')}:${plannedDepartureTime.getMinutes().toString().padStart(2, '0')}:00` : '';
    
    const loadPlanData = {
      shipping_carrier_id: shippingCarrierId,
      vehicle_type: vehicleType,
      shipment_ids: selectedShipmentIds,
      planned_departure_date: formattedDate,
      planned_departure_time: formattedTime,
      dock_door_id: dockDoorId || undefined
    };
    
    onCreateLoadPlan(loadPlanData);
  };

  return (
    <Modal isOpen={isOpen} toggle={toggle} size="lg">
      <ModalHeader toggle={toggle}>Create Load Plan</ModalHeader>
      <ModalBody>
        <Form>
          <Row>
            <Col md={6}>
              <FormGroup>
                <Label for="shippingCarrier">Shipping Carrier</Label>
                <Input
                  type="select"
                  id="shippingCarrier"
                  value={shippingCarrierId}
                  onChange={(e) => setShippingCarrierId(parseInt(e.target.value))}
                >
                  <option value={0}>-- Select Carrier --</option>
                  {carriers.map(carrier => (
                    <option key={carrier.id} value={carrier.id}>{carrier.name}</option>
                  ))}
                </Input>
              </FormGroup>
            </Col>
            <Col md={6}>
              <FormGroup>
                <Label for="vehicleType">Vehicle Type</Label>
                <Input
                  type="select"
                  id="vehicleType"
                  value={vehicleType}
                  onChange={(e) => setVehicleType(e.target.value)}
                >
                  <option value="">-- Select Vehicle Type --</option>
                  {vehicleTypes.map((type, index) => (
                    <option key={index} value={type}>{type}</option>
                  ))}
                </Input>
              </FormGroup>
            </Col>
          </Row>
          
          <Row>
            <Col md={6}>
              <FormGroup>
                <Label for="plannedDepartureDate">Planned Departure Date</Label>
                <DatePicker
                  selected={plannedDepartureDate}
                  onChange={(date) => setPlannedDepartureDate(date)}
                  className="form-control"
                  id="plannedDepartureDate"
                  minDate={new Date()}
                />
              </FormGroup>
            </Col>
            <Col md={6}>
              <FormGroup>
                <Label for="plannedDepartureTime">Planned Departure Time</Label>
                <DatePicker
                  selected={plannedDepartureTime}
                  onChange={(date) => setPlannedDepartureTime(date)}
                  showTimeSelect
                  showTimeSelectOnly
                  timeIntervals={15}
                  timeCaption="Time"
                  dateFormat="h:mm aa"
                  className="form-control"
                  id="plannedDepartureTime"
                />
              </FormGroup>
            </Col>
          </Row>
          
          <Row>
            <Col md={6}>
              <FormGroup>
                <Label for="dockDoor">Dock Door</Label>
                <Input
                  type="select"
                  id="dockDoor"
                  value={dockDoorId}
                  onChange={(e) => setDockDoorId(parseInt(e.target.value))}
                >
                  <option value={0}>-- Select Dock Door --</option>
                  {dockDoors.map(door => (
                    <option key={door.id} value={door.id}>{door.name}</option>
                  ))}
                </Input>
              </FormGroup>
            </Col>
          </Row>
          
          <hr className="my-4" />
          
          <h5>Select Shipments</h5>
          <div className="d-flex mb-3">
            <Input
              type="text"
              placeholder="Search shipments by number or customer..."
              value={searchShipmentTerm}
              onChange={(e) => setSearchShipmentTerm(e.target.value)}
              className="me-2"
            />
            <Button color="secondary" onClick={handleSearchShipments}>
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
                  <th>Shipment #</th>
                  <th>Customer</th>
                  <th>Weight (kg)</th>
                  <th>Volume (m³)</th>
                  <th>Status</th>
                  <th>Ship Date</th>
                </tr>
              </thead>
              <tbody>
                {searchResults.length > 0 ? (
                  searchResults.map((shipment) => (
                    <tr key={shipment.id}>
                      <td>
                        <Input
                          type="checkbox"
                          checked={selectedShipmentIds.includes(shipment.id)}
                          onChange={() => toggleShipmentSelection(shipment.id)}
                        />
                      </td>
                      <td>{shipment.shipment_number}</td>
                      <td>{shipment.customer_name}</td>
                      <td>{shipment.total_weight_kg}</td>
                      <td>{shipment.total_volume_m3}</td>
                      <td>
                        <Badge color="success">Ready</Badge>
                      </td>
                      <td>{shipment.ship_date}</td>
                    </tr>
                  ))
                ) : (
                  <tr>
                    <td colSpan={7} className="text-center py-3">
                      {searchShipmentTerm ? 'No shipments found matching your search' : 'Search for shipments to add to the load plan'}
                    </td>
                  </tr>
                )}
              </tbody>
            </Table>
          )}

          {selectedShipmentIds.length > 0 && (
            <div className="mt-2">
              <small>{selectedShipmentIds.length} shipments selected</small>
            </div>
          )}
        </Form>
      </ModalBody>
      <ModalFooter>
        <Button color="secondary" onClick={toggle}>Cancel</Button>
        <Button color="primary" onClick={handleSubmit}>Create Load Plan</Button>
      </ModalFooter>
    </Modal>
  );
};

export default CreateLoadPlanModal;