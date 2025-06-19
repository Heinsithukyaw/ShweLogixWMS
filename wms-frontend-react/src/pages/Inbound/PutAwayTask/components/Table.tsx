import React, { useState, useMemo } from 'react'
import { PutAwayTaskType } from '../../../../type/inbound/putAwayTaskType'
import AdvancedDataTable from '../../../../components/ui/dataTable'
import { TableColumn } from 'react-data-table-component'
import { showConfirm } from '../../../../utils/alert'
import Input from '../../../../components/form/input/InputField'
import { useAppDispatch } from '../../../../store/hook'
import PutAwayTaskViewModal from '../components/ViewModal'
import PutAwayTaskUpdateModal from '../components/UpdateModal'
import { getContentData } from '../../../../store/features/inbound/PutAwayTaskSlice'
import { deletePutAwayTask } from '../services/putAwayTaskApi'
import Swal from 'sweetalert2'

interface Props {
  isLoading: any
  putAwayTaskLists: PutAwayTaskType[]
}

const columns: TableColumn<PutAwayTaskType>[] = [
  {
    name: 'No.',
    selector: (_row: any, index: any) => index + 1,
    width: '70px',
    sortable: false,
  },
  {
    name: 'Code',
    selector: (row) => row.put_away_task_code,
    sortable: true,
  },
  {
    name: 'Detail Code',
    selector: (row) => row.inbound_shipment_detail_code,
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
    name: 'Due Date',
    selector: (row) => row.due_date || '-',
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
    name: 'Quantity',
    selector: (row) => row.qty || '-',
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
            : 'bg-green-100 text-green-700'
        }`}
      >
        {row.status === 0
          ? 'Pending'
          : row.status === 1
          ? 'In Progress'
          : 'Completed'}
      </span>
    ),
    sortable: true,
  },
]

const PutAwayTaskTable: React.FC<Props> = ({
  putAwayTaskLists,
}) => {
  const dispatch = useAppDispatch()
  const [filterText, setFilterText] = useState('')
  const [isViewOpen, setIsViewOpen] = useState(false)
  const [isUpdateOpen, setIsUpdateOpen] = useState(false)

  const filteredData = useMemo(() => {
    if (!filterText) return putAwayTaskLists

    return putAwayTaskLists.filter((item: any) =>
      Object.values(item).some(
        (val) =>
          val && val.toString().toLowerCase().includes(filterText.toLowerCase())
      )
    )
  }, [filterText, putAwayTaskLists])

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
      const complete = dispatch(deletePutAwayTask(row.id))
      if (!complete) {
        Swal.fire({
          title: 'Error!',
          text: 'Failed to delete item.',
          icon: 'error',
        })
      } else {
        Swal.fire({
          title: 'Deleted!',
          text: 'Putaway Task has been deleted.',
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
                placeholder="Search Putaway Tasks…"
                value={filterText}
                onChange={(e) => setFilterText(e.target.value)}
                className="w-full max-w-sm"
              />
            </div>
          }
        />
      </div>
      {isViewOpen && (
        <PutAwayTaskViewModal
          isViewOpen={isViewOpen}
          handleCloseModal={handleCloseModal}
        />
      )}
      {isUpdateOpen && (
        <PutAwayTaskUpdateModal
          isUpdateOpen={isUpdateOpen}
          handleCloseModal={handleCloseModal}
        />
      )}
    </>
  )
}

export default PutAwayTaskTable
