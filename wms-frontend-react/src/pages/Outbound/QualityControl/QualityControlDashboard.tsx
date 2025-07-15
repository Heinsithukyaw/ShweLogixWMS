import { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import DefaultLayout from '../../../layout/DefaultLayout';
import Breadcrumb from '../../../components/Breadcrumb';
import { Card, CardBody, CardHeader } from '../../../components/card';
import { Button, Badge } from '../../../components/ui';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '../../../components/ui/table';
import { useToast } from '../../../hooks/useToast';
import { fetchQualityExceptions, fetchQualityMetrics } from '../../../services/qualityControlService';

interface QualityException {
  id: number;
  exception_number: string;
  checkpoint_id: number;
  sales_order_id: number | null;
  shipment_id: number | null;
  packed_carton_id: number | null;
  exception_type: string;
  exception_severity: string;
  exception_status: string;
  exception_details: any;
  reported_by: number;
  assigned_to: number | null;
  resolution_notes: string | null;
  resolved_at: string | null;
  created_at: string;
  checkpoint?: {
    id: number;
    checkpoint_name: string;
  };
  reporter?: {
    id: number;
    employee_name: string;
  };
  assignee?: {
    id: number;
    employee_name: string;
  };
}

interface QualityMetrics {
  total_checks: number;
  passed_checks: number;
  failed_checks: number;
  pass_rate: number;
  exceptions_by_type: Record<string, number>;
  exceptions_by_severity: Record<string, number>;
  exceptions_by_status: Record<string, number>;
  top_failure_reasons: Array<{ reason: string; count: number }>;
  average_resolution_time: number;
}

const QualityControlDashboard = () => {
  const [exceptions, setExceptions] = useState<QualityException[]>([]);
  const [metrics, setMetrics] = useState<QualityMetrics | null>(null);
  const [loading, setLoading] = useState<boolean>(true);
  const { toast } = useToast();
  const navigate = useNavigate();

  useEffect(() => {
    const getQualityData = async () => {
      try {
        setLoading(true);
        const [exceptionsResponse, metricsResponse] = await Promise.all([
          fetchQualityExceptions(),
          fetchQualityMetrics()
        ]);
        
        if (exceptionsResponse.success) {
          setExceptions(exceptionsResponse.data);
        } else {
          toast({
            title: 'Error',
            description: 'Failed to fetch quality exceptions',
            variant: 'destructive',
          });
        }
        
        if (metricsResponse.success) {
          setMetrics(metricsResponse.data);
        } else {
          toast({
            title: 'Error',
            description: 'Failed to fetch quality metrics',
            variant: 'destructive',
          });
        }
      } catch (error) {
        console.error('Error fetching quality control data:', error);
        toast({
          title: 'Error',
          description: 'An unexpected error occurred',
          variant: 'destructive',
        });
      } finally {
        setLoading(false);
      }
    };

    getQualityData();
  }, [toast]);

  const getSeverityBadgeColor = (severity: string) => {
    switch (severity) {
      case 'critical':
        return 'danger';
      case 'major':
        return 'warning';
      case 'minor':
        return 'info';
      default:
        return 'default';
    }
  };

  const getStatusBadgeColor = (status: string) => {
    switch (status) {
      case 'open':
        return 'danger';
      case 'in_progress':
        return 'warning';
      case 'resolved':
        return 'success';
      default:
        return 'default';
    }
  };

  const handleCreateQualityCheck = () => {
    navigate('/outbound/quality-control/checks/create');
  };

  const handleViewException = (id: number) => {
    navigate(`/outbound/quality-control/exceptions/${id}`);
  };

  const handleResolveException = (id: number) => {
    navigate(`/outbound/quality-control/exceptions/${id}/resolve`);
  };

  return (
    <DefaultLayout>
      <Breadcrumb pageName="Quality Control Dashboard" />

      {metrics && (
        <div className="grid grid-cols-1 gap-4 md:grid-cols-2 md:gap-6 xl:grid-cols-4 2xl:gap-7.5">
          <Card className="bg-primary text-white">
            <CardBody>
              <div className="flex items-center justify-between">
                <div>
                  <h4 className="text-title-md font-bold text-white">
                    {metrics.total_checks}
                  </h4>
                  <span className="text-sm font-medium">Total Quality Checks</span>
                </div>
                <div className="flex h-11.5 w-11.5 items-center justify-center rounded-full bg-meta-2">
                  <svg
                    className="fill-primary"
                    width="22"
                    height="22"
                    viewBox="0 0 22 22"
                    fill="none"
                    xmlns="http://www.w3.org/2000/svg"
                  >
                    <path
                      d="M21 10H1M1 10L10 19M1 10L10 1"
                      stroke="currentColor"
                      strokeWidth="2"
                      strokeLinecap="round"
                      strokeLinejoin="round"
                    />
                  </svg>
                </div>
              </div>
            </CardBody>
          </Card>

          <Card className="bg-success text-white">
            <CardBody>
              <div className="flex items-center justify-between">
                <div>
                  <h4 className="text-title-md font-bold text-white">
                    {metrics.pass_rate.toFixed(1)}%
                  </h4>
                  <span className="text-sm font-medium">Pass Rate</span>
                </div>
                <div className="flex h-11.5 w-11.5 items-center justify-center rounded-full bg-meta-2">
                  <svg
                    className="fill-primary"
                    width="22"
                    height="22"
                    viewBox="0 0 22 22"
                    fill="none"
                    xmlns="http://www.w3.org/2000/svg"
                  >
                    <path
                      d="M21 10H1M1 10L10 19M1 10L10 1"
                      stroke="currentColor"
                      strokeWidth="2"
                      strokeLinecap="round"
                      strokeLinejoin="round"
                    />
                  </svg>
                </div>
              </div>
            </CardBody>
          </Card>

          <Card className="bg-danger text-white">
            <CardBody>
              <div className="flex items-center justify-between">
                <div>
                  <h4 className="text-title-md font-bold text-white">
                    {metrics.exceptions_by_status.open || 0}
                  </h4>
                  <span className="text-sm font-medium">Open Exceptions</span>
                </div>
                <div className="flex h-11.5 w-11.5 items-center justify-center rounded-full bg-meta-2">
                  <svg
                    className="fill-primary"
                    width="22"
                    height="22"
                    viewBox="0 0 22 22"
                    fill="none"
                    xmlns="http://www.w3.org/2000/svg"
                  >
                    <path
                      d="M21 10H1M1 10L10 19M1 10L10 1"
                      stroke="currentColor"
                      strokeWidth="2"
                      strokeLinecap="round"
                      strokeLinejoin="round"
                    />
                  </svg>
                </div>
              </div>
            </CardBody>
          </Card>

          <Card className="bg-warning text-white">
            <CardBody>
              <div className="flex items-center justify-between">
                <div>
                  <h4 className="text-title-md font-bold text-white">
                    {metrics.average_resolution_time ? `${metrics.average_resolution_time.toFixed(1)} hrs` : 'N/A'}
                  </h4>
                  <span className="text-sm font-medium">Avg. Resolution Time</span>
                </div>
                <div className="flex h-11.5 w-11.5 items-center justify-center rounded-full bg-meta-2">
                  <svg
                    className="fill-primary"
                    width="22"
                    height="22"
                    viewBox="0 0 22 22"
                    fill="none"
                    xmlns="http://www.w3.org/2000/svg"
                  >
                    <path
                      d="M21 10H1M1 10L10 19M1 10L10 1"
                      stroke="currentColor"
                      strokeWidth="2"
                      strokeLinecap="round"
                      strokeLinejoin="round"
                    />
                  </svg>
                </div>
              </div>
            </CardBody>
          </Card>
        </div>
      )}

      <div className="mt-4 md:mt-6 2xl:mt-7.5">
        <Card>
          <CardHeader className="flex flex-col items-start justify-between border-b border-stroke px-6.5 py-4 dark:border-strokedark md:flex-row md:items-center">
            <div>
              <h3 className="text-xl font-semibold text-black dark:text-white">
                Quality Exceptions
              </h3>
              <p className="text-sm text-body">Manage quality exceptions</p>
            </div>
            <Button onClick={handleCreateQualityCheck} className="mt-4 md:mt-0">
              Perform Quality Check
            </Button>
          </CardHeader>
          <CardBody>
            {loading ? (
              <div className="flex items-center justify-center py-8">
                <div className="h-8 w-8 animate-spin rounded-full border-4 border-solid border-primary border-t-transparent"></div>
              </div>
            ) : (
              <div className="overflow-x-auto">
                <Table>
                  <TableHeader>
                    <TableRow>
                      <TableHead>Exception #</TableHead>
                      <TableHead>Checkpoint</TableHead>
                      <TableHead>Type</TableHead>
                      <TableHead>Severity</TableHead>
                      <TableHead>Status</TableHead>
                      <TableHead>Reported By</TableHead>
                      <TableHead>Assigned To</TableHead>
                      <TableHead>Created At</TableHead>
                      <TableHead>Actions</TableHead>
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    {exceptions.length === 0 ? (
                      <TableRow>
                        <TableCell colSpan={9} className="text-center py-4">
                          No quality exceptions found
                        </TableCell>
                      </TableRow>
                    ) : (
                      exceptions.map((exception) => (
                        <TableRow key={exception.id}>
                          <TableCell>{exception.exception_number}</TableCell>
                          <TableCell>{exception.checkpoint?.checkpoint_name || 'N/A'}</TableCell>
                          <TableCell className="capitalize">{exception.exception_type.replace('_', ' ')}</TableCell>
                          <TableCell>
                            <Badge variant={getSeverityBadgeColor(exception.exception_severity)}>
                              {exception.exception_severity}
                            </Badge>
                          </TableCell>
                          <TableCell>
                            <Badge variant={getStatusBadgeColor(exception.exception_status)}>
                              {exception.exception_status.replace('_', ' ')}
                            </Badge>
                          </TableCell>
                          <TableCell>{exception.reporter?.employee_name || 'N/A'}</TableCell>
                          <TableCell>{exception.assignee?.employee_name || 'Unassigned'}</TableCell>
                          <TableCell>{new Date(exception.created_at).toLocaleString()}</TableCell>
                          <TableCell>
                            <div className="flex space-x-2">
                              <Button
                                variant="outline"
                                size="sm"
                                onClick={() => handleViewException(exception.id)}
                              >
                                View
                              </Button>
                              {exception.exception_status !== 'resolved' && (
                                <Button
                                  variant="outline"
                                  size="sm"
                                  onClick={() => handleResolveException(exception.id)}
                                >
                                  Resolve
                                </Button>
                              )}
                            </div>
                          </TableCell>
                        </TableRow>
                      ))
                    )}
                  </TableBody>
                </Table>
              </div>
            )}
          </CardBody>
        </Card>
      </div>
    </DefaultLayout>
  );
};

export default QualityControlDashboard;