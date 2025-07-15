import React, { useState, useEffect } from 'react';
import '../../styles/WorkflowManagement.css';
import {
  Container,
  Row,
  Col,
  Card,
  CardHeader,
  CardBody,
  Button,
  Nav,
  NavItem,
  NavLink,
  TabContent,
  TabPane,
  Table,
  Badge,
  Input,
  InputGroup,
  InputGroupText,
  Dropdown,
  DropdownToggle,
  DropdownMenu,
  DropdownItem,
  Spinner,
  Alert,
  Progress
} from 'reactstrap';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import {
  faPlus,
  faSearch,
  faFilter,
  faPlay,
  faPause,
  faStop,
  faEdit,
  faTrash,
  faCopy,
  faEye,
  faChartLine,
  faUsers,
  faClock,
  faCheckCircle,
  faExclamationTriangle,
  faTimesCircle,
  faEllipsisV,
  faDownload,
  faUpload,
  faCog,
  faHistory,
  faFileAlt,
  faProjectDiagram,
  faTasks,
  faUserCheck
} from '@fortawesome/free-solid-svg-icons';
import { format, parseISO } from 'date-fns';
import { toast } from 'react-toastify';

import {
  WorkflowDefinition,
  WorkflowInstance,
  workflowDefinitionApi,
  workflowInstanceApi,
  workflowApprovalApi,
  workflowAnalyticsApi
} from '../../services/workflowManagementApi';
import WorkflowDesignerModal from './WorkflowDesignerModal';
import WorkflowInstanceModal from './WorkflowInstanceModal';
import StartWorkflowModal from './StartWorkflowModal';
import WorkflowTemplateModal from './WorkflowTemplateModal';

