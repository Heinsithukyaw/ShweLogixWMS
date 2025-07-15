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
  Badge,
  Alert,
  Spinner,
  Input,
  InputGroup,
  InputGroupText,
  Form,
  FormGroup,
  Label,
  FormFeedback
} from 'reactstrap';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import {
  faFileAlt,
  faSearch,
  faPlus,
  faDownload,
  faEye,
  faCopy,
  faTrash,
  faProjectDiagram,
  faUsers,
  faThumbsUp,
  faCalendarAlt,
  faTags,
  faFilter
} from '@fortawesome/free-solid-svg-icons';
import { format, parseISO } from 'date-fns';
import { toast } from 'react-toastify';

import {
  WorkflowTemplate,
  workflowTemplateApi,
  workflowDefinitionApi
} from '../../services/workflowManagementApi';

interface WorkflowTemplateModalProps {
  isOpen: boolean;
  toggle: () => void;
  onSuccess: () => void;
}

const WorkflowTemplateModal: React.FC<WorkflowTemplateModalProps> = ({
  isOpen,
  toggle,
  onSuccess
}) => {
  // State
  const [activeTab, setActiveTab] = useState<string>('browse');
  const [templates, setTemplates] = useState<WorkflowTemplate[]>([]);
  const [popularTemplates, setPopularTemplates] = useState<WorkflowTemplate[]>([]);
  const [loading, setLoading] = useState<boolean>(false);
  const [searchTerm, setSearchTerm] = useState<string>('');
  const [filterCategory, setFilterCategory] = useState<string>('all');
  const [selectedTemplate, setSelectedTemplate] = useState<WorkflowTemplate | null>(null);
  
  // Create workflow from template form
  const [workflowName, setWorkflowName] = useState<string>('');
  const [workflowDescription, setWorkflowDescription] = useState<string>('');
  const [customizations, setCustomizations] = useState<any>({});
  const [errors, setErrors] = useState<Record<string, string>>({});
  
  // Template categories
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
  
  // Fetch data when modal opens
  useEffect(() => {
    if (isOpen) {
      fetchTemplates();
      fetchPopularTemplates();
    }
  }, [isOpen]);
  
  const fetchTemplates = async () => {
    setLoading(true);
    try {
      const response = await workflowTemplateApi.getWorkflowTemplates();
      setTemplates(response.data);
    } catch (error) {
      console.error('Error fetching templates:', error);
      toast.error('Failed to load workflow templates');
    } finally {
      setLoading(false);
    }
  };
  
  const fetchPopularTemplates = async () => {
    try {
      const response = await workflowTemplateApi.getPopularTemplates(5);
      setPopularTemplates(response.data);
    } catch (error) {
      console.error('Error fetching popular templates:', error);
    }
  };
  
  const handleTemplateAction = async (action: string, template: WorkflowTemplate) => {
    try {
      switch (action) {
        case 'view':
          setSelectedTemplate(template);
          setActiveTab('preview');
          break;
          
        case 'use':
          setSelectedTemplate(template);
          setWorkflowName(`${template.name} - ${new Date().toLocaleDateString()}`);
          setWorkflowDescription(template.description || '');
          setActiveTab('create');
          break;
          
        case 'download':
          // In a real implementation, this would download the template
          toast.success('Template downloaded successfully');
          break;
          
        case 'delete':
          if (window.confirm(`Are you sure you want to delete the template "${template.name}"?`)) {
            await workflowTemplateApi.deleteWorkflowTemplate(template.id);
            toast.success('Template deleted successfully');
            fetchTemplates();
          }
          break;
      }
    } catch (error) {
      console.error(`Error performing action ${action}:`, error);
      toast.error(`Failed to ${action} template`);
    }
  };
  
  const handleCreateWorkflow = async () => {
    if (!selectedTemplate) return;
    
    // Validate form
    const newErrors: Record<string, string> = {};
    if (!workflowName.trim()) {
      newErrors.workflowName = 'Workflow name is required';
    }
    
    setErrors(newErrors);
    if (Object.keys(newErrors).length > 0) return;
    
    setLoading(true);
    
    try {
      await workflowTemplateApi.createWorkflowFromTemplate(selectedTemplate.id, {
        name: workflowName,
        description: workflowDescription || undefined,
        customizations: Object.keys(customizations).length > 0 ? customizations : undefined
      });
      
      toast.success('Workflow created from template successfully');
      onSuccess();
    } catch (error) {
      console.error('Error creating workflow from template:', error);
      toast.error('Failed to create workflow from template');
    } finally {
      setLoading(false);
    }
  };
  
  const formatDate = (dateString: string) => {
    try {
      return format(parseISO(dateString), 'MMM d, yyyy');
    } catch (error) {
      return dateString;
    }
  };
  
  const filteredTemplates = templates.filter(template => {
    const matchesSearch = template.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
                         (template.description && template.description.toLowerCase().includes(searchTerm.toLowerCase()));
    const matchesCategory = filterCategory === 'all' || template.category === filterCategory;
    
    return matchesSearch && matchesCategory;
  });
  
  const renderBrowseTemplates = () => {
    if (loading) {
      return (
        <div className="text-center py-5">
          <Spinner color="primary" />
          <p className="mt-2">Loading templates...</p>
        </div>
      );
    }
    
    return (
      <div>
        {/* Search and Filter */}
        <Row className="mb-4">
          <Col md={8}>
            <InputGroup>
              <InputGroupText>
                <FontAwesomeIcon icon={faSearch} />
              </InputGroupText>
              <Input
                placeholder="Search templates..."
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
              />
            </InputGroup>
          </Col>
          <Col md={4}>
            <Input
              type="select"
              value={filterCategory}
              onChange={(e) => setFilterCategory(e.target.value)}
            >
              <option value="all">All Categories</option>
              {categories.map(category => (
                <option key={category} value={category}>{category}</option>
              ))}
            </Input>
          </Col>
        </Row>
        
        {/* Popular Templates */}
        {popularTemplates.length > 0 && (
          <div className="mb-4">
            <h5 className="mb-3">
              <FontAwesomeIcon icon={faThumbsUp} className="me-2 text-warning" />
              Popular Templates
            </h5>
            <Row>
              {popularTemplates.map(template => (
                <Col md={6} lg={4} key={template.id} className="mb-3">
                  <Card className="template-card h-100">
                    <CardBody>
                      <div className="d-flex justify-content-between align-items-start mb-2">
                        <h6 className="card-title">{template.name}</h6>
                        <Badge color="warning">Popular</Badge>
                      </div>
                      <p className="card-text small text-muted">
                        {template.description || 'No description available'}
                      </p>
                      <div className="template-meta">
                        <Badge color="info" className="me-2">{template.category}</Badge>
                        <small className="text-muted">
                          <FontAwesomeIcon icon={faUsers} className="me-1" />
                          {template.usage_count || 0} uses
                        </small>
                      </div>
                      <div className="template-actions mt-3">
                        <Button color="primary" size="sm" className="me-2" onClick={() => handleTemplateAction('use', template)}>
                          <FontAwesomeIcon icon={faPlus} className="me-1" /> Use Template
                        </Button>
                        <Button color="light" size="sm" onClick={() => handleTemplateAction('view', template)}>
                          <FontAwesomeIcon icon={faEye} />
                        </Button>
                      </div>
                    </CardBody>
                  </Card>
                </Col>
              ))}
            </Row>
          </div>
        )}
        
        {/* All Templates */}
        <h5 className="mb-3">
          <FontAwesomeIcon icon={faFileAlt} className="me-2" />
          All Templates ({filteredTemplates.length})
        </h5>
        
        {filteredTemplates.length === 0 ? (
          <Alert color="info">
            No templates found. {searchTerm || filterCategory !== 'all' ? 
              'Try adjusting your search or filter criteria.' : 
              'No workflow templates are available.'}
          </Alert>
        ) : (
          <Row>
            {filteredTemplates.map(template => (
              <Col md={6} lg={4} key={template.id} className="mb-3">
                <Card className="template-card h-100">
                  <CardBody>
                    <div className="d-flex justify-content-between align-items-start mb-2">
                      <h6 className="card-title">{template.name}</h6>
                      {template.is_public && <Badge color="success">Public</Badge>}
                    </div>
                    <p className="card-text small text-muted">
                      {template.description || 'No description available'}
                    </p>
                    <div className="template-meta">
                      <Badge color="info" className="me-2">{template.category}</Badge>
                      <small className="text-muted">
                        <FontAwesomeIcon icon={faCalendarAlt} className="me-1" />
                        {formatDate(template.created_at)}
                      </small>
                    </div>
                    <div className="template-actions mt-3">
                      <Button color="primary" size="sm" className="me-2" onClick={() => handleTemplateAction('use', template)}>
                        <FontAwesomeIcon icon={faPlus} className="me-1" /> Use Template
                      </Button>
                      <Button color="light" size="sm" className="me-1" onClick={() => handleTemplateAction('view', template)}>
                        <FontAwesomeIcon icon={faEye} />
                      </Button>
                      <Button color="light" size="sm" onClick={() => handleTemplateAction('download', template)}>
                        <FontAwesomeIcon icon={faDownload} />
                      </Button>
                    </div>
                  </CardBody>
                </Card>
              </Col>
            ))}
          </Row>
        )}
      </div>
    );
  };
  
  const renderTemplatePreview = () => {
    if (!selectedTemplate) {
      return (
        <Alert color="info">
          Select a template to view its details.
        </Alert>
      );
    }
    
    return (
      <div>
        <Card>
          <CardHeader>
            <h5 className="mb-0">
              <FontAwesomeIcon icon={faFileAlt} className="me-2" />
              {selectedTemplate.name}
            </h5>
          </CardHeader>
          <CardBody>
            <Row>
              <Col md={6}>
                <h6>Template Information</h6>
                <table className="table table-sm table-borderless">
                  <tbody>
                    <tr>
                      <th width="30%">Name:</th>
                      <td>{selectedTemplate.name}</td>
                    </tr>
                    <tr>
                      <th>Category:</th>
                      <td><Badge color="info">{selectedTemplate.category}</Badge></td>
                    </tr>
                    <tr>
                      <th>Visibility:</th>
                      <td>
                        <Badge color={selectedTemplate.is_public ? 'success' : 'secondary'}>
                          {selectedTemplate.is_public ? 'Public' : 'Private'}
                        </Badge>
                      </td>
                    </tr>
                    <tr>
                      <th>Created:</th>
                      <td>{formatDate(selectedTemplate.created_at)}</td>
                    </tr>
                    <tr>
                      <th>Usage Count:</th>
                      <td>{selectedTemplate.usage_count || 0}</td>
                    </tr>
                  </tbody>
                </table>
              </Col>
              <Col md={6}>
                <h6>Description</h6>
                <p>{selectedTemplate.description || 'No description provided'}</p>
              </Col>
            </Row>
            
            <hr />
            
            <h6>Template Structure</h6>
            <Alert color="info">
              <FontAwesomeIcon icon={faProjectDiagram} className="me-2" />
              Template structure and configuration details would be displayed here in a real implementation.
              This would include step definitions, configurations, and any template-specific settings.
            </Alert>
            
            <div className="mt-3">
              <Button color="primary" className="me-2" onClick={() => handleTemplateAction('use', selectedTemplate)}>
                <FontAwesomeIcon icon={faPlus} className="me-1" /> Use This Template
              </Button>
              <Button color="light" onClick={() => handleTemplateAction('download', selectedTemplate)}>
                <FontAwesomeIcon icon={faDownload} className="me-1" /> Download
              </Button>
            </div>
          </CardBody>
        </Card>
      </div>
    );
  };
  
  const renderCreateFromTemplate = () => {
    if (!selectedTemplate) {
      return (
        <Alert color="warning">
          Please select a template first.
        </Alert>
      );
    }
    
    return (
      <div>
        <Alert color="info" className="mb-4">
          <FontAwesomeIcon icon={faFileAlt} className="me-2" />
          Creating workflow from template: <strong>{selectedTemplate.name}</strong>
        </Alert>
        
        <Form>
          <Row>
            <Col md={6}>
              <FormGroup>
                <Label for="workflowName">Workflow Name *</Label>
                <Input
                  id="workflowName"
                  value={workflowName}
                  onChange={(e) => setWorkflowName(e.target.value)}
                  invalid={!!errors.workflowName}
                />
                <FormFeedback>{errors.workflowName}</FormFeedback>
              </FormGroup>
              
              <FormGroup>
                <Label for="workflowDescription">Description</Label>
                <Input
                  type="textarea"
                  id="workflowDescription"
                  rows={4}
                  value={workflowDescription}
                  onChange={(e) => setWorkflowDescription(e.target.value)}
                />
              </FormGroup>
            </Col>
            
            <Col md={6}>
              <Card>
                <CardHeader>
                  <h6 className="mb-0">Template Information</h6>
                </CardHeader>
                <CardBody>
                  <table className="table table-sm table-borderless">
                    <tbody>
                      <tr>
                        <th width="40%">Template:</th>
                        <td>{selectedTemplate.name}</td>
                      </tr>
                      <tr>
                        <th>Category:</th>
                        <td><Badge color="info">{selectedTemplate.category}</Badge></td>
                      </tr>
                      <tr>
                        <th>Usage Count:</th>
                        <td>{selectedTemplate.usage_count || 0}</td>
                      </tr>
                    </tbody>
                  </table>
                </CardBody>
              </Card>
            </Col>
          </Row>
          
          <Card className="mt-3">
            <CardHeader>
              <h6 className="mb-0">Customizations (Optional)</h6>
            </CardHeader>
            <CardBody>
              <Alert color="info">
                <FontAwesomeIcon icon={faTags} className="me-2" />
                Template customization options would be displayed here based on the selected template.
                This could include configurable parameters, step modifications, or other template-specific settings.
              </Alert>
              
              <FormGroup>
                <Label for="customNotes">Custom Notes</Label>
                <Input
                  type="textarea"
                  id="customNotes"
                  rows={3}
                  placeholder="Add any custom notes or modifications for this workflow instance..."
                  value={customizations.notes || ''}
                  onChange={(e) => setCustomizations(prev => ({ ...prev, notes: e.target.value }))}
                />
              </FormGroup>
            </CardBody>
          </Card>
        </Form>
      </div>
    );
  };
  
  return (
    <Modal isOpen={isOpen} toggle={toggle} size="xl">
      <ModalHeader toggle={toggle}>
        <FontAwesomeIcon icon={faFileAlt} className="me-2" />
        Workflow Templates
      </ModalHeader>
      <ModalBody>
        <Nav tabs className="mb-3">
          <NavItem>
            <NavLink
              className={activeTab === 'browse' ? 'active' : ''}
              onClick={() => setActiveTab('browse')}
            >
              <FontAwesomeIcon icon={faSearch} className="me-1" /> Browse Templates
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
          <NavItem>
            <NavLink
              className={activeTab === 'create' ? 'active' : ''}
              onClick={() => setActiveTab('create')}
            >
              <FontAwesomeIcon icon={faPlus} className="me-1" /> Create from Template
              {selectedTemplate && (
                <Badge color="primary" className="ms-1">1</Badge>
              )}
            </NavLink>
          </NavItem>
        </Nav>
        
        <TabContent activeTab={activeTab}>
          <TabPane tabId="browse">
            {renderBrowseTemplates()}
          </TabPane>
          <TabPane tabId="preview">
            {renderTemplatePreview()}
          </TabPane>
          <TabPane tabId="create">
            {renderCreateFromTemplate()}
          </TabPane>
        </TabContent>
      </ModalBody>
      <ModalFooter>
        {activeTab === 'create' && selectedTemplate ? (
          <>
            <Button color="secondary" onClick={() => setActiveTab('browse')} disabled={loading}>
              Back to Templates
            </Button>
            <Button color="primary" onClick={handleCreateWorkflow} disabled={loading}>
              <FontAwesomeIcon icon={faProjectDiagram} className="me-1" />
              {loading ? 'Creating...' : 'Create Workflow'}
            </Button>
          </>
        ) : (
          <Button color="secondary" onClick={toggle}>
            Close
          </Button>
        )}
      </ModalFooter>
    </Modal>
  );
};

export default WorkflowTemplateModal;