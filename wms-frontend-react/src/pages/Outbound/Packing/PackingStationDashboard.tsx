import { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import DefaultLayout from '../../../layout/DefaultLayout';
import Breadcrumb from '../../../components/Breadcrumb';
import { Card, CardBody, CardHeader } from '../../../components/card';
import { Button, Badge } from '../../../components/ui';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '../../../components/ui/table';
import { useToast } from '../../../hooks/useToast';
import { fetchPackingStations } from '../../../services/packingService';

interface PackingStation {
  id: number;
  station_code: string;
  station_name: string;
  warehouse_id: number;
  zone_id: number | null;
  station_type: string;
  station_status: string;
  capabilities: any;
  max_weight_kg: number | null;
  equipment_list: any;
  assigned_to: number | null;
  is_automated: boolean;
  warehouse?: {
    id: number;
    warehouse_name: string;
  };
  zone?: {
    id: number;
    zone_name: string;
  };
  employee?: {
    id: number;
    employee_name: string;
  };
}

const PackingStationDashboard = () => {
  const [packingStations, setPackingStations] = useState<PackingStation[]>([]);
  const [loading, setLoading] = useState<boolean>(true);
  const { toast } = useToast();
  const navigate = useNavigate();

  useEffect(() => {
    const getPackingStations = async () => {
      try {
        setLoading(true);
        const response = await fetchPackingStations();
        if (response.success) {
          setPackingStations(response.data);
        } else {
          toast({
            title: 'Error',
            description: 'Failed to fetch packing stations',
            variant: 'destructive',
          });
        }
      } catch (error) {
        console.error('Error fetching packing stations:', error);
        toast({
          title: 'Error',
          description: 'An unexpected error occurred',
          variant: 'destructive',
        });
      } finally {
        setLoading(false);
      }
    };

    getPackingStations();
  }, [toast]);

  const getStatusBadgeColor = (status: string) => {
    switch (status) {
      case 'active':
        return 'success';
      case 'inactive':
        return 'warning';
      case 'maintenance':
        return 'danger';
      default:
        return 'default';
    }
  };

  const handleCreateStation = () => {
    navigate('/outbound/packing/stations/create');
  };

  const handleViewStation = (id: number) => {
    navigate(`/outbound/packing/stations/${id}`);
  };

  const handleAssignEmployee = (id: number) => {
    navigate(`/outbound/packing/stations/${id}/assign`);
  };

  return (
    <DefaultLayout>
      <Breadcrumb pageName="Packing Station Dashboard" />

      <div className="grid grid-cols-1 gap-4 md:grid-cols-2 md:gap-6 xl:grid-cols-4 2xl:gap-7.5">
        <Card className="bg-success text-white">
          <CardBody>
            <div className="flex items-center justify-between">
              <div>
                <h4 className="text-title-md font-bold text-white">
                  {packingStations.filter(station => station.station_status === 'active').length}
                </h4>
                <span className="text-sm font-medium">Active Stations</span>
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
                  {packingStations.filter(station => station.station_status === 'inactive').length}
                </h4>
                <span className="text-sm font-medium">Inactive Stations</span>
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
                  {packingStations.filter(station => station.station_status === 'maintenance').length}
                </h4>
                <span className="text-sm font-medium">Maintenance</span>
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

        <Card className="bg-primary text-white">
          <CardBody>
            <div className="flex items-center justify-between">
              <div>
                <h4 className="text-title-md font-bold text-white">
                  {packingStations.filter(station => station.assigned_to !== null).length}
                </h4>
                <span className="text-sm font-medium">Assigned Stations</span>
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
                Packing Stations
              </h3>
              <p className="text-sm text-body">Manage your packing stations</p>
            </div>
            <Button onClick={handleCreateStation} className="mt-4 md:mt-0">
              Create New Station
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
                      <TableHead>Station Code</TableHead>
                      <TableHead>Station Name</TableHead>
                      <TableHead>Warehouse</TableHead>
                      <TableHead>Zone</TableHead>
                      <TableHead>Type</TableHead>
                      <TableHead>Status</TableHead>
                      <TableHead>Assigned To</TableHead>
                      <TableHead>Actions</TableHead>
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    {packingStations.length === 0 ? (
                      <TableRow>
                        <TableCell colSpan={8} className="text-center py-4">
                          No packing stations found
                        </TableCell>
                      </TableRow>
                    ) : (
                      packingStations.map((station) => (
                        <TableRow key={station.id}>
                          <TableCell>{station.station_code}</TableCell>
                          <TableCell>{station.station_name}</TableCell>
                          <TableCell>{station.warehouse?.warehouse_name || 'N/A'}</TableCell>
                          <TableCell>{station.zone?.zone_name || 'N/A'}</TableCell>
                          <TableCell className="capitalize">{station.station_type}</TableCell>
                          <TableCell>
                            <Badge variant={getStatusBadgeColor(station.station_status)}>
                              {station.station_status}
                            </Badge>
                          </TableCell>
                          <TableCell>
                            {station.employee?.employee_name || 'Unassigned'}
                          </TableCell>
                          <TableCell>
                            <div className="flex space-x-2">
                              <Button
                                variant="outline"
                                size="sm"
                                onClick={() => handleViewStation(station.id)}
                              >
                                View
                              </Button>
                              <Button
                                variant="outline"
                                size="sm"
                                onClick={() => handleAssignEmployee(station.id)}
                              >
                                Assign
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

export default PackingStationDashboard;