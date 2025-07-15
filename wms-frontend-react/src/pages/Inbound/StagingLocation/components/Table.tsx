import React, { useState, useMemo } from 'react'
import { StagingLocationType } from '../../../../type/inbound/stagingLocationType'
import AdvancedDataTable from '../../../../components/ui/dataTable'
import { TableColumn } from 'react-data-table-component'
import { showConfirm } from '../../../../utils/alert'
import Input from '../../../../components/form/input/InputField'
import { useAppDispatch } from '../../../../store/hook'
import StagingLocationViewModal from '../components/ViewModal'
import StagingLocationUpdateModal from '../components/UpdateModal'
import { getContentData } from '../../../../store/features/inbound/StagingLocationSlice'
import { deleteStagingLocation } from '../services/stagingLocationApi'
import Swal from 'sweetalert2'

interface Props {
  isLoading: any
  stagingLocationLists: StagingLocationType[]
}

const columns: TableColumn<StagingLocationType>[] = [
  {
    name: 'No.',
    selector: (_row: any, index: any) => index + 1,
    width: '70px',
    sortable: false,
  },
  {
    name: 'Code',
    selector: (row) => row.staging_location_code,
    sortable: true,
  },
  {
    name: 'Name',
    selector: (row) => row.staging_location_name,
    sortable: true,
  },
  {
    name: 'Type',
    selector: (row) => row.type,
    sortable: true,
  },
  {
    name: 'Warehouse',
    selector: (row) => row.warehouse_code,
    sortable: true,
  },
  {
    name: 'Area',
    selector: (row) => row.area_code,
    sortable: true,
  },
  {
    name: 'Zone',
    selector: (row) => row.zone_code,
    sortable: true,
  },
  {
    name: 'Capacity',
    selector: (row) => row.capacity || '-',
    sortable: true,
  },
  {
    name: 'Current Usage',
    selector: (row) =>
      row?.current_usage != null && row?.capacity != null
        ? `${row.current_usage}/${row.capacity}`
        : '-',
    sortable: true,
  },
  {
    name: 'Last Updated',
    selector: (row) => row.last_updated || '-',
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
          ? 'In Active'
          : row.status === 1
          ? 'Maintenance'
          : 'Active'}
      </span>
    ),
    sortable: true,
  },
]

const StagingLocationTable: React.FC<Props> = ({
  stagingLocationLists,
}) => {
  const dispatch = useAppDispatch()
  const [filterText, setFilterText] = useState('')
  const [isViewOpen, setIsViewOpen] = useState(false)
  const [isUpdateOpen, setIsUpdateOpen] = useState(false)

  const filteredData = useMemo(() => {
    if (!filterText) return stagingLocationLists

    return stagingLocationLists.filter((item: any) =>
      Object.values(item).some(
        (val) =>
          val && val.toString().toLowerCase().includes(filterText.toLowerCase())
      )
    )
  }, [filterText, stagingLocationLists])

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
      const complete = dispatch(deleteStagingLocation(row.id))
      if (!complete) {
        Swal.fire({
          title: 'Error!',
          text: 'Failed to delete item.',
          icon: 'error',
        })
      } else {
        Swal.fire({
          title: 'Deleted!',
          text: 'Staging Location has been deleted.',
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
                placeholder="Search Staging Locations…"
                value={filterText}
                onChange={(e) => setFilterText(e.target.value)}
                className="w-full max-w-sm"
              />
            </div>
          }
        />
      </div>
      {isViewOpen && (
        <StagingLocationViewModal
          isViewOpen={isViewOpen}
          handleCloseModal={handleCloseModal}
        />
      )}
      {isUpdateOpen && (
        <StagingLocationUpdateModal
          isUpdateOpen={isUpdateOpen}
          handleCloseModal={handleCloseModal}
        />
      )}
    </>
  )
}

export default StagingLocationTable
