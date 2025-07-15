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
  Table,
  Badge,
  Alert,
  Spinner,
  Row,
  Col
} from 'reactstrap';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import {
  faLock,
  faLockOpen,
  faUserPlus,
  faUsers,
  faTrash,
  faEye,
  faEdit,
  faShare,
  faCalendarAlt
} from '@fortawesome/free-solid-svg-icons';
import { toast } from 'react-toastify';
import Select from 'react-select';
import DatePicker from 'react-datepicker';
import 'react-datepicker/dist/react-datepicker.css';
import { format, parseISO, addDays } from 'date-fns';

import { Document, DocumentPermission, documentApi } from '../../services/documentManagementApi';

interface DocumentPermissionsModalProps {
  isOpen: boolean;
  toggle: () => void;
  document: Document | null;
  onSuccess: () => void;
}

const DocumentPermissionsModal: React.FC<DocumentPermissionsModalProps> = ({
  isOpen,
  toggle,
  document,
  onSuccess
}) => {
  // State
  const [permissions, setPermissions] = useState<DocumentPermission[]>([]);
  const [loading, setLoading] = useState<boolean>(false);
  const [showAddForm, setShowAddForm] = useState<boolean>(false);
  
  // Form state
  const [permissionType, setPermissionType] = useState<string>('view');
  const [userId, setUserId] = useState<number | null>(null);
  const [userGroupId, setUserGroupId] = useState<number | null>(null);
  const [expirationDate, setExpirationDate] = useState<Date | null>(addDays(new Date(), 30));
  
  // Mock data for users and user groups
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
  
  // Fetch permissions when modal opens
  useEffect(() => {
    if (isOpen && document) {
      fetchPermissions();
    }
  }, [isOpen, document]);
  
  const fetchPermissions = async () => {
    if (!document) return;
    
    setLoading(true);
    
    try {
      const response = await documentApi.getDocumentPermissions(document.id);
      setPermissions(response.data);
    } catch (error) {
      console.error('Error fetching document permissions:', error);
      toast.error('Failed to load document permissions');
    } finally {
      setLoading(false);
    }
  };
  
  const resetForm = () => {
    setPermissionType('view');
    setUserId(null);
    setUserGroupId(null);
    setExpirationDate(addDays(new Date(), 30));
  };
  
  const handleAddPermission = async () => {
    if (!document) return;
    
    if (!userId && !userGroupId) {
      toast.error('Please select a user or user group');
      return;
    }
    
    setLoading(true);
    
    try {
      const data = {
        user_id: userId || undefined,
        user_group_id: userGroupId || undefined,
        permission_type: permissionType as 'view' | 'edit' | 'delete' | 'share',
        expires_at: expirationDate ? format(expirationDate, 'yyyy-MM-dd') : undefined
      };
      
      await documentApi.grantPermission(document.id, data);
      toast.success('Permission granted successfully');
      fetchPermissions();
      resetForm();
      setShowAddForm(false);
    } catch (error) {
      console.error('Error granting permission:', error);
      toast.error('Failed to grant permission');
    } finally {
      setLoading(false);
    }
  };
  
  const handleRevokePermission = async (permissionId: number) => {
    if (!document) return;
    
    if (window.confirm('Are you sure you want to revoke this permission?')) {
      setLoading(true);
      
      try {
        await documentApi.revokePermission(document.id, permissionId);
        toast.success('Permission revoked successfully');
        fetchPermissions();
      } catch (error) {
        console.error('Error revoking permission:', error);
        toast.error('Failed to revoke permission');
      } finally {
        setLoading(false);
      }
    }
  };
  
  const getPermissionIcon = (type: string) => {
    switch (type) {
      case 'view':
        return <FontAwesomeIcon icon={faEye} className="text-info" />;
      case 'edit':
        return <FontAwesomeIcon icon={faEdit} className="text-warning" />;
      case 'delete':
        return <FontAwesomeIcon icon={faTrash} className="text-danger" />;
      case 'share':
        return <FontAwesomeIcon icon={faShare} className="text-success" />;
      default:
        return <FontAwesomeIcon icon={faEye} className="text-info" />;
    }
  };
  
  const getPermissionLabel = (type: string) => {
    switch (type) {
      case 'view':
        return <Badge color="info">View</Badge>;
      case 'edit':
        return <Badge color="warning">Edit</Badge>;
      case 'delete':
        return <Badge color="danger">Delete</Badge>;
      case 'share':
        return <Badge color="success">Share</Badge>;
      default:
        return <Badge color="info">View</Badge>;
    }
  };
  
  const formatDate = (dateString: string) => {
    try {
      return format(parseISO(dateString), 'MMM d, yyyy');
    } catch (error) {
      return dateString;
    }
  };
  
  const getUserOrGroupName = (permission: DocumentPermission) => {
    if (permission.user_id) {
      const user = users.find(u => u.id === permission.user_id);
      return user ? `${user.name} (User)` : `User ID: ${permission.user_id}`;
    } else if (permission.user_group_id) {
      const group = userGroups.find(g => g.id === permission.user_group_id);
      return group ? `${group.name} (Group)` : `Group ID: ${permission.user_group_id}`;
    }
    return 'Unknown';
  };
  
  if (!document) {
    return null;
  }
  
  return (
    <Modal isOpen={isOpen} toggle={toggle} size="lg">
      <ModalHeader toggle={toggle}>
        <FontAwesomeIcon icon={faLock} className="me-2" />
        Document Permissions: {document.title}
      </ModalHeader>
      <ModalBody>
        <div className="mb-4">
          <h5>Access Control</h5>
          <p>
            Current access level: {' '}
            <Badge color={
              document.access_level === 'public' ? 'success' :
              document.access_level === 'restricted' ? 'warning' : 'danger'
            }>
              {document.access_level}
            </Badge>
          </p>
          <p className="text-muted small">
            <FontAwesomeIcon icon={faLockOpen} className="me-1" /> <strong>Public:</strong> All users can view the document<br />
            <FontAwesomeIcon icon={faLock} className="me-1" /> <strong>Restricted:</strong> Only users with explicit permissions can access<br />
            <FontAwesomeIcon icon={faLock} className="me-1" /> <strong>Private:</strong> Only the owner and administrators can access
          </p>
        </div>
        
        <div className="d-flex justify-content-between align-items-center mb-3">
          <h5>User & Group Permissions</h5>
          <Button color="primary" size="sm" onClick={() => setShowAddForm(!showAddForm)}>
            <FontAwesomeIcon icon={faUserPlus} className="me-1" /> 
            {showAddForm ? 'Cancel' : 'Add Permission'}
          </Button>
        </div>
        
        {showAddForm && (
          <div className="add-permission-form mb-4 p-3 border rounded">
            <h6>Grant New Permission</h6>
            <Form>
              <Row>
                <Col md={6}>
                  <FormGroup>
                    <Label for="permissionType">Permission Type</Label>
                    <Input
                      type="select"
                      id="permissionType"
                      value={permissionType}
                      onChange={(e) => setPermissionType(e.target.value)}
                    >
                      <option value="view">View</option>
                      <option value="edit">Edit</option>
                      <option value="delete">Delete</option>
                      <option value="share">Share</option>
                    </Input>
                  </FormGroup>
                </Col>
                <Col md={6}>
                  <FormGroup>
                    <Label for="expirationDate">Expiration Date (Optional)</Label>
                    <DatePicker
                      selected={expirationDate}
                      onChange={(date) => setExpirationDate(date)}
                      minDate={new Date()}
                      dateFormat="MMMM d, yyyy"
                      className="form-control"
                      id="expirationDate"
                      placeholderText="Never expires"
                      isClearable
                    />
                  </FormGroup>
                </Col>
              </Row>
              
              <Row>
                <Col md={6}>
                  <FormGroup>
                    <Label for="userId">User</Label>
                    <Select
                      id="userId"
                      options={users.map(user => ({ value: user.id, label: user.name }))}
                      value={userId ? { value: userId, label: users.find(u => u.id === userId)?.name } : null}
                      onChange={(option: any) => {
                        setUserId(option ? option.value : null);
                        if (option) setUserGroupId(null);
                      }}
                      placeholder="Select a user"
                      isClearable
                      isDisabled={!!userGroupId}
                    />
                  </FormGroup>
                </Col>
                <Col md={6}>
                  <FormGroup>
                    <Label for="userGroupId">User Group</Label>
                    <Select
                      id="userGroupId"
                      options={userGroups.map(group => ({ value: group.id, label: group.name }))}
                      value={userGroupId ? { value: userGroupId, label: userGroups.find(g => g.id === userGroupId)?.name } : null}
                      onChange={(option: any) => {
                        setUserGroupId(option ? option.value : null);
                        if (option) setUserId(null);
                      }}
                      placeholder="Select a user group"
                      isClearable
                      isDisabled={!!userId}
                    />
                  </FormGroup>
                </Col>
              </Row>
              
              <div className="text-end">
                <Button color="secondary" className="me-2" onClick={() => setShowAddForm(false)}>
                  Cancel
                </Button>
                <Button color="primary" onClick={handleAddPermission} disabled={loading}>
                  Grant Permission
                </Button>
              </div>
            </Form>
          </div>
        )}
        
        {loading ? (
          <div className="text-center py-3">
            <Spinner color="primary" />
          </div>
        ) : permissions.length === 0 ? (
          <Alert color="info">
            No specific permissions have been granted for this document.
          </Alert>
        ) : (
          <Table responsive striped>
            <thead>
              <tr>
                <th>User/Group</th>
                <th>Permission</th>
                <th>Granted By</th>
                <th>Granted Date</th>
                <th>Expires</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              {permissions.map(permission => (
                <tr key={permission.id}>
                  <td>
                    <FontAwesomeIcon 
                      icon={permission.user_id ? faUsers : faUsers} 
                      className="me-2" 
                    />
                    {getUserOrGroupName(permission)}
                  </td>
                  <td>{getPermissionLabel(permission.permission_type)}</td>
                  <td>User ID: {permission.granted_by}</td>
                  <td>{formatDate(permission.granted_at)}</td>
                  <td>
                    {permission.expires_at ? (
                      <span>
                        <FontAwesomeIcon icon={faCalendarAlt} className="me-1" />
                        {formatDate(permission.expires_at)}
                      </span>
                    ) : (
                      <span className="text-muted">Never</span>
                    )}
                  </td>
                  <td>
                    <Button color="danger" size="sm" onClick={() => handleRevokePermission(permission.id)}>
                      <FontAwesomeIcon icon={faTrash} /> Revoke
                    </Button>
                  </td>
                </tr>
              ))}
            </tbody>
          </Table>
        )}
      </ModalBody>
      <ModalFooter>
        <Button color="secondary" onClick={toggle}>
          Close
        </Button>
      </ModalFooter>
    </Modal>
  );
};

export default DocumentPermissionsModal;