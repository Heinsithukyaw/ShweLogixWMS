import React, { useState, useMemo } from 'react'
import { AsnType } from '../../../../type/inbound/asnType'
import AdvancedDataTable from '../../../../components/ui/dataTable'
import { TableColumn } from 'react-data-table-component'
import { showConfirm } from '../../../../utils/alert'
import Input from '../../../../components/form/input/InputField'
import { useAppDispatch } from '../../../../store/hook'
import AsnViewModal from '../components/AsnViewModal'
import AsnUpdateModal from '../components/AsnUpdateModal'
import { getContentData } from '../../../../store/features/inbound/AsnSlice'
import { deleteAsn } from '../services/asnApi'
import Swal from 'sweetalert2'

interface Props {
  isLoading: any
  asnLists: AsnType[]
}

const columns: TableColumn<AsnType>[] = [
  {
    name: 'No.',
    selector: (_row: any, index: any) => index + 1,
    width: '70px',
    sortable: false,
  },
  {
    name: 'Code',
    selector: (row) => row.asn_code,
    sortable: true,
  },
  {
    name: 'Supplier Code',
    selector: (row) => row.supplier_code,
    sortable: true,
  },
  {
    name: 'PO Name',
    selector: (row) => row.purchase_order_name || '-',
    sortable: true,
  },
  {
    name: 'Expected Arrival',
    selector: (row) => row.expected_arrival || '-',
    sortable: true,
  },
  {
    name: 'Carrier Code',
    selector: (row) => row.carrier_code || '-',
    sortable: true,
  },
  {
    name: 'Tracking Number',
    selector: (row) => row.tracking_number || '-',
    sortable: true,
  },
  {
    name: 'Total Items',
    selector: (row) => row.total_items || '-',
    sortable: true,
  },
  {
    name: 'Total Pallets',
    selector: (row) => row.total_pallets || '-',
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
            ? 'bg-green-100 text-green-700'
            : 'bg-blue-100 text-blue-700'
        }`}
      >
        {row.status === 0 ? 'Pending' : (row.status === 1?'Verified':'Received')}
      </span>
    ),
    sortable: true,
  },
]

const AsnTable: React.FC<Props> = ({
  asnLists,
}) => {
  const dispatch = useAppDispatch()
  const [filterText, setFilterText] = useState('')
  const [isViewOpen, setIsViewOpen] = useState(false)
  const [isUpdateOpen, setIsUpdateOpen] = useState(false)

  const filteredData = useMemo(() => {
    if (!filterText) return asnLists

    return asnLists.filter((item: any) =>
      Object.values(item).some(
        (val) =>
          val && val.toString().toLowerCase().includes(filterText.toLowerCase())
      )
    )
  }, [filterText, asnLists])

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
      const complete = dispatch(deleteAsn(row.id))
      if (!complete) {
        Swal.fire({
          title: 'Error!',
          text: 'Failed to delete item.',
          icon: 'error',
        })
      } else {
        Swal.fire({
          title: 'Deleted!',
          text: 'ASN has been deleted.',
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
                placeholder="Search ASNs…"
                value={filterText}
                onChange={(e) => setFilterText(e.target.value)}
                className="w-full max-w-sm"
              />
            </div>
          }
        />
      </div>
      {isViewOpen && (
        <AsnViewModal
          isViewOpen={isViewOpen}
          handleCloseModal={handleCloseModal}
        />
      )}
      {isUpdateOpen && (
        <AsnUpdateModal
          isUpdateOpen={isUpdateOpen}
          handleCloseModal={handleCloseModal}
        />
      )}
    </>
  )
}

export default AsnTable
