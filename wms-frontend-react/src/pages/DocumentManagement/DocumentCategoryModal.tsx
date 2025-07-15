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
  Table,
  Alert,
  Spinner
} from 'reactstrap';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import {
  faFolder,
  faFolderPlus,
  faEdit,
  faTrash,
  faArrowUp
} from '@fortawesome/free-solid-svg-icons';
import { toast } from 'react-toastify';
import Select from 'react-select';

import { DocumentCategory, documentCategoryApi } from '../../services/documentManagementApi';

interface DocumentCategoryModalProps {
  isOpen: boolean;
  toggle: () => void;
  categories: any[];
  onSuccess: () => void;
}

const DocumentCategoryModal: React.FC<DocumentCategoryModalProps> = ({
  isOpen,
  toggle,
  categories,
  onSuccess
}) => {
  // State
  const [mode, setMode] = useState<'list' | 'create' | 'edit'>('list');
  const [loading, setLoading] = useState<boolean>(false);
  const [selectedCategory, setSelectedCategory] = useState<DocumentCategory | null>(null);
  
  // Form state
  const [name, setName] = useState<string>('');
  const [description, setDescription] = useState<string>('');
  const [parentId, setParentId] = useState<number | null>(null);
  const [errors, setErrors] = useState<Record<string, string>>({});
  
  // Reset form when modal opens
  useEffect(() => {
    if (isOpen) {
      resetForm();
    }
  }, [isOpen]);
  
  const resetForm = () => {
    setMode('list');
    setName('');
    setDescription('');
    setParentId(null);
    setSelectedCategory(null);
    setErrors({});
  };
  
  const validateForm = () => {
    const newErrors: Record<string, string> = {};
    
    if (!name.trim()) {
      newErrors.name = 'Category name is required';
    }
    
    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };
  
  const handleCreateCategory = () => {
    setMode('create');
    setSelectedCategory(null);
    setName('');
    setDescription('');
    setParentId(null);
  };
  
  const handleEditCategory = (category: DocumentCategory) => {
    setMode('edit');
    setSelectedCategory(category);
    setName(category.name);
    setDescription(category.description || '');
    setParentId(category.parent_id || null);
  };
  
  const handleDeleteCategory = async (category: DocumentCategory) => {
    if (window.confirm(`Are you sure you want to delete the category "${category.name}"? This will also delete all documents in this category.`)) {
      setLoading(true);
      
      try {
        await documentCategoryApi.deleteCategory(category.id);
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
      const data = {
        name,
        description: description || undefined,
        parent_id: parentId || undefined
      };
      
      if (mode === 'create') {
        await documentCategoryApi.createCategory(data);
        toast.success('Category created successfully');
      } else if (mode === 'edit' && selectedCategory) {
        await documentCategoryApi.updateCategory(selectedCategory.id, data);
        toast.success('Category updated successfully');
      }
      
      resetForm();
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
          No categories found. Create your first category to get started.
        </Alert>
      );
    }
    
    return (
      <Table responsive striped>
        <thead>
          <tr>
            <th>Name</th>
            <th>Description</th>
            <th>Parent</th>
            <th>Documents</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          {categories.map(category => (
            <tr key={category.id}>
              <td>
                <FontAwesomeIcon icon={faFolder} className="me-2 text-warning" />
                {category.name}
              </td>
              <td>{category.description || '-'}</td>
              <td>
                {category.parent_id ? (
                  categories.find(c => c.id === category.parent_id)?.name || '-'
                ) : '-'}
              </td>
              <td>{category.document_count || 0}</td>
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
        
        <FormGroup>
          <Label for="parentId">Parent Category</Label>
          <Select
            id="parentId"
            options={categories
              .filter(cat => cat.id !== selectedCategory?.id) // Prevent selecting self as parent
              .map(cat => ({ value: cat.id, label: cat.name }))}
            value={parentId ? { value: parentId, label: categories.find(c => c.id === parentId)?.name } : null}
            onChange={(option: any) => setParentId(option ? option.value : null)}
            placeholder="Select a parent category (optional)"
            isClearable
          />
        </FormGroup>
      </Form>
    );
  };
  
  return (
    <Modal isOpen={isOpen} toggle={toggle}>
      <ModalHeader toggle={toggle}>
        {mode === 'list' ? 'Document Categories' : 
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
                  <FontAwesomeIcon icon={faFolderPlus} className="me-1" /> Create Category
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
              <FontAwesomeIcon icon={faArrowUp} className="me-1" /> Back to List
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

export default DocumentCategoryModal;