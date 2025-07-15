import React, { useState, useEffect } from 'react';
import {
  Container,
  Row,
  Col,
  Card,
  CardHeader,
  CardBody,
  Button,
  Input,
  InputGroup,
  InputGroupText,
  Nav,
  NavItem,
  NavLink,
  TabContent,
  TabPane,
  Badge,
  Spinner,
  Table,
  Dropdown,
  DropdownToggle,
  DropdownMenu,
  DropdownItem,
  Alert,
  Progress
} from 'reactstrap';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import {
  faSearch,
  faFilter,
  faPlus,
  faPlay,
  faPause,
  faArchive,
  faClone,
  faEdit,
  faTrash,
  faDownload,
  faUpload,
  faExchangeAlt,
  faHistory,
  faChartLine,
  faSort,
  faSortUp,
  faSortDown,
  faEllipsisV,
  faCheck,
  faTimes,
  faClock,
  faExclamationTriangle,
  faCalendarAlt,
  faTag,
  faUser,
  faUserClock,
  faListAlt,
  faCodeBranch,
  faRandom,
  faLayerGroup,
  faBell,
  faHourglass,
  faLink,
  faCheckDouble
} from '@fortawesome/free-solid-svg-icons';
import { toast } from 'react-toastify';
import { format, parseISO } from 'date-fns';

import { Workflow, workflowApi, workflowExecutionApi, workflowAnalyticsApi } from '../../services/workflowEngineApi';
import WorkflowDesignerModal from './WorkflowDesignerModal';
import WorkflowDetailsModal from './WorkflowDetailsModal';
import WorkflowExecutionModal from './WorkflowExecutionModal';
import WorkflowImportModal from './WorkflowImportModal';
import WorkflowCategoryModal from './WorkflowCategoryModal';
import './WorkflowEngine.css';

