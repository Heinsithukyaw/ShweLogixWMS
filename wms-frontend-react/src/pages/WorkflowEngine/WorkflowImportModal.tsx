import React, { useState, useRef } from 'react';
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
  Progress
} from 'reactstrap';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import {
  faUpload,
  faFile,
  faInfoCircle,
  faExclamationTriangle,
  faCheck
} from '@fortawesome/free-solid-svg-icons';
import { toast } from 'react-toastify';

import { workflowApi } from '../../services/workflowEngineApi';

interface WorkflowImportModalProps {
  isOpen: boolean;
  toggle: () => void;
  onSuccess: () => void;
}

const WorkflowImportModal: React.FC<WorkflowImportModalProps> = ({
  isOpen,
  toggle,
  onSuccess
}) => {
  // State
  const [file, setFile] = useState<File | null>(null);
  const [importing, setImporting] = useState<boolean>(false);
  const [importProgress, setImportProgress] = useState<number>(0);
  const [errors, setErrors] = useState<Record<string, string>>({});
  const [importSuccess, setImportSuccess] = useState<boolean>(false);
  const [importedWorkflowName, setImportedWorkflowName] = useState<string>('');
  
  // Refs
  const fileInputRef = useRef<HTMLInputElement>(null);
  
  // Reset form when modal opens/closes
  const resetForm = () => {
    setFile(null);
    setErrors({});
    setImporting(false);
    setImportProgress(0);
    setImportSuccess(false);
    setImportedWorkflowName('');
    
    // Reset file input
    if (fileInputRef.current) {
      fileInputRef.current.value = '';
    }
  };
  
  const validateForm = () => {
    const newErrors: Record<string, string> = {};
    
    if (!file) {
      newErrors.file = 'Please select a workflow file to import';
    } else if (file.type !== 'application/json' && !file.name.endsWith('.json')) {
      newErrors.file = 'Only JSON files are supported';
    }
    
    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };
  
  const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    if (e.target.files && e.target.files.length > 0) {
      setFile(e.target.files[0]);
    }
  };
  
  const handleImportWorkflow = async () => {
    if (!validateForm()) {
      return;
    }
    
    setImporting(true);
    setImportProgress(0);
    
    try {
      // Simulate progress
      const progressInterval = setInterval(() => {
        setImportProgress(prev => {
          const newProgress = Math.min(prev + 10, 90);
          return newProgress;
        });
      }, 300);
      
      const formData = new FormData();
      if (file) {
        formData.append('file', file);
      }
      
      const response = await workflowApi.importWorkflow(formData);
      
      clearInterval(progressInterval);
      setImportProgress(100);
      
      setImportSuccess(true);
      setImportedWorkflowName(response.data.name || 'Workflow');
      toast.success('Workflow imported successfully');
    } catch (error) {
      console.error('Error importing workflow:', error);
      toast.error('Failed to import workflow');
    } finally {
      setImporting(false);
    }
  };
  
  const handleClose = () => {
    if (importSuccess) {
      onSuccess();
    }
    resetForm();
    toggle();
  };
  
  return (
    <Modal isOpen={isOpen} toggle={toggle}>
      <ModalHeader toggle={toggle}>
        <FontAwesomeIcon icon={faUpload} className="me-2" />
        Import Workflow
      </ModalHeader>
      <ModalBody>
        {importSuccess ? (
          <div className="import-success">
            <div className="text-center mb-4">
              <div className="success-icon">
                <FontAwesomeIcon icon={faCheck} size="3x" className="text-success" />
              </div>
              <h4 className="mt-3">Workflow Imported Successfully</h4>
              <p className="text-muted">
                The workflow <strong>{importedWorkflowName}</strong> has been imported.
              </p>
            </div>
            
            <Alert color="info">
              <FontAwesomeIcon icon={faInfoCircle} className="me-2" />
              You can now find the imported workflow in your workflow list.
            </Alert>
          </div>
        ) : (
          <Form>
            <Alert color="info" className="mb-4">
              <FontAwesomeIcon icon={faInfoCircle} className="me-2" />
              Import a workflow from a JSON file. The file should be in the format exported by the Workflow Engine.
            </Alert>
            
            <FormGroup>
              <Label for="file">Workflow File (JSON)</Label>
              <div className="custom-file-upload">
                <Input
                  type="file"
                  id="file"
                  innerRef={fileInputRef}
                  onChange={handleFileChange}
                  accept=".json,application/json"
                  invalid={!!errors.file}
                  hidden
                />
                <div 
                  className={`file-upload-box ${errors.file ? 'is-invalid' : ''}`}
                  onClick={() => fileInputRef.current?.click()}
                >
                  <FontAwesomeIcon icon={faFile} size="2x" className="mb-2" />
                  <div>
                    {file ? (
                      <span>{file.name} ({(file.size / 1024).toFixed(2)} KB)</span>
                    ) : (
                      <span>Click to select workflow file</span>
                    )}
                  </div>
                </div>
                <FormFeedback>{errors.file}</FormFeedback>
              </div>
            </FormGroup>
            
            {importing && (
              <div className="mt-3">
                <Label>Import Progress</Label>
                <Progress value={importProgress} className="mb-2">
                  {importProgress}%
                </Progress>
              </div>
            )}
            
            <Alert color="warning" className="mt-4">
              <FontAwesomeIcon icon={faExclamationTriangle} className="me-2" />
              <strong>Important:</strong> If a workflow with the same name already exists, it will be created as a new version.
            </Alert>
          </Form>
        )}
      </ModalBody>
      <ModalFooter>
        {importSuccess ? (
          <Button color="primary" onClick={handleClose}>
            Close
          </Button>
        ) : (
          <>
            <Button color="secondary" onClick={toggle} disabled={importing}>
              Cancel
            </Button>
            <Button color="primary" onClick={handleImportWorkflow} disabled={importing || !file}>
              {importing ? (
                <>
                  <Spinner size="sm" className="me-2" /> Importing...
                </>
              ) : (
                <>
                  <FontAwesomeIcon icon={faUpload} className="me-2" /> Import Workflow
                </>
              )}
            </Button>
          </>
        )}
      </ModalFooter>
    </Modal>
  );
};

export default WorkflowImportModal;