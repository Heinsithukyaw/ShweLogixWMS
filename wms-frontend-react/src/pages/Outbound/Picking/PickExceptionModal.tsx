import React, { useState } from 'react';
import {
  Modal,
  ModalHeader,
  ModalBody,
  ModalFooter,
  Button,
  Badge,
  FormGroup,
  Label,
  Input,
  Card,
  CardHeader,
  CardBody,
  Row,
  Col
} from 'reactstrap';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faCheck, faTimes } from '@fortawesome/free-solid-svg-icons';
import { PickException } from '../../../type/outbound/picking';
import { toast } from 'react-toastify';

interface PickExceptionModalProps {
  isOpen: boolean;
  toggle: () => void;
  exception: PickException;
  onResolve: (exceptionId: number, resolutionNotes: string) => void;
  onRefresh: () => void;
}

const PickExceptionModal: React.FC<PickExceptionModalProps> = ({
  isOpen,
  toggle,
  exception,
  onResolve,
  onRefresh
}) => {
  const [resolutionNotes, setResolutionNotes] = useState<string>('');

  const handleResolveException = () => {
    if (!resolutionNotes.trim()) {
      toast.error('Please provide resolution notes');
      return;
    }
    
    onResolve(exception.id, resolutionNotes);
    toggle();
  };

  const getStatusBadge = (status: string) => {
    switch (status) {
      case 'pending':
        return <Badge color="warning">Pending</Badge>;
      case 'resolved':
        return <Badge color="success">Resolved</Badge>;
      case 'escalated':
        return <Badge color="danger">Escalated</Badge>;
      default:
        return <Badge color="secondary">{status}</Badge>;
    }
  };

  const formatExceptionType = (type: string) => {
    return type.split('_').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ');
  };

  return (
    <Modal isOpen={isOpen} toggle={toggle} size="lg">
      <ModalHeader toggle={toggle}>
        Pick Exception Details
      </ModalHeader>
      <ModalBody>
        <Card className="mb-3">
          <CardHeader>Exception Information</CardHeader>
          <CardBody>
            <Row>
              <Col md={6}>
                <table className="table table-sm table-borderless">
                  <tbody>
                    <tr>
                      <th style={{ width: '40%' }}>Exception ID:</th>
                      <td>{exception.id}</td>
                    </tr>
                    <tr>
                      <th>Task ID:</th>
                      <td>{exception.task_id}</td>
                    </tr>
                    <tr>
                      <th>Task Item ID:</th>
                      <td>{exception.task_item_id}</td>
                    </tr>
                    <tr>
                      <th>Exception Type:</th>
                      <td>{formatExceptionType(exception.exception_type)}</td>
                    </tr>
                    <tr>
                      <th>Status:</th>
                      <td>{getStatusBadge(exception.resolution_status)}</td>
                    </tr>
                  </tbody>
                </table>
              </Col>
              <Col md={6}>
                <table className="table table-sm table-borderless">
                  <tbody>
                    <tr>
                      <th style={{ width: '40%' }}>Reported By:</th>
                      <td>Employee ID: {exception.reported_by}</td>
                    </tr>
                    <tr>
                      <th>Reported At:</th>
                      <td>{new Date(exception.reported_at).toLocaleString()}</td>
                    </tr>
                    {exception.resolved_by && (
                      <tr>
                        <th>Resolved By:</th>
                        <td>Employee ID: {exception.resolved_by}</td>
                      </tr>
                    )}
                    {exception.resolved_at && (
                      <tr>
                        <th>Resolved At:</th>
                        <td>{new Date(exception.resolved_at).toLocaleString()}</td>
                      </tr>
                    )}
                  </tbody>
                </table>
              </Col>
            </Row>
          </CardBody>
        </Card>

        <Card className="mb-3">
          <CardHeader>Exception Description</CardHeader>
          <CardBody>
            <p className="mb-0">{exception.description}</p>
          </CardBody>
        </Card>

        {exception.resolution_notes && (
          <Card className="mb-3">
            <CardHeader>Resolution Notes</CardHeader>
            <CardBody>
              <p className="mb-0">{exception.resolution_notes}</p>
            </CardBody>
          </Card>
        )}

        {exception.resolution_status === 'pending' && (
          <Card>
            <CardHeader>Resolve Exception</CardHeader>
            <CardBody>
              <FormGroup>
                <Label for="resolutionNotes">Resolution Notes</Label>
                <Input
                  type="textarea"
                  id="resolutionNotes"
                  value={resolutionNotes}
                  onChange={(e) => setResolutionNotes(e.target.value)}
                  rows={4}
                  placeholder="Describe how the exception was resolved..."
                />
              </FormGroup>
            </CardBody>
          </Card>
        )}
      </ModalBody>
      <ModalFooter>
        {exception.resolution_status === 'pending' ? (
          <>
            <Button color="success" onClick={handleResolveException}>
              <FontAwesomeIcon icon={faCheck} className="me-1" /> Resolve Exception
            </Button>
            <Button color="secondary" onClick={toggle}>
              <FontAwesomeIcon icon={faTimes} className="me-1" /> Cancel
            </Button>
          </>
        ) : (
          <Button color="secondary" onClick={toggle}>Close</Button>
        )}
      </ModalFooter>
    </Modal>
  );
};

export default PickExceptionModal;