import React, { useState, useMemo } from 'react'
import { UnloadingSessionType } from '../../../../type/inbound/unloadingSessionType'
import AdvancedDataTable from '../../../../components/ui/dataTable'
import { TableColumn } from 'react-data-table-component'
import { showConfirm } from '../../../../utils/alert'
import Input from '../../../../components/form/input/InputField'
import { useAppDispatch } from '../../../../store/hook'
import UnloadingSessionViewModal from '../components/ViewModal'
import UnloadingSessionUpdateModal from '../components/UpdateModal'
import { getContentData } from '../../../../store/features/inbound/UnloadingSessionSlice'
import { deleteUnloadingSession } from '../services/unloadingSessionApi'
import Swal from 'sweetalert2'

interface Props {
  isLoading: any
  unloadingSessionLists: UnloadingSessionType[]
}

const columns: TableColumn<UnloadingSessionType>[] = [
  {
    name: 'No.',
    selector: (_row: any, index: any) => index + 1,
    width: '70px',
    sortable: false,
  },
  {
    name: 'Code',
    selector: (row) => row.unloading_session_code,
    sortable: true,
  },
  {
    name: 'Shipment Code',
    selector: (row) => row.inbound_shipment_code,
    sortable: true,
  },
  {
    name: 'Dock Code',
    selector: (row) => row.dock_code || '-',
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
    name: 'Supervisor',
    selector: (row) => row.supervisor_code || '-',
    sortable: true,
  },
  {
    name: 'Total Pallets Unloaded',
    selector: (row) => row.total_pallets_unloaded || '-',
    sortable: true,
  },
  {
    name: 'Total Items Unloaded',
    selector: (row) => row.total_items_unloaded || '-',
    sortable: true,
  },
  {
    name: 'Expiration Date',
    selector: (row) => row.expiration_date || '-',
    sortable: true,
  },
  {
    name: 'Equipment Used',
    selector: (row) =>
      Array.isArray(row?.equipment_used_details)
        ? row.equipment_used_details.map((item) => item.mhe_code).join(', ')
        : '-',
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
            : 'bg-yellow-100 text-yellow-700'
        }`}
      >
        {row.status === 0
          ? 'Planned'
          : row.status === 1
          ? 'In Progress'
          : 'Completed'}
      </span>
    ),
    sortable: true,
  },
]

const UnloadingSessionTable: React.FC<Props> = ({
  unloadingSessionLists,
}) => {
  const dispatch = useAppDispatch()
  const [filterText, setFilterText] = useState('')
  const [isViewOpen, setIsViewOpen] = useState(false)
  const [isUpdateOpen, setIsUpdateOpen] = useState(false)

  const filteredData = useMemo(() => {
    if (!filterText) return unloadingSessionLists

    return unloadingSessionLists.filter((item: any) =>
      Object.values(item).some(
        (val) =>
          val && val.toString().toLowerCase().includes(filterText.toLowerCase())
      )
    )
  }, [filterText, unloadingSessionLists])

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
      const complete = dispatch(deleteUnloadingSession(row.id))
      if (!complete) {
        Swal.fire({
          title: 'Error!',
          text: 'Failed to delete item.',
          icon: 'error',
        })
      } else {
        Swal.fire({
          title: 'Deleted!',
          text: 'Unloading Session has been deleted.',
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
        <UnloadingSessionViewModal
          isViewOpen={isViewOpen}
          handleCloseModal={handleCloseModal}
        />
      )}
      {isUpdateOpen && (
        <UnloadingSessionUpdateModal
          isUpdateOpen={isUpdateOpen}
          handleCloseModal={handleCloseModal}
        />
      )}
    </>
  )
}

export default UnloadingSessionTable