const WorkflowEngine: React.FC = () => {
  // State
  const [activeTab, setActiveTab] = useState<string>('all');
  const [workflows, setWorkflows] = useState<Workflow[]>([]);
  const [filteredWorkflows, setFilteredWorkflows] = useState<Workflow[]>([]);
  const [categories, setCategories] = useState<string[]>([]);
  const [loading, setLoading] = useState<boolean>(true);
  const [searchTerm, setSearchTerm] = useState<string>('');
  const [selectedCategory, setSelectedCategory] = useState<string | null>(null);
  const [sortField, setSortField] = useState<string>('updated_at');
  const [sortDirection, setSortDirection] = useState<'asc' | 'desc'>('desc');
  const [filterDropdownOpen, setFilterDropdownOpen] = useState<boolean>(false);
  const [sortDropdownOpen, setSortDropdownOpen] = useState<boolean>(false);
  const [actionsDropdownOpen, setActionsDropdownOpen] = useState<number | null>(null);
  
  // Performance metrics
  const [performanceMetrics, setPerformanceMetrics] = useState<any>({
    total_workflows: 0,
    active_workflows: 0,
    total_executions: 0,
    success_rate: 0,
    avg_execution_time: 0,
    executions_today: 0,
    pending_approvals: 0
  });
  
  // Modals
  const [designerModalOpen, setDesignerModalOpen] = useState<boolean>(false);
  const [detailsModalOpen, setDetailsModalOpen] = useState<boolean>(false);
  const [executionModalOpen, setExecutionModalOpen] = useState<boolean>(false);
  const [importModalOpen, setImportModalOpen] = useState<boolean>(false);
  const [categoryModalOpen, setCategoryModalOpen] = useState<boolean>(false);
  const [selectedWorkflow, setSelectedWorkflow] = useState<Workflow | null>(null);
  
  // Fetch data
  useEffect(() => {
    fetchWorkflows();
    fetchCategories();
    fetchPerformanceMetrics();
  }, []);
  
  // Filter workflows when search term or category changes
  useEffect(() => {
    filterWorkflows();
  }, [workflows, searchTerm, selectedCategory, activeTab]);
  
  const fetchWorkflows = async () => {
    setLoading(true);
    try {
      let response;
      
      if (activeTab === 'templates') {
        response = await workflowApi.getTemplates();
      } else {
        response = await workflowApi.getWorkflows({
          status: activeTab !== 'all' ? activeTab : undefined
        });
      }
      
      setWorkflows(response.data);
      setLoading(false);
    } catch (error) {
      console.error('Error fetching workflows:', error);
      toast.error('Failed to load workflows');
      setLoading(false);
    }
  };
  
  const fetchCategories = async () => {
    try {
      const response = await workflowApi.getCategories();
      setCategories(response.data.map((category: any) => category.name));
    } catch (error) {
      console.error('Error fetching categories:', error);
      toast.error('Failed to load workflow categories');
    }
  };
  
  const fetchPerformanceMetrics = async () => {
    try {
      const response = await workflowAnalyticsApi.getPerformanceMetrics();
      setPerformanceMetrics(response.data);
    } catch (error) {
      console.error('Error fetching performance metrics:', error);
    }
  };
  
  const filterWorkflows = () => {
    let filtered = [...workflows];
    
    // Filter by search term
    if (searchTerm) {
      const term = searchTerm.toLowerCase();
      filtered = filtered.filter(workflow => 
        workflow.name.toLowerCase().includes(term) || 
        (workflow.description && workflow.description.toLowerCase().includes(term))
      );
    }
    
    // Filter by category
    if (selectedCategory) {
      filtered = filtered.filter(workflow => workflow.category === selectedCategory);
    }
    
    // Filter by tab
    if (activeTab !== 'all' && activeTab !== 'templates') {
      filtered = filtered.filter(workflow => workflow.status === activeTab);
    }
    
    // Sort workflows
    filtered.sort((a, b) => {
      let valueA: any = a[sortField as keyof Workflow];
      let valueB: any = b[sortField as keyof Workflow];
      
      // Handle dates
      if (typeof valueA === 'string' && (valueA.includes('-') || valueA.includes('/'))) {
        try {
          valueA = new Date(valueA).getTime();
          valueB = new Date(valueB).getTime();
        } catch (e) {
          // Not a valid date, continue with string comparison
        }
      }
      
      if (valueA < valueB) return sortDirection === 'asc' ? -1 : 1;
      if (valueA > valueB) return sortDirection === 'asc' ? 1 : -1;
      return 0;
    });
    
    setFilteredWorkflows(filtered);
  };
  
  const handleSort = (field: string) => {
    if (sortField === field) {
      setSortDirection(sortDirection === 'asc' ? 'desc' : 'asc');
    } else {
      setSortField(field);
      setSortDirection('asc');
    }
  };
  
  const handleTabChange = (tab: string) => {
    setActiveTab(tab);
    // Reset filters when changing tabs
    setSearchTerm('');
    setSelectedCategory(null);
  };
  
  const handleCategoryClick = (category: string) => {
    setSelectedCategory(category === selectedCategory ? null : category);
  };
  
  const handleCreateWorkflow = () => {
    setSelectedWorkflow(null);
    setDesignerModalOpen(true);
  };
  
  const handleWorkflowClick = (workflow: Workflow) => {
    setSelectedWorkflow(workflow);
    setDetailsModalOpen(true);
  };
  
  const handleWorkflowAction = async (action: string, workflow: Workflow) => {
    try {
      switch (action) {
        case 'view':
          setSelectedWorkflow(workflow);
          setDetailsModalOpen(true);
          break;
          
        case 'edit':
          setSelectedWorkflow(workflow);
          setDesignerModalOpen(true);
          break;
          
        case 'execute':
          setSelectedWorkflow(workflow);
          setExecutionModalOpen(true);
          break;
          
        case 'activate':
          await workflowApi.activateWorkflow(workflow.id);
          toast.success(`Workflow "${workflow.name}" activated successfully`);
          fetchWorkflows();
          break;
          
        case 'deactivate':
          await workflowApi.deactivateWorkflow(workflow.id);
          toast.success(`Workflow "${workflow.name}" deactivated successfully`);
          fetchWorkflows();
          break;
          
        case 'archive':
          await workflowApi.archiveWorkflow(workflow.id);
          toast.success(`Workflow "${workflow.name}" archived successfully`);
          fetchWorkflows();
          break;
          
        case 'clone':
          const newName = `${workflow.name} (Copy)`;
          await workflowApi.cloneWorkflow(workflow.id, { name: newName });
          toast.success(`Workflow cloned successfully as "${newName}"`);
          fetchWorkflows();
          break;
          
        case 'export':
          const response = await workflowApi.exportWorkflow(workflow.id);
          // Create a download link
          const blob = new Blob([JSON.stringify(response.data, null, 2)], { type: 'application/json' });
          const url = window.URL.createObjectURL(blob);
          const link = document.createElement('a');
          link.href = url;
          link.setAttribute('download', `workflow_${workflow.id}_${workflow.name.replace(/\s+/g, '_')}.json`);
          document.body.appendChild(link);
          link.click();
          link.remove();
          break;
          
        case 'delete':
          if (window.confirm(`Are you sure you want to delete workflow "${workflow.name}"? This action cannot be undone.`)) {
            await workflowApi.deleteWorkflow(workflow.id);
            toast.success(`Workflow "${workflow.name}" deleted successfully`);
            fetchWorkflows();
          }
          break;
      }
    } catch (error) {
      console.error(`Error performing action ${action}:`, error);
      toast.error(`Failed to ${action} workflow`);
    }
  };
  
  const handleImportWorkflow = () => {
    setImportModalOpen(true);
  };
  
  const handleCreateCategory = () => {
    setCategoryModalOpen(true);
  };
  
  const getStatusBadge = (status: string) => {
    switch (status) {
      case 'draft':
        return <Badge color="secondary">Draft</Badge>;
      case 'active':
        return <Badge color="success">Active</Badge>;
      case 'inactive':
        return <Badge color="warning">Inactive</Badge>;
      case 'archived':
        return <Badge color="dark">Archived</Badge>;
      default:
        return <Badge color="light">Unknown</Badge>;
    }
  };
  
  const getTriggerIcon = (triggerType: string) => {
    switch (triggerType) {
      case 'manual':
        return <FontAwesomeIcon icon={faPlay} className="text-primary" />;
      case 'event':
        return <FontAwesomeIcon icon={faBell} className="text-warning" />;
      case 'scheduled':
        return <FontAwesomeIcon icon={faCalendarAlt} className="text-info" />;
      default:
        return <FontAwesomeIcon icon={faPlay} className="text-primary" />;
    }
  };
  
  const formatDate = (dateString: string) => {
    try {
      return format(parseISO(dateString), 'MMM d, yyyy h:mm a');
    } catch (error) {
      return dateString;
    }
  };
  
  const renderWorkflowList = () => {
    if (loading) {
      return (
        <div className="text-center py-5">
          <Spinner color="primary" />
          <p className="mt-2">Loading workflows...</p>
        </div>
      );
    }
    
    if (filteredWorkflows.length === 0) {
      return (
        <Alert color="info" className="text-center">
          No workflows found. {searchTerm || selectedCategory ? 'Try adjusting your filters.' : 'Create a workflow to get started.'}
        </Alert>
      );
    }
    
    return (
      <Table responsive hover>
        <thead>
          <tr>
            <th onClick={() => handleSort('name')} style={{ cursor: 'pointer' }}>
              Name
              {sortField === 'name' && (
                <FontAwesomeIcon icon={sortDirection === 'asc' ? faSortUp : faSortDown} className="ms-1" />
              )}
            </th>
            <th onClick={() => handleSort('category')} style={{ cursor: 'pointer' }}>
              Category
              {sortField === 'category' && (
                <FontAwesomeIcon icon={sortDirection === 'asc' ? faSortUp : faSortDown} className="ms-1" />
              )}
            </th>
            <th onClick={() => handleSort('trigger_type')} style={{ cursor: 'pointer' }}>
              Trigger
              {sortField === 'trigger_type' && (
                <FontAwesomeIcon icon={sortDirection === 'asc' ? faSortUp : faSortDown} className="ms-1" />
              )}
            </th>
            <th onClick={() => handleSort('status')} style={{ cursor: 'pointer' }}>
              Status
              {sortField === 'status' && (
                <FontAwesomeIcon icon={sortDirection === 'asc' ? faSortUp : faSortDown} className="ms-1" />
              )}
            </th>
            <th onClick={() => handleSort('execution_count')} style={{ cursor: 'pointer' }}>
              Executions
              {sortField === 'execution_count' && (
                <FontAwesomeIcon icon={sortDirection === 'asc' ? faSortUp : faSortDown} className="ms-1" />
              )}
            </th>
            <th onClick={() => handleSort('updated_at')} style={{ cursor: 'pointer' }}>
              Last Updated
              {sortField === 'updated_at' && (
                <FontAwesomeIcon icon={sortDirection === 'asc' ? faSortUp : faSortDown} className="ms-1" />
              )}
            </th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          {filteredWorkflows.map(workflow => (
            <tr key={workflow.id} onClick={() => handleWorkflowClick(workflow)} style={{ cursor: 'pointer' }}>
              <td>
                <div className="d-flex align-items-center">
                  <FontAwesomeIcon icon={faCodeBranch} className="me-2 text-primary" />
                  <div>
                    <div className="fw-bold">{workflow.name}</div>
                    {workflow.description && (
                      <small className="text-muted">{workflow.description}</small>
                    )}
                  </div>
                </div>
              </td>
              <td>
                <Badge color="info" className="category-badge">
                  {workflow.category}
                </Badge>
              </td>
              <td>
                <div className="d-flex align-items-center">
                  {getTriggerIcon(workflow.trigger_type)}
                  <span className="ms-2">{workflow.trigger_type.charAt(0).toUpperCase() + workflow.trigger_type.slice(1)}</span>
                </div>
              </td>
              <td>{getStatusBadge(workflow.status)}</td>
              <td>
                {workflow.execution_count !== undefined ? (
                  <div>
                    <div>{workflow.execution_count}</div>
                    {workflow.success_rate !== undefined && (
                      <small className={workflow.success_rate >= 90 ? 'text-success' : workflow.success_rate >= 70 ? 'text-warning' : 'text-danger'}>
                        {workflow.success_rate}% success
                      </small>
                    )}
                  </div>
                ) : (
                  <span>-</span>
                )}
              </td>
              <td>{workflow.updated_at ? formatDate(workflow.updated_at) : formatDate(workflow.created_at)}</td>
              <td onClick={(e) => e.stopPropagation()}>
                <div className="d-flex">
                  {workflow.status === 'active' && (
                    <Button color="primary" size="sm" className="me-2" title="Execute" onClick={(e) => {
                      e.stopPropagation();
                      handleWorkflowAction('execute', workflow);
                    }}>
                      <FontAwesomeIcon icon={faPlay} />
                    </Button>
                  )}
                  <Button color="secondary" size="sm" className="me-2" title="Edit" onClick={(e) => {
                    e.stopPropagation();
                    handleWorkflowAction('edit', workflow);
                  }}>
                    <FontAwesomeIcon icon={faEdit} />
                  </Button>
                  <Dropdown isOpen={actionsDropdownOpen === workflow.id} toggle={() => setActionsDropdownOpen(actionsDropdownOpen === workflow.id ? null : workflow.id)}>
                    <DropdownToggle color="light" size="sm">
                      <FontAwesomeIcon icon={faEllipsisV} />
                    </DropdownToggle>
                    <DropdownMenu end>
                      {workflow.status === 'draft' || workflow.status === 'inactive' ? (
                        <DropdownItem onClick={() => handleWorkflowAction('activate', workflow)}>
                          <FontAwesomeIcon icon={faPlay} className="me-2 text-success" /> Activate
                        </DropdownItem>
                      ) : workflow.status === 'active' ? (
                        <DropdownItem onClick={() => handleWorkflowAction('deactivate', workflow)}>
                          <FontAwesomeIcon icon={faPause} className="me-2 text-warning" /> Deactivate
                        </DropdownItem>
                      ) : null}
                      
                      {workflow.status !== 'archived' && (
                        <DropdownItem onClick={() => handleWorkflowAction('archive', workflow)}>
                          <FontAwesomeIcon icon={faArchive} className="me-2 text-secondary" /> Archive
                        </DropdownItem>
                      )}
                      
                      <DropdownItem onClick={() => handleWorkflowAction('clone', workflow)}>
                        <FontAwesomeIcon icon={faClone} className="me-2 text-info" /> Clone
                      </DropdownItem>
                      
                      <DropdownItem onClick={() => handleWorkflowAction('export', workflow)}>
                        <FontAwesomeIcon icon={faDownload} className="me-2 text-primary" /> Export
                      </DropdownItem>
                      
                      <DropdownItem divider />
                      
                      <DropdownItem onClick={() => handleWorkflowAction('delete', workflow)} className="text-danger">
                        <FontAwesomeIcon icon={faTrash} className="me-2" /> Delete
                      </DropdownItem>
                    </DropdownMenu>
                  </Dropdown>
                </div>
              </td>
            </tr>
          ))}
        </tbody>
      </Table>
    );
  };
  
  return (
    <Container fluid className="workflow-engine-container">
      <Row className="mb-4">
        <Col>
          <h2 className="page-title">Workflow Engine</h2>
        </Col>
        <Col xs="auto">
          <Button color="primary" className="me-2" onClick={handleCreateWorkflow}>
            <FontAwesomeIcon icon={faPlus} className="me-2" /> Create Workflow
          </Button>
          <Button color="secondary" onClick={handleImportWorkflow}>
            <FontAwesomeIcon icon={faUpload} className="me-2" /> Import
          </Button>
        </Col>
      </Row>
      
      <Row className="mb-4">
        <Col md={3}>
          <Card className="metric-card">
            <CardBody>
              <div className="d-flex justify-content-between align-items-center">
                <div>
                  <h6 className="text-muted mb-1">Total Workflows</h6>
                  <h3 className="mb-0">{performanceMetrics.total_workflows}</h3>
                </div>
                <div className="metric-icon bg-primary">
                  <FontAwesomeIcon icon={faCodeBranch} />
                </div>
              </div>
              <div className="mt-2">
                <small className="text-muted">
                  {performanceMetrics.active_workflows} active workflows
                </small>
              </div>
            </CardBody>
          </Card>
        </Col>
        <Col md={3}>
          <Card className="metric-card">
            <CardBody>
              <div className="d-flex justify-content-between align-items-center">
                <div>
                  <h6 className="text-muted mb-1">Total Executions</h6>
                  <h3 className="mb-0">{performanceMetrics.total_executions}</h3>
                </div>
                <div className="metric-icon bg-success">
                  <FontAwesomeIcon icon={faHistory} />
                </div>
              </div>
              <div className="mt-2">
                <small className="text-muted">
                  {performanceMetrics.executions_today} executions today
                </small>
              </div>
            </CardBody>
          </Card>
        </Col>
        <Col md={3}>
          <Card className="metric-card">
            <CardBody>
              <div className="d-flex justify-content-between align-items-center">
                <div>
                  <h6 className="text-muted mb-1">Success Rate</h6>
                  <h3 className="mb-0">{performanceMetrics.success_rate}%</h3>
                </div>
                <div className="metric-icon bg-warning">
                  <FontAwesomeIcon icon={faCheckDouble} />
                </div>
              </div>
              <div className="mt-2">
                <Progress value={performanceMetrics.success_rate} color={
                  performanceMetrics.success_rate >= 90 ? 'success' :
                  performanceMetrics.success_rate >= 70 ? 'warning' : 'danger'
                } />
              </div>
            </CardBody>
          </Card>
        </Col>
        <Col md={3}>
          <Card className="metric-card">
            <CardBody>
              <div className="d-flex justify-content-between align-items-center">
                <div>
                  <h6 className="text-muted mb-1">Pending Approvals</h6>
                  <h3 className="mb-0">{performanceMetrics.pending_approvals}</h3>
                </div>
                <div className="metric-icon bg-danger">
                  <FontAwesomeIcon icon={faUserClock} />
                </div>
              </div>
              <div className="mt-2">
                <small className="text-muted">
                  Avg. approval time: {performanceMetrics.avg_approval_time || 'N/A'}
                </small>
              </div>
            </CardBody>
          </Card>
        </Col>
      </Row>
      
      <Row>
        <Col md={3} className="mb-4">
          <Card className="sidebar-card">
            <CardHeader>
              <h5 className="mb-0">Workflow Status</h5>
            </CardHeader>
            <CardBody className="p-0">
              <Nav vertical className="workflow-nav">
                <NavItem>
                  <NavLink
                    className={activeTab === 'all' ? 'active' : ''}
                    onClick={() => handleTabChange('all')}
                  >
                    <FontAwesomeIcon icon={faListAlt} className="me-2" /> All Workflows
                  </NavLink>
                </NavItem>
                <NavItem>
                  <NavLink
                    className={activeTab === 'active' ? 'active' : ''}
                    onClick={() => handleTabChange('active')}
                  >
                    <FontAwesomeIcon icon={faPlay} className="me-2" /> Active
                  </NavLink>
                </NavItem>
                <NavItem>
                  <NavLink
                    className={activeTab === 'draft' ? 'active' : ''}
                    onClick={() => handleTabChange('draft')}
                  >
                    <FontAwesomeIcon icon={faEdit} className="me-2" /> Drafts
                  </NavLink>
                </NavItem>
                <NavItem>
                  <NavLink
                    className={activeTab === 'inactive' ? 'active' : ''}
                    onClick={() => handleTabChange('inactive')}
                  >
                    <FontAwesomeIcon icon={faPause} className="me-2" /> Inactive
                  </NavLink>
                </NavItem>
                <NavItem>
                  <NavLink
                    className={activeTab === 'archived' ? 'active' : ''}
                    onClick={() => handleTabChange('archived')}
                  >
                    <FontAwesomeIcon icon={faArchive} className="me-2" /> Archived
                  </NavLink>
                </NavItem>
                <NavItem>
                  <NavLink
                    className={activeTab === 'templates' ? 'active' : ''}
                    onClick={() => handleTabChange('templates')}
                  >
                    <FontAwesomeIcon icon={faClone} className="me-2" /> Templates
                  </NavLink>
                </NavItem>
              </Nav>
            </CardBody>
          </Card>
          
          <Card className="sidebar-card mt-4">
            <CardHeader className="d-flex justify-content-between align-items-center">
              <h5 className="mb-0">Categories</h5>
              <Button color="link" className="p-0" onClick={handleCreateCategory}>
                <FontAwesomeIcon icon={faPlus} />
              </Button>
            </CardHeader>
            <CardBody className="p-0">
              <Nav vertical className="workflow-nav">
                {categories.map(category => (
                  <NavItem key={category}>
                    <NavLink
                      className={selectedCategory === category ? 'active' : ''}
                      onClick={() => handleCategoryClick(category)}
                    >
                      <FontAwesomeIcon icon={faTag} className="me-2" /> {category}
                    </NavLink>
                  </NavItem>
                ))}
                {categories.length === 0 && (
                  <div className="p-3 text-muted">
                    No categories found. Create a category to organize your workflows.
                  </div>
                )}
              </Nav>
            </CardBody>
          </Card>
        </Col>
        
        <Col md={9}>
          <Card className="main-card">
            <CardHeader>
              <Row className="align-items-center">
                <Col>
                  <InputGroup>
                    <InputGroupText>
                      <FontAwesomeIcon icon={faSearch} />
                    </InputGroupText>
                    <Input
                      placeholder="Search workflows..."
                      value={searchTerm}
                      onChange={(e) => setSearchTerm(e.target.value)}
                    />
                  </InputGroup>
                </Col>
                <Col xs="auto">
                  <Dropdown isOpen={filterDropdownOpen} toggle={() => setFilterDropdownOpen(!filterDropdownOpen)} className="me-2">
                    <DropdownToggle color="light" caret>
                      <FontAwesomeIcon icon={faFilter} className="me-1" /> Filter
                    </DropdownToggle>
                    <DropdownMenu>
                      <DropdownItem header>Trigger Type</DropdownItem>
                      <DropdownItem>Manual</DropdownItem>
                      <DropdownItem>Event</DropdownItem>
                      <DropdownItem>Scheduled</DropdownItem>
                      <DropdownItem divider />
                      <DropdownItem header>Date Range</DropdownItem>
                      <DropdownItem>Today</DropdownItem>
                      <DropdownItem>This Week</DropdownItem>
                      <DropdownItem>This Month</DropdownItem>
                      <DropdownItem>This Year</DropdownItem>
                    </DropdownMenu>
                  </Dropdown>
                  
                  <Dropdown isOpen={sortDropdownOpen} toggle={() => setSortDropdownOpen(!sortDropdownOpen)}>
                    <DropdownToggle color="light" caret>
                      <FontAwesomeIcon icon={faSort} className="me-1" /> Sort
                    </DropdownToggle>
                    <DropdownMenu>
                      <DropdownItem onClick={() => handleSort('name')}>
                        Name {sortField === 'name' && (
                          <FontAwesomeIcon icon={sortDirection === 'asc' ? faSortUp : faSortDown} className="ms-1" />
                        )}
                      </DropdownItem>
                      <DropdownItem onClick={() => handleSort('category')}>
                        Category {sortField === 'category' && (
                          <FontAwesomeIcon icon={sortDirection === 'asc' ? faSortUp : faSortDown} className="ms-1" />
                        )}
                      </DropdownItem>
                      <DropdownItem onClick={() => handleSort('status')}>
                        Status {sortField === 'status' && (
                          <FontAwesomeIcon icon={sortDirection === 'asc' ? faSortUp : faSortDown} className="ms-1" />
                        )}
                      </DropdownItem>
                      <DropdownItem onClick={() => handleSort('execution_count')}>
                        Executions {sortField === 'execution_count' && (
                          <FontAwesomeIcon icon={sortDirection === 'asc' ? faSortUp : faSortDown} className="ms-1" />
                        )}
                      </DropdownItem>
                      <DropdownItem onClick={() => handleSort('created_at')}>
                        Date Created {sortField === 'created_at' && (
                          <FontAwesomeIcon icon={sortDirection === 'asc' ? faSortUp : faSortDown} className="ms-1" />
                        )}
                      </DropdownItem>
                      <DropdownItem onClick={() => handleSort('updated_at')}>
                        Last Updated {sortField === 'updated_at' && (
                          <FontAwesomeIcon icon={sortDirection === 'asc' ? faSortUp : faSortDown} className="ms-1" />
                        )}
                      </DropdownItem>
                    </DropdownMenu>
                  </Dropdown>
                </Col>
              </Row>
            </CardHeader>
            <CardBody>
              <TabContent activeTab={activeTab}>
                <TabPane tabId={activeTab}>
                  {renderWorkflowList()}
                </TabPane>
              </TabContent>
            </CardBody>
          </Card>
        </Col>
      </Row>
      
      {/* Modals */}
      <WorkflowDesignerModal
        isOpen={designerModalOpen}
        toggle={() => setDesignerModalOpen(!designerModalOpen)}
        workflow={selectedWorkflow}
        categories={categories}
        onSuccess={() => {
          setDesignerModalOpen(false);
          fetchWorkflows();
        }}
      />
      
      <WorkflowDetailsModal
        isOpen={detailsModalOpen}
        toggle={() => setDetailsModalOpen(!detailsModalOpen)}
        workflow={selectedWorkflow}
        onAction={(action) => {
          if (selectedWorkflow) {
            handleWorkflowAction(action, selectedWorkflow);
          }
          if (action !== 'view') {
            setDetailsModalOpen(false);
          }
        }}
      />
      
      <WorkflowExecutionModal
        isOpen={executionModalOpen}
        toggle={() => setExecutionModalOpen(!executionModalOpen)}
        workflow={selectedWorkflow}
        onSuccess={() => {
          setExecutionModalOpen(false);
          fetchWorkflows();
        }}
      />
      
      <WorkflowImportModal
        isOpen={importModalOpen}
        toggle={() => setImportModalOpen(!importModalOpen)}
        onSuccess={() => {
          setImportModalOpen(false);
          fetchWorkflows();
        }}
      />
      
      <WorkflowCategoryModal
        isOpen={categoryModalOpen}
        toggle={() => setCategoryModalOpen(!categoryModalOpen)}
        categories={categories}
        onSuccess={() => {
          setCategoryModalOpen(false);
          fetchCategories();
        }}
      />
    </Container>
  );
};

export default WorkflowEngine;