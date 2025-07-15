import React, { useState, useEffect } from 'react';
import {
  Card,
  CardHeader,
  CardBody,
  Button,
  Table,
  Badge,
  Spinner,
  Pagination,
  PaginationItem,
  PaginationLink,
  Input,
  FormGroup,
  Label,
  Row,
  Col,
  Progress
} from 'reactstrap';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import {
  faPlus,
  faEdit,
  faTrash,
  faPlay,
  faTimes,
  faSearch,
  faFilter,
  faSort,
  faTruck,
  faBoxes,
  faOptimize,
  faCheck
} from '@fortawesome/free-solid-svg-icons';
import { shippingApi } from '../../../services/outboundApi';
import { toast } from 'react-toastify';
import CreateLoadPlanModal from './CreateLoadPlanModal';
import LoadPlanDetailsModal from './LoadPlanDetailsModal';

const LoadPlanning: React.FC = () => {
  const [loadPlans, setLoadPlans] = useState<any[]>([]);
  const [loading, setLoading] = useState<boolean>(true);
  const [currentPage, setCurrentPage] = useState<number>(1);
  const [totalPages, setTotalPages] = useState<number>(1);
  const [searchTerm, setSearchTerm] = useState<string>('');
  const [statusFilter, setStatusFilter] = useState<string>('all');
  const [sortField, setSortField] = useState<string>('planned_departure_date');
  const [sortDirection, setSortDirection] = useState<string>('asc');
  const [isCreateModalOpen, setIsCreateModalOpen] = useState<boolean>(false);
  const [isDetailsModalOpen, setIsDetailsModalOpen] = useState<boolean>(false);
  const [selectedLoadPlan, setSelectedLoadPlan] = useState<any | null>(null);
  const [refreshTrigger, setRefreshTrigger] = useState<number>(0);

  useEffect(() => {
    fetchLoadPlans();
  }, [currentPage, searchTerm, statusFilter, sortField, sortDirection, refreshTrigger]);

  const fetchLoadPlans = async () => {
    setLoading(true);
    try {
      const params = {
        page: currentPage,
        search: searchTerm,
        status: statusFilter !== 'all' ? statusFilter : undefined,
        sort_by: sortField,
        sort_direction: sortDirection,
        per_page: 10
      };
      
      const response = await shippingApi.getLoadPlans(params);
      
      // Mock data for development
      if (!response.data || !response.data.data) {
        const mockData = generateMockLoadPlans();
        setLoadPlans(mockData);
        setTotalPages(Math.ceil(mockData.length / 10));
      } else {
        setLoadPlans(response.data.data);
        setTotalPages(Math.ceil(response.data.total / response.data.per_page));
      }
    } catch (error) {
      console.error('Error fetching load plans:', error);
      toast.error('Failed to load data');
      
      // Use mock data if API fails
      const mockData = generateMockLoadPlans();
      setLoadPlans(mockData);
      setTotalPages(Math.ceil(mockData.length / 10));
    } finally {
      setLoading(false);
    }
  };

  const generateMockLoadPlans = () => {
    return [
      {
        id: 1,
        load_number: 'LP-10001',
        shipping_carrier: 'FedEx Freight',
        vehicle_type: 'Semi-Trailer',
        shipment_count: 5,
        total_weight_kg: 2500,
        total_volume_m3: 45,
        utilization_percentage: 85,
        planned_departure_date: '2025-07-16',
        planned_departure_time: '08:00:00',
        status: 'planned',
        dock_door: 'D-101'
      },
      {
        id: 2,
        load_number: 'LP-10002',
        shipping_carrier: 'UPS Freight',
        vehicle_type: 'Box Truck',
        shipment_count: 3,
        total_weight_kg: 1200,
        total_volume_m3: 18,
        utilization_percentage: 72,
        planned_departure_date: '2025-07-16',
        planned_departure_time: '10:30:00',
        status: 'in_progress',
        dock_door: 'D-102'
      },
      {
        id: 3,
        load_number: 'LP-10003',
        shipping_carrier: 'XPO Logistics',
        vehicle_type: 'Semi-Trailer',
        shipment_count: 8,
        total_weight_kg: 3800,
        total_volume_m3: 52,
        utilization_percentage: 94,
        planned_departure_date: '2025-07-17',
        planned_departure_time: '09:15:00',
        status: 'planned',
        dock_door: 'D-103'
      },
      {
        id: 4,
        load_number: 'LP-10004',
        shipping_carrier: 'DHL Freight',
        vehicle_type: 'Box Truck',
        shipment_count: 2,
        total_weight_kg: 850,
        total_volume_m3: 12,
        utilization_percentage: 60,
        planned_departure_date: '2025-07-17',
        planned_departure_time: '14:00:00',
        status: 'completed',
        dock_door: 'D-104'
      },
      {
        id: 5,
        load_number: 'LP-10005',
        shipping_carrier: 'J.B. Hunt',
        vehicle_type: 'Semi-Trailer',
        shipment_count: 6,
        total_weight_kg: 3200,
        total_volume_m3: 48,
        utilization_percentage: 88,
        planned_departure_date: '2025-07-18',
        planned_departure_time: '07:30:00',
        status: 'planned',
        dock_door: 'D-105'
      }
    ];
  };

  const handlePageChange = (page: number) => {
    setCurrentPage(page);
  };

  const handleSort = (field: string) => {
    if (sortField === field) {
      setSortDirection(sortDirection === 'asc' ? 'desc' : 'asc');
    } else {
      setSortField(field);
      setSortDirection('asc');
    }
  };

  const handleSearch = (e: React.FormEvent) => {
    e.preventDefault();
    setCurrentPage(1);
  };

  const handleStatusFilterChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    setStatusFilter(e.target.value);
    setCurrentPage(1);
  };

  const toggleCreateModal = () => {
    setIsCreateModalOpen(!isCreateModalOpen);
  };

  const toggleDetailsModal = (loadPlan: any | null = null) => {
    setSelectedLoadPlan(loadPlan);
    setIsDetailsModalOpen(!isDetailsModalOpen);
  };

  const handleCreateLoadPlan = async (loadPlanData: any) => {
    try {
      await shippingApi.createLoadPlan(loadPlanData);
      toast.success('Load plan created successfully');
      toggleCreateModal();
      refreshLoadPlans();
    } catch (error) {
      console.error('Error creating load plan:', error);
      toast.error('Failed to create load plan');
    }
  };

  const handleOptimizeLoad = async (loadId: number) => {
    try {
      await shippingApi.optimizeLoad(loadId);
      toast.success('Load plan optimized successfully');
      refreshLoadPlans();
    } catch (error) {
      console.error('Error optimizing load plan:', error);
      toast.error('Failed to optimize load plan');
    }
  };

  const handleConfirmLoading = async (loadId: number, data: any) => {
    try {
      await shippingApi.confirmLoading(loadId, data);
      toast.success('Loading confirmed successfully');
      refreshLoadPlans();
    } catch (error) {
      console.error('Error confirming loading:', error);
      toast.error('Failed to confirm loading');
    }
  };

  const handleDispatchLoad = async (loadId: number) => {
    try {
      await shippingApi.dispatchLoad(loadId);
      toast.success('Load dispatched successfully');
      refreshLoadPlans();
    } catch (error) {
      console.error('Error dispatching load:', error);
      toast.error('Failed to dispatch load');
    }
  };

  const refreshLoadPlans = () => {
    setRefreshTrigger(prev => prev + 1);
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
    <div className="load-planning-container">
      <Card className="mb-4">
        <CardHeader className="d-flex justify-content-between align-items-center">
          <h5 className="mb-0">Load Planning</h5>
          <Button color="primary" onClick={toggleCreateModal}>
            <FontAwesomeIcon icon={faPlus} className="me-2" />
            Create Load Plan
          </Button>
        </CardHeader>
        <CardBody>
          <Row className="mb-3">
            <Col md={6}>
              <form onSubmit={handleSearch}>
                <div className="d-flex">
                  <Input
                    type="text"
                    placeholder="Search load plans..."
                    value={searchTerm}
                    onChange={(e) => setSearchTerm(e.target.value)}
                    className="me-2"
                  />
                  <Button color="secondary" type="submit">
                    <FontAwesomeIcon icon={faSearch} />
                  </Button>
                </div>
              </form>
            </Col>
            <Col md={6}>
              <FormGroup className="d-flex align-items-center justify-content-end">
                <Label className="me-2 mb-0">Status:</Label>
                <Input
                  type="select"
                  value={statusFilter}
                  onChange={handleStatusFilterChange}
                  style={{ width: '150px' }}
                >
                  <option value="all">All</option>
                  <option value="planned">Planned</option>
                  <option value="in_progress">Loading</option>
                  <option value="loaded">Loaded</option>
                  <option value="dispatched">Dispatched</option>
                  <option value="completed">Completed</option>
                  <option value="cancelled">Cancelled</option>
                </Input>
              </FormGroup>
            </Col>
          </Row>

          {loading ? (
            <div className="text-center py-5">
              <Spinner color="primary" />
            </div>
          ) : (
            <>
              <Table responsive striped hover>
                <thead>
                  <tr>
                    <th onClick={() => handleSort('load_number')} style={{ cursor: 'pointer' }}>
                      Load # {sortField === 'load_number' && (
                        <FontAwesomeIcon icon={faSort} className="ms-1" />
                      )}
                    </th>
                    <th onClick={() => handleSort('shipping_carrier')} style={{ cursor: 'pointer' }}>
                      Carrier {sortField === 'shipping_carrier' && (
                        <FontAwesomeIcon icon={faSort} className="ms-1" />
                      )}
                    </th>
                    <th onClick={() => handleSort('vehicle_type')} style={{ cursor: 'pointer' }}>
                      Vehicle Type {sortField === 'vehicle_type' && (
                        <FontAwesomeIcon icon={faSort} className="ms-1" />
                      )}
                    </th>
                    <th onClick={() => handleSort('shipment_count')} style={{ cursor: 'pointer' }}>
                      Shipments {sortField === 'shipment_count' && (
                        <FontAwesomeIcon icon={faSort} className="ms-1" />
                      )}
                    </th>
                    <th onClick={() => handleSort('utilization_percentage')} style={{ cursor: 'pointer' }}>
                      Utilization {sortField === 'utilization_percentage' && (
                        <FontAwesomeIcon icon={faSort} className="ms-1" />
                      )}
                    </th>
                    <th onClick={() => handleSort('planned_departure_date')} style={{ cursor: 'pointer' }}>
                      Departure {sortField === 'planned_departure_date' && (
                        <FontAwesomeIcon icon={faSort} className="ms-1" />
                      )}
                    </th>
                    <th onClick={() => handleSort('status')} style={{ cursor: 'pointer' }}>
                      Status {sortField === 'status' && (
                        <FontAwesomeIcon icon={faSort} className="ms-1" />
                      )}
                    </th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  {loadPlans.length > 0 ? (
                    loadPlans.map((loadPlan) => (
                      <tr key={loadPlan.id}>
                        <td>
                          <a href="#" onClick={() => toggleDetailsModal(loadPlan)}>
                            {loadPlan.load_number}
                          </a>
                        </td>
                        <td>{loadPlan.shipping_carrier}</td>
                        <td>{loadPlan.vehicle_type}</td>
                        <td>{loadPlan.shipment_count}</td>
                        <td>
                          <div className="d-flex align-items-center">
                            <Progress 
                              value={loadPlan.utilization_percentage} 
                              className="me-2" 
                              style={{ width: '60px', height: '8px' }}
                              color={
                                loadPlan.utilization_percentage > 90 ? 'success' :
                                loadPlan.utilization_percentage > 70 ? 'info' :
                                loadPlan.utilization_percentage > 50 ? 'warning' : 'danger'
                              }
                            />
                            <span>{loadPlan.utilization_percentage}%</span>
                          </div>
                        </td>
                        <td>
                          {loadPlan.planned_departure_date} {loadPlan.planned_departure_time.substring(0, 5)}
                        </td>
                        <td>{getStatusBadge(loadPlan.status)}</td>
                        <td>
                          <div className="d-flex">
                            <Button
                              color="info"
                              size="sm"
                              className="me-1"
                              onClick={() => toggleDetailsModal(loadPlan)}
                              title="View Details"
                            >
                              <FontAwesomeIcon icon={faEdit} />
                            </Button>
                            
                            {loadPlan.status === 'planned' && (
                              <>
                                <Button
                                  color="primary"
                                  size="sm"
                                  className="me-1"
                                  onClick={() => handleOptimizeLoad(loadPlan.id)}
                                  title="Optimize Load"
                                >
                                  <FontAwesomeIcon icon={faOptimize} />
                                </Button>
                              </>
                            )}
                            
                            {loadPlan.status === 'in_progress' && (
                              <Button
                                color="success"
                                size="sm"
                                className="me-1"
                                onClick={() => toggleDetailsModal(loadPlan)}
                                title="Confirm Loading"
                              >
                                <FontAwesomeIcon icon={faCheck} />
                              </Button>
                            )}
                            
                            {loadPlan.status === 'loaded' && (
                              <Button
                                color="success"
                                size="sm"
                                className="me-1"
                                onClick={() => handleDispatchLoad(loadPlan.id)}
                                title="Dispatch Load"
                              >
                                <FontAwesomeIcon icon={faTruck} />
                              </Button>
                            )}
                          </div>
                        </td>
                      </tr>
                    ))
                  ) : (
                    <tr>
                      <td colSpan={8} className="text-center py-3">
                        No load plans found
                      </td>
                    </tr>
                  )}
                </tbody>
              </Table>

              <div className="d-flex justify-content-between align-items-center mt-3">
                <div>
                  Showing {loadPlans.length} of {totalPages * 10} load plans
                </div>
                <Pagination>
                  <PaginationItem disabled={currentPage === 1}>
                    <PaginationLink previous onClick={() => handlePageChange(currentPage - 1)} />
                  </PaginationItem>
                  
                  {[...Array(totalPages)].map((_, i) => (
                    <PaginationItem key={i} active={i + 1 === currentPage}>
                      <PaginationLink onClick={() => handlePageChange(i + 1)}>
                        {i + 1}
                      </PaginationLink>
                    </PaginationItem>
                  ))}
                  
                  <PaginationItem disabled={currentPage === totalPages}>
                    <PaginationLink next onClick={() => handlePageChange(currentPage + 1)} />
                  </PaginationItem>
                </Pagination>
              </div>
            </>
          )}
        </CardBody>
      </Card>

      {/* Create Load Plan Modal */}
      <CreateLoadPlanModal
        isOpen={isCreateModalOpen}
        toggle={toggleCreateModal}
        onCreateLoadPlan={handleCreateLoadPlan}
      />

      {/* Load Plan Details Modal */}
      {selectedLoadPlan && (
        <LoadPlanDetailsModal
          isOpen={isDetailsModalOpen}
          toggle={toggleDetailsModal}
          loadPlan={selectedLoadPlan}
          onOptimize={handleOptimizeLoad}
          onConfirmLoading={handleConfirmLoading}
          onDispatch={handleDispatchLoad}
          onRefresh={refreshLoadPlans}
        />
      )}
    </div>
  );
};

export default LoadPlanning;