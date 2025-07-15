import React, { useState, useMemo } from 'react'
import { ReceivingEquipmentType } from '../../../../type/inbound/receivingEquipmentType'
import AdvancedDataTable from '../../../../components/ui/dataTable'
import { TableColumn } from 'react-data-table-component'
import { showConfirm } from '../../../../utils/alert'
import Input from '../../../../components/form/input/InputField'
import { useAppDispatch } from '../../../../store/hook'
import ReceivingEquipmentViewModal from '../components/ViewModal'
import ReceivingEquipmentUpdateModal from '../components/UpdateModal'
import { getContentData } from '../../../../store/features/inbound/ReceivingEquipmentSlice'
import { deleteReceivingEquipment } from '../services/receivingEquipmentApi'
import Swal from 'sweetalert2'

interface Props {
  isLoading: any
  receivingEquipmentLists: ReceivingEquipmentType[]
}

const columns: TableColumn<ReceivingEquipmentType>[] = [
  {
    name: 'No.',
    selector: (_row: any, index: any) => index + 1,
    width: '70px',
    sortable: false,
  },
  {
    name: 'Code',
    selector: (row) => row.receiving_equipment_code,
    sortable: true,
  },
  {
    name: 'Name',
    selector: (row) => row.receiving_equipment_name,
    sortable: true,
  },
  {
    name: 'Type',
    selector: (row) => row.receiving_equipment_type || '-',
    sortable: true,
  },
  {
    name: 'Assigned To',
    selector: (row) => row.assigned_to_code || '-',
    sortable: true,
  },
  {
    name: 'Last Maintenance Date',
    selector: (row) => row.last_maintenance_date || '-',
    sortable: true,
  },
  {
    name: 'Days Since Maintenance',
    selector: (row) => row.days_since_maintenance || '-',
    sortable: true,
  },
  {
    name: 'Version Control',
    selector: (row) => row.days_since_maintenance || '-',
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
          ? 'In Use'
          : row.status === 1
          ? 'Maintenance'
          : 'Available'}
      </span>
    ),
    sortable: true,
  },
]

const ReceivingEquipmentTable: React.FC<Props> = ({
  receivingEquipmentLists,
}) => {
  const dispatch = useAppDispatch()
  const [filterText, setFilterText] = useState('')
  const [isViewOpen, setIsViewOpen] = useState(false)
  const [isUpdateOpen, setIsUpdateOpen] = useState(false)

  const filteredData = useMemo(() => {
    if (!filterText) return receivingEquipmentLists

    return receivingEquipmentLists.filter((item: any) =>
      Object.values(item).some(
        (val) =>
          val && val.toString().toLowerCase().includes(filterText.toLowerCase())
      )
    )
  }, [filterText, receivingEquipmentLists])

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
      const complete = dispatch(deleteReceivingEquipment(row.id))
      if (!complete) {
        Swal.fire({
          title: 'Error!',
          text: 'Failed to delete item.',
          icon: 'error',
        })
      } else {
        Swal.fire({
          title: 'Deleted!',
          text: 'Receiving Equipment has been deleted.',
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
                placeholder="Search Receiving Equipments…"
                value={filterText}
                onChange={(e) => setFilterText(e.target.value)}
                className="w-full max-w-sm"
              />
            </div>
          }
        />
      </div>
      {isViewOpen && (
        <ReceivingEquipmentViewModal
          isViewOpen={isViewOpen}
          handleCloseModal={handleCloseModal}
        />
      )}
      {isUpdateOpen && (
        <ReceivingEquipmentUpdateModal
          isUpdateOpen={isUpdateOpen}
          handleCloseModal={handleCloseModal}
        />
      )}
    </>
  )
}

export default ReceivingEquipmentTable
