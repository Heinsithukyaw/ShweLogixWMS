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
  Modal,
  ModalHeader,
  ModalBody,
  ModalFooter,
  Form
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
  faUsers,
  faList,
  faChartLine
} from '@fortawesome/free-solid-svg-icons';
import { waveManagementApi } from '../../../services/waveManagementApi';
import { PickWave } from '../../../type/outbound/picking';
import { toast } from 'react-toastify';
import CreateWaveModal from './CreateWaveModal';
import WaveDetailsModal from './WaveDetailsModal';

const WaveManagement: React.FC = () => {
  const [waves, setWaves] = useState<PickWave[]>([]);
  const [loading, setLoading] = useState<boolean>(true);
  const [currentPage, setCurrentPage] = useState<number>(1);
  const [totalPages, setTotalPages] = useState<number>(1);
  const [searchTerm, setSearchTerm] = useState<string>('');
  const [statusFilter, setStatusFilter] = useState<string>('all');
  const [sortField, setSortField] = useState<string>('created_at');
  const [sortDirection, setSortDirection] = useState<string>('desc');
  const [isCreateModalOpen, setIsCreateModalOpen] = useState<boolean>(false);
  const [isDetailsModalOpen, setIsDetailsModalOpen] = useState<boolean>(false);
  const [selectedWave, setSelectedWave] = useState<PickWave | null>(null);
  const [refreshTrigger, setRefreshTrigger] = useState<number>(0);

  useEffect(() => {
    fetchWaves();
  }, [currentPage, searchTerm, statusFilter, sortField, sortDirection, refreshTrigger]);

  const fetchWaves = async () => {
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
      
      const response = await waveManagementApi.getWaves(params);
      setWaves(response.data.data);
      setTotalPages(Math.ceil(response.data.total / response.data.per_page));
    } catch (error) {
      console.error('Error fetching waves:', error);
      toast.error('Failed to load pick waves');
    } finally {
      setLoading(false);
    }
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

  const toggleDetailsModal = (wave: PickWave | null = null) => {
    setSelectedWave(wave);
    setIsDetailsModalOpen(!isDetailsModalOpen);
  };

  const handleCreateWave = async (waveData: any) => {
    try {
      await waveManagementApi.createWave(waveData);
      toast.success('Pick wave created successfully');
      toggleCreateModal();
      refreshWaves();
    } catch (error) {
      console.error('Error creating wave:', error);
      toast.error('Failed to create pick wave');
    }
  };

  const handleReleaseWave = async (waveId: number) => {
    try {
      await waveManagementApi.releaseWave(waveId);
      toast.success('Wave released for picking');
      refreshWaves();
    } catch (error) {
      console.error('Error releasing wave:', error);
      toast.error('Failed to release wave');
    }
  };

  const handleCancelWave = async (waveId: number) => {
    if (window.confirm('Are you sure you want to cancel this wave?')) {
      try {
        await waveManagementApi.cancelWave(waveId);
        toast.success('Wave cancelled successfully');
        refreshWaves();
      } catch (error) {
        console.error('Error cancelling wave:', error);
        toast.error('Failed to cancel wave');
      }
    }
  };

  const refreshWaves = () => {
    setRefreshTrigger(prev => prev + 1);
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

  return (
    <div className="wave-management-container">
      <Card className="mb-4">
        <CardHeader className="d-flex justify-content-between align-items-center">
          <h5 className="mb-0">Pick Wave Management</h5>
          <Button color="primary" onClick={toggleCreateModal}>
            <FontAwesomeIcon icon={faPlus} className="me-2" />
            Create Wave
          </Button>
        </CardHeader>
        <CardBody>
          <Row className="mb-3">
            <Col md={6}>
              <Form onSubmit={handleSearch}>
                <div className="d-flex">
                  <Input
                    type="text"
                    placeholder="Search waves..."
                    value={searchTerm}
                    onChange={(e) => setSearchTerm(e.target.value)}
                    className="me-2"
                  />
                  <Button color="secondary" type="submit">
                    <FontAwesomeIcon icon={faSearch} />
                  </Button>
                </div>
              </Form>
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
                  <option value="released">Released</option>
                  <option value="in_progress">In Progress</option>
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
                    <th onClick={() => handleSort('wave_number')} style={{ cursor: 'pointer' }}>
                      Wave # {sortField === 'wave_number' && (
                        <FontAwesomeIcon icon={faSort} className="ms-1" />
                      )}
                    </th>
                    <th onClick={() => handleSort('wave_type')} style={{ cursor: 'pointer' }}>
                      Type {sortField === 'wave_type' && (
                        <FontAwesomeIcon icon={faSort} className="ms-1" />
                      )}
                    </th>
                    <th onClick={() => handleSort('wave_status')} style={{ cursor: 'pointer' }}>
                      Status {sortField === 'wave_status' && (
                        <FontAwesomeIcon icon={faSort} className="ms-1" />
                      )}
                    </th>
                    <th onClick={() => handleSort('total_orders')} style={{ cursor: 'pointer' }}>
                      Orders {sortField === 'total_orders' && (
                        <FontAwesomeIcon icon={faSort} className="ms-1" />
                      )}
                    </th>
                    <th onClick={() => handleSort('total_lines')} style={{ cursor: 'pointer' }}>
                      Lines {sortField === 'total_lines' && (
                        <FontAwesomeIcon icon={faSort} className="ms-1" />
                      )}
                    </th>
                    <th onClick={() => handleSort('total_units')} style={{ cursor: 'pointer' }}>
                      Units {sortField === 'total_units' && (
                        <FontAwesomeIcon icon={faSort} className="ms-1" />
                      )}
                    </th>
                    <th onClick={() => handleSort('created_at')} style={{ cursor: 'pointer' }}>
                      Created {sortField === 'created_at' && (
                        <FontAwesomeIcon icon={faSort} className="ms-1" />
                      )}
                    </th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  {waves.length > 0 ? (
                    waves.map((wave) => (
                      <tr key={wave.id}>
                        <td>
                          <a href="#" onClick={() => toggleDetailsModal(wave)}>
                            {wave.wave_number}
                          </a>
                        </td>
                        <td>{wave.wave_type.charAt(0).toUpperCase() + wave.wave_type.slice(1)}</td>
                        <td>{getStatusBadge(wave.wave_status)}</td>
                        <td>{wave.total_orders}</td>
                        <td>{wave.total_lines}</td>
                        <td>{wave.total_units}</td>
                        <td>{new Date(wave.created_at).toLocaleString()}</td>
                        <td>
                          <div className="d-flex">
                            <Button
                              color="info"
                              size="sm"
                              className="me-1"
                              onClick={() => toggleDetailsModal(wave)}
                              title="View Details"
                            >
                              <FontAwesomeIcon icon={faEdit} />
                            </Button>
                            
                            {wave.wave_status === 'planned' && (
                              <>
                                <Button
                                  color="success"
                                  size="sm"
                                  className="me-1"
                                  onClick={() => handleReleaseWave(wave.id)}
                                  title="Release Wave"
                                >
                                  <FontAwesomeIcon icon={faPlay} />
                                </Button>
                                <Button
                                  color="danger"
                                  size="sm"
                                  onClick={() => handleCancelWave(wave.id)}
                                  title="Cancel Wave"
                                >
                                  <FontAwesomeIcon icon={faTimes} />
                                </Button>
                              </>
                            )}
                          </div>
                        </td>
                      </tr>
                    ))
                  ) : (
                    <tr>
                      <td colSpan={8} className="text-center py-3">
                        No pick waves found
                      </td>
                    </tr>
                  )}
                </tbody>
              </Table>

              <div className="d-flex justify-content-between align-items-center mt-3">
                <div>
                  Showing {waves.length} of {totalPages * 10} waves
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

      {/* Create Wave Modal */}
      <CreateWaveModal
        isOpen={isCreateModalOpen}
        toggle={toggleCreateModal}
        onCreateWave={handleCreateWave}
      />

      {/* Wave Details Modal */}
      {selectedWave && (
        <WaveDetailsModal
          isOpen={isDetailsModalOpen}
          toggle={toggleDetailsModal}
          wave={selectedWave}
          onRefresh={refreshWaves}
        />
      )}
    </div>
  );
};

export default WaveManagement;