import React, { useState, useMemo } from 'react'
import { ShipmentType } from '../../../../type/inbound/shipmentType'
import AdvancedDataTable from '../../../../components/ui/dataTable'
import { TableColumn } from 'react-data-table-component'
import { showConfirm } from '../../../../utils/alert'
import Input from '../../../../components/form/input/InputField'
import { useAppDispatch } from '../../../../store/hook'
import ShipmentViewModal from '../components/ViewModal'
import ShipmentUpdateModal from '../components/UpdateModal'
import { getContentData } from '../../../../store/features/inbound/ShipmentSlice'
import { deleteShipment } from '../services/shipmentApi'
import Swal from 'sweetalert2'

interface Props {
  isLoading: any
  shipmentLists: ShipmentType[]
}

const columns: TableColumn<ShipmentType>[] = [
  {
    name: 'No.',
    selector: (_row: any, index: any) => index + 1,
    width: '70px',
    sortable: false,
  },
  {
    name: 'Code',
    selector: (row) => row.shipment_code,
    sortable: true,
  },
  {
    name: 'Supplier Code',
    selector: (row) => row.supplier_code,
    sortable: true,
  },
  {
    name: 'Carrier Code',
    selector: (row) => row.carrier_code || '-',
    sortable: true,
  },
  {
    name: 'PO Number',
    selector: (row) => row.purchase_order_number || '-',
    sortable: true,
  },
  {
    name: 'Expected Arrival',
    selector: (row) => row.expected_arrival || '-',
    sortable: true,
  },
  {
    name: 'Actual Arrival',
    selector: (row) => row.actual_arrival || '-',
    sortable: true,
  },
  {
    name: 'Staging Location',
    selector: (row) => row.staging_location_code || '-',
    sortable: true,
  },
  {
    name: 'Trailer Number',
    selector: (row) => row.trailer_number || '-',
    sortable: true,
  },
  {
    name: 'Seal Number',
    selector: (row) => row.seal_number || '-',
    sortable: true,
  },
  {
    name: 'Total Pallets',
    selector: (row) => row.total_pallet || '-',
    sortable: true,
  },
  {
    name: 'Total Weight',
    selector: (row) => row.total_weight || '-',
    sortable: true,
  },
  {
    name: 'Status',
    cell: (row) => (
      <span
        className={`px-2 py-1 text-xs font-semibold rounded-full ${
          row.status === 0
            ? 'bg-blue-100 text-blue-700'
            : row.status === 1
            ? 'bg-red-100 text-red-700'
            : row.status === 2
            ? 'bg-yellow-100 text-yellow-700'
            : row.status === 3
            ? 'bg-gray-100 text-gray-700'
            : 'bg-green-100 text-green-700'
        }`}
      >
        {row.status === 0
          ? 'Expected'
          : row.status === 1
          ? 'In Transit'
          : row.status === 2
          ? 'Arrival'
          : row.status === 3
          ? 'Unloaded'
          : 'Received'}
      </span>
    ),
    sortable: true,
  },
]

const ShipmentTable: React.FC<Props> = ({
  shipmentLists,
}) => {
  const dispatch = useAppDispatch()
  const [filterText, setFilterText] = useState('')
  const [isViewOpen, setIsViewOpen] = useState(false)
  const [isUpdateOpen, setIsUpdateOpen] = useState(false)

  const filteredData = useMemo(() => {
    if (!filterText) return shipmentLists

    return shipmentLists.filter((item: any) =>
      Object.values(item).some(
        (val) =>
          val && val.toString().toLowerCase().includes(filterText.toLowerCase())
      )
    )
  }, [filterText, shipmentLists])

  const handleCloseModal = () => {
    setIsViewOpen(false)
    setIsUpdateOpen(false)
  }

  const handleView = (row: any) => {
    setIsViewOpen(true)
    console.log(row.id)
    dispatch(getContentData(row.id))
  }
  const handleEdit = (row:any) => {
    setIsUpdateOpen(true)
    console.log(row.id)
    dispatch(getContentData(row.id))
  }
  const handleDelete = async (row: any) => {
      const confirmed = await showConfirm(
        'Are you sure?',
        'This action cannot be undone.'
      )

      if (!confirmed) return
      const complete = dispatch(deleteShipment(row.id))
      if (!complete) {
        Swal.fire({
          title: 'Error!',
          text: 'Failed to delete item.',
          icon: 'error',
        })
      } else {
        Swal.fire({
          title: 'Deleted!',
          text: 'Shipment has been deleted.',
          icon: 'success',
        })
      }
      
    }

  return (
    <>
      <div style={{ overflowX: 'auto' }}>
        <AdvancedDataTable
          data={filteredData || []}
          columns={columns}
          onView={handleView}
          onEdit={handleEdit}
          onDelete={handleDelete}
          subHeader
          subHeaderComponent={
            <div className="w-full flex items-center justify-between px-0 py-2 bg-muted">
              <Input
                type="text"
                placeholder="Search Inbound Shipments…"
                value={filterText}
                onChange={(e) => setFilterText(e.target.value)}
                className="w-full max-w-sm"
              />
            </div>
          }
        />
      </div>
      {isViewOpen && (
        <ShipmentViewModal
          isViewOpen={isViewOpen}
          handleCloseModal={handleCloseModal}
        />
      )}
      {isUpdateOpen && (
        <ShipmentUpdateModal
          isUpdateOpen={isUpdateOpen}
          handleCloseModal={handleCloseModal}
        />
      )}
    </>
  )
}

export default ShipmentTable
