import React, { useState, useEffect, useRef } from 'react';
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
  FormFeedback,
  Nav,
  NavItem,
  NavLink,
  TabContent,
  TabPane,
  Row,
  Col,
  Card,
  CardBody,
  Spinner,
  Alert,
  Badge
} from 'reactstrap';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import {
  faSave,
  faPlay,
  faPause,
  faPlus,
  faTrash,
  faArrowUp,
  faArrowDown,
  faExclamationTriangle,
  faCheck,
  faTimes,
  faCalendarAlt,
  faBell,
  faUserCheck,
  faRandom,
  faHourglass,
  faLink,
  faCode,
  faExchangeAlt,
  faLayerGroup,
  faInfoCircle,
  faWrench,
  faList
} from '@fortawesome/free-solid-svg-icons';
import { toast } from 'react-toastify';
import Select from 'react-select';
import ReactFlow, {
  Background,
  Controls,
  MiniMap,
  addEdge,
  ReactFlowProvider,
  useNodesState,
  useEdgesState,
  MarkerType
} from 'reactflow';
import 'reactflow/dist/style.css';

import { Workflow, WorkflowStep, workflowApi, workflowStepApi } from '../../services/workflowEngineApi';

interface WorkflowDesignerModalProps {
  isOpen: boolean;
  toggle: () => void;
  workflow: Workflow | null;
  categories: string[];
  onSuccess: () => void;
}

