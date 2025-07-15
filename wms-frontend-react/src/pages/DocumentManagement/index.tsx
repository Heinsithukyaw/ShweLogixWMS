import React, { useState, useEffect } from 'react';
import '../../styles/DocumentManagement.css';
import {
  Container,
  Row,
  Col,
  Card,
  CardHeader,
  CardBody,
  Button,
  Input,
  InputGroup,
  InputGroupText,
  Nav,
  NavItem,
  NavLink,
  TabContent,
  TabPane,
  Badge,
  Spinner,
  Dropdown,
  DropdownToggle,
  DropdownMenu,
  DropdownItem,
  Alert
} from 'reactstrap';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import {
  faSearch,
  faFilter,
  faPlus,
  faFile,
  faFileAlt,
  faFileImage,
  faFilePdf,
  faFileExcel,
  faFileWord,
  faFileArchive,
  faFileCode,
  faFolder,
  faStar,
  faStarHalfAlt,
  faEllipsisV,
  faDownload,
  faEdit,
  faTrash,
  faArchive,
  faShare,
  faHistory,
  faLock,
  faCalendarAlt,
  faTag,
  faSort,
  faSortUp,
  faSortDown,
  faEye
} from '@fortawesome/free-solid-svg-icons';
import { format, parseISO } from 'date-fns';
import { toast } from 'react-toastify';

import { Document, documentApi, documentCategoryApi, documentTagApi } from '../../services/documentManagementApi';
import UploadDocumentModal from './UploadDocumentModal';
import DocumentDetailsModal from './DocumentDetailsModal';
import DocumentCategoryModal from './DocumentCategoryModal';
import DocumentPermissionsModal from './DocumentPermissionsModal';

