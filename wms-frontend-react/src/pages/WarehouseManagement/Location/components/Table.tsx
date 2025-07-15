import React, { useState, useMemo } from 'react'
import { LocationType } from '../../../../type/masterData/locationType'
import AdvancedDataTable from '../../../../components/ui/dataTable'
import { TableColumn } from 'react-data-table-component'
import { showConfirm } from '../../../../utils/alert'
import Input from '../../../../components/form/input/InputField'
import { useAppDispatch } from '../../../../store/hook'
import LocationViewModal from '../components/ViewModal'
import LocationUpdateModal from '../components/UpdateModal'
import { getContentData } from '../../../../store/features/masterData/LocationSlice'
import { deleteLocation } from '../services/locationApi'
import Swal from 'sweetalert2'

interface Props {
  isLoading: any
  locationLists: LocationType[]
}

const columns: TableColumn<LocationType>[] = [
  {
    name: 'No.',
    selector: (_row: any, index: any) => index + 1,
    width: '70px',
    sortable: false,
  },
  {
    name: 'Code',
    selector: (row) => row.location_code,
    sortable: true,
  },
  {
    name: 'Name',
    selector: (row) => row.location_name,
    sortable: true,
  },
  {
    name: 'Type',
    selector: (row) => row.location_type || '-',
    sortable: true,
  },
  {
    name: 'Zone',
    selector: (row) =>
      row.zone_code ? `${row.zone_code} - ${row.zone_type}` : '-',
    sortable: true,
  },
  // {
  //   name: 'Aisle',
  //   selector: (row) => row.aisle || '-',
  //   sortable: true,
  // },
  // {
  //   name: 'Row',
  //   selector: (row) => row.row || '-',
  //   sortable: true,
  // },
  // {
  //   name: 'Level',
  //   selector: (row) => row.level || '-',
  //   sortable: true,
  // },
  // {
  //   name: 'Bin',
  //   selector: (row) => row.bin || '-',
  //   sortable: true,
  // },
  // {
  //   name: 'Capacity',
  //   selector: (row) => row.capacity || '-',
  //   sortable: true,
  // },
  // {
  //   name: 'Capacity Unit',
  //   selector: (row) => row.capacity_unit || '-',
  //   sortable: true,
  // },
  // {
  //   name: 'Restrictions',
  //   selector: (row) => row.restrictions || '-',
  //   sortable: true,
  // },
  // {
  //   name: 'Bar Code',
  //   selector: (row) => row.bar_code || '-',
  //   sortable: true,
  // },
  {
    name: 'Status',
    selector: (row) => row.status || '-',

    sortable: true,
  },
]

const LocationTable: React.FC<Props> = ({
  locationLists,
}) => {
  const dispatch = useAppDispatch()
  const [filterText, setFilterText] = useState('')
  const [isViewOpen, setIsViewOpen] = useState(false)
  const [isUpdateOpen, setIsUpdateOpen] = useState(false)

  const filteredData = useMemo(() => {
    if (!filterText) return locationLists

    return locationLists.filter((item: any) =>
      Object.values(item).some(
        (val) =>
          val && val.toString().toLowerCase().includes(filterText.toLowerCase())
      )
    )
  }, [filterText, locationLists])

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
      const complete = dispatch(deleteLocation(row.id))
      if (!complete) {
        Swal.fire({
          title: 'Error!',
          text: 'Failed to delete item.',
          icon: 'error',
        })
      } else {
        Swal.fire({
          title: 'Deleted!',
          text: 'Location has been deleted.',
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
                placeholder="Search ASN Details…"
                value={filterText}
                onChange={(e) => setFilterText(e.target.value)}
                className="w-full max-w-sm"
              />
            </div>
          }
        />
      </div>
      {isViewOpen && (
        <LocationViewModal
          isViewOpen={isViewOpen}
          handleCloseModal={handleCloseModal}
        />
      )}
      {isUpdateOpen && (
        <LocationUpdateModal
          isUpdateOpen={isUpdateOpen}
          handleCloseModal={handleCloseModal}
        />
      )}
    </>
  )
}

export default LocationTable
