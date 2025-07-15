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
  CardBody,
  Spinner,
  Alert,
  Progress
} from 'reactstrap';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import {
  faPlay,
  faPause,
  faEdit,
  faTrash,
  faArchive,
  faClone,
  faDownload,
  faHistory,
  faInfoCircle,
  faList,
  faChartLine,
  faCodeBranch,
  faCalendarAlt,
  faBell,
  faUser,
  faClock,
  faCheck,
  faTimes,
  faExclamationTriangle,
  faRandom,
  faUserCheck,
  faHourglass,
  faLink,
  faLayerGroup
} from '@fortawesome/free-solid-svg-icons';
import { toast } from 'react-toastify';
import { format, parseISO } from 'date-fns';
import ReactFlow, {
  Background,
  Controls,
  MiniMap,
  ReactFlowProvider
} from 'reactflow';
import 'reactflow/dist/style.css';

import { Workflow, WorkflowStep, WorkflowExecution, workflowApi, workflowStepApi, workflowExecutionApi } from '../../services/workflowEngineApi';

interface WorkflowDetailsModalProps {
  isOpen: boolean;
  toggle: () => void;
  workflow: Workflow | null;
  onAction: (action: string) => void;
}

const WorkflowDetailsModal: React.FC<WorkflowDetailsModalProps> = ({
  isOpen,
  toggle,
  workflow,
  onAction
}) => {
  // State
  const [activeTab, setActiveTab] = useState<string>('overview');
  const [loading, setLoading] = useState<boolean>(false);
  const [steps, setSteps] = useState<WorkflowStep[]>([]);
  const [executions, setExecutions] = useState<WorkflowExecution[]>([]);
  const [nodes, setNodes] = useState<any[]>([]);
  const [edges, setEdges] = useState<any[]>([]);
  const [metrics, setMetrics] = useState<any>({
    total_executions: 0,
    success_rate: 0,
    avg_execution_time: 0,
    executions_by_status: {
      completed: 0,
      failed: 0,
      in_progress: 0,
      pending: 0,
      cancelled: 0
    },
    executions_over_time: []
  });
  
  // Fetch data when modal opens
  useEffect(() => {
    if (isOpen && workflow) {
      fetchWorkflowSteps();
      fetchWorkflowExecutions();
      generateFlowVisualization();
    }
  }, [isOpen, workflow]);
  
  const fetchWorkflowSteps = async () => {
    if (!workflow) return;
    
    setLoading(true);
    
    try {
      const response = await workflowStepApi.getWorkflowSteps(workflow.id);
      setSteps(response.data);
    } catch (error) {
      console.error('Error fetching workflow steps:', error);
      toast.error('Failed to load workflow steps');
    } finally {
      setLoading(false);
    }
  };
  
  const fetchWorkflowExecutions = async () => {
    if (!workflow) return;
    
    try {
      const response = await workflowExecutionApi.getWorkflowExecutions(workflow.id);
      setExecutions(response.data);
      
      // In a real implementation, you would fetch actual metrics from the API
      // Here we're generating mock metrics based on the executions
      generateMockMetrics(response.data);
    } catch (error) {
      console.error('Error fetching workflow executions:', error);
      toast.error('Failed to load workflow executions');
    }
  };
  
  const generateMockMetrics = (executions: WorkflowExecution[]) => {
    const total = executions.length;
    const completed = executions.filter(e => e.status === 'completed').length;
    const failed = executions.filter(e => e.status === 'failed').length;
    const inProgress = executions.filter(e => e.status === 'in_progress').length;
    const pending = executions.filter(e => e.status === 'pending').length;
    const cancelled = executions.filter(e => e.status === 'cancelled').length;
    
    const successRate = total > 0 ? (completed / total) * 100 : 0;
    
    const executionTimes = executions
      .filter(e => e.execution_time_seconds)
      .map(e => e.execution_time_seconds as number);
    
    const avgExecutionTime = executionTimes.length > 0
      ? executionTimes.reduce((sum, time) => sum + time, 0) / executionTimes.length
      : 0;
    
    // Generate mock time series data
    const executionsOverTime = [
      { date: '2023-01', count: 5, success_rate: 80 },
      { date: '2023-02', count: 8, success_rate: 75 },
      { date: '2023-03', count: 12, success_rate: 83 },
      { date: '2023-04', count: 10, success_rate: 90 },
      { date: '2023-05', count: 15, success_rate: 87 },
      { date: '2023-06', count: 18, success_rate: 94 }
    ];
    
    setMetrics({
      total_executions: total,
      success_rate: successRate.toFixed(1),
      avg_execution_time: avgExecutionTime.toFixed(1),
      executions_by_status: {
        completed,
        failed,
        in_progress: inProgress,
        pending,
        cancelled
      },
      executions_over_time: executionsOverTime
    });
  };
  
  const generateFlowVisualization = () => {
    if (!steps || steps.length === 0) return;
    
    const newNodes = steps.map((step, index) => ({
      id: step.id.toString(),
      type: 'default',
      data: { 
        label: (
          <div>
            <div className="fw-bold">{step.name}</div>
            <Badge color={getStepTypeBadgeColor(step.step_type)}>
              {step.step_type}
            </Badge>
          </div>
        )
      },
      position: { x: 250, y: index * 100 + 50 }
    }));
    
    const newEdges = steps.map((step, index) => {
      if (index < steps.length - 1) {
        return {
          id: `e${step.id}-${steps[index + 1].id}`,
          source: step.id.toString(),
          target: steps[index + 1].id.toString(),
          type: 'smoothstep',
          animated: true
        };
      }
      return null;
    }).filter(Boolean);
    
    setNodes(newNodes);
    setEdges(newEdges);
  };
  
  const getStepTypeBadgeColor = (stepType: string) => {
    switch (stepType) {
      case 'task':
        return 'primary';
      case 'approval':
        return 'success';
      case 'condition':
        return 'warning';
      case 'notification':
        return 'info';
      case 'integration':
        return 'dark';
      case 'delay':
        return 'secondary';
      case 'subprocess':
        return 'danger';
      default:
        return 'light';
    }
  };
  
  const getStepTypeIcon = (stepType: string) => {
    switch (stepType) {
      case 'task':
        return <FontAwesomeIcon icon={faList} />;
      case 'approval':
        return <FontAwesomeIcon icon={faUserCheck} />;
      case 'condition':
        return <FontAwesomeIcon icon={faRandom} />;
      case 'notification':
        return <FontAwesomeIcon icon={faBell} />;
      case 'integration':
        return <FontAwesomeIcon icon={faLink} />;
      case 'delay':
        return <FontAwesomeIcon icon={faHourglass} />;
      case 'subprocess':
        return <FontAwesomeIcon icon={faLayerGroup} />;
      default:
        return <FontAwesomeIcon icon={faList} />;
    }
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
      case 'completed':
        return <Badge color="success">Completed</Badge>;
      case 'failed':
        return <Badge color="danger">Failed</Badge>;
      case 'in_progress':
        return <Badge color="primary">In Progress</Badge>;
      case 'pending':
        return <Badge color="info">Pending</Badge>;
      case 'cancelled':
        return <Badge color="secondary">Cancelled</Badge>;
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
  
  const formatDuration = (seconds: number) => {
    if (seconds < 60) {
      return `${seconds} seconds`;
    } else if (seconds < 3600) {
      const minutes = Math.floor(seconds / 60);
      const remainingSeconds = seconds % 60;
      return `${minutes} min ${remainingSeconds} sec`;
    } else {
      const hours = Math.floor(seconds / 3600);
      const minutes = Math.floor((seconds % 3600) / 60);
      return `${hours} hr ${minutes} min`;
    }
  };
  
  const renderOverviewTab = () => {
    if (!workflow) return null;
    
    return (
      <Row>
        <Col md={6}>
          <Card className="mb-4">
            <CardBody>
              <h5 className="mb-3">Workflow Information</h5>
              <Table borderless size="sm">
                <tbody>
                  <tr>
                    <th width="40%">Name</th>
                    <td>{workflow.name}</td>
                  </tr>
                  <tr>
                    <th>Description</th>
                    <td>{workflow.description || 'No description provided'}</td>
                  </tr>
                  <tr>
                    <th>Category</th>
                    <td>
                      <Badge color="info">{workflow.category}</Badge>
                    </td>
                  </tr>
                  <tr>
                    <th>Version</th>
                    <td>{workflow.version}</td>
                  </tr>
                  <tr>
                    <th>Status</th>
                    <td>{getStatusBadge(workflow.status)}</td>
                  </tr>
                  <tr>
                    <th>Trigger Type</th>
                    <td>
                      <div className="d-flex align-items-center">
                        {getTriggerIcon(workflow.trigger_type)}
                        <span className="ms-2">{workflow.trigger_type.charAt(0).toUpperCase() + workflow.trigger_type.slice(1)}</span>
                      </div>
                    </td>
                  </tr>
                  {workflow.trigger_type === 'scheduled' && workflow.trigger_config?.schedule && (
                    <tr>
                      <th>Schedule</th>
                      <td>{workflow.trigger_config.schedule}</td>
                    </tr>
                  )}
                  {workflow.trigger_type === 'event' && workflow.trigger_config?.event_type && (
                    <tr>
                      <th>Event Type</th>
                      <td>{workflow.trigger_config.event_type}</td>
                    </tr>
                  )}
                  <tr>
                    <th>Created By</th>
                    <td>User ID: {workflow.created_by}</td>
                  </tr>
                  <tr>
                    <th>Created Date</th>
                    <td>{formatDate(workflow.created_at)}</td>
                  </tr>
                  {workflow.updated_at && (
                    <tr>
                      <th>Last Updated</th>
                      <td>{formatDate(workflow.updated_at)}</td>
                    </tr>
                  )}
                </tbody>
              </Table>
            </CardBody>
          </Card>
          
          <Card>
            <CardBody>
              <h5 className="mb-3">Execution Statistics</h5>
              <div className="mb-4">
                <div className="d-flex justify-content-between mb-1">
                  <span>Success Rate</span>
                  <span className={
                    parseFloat(metrics.success_rate) >= 90 ? 'text-success' : 
                    parseFloat(metrics.success_rate) >= 70 ? 'text-warning' : 'text-danger'
                  }>
                    {metrics.success_rate}%
                  </span>
                </div>
                <Progress value={metrics.success_rate} color={
                  parseFloat(metrics.success_rate) >= 90 ? 'success' : 
                  parseFloat(metrics.success_rate) >= 70 ? 'warning' : 'danger'
                } />
              </div>
              
              <Table borderless size="sm">
                <tbody>
                  <tr>
                    <th width="60%">Total Executions</th>
                    <td>{metrics.total_executions}</td>
                  </tr>
                  <tr>
                    <th>Average Execution Time</th>
                    <td>{formatDuration(parseFloat(metrics.avg_execution_time))}</td>
                  </tr>
                  <tr>
                    <th>Completed Executions</th>
                    <td>{metrics.executions_by_status.completed}</td>
                  </tr>
                  <tr>
                    <th>Failed Executions</th>
                    <td>{metrics.executions_by_status.failed}</td>
                  </tr>
                  <tr>
                    <th>In Progress</th>
                    <td>{metrics.executions_by_status.in_progress}</td>
                  </tr>
                  <tr>
                    <th>Pending</th>
                    <td>{metrics.executions_by_status.pending}</td>
                  </tr>
                  <tr>
                    <th>Cancelled</th>
                    <td>{metrics.executions_by_status.cancelled}</td>
                  </tr>
                </tbody>
              </Table>
            </CardBody>
          </Card>
        </Col>
        
        <Col md={6}>
          <Card className="mb-4">
            <CardBody>
              <div className="d-flex justify-content-between align-items-center mb-3">
                <h5 className="mb-0">Workflow Steps</h5>
                <Badge color="primary">{steps.length} Steps</Badge>
              </div>
              
              {loading ? (
                <div className="text-center py-3">
                  <Spinner color="primary" />
                </div>
              ) : steps.length === 0 ? (
                <Alert color="info">
                  No steps defined for this workflow yet.
                </Alert>
              ) : (
                <div className="steps-list">
                  {steps.map((step, index) => (
                    <div key={step.id} className="step-item">
                      <div className="step-number">{index + 1}</div>
                      <div className="step-content">
                        <div className="step-header">
                          <h6 className="mb-0">{step.name}</h6>
                          <Badge color={getStepTypeBadgeColor(step.step_type)}>
                            {getStepTypeIcon(step.step_type)} {step.step_type}
                          </Badge>
                        </div>
                        {step.description && (
                          <div className="step-description text-muted">
                            {step.description}
                          </div>
                        )}
                      </div>
                    </div>
                  ))}
                </div>
              )}
            </CardBody>
          </Card>
          
          <Card>
            <CardBody>
              <h5 className="mb-3">Recent Executions</h5>
              
              {executions.length === 0 ? (
                <Alert color="info">
                  No executions found for this workflow.
                </Alert>
              ) : (
                <div className="recent-executions">
                  {executions.slice(0, 5).map(execution => (
                    <div key={execution.id} className="execution-item">
                      <div className="execution-status">
                        {execution.status === 'completed' && <FontAwesomeIcon icon={faCheck} className="text-success" />}
                        {execution.status === 'failed' && <FontAwesomeIcon icon={faTimes} className="text-danger" />}
                        {execution.status === 'in_progress' && <Spinner size="sm" color="primary" />}
                        {execution.status === 'pending' && <FontAwesomeIcon icon={faClock} className="text-info" />}
                        {execution.status === 'cancelled' && <FontAwesomeIcon icon={faTimes} className="text-secondary" />}
                      </div>
                      <div className="execution-content">
                        <div className="execution-header">
                          <div>
                            <span className="fw-bold">Execution #{execution.id}</span>
                            <span className="ms-2">{getStatusBadge(execution.status)}</span>
                          </div>
                          <div className="execution-time">
                            {formatDate(execution.started_at)}
                          </div>
                        </div>
                        <div className="execution-details">
                          <div>
                            <FontAwesomeIcon icon={faUser} className="me-1" /> Initiated by: User ID {execution.initiated_by}
                          </div>
                          {execution.execution_time_seconds && (
                            <div>
                              <FontAwesomeIcon icon={faClock} className="me-1" /> Duration: {formatDuration(execution.execution_time_seconds)}
                            </div>
                          )}
                          {execution.error_message && (
                            <div className="text-danger">
                              <FontAwesomeIcon icon={faExclamationTriangle} className="me-1" /> {execution.error_message}
                            </div>
                          )}
                        </div>
                      </div>
                    </div>
                  ))}
                </div>
              )}
              
              {executions.length > 5 && (
                <div className="text-center mt-3">
                  <Button color="link" size="sm" onClick={() => setActiveTab('executions')}>
                    View All Executions
                  </Button>
                </div>
              )}
            </CardBody>
          </Card>
        </Col>
      </Row>
    );
  };
  
  const renderStepsTab = () => {
    if (loading) {
      return (
        <div className="text-center py-5">
          <Spinner color="primary" />
          <p className="mt-2">Loading workflow steps...</p>
        </div>
      );
    }
    
    if (steps.length === 0) {
      return (
        <Alert color="info" className="text-center">
          No steps defined for this workflow yet.
        </Alert>
      );
    }
    
    return (
      <div>
        <div className="workflow-visualization mb-4">
          <div className="flow-container" style={{ height: '400px' }}>
            <ReactFlowProvider>
              <ReactFlow
                nodes={nodes}
                edges={edges}
                fitView
                nodesDraggable={false}
                nodesConnectable={false}
                elementsSelectable={false}
              >
                <Controls />
                <MiniMap />
                <Background />
              </ReactFlow>
            </ReactFlowProvider>
          </div>
        </div>
        
        <Table responsive striped>
          <thead>
            <tr>
              <th width="5%">#</th>
              <th width="25%">Name</th>
              <th width="15%">Type</th>
              <th width="40%">Description</th>
              <th width="15%">Required</th>
            </tr>
          </thead>
          <tbody>
            {steps.map((step, index) => (
              <tr key={step.id}>
                <td>{index + 1}</td>
                <td>{step.name}</td>
                <td>
                  <Badge color={getStepTypeBadgeColor(step.step_type)}>
                    {getStepTypeIcon(step.step_type)} {step.step_type}
                  </Badge>
                </td>
                <td>{step.description || '-'}</td>
                <td>
                  {step.is_required ? (
                    <Badge color="success">Required</Badge>
                  ) : (
                    <Badge color="secondary">Optional</Badge>
                  )}
                </td>
              </tr>
            ))}
          </tbody>
        </Table>
      </div>
    );
  };
  
  const renderExecutionsTab = () => {
    if (executions.length === 0) {
      return (
        <Alert color="info" className="text-center">
          No executions found for this workflow.
        </Alert>
      );
    }
    
    return (
      <Table responsive striped>
        <thead>
          <tr>
            <th>ID</th>
            <th>Status</th>
            <th>Started</th>
            <th>Completed</th>
            <th>Duration</th>
            <th>Initiated By</th>
            <th>Details</th>
          </tr>
        </thead>
        <tbody>
          {executions.map(execution => (
            <tr key={execution.id}>
              <td>{execution.id}</td>
              <td>{getStatusBadge(execution.status)}</td>
              <td>{formatDate(execution.started_at)}</td>
              <td>{execution.completed_at ? formatDate(execution.completed_at) : '-'}</td>
              <td>
                {execution.execution_time_seconds
                  ? formatDuration(execution.execution_time_seconds)
                  : '-'}
              </td>
              <td>User ID: {execution.initiated_by}</td>
              <td>
                <Button color="primary" size="sm">
                  View Details
                </Button>
              </td>
            </tr>
          ))}
        </tbody>
      </Table>
    );
  };
  
  const renderAnalyticsTab = () => {
    return (
      <div>
        <Row className="mb-4">
          <Col md={3}>
            <Card className="metric-card">
              <CardBody>
                <div className="d-flex justify-content-between align-items-center">
                  <div>
                    <h6 className="text-muted mb-1">Total Executions</h6>
                    <h3 className="mb-0">{metrics.total_executions}</h3>
                  </div>
                  <div className="metric-icon bg-primary">
                    <FontAwesomeIcon icon={faHistory} />
                  </div>
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
                    <h3 className="mb-0">{metrics.success_rate}%</h3>
                  </div>
                  <div className="metric-icon bg-success">
                    <FontAwesomeIcon icon={faCheck} />
                  </div>
                </div>
              </CardBody>
            </Card>
          </Col>
          <Col md={3}>
            <Card className="metric-card">
              <CardBody>
                <div className="d-flex justify-content-between align-items-center">
                  <div>
                    <h6 className="text-muted mb-1">Avg. Duration</h6>
                    <h3 className="mb-0">{formatDuration(parseFloat(metrics.avg_execution_time))}</h3>
                  </div>
                  <div className="metric-icon bg-warning">
                    <FontAwesomeIcon icon={faClock} />
                  </div>
                </div>
              </CardBody>
            </Card>
          </Col>
          <Col md={3}>
            <Card className="metric-card">
              <CardBody>
                <div className="d-flex justify-content-between align-items-center">
                  <div>
                    <h6 className="text-muted mb-1">Failed Executions</h6>
                    <h3 className="mb-0">{metrics.executions_by_status.failed}</h3>
                  </div>
                  <div className="metric-icon bg-danger">
                    <FontAwesomeIcon icon={faTimes} />
                  </div>
                </div>
              </CardBody>
            </Card>
          </Col>
        </Row>
        
        <Row>
          <Col md={6}>
            <Card className="mb-4">
              <CardBody>
                <h5 className="mb-3">Execution Status Distribution</h5>
                <div className="chart-placeholder">
                  <Alert color="info">
                    <FontAwesomeIcon icon={faInfoCircle} className="me-2" />
                    In a real implementation, a pie chart showing the distribution of execution statuses would be displayed here.
                  </Alert>
                  <div className="status-legend">
                    <div className="status-item">
                      <Badge color="success" className="me-2">Completed</Badge>
                      <span>{metrics.executions_by_status.completed}</span>
                    </div>
                    <div className="status-item">
                      <Badge color="danger" className="me-2">Failed</Badge>
                      <span>{metrics.executions_by_status.failed}</span>
                    </div>
                    <div className="status-item">
                      <Badge color="primary" className="me-2">In Progress</Badge>
                      <span>{metrics.executions_by_status.in_progress}</span>
                    </div>
                    <div className="status-item">
                      <Badge color="info" className="me-2">Pending</Badge>
                      <span>{metrics.executions_by_status.pending}</span>
                    </div>
                    <div className="status-item">
                      <Badge color="secondary" className="me-2">Cancelled</Badge>
                      <span>{metrics.executions_by_status.cancelled}</span>
                    </div>
                  </div>
                </div>
              </CardBody>
            </Card>
          </Col>
          
          <Col md={6}>
            <Card className="mb-4">
              <CardBody>
                <h5 className="mb-3">Executions Over Time</h5>
                <div className="chart-placeholder">
                  <Alert color="info">
                    <FontAwesomeIcon icon={faInfoCircle} className="me-2" />
                    In a real implementation, a line chart showing the number of executions over time would be displayed here.
                  </Alert>
                  <Table size="sm">
                    <thead>
                      <tr>
                        <th>Period</th>
                        <th>Executions</th>
                        <th>Success Rate</th>
                      </tr>
                    </thead>
                    <tbody>
                      {metrics.executions_over_time.map((item: any) => (
                        <tr key={item.date}>
                          <td>{item.date}</td>
                          <td>{item.count}</td>
                          <td>
                            <div className="d-flex align-items-center">
                              <Progress value={item.success_rate} color={
                                item.success_rate >= 90 ? 'success' : 
                                item.success_rate >= 70 ? 'warning' : 'danger'
                              } style={{ width: '100px' }} className="me-2" />
                              <span>{item.success_rate}%</span>
                            </div>
                          </td>
                        </tr>
                      ))}
                    </tbody>
                  </Table>
                </div>
              </CardBody>
            </Card>
          </Col>
        </Row>
        
        <Row>
          <Col md={12}>
            <Card>
              <CardBody>
                <h5 className="mb-3">Step Performance Analysis</h5>
                <div className="chart-placeholder">
                  <Alert color="info">
                    <FontAwesomeIcon icon={faInfoCircle} className="me-2" />
                    In a real implementation, a bar chart showing the average execution time and failure rate for each step would be displayed here.
                  </Alert>
                  <Table responsive>
                    <thead>
                      <tr>
                        <th>Step</th>
                        <th>Type</th>
                        <th>Avg. Duration</th>
                        <th>Success Rate</th>
                        <th>Failures</th>
                      </tr>
                    </thead>
                    <tbody>
                      {steps.map((step, index) => (
                        <tr key={step.id}>
                          <td>{step.name}</td>
                          <td>
                            <Badge color={getStepTypeBadgeColor(step.step_type)}>
                              {step.step_type}
                            </Badge>
                          </td>
                          <td>{formatDuration(Math.floor(Math.random() * 60) + 5)}</td>
                          <td>
                            <div className="d-flex align-items-center">
                              <Progress value={Math.floor(Math.random() * 30) + 70} color={
                                Math.floor(Math.random() * 30) + 70 >= 90 ? 'success' : 
                                Math.floor(Math.random() * 30) + 70 >= 70 ? 'warning' : 'danger'
                              } style={{ width: '100px' }} className="me-2" />
                              <span>{Math.floor(Math.random() * 30) + 70}%</span>
                            </div>
                          </td>
                          <td>{Math.floor(Math.random() * 5)}</td>
                        </tr>
                      ))}
                    </tbody>
                  </Table>
                </div>
              </CardBody>
            </Card>
          </Col>
        </Row>
      </div>
    );
  };
  
  if (!workflow) {
    return null;
  }
  
  return (
    <Modal isOpen={isOpen} toggle={toggle} size="xl">
      <ModalHeader toggle={toggle}>
        <div className="d-flex align-items-center">
          <FontAwesomeIcon icon={faCodeBranch} className="me-2 text-primary" />
          {workflow.name}
          <Badge color={
            workflow.status === 'active' ? 'success' :
            workflow.status === 'draft' ? 'secondary' :
            workflow.status === 'inactive' ? 'warning' : 'dark'
          } className="ms-2">
            {workflow.status}
          </Badge>
        </div>
      </ModalHeader>
      <ModalBody>
        <Nav tabs className="mb-4">
          <NavItem>
            <NavLink
              className={activeTab === 'overview' ? 'active' : ''}
              onClick={() => setActiveTab('overview')}
            >
              <FontAwesomeIcon icon={faInfoCircle} className="me-2" /> Overview
            </NavLink>
          </NavItem>
          <NavItem>
            <NavLink
              className={activeTab === 'steps' ? 'active' : ''}
              onClick={() => setActiveTab('steps')}
            >
              <FontAwesomeIcon icon={faList} className="me-2" /> Steps
            </NavLink>
          </NavItem>
          <NavItem>
            <NavLink
              className={activeTab === 'executions' ? 'active' : ''}
              onClick={() => setActiveTab('executions')}
            >
              <FontAwesomeIcon icon={faHistory} className="me-2" /> Executions
            </NavLink>
          </NavItem>
          <NavItem>
            <NavLink
              className={activeTab === 'analytics' ? 'active' : ''}
              onClick={() => setActiveTab('analytics')}
            >
              <FontAwesomeIcon icon={faChartLine} className="me-2" /> Analytics
            </NavLink>
          </NavItem>
        </Nav>
        
        <TabContent activeTab={activeTab}>
          <TabPane tabId="overview">
            {renderOverviewTab()}
          </TabPane>
          <TabPane tabId="steps">
            {renderStepsTab()}
          </TabPane>
          <TabPane tabId="executions">
            {renderExecutionsTab()}
          </TabPane>
          <TabPane tabId="analytics">
            {renderAnalyticsTab()}
          </TabPane>
        </TabContent>
      </ModalBody>
      <ModalFooter>
        <div className="d-flex justify-content-between w-100">
          <div>
            <Button color="danger" className="me-2" onClick={() => onAction('delete')}>
              <FontAwesomeIcon icon={faTrash} className="me-1" /> Delete
            </Button>
            {workflow.status !== 'archived' && (
              <Button color="secondary" onClick={() => onAction('archive')}>
                <FontAwesomeIcon icon={faArchive} className="me-1" /> Archive
              </Button>
            )}
          </div>
          <div>
            <Button color="info" className="me-2" onClick={() => onAction('clone')}>
              <FontAwesomeIcon icon={faClone} className="me-1" /> Clone
            </Button>
            <Button color="success" className="me-2" onClick={() => onAction('export')}>
              <FontAwesomeIcon icon={faDownload} className="me-1" /> Export
            </Button>
            {workflow.status === 'active' && (
              <Button color="primary" className="me-2" onClick={() => onAction('execute')}>
                <FontAwesomeIcon icon={faPlay} className="me-1" /> Execute
              </Button>
            )}
            <Button color="warning" onClick={() => onAction('edit')}>
              <FontAwesomeIcon icon={faEdit} className="me-1" /> Edit
            </Button>
          </div>
        </div>
      </ModalFooter>
    </Modal>
  );
};

export default WorkflowDetailsModal;