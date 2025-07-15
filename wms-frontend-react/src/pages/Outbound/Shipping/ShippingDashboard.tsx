import { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import DefaultLayout from '../../../layout/DefaultLayout';
import Breadcrumb from '../../../components/Breadcrumb';
import { Card, CardBody, CardHeader } from '../../../components/card';
import { Button, Badge } from '../../../components/ui';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '../../../components/ui/table';
import { useToast } from '../../../hooks/useToast';
import { fetchShipments } from '../../../services/shippingService';

interface Shipment {
  id: number;
  shipment_number: string;
  sales_order_ids: number[];
  customer_id: number;
  shipping_carrier_id: number;
  service_level: string;
  shipment_status: string;
  shipment_type: string;
  tracking_number: string;
  total_weight_kg: number;
  total_volume_cm3: number;
  total_cartons: number;
  shipping_cost: number;
  ship_date: string;
  expected_delivery_date: string;
  actual_delivery_date: string | null;
  customer?: {
    id: number;
    business_name: string;
  };
  carrier?: {
    id: number;
    carrier_name: string;
  };
}

const ShippingDashboard = () => {
  const [shipments, setShipments] = useState<Shipment[]>([]);
  const [loading, setLoading] = useState<boolean>(true);
  const { toast } = useToast();
  const navigate = useNavigate();

  useEffect(() => {
    const getShipments = async () => {
      try {
        setLoading(true);
        const response = await fetchShipments();
        if (response.success) {
          setShipments(response.data);
        } else {
          toast({
            title: 'Error',
            description: 'Failed to fetch shipments',
            variant: 'destructive',
          });
        }
      } catch (error) {
        console.error('Error fetching shipments:', error);
        toast({
          title: 'Error',
          description: 'An unexpected error occurred',
          variant: 'destructive',
        });
      } finally {
        setLoading(false);
      }
    };

    getShipments();
  }, [toast]);

  const getStatusBadgeColor = (status: string) => {
    switch (status) {
      case 'planned':
        return 'default';
      case 'ready':
        return 'primary';
      case 'picked_up':
        return 'warning';
      case 'in_transit':
        return 'info';
      case 'delivered':
        return 'success';
      case 'exception':
        return 'danger';
      default:
        return 'default';
    }
  };

  const handleCreateShipment = () => {
    navigate('/outbound/shipping/shipments/create');
  };

  const handleViewShipment = (id: number) => {
    navigate(`/outbound/shipping/shipments/${id}`);
  };

  const handleGenerateLabels = (id: number) => {
    navigate(`/outbound/shipping/shipments/${id}/labels`);
  };

  const handleGenerateDocuments = (id: number) => {
    navigate(`/outbound/shipping/shipments/${id}/documents`);
  };

  return (
    <DefaultLayout>
      <Breadcrumb pageName="Shipping Dashboard" />

      <div className="grid grid-cols-1 gap-4 md:grid-cols-2 md:gap-6 xl:grid-cols-4 2xl:gap-7.5">
        <Card className="bg-primary text-white">
          <CardBody>
            <div className="flex items-center justify-between">
              <div>
                <h4 className="text-title-md font-bold text-white">
                  {shipments.filter(shipment => shipment.shipment_status === 'planned').length}
                </h4>
                <span className="text-sm font-medium">Planned Shipments</span>
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
                  {shipments.filter(shipment => shipment.shipment_status === 'ready').length}
                </h4>
                <span className="text-sm font-medium">Ready for Pickup</span>
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

        <Card className="bg-info text-white">
          <CardBody>
            <div className="flex items-center justify-between">
              <div>
                <h4 className="text-title-md font-bold text-white">
                  {shipments.filter(shipment => shipment.shipment_status === 'in_transit').length}
                </h4>
                <span className="text-sm font-medium">In Transit</span>
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
                  {shipments.filter(shipment => shipment.shipment_status === 'delivered').length}
                </h4>
                <span className="text-sm font-medium">Delivered</span>
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

      <div className="mt-4 md:mt-6 2xl:mt-7.5">
        <Card>
          <CardHeader className="flex flex-col items-start justify-between border-b border-stroke px-6.5 py-4 dark:border-strokedark md:flex-row md:items-center">
            <div>
              <h3 className="text-xl font-semibold text-black dark:text-white">
                Shipments
              </h3>
              <p className="text-sm text-body">Manage your shipments</p>
            </div>
            <Button onClick={handleCreateShipment} className="mt-4 md:mt-0">
              Create New Shipment
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
                      <TableHead>Shipment #</TableHead>
                      <TableHead>Customer</TableHead>
                      <TableHead>Carrier</TableHead>
                      <TableHead>Service Level</TableHead>
                      <TableHead>Status</TableHead>
                      <TableHead>Ship Date</TableHead>
                      <TableHead>Tracking #</TableHead>
                      <TableHead>Actions</TableHead>
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    {shipments.length === 0 ? (
                      <TableRow>
                        <TableCell colSpan={8} className="text-center py-4">
                          No shipments found
                        </TableCell>
                      </TableRow>
                    ) : (
                      shipments.map((shipment) => (
                        <TableRow key={shipment.id}>
                          <TableCell>{shipment.shipment_number}</TableCell>
                          <TableCell>{shipment.customer?.business_name || 'N/A'}</TableCell>
                          <TableCell>{shipment.carrier?.carrier_name || 'N/A'}</TableCell>
                          <TableCell>{shipment.service_level}</TableCell>
                          <TableCell>
                            <Badge variant={getStatusBadgeColor(shipment.shipment_status)}>
                              {shipment.shipment_status.replace('_', ' ')}
                            </Badge>
                          </TableCell>
                          <TableCell>{new Date(shipment.ship_date).toLocaleDateString()}</TableCell>
                          <TableCell>{shipment.tracking_number || 'Not assigned'}</TableCell>
                          <TableCell>
                            <div className="flex space-x-2">
                              <Button
                                variant="outline"
                                size="sm"
                                onClick={() => handleViewShipment(shipment.id)}
                              >
                                View
                              </Button>
                              <Button
                                variant="outline"
                                size="sm"
                                onClick={() => handleGenerateLabels(shipment.id)}
                              >
                                Labels
                              </Button>
                              <Button
                                variant="outline"
                                size="sm"
                                onClick={() => handleGenerateDocuments(shipment.id)}
                              >
                                Docs
                              </Button>
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

export default ShippingDashboard;