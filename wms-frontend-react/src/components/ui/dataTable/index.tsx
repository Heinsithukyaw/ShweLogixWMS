import React from 'react'
import DataTable, { TableColumn } from 'react-data-table-component'
import CustomMaterialMenu from '../../customMaterialMenu/index'

interface AdvancedDataTableProps<T> {
  data: T[]
  columns: TableColumn<T>[]
  onView?: (row: T) => void
  onEdit?: (row: T) => void
  onDelete?: (row: T) => void
  subHeader?: boolean
  subHeaderComponent?: React.ReactNode
}


const customStyles = {
  headRow: {
    style: {
      border: 'none',
    },
  },
  headCells: {
    style: {
      color: '#202124',
      fontSize: '14px',
    },
  },
  rows: {
    highlightOnHoverStyle: {
      backgroundColor: '#FFFFFF',
      borderBottomColor: '#FFFFFF',
      borderRadius: '25px',
      outline: '1px solid #FFFFFF',
    },
  },
  pagination: {
    style: {
      border: 'none',
    },
  },
 
}


const AdvancedDataTable = <T,>({
  data,
  columns,
  onView,
  onEdit,
  onDelete,
  subHeader,
  subHeaderComponent,
}: AdvancedDataTableProps<T>) => {
  const actionColumn: TableColumn<T> = {
    name: '',
    cell: (row) => (
      <CustomMaterialMenu
        row={row}
        onView={onView}
        onEdit={onEdit}
        onDelete={onDelete}
      />
    ),
    ignoreRowClick: true,
    allowOverflow: true,
    button: true,
    width: '56px',
  }

  return (
    <div className="p-4 ">
      <div className="min-w-[640px]">
        <DataTable
          columns={[...columns, actionColumn]}
          data={data}
          defaultSortFieldId={1}
          pagination
          highlightOnHover
          customStyles={customStyles}
          striped
          subHeader={subHeader}
          subHeaderComponent={subHeaderComponent}
        />
      </div>
    </div>
  )
}

export default AdvancedDataTable
