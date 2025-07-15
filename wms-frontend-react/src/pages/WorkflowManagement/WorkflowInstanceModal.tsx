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
  Card,
  CardHeader,
  CardBody,
  Table,
  Badge,
  Progress,
  Alert,
  Spinner,
  Input,
  FormGroup,
  Label
} from 'reactstrap';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import {
  faPlay,
  faPause,
  faStop,
  faHistory,
  faInfoCircle,
  faComment,
  faChartLine,
  faClock,
  faUser,
  faCheckCircle,
  faTimesCircle,
  faExclamationTriangle,
  faHourglassHalf,
  faPaperPlane,
  faUserCheck,
  faBell,
  faCode,
  faQuestion,
  faLink
} from '@fortawesome/free-solid-svg-icons';
import { format, parseISO, differenceInHours, differenceInMinutes } from 'date-fns';
import { toast } from 'react-toastify';

import {
  WorkflowInstance,
  workflowInstanceApi,
  workflowStepExecutionApi,
  workflowApprovalApi
} from '../../services/workflowManagementApi';

interface WorkflowInstanceModalProps {
  isOpen: boolean;
  toggle: () => void;
  instance: WorkflowInstance | null;
  onAction: (action: string) => void;
}

const WorkflowInstanceModal: React.FC<WorkflowInstanceModalProps> = ({
  isOpen,
  toggle,
  instance,
  onAction
}) => {
  // State
  const [activeTab, setActiveTab] = useState<string>('overview');
  const [stepExecutions, setStepExecutions] = useState<any[]>([]);
  const [timeline, setTimeline] = useState<any[]>([]);
  const [approvals, setApprovals] = useState<any[]>([]);
  const [loading, setLoading] = useState<boolean>(false);
  const [newComment, setNewComment] = useState<string>('');
  
  // Fetch data when modal opens
  useEffect(() => {
    if (isOpen && instance) {
      fetchInstanceData();
    }
  }, [isOpen, instance, activeTab]);
  
  const fetchInstanceData = async () => {
    if (!instance) return;
    
    setLoading(true);
    
    try {
      switch (activeTab) {
        case 'steps':
          const stepsResponse = await workflowInstanceApi.getWorkflowInstanceSteps(instance.id);
          setStepExecutions(stepsResponse.data);
          break;
        case 'timeline':
          const timelineResponse = await workflowInstanceApi.getWorkflowInstanceTimeline(instance.id);
          setTimeline(timelineResponse.data);
          break;
        case 'approvals':
          const approvalsResponse = await workflowApprovalApi.getApprovalHistory(instance.id);
          setApprovals(approvalsResponse.data);
          break;
      }
    } catch (error) {
      console.error('Error fetching instance data:', error);
      toast.error('Failed to load instance data');
    } finally {
      setLoading(false);
    }
  };
  
  const handleStepAction = async (action: string, stepExecutionId: number) => {
    try {
      switch (action) {
        case 'complete':
          await workflowStepExecutionApi.completeStepExecution(stepExecutionId, {
            comments: newComment || undefined
          });
          toast.success('Step completed successfully');
          break;
        case 'skip':
          const reason = prompt('Please provide a reason for skipping this step:');
          if (reason) {
            await workflowStepExecutionApi.skipStepExecution(stepExecutionId, reason);
            toast.success('Step skipped successfully');
          }
          break;
        case 'fail':
          const errorMessage = prompt('Please provide an error message:');
          if (errorMessage) {
            await workflowStepExecutionApi.failStepExecution(stepExecutionId, {
              error_message: errorMessage
            });
            toast.success('Step marked as failed');
          }
          break;
      }
      
      setNewComment('');
      fetchInstanceData();
    } catch (error) {
      console.error(`Error performing step action ${action}:`, error);
      toast.error(`Failed to ${action} step`);
    }
  };
  
  const handleApprovalAction = async (action: string, approvalId: number) => {
    try {
      switch (action) {
        case 'approve':
          await workflowApprovalApi.approveWorkflowStep(approvalId, {
            comments: newComment || undefined
          });
          toast.success('Approval granted');
          break;
        case 'reject':
          const reason = prompt('Please provide a reason for rejection:');
          if (reason) {
            await workflowApprovalApi.rejectWorkflowStep(approvalId, {
              comments: reason
            });
            toast.success('Approval rejected');
          }
          break;
      }
      
      setNewComment('');
      fetchInstanceData();
    } catch (error) {
      console.error(`Error performing approval action ${action}:`, error);
      toast.error(`Failed to ${action} approval`);
    }
  };
  
  const addComment = async (stepExecutionId: number) => {
    if (!newComment.trim()) {
      toast.error('Please enter a comment');
      return;
    }
    
    try {
      await workflowStepExecutionApi.addStepComment(stepExecutionId, newComment);
      toast.success('Comment added successfully');
      setNewComment('');
      fetchInstanceData();
    } catch (error) {
      console.error('Error adding comment:', error);
      toast.error('Failed to add comment');
    }
  };
  
  const getStatusBadge = (status: string, type: 'instance' | 'step' | 'approval' = 'instance') => {
    const statusConfig = {
      instance: {
        pending: { color: 'info', text: 'Pending' },
        in_progress: { color: 'primary', text: 'In Progress' },
        completed: { color: 'success', text: 'Completed' },
        failed: { color: 'danger', text: 'Failed' },
        cancelled: { color: 'secondary', text: 'Cancelled' },
        paused: { color: 'warning', text: 'Paused' }
      },
      step: {
        pending: { color: 'info', text: 'Pending' },
        in_progress: { color: 'primary', text: 'In Progress' },
        completed: { color: 'success', text: 'Completed' },
        failed: { color: 'danger', text: 'Failed' },
        skipped: { color: 'warning', text: 'Skipped' },
        cancelled: { color: 'secondary', text: 'Cancelled' }
      },
      approval: {
        pending: { color: 'info', text: 'Pending' },
        approved: { color: 'success', text: 'Approved' },
        rejected: { color: 'danger', text: 'Rejected' },
        delegated: { color: 'warning', text: 'Delegated' }
      }
    };

    const config = statusConfig[type][status as keyof typeof statusConfig[typeof type]] || 
                   { color: 'secondary', text: status };
    
    return <Badge color={config.color}>{config.text}</Badge>;
  };
  
  const getStepTypeIcon = (type: string) => {
    switch (type) {
      case 'approval':
        return <FontAwesomeIcon icon={faUserCheck} className="text-info" />;
      case 'notification':
        return <FontAwesomeIcon icon={faBell} className="text-warning" />;
      case 'task':
        return <FontAwesomeIcon icon={faPlay} className="text-primary" />;
      case 'condition':
        return <FontAwesomeIcon icon={faQuestion} className="text-secondary" />;
      case 'script':
        return <FontAwesomeIcon icon={faCode} className="text-dark" />;
      case 'integration':
        return <FontAwesomeIcon icon={faLink} className="text-success" />;
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
  
  const formatDuration = (startDate: string, endDate?: string) => {
    try {
      const start = parseISO(startDate);
      const end = endDate ? parseISO(endDate) : new Date();
      
      const hours = differenceInHours(end, start);
      const minutes = differenceInMinutes(end, start) % 60;
      
      if (hours > 0) {
        return `${hours}h ${minutes}m`;
      } else {
        return `${minutes}m`;
      }
    } catch (error) {
      return 'Unknown';
    }
  };
  
  const calculateProgress = () => {
    if (!instance || stepExecutions.length === 0) return 0;
    
    const completedSteps = stepExecutions.filter(step => 
      ['completed', 'skipped'].includes(step.status)
    ).length;
    
    return Math.round((completedSteps / stepExecutions.length) * 100);
  };
  
  const renderOverview = () => {
    if (!instance) return null;
    
    const progress = calculateProgress();
    
    return (
      <Row>
        <Col md={6}>
          <Card className="mb-3">
            <CardHeader>
              <h5 className="mb-0">Instance Information</h5>
            </CardHeader>
            <CardBody>
              <Table borderless size="sm">
                <tbody>
                  <tr>
                    <th width="40%">Instance ID:</th>
                    <td>{instance.id}</td>
                  </tr>
                  <tr>
                    <th>Instance Name:</th>
                    <td>{instance.instance_name || `Instance #${instance.id}`}</td>
                  </tr>
                  <tr>
                    <th>Workflow:</th>
                    <td>{instance.workflow_name}</td>
                  </tr>
                  <tr>
                    <th>Status:</th>
                    <td>{getStatusBadge(instance.status, 'instance')}</td>
                  </tr>
                  <tr>
                    <th>Priority:</th>
                    <td>
                      <Badge color={
                        instance.priority === 'urgent' ? 'danger' :
                        instance.priority === 'high' ? 'warning' :
                        instance.priority === 'normal' ? 'info' : 'secondary'
                      }>
                        {instance.priority}
                      </Badge>
                    </td>
                  </tr>
                  <tr>
                    <th>Progress:</th>
                    <td>
                      <div className="d-flex align-items-center">
                        <Progress value={progress} className="me-2" style={{ width: '100px' }} />
                        <span>{progress}%</span>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </Table>
            </CardBody>
          </Card>
        </Col>
        
        <Col md={6}>
          <Card className="mb-3">
            <CardHeader>
              <h5 className="mb-0">Timeline</h5>
            </CardHeader>
            <CardBody>
              <Table borderless size="sm">
                <tbody>
                  <tr>
                    <th width="40%">Initiated By:</th>
                    <td>User ID: {instance.initiated_by}</td>
                  </tr>
                  <tr>
                    <th>Initiated At:</th>
                    <td>{formatDate(instance.initiated_at)}</td>
                  </tr>
                  <tr>
                    <th>Duration:</th>
                    <td>{formatDuration(instance.initiated_at, instance.completed_at)}</td>
                  </tr>
                  {instance.due_date && (
                    <tr>
                      <th>Due Date:</th>
                      <td>{formatDate(instance.due_date)}</td>
                    </tr>
                  )}
                  {instance.completed_at && (
                    <tr>
                      <th>Completed At:</th>
                      <td>{formatDate(instance.completed_at)}</td>
                    </tr>
                  )}
                </tbody>
              </Table>
            </CardBody>
          </Card>
          
          {instance.notes && (
            <Card>
              <CardHeader>
                <h5 className="mb-0">Notes</h5>
              </CardHeader>
              <CardBody>
                <p>{instance.notes}</p>
              </CardBody>
            </Card>
          )}
        </Col>
      </Row>
    );
  };
  
  const renderSteps = () => {
    if (loading) {
      return (
        <div className="text-center py-3">
          <Spinner color="primary" />
        </div>
      );
    }
    
    if (stepExecutions.length === 0) {
      return (
        <Alert color="info">No step executions found for this workflow instance.</Alert>
      );
    }
    
    return (
      <div className="workflow-steps-execution">
        {stepExecutions.map((step, index) => (
          <Card key={step.id} className="mb-3">
            <CardHeader>
              <Row className="align-items-center">
                <Col>
                  <div className="d-flex align-items-center">
                    <div className="step-number me-3">{index + 1}</div>
                    <div>
                      <h6 className="mb-0">
                        {getStepTypeIcon(step.step_type)}
                        <span className="ms-2">{step.step_name}</span>
                        {getStatusBadge(step.status, 'step')}
                      </h6>
                      {step.assigned_to && (
                        <small className="text-muted">Assigned to: User ID {step.assigned_to}</small>
                      )}
                    </div>
                  </div>
                </Col>
                <Col xs="auto">
                  {step.status === 'in_progress' && (
                    <div>
                      <Button color="success" size="sm" className="me-1" onClick={() => handleStepAction('complete', step.id)}>
                        <FontAwesomeIcon icon={faCheckCircle} /> Complete
                      </Button>
                      <Button color="warning" size="sm" className="me-1" onClick={() => handleStepAction('skip', step.id)}>
                        <FontAwesomeIcon icon={faHourglassHalf} /> Skip
                      </Button>
                      <Button color="danger" size="sm" onClick={() => handleStepAction('fail', step.id)}>
                        <FontAwesomeIcon icon={faTimesCircle} /> Fail
                      </Button>
                    </div>
                  )}
                </Col>
              </Row>
            </CardHeader>
            <CardBody>
              <Row>
                <Col md={8}>
                  <Table borderless size="sm">
                    <tbody>
                      <tr>
                        <th width="25%">Started:</th>
                        <td>{step.started_at ? formatDate(step.started_at) : 'Not started'}</td>
                      </tr>
                      <tr>
                        <th>Completed:</th>
                        <td>{step.completed_at ? formatDate(step.completed_at) : 'Not completed'}</td>
                      </tr>
                      <tr>
                        <th>Duration:</th>
                        <td>
                          {step.started_at ? formatDuration(step.started_at, step.completed_at) : 'Not started'}
                        </td>
                      </tr>
                      {step.comments && (
                        <tr>
                          <th>Comments:</th>
                          <td>{step.comments}</td>
                        </tr>
                      )}
                    </tbody>
                  </Table>
                </Col>
                <Col md={4}>
                  {step.status === 'in_progress' && (
                    <div>
                      <FormGroup>
                        <Label for={`comment-${step.id}`}>Add Comment</Label>
                        <Input
                          type="textarea"
                          id={`comment-${step.id}`}
                          rows={2}
                          value={newComment}
                          onChange={(e) => setNewComment(e.target.value)}
                          placeholder="Enter your comment..."
                        />
                      </FormGroup>
                      <Button color="primary" size="sm" onClick={() => addComment(step.id)}>
                        <FontAwesomeIcon icon={faPaperPlane} className="me-1" /> Add Comment
                      </Button>
                    </div>
                  )}
                </Col>
              </Row>
            </CardBody>
          </Card>
        ))}
      </div>
    );
  };
  
  const renderTimeline = () => {
    if (loading) {
      return (
        <div className="text-center py-3">
          <Spinner color="primary" />
        </div>
      );
    }
    
    if (timeline.length === 0) {
      return (
        <Alert color="info">No timeline events found for this workflow instance.</Alert>
      );
    }
    
    return (
      <div className="workflow-timeline">
        {timeline.map((event, index) => (
          <div key={index} className="timeline-event">
            <div className="timeline-marker">
              <FontAwesomeIcon icon={
                event.event_type === 'started' ? faPlay :
                event.event_type === 'completed' ? faCheckCircle :
                event.event_type === 'failed' ? faTimesCircle :
                event.event_type === 'paused' ? faPause :
                event.event_type === 'resumed' ? faPlay :
                event.event_type === 'cancelled' ? faStop :
                faInfoCircle
              } />
            </div>
            <div className="timeline-content">
              <div className="timeline-header">
                <strong>{event.event_title}</strong>
                <span className="timeline-date">{formatDate(event.created_at)}</span>
              </div>
              <div className="timeline-description">{event.description}</div>
              {event.user_id && (
                <div className="timeline-user">
                  <FontAwesomeIcon icon={faUser} className="me-1" />
                  User ID: {event.user_id}
                </div>
              )}
            </div>
          </div>
        ))}
      </div>
    );
  };
  
  const renderApprovals = () => {
    if (loading) {
      return (
        <div className="text-center py-3">
          <Spinner color="primary" />
        </div>
      );
    }
    
    if (approvals.length === 0) {
      return (
        <Alert color="info">No approvals found for this workflow instance.</Alert>
      );
    }
    
    return (
      <Table responsive striped>
        <thead>
          <tr>
            <th>Step</th>
            <th>Approver</th>
            <th>Status</th>
            <th>Date</th>
            <th>Comments</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          {approvals.map(approval => (
            <tr key={approval.id}>
              <td>{approval.step_name}</td>
              <td>User ID: {approval.approver_id}</td>
              <td>{getStatusBadge(approval.approval_status, 'approval')}</td>
              <td>{approval.approval_date ? formatDate(approval.approval_date) : 'Pending'}</td>
              <td>{approval.comments || '-'}</td>
              <td>
                {approval.approval_status === 'pending' && (
                  <>
                    <Button color="success" size="sm" className="me-1" onClick={() => handleApprovalAction('approve', approval.id)}>
                      <FontAwesomeIcon icon={faCheckCircle} /> Approve
                    </Button>
                    <Button color="danger" size="sm" onClick={() => handleApprovalAction('reject', approval.id)}>
                      <FontAwesomeIcon icon={faTimesCircle} /> Reject
                    </Button>
                  </>
                )}
              </td>
            </tr>
          ))}
        </tbody>
      </Table>
    );
  };
  
  if (!instance) {
    return null;
  }
  
  return (
    <Modal isOpen={isOpen} toggle={toggle} size="xl">
      <ModalHeader toggle={toggle}>
        Workflow Instance: {instance.instance_name || `Instance #${instance.id}`}
        {getStatusBadge(instance.status, 'instance')}
      </ModalHeader>
      <ModalBody>
        <Nav tabs className="mb-3">
          <NavItem>
            <NavLink
              className={activeTab === 'overview' ? 'active' : ''}
              onClick={() => setActiveTab('overview')}
            >
              <FontAwesomeIcon icon={faInfoCircle} className="me-1" /> Overview
            </NavLink>
          </NavItem>
          <NavItem>
            <NavLink
              className={activeTab === 'steps' ? 'active' : ''}
              onClick={() => setActiveTab('steps')}
            >
              <FontAwesomeIcon icon={faPlay} className="me-1" /> Steps
            </NavLink>
          </NavItem>
          <NavItem>
            <NavLink
              className={activeTab === 'timeline' ? 'active' : ''}
              onClick={() => setActiveTab('timeline')}
            >
              <FontAwesomeIcon icon={faHistory} className="me-1" /> Timeline
            </NavLink>
          </NavItem>
          <NavItem>
            <NavLink
              className={activeTab === 'approvals' ? 'active' : ''}
              onClick={() => setActiveTab('approvals')}
            >
              <FontAwesomeIcon icon={faUserCheck} className="me-1" /> Approvals
            </NavLink>
          </NavItem>
        </Nav>
        
        <TabContent activeTab={activeTab}>
          <TabPane tabId="overview">
            {renderOverview()}
          </TabPane>
          <TabPane tabId="steps">
            {renderSteps()}
          </TabPane>
          <TabPane tabId="timeline">
            {renderTimeline()}
          </TabPane>
          <TabPane tabId="approvals">
            {renderApprovals()}
          </TabPane>
        </TabContent>
      </ModalBody>
      <ModalFooter>
        <div className="d-flex justify-content-between w-100">
          <div>
            {instance.status === 'in_progress' && (
              <Button color="warning" className="me-2" onClick={() => onAction('pause')}>
                <FontAwesomeIcon icon={faPause} className="me-1" /> Pause
              </Button>
            )}
            {instance.status === 'paused' && (
              <Button color="success" className="me-2" onClick={() => onAction('resume')}>
                <FontAwesomeIcon icon={faPlay} className="me-1" /> Resume
              </Button>
            )}
            {instance.status === 'failed' && (
              <Button color="primary" className="me-2" onClick={() => onAction('retry')}>
                <FontAwesomeIcon icon={faHistory} className="me-1" /> Retry
              </Button>
            )}
            {['pending', 'in_progress', 'paused'].includes(instance.status) && (
              <Button color="danger" onClick={() => onAction('cancel')}>
                <FontAwesomeIcon icon={faStop} className="me-1" /> Cancel
              </Button>
            )}
          </div>
          <Button color="secondary" onClick={toggle}>
            Close
          </Button>
        </div>
      </ModalFooter>
    </Modal>
  );
};

export default WorkflowInstanceModal;