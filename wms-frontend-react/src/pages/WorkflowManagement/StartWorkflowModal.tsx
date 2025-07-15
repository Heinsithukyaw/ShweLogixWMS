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
  Card,
  CardBody,
  Badge,
  Alert,
  FormFeedback,
  Table
} from 'reactstrap';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import {
  faPlay,
  faProjectDiagram,
  faCalendarAlt,
  faExclamationTriangle,
  faInfoCircle,
  faUserCheck,
  faBell,
  faCode,
  faQuestion,
  faLink
} from '@fortawesome/free-solid-svg-icons';
import { toast } from 'react-toastify';
import Select from 'react-select';
import DatePicker from 'react-datepicker';
import 'react-datepicker/dist/react-datepicker.css';

import { WorkflowDefinition, workflowDefinitionApi, workflowInstanceApi } from '../../services/workflowManagementApi';

interface StartWorkflowModalProps {
  isOpen: boolean;
  toggle: () => void;
  workflow?: WorkflowDefinition | null;
  onSuccess: () => void;
}

const StartWorkflowModal: React.FC<StartWorkflowModalProps> = ({
  isOpen,
  toggle,
  workflow,
  onSuccess
}) => {
  // State
  const [availableWorkflows, setAvailableWorkflows] = useState<WorkflowDefinition[]>([]);
  const [selectedWorkflowId, setSelectedWorkflowId] = useState<number | null>(null);
  const [selectedWorkflow, setSelectedWorkflow] = useState<WorkflowDefinition | null>(null);
  const [instanceName, setInstanceName] = useState<string>('');
  const [priority, setPriority] = useState<string>('normal');
  const [dueDate, setDueDate] = useState<Date | null>(null);
  const [notes, setNotes] = useState<string>('');
  const [contextData, setContextData] = useState<Record<string, any>>({});
  const [loading, setLoading] = useState<boolean>(false);
  const [errors, setErrors] = useState<Record<string, string>>({});
  
  // Context data fields (dynamic based on workflow)
  const [contextFields, setContextFields] = useState<any[]>([]);
  
  // Fetch available workflows when modal opens
  useEffect(() => {
    if (isOpen) {
      if (workflow) {
        setSelectedWorkflow(workflow);
        setSelectedWorkflowId(workflow.id);
        setInstanceName(`${workflow.name} - ${new Date().toLocaleDateString()}`);
        generateContextFields(workflow);
      } else {
        fetchAvailableWorkflows();
        resetForm();
      }
    }
  }, [isOpen, workflow]);
  
  // Update selected workflow when selection changes
  useEffect(() => {
    if (selectedWorkflowId && availableWorkflows.length > 0) {
      const selected = availableWorkflows.find(w => w.id === selectedWorkflowId);
      setSelectedWorkflow(selected || null);
      if (selected) {
        setInstanceName(`${selected.name} - ${new Date().toLocaleDateString()}`);
        generateContextFields(selected);
      }
    }
  }, [selectedWorkflowId, availableWorkflows]);
  
  const fetchAvailableWorkflows = async () => {
    try {
      const response = await workflowDefinitionApi.getWorkflowDefinitions({
        status: 'active'
      });
      setAvailableWorkflows(response.data);
    } catch (error) {
      console.error('Error fetching workflows:', error);
      toast.error('Failed to load available workflows');
    }
  };
  
  const generateContextFields = (workflowDef: WorkflowDefinition) => {
    // Generate context fields based on workflow configuration
    // This would typically come from the workflow definition
    const fields = [];
    
    // Common context fields
    fields.push({
      key: 'reference_id',
      label: 'Reference ID',
      type: 'text',
      required: false,
      description: 'External reference identifier'
    });
    
    fields.push({
      key: 'department',
      label: 'Department',
      type: 'select',
      required: false,
      options: [
        { value: 'warehouse', label: 'Warehouse' },
        { value: 'finance', label: 'Finance' },
        { value: 'hr', label: 'Human Resources' },
        { value: 'operations', label: 'Operations' },
        { value: 'quality', label: 'Quality Control' }
      ],
      description: 'Department initiating the workflow'
    });
    
    // Category-specific fields
    switch (workflowDef.category) {
      case 'Approval Process':
        fields.push({
          key: 'approval_amount',
          label: 'Approval Amount',
          type: 'number',
          required: true,
          description: 'Amount requiring approval'
        });
        fields.push({
          key: 'justification',
          label: 'Justification',
          type: 'textarea',
          required: true,
          description: 'Reason for approval request'
        });
        break;
        
      case 'Document Review':
        fields.push({
          key: 'document_id',
          label: 'Document ID',
          type: 'text',
          required: true,
          description: 'ID of document to be reviewed'
        });
        fields.push({
          key: 'review_type',
          label: 'Review Type',
          type: 'select',
          required: true,
          options: [
            { value: 'technical', label: 'Technical Review' },
            { value: 'compliance', label: 'Compliance Review' },
            { value: 'quality', label: 'Quality Review' },
            { value: 'legal', label: 'Legal Review' }
          ],
          description: 'Type of review required'
        });
        break;
        
      case 'Order Processing':
        fields.push({
          key: 'order_id',
          label: 'Order ID',
          type: 'text',
          required: true,
          description: 'Sales order identifier'
        });
        fields.push({
          key: 'customer_id',
          label: 'Customer ID',
          type: 'text',
          required: true,
          description: 'Customer identifier'
        });
        fields.push({
          key: 'order_value',
          label: 'Order Value',
          type: 'number',
          required: false,
          description: 'Total order value'
        });
        break;
        
      case 'Quality Control':
        fields.push({
          key: 'product_id',
          label: 'Product ID',
          type: 'text',
          required: true,
          description: 'Product identifier'
        });
        fields.push({
          key: 'batch_number',
          label: 'Batch Number',
          type: 'text',
          required: false,
          description: 'Product batch number'
        });
        fields.push({
          key: 'inspection_type',
          label: 'Inspection Type',
          type: 'select',
          required: true,
          options: [
            { value: 'incoming', label: 'Incoming Inspection' },
            { value: 'in_process', label: 'In-Process Inspection' },
            { value: 'final', label: 'Final Inspection' },
            { value: 'audit', label: 'Quality Audit' }
          ],
          description: 'Type of quality inspection'
        });
        break;
    }
    
    setContextFields(fields);
    
    // Initialize context data with default values
    const initialContextData: Record<string, any> = {};
    fields.forEach(field => {
      if (field.type === 'select' && field.options && field.options.length > 0) {
        initialContextData[field.key] = field.required ? field.options[0].value : '';
      } else {
        initialContextData[field.key] = '';
      }
    });
    setContextData(initialContextData);
  };
  
  const resetForm = () => {
    setSelectedWorkflowId(null);
    setSelectedWorkflow(null);
    setInstanceName('');
    setPriority('normal');
    setDueDate(null);
    setNotes('');
    setContextData({});
    setContextFields([]);
    setErrors({});
  };
  
  const validateForm = () => {
    const newErrors: Record<string, string> = {};
    
    if (!selectedWorkflowId) {
      newErrors.workflow = 'Please select a workflow';
    }
    
    if (!instanceName.trim()) {
      newErrors.instanceName = 'Instance name is required';
    }
    
    // Validate required context fields
    contextFields.forEach(field => {
      if (field.required && !contextData[field.key]) {
        newErrors[`context_${field.key}`] = `${field.label} is required`;
      }
    });
    
    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };
  
  const handleContextDataChange = (key: string, value: any) => {
    setContextData(prev => ({
      ...prev,
      [key]: value
    }));
  };
  
  const handleSubmit = async () => {
    if (!validateForm()) return;
    
    setLoading(true);
    
    try {
      const workflowData = {
        workflow_definition_id: selectedWorkflowId!,
        instance_name: instanceName,
        context_data: Object.keys(contextData).length > 0 ? contextData : undefined,
        priority,
        due_date: dueDate ? dueDate.toISOString().split('T')[0] : undefined,
        notes: notes || undefined
      };
      
      await workflowInstanceApi.startWorkflowInstance(workflowData);
      toast.success('Workflow started successfully');
      onSuccess();
    } catch (error) {
      console.error('Error starting workflow:', error);
      toast.error('Failed to start workflow');
    } finally {
      setLoading(false);
    }
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
  
  const renderContextFields = () => {
    if (contextFields.length === 0) return null;
    
    return (
      <Card className="mt-3">
        <CardBody>
          <h6 className="mb-3">Workflow Context</h6>
          <Row>
            {contextFields.map(field => (
              <Col md={6} key={field.key} className="mb-3">
                <FormGroup>
                  <Label for={field.key}>
                    {field.label}
                    {field.required && <span className="text-danger"> *</span>}
                  </Label>
                  
                  {field.type === 'text' && (
                    <Input
                      id={field.key}
                      value={contextData[field.key] || ''}
                      onChange={(e) => handleContextDataChange(field.key, e.target.value)}
                      invalid={!!errors[`context_${field.key}`]}
                    />
                  )}
                  
                  {field.type === 'number' && (
                    <Input
                      type="number"
                      id={field.key}
                      value={contextData[field.key] || ''}
                      onChange={(e) => handleContextDataChange(field.key, parseFloat(e.target.value) || '')}
                      invalid={!!errors[`context_${field.key}`]}
                    />
                  )}
                  
                  {field.type === 'textarea' && (
                    <Input
                      type="textarea"
                      id={field.key}
                      rows={3}
                      value={contextData[field.key] || ''}
                      onChange={(e) => handleContextDataChange(field.key, e.target.value)}
                      invalid={!!errors[`context_${field.key}`]}
                    />
                  )}
                  
                  {field.type === 'select' && (
                    <Input
                      type="select"
                      id={field.key}
                      value={contextData[field.key] || ''}
                      onChange={(e) => handleContextDataChange(field.key, e.target.value)}
                      invalid={!!errors[`context_${field.key}`]}
                    >
                      {!field.required && <option value="">-- Select --</option>}
                      {field.options?.map((option: any) => (
                        <option key={option.value} value={option.value}>
                          {option.label}
                        </option>
                      ))}
                    </Input>
                  )}
                  
                  {field.description && (
                    <small className="text-muted">{field.description}</small>
                  )}
                  
                  <FormFeedback>{errors[`context_${field.key}`]}</FormFeedback>
                </FormGroup>
              </Col>
            ))}
          </Row>
        </CardBody>
      </Card>
    );
  };
  
  return (
    <Modal isOpen={isOpen} toggle={toggle} size="lg">
      <ModalHeader toggle={toggle}>
        <FontAwesomeIcon icon={faPlay} className="me-2" />
        Start Workflow
      </ModalHeader>
      <ModalBody>
        <Form>
          <Row>
            <Col md={6}>
              <FormGroup>
                <Label for="workflow">Workflow *</Label>
                {workflow ? (
                  <div className="selected-workflow">
                    <Card>
                      <CardBody className="py-2">
                        <div className="d-flex align-items-center">
                          <FontAwesomeIcon icon={faProjectDiagram} className="me-2 text-primary" />
                          <div>
                            <strong>{workflow.name}</strong>
                            <div className="text-muted small">{workflow.description}</div>
                          </div>
                          <Badge color="info" className="ms-auto">{workflow.category}</Badge>
                        </div>
                      </CardBody>
                    </Card>
                  </div>
                ) : (
                  <Select
                    id="workflow"
                    options={availableWorkflows.map(w => ({
                      value: w.id,
                      label: `${w.name} (${w.category})`,
                      workflow: w
                    }))}
                    value={selectedWorkflowId ? {
                      value: selectedWorkflowId,
                      label: availableWorkflows.find(w => w.id === selectedWorkflowId)?.name
                    } : null}
                    onChange={(option: any) => setSelectedWorkflowId(option ? option.value : null)}
                    placeholder="Select a workflow"
                    className={errors.workflow ? 'is-invalid' : ''}
                  />
                )}
                <FormFeedback>{errors.workflow}</FormFeedback>
              </FormGroup>
              
              <FormGroup>
                <Label for="instanceName">Instance Name *</Label>
                <Input
                  id="instanceName"
                  value={instanceName}
                  onChange={(e) => setInstanceName(e.target.value)}
                  invalid={!!errors.instanceName}
                />
                <FormFeedback>{errors.instanceName}</FormFeedback>
              </FormGroup>
              
              <FormGroup>
                <Label for="priority">Priority</Label>
                <Input
                  type="select"
                  id="priority"
                  value={priority}
                  onChange={(e) => setPriority(e.target.value)}
                >
                  <option value="low">Low</option>
                  <option value="normal">Normal</option>
                  <option value="high">High</option>
                  <option value="urgent">Urgent</option>
                </Input>
              </FormGroup>
            </Col>
            
            <Col md={6}>
              <FormGroup>
                <Label for="dueDate">Due Date (Optional)</Label>
                <DatePicker
                  selected={dueDate}
                  onChange={(date) => setDueDate(date)}
                  minDate={new Date()}
                  dateFormat="MMMM d, yyyy"
                  className="form-control"
                  id="dueDate"
                  placeholderText="Select due date"
                  isClearable
                />
              </FormGroup>
              
              <FormGroup>
                <Label for="notes">Notes</Label>
                <Input
                  type="textarea"
                  id="notes"
                  rows={3}
                  value={notes}
                  onChange={(e) => setNotes(e.target.value)}
                  placeholder="Enter any additional notes or instructions..."
                />
              </FormGroup>
            </Col>
          </Row>
          
          {renderContextFields()}
          
          {selectedWorkflow && (
            <Card className="mt-3">
              <CardBody>
                <h6 className="mb-3">Workflow Preview</h6>
                <Row>
                  <Col md={6}>
                    <Table borderless size="sm">
                      <tbody>
                        <tr>
                          <th width="30%">Name:</th>
                          <td>{selectedWorkflow.name}</td>
                        </tr>
                        <tr>
                          <th>Category:</th>
                          <td><Badge color="info">{selectedWorkflow.category}</Badge></td>
                        </tr>
                        <tr>
                          <th>Trigger:</th>
                          <td><Badge color="light">{selectedWorkflow.trigger_type}</Badge></td>
                        </tr>
                        <tr>
                          <th>Steps:</th>
                          <td>{selectedWorkflow.steps?.length || 0}</td>
                        </tr>
                      </tbody>
                    </Table>
                  </Col>
                  <Col md={6}>
                    <div className="workflow-steps-preview">
                      <h6>Steps:</h6>
                      {selectedWorkflow.steps && selectedWorkflow.steps.length > 0 ? (
                        <div className="steps-list">
                          {selectedWorkflow.steps.slice(0, 3).map((step, index) => (
                            <div key={index} className="step-preview">
                              {getStepTypeIcon(step.step_type)}
                              <span className="ms-2">{step.name}</span>
                            </div>
                          ))}
                          {selectedWorkflow.steps.length > 3 && (
                            <div className="text-muted small">
                              ... and {selectedWorkflow.steps.length - 3} more steps
                            </div>
                          )}
                        </div>
                      ) : (
                        <div className="text-muted">No steps defined</div>
                      )}
                    </div>
                  </Col>
                </Row>
                
                {selectedWorkflow.description && (
                  <>
                    <hr />
                    <h6>Description</h6>
                    <p>{selectedWorkflow.description}</p>
                  </>
                )}
              </CardBody>
            </Card>
          )}
          
          {selectedWorkflow && selectedWorkflow.trigger_type === 'manual' && (
            <Alert color="info" className="mt-3">
              <FontAwesomeIcon icon={faInfoCircle} className="me-2" />
              This workflow requires manual initiation. Once started, it will proceed according to the defined steps.
            </Alert>
          )}
          
          {selectedWorkflow && selectedWorkflow.status !== 'active' && (
            <Alert color="warning" className="mt-3">
              <FontAwesomeIcon icon={faExclamationTriangle} className="me-2" />
              Warning: This workflow is not currently active. Please contact an administrator if you need to start this workflow.
            </Alert>
          )}
        </Form>
      </ModalBody>
      <ModalFooter>
        <Button color="secondary" onClick={toggle} disabled={loading}>
          Cancel
        </Button>
        <Button color="primary" onClick={handleSubmit} disabled={loading || !selectedWorkflow}>
          <FontAwesomeIcon icon={faPlay} className="me-1" />
          {loading ? 'Starting...' : 'Start Workflow'}
        </Button>
      </ModalFooter>
    </Modal>
  );
};

export default StartWorkflowModal;