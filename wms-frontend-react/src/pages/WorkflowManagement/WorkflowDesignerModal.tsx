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
  Row,
  Col,
  Card,
  CardHeader,
  CardBody,
  Badge,
  Alert,
  Nav,
  NavItem,
  NavLink,
  TabContent,
  TabPane,
  Table,
  FormFeedback
} from 'reactstrap';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import {
  faPlus,
  faMinus,
  faArrowUp,
  faArrowDown,
  faEdit,
  faTrash,
  faCog,
  faPlay,
  faUserCheck,
  faBell,
  faCode,
  faQuestion,
  faLink,
  faSave,
  faEye,
  faProjectDiagram
} from '@fortawesome/free-solid-svg-icons';
import { toast } from 'react-toastify';
import Select from 'react-select';

import { WorkflowDefinition, WorkflowStep, workflowDefinitionApi } from '../../services/workflowManagementApi';

interface WorkflowDesignerModalProps {
  isOpen: boolean;
  toggle: () => void;
  workflow?: WorkflowDefinition | null;
  onSuccess: () => void;
}

const WorkflowDesignerModal: React.FC<WorkflowDesignerModalProps> = ({
  isOpen,
  toggle,
  workflow,
  onSuccess
}) => {
  // State
  const [activeTab, setActiveTab] = useState<string>('basic');
  const [loading, setLoading] = useState<boolean>(false);
  const [errors, setErrors] = useState<Record<string, string>>({});
  
  // Basic workflow info
  const [name, setName] = useState<string>('');
  const [description, setDescription] = useState<string>('');
  const [category, setCategory] = useState<string>('');
  const [triggerType, setTriggerType] = useState<string>('manual');
  const [triggerConditions, setTriggerConditions] = useState<any>({});
  const [tags, setTags] = useState<string[]>([]);
  
  // Workflow steps
  const [steps, setSteps] = useState<Partial<WorkflowStep>[]>([]);
  const [selectedStepIndex, setSelectedStepIndex] = useState<number | null>(null);
  
  // Step configuration
  const [stepName, setStepName] = useState<string>('');
  const [stepDescription, setStepDescription] = useState<string>('');
  const [stepType, setStepType] = useState<string>('approval');
  const [stepConfiguration, setStepConfiguration] = useState<any>({});
  const [assignedTo, setAssignedTo] = useState<number | null>(null);
  const [assignedGroup, setAssignedGroup] = useState<number | null>(null);
  const [timeoutHours, setTimeoutHours] = useState<number>(24);
  const [isRequired, setIsRequired] = useState<boolean>(true);
  const [parallelExecution, setParallelExecution] = useState<boolean>(false);
  
  // Mock data
  const categories = [
    'Approval Process',
    'Document Review',
    'Quality Control',
    'Inventory Management',
    'Order Processing',
    'Compliance',
    'HR Process',
    'Finance Process'
  ];
  
  const users = [
    { id: 1, name: 'John Doe' },
    { id: 2, name: 'Jane Smith' },
    { id: 3, name: 'Robert Johnson' },
    { id: 4, name: 'Emily Davis' },
    { id: 5, name: 'Michael Wilson' }
  ];
  
  const userGroups = [
    { id: 1, name: 'Administrators' },
    { id: 2, name: 'Managers' },
    { id: 3, name: 'Warehouse Staff' },
    { id: 4, name: 'Finance Department' },
    { id: 5, name: 'HR Department' }
  ];
  
  const stepTypes = [
    { value: 'approval', label: 'Approval', icon: faUserCheck, description: 'Requires user approval to proceed' },
    { value: 'notification', label: 'Notification', icon: faBell, description: 'Sends notification to users' },
    { value: 'task', label: 'Task', icon: faPlay, description: 'Assigns a task to be completed' },
    { value: 'condition', label: 'Condition', icon: faQuestion, description: 'Evaluates conditions to determine next step' },
    { value: 'script', label: 'Script', icon: faCode, description: 'Executes custom script or automation' },
    { value: 'integration', label: 'Integration', icon: faLink, description: 'Integrates with external systems' }
  ];
  
  // Initialize form when modal opens
  useEffect(() => {
    if (isOpen) {
      if (workflow) {
        // Edit mode
        setName(workflow.name);
        setDescription(workflow.description || '');
        setCategory(workflow.category);
        setTriggerType(workflow.trigger_type);
        setTriggerConditions(workflow.trigger_conditions || {});
        setTags(workflow.tags || []);
        setSteps(workflow.steps || []);
      } else {
        // Create mode
        resetForm();
      }
    }
  }, [isOpen, workflow]);
  
  const resetForm = () => {
    setName('');
    setDescription('');
    setCategory('');
    setTriggerType('manual');
    setTriggerConditions({});
    setTags([]);
    setSteps([]);
    setSelectedStepIndex(null);
    resetStepForm();
    setErrors({});
  };
  
  const resetStepForm = () => {
    setStepName('');
    setStepDescription('');
    setStepType('approval');
    setStepConfiguration({});
    setAssignedTo(null);
    setAssignedGroup(null);
    setTimeoutHours(24);
    setIsRequired(true);
    setParallelExecution(false);
  };
  
  const validateForm = () => {
    const newErrors: Record<string, string> = {};
    
    if (!name.trim()) {
      newErrors.name = 'Workflow name is required';
    }
    
    if (!category) {
      newErrors.category = 'Category is required';
    }
    
    if (steps.length === 0) {
      newErrors.steps = 'At least one step is required';
    }
    
    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };
  
  const validateStep = () => {
    if (!stepName.trim()) {
      toast.error('Step name is required');
      return false;
    }
    
    if (!stepType) {
      toast.error('Step type is required');
      return false;
    }
    
    if (['approval', 'task'].includes(stepType) && !assignedTo && !assignedGroup) {
      toast.error('Assignment is required for approval and task steps');
      return false;
    }
    
    return true;
  };
  
  const addStep = () => {
    if (!validateStep()) return;
    
    const newStep: Partial<WorkflowStep> = {
      step_number: steps.length + 1,
      name: stepName,
      description: stepDescription || undefined,
      step_type: stepType as any,
      configuration: stepConfiguration,
      assigned_to: assignedTo || undefined,
      assigned_group: assignedGroup || undefined,
      timeout_hours: timeoutHours,
      is_required: isRequired,
      parallel_execution: parallelExecution || undefined
    };
    
    setSteps([...steps, newStep]);
    resetStepForm();
    toast.success('Step added successfully');
  };
  
  const updateStep = () => {
    if (selectedStepIndex === null || !validateStep()) return;
    
    const updatedSteps = [...steps];
    updatedSteps[selectedStepIndex] = {
      ...updatedSteps[selectedStepIndex],
      name: stepName,
      description: stepDescription || undefined,
      step_type: stepType as any,
      configuration: stepConfiguration,
      assigned_to: assignedTo || undefined,
      assigned_group: assignedGroup || undefined,
      timeout_hours: timeoutHours,
      is_required: isRequired,
      parallel_execution: parallelExecution || undefined
    };
    
    setSteps(updatedSteps);
    setSelectedStepIndex(null);
    resetStepForm();
    toast.success('Step updated successfully');
  };
  
  const editStep = (index: number) => {
    const step = steps[index];
    setSelectedStepIndex(index);
    setStepName(step.name || '');
    setStepDescription(step.description || '');
    setStepType(step.step_type || 'approval');
    setStepConfiguration(step.configuration || {});
    setAssignedTo(step.assigned_to || null);
    setAssignedGroup(step.assigned_group || null);
    setTimeoutHours(step.timeout_hours || 24);
    setIsRequired(step.is_required !== false);
    setParallelExecution(step.parallel_execution || false);
    setActiveTab('steps');
  };
  
  const deleteStep = (index: number) => {
    if (window.confirm('Are you sure you want to delete this step?')) {
      const updatedSteps = steps.filter((_, i) => i !== index);
      // Renumber steps
      const renumberedSteps = updatedSteps.map((step, i) => ({
        ...step,
        step_number: i + 1
      }));
      setSteps(renumberedSteps);
      
      if (selectedStepIndex === index) {
        setSelectedStepIndex(null);
        resetStepForm();
      } else if (selectedStepIndex !== null && selectedStepIndex > index) {
        setSelectedStepIndex(selectedStepIndex - 1);
      }
      
      toast.success('Step deleted successfully');
    }
  };
  
  const moveStep = (index: number, direction: 'up' | 'down') => {
    if ((direction === 'up' && index === 0) || (direction === 'down' && index === steps.length - 1)) {
      return;
    }
    
    const newIndex = direction === 'up' ? index - 1 : index + 1;
    const updatedSteps = [...steps];
    
    // Swap steps
    [updatedSteps[index], updatedSteps[newIndex]] = [updatedSteps[newIndex], updatedSteps[index]];
    
    // Update step numbers
    updatedSteps[index].step_number = index + 1;
    updatedSteps[newIndex].step_number = newIndex + 1;
    
    setSteps(updatedSteps);
    
    // Update selected step index if needed
    if (selectedStepIndex === index) {
      setSelectedStepIndex(newIndex);
    } else if (selectedStepIndex === newIndex) {
      setSelectedStepIndex(index);
    }
  };
  
  const handleSubmit = async () => {
    if (!validateForm()) return;
    
    setLoading(true);
    
    try {
      const workflowData = {
        name,
        description: description || undefined,
        category,
        trigger_type: triggerType,
        trigger_conditions: Object.keys(triggerConditions).length > 0 ? triggerConditions : undefined,
        steps: steps.map((step, index) => ({
          ...step,
          step_number: index + 1
        })),
        tags: tags.length > 0 ? tags : undefined
      };
      
      if (workflow) {
        await workflowDefinitionApi.updateWorkflowDefinition(workflow.id, workflowData);
        toast.success('Workflow updated successfully');
      } else {
        await workflowDefinitionApi.createWorkflowDefinition(workflowData);
        toast.success('Workflow created successfully');
      }
      
      onSuccess();
    } catch (error) {
      console.error('Error saving workflow:', error);
      toast.error('Failed to save workflow');
    } finally {
      setLoading(false);
    }
  };
  
  const getStepTypeIcon = (type: string) => {
    const stepType = stepTypes.find(st => st.value === type);
    return stepType ? stepType.icon : faPlay;
  };
  
  const getStepTypeLabel = (type: string) => {
    const stepType = stepTypes.find(st => st.value === type);
    return stepType ? stepType.label : type;
  };
  
  const renderBasicInfo = () => (
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
            <Label for="category">Category *</Label>
            <Input
              type="select"
              id="category"
              value={category}
              onChange={(e) => setCategory(e.target.value)}
              invalid={!!errors.category}
            >
              <option value="">-- Select Category --</option>
              {categories.map(cat => (
                <option key={cat} value={cat}>{cat}</option>
              ))}
            </Input>
            <FormFeedback>{errors.category}</FormFeedback>
          </FormGroup>
          
          <FormGroup>
            <Label for="triggerType">Trigger Type</Label>
            <Input
              type="select"
              id="triggerType"
              value={triggerType}
              onChange={(e) => setTriggerType(e.target.value)}
            >
              <option value="manual">Manual</option>
              <option value="automatic">Automatic</option>
              <option value="scheduled">Scheduled</option>
              <option value="event">Event-based</option>
            </Input>
          </FormGroup>
        </Col>
        
        <Col md={6}>
          <FormGroup>
            <Label for="description">Description</Label>
            <Input
              type="textarea"
              id="description"
              rows={3}
              value={description}
              onChange={(e) => setDescription(e.target.value)}
            />
          </FormGroup>
          
          <FormGroup>
            <Label for="tags">Tags (comma-separated)</Label>
            <Input
              id="tags"
              value={tags.join(', ')}
              onChange={(e) => setTags(e.target.value.split(',').map(tag => tag.trim()).filter(tag => tag))}
              placeholder="approval, urgent, finance"
            />
          </FormGroup>
        </Col>
      </Row>
    </Form>
  );
  
  const renderStepDesigner = () => (
    <Row>
      <Col md={8}>
        <Card>
          <CardHeader>
            <h5 className="mb-0">Workflow Steps</h5>
          </CardHeader>
          <CardBody>
            {steps.length === 0 ? (
              <Alert color="info">
                No steps defined yet. Add your first step to get started.
              </Alert>
            ) : (
              <div className="workflow-steps">
                {steps.map((step, index) => (
                  <div key={index} className={`workflow-step ${selectedStepIndex === index ? 'selected' : ''}`}>
                    <div className="step-header">
                      <div className="step-number">{index + 1}</div>
                      <div className="step-info">
                        <div className="step-title">
                          <FontAwesomeIcon icon={getStepTypeIcon(step.step_type || '')} className="me-2" />
                          {step.name}
                          <Badge color="light" className="ms-2">{getStepTypeLabel(step.step_type || '')}</Badge>
                        </div>
                        {step.description && (
                          <div className="step-description">{step.description}</div>
                        )}
                      </div>
                      <div className="step-actions">
                        <Button color="link" size="sm" onClick={() => moveStep(index, 'up')} disabled={index === 0}>
                          <FontAwesomeIcon icon={faArrowUp} />
                        </Button>
                        <Button color="link" size="sm" onClick={() => moveStep(index, 'down')} disabled={index === steps.length - 1}>
                          <FontAwesomeIcon icon={faArrowDown} />
                        </Button>
                        <Button color="link" size="sm" onClick={() => editStep(index)}>
                          <FontAwesomeIcon icon={faEdit} />
                        </Button>
                        <Button color="link" size="sm" className="text-danger" onClick={() => deleteStep(index)}>
                          <FontAwesomeIcon icon={faTrash} />
                        </Button>
                      </div>
                    </div>
                    {index < steps.length - 1 && (
                      <div className="step-connector">
                        <div className="connector-line"></div>
                      </div>
                    )}
                  </div>
                ))}
              </div>
            )}
            
            {errors.steps && (
              <Alert color="danger" className="mt-3">{errors.steps}</Alert>
            )}
          </CardBody>
        </Card>
      </Col>
      
      <Col md={4}>
        <Card>
          <CardHeader>
            <h5 className="mb-0">
              {selectedStepIndex !== null ? 'Edit Step' : 'Add Step'}
            </h5>
          </CardHeader>
          <CardBody>
            <Form>
              <FormGroup>
                <Label for="stepName">Step Name *</Label>
                <Input
                  id="stepName"
                  value={stepName}
                  onChange={(e) => setStepName(e.target.value)}
                />
              </FormGroup>
              
              <FormGroup>
                <Label for="stepType">Step Type *</Label>
                <Input
                  type="select"
                  id="stepType"
                  value={stepType}
                  onChange={(e) => setStepType(e.target.value)}
                >
                  {stepTypes.map(type => (
                    <option key={type.value} value={type.value}>{type.label}</option>
                  ))}
                </Input>
                <small className="text-muted">
                  {stepTypes.find(t => t.value === stepType)?.description}
                </small>
              </FormGroup>
              
              <FormGroup>
                <Label for="stepDescription">Description</Label>
                <Input
                  type="textarea"
                  id="stepDescription"
                  rows={2}
                  value={stepDescription}
                  onChange={(e) => setStepDescription(e.target.value)}
                />
              </FormGroup>
              
              {['approval', 'task'].includes(stepType) && (
                <>
                  <FormGroup>
                    <Label for="assignedTo">Assigned User</Label>
                    <Select
                      id="assignedTo"
                      options={users.map(user => ({ value: user.id, label: user.name }))}
                      value={assignedTo ? { value: assignedTo, label: users.find(u => u.id === assignedTo)?.name } : null}
                      onChange={(option: any) => {
                        setAssignedTo(option ? option.value : null);
                        if (option) setAssignedGroup(null);
                      }}
                      placeholder="Select user"
                      isClearable
                      isDisabled={!!assignedGroup}
                    />
                  </FormGroup>
                  
                  <FormGroup>
                    <Label for="assignedGroup">Assigned Group</Label>
                    <Select
                      id="assignedGroup"
                      options={userGroups.map(group => ({ value: group.id, label: group.name }))}
                      value={assignedGroup ? { value: assignedGroup, label: userGroups.find(g => g.id === assignedGroup)?.name } : null}
                      onChange={(option: any) => {
                        setAssignedGroup(option ? option.value : null);
                        if (option) setAssignedTo(null);
                      }}
                      placeholder="Select group"
                      isClearable
                      isDisabled={!!assignedTo}
                    />
                  </FormGroup>
                </>
              )}
              
              <FormGroup>
                <Label for="timeoutHours">Timeout (hours)</Label>
                <Input
                  type="number"
                  id="timeoutHours"
                  value={timeoutHours}
                  onChange={(e) => setTimeoutHours(parseInt(e.target.value) || 24)}
                  min={1}
                  max={168}
                />
              </FormGroup>
              
              <FormGroup check>
                <Input
                  type="checkbox"
                  id="isRequired"
                  checked={isRequired}
                  onChange={(e) => setIsRequired(e.target.checked)}
                />
                <Label check for="isRequired">Required Step</Label>
              </FormGroup>
              
              <FormGroup check>
                <Input
                  type="checkbox"
                  id="parallelExecution"
                  checked={parallelExecution}
                  onChange={(e) => setParallelExecution(e.target.checked)}
                />
                <Label check for="parallelExecution">Allow Parallel Execution</Label>
              </FormGroup>
              
              <div className="mt-3">
                {selectedStepIndex !== null ? (
                  <>
                    <Button color="primary" onClick={updateStep} className="me-2">
                      Update Step
                    </Button>
                    <Button color="secondary" onClick={() => {
                      setSelectedStepIndex(null);
                      resetStepForm();
                    }}>
                      Cancel
                    </Button>
                  </>
                ) : (
                  <Button color="primary" onClick={addStep}>
                    <FontAwesomeIcon icon={faPlus} className="me-1" /> Add Step
                  </Button>
                )}
              </div>
            </Form>
          </CardBody>
        </Card>
      </Col>
    </Row>
  );
  
  const renderPreview = () => (
    <div className="workflow-preview">
      <Card>
        <CardHeader>
          <h5 className="mb-0">
            <FontAwesomeIcon icon={faProjectDiagram} className="me-2" />
            Workflow Preview
          </h5>
        </CardHeader>
        <CardBody>
          <Row>
            <Col md={6}>
              <h6>Basic Information</h6>
              <Table borderless size="sm">
                <tbody>
                  <tr>
                    <th width="30%">Name:</th>
                    <td>{name || 'Untitled Workflow'}</td>
                  </tr>
                  <tr>
                    <th>Category:</th>
                    <td>{category || 'Not specified'}</td>
                  </tr>
                  <tr>
                    <th>Trigger:</th>
                    <td>{triggerType}</td>
                  </tr>
                  <tr>
                    <th>Steps:</th>
                    <td>{steps.length}</td>
                  </tr>
                  {tags.length > 0 && (
                    <tr>
                      <th>Tags:</th>
                      <td>
                        {tags.map(tag => (
                          <Badge key={tag} color="light" className="me-1">{tag}</Badge>
                        ))}
                      </td>
                    </tr>
                  )}
                </tbody>
              </Table>
            </Col>
            <Col md={6}>
              <h6>Description</h6>
              <p>{description || 'No description provided'}</p>
            </Col>
          </Row>
          
          {steps.length > 0 && (
            <>
              <hr />
              <h6>Workflow Steps</h6>
              <div className="workflow-preview-steps">
                {steps.map((step, index) => (
                  <div key={index} className="preview-step">
                    <div className="step-number">{index + 1}</div>
                    <div className="step-content">
                      <div className="step-title">
                        <FontAwesomeIcon icon={getStepTypeIcon(step.step_type || '')} className="me-2" />
                        {step.name}
                        <Badge color="light" className="ms-2">{getStepTypeLabel(step.step_type || '')}</Badge>
                      </div>
                      {step.description && (
                        <div className="step-description">{step.description}</div>
                      )}
                      <div className="step-details">
                        {step.assigned_to && (
                          <small>Assigned to: {users.find(u => u.id === step.assigned_to)?.name}</small>
                        )}
                        {step.assigned_group && (
                          <small>Assigned to: {userGroups.find(g => g.id === step.assigned_group)?.name}</small>
                        )}
                        {step.timeout_hours && (
                          <small className="ms-2">Timeout: {step.timeout_hours}h</small>
                        )}
                      </div>
                    </div>
                  </div>
                ))}
              </div>
            </>
          )}
        </CardBody>
      </Card>
    </div>
  );
  
  return (
    <Modal isOpen={isOpen} toggle={toggle} size="xl">
      <ModalHeader toggle={toggle}>
        <FontAwesomeIcon icon={faProjectDiagram} className="me-2" />
        {workflow ? 'Edit Workflow' : 'Create New Workflow'}
      </ModalHeader>
      <ModalBody>
        <Nav tabs className="mb-3">
          <NavItem>
            <NavLink
              className={activeTab === 'basic' ? 'active' : ''}
              onClick={() => setActiveTab('basic')}
            >
              <FontAwesomeIcon icon={faCog} className="me-1" /> Basic Info
            </NavLink>
          </NavItem>
          <NavItem>
            <NavLink
              className={activeTab === 'steps' ? 'active' : ''}
              onClick={() => setActiveTab('steps')}
            >
              <FontAwesomeIcon icon={faPlay} className="me-1" /> Steps
              {steps.length > 0 && (
                <Badge color="primary" className="ms-1">{steps.length}</Badge>
              )}
            </NavLink>
          </NavItem>
          <NavItem>
            <NavLink
              className={activeTab === 'preview' ? 'active' : ''}
              onClick={() => setActiveTab('preview')}
            >
              <FontAwesomeIcon icon={faEye} className="me-1" /> Preview
            </NavLink>
          </NavItem>
        </Nav>
        
        <TabContent activeTab={activeTab}>
          <TabPane tabId="basic">
            {renderBasicInfo()}
          </TabPane>
          <TabPane tabId="steps">
            {renderStepDesigner()}
          </TabPane>
          <TabPane tabId="preview">
            {renderPreview()}
          </TabPane>
        </TabContent>
      </ModalBody>
      <ModalFooter>
        <Button color="secondary" onClick={toggle} disabled={loading}>
          Cancel
        </Button>
        <Button color="primary" onClick={handleSubmit} disabled={loading}>
          <FontAwesomeIcon icon={faSave} className="me-1" />
          {loading ? 'Saving...' : workflow ? 'Update Workflow' : 'Create Workflow'}
        </Button>
      </ModalFooter>
    </Modal>
  );
};

export default WorkflowDesignerModal;