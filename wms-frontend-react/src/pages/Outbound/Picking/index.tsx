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
  Nav,
  NavItem,
  NavLink,
  TabContent,
  TabPane,
  Progress,
  Alert
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
  faChartLine,
  faCheck,
  faExclamationTriangle
} from '@fortawesome/free-solid-svg-icons';
import { pickListApi } from '../../../services/outboundApi';
import { pickTaskApi } from '../../../services/waveManagementApi';
import { PickList, PickTask, PickTaskItem, PickException } from '../../../type/outbound/picking';
import { toast } from 'react-toastify';
import PickTaskModal from './PickTaskModal';
import PickExceptionModal from './PickExceptionModal';

const PickingInterface: React.FC = () => {
  const [activeTab, setActiveTab] = useState<string>('tasks');
  const [pickTasks, setPickTasks] = useState<PickTask[]>([]);
  const [pickLists, setPickLists] = useState<PickList[]>([]);
  const [exceptions, setExceptions] = useState<PickException[]>([]);
  const [loading, setLoading] = useState<boolean>(true);
  const [currentPage, setCurrentPage] = useState<number>(1);
  const [totalPages, setTotalPages] = useState<number>(1);
  const [searchTerm, setSearchTerm] = useState<string>('');
  const [statusFilter, setStatusFilter] = useState<string>('all');
  const [sortField, setSortField] = useState<string>('created_at');
  const [sortDirection, setSortDirection] = useState<string>('desc');
  const [isTaskModalOpen, setIsTaskModalOpen] = useState<boolean>(false);
  const [isExceptionModalOpen, setIsExceptionModalOpen] = useState<boolean>(false);
  const [selectedTask, setSelectedTask] = useState<PickTask | null>(null);
  const [selectedException, setSelectedException] = useState<PickException | null>(null);
  const [refreshTrigger, setRefreshTrigger] = useState<number>(0);

  useEffect(() => {
    fetchData();
  }, [activeTab, currentPage, searchTerm, statusFilter, sortField, sortDirection, refreshTrigger]);

  const fetchData = async () => {
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

      if (activeTab === 'tasks') {
        const response = await pickTaskApi.getTasks(params);
        setPickTasks(response.data.data);
        setTotalPages(Math.ceil(response.data.total / response.data.per_page));
      } else if (activeTab === 'lists') {
        const response = await pickListApi.getPickLists(params);
        setPickLists(response.data.data);
        setTotalPages(Math.ceil(response.data.total / response.data.per_page));
      } else if (activeTab === 'exceptions') {
        const response = await pickTaskApi.getTaskExceptions(params);
        setExceptions(response.data.data);
        setTotalPages(Math.ceil(response.data.total / response.data.per_page));
      }
    } catch (error) {
      console.error('Error fetching data:', error);
      toast.error('Failed to load data');
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

  const toggleTaskModal = (task: PickTask | null = null) => {
    setSelectedTask(task);
    setIsTaskModalOpen(!isTaskModalOpen);
  };

  const toggleExceptionModal = (exception: PickException | null = null) => {
    setSelectedException(exception);
    setIsExceptionModalOpen(!isExceptionModalOpen);
  };

  const handleAssignTask = async (taskId: number, employeeId: number) => {
    try {
      await pickTaskApi.assignTask(taskId, { employee_id: employeeId });
      toast.success('Task assigned successfully');
      refreshData();
    } catch (error) {
      console.error('Error assigning task:', error);
      toast.error('Failed to assign task');
    }
  };

  const handleStartTask = async (taskId: number) => {
    try {
      await pickTaskApi.startTask(taskId);
      toast.success('Task started successfully');
      refreshData();
    } catch (error) {
      console.error('Error starting task:', error);
      toast.error('Failed to start task');
    }
  };

  const handleCompleteTask = async (taskId: number) => {
    try {
      await pickTaskApi.completeTask(taskId);
      toast.success('Task completed successfully');
      refreshData();
    } catch (error) {
      console.error('Error completing task:', error);
      toast.error('Failed to complete task');
    }
  };

  const handleResolveException = async (exceptionId: number, resolutionNotes: string) => {
    try {
      await pickTaskApi.resolveException(exceptionId, { resolution_notes: resolutionNotes });
      toast.success('Exception resolved successfully');
      refreshData();
    } catch (error) {
      console.error('Error resolving exception:', error);
      toast.error('Failed to resolve exception');
    }
  };

  const refreshData = () => {
    setRefreshTrigger(prev => prev + 1);
  };

  const getStatusBadge = (status: string) => {
    switch (status) {
      case 'pending':
        return <Badge color="secondary">Pending</Badge>;
      case 'assigned':
        return <Badge color="info">Assigned</Badge>;
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

  const getPriorityBadge = (priority: string) => {
    switch (priority) {
      case 'low':
        return <Badge color="secondary">Low</Badge>;
      case 'normal':
        return <Badge color="info">Normal</Badge>;
      case 'high':
        return <Badge color="warning">High</Badge>;
      case 'urgent':
        return <Badge color="danger">Urgent</Badge>;
      default:
        return <Badge color="secondary">{priority}</Badge>;
    }
  };

  const getExceptionStatusBadge = (status: string) => {
    switch (status) {
      case 'pending':
        return <Badge color="warning">Pending</Badge>;
      case 'resolved':
        return <Badge color="success">Resolved</Badge>;
      case 'escalated':
        return <Badge color="danger">Escalated</Badge>;
      default:
        return <Badge color="secondary">{status}</Badge>;
    }
  };

  return (
    <div className="picking-interface-container">
      <Card className="mb-4">
        <CardHeader className="d-flex justify-content-between align-items-center">
          <h5 className="mb-0">Picking Interface</h5>
        </CardHeader>
        <CardBody>
          <Nav tabs className="mb-3">
            <NavItem>
              <NavLink
                className={activeTab === 'tasks' ? 'active' : ''}
                onClick={() => {
                  setActiveTab('tasks');
                  setCurrentPage(1);
                }}
                style={{ cursor: 'pointer' }}
              >
                Pick Tasks
              </NavLink>
            </NavItem>
            <NavItem>
              <NavLink
                className={activeTab === 'lists' ? 'active' : ''}
                onClick={() => {
                  setActiveTab('lists');
                  setCurrentPage(1);
                }}
                style={{ cursor: 'pointer' }}
              >
                Pick Lists
              </NavLink>
            </NavItem>
            <NavItem>
              <NavLink
                className={activeTab === 'exceptions' ? 'active' : ''}
                onClick={() => {
                  setActiveTab('exceptions');
                  setCurrentPage(1);
                }}
                style={{ cursor: 'pointer' }}
              >
                Exceptions
              </NavLink>
            </NavItem>
          </Nav>

          <Row className="mb-3">
            <Col md={6}>
              <form onSubmit={handleSearch}>
                <div className="d-flex">
                  <Input
                    type="text"
                    placeholder={`Search ${activeTab}...`}
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
                  <option value="pending">Pending</option>
                  <option value="assigned">Assigned</option>
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
            <TabContent activeTab={activeTab}>
              <TabPane tabId="tasks">
                <Table responsive striped hover>
                  <thead>
                    <tr>
                      <th onClick={() => handleSort('task_number')} style={{ cursor: 'pointer' }}>
                        Task # {sortField === 'task_number' && (
                          <FontAwesomeIcon icon={faSort} className="ms-1" />
                        )}
                      </th>
                      <th onClick={() => handleSort('task_type')} style={{ cursor: 'pointer' }}>
                        Type {sortField === 'task_type' && (
                          <FontAwesomeIcon icon={faSort} className="ms-1" />
                        )}
                      </th>
                      <th onClick={() => handleSort('priority')} style={{ cursor: 'pointer' }}>
                        Priority {sortField === 'priority' && (
                          <FontAwesomeIcon icon={faSort} className="ms-1" />
                        )}
                      </th>
                      <th onClick={() => handleSort('task_status')} style={{ cursor: 'pointer' }}>
                        Status {sortField === 'task_status' && (
                          <FontAwesomeIcon icon={faSort} className="ms-1" />
                        )}
                      </th>
                      <th onClick={() => handleSort('employee_name')} style={{ cursor: 'pointer' }}>
                        Assigned To {sortField === 'employee_name' && (
                          <FontAwesomeIcon icon={faSort} className="ms-1" />
                        )}
                      </th>
                      <th onClick={() => handleSort('total_items')} style={{ cursor: 'pointer' }}>
                        Items {sortField === 'total_items' && (
                          <FontAwesomeIcon icon={faSort} className="ms-1" />
                        )}
                      </th>
                      <th onClick={() => handleSort('progress')} style={{ cursor: 'pointer' }}>
                        Progress {sortField === 'progress' && (
                          <FontAwesomeIcon icon={faSort} className="ms-1" />
                        )}
                      </th>
                      <th>Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    {pickTasks.length > 0 ? (
                      pickTasks.map((task) => (
                        <tr key={task.id}>
                          <td>
                            <a href="#" onClick={() => toggleTaskModal(task)}>
                              {task.task_number}
                            </a>
                          </td>
                          <td>{task.task_type.charAt(0).toUpperCase() + task.task_type.slice(1)}</td>
                          <td>{getPriorityBadge(task.priority)}</td>
                          <td>{getStatusBadge(task.task_status)}</td>
                          <td>{task.employee_name || 'Unassigned'}</td>
                          <td>{task.completed_items} / {task.total_items}</td>
                          <td>
                            <Progress value={(task.completed_items / task.total_items) * 100} />
                          </td>
                          <td>
                            <div className="d-flex">
                              <Button
                                color="info"
                                size="sm"
                                className="me-1"
                                onClick={() => toggleTaskModal(task)}
                                title="View Details"
                              >
                                <FontAwesomeIcon icon={faEdit} />
                              </Button>
                              
                              {task.task_status === 'pending' && (
                                <Button
                                  color="primary"
                                  size="sm"
                                  className="me-1"
                                  onClick={() => handleAssignTask(task.id, 1)} // Mock employee ID
                                  title="Assign Task"
                                >
                                  <FontAwesomeIcon icon={faUsers} />
                                </Button>
                              )}
                              
                              {task.task_status === 'assigned' && (
                                <Button
                                  color="success"
                                  size="sm"
                                  className="me-1"
                                  onClick={() => handleStartTask(task.id)}
                                  title="Start Task"
                                >
                                  <FontAwesomeIcon icon={faPlay} />
                                </Button>
                              )}
                              
                              {task.task_status === 'in_progress' && (
                                <Button
                                  color="success"
                                  size="sm"
                                  className="me-1"
                                  onClick={() => handleCompleteTask(task.id)}
                                  title="Complete Task"
                                >
                                  <FontAwesomeIcon icon={faCheck} />
                                </Button>
                              )}
                            </div>
                          </td>
                        </tr>
                      ))
                    ) : (
                      <tr>
                        <td colSpan={8} className="text-center py-3">
                          No pick tasks found
                        </td>
                      </tr>
                    )}
                  </tbody>
                </Table>
              </TabPane>

              <TabPane tabId="lists">
                <Table responsive striped hover>
                  <thead>
                    <tr>
                      <th onClick={() => handleSort('pick_list_number')} style={{ cursor: 'pointer' }}>
                        List # {sortField === 'pick_list_number' && (
                          <FontAwesomeIcon icon={faSort} className="ms-1" />
                        )}
                      </th>
                      <th onClick={() => handleSort('pick_wave_id')} style={{ cursor: 'pointer' }}>
                        Wave ID {sortField === 'pick_wave_id' && (
                          <FontAwesomeIcon icon={faSort} className="ms-1" />
                        )}
                      </th>
                      <th onClick={() => handleSort('pick_type')} style={{ cursor: 'pointer' }}>
                        Type {sortField === 'pick_type' && (
                          <FontAwesomeIcon icon={faSort} className="ms-1" />
                        )}
                      </th>
                      <th onClick={() => handleSort('pick_method')} style={{ cursor: 'pointer' }}>
                        Method {sortField === 'pick_method' && (
                          <FontAwesomeIcon icon={faSort} className="ms-1" />
                        )}
                      </th>
                      <th onClick={() => handleSort('pick_status')} style={{ cursor: 'pointer' }}>
                        Status {sortField === 'pick_status' && (
                          <FontAwesomeIcon icon={faSort} className="ms-1" />
                        )}
                      </th>
                      <th onClick={() => handleSort('assigned_to')} style={{ cursor: 'pointer' }}>
                        Assigned To {sortField === 'assigned_to' && (
                          <FontAwesomeIcon icon={faSort} className="ms-1" />
                        )}
                      </th>
                      <th onClick={() => handleSort('total_picks')} style={{ cursor: 'pointer' }}>
                        Picks {sortField === 'total_picks' && (
                          <FontAwesomeIcon icon={faSort} className="ms-1" />
                        )}
                      </th>
                      <th>Progress</th>
                    </tr>
                  </thead>
                  <tbody>
                    {pickLists.length > 0 ? (
                      pickLists.map((list) => (
                        <tr key={list.id}>
                          <td>{list.pick_list_number}</td>
                          <td>{list.pick_wave_id}</td>
                          <td>{list.pick_type.charAt(0).toUpperCase() + list.pick_type.slice(1)}</td>
                          <td>{list.pick_method.charAt(0).toUpperCase() + list.pick_method.slice(1)}</td>
                          <td>{getStatusBadge(list.pick_status)}</td>
                          <td>{list.assigned_to || 'Unassigned'}</td>
                          <td>{list.completed_picks} / {list.total_picks}</td>
                          <td>
                            <Progress value={(list.completed_picks / list.total_picks) * 100} />
                          </td>
                        </tr>
                      ))
                    ) : (
                      <tr>
                        <td colSpan={8} className="text-center py-3">
                          No pick lists found
                        </td>
                      </tr>
                    )}
                  </tbody>
                </Table>
              </TabPane>

              <TabPane tabId="exceptions">
                <Table responsive striped hover>
                  <thead>
                    <tr>
                      <th onClick={() => handleSort('id')} style={{ cursor: 'pointer' }}>
                        Exception ID {sortField === 'id' && (
                          <FontAwesomeIcon icon={faSort} className="ms-1" />
                        )}
                      </th>
                      <th onClick={() => handleSort('task_id')} style={{ cursor: 'pointer' }}>
                        Task ID {sortField === 'task_id' && (
                          <FontAwesomeIcon icon={faSort} className="ms-1" />
                        )}
                      </th>
                      <th onClick={() => handleSort('exception_type')} style={{ cursor: 'pointer' }}>
                        Type {sortField === 'exception_type' && (
                          <FontAwesomeIcon icon={faSort} className="ms-1" />
                        )}
                      </th>
                      <th>Description</th>
                      <th onClick={() => handleSort('reported_by')} style={{ cursor: 'pointer' }}>
                        Reported By {sortField === 'reported_by' && (
                          <FontAwesomeIcon icon={faSort} className="ms-1" />
                        )}
                      </th>
                      <th onClick={() => handleSort('reported_at')} style={{ cursor: 'pointer' }}>
                        Reported At {sortField === 'reported_at' && (
                          <FontAwesomeIcon icon={faSort} className="ms-1" />
                        )}
                      </th>
                      <th onClick={() => handleSort('resolution_status')} style={{ cursor: 'pointer' }}>
                        Status {sortField === 'resolution_status' && (
                          <FontAwesomeIcon icon={faSort} className="ms-1" />
                        )}
                      </th>
                      <th>Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    {exceptions.length > 0 ? (
                      exceptions.map((exception) => (
                        <tr key={exception.id}>
                          <td>{exception.id}</td>
                          <td>{exception.task_id}</td>
                          <td>{exception.exception_type.replace('_', ' ').charAt(0).toUpperCase() + exception.exception_type.replace('_', ' ').slice(1)}</td>
                          <td>{exception.description.length > 30 ? `${exception.description.substring(0, 30)}...` : exception.description}</td>
                          <td>{exception.reported_by}</td>
                          <td>{new Date(exception.reported_at).toLocaleString()}</td>
                          <td>{getExceptionStatusBadge(exception.resolution_status)}</td>
                          <td>
                            <div className="d-flex">
                              <Button
                                color="info"
                                size="sm"
                                className="me-1"
                                onClick={() => toggleExceptionModal(exception)}
                                title="View Details"
                              >
                                <FontAwesomeIcon icon={faEdit} />
                              </Button>
                              
                              {exception.resolution_status === 'pending' && (
                                <Button
                                  color="success"
                                  size="sm"
                                  onClick={() => toggleExceptionModal(exception)}
                                  title="Resolve Exception"
                                >
                                  <FontAwesomeIcon icon={faCheck} />
                                </Button>
                              )}
                            </div>
                          </td>
                        </tr>
                      ))
                    ) : (
                      <tr>
                        <td colSpan={8} className="text-center py-3">
                          No exceptions found
                        </td>
                      </tr>
                    )}
                  </tbody>
                </Table>
              </TabPane>
            </TabContent>
          )}

          <div className="d-flex justify-content-between align-items-center mt-3">
            <div>
              Showing {activeTab === 'tasks' ? pickTasks.length : activeTab === 'lists' ? pickLists.length : exceptions.length} items
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
        </CardBody>
      </Card>

      {/* Pick Task Modal */}
      {selectedTask && (
        <PickTaskModal
          isOpen={isTaskModalOpen}
          toggle={toggleTaskModal}
          task={selectedTask}
          onAssign={handleAssignTask}
          onStart={handleStartTask}
          onComplete={handleCompleteTask}
          onRefresh={refreshData}
        />
      )}

      {/* Pick Exception Modal */}
      {selectedException && (
        <PickExceptionModal
          isOpen={isExceptionModalOpen}
          toggle={toggleExceptionModal}
          exception={selectedException}
          onResolve={handleResolveException}
          onRefresh={refreshData}
        />
      )}
    </div>
  );
};

export default PickingInterface;