const WorkflowManagement: React.FC = () => {
  // State
  const [activeTab, setActiveTab] = useState<string>('definitions');
  const [workflowDefinitions, setWorkflowDefinitions] = useState<WorkflowDefinition[]>([]);
  const [workflowInstances, setWorkflowInstances] = useState<WorkflowInstance[]>([]);
  const [pendingApprovals, setPendingApprovals] = useState<any[]>([]);
  const [dashboardData, setDashboardData] = useState<any>(null);
  const [loading, setLoading] = useState<boolean>(true);
  const [searchTerm, setSearchTerm] = useState<string>('');
  const [filterStatus, setFilterStatus] = useState<string>('all');
  const [filterCategory, setFilterCategory] = useState<string>('all');
  
  // Modals
  const [designerModalOpen, setDesignerModalOpen] = useState<boolean>(false);
  const [instanceModalOpen, setInstanceModalOpen] = useState<boolean>(false);
  const [startWorkflowModalOpen, setStartWorkflowModalOpen] = useState<boolean>(false);
  const [templateModalOpen, setTemplateModalOpen] = useState<boolean>(false);
  const [selectedWorkflow, setSelectedWorkflow] = useState<WorkflowDefinition | null>(null);
  const [selectedInstance, setSelectedInstance] = useState<WorkflowInstance | null>(null);
  
  // Dropdowns
  const [actionDropdownOpen, setActionDropdownOpen] = useState<number | null>(null);
  const [filterDropdownOpen, setFilterDropdownOpen] = useState<boolean>(false);

  // Fetch data
  useEffect(() => {
    fetchData();
  }, [activeTab]);

  const fetchData = async () => {
    setLoading(true);
    try {
      switch (activeTab) {
        case 'definitions':
          await fetchWorkflowDefinitions();
          break;
        case 'instances':
          await fetchWorkflowInstances();
          break;
        case 'approvals':
          await fetchPendingApprovals();
          break;
        case 'dashboard':
          await fetchDashboardData();
          break;
      }
    } catch (error) {
      console.error('Error fetching data:', error);
      toast.error('Failed to load data');
    } finally {
      setLoading(false);
    }
  };

  const fetchWorkflowDefinitions = async () => {
    const response = await workflowDefinitionApi.getWorkflowDefinitions();
    setWorkflowDefinitions(response.data);
  };

  const fetchWorkflowInstances = async () => {
    const response = await workflowInstanceApi.getWorkflowInstances();
    setWorkflowInstances(response.data);
  };

  const fetchPendingApprovals = async () => {
    const response = await workflowApprovalApi.getPendingApprovals();
    setPendingApprovals(response.data);
  };

  const fetchDashboardData = async () => {
    const response = await workflowAnalyticsApi.getDashboardData();
    setDashboardData(response.data);
  };

  const handleCreateWorkflow = () => {
    setSelectedWorkflow(null);
    setDesignerModalOpen(true);
  };

  const handleEditWorkflow = (workflow: WorkflowDefinition) => {
    setSelectedWorkflow(workflow);
    setDesignerModalOpen(true);
  };

  const handleStartWorkflow = (workflow?: WorkflowDefinition) => {
    setSelectedWorkflow(workflow || null);
    setStartWorkflowModalOpen(true);
  };

  const handleViewInstance = (instance: WorkflowInstance) => {
    setSelectedInstance(instance);
    setInstanceModalOpen(true);
  };

  const handleWorkflowAction = async (action: string, workflow: WorkflowDefinition) => {
    try {
      switch (action) {
        case 'activate':
          await workflowDefinitionApi.activateWorkflowDefinition(workflow.id);
          toast.success('Workflow activated successfully');
          break;
        case 'deactivate':
          await workflowDefinitionApi.deactivateWorkflowDefinition(workflow.id);
          toast.success('Workflow deactivated successfully');
          break;
        case 'clone':
          const cloneData = {
            name: `${workflow.name} (Copy)`,
            description: workflow.description
          };
          await workflowDefinitionApi.cloneWorkflowDefinition(workflow.id, cloneData);
          toast.success('Workflow cloned successfully');
          break;
        case 'delete':
          if (window.confirm(`Are you sure you want to delete "${workflow.name}"?`)) {
            await workflowDefinitionApi.deleteWorkflowDefinition(workflow.id);
            toast.success('Workflow deleted successfully');
          }
          break;
        case 'export':
          const exportData = await workflowDefinitionApi.exportWorkflowDefinition(workflow.id);
          // Handle export download
          toast.success('Workflow exported successfully');
          break;
      }
      fetchData();
    } catch (error) {
      console.error(`Error performing action ${action}:`, error);
      toast.error(`Failed to ${action} workflow`);
    }
  };

  const handleInstanceAction = async (action: string, instance: WorkflowInstance) => {
    try {
      switch (action) {
        case 'cancel':
          await workflowInstanceApi.cancelWorkflowInstance(instance.id);
          toast.success('Workflow instance cancelled');
          break;
        case 'pause':
          await workflowInstanceApi.pauseWorkflowInstance(instance.id);
          toast.success('Workflow instance paused');
          break;
        case 'resume':
          await workflowInstanceApi.resumeWorkflowInstance(instance.id);
          toast.success('Workflow instance resumed');
          break;
        case 'retry':
          await workflowInstanceApi.retryWorkflowInstance(instance.id);
          toast.success('Workflow instance retried');
          break;
      }
      fetchData();
    } catch (error) {
      console.error(`Error performing action ${action}:`, error);
      toast.error(`Failed to ${action} workflow instance`);
    }
  };

  const handleApprovalAction = async (action: string, approval: any) => {
    try {
      switch (action) {
        case 'approve':
          await workflowApprovalApi.approveWorkflowStep(approval.id, {});
          toast.success('Approval granted');
          break;
        case 'reject':
          const reason = prompt('Please provide a reason for rejection:');
          if (reason) {
            await workflowApprovalApi.rejectWorkflowStep(approval.id, { comments: reason });
            toast.success('Approval rejected');
          }
          break;
      }
      fetchData();
    } catch (error) {
      console.error(`Error performing approval action ${action}:`, error);
      toast.error(`Failed to ${action} approval`);
    }
  };

  const getStatusBadge = (status: string, type: 'workflow' | 'instance' = 'workflow') => {
    const statusConfig = {
      workflow: {
        draft: { color: 'secondary', text: 'Draft' },
        active: { color: 'success', text: 'Active' },
        inactive: { color: 'warning', text: 'Inactive' },
        archived: { color: 'dark', text: 'Archived' }
      },
      instance: {
        pending: { color: 'info', text: 'Pending' },
        in_progress: { color: 'primary', text: 'In Progress' },
        completed: { color: 'success', text: 'Completed' },
        failed: { color: 'danger', text: 'Failed' },
        cancelled: { color: 'secondary', text: 'Cancelled' },
        paused: { color: 'warning', text: 'Paused' }
      }
    };

    const config = statusConfig[type][status as keyof typeof statusConfig[typeof type]] || 
                   { color: 'secondary', text: status };
    
    return <Badge color={config.color}>{config.text}</Badge>;
  };

  const formatDate = (dateString: string) => {
    try {
      return format(parseISO(dateString), 'MMM d, yyyy h:mm a');
    } catch (error) {
      return dateString;
    }
  };

  const renderWorkflowDefinitions = () => {
    const filteredWorkflows = workflowDefinitions.filter(workflow => {
      const matchesSearch = workflow.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
                           (workflow.description && workflow.description.toLowerCase().includes(searchTerm.toLowerCase()));
      const matchesStatus = filterStatus === 'all' || workflow.status === filterStatus;
      const matchesCategory = filterCategory === 'all' || workflow.category === filterCategory;
      
      return matchesSearch && matchesStatus && matchesCategory;
    });

    if (filteredWorkflows.length === 0) {
      return (
        <Alert color="info">
          No workflow definitions found. {searchTerm || filterStatus !== 'all' || filterCategory !== 'all' ? 
            'Try adjusting your filters.' : 'Create your first workflow to get started.'}
        </Alert>
      );
    }

    return (
      <Table responsive striped hover>
        <thead>
          <tr>
            <th>Name</th>
            <th>Category</th>
            <th>Version</th>
            <th>Status</th>
            <th>Trigger</th>
            <th>Steps</th>
            <th>Created</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          {filteredWorkflows.map(workflow => (
            <tr key={workflow.id}>
              <td>
                <div>
                  <strong>{workflow.name}</strong>
                  {workflow.description && (
                    <div className="text-muted small">{workflow.description}</div>
                  )}
                </div>
              </td>
              <td>
                <Badge color="info">{workflow.category}</Badge>
              </td>
              <td>v{workflow.version}</td>
              <td>{getStatusBadge(workflow.status, 'workflow')}</td>
              <td>
                <Badge color="light">{workflow.trigger_type}</Badge>
              </td>
              <td>{workflow.steps?.length || 0}</td>
              <td>{formatDate(workflow.created_at)}</td>
              <td>
                <Button color="primary" size="sm" className="me-1" onClick={() => handleStartWorkflow(workflow)}>
                  <FontAwesomeIcon icon={faPlay} />
                </Button>
                <Button color="info" size="sm" className="me-1" onClick={() => handleEditWorkflow(workflow)}>
                  <FontAwesomeIcon icon={faEdit} />
                </Button>
                <Dropdown 
                  isOpen={actionDropdownOpen === workflow.id} 
                  toggle={() => setActionDropdownOpen(actionDropdownOpen === workflow.id ? null : workflow.id)}
                >
                  <DropdownToggle color="light" size="sm" caret>
                    <FontAwesomeIcon icon={faEllipsisV} />
                  </DropdownToggle>
                  <DropdownMenu>
                    {workflow.status === 'active' ? (
                      <DropdownItem onClick={() => handleWorkflowAction('deactivate', workflow)}>
                        <FontAwesomeIcon icon={faPause} className="me-2" /> Deactivate
                      </DropdownItem>
                    ) : (
                      <DropdownItem onClick={() => handleWorkflowAction('activate', workflow)}>
                        <FontAwesomeIcon icon={faPlay} className="me-2" /> Activate
                      </DropdownItem>
                    )}
                    <DropdownItem onClick={() => handleWorkflowAction('clone', workflow)}>
                      <FontAwesomeIcon icon={faCopy} className="me-2" /> Clone
                    </DropdownItem>
                    <DropdownItem onClick={() => handleWorkflowAction('export', workflow)}>
                      <FontAwesomeIcon icon={faDownload} className="me-2" /> Export
                    </DropdownItem>
                    <DropdownItem divider />
                    <DropdownItem onClick={() => handleWorkflowAction('delete', workflow)} className="text-danger">
                      <FontAwesomeIcon icon={faTrash} className="me-2" /> Delete
                    </DropdownItem>
                  </DropdownMenu>
                </Dropdown>
              </td>
            </tr>
          ))}
        </tbody>
      </Table>
    );
  };

  const renderWorkflowInstances = () => {
    const filteredInstances = workflowInstances.filter(instance => {
      const matchesSearch = (instance.instance_name && instance.instance_name.toLowerCase().includes(searchTerm.toLowerCase())) ||
                           (instance.workflow_name && instance.workflow_name.toLowerCase().includes(searchTerm.toLowerCase()));
      const matchesStatus = filterStatus === 'all' || instance.status === filterStatus;
      
      return matchesSearch && matchesStatus;
    });

    if (filteredInstances.length === 0) {
      return (
        <Alert color="info">
          No workflow instances found. {searchTerm || filterStatus !== 'all' ? 
            'Try adjusting your filters.' : 'Start a workflow to see instances here.'}
        </Alert>
      );
    }

    return (
      <Table responsive striped hover>
        <thead>
          <tr>
            <th>Instance Name</th>
            <th>Workflow</th>
            <th>Status</th>
            <th>Priority</th>
            <th>Progress</th>
            <th>Initiated</th>
            <th>Due Date</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          {filteredInstances.map(instance => (
            <tr key={instance.id}>
              <td>
                <div>
                  <strong>{instance.instance_name || `Instance #${instance.id}`}</strong>
                  <div className="text-muted small">ID: {instance.id}</div>
                </div>
              </td>
              <td>{instance.workflow_name}</td>
              <td>{getStatusBadge(instance.status, 'instance')}</td>
              <td>
                <Badge color={
                  instance.priority === 'urgent' ? 'danger' :
                  instance.priority === 'high' ? 'warning' :
                  instance.priority === 'normal' ? 'info' : 'secondary'
                }>
                  {instance.priority}
                </Badge>
              </td>
              <td>
                <div className="d-flex align-items-center">
                  <Progress 
                    value={instance.current_step ? (instance.current_step / 10) * 100 : 0} 
                    className="me-2" 
                    style={{ width: '100px', height: '8px' }}
                  />
                  <small>{instance.current_step || 0}/10</small>
                </div>
              </td>
              <td>{formatDate(instance.initiated_at)}</td>
              <td>{instance.due_date ? formatDate(instance.due_date) : '-'}</td>
              <td>
                <Button color="info" size="sm" className="me-1" onClick={() => handleViewInstance(instance)}>
                  <FontAwesomeIcon icon={faEye} />
                </Button>
                {instance.status === 'in_progress' && (
                  <Button color="warning" size="sm" className="me-1" onClick={() => handleInstanceAction('pause', instance)}>
                    <FontAwesomeIcon icon={faPause} />
                  </Button>
                )}
                {instance.status === 'paused' && (
                  <Button color="success" size="sm" className="me-1" onClick={() => handleInstanceAction('resume', instance)}>
                    <FontAwesomeIcon icon={faPlay} />
                  </Button>
                )}
                {instance.status === 'failed' && (
                  <Button color="primary" size="sm" className="me-1" onClick={() => handleInstanceAction('retry', instance)}>
                    <FontAwesomeIcon icon={faHistory} />
                  </Button>
                )}
                {['pending', 'in_progress', 'paused'].includes(instance.status) && (
                  <Button color="danger" size="sm" onClick={() => handleInstanceAction('cancel', instance)}>
                    <FontAwesomeIcon icon={faStop} />
                  </Button>
                )}
              </td>
            </tr>
          ))}
        </tbody>
      </Table>
    );
  };

  const renderPendingApprovals = () => {
    if (pendingApprovals.length === 0) {
      return (
        <Alert color="success">
          <FontAwesomeIcon icon={faCheckCircle} className="me-2" />
          No pending approvals. Great job staying on top of your tasks!
        </Alert>
      );
    }

    return (
      <Table responsive striped hover>
        <thead>
          <tr>
            <th>Workflow Instance</th>
            <th>Step</th>
            <th>Requested By</th>
            <th>Priority</th>
            <th>Due Date</th>
            <th>Waiting Since</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          {pendingApprovals.map(approval => (
            <tr key={approval.id}>
              <td>
                <div>
                  <strong>{approval.workflow_instance_name}</strong>
                  <div className="text-muted small">{approval.workflow_name}</div>
                </div>
              </td>
              <td>{approval.step_name}</td>
              <td>User ID: {approval.requested_by}</td>
              <td>
                <Badge color={
                  approval.priority === 'urgent' ? 'danger' :
                  approval.priority === 'high' ? 'warning' :
                  approval.priority === 'normal' ? 'info' : 'secondary'
                }>
                  {approval.priority}
                </Badge>
              </td>
              <td>{approval.due_date ? formatDate(approval.due_date) : '-'}</td>
              <td>{formatDate(approval.created_at)}</td>
              <td>
                <Button color="success" size="sm" className="me-1" onClick={() => handleApprovalAction('approve', approval)}>
                  <FontAwesomeIcon icon={faCheckCircle} /> Approve
                </Button>
                <Button color="danger" size="sm" onClick={() => handleApprovalAction('reject', approval)}>
                  <FontAwesomeIcon icon={faTimesCircle} /> Reject
                </Button>
              </td>
            </tr>
          ))}
        </tbody>
      </Table>
    );
  };

  const renderDashboard = () => {
    if (!dashboardData) {
      return (
        <div className="text-center py-5">
          <Spinner color="primary" />
          <p className="mt-2">Loading dashboard...</p>
        </div>
      );
    }

    return (
      <Row>
        <Col md={3} className="mb-4">
          <Card className="stats-card">
            <CardBody className="text-center">
              <FontAwesomeIcon icon={faProjectDiagram} size="2x" className="text-primary mb-2" />
              <h3>{dashboardData.total_workflows || 0}</h3>
              <p className="text-muted">Total Workflows</p>
            </CardBody>
          </Card>
        </Col>
        <Col md={3} className="mb-4">
          <Card className="stats-card">
            <CardBody className="text-center">
              <FontAwesomeIcon icon={faTasks} size="2x" className="text-info mb-2" />
              <h3>{dashboardData.active_instances || 0}</h3>
              <p className="text-muted">Active Instances</p>
            </CardBody>
          </Card>
        </Col>
        <Col md={3} className="mb-4">
          <Card className="stats-card">
            <CardBody className="text-center">
              <FontAwesomeIcon icon={faUserCheck} size="2x" className="text-warning mb-2" />
              <h3>{dashboardData.pending_approvals || 0}</h3>
              <p className="text-muted">Pending Approvals</p>
            </CardBody>
          </Card>
        </Col>
        <Col md={3} className="mb-4">
          <Card className="stats-card">
            <CardBody className="text-center">
              <FontAwesomeIcon icon={faCheckCircle} size="2x" className="text-success mb-2" />
              <h3>{dashboardData.completion_rate || 0}%</h3>
              <p className="text-muted">Completion Rate</p>
            </CardBody>
          </Card>
        </Col>
      </Row>
    );
  };

  return (
    <Container fluid className="workflow-management-container">
      <Row className="mb-4">
        <Col>
          <h2 className="page-title">Workflow Management</h2>
        </Col>
        <Col xs="auto">
          <Button color="success" className="me-2" onClick={() => setTemplateModalOpen(true)}>
            <FontAwesomeIcon icon={faFileAlt} className="me-1" /> Templates
          </Button>
          <Button color="info" className="me-2" onClick={() => handleStartWorkflow()}>
            <FontAwesomeIcon icon={faPlay} className="me-1" /> Start Workflow
          </Button>
          <Button color="primary" onClick={handleCreateWorkflow}>
            <FontAwesomeIcon icon={faPlus} className="me-1" /> Create Workflow
          </Button>
        </Col>
      </Row>

      <Card>
        <CardHeader>
          <Nav tabs>
            <NavItem>
              <NavLink
                className={activeTab === 'definitions' ? 'active' : ''}
                onClick={() => setActiveTab('definitions')}
              >
                <FontAwesomeIcon icon={faProjectDiagram} className="me-1" /> Workflow Definitions
              </NavLink>
            </NavItem>
            <NavItem>
              <NavLink
                className={activeTab === 'instances' ? 'active' : ''}
                onClick={() => setActiveTab('instances')}
              >
                <FontAwesomeIcon icon={faTasks} className="me-1" /> Running Instances
              </NavLink>
            </NavItem>
            <NavItem>
              <NavLink
                className={activeTab === 'approvals' ? 'active' : ''}
                onClick={() => setActiveTab('approvals')}
              >
                <FontAwesomeIcon icon={faUserCheck} className="me-1" /> Pending Approvals
                {pendingApprovals.length > 0 && (
                  <Badge color="danger" className="ms-1">{pendingApprovals.length}</Badge>
                )}
              </NavLink>
            </NavItem>
            <NavItem>
              <NavLink
                className={activeTab === 'dashboard' ? 'active' : ''}
                onClick={() => setActiveTab('dashboard')}
              >
                <FontAwesomeIcon icon={faChartLine} className="me-1" /> Dashboard
              </NavLink>
            </NavItem>
          </Nav>
        </CardHeader>
        <CardBody>
          {activeTab !== 'dashboard' && (
            <Row className="mb-3">
              <Col md={6}>
                <InputGroup>
                  <InputGroupText>
                    <FontAwesomeIcon icon={faSearch} />
                  </InputGroupText>
                  <Input
                    placeholder="Search..."
                    value={searchTerm}
                    onChange={(e) => setSearchTerm(e.target.value)}
                  />
                </InputGroup>
              </Col>
              <Col md={6} className="text-end">
                <Dropdown isOpen={filterDropdownOpen} toggle={() => setFilterDropdownOpen(!filterDropdownOpen)}>
                  <DropdownToggle color="light" caret>
                    <FontAwesomeIcon icon={faFilter} className="me-1" /> Filter
                  </DropdownToggle>
                  <DropdownMenu>
                    <DropdownItem header>Status</DropdownItem>
                    <DropdownItem onClick={() => setFilterStatus('all')}>All</DropdownItem>
                    {activeTab === 'definitions' && (
                      <>
                        <DropdownItem onClick={() => setFilterStatus('active')}>Active</DropdownItem>
                        <DropdownItem onClick={() => setFilterStatus('inactive')}>Inactive</DropdownItem>
                        <DropdownItem onClick={() => setFilterStatus('draft')}>Draft</DropdownItem>
                      </>
                    )}
                    {activeTab === 'instances' && (
                      <>
                        <DropdownItem onClick={() => setFilterStatus('pending')}>Pending</DropdownItem>
                        <DropdownItem onClick={() => setFilterStatus('in_progress')}>In Progress</DropdownItem>
                        <DropdownItem onClick={() => setFilterStatus('completed')}>Completed</DropdownItem>
                        <DropdownItem onClick={() => setFilterStatus('failed')}>Failed</DropdownItem>
                      </>
                    )}
                  </DropdownMenu>
                </Dropdown>
              </Col>
            </Row>
          )}

          <TabContent activeTab={activeTab}>
            <TabPane tabId="definitions">
              {loading ? (
                <div className="text-center py-5">
                  <Spinner color="primary" />
                </div>
              ) : (
                renderWorkflowDefinitions()
              )}
            </TabPane>
            <TabPane tabId="instances">
              {loading ? (
                <div className="text-center py-5">
                  <Spinner color="primary" />
                </div>
              ) : (
                renderWorkflowInstances()
              )}
            </TabPane>
            <TabPane tabId="approvals">
              {loading ? (
                <div className="text-center py-5">
                  <Spinner color="primary" />
                </div>
              ) : (
                renderPendingApprovals()
              )}
            </TabPane>
            <TabPane tabId="dashboard">
              {loading ? (
                <div className="text-center py-5">
                  <Spinner color="primary" />
                </div>
              ) : (
                renderDashboard()
              )}
            </TabPane>
          </TabContent>
        </CardBody>
      </Card>

      {/* Modals */}
      <WorkflowDesignerModal
        isOpen={designerModalOpen}
        toggle={() => setDesignerModalOpen(!designerModalOpen)}
        workflow={selectedWorkflow}
        onSuccess={() => {
          setDesignerModalOpen(false);
          setSelectedWorkflow(null);
          fetchData();
        }}
      />

      <WorkflowInstanceModal
        isOpen={instanceModalOpen}
        toggle={() => setInstanceModalOpen(!instanceModalOpen)}
        instance={selectedInstance}
        onAction={(action) => {
          if (selectedInstance) {
            handleInstanceAction(action, selectedInstance);
          }
        }}
      />

      <StartWorkflowModal
        isOpen={startWorkflowModalOpen}
        toggle={() => setStartWorkflowModalOpen(!startWorkflowModalOpen)}
        workflow={selectedWorkflow}
        onSuccess={() => {
          setStartWorkflowModalOpen(false);
          setSelectedWorkflow(null);
          fetchData();
        }}
      />

      <WorkflowTemplateModal
        isOpen={templateModalOpen}
        toggle={() => setTemplateModalOpen(!templateModalOpen)}
        onSuccess={() => {
          setTemplateModalOpen(false);
          fetchData();
        }}
      />
    </Container>
  );
};

export default WorkflowManagement;