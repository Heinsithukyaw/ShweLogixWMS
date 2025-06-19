import React, { useState, useMemo } from 'react'
import { ReceivingExceptionType } from '../../../../type/inbound/receivingExceptionType'
import AdvancedDataTable from '../../../../components/ui/dataTable'
import { TableColumn } from 'react-data-table-component'
import { showConfirm } from '../../../../utils/alert'
import Input from '../../../../components/form/input/InputField'
import { useAppDispatch } from '../../../../store/hook'
import ReceivingExceptionViewModal from '../components/ViewModal'
import ReceivingExceptionUpdateModal from '../components/UpdateModal'
import { getContentData } from '../../../../store/features/inbound/ReceivingExceptionSlice'
import { deleteReceivingException } from '../services/receivingExceptionApi'
import Swal from 'sweetalert2'

interface Props {
  isLoading: any
  receivingExceptionLists: ReceivingExceptionType[]
}

const columns: TableColumn<ReceivingExceptionType>[] = [
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
    name: 'Type',
    selector: (row) => row.exception_type || '-',
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
    name: 'Severity',
    selector: (row) => row.severity || '-',
    sortable: true,
  },
  {
    name: 'Reported By',
    selector: (row) => row.reported_by_code || '-',
    sortable: true,
  },
  {
    name: 'Assigned To',
    selector: (row) => row.assigned_to_code || '-',
    sortable: true,
  },
  {
    name: 'Reported Date',
    selector: (row) => row.reported_date || '-',
    sortable: true,
  },
  {
    name: 'Resolved Date',
    selector: (row) => row.resolved_date || '-',
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
          ? 'Pending Info'
          : row.status === 1
          ? 'In Progress'
          : row.status === 2
          ? 'Open'
          : 'Resolved'}
      </span>
    ),
    sortable: true,
  },
]

const ReceivingExceptionTable: React.FC<Props> = ({
  receivingExceptionLists,
}) => {
  const dispatch = useAppDispatch()
  const [filterText, setFilterText] = useState('')
  const [isViewOpen, setIsViewOpen] = useState(false)
  const [isUpdateOpen, setIsUpdateOpen] = useState(false)

  const filteredData = useMemo(() => {
    if (!filterText) return receivingExceptionLists

    return receivingExceptionLists.filter((item: any) =>
      Object.values(item).some(
        (val) =>
          val && val.toString().toLowerCase().includes(filterText.toLowerCase())
      )
    )
  }, [filterText, receivingExceptionLists])

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
      const complete = dispatch(deleteReceivingException(row.id))
      if (!complete) {
        Swal.fire({
          title: 'Error!',
          text: 'Failed to delete item.',
          icon: 'error',
        })
      } else {
        Swal.fire({
          title: 'Deleted!',
          text: 'Receiving Exception has been deleted.',
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
                placeholder="Search Receiving Exceptions…"
                value={filterText}
                onChange={(e) => setFilterText(e.target.value)}
                className="w-full max-w-sm"
              />
            </div>
          }
        />
      </div>
      {isViewOpen && (
        <ReceivingExceptionViewModal
          isViewOpen={isViewOpen}
          handleCloseModal={handleCloseModal}
        />
      )}
      {isUpdateOpen && (
        <ReceivingExceptionUpdateModal
          isUpdateOpen={isUpdateOpen}
          handleCloseModal={handleCloseModal}
        />
      )}
    </>
  )
}

export default ReceivingExceptionTable