const DocumentManagement: React.FC = () => {
  // State
  const [activeTab, setActiveTab] = useState<string>('all');
  const [documents, setDocuments] = useState<Document[]>([]);
  const [filteredDocuments, setFilteredDocuments] = useState<Document[]>([]);
  const [categories, setCategories] = useState<any[]>([]);
  const [tags, setTags] = useState<string[]>([]);
  const [loading, setLoading] = useState<boolean>(true);
  const [searchTerm, setSearchTerm] = useState<string>('');
  const [selectedCategory, setSelectedCategory] = useState<number | null>(null);
  const [selectedTags, setSelectedTags] = useState<string[]>([]);
  const [sortField, setSortField] = useState<string>('updated_at');
  const [sortDirection, setSortDirection] = useState<'asc' | 'desc'>('desc');
  const [filterDropdownOpen, setFilterDropdownOpen] = useState<boolean>(false);
  const [sortDropdownOpen, setSortDropdownOpen] = useState<boolean>(false);
  
  // Modals
  const [uploadModalOpen, setUploadModalOpen] = useState<boolean>(false);
  const [detailsModalOpen, setDetailsModalOpen] = useState<boolean>(false);
  const [categoryModalOpen, setCategoryModalOpen] = useState<boolean>(false);
  const [permissionsModalOpen, setPermissionsModalOpen] = useState<boolean>(false);
  const [selectedDocument, setSelectedDocument] = useState<Document | null>(null);
  
  // Fetch data
  useEffect(() => {
    fetchDocuments();
    fetchCategories();
    fetchTags();
  }, []);
  
  // Filter documents when search term, category, or tags change
  useEffect(() => {
    filterDocuments();
  }, [documents, searchTerm, selectedCategory, selectedTags, activeTab]);
  
  const fetchDocuments = async () => {
    setLoading(true);
    try {
      let response;
      
      if (activeTab === 'favorites') {
        response = await documentApi.getFavoriteDocuments();
      } else if (activeTab === 'recent') {
        response = await documentApi.getRecentDocuments(20);
      } else {
        response = await documentApi.getDocuments();
      }
      
      setDocuments(response.data);
      setLoading(false);
    } catch (error) {
      console.error('Error fetching documents:', error);
      toast.error('Failed to load documents');
      setLoading(false);
    }
  };
  
  const fetchCategories = async () => {
    try {
      const response = await documentCategoryApi.getCategoryHierarchy();
      setCategories(response.data);
    } catch (error) {
      console.error('Error fetching categories:', error);
      toast.error('Failed to load document categories');
    }
  };
  
  const fetchTags = async () => {
    try {
      const response = await documentTagApi.getTags();
      setTags(response.data.map((tag: any) => tag.name));
    } catch (error) {
      console.error('Error fetching tags:', error);
      toast.error('Failed to load document tags');
    }
  };
  
  const filterDocuments = () => {
    let filtered = [...documents];
    
    // Filter by search term
    if (searchTerm) {
      const term = searchTerm.toLowerCase();
      filtered = filtered.filter(doc => 
        doc.title.toLowerCase().includes(term) || 
        (doc.description && doc.description.toLowerCase().includes(term)) ||
        doc.file_name.toLowerCase().includes(term) ||
        (doc.tags && doc.tags.some(tag => tag.toLowerCase().includes(term)))
      );
    }
    
    // Filter by category
    if (selectedCategory) {
      filtered = filtered.filter(doc => doc.category_id === selectedCategory);
    }
    
    // Filter by tags
    if (selectedTags.length > 0) {
      filtered = filtered.filter(doc => 
        doc.tags && selectedTags.every(tag => doc.tags!.includes(tag))
      );
    }
    
    // Filter by tab
    if (activeTab === 'favorites') {
      filtered = filtered.filter(doc => doc.is_favorite);
    } else if (activeTab === 'archived') {
      filtered = filtered.filter(doc => doc.status === 'archived');
    } else if (activeTab !== 'all' && activeTab !== 'recent') {
      // Filter by file type
      filtered = filtered.filter(doc => {
        const extension = doc.file_name.split('.').pop()?.toLowerCase();
        
        switch (activeTab) {
          case 'documents':
            return ['doc', 'docx', 'txt', 'rtf', 'pdf'].includes(extension || '');
          case 'spreadsheets':
            return ['xls', 'xlsx', 'csv'].includes(extension || '');
          case 'presentations':
            return ['ppt', 'pptx'].includes(extension || '');
          case 'images':
            return ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'svg'].includes(extension || '');
          case 'archives':
            return ['zip', 'rar', '7z', 'tar', 'gz'].includes(extension || '');
          default:
            return true;
        }
      });
    }
    
    // Sort documents
    filtered.sort((a, b) => {
      let valueA: any = a[sortField as keyof Document];
      let valueB: any = b[sortField as keyof Document];
      
      // Handle dates
      if (typeof valueA === 'string' && (valueA.includes('-') || valueA.includes('/'))) {
        try {
          valueA = new Date(valueA).getTime();
          valueB = new Date(valueB).getTime();
        } catch (e) {
          // Not a valid date, continue with string comparison
        }
      }
      
      if (valueA < valueB) return sortDirection === 'asc' ? -1 : 1;
      if (valueA > valueB) return sortDirection === 'asc' ? 1 : -1;
      return 0;
    });
    
    setFilteredDocuments(filtered);
  };
  
  const handleSort = (field: string) => {
    if (sortField === field) {
      setSortDirection(sortDirection === 'asc' ? 'desc' : 'asc');
    } else {
      setSortField(field);
      setSortDirection('asc');
    }
  };
  
  const handleTabChange = (tab: string) => {
    setActiveTab(tab);
    // Reset filters when changing tabs
    setSearchTerm('');
    setSelectedCategory(null);
    setSelectedTags([]);
  };
  
  const handleDocumentUpload = () => {
    setUploadModalOpen(true);
  };
  
  const handleDocumentClick = (document: Document) => {
    setSelectedDocument(document);
    setDetailsModalOpen(true);
  };
  
  const handleCategoryClick = (categoryId: number) => {
    setSelectedCategory(categoryId === selectedCategory ? null : categoryId);
  };
  
  const handleTagClick = (tag: string) => {
    if (selectedTags.includes(tag)) {
      setSelectedTags(selectedTags.filter(t => t !== tag));
    } else {
      setSelectedTags([...selectedTags, tag]);
    }
  };
  
  const handleDocumentAction = async (action: string, document: Document) => {
    try {
      switch (action) {
        case 'view':
          setSelectedDocument(document);
          setDetailsModalOpen(true);
          break;
          
        case 'download':
          const response = await documentApi.downloadDocument(document.id);
          const url = window.URL.createObjectURL(new Blob([response.data]));
          const link = document.createElement('a');
          link.href = url;
          link.setAttribute('download', document.file_name);
          document.body.appendChild(link);
          link.click();
          link.remove();
          break;
          
        case 'edit':
          setSelectedDocument(document);
          setUploadModalOpen(true);
          break;
          
        case 'delete':
          if (window.confirm(`Are you sure you want to delete "${document.title}"?`)) {
            await documentApi.deleteDocument(document.id);
            toast.success('Document deleted successfully');
            fetchDocuments();
          }
          break;
          
        case 'archive':
          await documentApi.archiveDocument(document.id);
          toast.success('Document archived successfully');
          fetchDocuments();
          break;
          
        case 'restore':
          await documentApi.restoreDocument(document.id);
          toast.success('Document restored successfully');
          fetchDocuments();
          break;
          
        case 'permissions':
          setSelectedDocument(document);
          setPermissionsModalOpen(true);
          break;
          
        case 'favorite':
          await documentApi.toggleFavorite(document.id);
          toast.success(document.is_favorite ? 'Removed from favorites' : 'Added to favorites');
          fetchDocuments();
          break;
      }
    } catch (error) {
      console.error(`Error performing action ${action}:`, error);
      toast.error(`Failed to ${action} document`);
    }
  };
  
  const handleCreateCategory = () => {
    setCategoryModalOpen(true);
  };
  
  const getFileIcon = (fileName: string) => {
    const extension = fileName.split('.').pop()?.toLowerCase();
    
    switch (extension) {
      case 'pdf':
        return <FontAwesomeIcon icon={faFilePdf} className="text-danger" />;
      case 'doc':
      case 'docx':
      case 'rtf':
      case 'txt':
        return <FontAwesomeIcon icon={faFileWord} className="text-primary" />;
      case 'xls':
      case 'xlsx':
      case 'csv':
        return <FontAwesomeIcon icon={faFileExcel} className="text-success" />;
      case 'ppt':
      case 'pptx':
        return <FontAwesomeIcon icon={faFileWord} className="text-warning" />;
      case 'jpg':
      case 'jpeg':
      case 'png':
      case 'gif':
      case 'bmp':
      case 'svg':
        return <FontAwesomeIcon icon={faFileImage} className="text-info" />;
      case 'zip':
      case 'rar':
      case '7z':
      case 'tar':
      case 'gz':
        return <FontAwesomeIcon icon={faFileArchive} className="text-secondary" />;
      case 'html':
      case 'css':
      case 'js':
      case 'json':
      case 'xml':
        return <FontAwesomeIcon icon={faFileCode} className="text-dark" />;
      default:
        return <FontAwesomeIcon icon={faFileAlt} className="text-muted" />;
    }
  };
  
  const formatFileSize = (bytes: number) => {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
  };
  
  const formatDate = (dateString: string) => {
    try {
      return format(parseISO(dateString), 'MMM d, yyyy h:mm a');
    } catch (error) {
      return dateString;
    }
  };
  
  const renderDocumentList = () => {
    if (loading) {
      return (
        <div className="text-center py-5">
          <Spinner color="primary" />
          <p className="mt-2">Loading documents...</p>
        </div>
      );
    }
    
    if (filteredDocuments.length === 0) {
      return (
        <Alert color="info" className="text-center">
          No documents found. {searchTerm || selectedCategory || selectedTags.length > 0 ? 'Try adjusting your filters.' : 'Upload a document to get started.'}
        </Alert>
      );
    }
    
    return (
      <div className="document-list">
        <div className="document-list-header d-none d-md-flex">
          <div className="document-name" onClick={() => handleSort('title')}>
            Name
            {sortField === 'title' && (
              <FontAwesomeIcon icon={sortDirection === 'asc' ? faSortUp : faSortDown} className="ms-1" />
            )}
          </div>
          <div className="document-category" onClick={() => handleSort('category_name')}>
            Category
            {sortField === 'category_name' && (
              <FontAwesomeIcon icon={sortDirection === 'asc' ? faSortUp : faSortDown} className="ms-1" />
            )}
          </div>
          <div className="document-size" onClick={() => handleSort('file_size')}>
            Size
            {sortField === 'file_size' && (
              <FontAwesomeIcon icon={sortDirection === 'asc' ? faSortUp : faSortDown} className="ms-1" />
            )}
          </div>
          <div className="document-date" onClick={() => handleSort('updated_at')}>
            Last Modified
            {sortField === 'updated_at' && (
              <FontAwesomeIcon icon={sortDirection === 'asc' ? faSortUp : faSortDown} className="ms-1" />
            )}
          </div>
          <div className="document-actions">Actions</div>
        </div>
        
        {filteredDocuments.map(document => (
          <div key={document.id} className="document-item">
            <div className="document-name" onClick={() => handleDocumentClick(document)}>
              <div className="document-icon">{getFileIcon(document.file_name)}</div>
              <div className="document-title">
                <span>{document.title}</span>
                {document.is_favorite && (
                  <FontAwesomeIcon icon={faStar} className="text-warning ms-2" />
                )}
                {document.status === 'archived' && (
                  <Badge color="secondary" className="ms-2">Archived</Badge>
                )}
              </div>
            </div>
            <div className="document-category d-none d-md-block">
              <Badge color="info">{document.category_name}</Badge>
            </div>
            <div className="document-size d-none d-md-block">
              {formatFileSize(document.file_size)}
            </div>
            <div className="document-date d-none d-md-block">
              {formatDate(document.updated_at || document.created_at)}
            </div>
            <div className="document-actions">
              <Button color="link" className="p-0 me-2" title="View" onClick={() => handleDocumentAction('view', document)}>
                <FontAwesomeIcon icon={faEye} />
              </Button>
              <Button color="link" className="p-0 me-2" title="Download" onClick={() => handleDocumentAction('download', document)}>
                <FontAwesomeIcon icon={faDownload} />
              </Button>
              <Dropdown isOpen={document.id === selectedDocument?.id} toggle={() => setSelectedDocument(document.id === selectedDocument?.id ? null : document)}>
                <DropdownToggle color="link" className="p-0">
                  <FontAwesomeIcon icon={faEllipsisV} />
                </DropdownToggle>
                <DropdownMenu end>
                  <DropdownItem onClick={() => handleDocumentAction('edit', document)}>
                    <FontAwesomeIcon icon={faEdit} className="me-2" /> Edit
                  </DropdownItem>
                  <DropdownItem onClick={() => handleDocumentAction('permissions', document)}>
                    <FontAwesomeIcon icon={faLock} className="me-2" /> Permissions
                  </DropdownItem>
                  <DropdownItem onClick={() => handleDocumentAction('favorite', document)}>
                    <FontAwesomeIcon icon={faStar} className="me-2" /> {document.is_favorite ? 'Remove from Favorites' : 'Add to Favorites'}
                  </DropdownItem>
                  {document.status === 'archived' ? (
                    <DropdownItem onClick={() => handleDocumentAction('restore', document)}>
                      <FontAwesomeIcon icon={faHistory} className="me-2" /> Restore
                    </DropdownItem>
                  ) : (
                    <DropdownItem onClick={() => handleDocumentAction('archive', document)}>
                      <FontAwesomeIcon icon={faArchive} className="me-2" /> Archive
                    </DropdownItem>
                  )}
                  <DropdownItem divider />
                  <DropdownItem onClick={() => handleDocumentAction('delete', document)} className="text-danger">
                    <FontAwesomeIcon icon={faTrash} className="me-2" /> Delete
                  </DropdownItem>
                </DropdownMenu>
              </Dropdown>
            </div>
          </div>
        ))}
      </div>
    );
  };
  
  return (
    <Container fluid className="document-management-container">
      <Row className="mb-4">
        <Col>
          <h2 className="page-title">Document Management</h2>
        </Col>
        <Col xs="auto">
          <Button color="primary" onClick={handleDocumentUpload}>
            <FontAwesomeIcon icon={faPlus} className="me-2" /> Upload Document
          </Button>
        </Col>
      </Row>
      
      <Row>
        <Col md={3} className="mb-4">
          <Card className="sidebar-card">
            <CardHeader>
              <h5 className="mb-0">Document Types</h5>
            </CardHeader>
            <CardBody className="p-0">
              <Nav vertical className="document-nav">
                <NavItem>
                  <NavLink
                    className={activeTab === 'all' ? 'active' : ''}
                    onClick={() => handleTabChange('all')}
                  >
                    <FontAwesomeIcon icon={faFile} className="me-2" /> All Documents
                  </NavLink>
                </NavItem>
                <NavItem>
                  <NavLink
                    className={activeTab === 'recent' ? 'active' : ''}
                    onClick={() => handleTabChange('recent')}
                  >
                    <FontAwesomeIcon icon={faCalendarAlt} className="me-2" /> Recent Documents
                  </NavLink>
                </NavItem>
                <NavItem>
                  <NavLink
                    className={activeTab === 'favorites' ? 'active' : ''}
                    onClick={() => handleTabChange('favorites')}
                  >
                    <FontAwesomeIcon icon={faStar} className="me-2" /> Favorites
                  </NavLink>
                </NavItem>
                <NavItem>
                  <NavLink
                    className={activeTab === 'documents' ? 'active' : ''}
                    onClick={() => handleTabChange('documents')}
                  >
                    <FontAwesomeIcon icon={faFileAlt} className="me-2" /> Text Documents
                  </NavLink>
                </NavItem>
                <NavItem>
                  <NavLink
                    className={activeTab === 'spreadsheets' ? 'active' : ''}
                    onClick={() => handleTabChange('spreadsheets')}
                  >
                    <FontAwesomeIcon icon={faFileExcel} className="me-2" /> Spreadsheets
                  </NavLink>
                </NavItem>
                <NavItem>
                  <NavLink
                    className={activeTab === 'presentations' ? 'active' : ''}
                    onClick={() => handleTabChange('presentations')}
                  >
                    <FontAwesomeIcon icon={faFileWord} className="me-2" /> Presentations
                  </NavLink>
                </NavItem>
                <NavItem>
                  <NavLink
                    className={activeTab === 'images' ? 'active' : ''}
                    onClick={() => handleTabChange('images')}
                  >
                    <FontAwesomeIcon icon={faFileImage} className="me-2" /> Images
                  </NavLink>
                </NavItem>
                <NavItem>
                  <NavLink
                    className={activeTab === 'archives' ? 'active' : ''}
                    onClick={() => handleTabChange('archives')}
                  >
                    <FontAwesomeIcon icon={faFileArchive} className="me-2" /> Archives
                  </NavLink>
                </NavItem>
                <NavItem>
                  <NavLink
                    className={activeTab === 'archived' ? 'active' : ''}
                    onClick={() => handleTabChange('archived')}
                  >
                    <FontAwesomeIcon icon={faArchive} className="me-2" /> Archived
                  </NavLink>
                </NavItem>
              </Nav>
            </CardBody>
          </Card>
          
          <Card className="sidebar-card mt-4">
            <CardHeader className="d-flex justify-content-between align-items-center">
              <h5 className="mb-0">Categories</h5>
              <Button color="link" className="p-0" onClick={handleCreateCategory}>
                <FontAwesomeIcon icon={faPlus} />
              </Button>
            </CardHeader>
            <CardBody className="p-0">
              <Nav vertical className="document-nav">
                {categories.map(category => (
                  <NavItem key={category.id}>
                    <NavLink
                      className={selectedCategory === category.id ? 'active' : ''}
                      onClick={() => handleCategoryClick(category.id)}
                    >
                      <FontAwesomeIcon icon={faFolder} className="me-2" /> {category.name}
                      {category.document_count > 0 && (
                        <Badge color="light" className="ms-2">{category.document_count}</Badge>
                      )}
                    </NavLink>
                  </NavItem>
                ))}
              </Nav>
            </CardBody>
          </Card>
          
          {tags.length > 0 && (
            <Card className="sidebar-card mt-4">
              <CardHeader>
                <h5 className="mb-0">Tags</h5>
              </CardHeader>
              <CardBody>
                <div className="tag-cloud">
                  {tags.map(tag => (
                    <Badge
                      key={tag}
                      color={selectedTags.includes(tag) ? 'primary' : 'light'}
                      className="tag-badge"
                      onClick={() => handleTagClick(tag)}
                    >
                      <FontAwesomeIcon icon={faTag} className="me-1" /> {tag}
                    </Badge>
                  ))}
                </div>
              </CardBody>
            </Card>
          )}
        </Col>
        
        <Col md={9}>
          <Card className="main-card">
            <CardHeader>
              <Row className="align-items-center">
                <Col>
                  <InputGroup>
                    <InputGroupText>
                      <FontAwesomeIcon icon={faSearch} />
                    </InputGroupText>
                    <Input
                      placeholder="Search documents..."
                      value={searchTerm}
                      onChange={(e) => setSearchTerm(e.target.value)}
                    />
                  </InputGroup>
                </Col>
                <Col xs="auto">
                  <Dropdown isOpen={filterDropdownOpen} toggle={() => setFilterDropdownOpen(!filterDropdownOpen)} className="me-2">
                    <DropdownToggle color="light" caret>
                      <FontAwesomeIcon icon={faFilter} className="me-1" /> Filter
                    </DropdownToggle>
                    <DropdownMenu>
                      <DropdownItem header>Access Level</DropdownItem>
                      <DropdownItem>Public</DropdownItem>
                      <DropdownItem>Restricted</DropdownItem>
                      <DropdownItem>Private</DropdownItem>
                      <DropdownItem divider />
                      <DropdownItem header>Date Range</DropdownItem>
                      <DropdownItem>Today</DropdownItem>
                      <DropdownItem>This Week</DropdownItem>
                      <DropdownItem>This Month</DropdownItem>
                      <DropdownItem>This Year</DropdownItem>
                    </DropdownMenu>
                  </Dropdown>
                  
                  <Dropdown isOpen={sortDropdownOpen} toggle={() => setSortDropdownOpen(!sortDropdownOpen)}>
                    <DropdownToggle color="light" caret>
                      <FontAwesomeIcon icon={faSort} className="me-1" /> Sort
                    </DropdownToggle>
                    <DropdownMenu>
                      <DropdownItem onClick={() => handleSort('title')}>
                        Name {sortField === 'title' && (
                          <FontAwesomeIcon icon={sortDirection === 'asc' ? faSortUp : faSortDown} className="ms-1" />
                        )}
                      </DropdownItem>
                      <DropdownItem onClick={() => handleSort('category_name')}>
                        Category {sortField === 'category_name' && (
                          <FontAwesomeIcon icon={sortDirection === 'asc' ? faSortUp : faSortDown} className="ms-1" />
                        )}
                      </DropdownItem>
                      <DropdownItem onClick={() => handleSort('file_size')}>
                        Size {sortField === 'file_size' && (
                          <FontAwesomeIcon icon={sortDirection === 'asc' ? faSortUp : faSortDown} className="ms-1" />
                        )}
                      </DropdownItem>
                      <DropdownItem onClick={() => handleSort('created_at')}>
                        Date Created {sortField === 'created_at' && (
                          <FontAwesomeIcon icon={sortDirection === 'asc' ? faSortUp : faSortDown} className="ms-1" />
                        )}
                      </DropdownItem>
                      <DropdownItem onClick={() => handleSort('updated_at')}>
                        Last Modified {sortField === 'updated_at' && (
                          <FontAwesomeIcon icon={sortDirection === 'asc' ? faSortUp : faSortDown} className="ms-1" />
                        )}
                      </DropdownItem>
                    </DropdownMenu>
                  </Dropdown>
                </Col>
              </Row>
            </CardHeader>
            <CardBody>
              <TabContent activeTab={activeTab}>
                <TabPane tabId={activeTab}>
                  {renderDocumentList()}
                </TabPane>
              </TabContent>
            </CardBody>
          </Card>
        </Col>
      </Row>
      
      {/* Modals */}
      <UploadDocumentModal
        isOpen={uploadModalOpen}
        toggle={() => setUploadModalOpen(!uploadModalOpen)}
        document={selectedDocument}
        categories={categories}
        tags={tags}
        onSuccess={() => {
          setUploadModalOpen(false);
          setSelectedDocument(null);
          fetchDocuments();
        }}
      />
      
      <DocumentDetailsModal
        isOpen={detailsModalOpen}
        toggle={() => setDetailsModalOpen(!detailsModalOpen)}
        document={selectedDocument}
        onAction={(action) => {
          if (selectedDocument) {
            handleDocumentAction(action, selectedDocument);
          }
          if (action !== 'view' && action !== 'permissions') {
            setDetailsModalOpen(false);
          }
        }}
      />
      
      <DocumentCategoryModal
        isOpen={categoryModalOpen}
        toggle={() => setCategoryModalOpen(!categoryModalOpen)}
        categories={categories}
        onSuccess={() => {
          setCategoryModalOpen(false);
          fetchCategories();
        }}
      />
      
      <DocumentPermissionsModal
        isOpen={permissionsModalOpen}
        toggle={() => setPermissionsModalOpen(!permissionsModalOpen)}
        document={selectedDocument}
        onSuccess={() => {
          setPermissionsModalOpen(false);
          fetchDocuments();
        }}
      />
    </Container>
  );
};

export default DocumentManagement;