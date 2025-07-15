import React, { useState } from 'react';
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
  Table,
  Alert,
  Spinner
} from 'reactstrap';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import {
  faTag,
  faPlus,
  faEdit,
  faTrash
} from '@fortawesome/free-solid-svg-icons';
import { toast } from 'react-toastify';

import { workflowApi } from '../../services/workflowEngineApi';

interface WorkflowCategoryModalProps {
  isOpen: boolean;
  toggle: () => void;
  categories: string[];
  onSuccess: () => void;
}

const WorkflowCategoryModal: React.FC<WorkflowCategoryModalProps> = ({
  isOpen,
  toggle,
  categories,
  onSuccess
}) => {
  // State
  const [mode, setMode] = useState<'list' | 'create' | 'edit'>('list');
  const [loading, setLoading] = useState<boolean>(false);
  const [name, setName] = useState<string>('');
  const [description, setDescription] = useState<string>('');
  const [selectedCategory, setSelectedCategory] = useState<string>('');
  const [errors, setErrors] = useState<Record<string, string>>({});
  
  const resetForm = () => {
    setName('');
    setDescription('');
    setSelectedCategory('');
    setErrors({});
  };
  
  const validateForm = () => {
    const newErrors: Record<string, string> = {};
    
    if (!name.trim()) {
      newErrors.name = 'Category name is required';
    } else if (mode === 'create' && categories.includes(name)) {
      newErrors.name = 'A category with this name already exists';
    }
    
    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };
  
  const handleCreateCategory = () => {
    setMode('create');
    resetForm();
  };
  
  const handleEditCategory = (category: string) => {
    setMode('edit');
    setSelectedCategory(category);
    setName(category);
    setDescription(''); // In a real implementation, you would fetch the description
  };
  
  const handleDeleteCategory = async (category: string) => {
    if (window.confirm(`Are you sure you want to delete the category "${category}"?`)) {
      setLoading(true);
      
      try {
        // In a real implementation, you would call an API to delete the category
        // await workflowApi.deleteCategory(category);
        
        // Simulate API call
        await new Promise(resolve => setTimeout(resolve, 500));
        
        toast.success('Category deleted successfully');
        onSuccess();
      } catch (error) {
        console.error('Error deleting category:', error);
        toast.error('Failed to delete category');
      } finally {
        setLoading(false);
      }
    }
  };
  
  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    
    if (!validateForm()) {
      return;
    }
    
    setLoading(true);
    
    try {
      if (mode === 'create') {
        await workflowApi.createCategory({
          name,
          description: description || undefined
        });
        toast.success('Category created successfully');
      } else {
        // In a real implementation, you would call an API to update the category
        // await workflowApi.updateCategory(selectedCategory, { name, description });
        
        // Simulate API call
        await new Promise(resolve => setTimeout(resolve, 500));
        
        toast.success('Category updated successfully');
      }
      
      resetForm();
      setMode('list');
      onSuccess();
    } catch (error) {
      console.error(`Error ${mode === 'create' ? 'creating' : 'updating'} category:`, error);
      toast.error(`Failed to ${mode === 'create' ? 'create' : 'update'} category`);
    } finally {
      setLoading(false);
    }
  };
  
  const renderCategoryList = () => {
    if (categories.length === 0) {
      return (
        <Alert color="info">
          No categories found. Create your first category to organize your workflows.
        </Alert>
      );
    }
    
    return (
      <Table responsive striped>
        <thead>
          <tr>
            <th>Name</th>
            <th>Workflows</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          {categories.map(category => (
            <tr key={category}>
              <td>
                <FontAwesomeIcon icon={faTag} className="me-2 text-primary" />
                {category}
              </td>
              <td>
                {/* In a real implementation, you would show the number of workflows in this category */}
                {Math.floor(Math.random() * 10)}
              </td>
              <td>
                <Button color="link" className="p-0 me-2" title="Edit" onClick={() => handleEditCategory(category)}>
                  <FontAwesomeIcon icon={faEdit} />
                </Button>
                <Button color="link" className="p-0 text-danger" title="Delete" onClick={() => handleDeleteCategory(category)}>
                  <FontAwesomeIcon icon={faTrash} />
                </Button>
              </td>
            </tr>
          ))}
        </tbody>
      </Table>
    );
  };
  
  const renderCategoryForm = () => {
    return (
      <Form onSubmit={handleSubmit}>
        <FormGroup>
          <Label for="name">Category Name *</Label>
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
      </Form>
    );
  };
  
  return (
    <Modal isOpen={isOpen} toggle={toggle}>
      <ModalHeader toggle={toggle}>
        {mode === 'list' ? 'Workflow Categories' : 
         mode === 'create' ? 'Create Category' : 'Edit Category'}
      </ModalHeader>
      <ModalBody>
        {loading ? (
          <div className="text-center py-3">
            <Spinner color="primary" />
          </div>
        ) : (
          <>
            {mode === 'list' && (
              <div className="mb-3">
                <Button color="primary" onClick={handleCreateCategory}>
                  <FontAwesomeIcon icon={faPlus} className="me-1" /> Create Category
                </Button>
              </div>
            )}
            
            {mode === 'list' ? renderCategoryList() : renderCategoryForm()}
          </>
        )}
      </ModalBody>
      <ModalFooter>
        {mode !== 'list' && (
          <>
            <Button color="secondary" onClick={() => setMode('list')} disabled={loading}>
              Cancel
            </Button>
            <Button color="primary" onClick={handleSubmit} disabled={loading}>
              {mode === 'create' ? 'Create Category' : 'Update Category'}
            </Button>
          </>
        )}
        {mode === 'list' && (
          <Button color="secondary" onClick={toggle}>
            Close
          </Button>
        )}
      </ModalFooter>
    </Modal>
  );
};

export default WorkflowCategoryModal;