const WorkflowDesignerModal: React.FC<WorkflowDesignerModalProps> = ({
  isOpen,
  toggle,
  workflow,
  categories,
  onSuccess
}) => {
  // State
  const [activeTab, setActiveTab] = useState<string>('details');
  const [loading, setLoading] = useState<boolean>(false);
  const [saving, setSaving] = useState<boolean>(false);
  const [errors, setErrors] = useState<Record<string, string>>({});
  
  // Workflow details
  const [name, setName] = useState<string>('');
  const [description, setDescription] = useState<string>('');
  const [category, setCategory] = useState<string>('');
  const [triggerType, setTriggerType] = useState<string>('manual');
  const [triggerConfig, setTriggerConfig] = useState<any>({});
  const [isTemplate, setIsTemplate] = useState<boolean>(false);
  
  // Workflow steps
  const [steps, setSteps] = useState<WorkflowStep[]>([]);
  const [selectedStep, setSelectedStep] = useState<WorkflowStep | null>(null);
  const [stepTypes, setStepTypes] = useState<any[]>([]);
  
  // Flow visualization
  const [nodes, setNodes, onNodesChange] = useNodesState([]);
  const [edges, setEdges, onEdgesChange] = useEdgesState([]);
  const reactFlowWrapper = useRef<HTMLDivElement>(null);
  
  // Reset form when modal opens/closes or workflow changes
  useEffect(() => {
    if (isOpen) {
      if (workflow) {
        // Edit mode
        setName(workflow.name || '');
        setDescription(workflow.description || '');
        setCategory(workflow.category || '');
        setTriggerType(workflow.trigger_type || 'manual');
        setTriggerConfig(workflow.trigger_config || {});
        setIsTemplate(workflow.is_template || false);
        fetchWorkflowSteps(workflow.id);
      } else {
        // Create mode
        resetForm();
      }
      fetchStepTypes();
    }
  }, [isOpen, workflow]);
  
  // Update flow visualization when steps change
  useEffect(() => {
    if (steps.length > 0) {
      generateFlowVisualization();
    }
  }, [steps]);
  
  const resetForm = () => {
    setName('');
    setDescription('');
    setCategory('');
    setTriggerType('manual');
    setTriggerConfig({});
    setIsTemplate(false);
    setSteps([]);
    setSelectedStep(null);
    setErrors({});
    setActiveTab('details');
  };
  
  const fetchWorkflowSteps = async (workflowId: number) => {
    setLoading(true);
    
    try {
      const response = await workflowStepApi.getWorkflowSteps(workflowId);
      setSteps(response.data);
    } catch (error) {
      console.error('Error fetching workflow steps:', error);
      toast.error('Failed to load workflow steps');
    } finally {
      setLoading(false);
    }
  };
  
  const fetchStepTypes = async () => {
    try {
      const response = await workflowStepApi.getStepTypes();
      setStepTypes(response.data);
    } catch (error) {
      console.error('Error fetching step types:', error);
      toast.error('Failed to load step types');
    }
  };
  
  const validateForm = () => {
    const newErrors: Record<string, string> = {};
    
    if (!name.trim()) {
      newErrors.name = 'Workflow name is required';
    }
    
    if (!category) {
      newErrors.category = 'Category is required';
    }
    
    if (triggerType === 'scheduled' && !triggerConfig.schedule) {
      newErrors.schedule = 'Schedule is required for scheduled workflows';
    }
    
    if (triggerType === 'event' && !triggerConfig.event_type) {
      newErrors.event_type = 'Event type is required for event-triggered workflows';
    }
    
    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };
  
  const handleSaveWorkflow = async () => {
    if (!validateForm()) {
      setActiveTab('details');
      return;
    }
    
    setSaving(true);
    
    try {
      const workflowData = {
        name,
        description: description || undefined,
        category,
        trigger_type: triggerType as 'manual' | 'event' | 'scheduled',
        trigger_config: triggerConfig,
        is_template: isTemplate
      };
      
      let workflowId: number;
      
      if (workflow) {
        // Update existing workflow
        await workflowApi.updateWorkflow(workflow.id, workflowData);
        workflowId = workflow.id;
        toast.success('Workflow updated successfully');
      } else {
        // Create new workflow
        const response = await workflowApi.createWorkflow(workflowData);
        workflowId = response.data.id;
        toast.success('Workflow created successfully');
      }
      
      // Save steps if we're on the steps tab
      if (activeTab === 'steps' && steps.length > 0) {
        // For simplicity, we're not handling step updates here
        // In a real implementation, you would need to handle creating/updating/deleting steps
        toast.info('Steps would be saved in a real implementation');
      }
      
      resetForm();
      onSuccess();
    } catch (error) {
      console.error('Error saving workflow:', error);
      toast.error('Failed to save workflow');
    } finally {
      setSaving(false);
    }
  };
  
  const handleAddStep = () => {
    const newStep: WorkflowStep = {
      id: -1 * (steps.length + 1), // Temporary negative ID for new steps
      workflow_id: workflow?.id || 0,
      step_number: steps.length + 1,
      name: `Step ${steps.length + 1}`,
      description: '',
      step_type: 'task',
      config: {},
      is_required: true,
      created_by: 1, // Placeholder
      created_at: new Date().toISOString()
    };
    
    setSteps([...steps, newStep]);
    setSelectedStep(newStep);
  };
  
  const handleDeleteStep = (stepId: number) => {
    if (window.confirm('Are you sure you want to delete this step?')) {
      const updatedSteps = steps.filter(step => step.id !== stepId);
      
      // Renumber remaining steps
      const renumberedSteps = updatedSteps.map((step, index) => ({
        ...step,
        step_number: index + 1
      }));
      
      setSteps(renumberedSteps);
      
      if (selectedStep && selectedStep.id === stepId) {
        setSelectedStep(null);
      }
    }
  };
  
  const handleMoveStep = (stepId: number, direction: 'up' | 'down') => {
    const stepIndex = steps.findIndex(step => step.id === stepId);
    
    if (
      (direction === 'up' && stepIndex === 0) ||
      (direction === 'down' && stepIndex === steps.length - 1)
    ) {
      return;
    }
    
    const newSteps = [...steps];
    const targetIndex = direction === 'up' ? stepIndex - 1 : stepIndex + 1;
    
    // Swap steps
    [newSteps[stepIndex], newSteps[targetIndex]] = [newSteps[targetIndex], newSteps[stepIndex]];
    
    // Renumber steps
    const renumberedSteps = newSteps.map((step, index) => ({
      ...step,
      step_number: index + 1
    }));
    
    setSteps(renumberedSteps);
  };
  
  const handleStepChange = (field: string, value: any) => {
    if (!selectedStep) return;
    
    const updatedStep = { ...selectedStep, [field]: value };
    
    const updatedSteps = steps.map(step => 
      step.id === selectedStep.id ? updatedStep : step
    );
    
    setSteps(updatedSteps);
    setSelectedStep(updatedStep);
  };
  
  const generateFlowVisualization = () => {
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
          animated: true,
          markerEnd: {
            type: MarkerType.ArrowClosed
          }
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
  
  const renderDetailsTab = () => {
    return (
      <Form>
        <Row>
          <Col md={6}>
            <FormGroup>
              <Label for="name">Workflow Name *</Label>
              <Input
                id="name"
                value={name}
                onChange={(e) => setName(e.target.value)}
                invalid={!!errors.name}
              />
              <FormFeedback>{errors.name}</FormFeedback>
            </FormGroup>
            
            <FormGroup>
              <Label for="description">Description</Label>
              <Input
                id="description"
                type="textarea"
                rows={3}
                value={description}
                onChange={(e) => setDescription(e.target.value)}
              />
            </FormGroup>
            
            <FormGroup>
              <Label for="category">Category *</Label>
              <Select
                id="category"
                options={categories.map(cat => ({ value: cat, label: cat }))}
                value={category ? { value: category, label: category } : null}
                onChange={(option: any) => setCategory(option ? option.value : '')}
                placeholder="Select a category"
                className={errors.category ? 'is-invalid' : ''}
              />
              {errors.category && (
                <div className="invalid-feedback d-block">{errors.category}</div>
              )}
            </FormGroup>
          </Col>
          
          <Col md={6}>
            <FormGroup>
              <Label for="triggerType">Trigger Type</Label>
              <div className="mb-2">
                <Button
                  color={triggerType === 'manual' ? 'primary' : 'light'}
                  className="me-2 mb-2"
                  onClick={() => setTriggerType('manual')}
                >
                  <FontAwesomeIcon icon={faPlay} className="me-2" /> Manual
                </Button>
                <Button
                  color={triggerType === 'event' ? 'primary' : 'light'}
                  className="me-2 mb-2"
                  onClick={() => setTriggerType('event')}
                >
                  <FontAwesomeIcon icon={faBell} className="me-2" /> Event
                </Button>
                <Button
                  color={triggerType === 'scheduled' ? 'primary' : 'light'}
                  className="mb-2"
                  onClick={() => setTriggerType('scheduled')}
                >
                  <FontAwesomeIcon icon={faCalendarAlt} className="me-2" /> Scheduled
                </Button>
              </div>
              
              {triggerType === 'event' && (
                <FormGroup>
                  <Label for="eventType">Event Type *</Label>
                  <Input
                    id="eventType"
                    type="select"
                    value={triggerConfig.event_type || ''}
                    onChange={(e) => setTriggerConfig({ ...triggerConfig, event_type: e.target.value })}
                    invalid={!!errors.event_type}
                  >
                    <option value="">Select an event type</option>
                    <option value="order_created">Order Created</option>
                    <option value="order_updated">Order Updated</option>
                    <option value="order_shipped">Order Shipped</option>
                    <option value="inventory_low">Inventory Low</option>
                    <option value="product_received">Product Received</option>
                    <option value="custom">Custom Event</option>
                  </Input>
                  <FormFeedback>{errors.event_type}</FormFeedback>
                </FormGroup>
              )}
              
              {triggerType === 'scheduled' && (
                <FormGroup>
                  <Label for="schedule">Schedule *</Label>
                  <Input
                    id="schedule"
                    placeholder="CRON expression (e.g., 0 0 * * *)"
                    value={triggerConfig.schedule || ''}
                    onChange={(e) => setTriggerConfig({ ...triggerConfig, schedule: e.target.value })}
                    invalid={!!errors.schedule}
                  />
                  <FormFeedback>{errors.schedule}</FormFeedback>
                  <small className="text-muted">
                    Enter a CRON expression to define when this workflow should run.
                  </small>
                </FormGroup>
              )}
            </FormGroup>
            
            <FormGroup check className="mt-4">
              <Label check>
                <Input
                  type="checkbox"
                  checked={isTemplate}
                  onChange={(e) => setIsTemplate(e.target.checked)}
                />{' '}
                Save as template
              </Label>
              <small className="text-muted d-block mt-1">
                Templates can be used as a starting point for new workflows.
              </small>
            </FormGroup>
          </Col>
        </Row>
      </Form>
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
    
    return (
      <Row>
        <Col md={4}>
          <div className="d-flex justify-content-between align-items-center mb-3">
            <h5 className="mb-0">Steps</h5>
            <Button color="primary" size="sm" onClick={handleAddStep}>
              <FontAwesomeIcon icon={faPlus} className="me-1" /> Add Step
            </Button>
          </div>
          
          <div className="steps-list">
            {steps.length === 0 ? (
              <Alert color="info">
                No steps defined yet. Click "Add Step" to create your first workflow step.
              </Alert>
            ) : (
              steps.map((step, index) => (
                <Card 
                  key={step.id} 
                  className={`step-card mb-2 ${selectedStep?.id === step.id ? 'selected' : ''}`}
                  onClick={() => setSelectedStep(step)}
                >
                  <CardBody className="p-2">
                    <div className="d-flex justify-content-between align-items-center">
                      <div>
                        <Badge color="secondary" className="me-2">{index + 1}</Badge>
                        <span className="fw-bold">{step.name}</span>
                      </div>
                      <div>
                        <Button color="link" className="p-0 me-1" title="Move Up" onClick={(e) => {
                          e.stopPropagation();
                          handleMoveStep(step.id, 'up');
                        }} disabled={index === 0}>
                          <FontAwesomeIcon icon={faArrowUp} />
                        </Button>
                        <Button color="link" className="p-0 me-1" title="Move Down" onClick={(e) => {
                          e.stopPropagation();
                          handleMoveStep(step.id, 'down');
                        }} disabled={index === steps.length - 1}>
                          <FontAwesomeIcon icon={faArrowDown} />
                        </Button>
                        <Button color="link" className="p-0 text-danger" title="Delete" onClick={(e) => {
                          e.stopPropagation();
                          handleDeleteStep(step.id);
                        }}>
                          <FontAwesomeIcon icon={faTrash} />
                        </Button>
                      </div>
                    </div>
                    <div className="mt-1">
                      <Badge color={getStepTypeBadgeColor(step.step_type)}>
                        {getStepTypeIcon(step.step_type)} {step.step_type}
                      </Badge>
                    </div>
                  </CardBody>
                </Card>
              ))
            )}
          </div>
        </Col>
        
        <Col md={8}>
          {selectedStep ? (
            <Card>
              <CardBody>
                <h5>Step Configuration</h5>
                <Form>
                  <FormGroup>
                    <Label for="stepName">Step Name</Label>
                    <Input
                      id="stepName"
                      value={selectedStep.name}
                      onChange={(e) => handleStepChange('name', e.target.value)}
                    />
                  </FormGroup>
                  
                  <FormGroup>
                    <Label for="stepDescription">Description</Label>
                    <Input
                      id="stepDescription"
                      type="textarea"
                      rows={2}
                      value={selectedStep.description || ''}
                      onChange={(e) => handleStepChange('description', e.target.value)}
                    />
                  </FormGroup>
                  
                  <FormGroup>
                    <Label for="stepType">Step Type</Label>
                    <Input
                      id="stepType"
                      type="select"
                      value={selectedStep.step_type}
                      onChange={(e) => handleStepChange('step_type', e.target.value)}
                    >
                      <option value="task">Task</option>
                      <option value="approval">Approval</option>
                      <option value="condition">Condition</option>
                      <option value="notification">Notification</option>
                      <option value="integration">Integration</option>
                      <option value="delay">Delay</option>
                      <option value="subprocess">Subprocess</option>
                    </Input>
                  </FormGroup>
                  
                  <FormGroup check>
                    <Label check>
                      <Input
                        type="checkbox"
                        checked={selectedStep.is_required}
                        onChange={(e) => handleStepChange('is_required', e.target.checked)}
                      />{' '}
                      Required Step
                    </Label>
                  </FormGroup>
                  
                  {/* Step-specific configuration would go here */}
                  <Alert color="info" className="mt-3">
                    <FontAwesomeIcon icon={faInfoCircle} className="me-2" />
                    Additional configuration options for {selectedStep.step_type} steps would be displayed here.
                  </Alert>
                </Form>
              </CardBody>
            </Card>
          ) : (
            <div className="text-center py-5 text-muted">
              <FontAwesomeIcon icon={faWrench} size="3x" className="mb-3" />
              <p>Select a step from the list to configure it, or add a new step to get started.</p>
            </div>
          )}
        </Col>
      </Row>
    );
  };
  
  const renderVisualizationTab = () => {
    return (
      <div className="workflow-visualization">
        <div className="flow-container" ref={reactFlowWrapper} style={{ height: '500px' }}>
          <ReactFlowProvider>
            <ReactFlow
              nodes={nodes}
              edges={edges}
              onNodesChange={onNodesChange}
              onEdgesChange={onEdgesChange}
              fitView
            >
              <Controls />
              <MiniMap />
              <Background />
            </ReactFlow>
          </ReactFlowProvider>
        </div>
      </div>
    );
  };
  
  return (
    <Modal isOpen={isOpen} toggle={toggle} size="xl">
      <ModalHeader toggle={toggle}>
        {workflow ? `Edit Workflow: ${workflow.name}` : 'Create New Workflow'}
      </ModalHeader>
      <ModalBody>
        <Nav tabs className="mb-4">
          <NavItem>
            <NavLink
              className={activeTab === 'details' ? 'active' : ''}
              onClick={() => setActiveTab('details')}
            >
              <FontAwesomeIcon icon={faInfoCircle} className="me-2" /> Details
            </NavLink>
          </NavItem>
          <NavItem>
            <NavLink
              className={activeTab === 'steps' ? 'active' : ''}
              onClick={() => setActiveTab('steps')}
              disabled={!workflow && !name}
            >
              <FontAwesomeIcon icon={faList} className="me-2" /> Steps
            </NavLink>
          </NavItem>
          <NavItem>
            <NavLink
              className={activeTab === 'visualization' ? 'active' : ''}
              onClick={() => setActiveTab('visualization')}
              disabled={steps.length === 0}
            >
              <FontAwesomeIcon icon={faCodeBranch} className="me-2" /> Visualization
            </NavLink>
          </NavItem>
        </Nav>
        
        <TabContent activeTab={activeTab}>
          <TabPane tabId="details">
            {renderDetailsTab()}
          </TabPane>
          <TabPane tabId="steps">
            {renderStepsTab()}
          </TabPane>
          <TabPane tabId="visualization">
            {renderVisualizationTab()}
          </TabPane>
        </TabContent>
      </ModalBody>
      <ModalFooter>
        <Button color="secondary" onClick={toggle} disabled={saving}>
          Cancel
        </Button>
        <Button color="primary" onClick={handleSaveWorkflow} disabled={saving}>
          {saving ? (
            <>
              <Spinner size="sm" className="me-2" /> Saving...
            </>
          ) : (
            <>
              <FontAwesomeIcon icon={faSave} className="me-2" /> Save Workflow
            </>
          )}
        </Button>
      </ModalFooter>
    </Modal>
  );
};

export default WorkflowDesignerModal;