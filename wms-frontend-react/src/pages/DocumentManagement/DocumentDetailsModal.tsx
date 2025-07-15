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
  Table,
  Badge,
  Card,
  CardBody,
  Input,
  FormGroup,
  Label,
  Spinner,
  Alert
} from 'reactstrap';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import {
  faDownload,
  faEdit,
  faTrash,
  faArchive,
  faShare,
  faHistory,
  faLock,
  faStar,
  faFile,
  faFilePdf,
  faFileWord,
  faFileExcel,
  faFileImage,
  faFileArchive,
  faFileCode,
  faFileAlt,
  faTag,
  faUser,
  faCalendarAlt,
  faInfoCircle,
  faClock,
  faComment,
  faPaperPlane,
  faEye
} from '@fortawesome/free-solid-svg-icons';
import { format, parseISO } from 'date-fns';
import { toast } from 'react-toastify';

import { Document, DocumentVersion, DocumentComment, documentApi } from '../../services/documentManagementApi';

interface DocumentDetailsModalProps {
  isOpen: boolean;
  toggle: () => void;
  document: Document | null;
  onAction: (action: string) => void;
}

const DocumentDetailsModal: React.FC<DocumentDetailsModalProps> = ({
  isOpen,
  toggle,
  document,
  onAction
}) => {
  // State
  const [activeTab, setActiveTab] = useState<string>('preview');
  const [versions, setVersions] = useState<DocumentVersion[]>([]);
  const [comments, setComments] = useState<DocumentComment[]>([]);
  const [newComment, setNewComment] = useState<string>('');
  const [loading, setLoading] = useState<boolean>(false);
  const [previewUrl, setPreviewUrl] = useState<string>('');
  const [previewError, setPreviewError] = useState<string>('');
  
  // Fetch document details when modal opens
  useEffect(() => {
    if (isOpen && document) {
      fetchVersions();
      fetchComments();
      generatePreview();
    }
  }, [isOpen, document, activeTab]);
  
  const fetchVersions = async () => {
    if (!document) return;
    
    try {
      const response = await documentApi.getDocumentVersions(document.id);
      setVersions(response.data);
    } catch (error) {
      console.error('Error fetching document versions:', error);
      toast.error('Failed to load document versions');
    }
  };
  
  const fetchComments = async () => {
    if (!document) return;
    
    try {
      const response = await documentApi.getDocumentComments(document.id);
      setComments(response.data);
    } catch (error) {
      console.error('Error fetching document comments:', error);
      toast.error('Failed to load document comments');
    }
  };
  
  const generatePreview = async () => {
    if (!document) return;
    
    setLoading(true);
    setPreviewError('');
    
    try {
      // For a real implementation, you would generate a preview URL from your backend
      // Here we're simulating it based on file type
      const extension = document.file_name.split('.').pop()?.toLowerCase();
      
      // In a real implementation, you would use a document preview service
      // For now, we'll just simulate different preview types
      if (['pdf'].includes(extension || '')) {
        setPreviewUrl(`/api/documents/${document.id}/preview`);
      } else if (['jpg', 'jpeg', 'png', 'gif'].includes(extension || '')) {
        setPreviewUrl(`/api/documents/${document.id}/preview`);
      } else if (['doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx'].includes(extension || '')) {
        // Office documents would typically use a conversion service
        setPreviewUrl(`/api/documents/${document.id}/preview`);
      } else {
        setPreviewError('Preview not available for this file type. Please download the file to view it.');
      }
    } catch (error) {
      console.error('Error generating preview:', error);
      setPreviewError('Failed to generate document preview');
    } finally {
      setLoading(false);
    }
  };
  
  const handleAddComment = async () => {
    if (!document || !newComment.trim()) return;
    
    try {
      await documentApi.addComment(document.id, { comment: newComment });
      setNewComment('');
      fetchComments();
      toast.success('Comment added successfully');
    } catch (error) {
      console.error('Error adding comment:', error);
      toast.error('Failed to add comment');
    }
  };
  
  const handleDownloadVersion = async (versionId: number) => {
    if (!document) return;
    
    try {
      const response = await documentApi.getDocumentVersion(document.id, versionId);
      // In a real implementation, you would trigger a download here
      toast.success('Version download started');
    } catch (error) {
      console.error('Error downloading version:', error);
      toast.error('Failed to download version');
    }
  };
  
  const handleRevertToVersion = async (versionId: number) => {
    if (!document) return;
    
    if (window.confirm('Are you sure you want to revert to this version? This will create a new version with the content of the selected version.')) {
      try {
        await documentApi.revertToPreviousVersion(document.id, versionId);
        fetchVersions();
        toast.success('Reverted to selected version successfully');
      } catch (error) {
        console.error('Error reverting to version:', error);
        toast.error('Failed to revert to version');
      }
    }
  };
  
  const getFileIcon = (fileName: string) => {
    const extension = fileName.split('.').pop()?.toLowerCase();
    
    switch (extension) {
      case 'pdf':
        return <FontAwesomeIcon icon={faFilePdf} className="text-danger" size="3x" />;
      case 'doc':
      case 'docx':
      case 'rtf':
      case 'txt':
        return <FontAwesomeIcon icon={faFileWord} className="text-primary" size="3x" />;
      case 'xls':
      case 'xlsx':
      case 'csv':
        return <FontAwesomeIcon icon={faFileExcel} className="text-success" size="3x" />;
      case 'ppt':
      case 'pptx':
        return <FontAwesomeIcon icon={faFileWord} className="text-warning" size="3x" />;
      case 'jpg':
      case 'jpeg':
      case 'png':
      case 'gif':
      case 'bmp':
      case 'svg':
        return <FontAwesomeIcon icon={faFileImage} className="text-info" size="3x" />;
      case 'zip':
      case 'rar':
      case '7z':
      case 'tar':
      case 'gz':
        return <FontAwesomeIcon icon={faFileArchive} className="text-secondary" size="3x" />;
      case 'html':
      case 'css':
      case 'js':
      case 'json':
      case 'xml':
        return <FontAwesomeIcon icon={faFileCode} className="text-dark" size="3x" />;
      default:
        return <FontAwesomeIcon icon={faFileAlt} className="text-muted" size="3x" />;
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
  
  if (!document) {
    return null;
  }
  
  return (
    <Modal isOpen={isOpen} toggle={toggle} size="xl">
      <ModalHeader toggle={toggle}>
        {document.title}
        {document.status === 'archived' && (
          <Badge color="secondary" className="ms-2">Archived</Badge>
        )}
      </ModalHeader>
      <ModalBody>
        <Row className="mb-4">
          <Col md={8}>
            <h5>{document.title}</h5>
            <p className="text-muted">
              {document.description || 'No description provided'}
            </p>
          </Col>
          <Col md={4} className="text-end">
            <Button color="primary" className="me-2" onClick={() => onAction('download')}>
              <FontAwesomeIcon icon={faDownload} className="me-1" /> Download
            </Button>
            <Button color="light" onClick={() => onAction('edit')}>
              <FontAwesomeIcon icon={faEdit} className="me-1" /> Edit
            </Button>
          </Col>
        </Row>
        
        <Nav tabs className="mb-3">
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
              className={activeTab === 'details' ? 'active' : ''}
              onClick={() => setActiveTab('details')}
            >
              <FontAwesomeIcon icon={faInfoCircle} className="me-1" /> Details
            </NavLink>
          </NavItem>
          <NavItem>
            <NavLink
              className={activeTab === 'versions' ? 'active' : ''}
              onClick={() => setActiveTab('versions')}
            >
              <FontAwesomeIcon icon={faHistory} className="me-1" /> Versions
            </NavLink>
          </NavItem>
          <NavItem>
            <NavLink
              className={activeTab === 'comments' ? 'active' : ''}
              onClick={() => setActiveTab('comments')}
            >
              <FontAwesomeIcon icon={faComment} className="me-1" /> Comments
            </NavLink>
          </NavItem>
        </Nav>
        
        <TabContent activeTab={activeTab}>
          <TabPane tabId="preview">
            {loading ? (
              <div className="text-center py-5">
                <Spinner color="primary" />
                <p className="mt-2">Loading preview...</p>
              </div>
            ) : previewError ? (
              <Alert color="info" className="text-center py-5">
                <div className="mb-3">
                  {getFileIcon(document.file_name)}
                </div>
                <h5>{document.file_name}</h5>
                <p>{previewError}</p>
                <Button color="primary" onClick={() => onAction('download')}>
                  <FontAwesomeIcon icon={faDownload} className="me-1" /> Download File
                </Button>
              </Alert>
            ) : (
              <div className="document-preview">
                {/* In a real implementation, you would render different preview components based on file type */}
                <Alert color="info" className="text-center py-5">
                  <div className="mb-3">
                    {getFileIcon(document.file_name)}
                  </div>
                  <h5>{document.file_name}</h5>
                  <p>Preview would be displayed here in a real implementation.</p>
                  <Button color="primary" onClick={() => onAction('download')}>
                    <FontAwesomeIcon icon={faDownload} className="me-1" /> Download File
                  </Button>
                </Alert>
              </div>
            )}
          </TabPane>
          
          <TabPane tabId="details">
            <Row>
              <Col md={6}>
                <Card className="mb-3">
                  <CardBody>
                    <h6 className="mb-3">Document Information</h6>
                    <Table borderless size="sm">
                      <tbody>
                        <tr>
                          <th width="40%">File Name</th>
                          <td>{document.file_name}</td>
                        </tr>
                        <tr>
                          <th>File Type</th>
                          <td>{document.file_type}</td>
                        </tr>
                        <tr>
                          <th>File Size</th>
                          <td>{formatFileSize(document.file_size)}</td>
                        </tr>
                        <tr>
                          <th>Version</th>
                          <td>{document.version}</td>
                        </tr>
                        <tr>
                          <th>Category</th>
                          <td>{document.category_name}</td>
                        </tr>
                        <tr>
                          <th>Access Level</th>
                          <td>
                            <Badge color={
                              document.access_level === 'public' ? 'success' :
                              document.access_level === 'restricted' ? 'warning' : 'danger'
                            }>
                              {document.access_level}
                            </Badge>
                          </td>
                        </tr>
                        <tr>
                          <th>Status</th>
                          <td>
                            <Badge color={
                              document.status === 'active' ? 'success' :
                              document.status === 'archived' ? 'secondary' : 'danger'
                            }>
                              {document.status}
                            </Badge>
                          </td>
                        </tr>
                      </tbody>
                    </Table>
                  </CardBody>
                </Card>
              </Col>
              
              <Col md={6}>
                <Card className="mb-3">
                  <CardBody>
                    <h6 className="mb-3">Timeline</h6>
                    <Table borderless size="sm">
                      <tbody>
                        <tr>
                          <th width="40%">Created By</th>
                          <td>User ID: {document.created_by}</td>
                        </tr>
                        <tr>
                          <th>Created Date</th>
                          <td>{formatDate(document.created_at)}</td>
                        </tr>
                        {document.updated_at && (
                          <>
                            <tr>
                              <th>Last Modified By</th>
                              <td>User ID: {document.updated_by}</td>
                            </tr>
                            <tr>
                              <th>Last Modified Date</th>
                              <td>{formatDate(document.updated_at)}</td>
                            </tr>
                          </>
                        )}
                        {document.expiration_date && (
                          <tr>
                            <th>Expiration Date</th>
                            <td>{formatDate(document.expiration_date)}</td>
                          </tr>
                        )}
                      </tbody>
                    </Table>
                  </CardBody>
                </Card>
                
                {document.tags && document.tags.length > 0 && (
                  <Card>
                    <CardBody>
                      <h6 className="mb-3">Tags</h6>
                      <div>
                        {document.tags.map(tag => (
                          <Badge key={tag} color="primary" className="me-2 mb-2 p-2">
                            <FontAwesomeIcon icon={faTag} className="me-1" /> {tag}
                          </Badge>
                        ))}
                      </div>
                    </CardBody>
                  </Card>
                )}
              </Col>
            </Row>
          </TabPane>
          
          <TabPane tabId="versions">
            {versions.length === 0 ? (
              <Alert color="info">No version history available for this document.</Alert>
            ) : (
              <Table responsive striped>
                <thead>
                  <tr>
                    <th>Version</th>
                    <th>File Name</th>
                    <th>Size</th>
                    <th>Created By</th>
                    <th>Created Date</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  {versions.map(version => (
                    <tr key={version.id} className={version.version === document.version ? 'table-active' : ''}>
                      <td>v{version.version}</td>
                      <td>{version.file_name}</td>
                      <td>{formatFileSize(version.file_size)}</td>
                      <td>User ID: {version.created_by}</td>
                      <td>{formatDate(version.created_at)}</td>
                      <td>
                        <Button color="link" className="p-0 me-2" title="Download" onClick={() => handleDownloadVersion(version.id)}>
                          <FontAwesomeIcon icon={faDownload} />
                        </Button>
                        {version.version !== document.version && (
                          <Button color="link" className="p-0" title="Revert to this version" onClick={() => handleRevertToVersion(version.id)}>
                            <FontAwesomeIcon icon={faHistory} />
                          </Button>
                        )}
                      </td>
                    </tr>
                  ))}
                </tbody>
              </Table>
            )}
          </TabPane>
          
          <TabPane tabId="comments">
            <div className="comments-section">
              <div className="comments-list mb-3">
                {comments.length === 0 ? (
                  <Alert color="info">No comments yet. Be the first to comment!</Alert>
                ) : (
                  comments.map(comment => (
                    <div key={comment.id} className="comment-item">
                      <div className="comment-header">
                        <span className="comment-author">
                          <FontAwesomeIcon icon={faUser} className="me-1" /> 
                          {comment.user_name || `User ID: ${comment.user_id}`}
                        </span>
                        <span className="comment-date">
                          <FontAwesomeIcon icon={faClock} className="me-1" /> 
                          {formatDate(comment.created_at)}
                        </span>
                      </div>
                      <div className="comment-body">
                        {comment.comment}
                      </div>
                    </div>
                  ))
                )}
              </div>
              
              <div className="add-comment">
                <FormGroup>
                  <Label for="newComment">Add a Comment</Label>
                  <Input
                    type="textarea"
                    id="newComment"
                    rows={3}
                    value={newComment}
                    onChange={(e) => setNewComment(e.target.value)}
                    placeholder="Type your comment here..."
                  />
                </FormGroup>
                <Button color="primary" onClick={handleAddComment} disabled={!newComment.trim()}>
                  <FontAwesomeIcon icon={faPaperPlane} className="me-1" /> Add Comment
                </Button>
              </div>
            </div>
          </TabPane>
        </TabContent>
      </ModalBody>
      <ModalFooter>
        <div className="d-flex justify-content-between w-100">
          <div>
            <Button color="danger" className="me-2" onClick={() => onAction('delete')}>
              <FontAwesomeIcon icon={faTrash} className="me-1" /> Delete
            </Button>
            {document.status === 'archived' ? (
              <Button color="secondary" onClick={() => onAction('restore')}>
                <FontAwesomeIcon icon={faHistory} className="me-1" /> Restore
              </Button>
            ) : (
              <Button color="secondary" onClick={() => onAction('archive')}>
                <FontAwesomeIcon icon={faArchive} className="me-1" /> Archive
              </Button>
            )}
          </div>
          <div>
            <Button color="info" className="me-2" onClick={() => onAction('permissions')}>
              <FontAwesomeIcon icon={faLock} className="me-1" /> Permissions
            </Button>
            <Button color="warning" className="me-2" onClick={() => onAction('favorite')}>
              <FontAwesomeIcon icon={faStar} className="me-1" /> {document.is_favorite ? 'Remove from Favorites' : 'Add to Favorites'}
            </Button>
            <Button color="primary" onClick={() => onAction('share')}>
              <FontAwesomeIcon icon={faShare} className="me-1" /> Share
            </Button>
          </div>
        </div>
      </ModalFooter>
    </Modal>
  );
};

export default DocumentDetailsModal;