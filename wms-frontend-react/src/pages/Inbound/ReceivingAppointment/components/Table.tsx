import React, { useState, useMemo } from 'react'
import { ReceivingAppointmentType } from '../../../../type/inbound/receivingAppointmentType'
import AdvancedDataTable from '../../../../components/ui/dataTable'
import { TableColumn } from 'react-data-table-component'
import { showConfirm } from '../../../../utils/alert'
import Input from '../../../../components/form/input/InputField'
import { useAppDispatch } from '../../../../store/hook'
import ReceivingAppointmentViewModal from '../components/ViewModal'
import ReceivingAppointmentUpdateModal from '../components/UpdateModal'
import { getContentData } from '../../../../store/features/inbound/ReceivingAppointmentSlice'
import { deleteReceivingAppointment } from '../services/receivingAppointmentApi'
import Swal from 'sweetalert2'

interface Props {
  isLoading: any
  receivingAppointmentLists: ReceivingAppointmentType[]
}

const columns: TableColumn<ReceivingAppointmentType>[] = [
  {
    name: 'No.',
    selector: (_row: any, index: any) => index + 1,
    width: '70px',
    sortable: false,
  },
  {
    name: 'Code',
    selector: (row) => row.appointment_code,
    sortable: true,
  },
  {
    name: 'Shipment Code',
    selector: (row) => row.inbound_shipment_code,
    sortable: true,
  },
  {
    name: 'Supplier Code',
    selector: (row) => row.supplier_code || '-',
    sortable: true,
  },
  {
    name: 'Dock Code',
    selector: (row) => row.dock_code || '-',
    sortable: true,
  },
  {
    name: 'PO Number',
    selector: (row) => row.po_number || '-',
    sortable: true,
  },
  {
    name: 'Scheduled Date',
    selector: (row) => row.scheduled_date || '-',
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
    name: 'Carrier Name',
    selector: (row) => row.carrier_name || '-',
    sortable: true,
  },
  {
    name: 'Driver Name',
    selector: (row) => row.driver_name || '-',
    sortable: true,
  },
  {
    name: 'Driver Ph',
    selector: (row) => row.driver_phone_number || '-',
    sortable: true,
  },
  {
    name: 'Trailer Number',
    selector: (row) => row.trailer_number || '-',
    sortable: true,
  },
  {
    name: 'Estimated Pallets',
    selector: (row) => row.estimated_pallet || '-',
    sortable: true,
  },
  {
    name: 'Status',
    cell: (row) => (
      <span
        className={`px-2 py-1 text-xs font-semibold rounded-full ${
          row.status === 0
            ? 'bg-blue-200 text-blue-700'
            : row.status === 1
            ? 'bg-red-200 text-red-700'
            : row.status === 2
            ? 'bg-yellow-200 text-yellow-700'
            : row.status === 3
            ? 'bg-gray-200 text-gray-700'
            : 'bg-green-200 text-green-700'
        }`}
      >
        {row.status === 0
          ? 'Scheduled'
          : row.status === 1
          ? 'Confirmed'
          : row.status === 2
          ? 'In Progress'
          : row.status === 3
          ?'Completed'
          : 'Cancelled'}
      </span>
    ),
    sortable: true,
  },
]

const ReceivingAppointmentTable: React.FC<Props> = ({
  receivingAppointmentLists,
}) => {
  const dispatch = useAppDispatch()
  const [filterText, setFilterText] = useState('')
  const [isViewOpen, setIsViewOpen] = useState(false)
  const [isUpdateOpen, setIsUpdateOpen] = useState(false)

  const filteredData = useMemo(() => {
    if (!filterText) return receivingAppointmentLists

    return receivingAppointmentLists.filter((item: any) =>
      Object.values(item).some(
        (val) =>
          val && val.toString().toLowerCase().includes(filterText.toLowerCase())
      )
    )
  }, [filterText, receivingAppointmentLists])

  const handleCloseModal = () => {
    setIsViewOpen(false)
    setIsUpdateOpen(false)
  }

  const handleView = (row: any) => {
    setIsViewOpen(true)
    console.log(row.id)
    dispatch(getContentData(row.id))
  }
  const handleEdit = (row: any) => {
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
    const complete = dispatch(deleteReceivingAppointment(row.id))
    if (!complete) {
      Swal.fire({
        title: 'Error!',
        text: 'Failed to delete item.',
        icon: 'error',
      })
    } else {
      Swal.fire({
        title: 'Deleted!',
        text: 'Receiving Appointment has been deleted.',
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
                placeholder="Search Receiving Appointments…"
                value={filterText}
                onChange={(e) => setFilterText(e.target.value)}
                className="w-full max-w-sm"
              />
            </div>
          }
        />
      </div>
      {isViewOpen && (
        <ReceivingAppointmentViewModal
          isViewOpen={isViewOpen}
          handleCloseModal={handleCloseModal}
        />
      )}
      {isUpdateOpen && (
        <ReceivingAppointmentUpdateModal
          isUpdateOpen={isUpdateOpen}
          handleCloseModal={handleCloseModal}
        />
      )}
    </>
  )
}

export default ReceivingAppointmentTable
