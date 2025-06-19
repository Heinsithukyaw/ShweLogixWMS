import React, { useState, useMemo } from 'react'
import { QualityInspectionType } from '../../../../type/inbound/qualityInspectionType'
import AdvancedDataTable from '../../../../components/ui/dataTable'
import { TableColumn } from 'react-data-table-component'
import { showConfirm } from '../../../../utils/alert'
import Input from '../../../../components/form/input/InputField'
import { useAppDispatch } from '../../../../store/hook'
import QualityInspectionViewModal from '../components/ViewModal'
import QualityInspectionUpdateModal from '../components/UpdateModal'
import { getContentData } from '../../../../store/features/inbound/QualityInspectionSlice'
import { deleteQualityInspection } from '../services/qualityInspectionApi'
import Swal from 'sweetalert2'

interface Props {
  isLoading: any
  qualityInspectionLists: QualityInspectionType[]
}

const columns: TableColumn<QualityInspectionType>[] = [
  {
    name: 'No.',
    selector: (_row: any, index: any) => index + 1,
    width: '70px',
    sortable: false,
  },
  {
    name: 'Code',
    selector: (row) => row.quality_inspection_code,
    sortable: true,
  },
  {
    name: 'Inbound Code',
    selector: (row) => row.inbound_shipment_detail_code,
    sortable: true,
  },
  {
    name: 'Inspection Name',
    selector: (row) => row.inspector_name || '-',
    sortable: true,
  },
  {
    name: 'Inspection Date',
    selector: (row) => row.inspection_date || '-',
    sortable: true,
  },
  {
    name: 'Rejection Reason',
    selector: (row) => row.rejection_reason|| '-',
    sortable: true,
  },
  {
    name: 'Sample Size',
    selector: (row) => row.sample_size || '-',
    sortable: true,
  },
  {
    name: 'Corrective Action',
    selector: (row) => row.corrective_action|| '-',
    sortable: true,
  },
  {
    name: 'Passed',
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
          ? 'Pending'
          : row.status === 1
          ? 'Failed'
          : 'Passed'
        }
      </span>
    ),
    sortable: true,
  },
]

const QualityInspectionTable: React.FC<Props> = ({
  qualityInspectionLists,
}) => {
  const dispatch = useAppDispatch()
  const [filterText, setFilterText] = useState('')
  const [isViewOpen, setIsViewOpen] = useState(false)
  const [isUpdateOpen, setIsUpdateOpen] = useState(false)

  const filteredData = useMemo(() => {
    if (!filterText) return qualityInspectionLists

    return qualityInspectionLists.filter((item: any) =>
      Object.values(item).some(
        (val) =>
          val && val.toString().toLowerCase().includes(filterText.toLowerCase())
      )
    )
  }, [filterText, qualityInspectionLists])

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
    const complete = dispatch(deleteQualityInspection(row.id))
    if (!complete) {
      Swal.fire({
        title: 'Error!',
        text: 'Failed to delete item.',
        icon: 'error',
      })
    } else {
      Swal.fire({
        title: 'Deleted!',
        text: 'Quality Inspection has been deleted.',
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
                placeholder="Search Quality Inspections…"
                value={filterText}
                onChange={(e) => setFilterText(e.target.value)}
                className="w-full max-w-sm"
              />
            </div>
          }
        />
      </div>
      {isViewOpen && (
        <QualityInspectionViewModal
          isViewOpen={isViewOpen}
          handleCloseModal={handleCloseModal}
        />
      )}
      {isUpdateOpen && (
        <QualityInspectionUpdateModal
          isUpdateOpen={isUpdateOpen}
          handleCloseModal={handleCloseModal}
        />
      )}
    </>
  )
}

export default QualityInspectionTable
