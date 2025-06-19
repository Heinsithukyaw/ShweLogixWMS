import React, { useState, useMemo } from 'react'
import { CrossDockingTaskType } from '../../../../type/inbound/crossDockingTaskType'
import AdvancedDataTable from '../../../../components/ui/dataTable'
import { TableColumn } from 'react-data-table-component'
import { showConfirm } from '../../../../utils/alert'
import Input from '../../../../components/form/input/InputField'
import { useAppDispatch } from '../../../../store/hook'
import CrossDockingTaskViewModal from '../components/ViewModal'
import CrossDockingTaskUpdateModal from '../components/UpdateModal'
import { getContentData } from '../../../../store/features/inbound/CrossDockingTaskSlice'
import { deleteCrossDockingTask } from '../services/crossDockingTaskApi'
import Swal from 'sweetalert2'

interface Props {
  isLoading: any
  crossDockingTaskLists: CrossDockingTaskType[]
}

const columns: TableColumn<CrossDockingTaskType>[] = [
  {
    name: 'No.',
    selector: (_row: any, index: any) => index + 1,
    width: '70px',
    sortable: false,
  },
  {
    name: 'Code',
    selector: (row) => row.exception_code,
    sortable: true,
  },
  {
    name: 'ASN Code',
    selector: (row) => row.asn_code,
    sortable: true,
  },
  {
    name: 'ASN Detail',
    selector: (row) => row.asn_detail_code || '-',
    sortable: true,
  },
  {
    name: 'Item',
    selector: (row) => row.item_code || '-',
    sortable: true,
  },
  {
    name: 'Item Description',
    selector: (row) => row.item_description || '-',
    sortable: true,
  },
  {
    name: 'Outbound',
    selector: (row) => row.outbound_shipment_id || '-',
    sortable: true,
  },
  {
    name: 'Quantity',
    selector: (row) => row.qty || '-',
    sortable: true,
  },
  {
    name: 'Source Location',
    selector: (row) => row.source_location_code || '-',
    sortable: true,
  },
  {
    name: 'Destination Location',
    selector: (row) => row.destination_location_code || '-',
    sortable: true,
  },
  {
    name: 'Assigned To',
    selector: (row) => row.assigned_to_code || '-',
    sortable: true,
  },
  {
    name: 'Created Date',
    selector: (row) => row.created_date || '-',
    sortable: true,
  },
  {
    name: 'Start Time',
    selector: (row) => row.start_time || '-',
    sortable: true,
  },
  {
    name: 'Complete Time',
    selector: (row) => row.complete_time || '-',
    sortable: true,
  },
  {
    name: 'Priority',
    selector: (row) => row.priority || '-',
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
          ? 'Pending'
          : row.status === 1
          ? 'In Progress'
          : row.status === 2
          ? 'Completed'
          : 'Delayed'}
      </span>
    ),
    sortable: true,
  },
]

const CrossDockingTaskTable: React.FC<Props> = ({
  crossDockingTaskLists,
}) => {
  const dispatch = useAppDispatch()
  const [filterText, setFilterText] = useState('')
  const [isViewOpen, setIsViewOpen] = useState(false)
  const [isUpdateOpen, setIsUpdateOpen] = useState(false)

  const filteredData = useMemo(() => {
    if (!filterText) return crossDockingTaskLists

    return crossDockingTaskLists.filter((item: any) =>
      Object.values(item).some(
        (val) =>
          val && val.toString().toLowerCase().includes(filterText.toLowerCase())
      )
    )
  }, [filterText, crossDockingTaskLists])

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
      const complete = dispatch(deleteCrossDockingTask(row.id))
      if (!complete) {
        Swal.fire({
          title: 'Error!',
          text: 'Failed to delete item.',
          icon: 'error',
        })
      } else {
        Swal.fire({
          title: 'Deleted!',
          text: 'CrossDocking Task has been deleted.',
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
                placeholder="Search CrossDocking Tasks…"
                value={filterText}
                onChange={(e) => setFilterText(e.target.value)}
                className="w-full max-w-sm"
              />
            </div>
          }
        />
      </div>
      {isViewOpen && (
        <CrossDockingTaskViewModal
          isViewOpen={isViewOpen}
          handleCloseModal={handleCloseModal}
        />
      )}
      {isUpdateOpen && (
        <CrossDockingTaskUpdateModal
          isUpdateOpen={isUpdateOpen}
          handleCloseModal={handleCloseModal}
        />
      )}
    </>
  )
}

export default CrossDockingTaskTable
