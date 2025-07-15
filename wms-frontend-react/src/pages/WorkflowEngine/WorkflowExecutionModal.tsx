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
  FormFeedback,
  Spinner,
  Alert,
  Card,
  CardBody,
  Row,
  Col
} from 'reactstrap';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import {
  faPlay,
  faInfoCircle,
  faExclamationTriangle,
  faCheck,
  faCodeBranch
} from '@fortawesome/free-solid-svg-icons';
import { toast } from 'react-toastify';
import JSONInput from 'react-json-editor-ajrm';
import locale from 'react-json-editor-ajrm/locale/en';

import { Workflow, workflowExecutionApi } from '../../services/workflowEngineApi';

interface WorkflowExecutionModalProps {
  isOpen: boolean;
  toggle: () => void;
  workflow: Workflow | null;
  onSuccess: () => void;
}

const WorkflowExecutionModal: React.FC<WorkflowExecutionModalProps> = ({
  isOpen,
  toggle,
  workflow,
  onSuccess
}) => {
  // State
  const [executing, setExecuting] = useState<boolean>(false);
  const [inputData, setInputData] = useState<any>({});
  const [errors, setErrors] = useState<Record<string, string>>({});
  const [executionStarted, setExecutionStarted] = useState<boolean>(false);
  const [executionId, setExecutionId] = useState<number | null>(null);
  
  // Reset form when modal opens/closes or workflow changes
  useEffect(() => {
    if (isOpen) {
      resetForm();
    }
  }, [isOpen, workflow]);
  
  const resetForm = () => {
    setInputData({});
    setErrors({});
    setExecuting(false);
    setExecutionStarted(false);
    setExecutionId(null);
  };
  
  const validateForm = () => {
    const newErrors: Record<string, string> = {};
    
    // Add validation rules based on workflow requirements
    // For example, check if required fields are present in inputData
    
    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };
  
  const handleInputDataChange = (data: any) => {
    try {
      if (data.jsObject) {
        setInputData(data.jsObject);
      }
    } catch (error) {
      console.error('Error parsing JSON input:', error);
    }
  };
  
  const handleExecuteWorkflow = async () => {
    if (!workflow) return;
    
    if (!validateForm()) {
      return;
    }
    
    setExecuting(true);
    
    try {
      const response = await workflowExecutionApi.startExecution(workflow.id, {
        input_data: inputData
      });
      
      setExecutionId(response.data.id);
      setExecutionStarted(true);
      toast.success('Workflow execution started successfully');
    } catch (error) {
      console.error('Error executing workflow:', error);
      toast.error('Failed to execute workflow');
    } finally {
      setExecuting(false);
    }
  };
  
  const handleClose = () => {
    if (executionStarted) {
      onSuccess();
    }
    toggle();
  };
  
  if (!workflow) {
    return null;
  }
  
  return (
    <Modal isOpen={isOpen} toggle={toggle} size="lg">
      <ModalHeader toggle={toggle}>
        <FontAwesomeIcon icon={faPlay} className="me-2 text-success" />
        Execute Workflow: {workflow.name}
      </ModalHeader>
      <ModalBody>
        {executionStarted ? (
          <div className="execution-success">
            <div className="text-center mb-4">
              <div className="success-icon">
                <FontAwesomeIcon icon={faCheck} size="3x" className="text-success" />
              </div>
              <h4 className="mt-3">Workflow Execution Started</h4>
              <p className="text-muted">
                Execution ID: {executionId}
              </p>
            </div>
            
            <Alert color="info">
              <FontAwesomeIcon icon={faInfoCircle} className="me-2" />
              Your workflow is now running. You can track its progress in the Executions tab.
            </Alert>
            
            <Row className="mt-4">
              <Col md={6}>
                <Card>
                  <CardBody>
                    <h5 className="mb-3">Workflow Details</h5>
                    <p><strong>Name:</strong> {workflow.name}</p>
                    <p><strong>Category:</strong> {workflow.category}</p>
                    <p><strong>Trigger Type:</strong> {workflow.trigger_type}</p>
                  </CardBody>
                </Card>
              </Col>
              <Col md={6}>
                <Card>
                  <CardBody>
                    <h5 className="mb-3">Input Data</h5>
                    <pre className="input-data-preview">
                      {JSON.stringify(inputData, null, 2) || 'No input data provided'}
                    </pre>
                  </CardBody>
                </Card>
              </Col>
            </Row>
          </div>
        ) : (
          <Form>
            <Alert color="info" className="mb-4">
              <FontAwesomeIcon icon={faInfoCircle} className="me-2" />
              You are about to execute the workflow <strong>{workflow.name}</strong>. 
              {workflow.trigger_type === 'manual' 
                ? ' This is a manual workflow that will start immediately.'
                : workflow.trigger_type === 'event'
                ? ' This is an event-triggered workflow that will be executed as if the event occurred.'
                : ' This is a scheduled workflow that will be executed immediately, regardless of its schedule.'}
            </Alert>
            
            <FormGroup>
              <Label for="inputData">Input Data (Optional JSON)</Label>
              <div className="json-editor-container">
                <JSONInput
                  id="inputData"
                  placeholder={inputData}
                  locale={locale}
                  height="300px"
                  width="100%"
                  onChange={handleInputDataChange}
                />
              </div>
              {errors.inputData && (
                <div className="invalid-feedback d-block">{errors.inputData}</div>
              )}
              <small className="text-muted">
                Provide any input data required by the workflow in JSON format.
              </small>
            </FormGroup>
            
            {workflow.trigger_type === 'event' && (
              <Alert color="warning">
                <FontAwesomeIcon icon={faExclamationTriangle} className="me-2" />
                This workflow is normally triggered by the <strong>{workflow.trigger_config?.event_type || 'unknown'}</strong> event. 
                Make sure your input data matches what the workflow expects from this event.
              </Alert>
            )}
          </Form>
        )}
      </ModalBody>
      <ModalFooter>
        {executionStarted ? (
          <Button color="primary" onClick={handleClose}>
            Close
          </Button>
        ) : (
          <>
            <Button color="secondary" onClick={toggle} disabled={executing}>
              Cancel
            </Button>
            <Button color="success" onClick={handleExecuteWorkflow} disabled={executing}>
              {executing ? (
                <>
                  <Spinner size="sm" className="me-2" /> Executing...
                </>
              ) : (
                <>
                  <FontAwesomeIcon icon={faPlay} className="me-2" /> Execute Workflow
                </>
              )}
            </Button>
          </>
        )}
      </ModalFooter>
    </Modal>
  );
};

export default WorkflowExecutionModal;