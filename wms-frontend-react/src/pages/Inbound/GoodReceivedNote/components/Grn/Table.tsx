import React, { useState, useMemo } from 'react'
import { GrnType } from '../../../../../type/inbound/grnType'
import AdvancedDataTable from '../../../../../components/ui/dataTable'
import { TableColumn } from 'react-data-table-component'
import { showConfirm } from '../../../../../utils/alert'
import Input from '../../../../../components/form/input/InputField'
import { useAppDispatch } from '../../../../../store/hook'
// import GrnViewForm from '../../components/Grn/ViewForm'
import { getContentData } from '../../../../../store/features/inbound/GrnSlice'
import { getItemContentData } from '../../../../../store/features/inbound/GrnItemSlice'
import { deleteGrn } from '../../services/grnApi'
import Swal from 'sweetalert2'

interface Props {
  isLoading: any
  grnLists: GrnType[]
  handleShowLists: () => void
  handleShowDetail: () => void
  handleUpdateOpen: () => void
}

const columns: TableColumn<GrnType>[] = [
  {
    name: 'No.',
    selector: (_row: any, index: any) => index + 1,
    width: '70px',
    sortable: false,
  },
  {
    name: 'Code',
    selector: (row) => row.grn_code,
    sortable: true,
  },
  {
    name: 'Shipment Code',
    selector: (row) => row.shipment_code,
    sortable: true,
  },
  {
    name: 'PO Number',
    selector: (row) => row.purchase_order_number || '-',
    sortable: true,
  },
  {
    name: 'Supplier',
    selector: (row) => row.supplier_code || '-',
    sortable: true,
  },
  {
    name: 'Received Date',
    selector: (row) => row.received_date || '-',
    sortable: true,
  },
  {
    name: 'Total Items',
    selector: (row) => row.total_items || '-',
    sortable: true,
  },
  {
    name: 'Created By',
    selector: (row) => row.created_by_name || '-',
    sortable: true,
  },
  {
    name: 'Approved By',
    selector: (row) => row.approved_by_name || '-',
    sortable: true,
  },
  {
    name: 'Status',
    cell: (row) => (
      <span
        className={`px-2 py-1 text-xs font-semibold rounded-full ${
          row.status === 0
            ? 'bg-yellow-100 text-yellow-700'
            : row.status === 1
            ? 'bg-red-100 text-red-700'
            : 'bg-green-100 text-green-700'
        }`}
      >
        {row.status === 0 ? 'Pending' : (row.status === 1?'Rejected':'Approved')}
      </span>
    ),
    sortable: true,
  },
]

const GrnTable: React.FC<Props> = ({
  grnLists,handleShowLists,handleShowDetail,handleUpdateOpen
}) => {
  const dispatch = useAppDispatch()
  const [filterText, setFilterText] = useState('')
  const [isViewOpen, setIsViewOpen] = useState(false)
  const [isUpdateOpen, setIsUpdateOpen] = useState(false)

  const filteredData = useMemo(() => {
    if (!filterText) return grnLists

    return grnLists.filter((item: any) =>
      Object.values(item).some(
        (val) =>
          val && val.toString().toLowerCase().includes(filterText.toLowerCase())
      )
    )
  }, [filterText, grnLists])

  const handleCloseModal = () => {
    setIsViewOpen(false)
    setIsUpdateOpen(false)
  }

//   const handleView = (row: any) => {
//     setIsViewOpen(true)
//     console.log(row.id)
//     dispatch(getContentData(row.id))
//   }
  const handleEdit = (row:any) => {
    setIsUpdateOpen(true)
    handleUpdateOpen()
    console.log(row.id)
    dispatch(getContentData(row.id))
    dispatch(getItemContentData(row.id))
    handleShowDetail()
  }

  const handleDelete = async (row: any) => {
      const confirmed = await showConfirm(
        'Are you sure?',
        'This action cannot be undone.'
      )

      if (!confirmed) return
      const complete = dispatch(deleteGrn(row.id))
      if (!complete) {
        Swal.fire({
          title: 'Error!',
          text: 'Failed to delete item.',
          icon: 'error',
        })
      } else {
        Swal.fire({
          title: 'Deleted!',
          text: 'Good Received Note has been deleted.',
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
        //   onView={handleView}
          onEdit={handleEdit}
          onDelete={handleDelete}
          subHeader
          subHeaderComponent={
            <div className="w-full flex items-center justify-between px-0 py-2 bg-muted">
              <Input
                type="text"
                placeholder="Search GRNs…"
                value={filterText}
                onChange={(e) => setFilterText(e.target.value)}
                className="w-full max-w-sm"
              />
            </div>
          }
        />
      </div>
      {isViewOpen && (
        <GrnViewForm
          isViewOpen={isViewOpen}
          handleCloseModal={handleCloseModal}
        />
      )}
      
    </>
  )
}

export default GrnTable
