import React, { useState, useMemo } from 'react'
import { ShipmentDetailType } from '../../../../type/inbound/shipmentDetailType'
import AdvancedDataTable from '../../../../components/ui/dataTable'
import { TableColumn } from 'react-data-table-component'
import { showConfirm } from '../../../../utils/alert'
import Input from '../../../../components/form/input/InputField'
import { useAppDispatch } from '../../../../store/hook'
import ShipmentDetailViewModal from '../components/ViewModal'
import ShipmentDetailUpdateModal from '../components/UpdateModal'
import { getContentData } from '../../../../store/features/inbound/ShipmentDetailSlice'
import { deleteShipmentDetail } from '../services/shipmentDetailApi'
import Swal from 'sweetalert2'

interface Props {
  isLoading: any
  shipmentDetailLists: ShipmentDetailType[]
}

const columns: TableColumn<ShipmentDetailType>[] = [
  {
    name: 'No.',
    selector: (_row: any, index: any) => index + 1,
    width: '70px',
    sortable: false,
  },
  {
    name: 'Code',
    selector: (row) => row.inbound_detail_code,
    sortable: true,
  },
  {
    name: 'Shipment Code',
    selector: (row) => row.inbound_shipment_code,
    sortable: true,
  },
  {
    name: 'Product Code',
    selector: (row) => row.product_code || '-',
    sortable: true,
  },
  {
    name: 'PO Number',
    selector: (row) => row.purchase_order_number || '-',
    sortable: true,
  },
  {
    name: 'Expected Qty',
    selector: (row) => row.expected_qty || '-',
    sortable: true,
  },
  {
    name: 'Received Qty',
    selector: (row) => row.received_qty || '-',
    sortable: true,
  },
  {
    name: 'Damaged Qty',
    selector: (row) => row.damaged_qty || '-',
    sortable: true,
  },
  {
    name: 'Lot Number',
    selector: (row) => row.lot_number || '-',
    sortable: true,
  },
  {
    name: 'Expiration Date',
    selector: (row) => row.expiration_date || '-',
    sortable: true,
  },
  {
    name: 'Location',
    selector: (row) => row.location_code || '-',
    sortable: true,
  },
  {
    name: 'Received By',
    selector: (row) => row.received_by || '-',
    sortable: true,
  },
  {
    name: 'Received Date',
    selector: (row) => row.received_date || '-',
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
            : 'bg-green-100 text-green-700'
        }`}
      >
        {row.status === 0
          ? 'Exception'
          : row.status === 1
          ? 'Expected'
          : 'Received'}
      </span>
    ),
    sortable: true,
  },
]

const ShipmentDetailTable: React.FC<Props> = ({
  shipmentDetailLists,
}) => {
  const dispatch = useAppDispatch()
  const [filterText, setFilterText] = useState('')
  const [isViewOpen, setIsViewOpen] = useState(false)
  const [isUpdateOpen, setIsUpdateOpen] = useState(false)

  const filteredData = useMemo(() => {
    if (!filterText) return shipmentDetailLists

    return shipmentDetailLists.filter((item: any) =>
      Object.values(item).some(
        (val) =>
          val && val.toString().toLowerCase().includes(filterText.toLowerCase())
      )
    )
  }, [filterText, shipmentDetailLists])

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
      const complete = dispatch(deleteShipmentDetail(row.id))
      if (!complete) {
        Swal.fire({
          title: 'Error!',
          text: 'Failed to delete item.',
          icon: 'error',
        })
      } else {
        Swal.fire({
          title: 'Deleted!',
          text: 'Shipment Detail has been deleted.',
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
                placeholder="Search Shipment Details…"
                value={filterText}
                onChange={(e) => setFilterText(e.target.value)}
                className="w-full max-w-sm"
              />
            </div>
          }
        />
      </div>
      {isViewOpen && (
        <ShipmentDetailViewModal
          isViewOpen={isViewOpen}
          handleCloseModal={handleCloseModal}
        />
      )}
      {isUpdateOpen && (
        <ShipmentDetailUpdateModal
          isUpdateOpen={isUpdateOpen}
          handleCloseModal={handleCloseModal}
        />
      )}
    </>
  )
}

export default ShipmentDetailTable
