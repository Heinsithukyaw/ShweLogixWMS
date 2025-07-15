import React, { useState, useEffect, useRef } from 'react';
import {
  Modal,
  ModalHeader,
  ModalBody,
  ModalFooter,
  Form,
  FormGroup,
  Label,
  Input,
  Button,
  Row,
  Col,
  Progress,
  Alert,
  FormFeedback,
  Badge
} from 'reactstrap';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import {
  faUpload,
  faFile,
  faTag,
  faPlus,
  faTimes
} from '@fortawesome/free-solid-svg-icons';
import { toast } from 'react-toastify';
import Select from 'react-select';

import { Document, documentApi } from '../../services/documentManagementApi';

interface UploadDocumentModalProps {
  isOpen: boolean;
  toggle: () => void;
  document?: Document | null;
  categories: any[];
  tags: string[];
  onSuccess: () => void;
}

const UploadDocumentModal: React.FC<UploadDocumentModalProps> = ({
  isOpen,
  toggle,
  document,
  categories,
  tags,
  onSuccess
}) => {
  // Form state
  const [title, setTitle] = useState<string>('');
  const [description, setDescription] = useState<string>('');
  const [categoryId, setCategoryId] = useState<number | null>(null);
  const [selectedTags, setSelectedTags] = useState<string[]>([]);
  const [accessLevel, setAccessLevel] = useState<string>('public');
  const [expirationDate, setExpirationDate] = useState<string>('');
  const [file, setFile] = useState<File | null>(null);
  const [newTag, setNewTag] = useState<string>('');
  
  // Upload state
  const [uploading, setUploading] = useState<boolean>(false);
  const [uploadProgress, setUploadProgress] = useState<number>(0);
  const [errors, setErrors] = useState<Record<string, string>>({});
  
  // Refs
  const fileInputRef = useRef<HTMLInputElement>(null);
  
  // Reset form when modal opens/closes or document changes
  useEffect(() => {
    if (isOpen) {
      if (document) {
        // Edit mode
        setTitle(document.title || '');
        setDescription(document.description || '');
        setCategoryId(document.category_id || null);
        setSelectedTags(document.tags || []);
        setAccessLevel(document.access_level || 'public');
        setExpirationDate(document.expiration_date || '');
        setFile(null);
      } else {
        // Create mode
        resetForm();
      }
    }
  }, [isOpen, document]);
  
  const resetForm = () => {
    setTitle('');
    setDescription('');
    setCategoryId(null);
    setSelectedTags([]);
    setAccessLevel('public');
    setExpirationDate('');
    setFile(null);
    setNewTag('');
    setErrors({});
    setUploadProgress(0);
    
    // Reset file input
    if (fileInputRef.current) {
      fileInputRef.current.value = '';
    }
  };
  
  const validateForm = () => {
    const newErrors: Record<string, string> = {};
    
    if (!title.trim()) {
      newErrors.title = 'Title is required';
    }
    
    if (!categoryId) {
      newErrors.categoryId = 'Category is required';
    }
    
    if (!document && !file) {
      newErrors.file = 'Please select a file to upload';
    }
    
    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };
  
  const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    if (e.target.files && e.target.files.length > 0) {
      const selectedFile = e.target.files[0];
      setFile(selectedFile);
      
      // Auto-fill title if empty
      if (!title) {
        // Remove extension from filename
        const fileName = selectedFile.name.replace(/\.[^/.]+$/, '');
        setTitle(fileName);
      }
    }
  };
  
  const handleAddTag = () => {
    if (newTag && !selectedTags.includes(newTag)) {
      setSelectedTags([...selectedTags, newTag]);
      setNewTag('');
    }
  };
  
  const handleRemoveTag = (tag: string) => {
    setSelectedTags(selectedTags.filter(t => t !== tag));
  };
  
  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    
    if (!validateForm()) {
      return;
    }
    
    setUploading(true);
    setUploadProgress(0);
    
    try {
      const formData = new FormData();
      
      // Add document metadata
      formData.append('title', title);
      formData.append('description', description || '');
      formData.append('category_id', categoryId?.toString() || '');
      formData.append('access_level', accessLevel);
      
      if (expirationDate) {
        formData.append('expiration_date', expirationDate);
      }
      
      if (selectedTags.length > 0) {
        selectedTags.forEach((tag, index) => {
          formData.append(`tags[${index}]`, tag);
        });
      }
      
      // Add file if provided (for new documents or updates)
      if (file) {
        formData.append('file', file);
      }
      
      // Create or update document
      if (document) {
        // Update existing document
        await documentApi.updateDocument(document.id, formData);
        toast.success('Document updated successfully');
      } else {
        // Create new document
        await documentApi.createDocument(formData);
        toast.success('Document uploaded successfully');
      }
      
      resetForm();
      onSuccess();
    } catch (error) {
      console.error('Error uploading document:', error);
      toast.error('Failed to upload document');
    } finally {
      setUploading(false);
    }
  };
  
  // Simulate upload progress
  useEffect(() => {
    let interval: NodeJS.Timeout;
    
    if (uploading && uploadProgress < 100) {
      interval = setInterval(() => {
        setUploadProgress(prev => {
          const increment = Math.floor(Math.random() * 10) + 1;
          const newProgress = Math.min(prev + increment, 99);
          return newProgress;
        });
      }, 300);
    }
    
    return () => {
      if (interval) clearInterval(interval);
    };
  }, [uploading, uploadProgress]);
  
  return (
    <Modal isOpen={isOpen} toggle={toggle} size="lg">
      <ModalHeader toggle={toggle}>
        {document ? 'Edit Document' : 'Upload New Document'}
      </ModalHeader>
      <ModalBody>
        <Form onSubmit={handleSubmit}>
          <Row>
            <Col md={6}>
              <FormGroup>
                <Label for="title">Title *</Label>
                <Input
                  id="title"
                  value={title}
                  onChange={(e) => setTitle(e.target.value)}
                  invalid={!!errors.title}
                />
                <FormFeedback>{errors.title}</FormFeedback>
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
                  options={categories.map(cat => ({ value: cat.id, label: cat.name }))}
                  value={categoryId ? { value: categoryId, label: categories.find(c => c.id === categoryId)?.name } : null}
                  onChange={(option: any) => setCategoryId(option ? option.value : null)}
                  placeholder="Select a category"
                  className={errors.categoryId ? 'is-invalid' : ''}
                />
                {errors.categoryId && (
                  <div className="invalid-feedback d-block">{errors.categoryId}</div>
                )}
              </FormGroup>
            </Col>
            
            <Col md={6}>
              <FormGroup>
                <Label for="file">
                  {document ? 'Replace File (optional)' : 'File *'}
                </Label>
                <div className="custom-file-upload">
                  <Input
                    type="file"
                    id="file"
                    innerRef={fileInputRef}
                    onChange={handleFileChange}
                    invalid={!!errors.file}
                    hidden
                  />
                  <div 
                    className={`file-upload-box ${errors.file ? 'is-invalid' : ''}`}
                    onClick={() => fileInputRef.current?.click()}
                  >
                    <FontAwesomeIcon icon={faUpload} size="2x" className="mb-2" />
                    <div>
                      {file ? (
                        <span>{file.name} ({(file.size / 1024).toFixed(2)} KB)</span>
                      ) : (
                        <span>{document ? 'Click to replace file' : 'Click to select file'}</span>
                      )}
                    </div>
                  </div>
                  <FormFeedback>{errors.file}</FormFeedback>
                </div>
              </FormGroup>
              
              <FormGroup>
                <Label for="accessLevel">Access Level</Label>
                <Input
                  type="select"
                  id="accessLevel"
                  value={accessLevel}
                  onChange={(e) => setAccessLevel(e.target.value)}
                >
                  <option value="public">Public</option>
                  <option value="restricted">Restricted</option>
                  <option value="private">Private</option>
                </Input>
              </FormGroup>
              
              <FormGroup>
                <Label for="expirationDate">Expiration Date (Optional)</Label>
                <Input
                  type="date"
                  id="expirationDate"
                  value={expirationDate}
                  onChange={(e) => setExpirationDate(e.target.value)}
                  min={new Date().toISOString().split('T')[0]}
                />
              </FormGroup>
            </Col>
          </Row>
          
          <FormGroup>
            <Label>Tags</Label>
            <div className="d-flex mb-2">
              <Input
                value={newTag}
                onChange={(e) => setNewTag(e.target.value)}
                placeholder="Add a tag"
                className="me-2"
                onKeyPress={(e) => {
                  if (e.key === 'Enter') {
                    e.preventDefault();
                    handleAddTag();
                  }
                }}
              />
              <Button color="secondary" onClick={handleAddTag}>
                <FontAwesomeIcon icon={faPlus} />
              </Button>
            </div>
            
            <div className="selected-tags">
              {selectedTags.map(tag => (
                <Badge key={tag} color="primary" className="me-2 mb-2 p-2">
                  <FontAwesomeIcon icon={faTag} className="me-1" /> {tag}
                  <Button
                    close
                    size="sm"
                    onClick={() => handleRemoveTag(tag)}
                    className="ms-2"
                  />
                </Badge>
              ))}
              
              {selectedTags.length === 0 && (
                <div className="text-muted">No tags selected</div>
              )}
            </div>
            
            {tags.length > 0 && (
              <div className="mt-2">
                <small className="text-muted">Suggested tags:</small>
                <div className="suggested-tags mt-1">
                  {tags
                    .filter(tag => !selectedTags.includes(tag))
                    .slice(0, 10)
                    .map(tag => (
                      <Badge
                        key={tag}
                        color="light"
                        className="me-2 mb-2 p-2 cursor-pointer"
                        onClick={() => setSelectedTags([...selectedTags, tag])}
                      >
                        <FontAwesomeIcon icon={faTag} className="me-1" /> {tag}
                      </Badge>
                    ))}
                </div>
              </div>
            )}
          </FormGroup>
          
          {uploading && (
            <div className="mt-3">
              <Label>Upload Progress</Label>
              <Progress value={uploadProgress} className="mb-2">
                {uploadProgress}%
              </Progress>
            </div>
          )}
        </Form>
      </ModalBody>
      <ModalFooter>
        <Button color="secondary" onClick={toggle} disabled={uploading}>
          Cancel
        </Button>
        <Button color="primary" onClick={handleSubmit} disabled={uploading}>
          {uploading ? 'Uploading...' : document ? 'Update Document' : 'Upload Document'}
        </Button>
      </ModalFooter>
    </Modal>
  );
};

export default UploadDocumentModal;