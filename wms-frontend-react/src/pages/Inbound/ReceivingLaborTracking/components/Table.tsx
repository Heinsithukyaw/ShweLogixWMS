import React, { useState, useMemo } from 'react'
import { ReceivingLaborTrackingType } from '../../../../type/inbound/receivingLaborTrackingType'
import AdvancedDataTable from '../../../../components/ui/dataTable'
import { TableColumn } from 'react-data-table-component'
import { showConfirm } from '../../../../utils/alert'
import Input from '../../../../components/form/input/InputField'
import { useAppDispatch } from '../../../../store/hook'
import ReceivingLaborTrackingViewModal from '../components/ViewModal'
import ReceivingLaborTrackingUpdateModal from '../components/UpdateModal'
import { getContentData } from '../../../../store/features/inbound/ReceivingLaborTrackingSlice'
import { deleteReceivingLaborTracking } from '../services/receivingLaborTrackingApi'
import Swal from 'sweetalert2'

interface Props {
  isLoading: any
  receivingLaborTrackingLists: ReceivingLaborTrackingType[]
}

const columns: TableColumn<ReceivingLaborTrackingType>[] = [
  {
    name: 'No.',
    selector: (_row: any, index: any) => index + 1,
    width: '70px',
    sortable: false,
  },
  {
    name: 'Code',
    selector: (row) => row.labor_entry_code,
    sortable: true,
  },
  {
    name: 'Shipment Code',
    selector: (row) => row.inbound_shipment_code,
    sortable: true,
  },
  {
    name: 'Task Type',
    selector: (row) => row.task_type || '-',
    sortable: true,
  },
  {
    name: 'Start Time',
    selector: (row) => row.start_time || '-',
    sortable: true,
  },
  {
    name: 'End Time',
    selector: (row) => row.end_time || '-',
    sortable: true,
  },
  {
    name: 'Duration (min)',
    selector: (row) => row.duration_min || '-',
    sortable: true,
  },
  {
    name: 'Items Processed',
    selector: (row) => row.items_processed || '-',
    sortable: true,
  },
  {
    name: 'Pallets Processed',
    selector: (row) => row.pallets_processed || '-',
    sortable: true,
  },
  {
    name: 'Items/Min',
    selector: (row) => row.items_min || '-',
    sortable: true,
  },
  {
    name: 'Version Control',
    selector: (row) => row.version_control || '-',
    sortable: true,
  },
  {
    name: 'Status',
    cell: (row) => (
      <span
        className={`px-2 py-1 text-xs font-semibold rounded-full ${
          row.status === 0
            ? 'bg-red-100 text-red-700'
            :  'bg-green-100 text-green-700'
        }`}
      >
        {row.status === 0
          ? 'In Active'
          : 'Active'}
      </span>
    ),
    sortable: true,
  },
]

const ReceivingLaborTrackingTable: React.FC<Props> = ({
  receivingLaborTrackingLists,
}) => {
  const dispatch = useAppDispatch()
  const [filterText, setFilterText] = useState('')
  const [isViewOpen, setIsViewOpen] = useState(false)
  const [isUpdateOpen, setIsUpdateOpen] = useState(false)

  const filteredData = useMemo(() => {
    if (!filterText) return receivingLaborTrackingLists

    return receivingLaborTrackingLists.filter((item: any) =>
      Object.values(item).some(
        (val) =>
          val && val.toString().toLowerCase().includes(filterText.toLowerCase())
      )
    )
  }, [filterText, receivingLaborTrackingLists])

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
      const complete = dispatch(deleteReceivingLaborTracking(row.id))
      if (!complete) {
        Swal.fire({
          title: 'Error!',
          text: 'Failed to delete item.',
          icon: 'error',
        })
      } else {
        Swal.fire({
          title: 'Deleted!',
          text: 'Receiving Labor Tracking has been deleted.',
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
                placeholder="Search Receiving Labor Trackings…"
                value={filterText}
                onChange={(e) => setFilterText(e.target.value)}
                className="w-full max-w-sm"
              />
            </div>
          }
        />
      </div>
      {isViewOpen && (
        <ReceivingLaborTrackingViewModal
          isViewOpen={isViewOpen}
          handleCloseModal={handleCloseModal}
        />
      )}
      {isUpdateOpen && (
        <ReceivingLaborTrackingUpdateModal
          isUpdateOpen={isUpdateOpen}
          handleCloseModal={handleCloseModal}
        />
      )}
    </>
  )
}

export default ReceivingLaborTrackingTable
