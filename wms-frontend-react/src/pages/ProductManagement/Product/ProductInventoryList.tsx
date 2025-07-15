import React, { useState } from 'react'
import http from '../../../lib/http'
import provideUtility from '../../../utils/toast'
import Spinner from '../../../components/ui/loading/spinner'
import AdvancedDataTable from '../../../components/ui/dataTable'
import Button from '../../../components/ui/button/Button'
import Label from '../../../components/form/Label'
import Input from '../../../components/form/input/InputField'
import SingleSelectInput from '../../../components/form/form-elements/SelectInputs'
import { ProductInventoryType, ProductListsType, UomListsType } from '../../../type/types'
import { TableColumn } from 'react-data-table-component'
import { showConfirm } from '../../../utils/alert'
import Swal from 'sweetalert2'


import ProductViewModal from './ProductModal/ViewModal'
import ProductInventoryUpdateModal from './ProductModal/UpdateProductInventoryModal'

interface Props {
  isPageLoading: any
  productInventory: ProductInventoryType[]
  productLists: ProductListsType[]
  uomLists: UomListsType[]
  isCreateOpen: true | false
  handleCloseCreateModal: () => void
  openViewModal: (row: any) => void
  handleReFetchProInvApi: () => void
}

interface RowData {
  product_code: string
  product_name: string
  uom: string
  warehouse_code: string
  location: string
  batch_no: string
  lot_no: string
  reorder_level:number
  packing_qty:number
  whole_qty:number
  loose_qty:number
  stock_rotation_policy: string
  [key: string]: any
}

interface Errors {
  product_id?: any
  uom_id?: any
  batch_no?: string
  lot_no?: string
}

const columns: TableColumn<RowData>[] = [
  {
    name: 'No.',
    selector: (_row: any, index: any) => index + 1,
    width: '70px',
    sortable: false,
  },
  {
    name: 'Code/SKU',
    selector: (row: RowData) => row.product_code,
    sortable: true,
  },
  {
    name: 'Product Name',
    selector: (row: RowData) => row.product_name,
    sortable: true,
  },
  {
    name: 'Unit Of Measure (UOM)',
    selector: (row: RowData) => row.uom_name || '-',
    sortable: true,
  },
  {
    name: 'Warehouse Code',
    selector: (row: RowData) => row.warehouse_code || '-',
    sortable: true,
  },
  {
    name: 'Location',
    selector: (row: RowData) => row.location || '-',
    sortable: true,
  },
  {
    name: 'Batch No',
    selector: (row: RowData) => row.batch_no,
    sortable: true,
  },
  {
    name: 'Lot No',
    selector: (row: RowData) => row.lot_no,
    sortable: true,
  },
  {
    name: 'Reorder Level',
    selector: (row: RowData) => row.reorder_level,
    sortable: true,
  },
  {
    name: 'Packing Qty',
    selector: (row: RowData) => row.packing_qty,
    sortable: true,
  },
  {
    name: 'Whole Qty',
    selector: (row: RowData) => row.whole_qty,
    sortable: true,
  },
  {
    name: 'Loose Qty',
    selector: (row: RowData) => row.loose_qty,
    sortable: true,
  },
  {
    name: 'Stock Rotation Policy',
    selector: (row: RowData) => row.stock_rotation_policy,
    sortable: true,
  },
]

const ProductInventoryList: React.FC<Props> = ({
  isPageLoading,
  productInventory,
  productLists,
  uomLists,
  isCreateOpen,
  handleCloseCreateModal,
  handleReFetchProInvApi,
}) => {
  const [errors, setErrors] = useState<Errors>({})
  const [isLoading, setIsLoading] = useState(false)
  const [productInventoryData, setProductInventoryData] = useState<any>({})
  const [isViewOpen, setIsViewOpen] = useState(false)
  const [isUpdateOpen, setIsUpdateOpen] = useState(false)
  const [updateProductInventoryData, setUpdateProductInventoryData] = useState<any>({})
  const { showToast } = provideUtility()

  const stockRotationPolicyData = [
    { id: 1, value: 'FIFO' },
    { id: 2, value: 'LIFO' },
    { id: 3, value: 'FEFO' },
  ]

  const [proInvFormData, setProInvFormData] = useState({
    product_id: '',
    uom_id: '',
    warehouse_code: '',
    location: '',
    batch_no: '',
    lot_no: '',
    packing_qty: '',
    whole_qty: '',
    loose_qty: '',
    reorder_level: '',
    stock_rotation_policy: '',
  })

  const handleCloseModal = () => {
   
    setIsViewOpen(false)
    setIsUpdateOpen(false)
  }

  //function
  const handleRemove = (field: string) => {
    setErrors((prev) => ({
      ...prev,
      [field]: null,
    }))
  }

 const handleSelfView = (row: any) => {
   const productInv = productInventory.find((x: any) => x.id === row.id)
   if (productInv) {
    setProductInventoryData(productInv)
    setIsViewOpen(true)
   }
 }


 const handleEdit = (row: any) => {
   setIsUpdateOpen(true)
   const product_data = productInventory.find((x: any) => x.id === row.id)
   setUpdateProductInventoryData(product_data)
 }

  const handleDelete = async (row: any) => {
    const confirmed = await showConfirm(
      'Are you sure?',
      'This action cannot be undone.'
    )

    if (!confirmed) return

    try {
      const response = await http.deleteDataWithToken(`/product-inventories/${row.id}`)
      console.log(response)
      if (response.status == true) {
        Swal.fire({
          title: 'Deleted!',
          text: 'Product Inventory has been deleted.',
          icon: 'success',
        })
        handleReFetchProInvApi()
      } else {
        Swal.fire({
          title: 'Error!',
          text: 'Failed to delete item.',
          icon: 'error',
        })
      }
    } catch (error: any) {
      Swal.fire({
        title: 'Error!',
        text: error.message || 'Failed to delete item.',
        icon: 'error',
      })
    }
  }

  const handleProInvChange =
    (field: string) =>
    (e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement>) => {
      const value = e.target.value
   
        setProInvFormData((prev) => ({
          ...prev,
          [field]: value,
        }))
    }

  const handleProInvSubmit = async () => {
    setIsLoading(true)
    setErrors({})
    try {
      const response = await http.postDataWithToken(
        '/product-inventories',
        proInvFormData
      )
      if (response.status === true) {
        showToast(
          '',
          'Create Product Inventory successful',
          'top-right',
          'success'
        )
        setProInvFormData({
          product_id: '',
          uom_id: '',
          warehouse_code: '',
          location: '',
          batch_no: '',
          lot_no: '',
          packing_qty: '',
          whole_qty: '',
          loose_qty: '',
          reorder_level: '',
          stock_rotation_policy: '',
        })
        handleReFetchProInvApi()
        handleCloseCreateModal()
      } else {
        showToast('', 'Something went wrong!.', 'top-right', 'error')
      }
    } catch (err: any) {
      if (err?.status === 422) {
        showToast('', err?.data.message, 'top-right', 'error')
        const apiErrors: Errors = err?.data.errors
        setErrors(apiErrors)
      } else {
        showToast('', 'Create Product Inventory failed!', 'top-right', 'error')
      }
      console.error(err)
    } finally {
      setIsLoading(false)
    }
  }

  return (
    <>
      {isCreateOpen ? (
        <div className="">
          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <Label>Product</Label>
              <SingleSelectInput
                options={productLists}
                valueKey="id"
                value={proInvFormData.product_id}
                getOptionLabel={(item) => `${item.id} - ${item.product_name}`}
                onSingleSelectChange={(val) => {
                  handleRemove('product_id')
                  setProInvFormData((prev) => ({
                    ...prev,
                    product_id: val,
                  }))
                }}
                error={!!errors.product_id}
                hint={errors.product_id}
              />
            </div>
            <div>
              <Label>Unit Of Measure</Label>
              <SingleSelectInput
                options={uomLists}
                valueKey="id"
                value={proInvFormData.uom_id}
                getOptionLabel={(item) => `${item.id} - ${item.uom_name}`}
                onSingleSelectChange={(val) => {
                  handleRemove('uom_id')
                  setProInvFormData((prev) => ({
                    ...prev,
                    uom_id: val,
                  }))
                }}
                error={!!errors.uom_id}
                hint={errors.uom_id}
              />
            </div>
            <div>
              <Label>Warehouse Code</Label>
              <Input
                type="text"
                value={proInvFormData.warehouse_code}
                onChange={handleProInvChange('warehouse_code')}
                onKeyUp={() => handleRemove('warehouse_code')}
              />
            </div>
            <div>
              <Label>Location</Label>
              <Input
                type="text"
                value={proInvFormData.location}
                onChange={handleProInvChange('location')}
                onKeyUp={() => handleRemove('location')}
              />
            </div>
            <div>
              <Label>
                Reorder Level<span className="text-error-500">*</span>
              </Label>
              <Input
                type="number"
                value={proInvFormData.reorder_level}
                onChange={handleProInvChange('reorder_level')}
                onKeyUp={() => handleRemove('reorder_level')}
              />
            </div>
            <div>
              <Label>
                Batch No<span className="text-error-500">*</span>
              </Label>
              <Input
                type="text"
                value={proInvFormData.batch_no}
                onChange={handleProInvChange('batch_no')}
                onKeyUp={() => handleRemove('batch_no')}
                error={!!errors.batch_no}
                hint={errors.batch_no}
              />
            </div>
            <div>
              <Label>
                Lot No<span className="text-error-500">*</span>
              </Label>
              <Input
                type="text"
                value={proInvFormData.lot_no}
                onChange={handleProInvChange('lot_no')}
                onKeyUp={() => handleRemove('lot_no')}
                error={!!errors.lot_no}
                hint={errors.lot_no}
              />
            </div>
            <div>
              <Label>Packing Qty</Label>
              <Input
                type="number"
                value={proInvFormData.packing_qty}
                onChange={handleProInvChange('packing_qty')}
              />
            </div>
            <div>
              <Label>Whole Qty</Label>
              <Input
                type="number"
                value={proInvFormData.whole_qty}
                onChange={handleProInvChange('whole_qty')}
              />
            </div>
            <div>
              <Label>Loose Qty</Label>
              <Input
                type="number"
                value={proInvFormData.loose_qty}
                onChange={handleProInvChange('loose_qty')}
              />
            </div>
            <div>
              <Label>Stock Rotation Policy</Label>
              <SingleSelectInput
                options={stockRotationPolicyData}
                valueKey="value"
                value={proInvFormData.stock_rotation_policy}
                getOptionLabel={(item) => `${item.id} - ${item.value}`}
                onSingleSelectChange={(val) => {
                  handleRemove('stock_rotation_policy')
                  setProInvFormData((prev) => ({
                    ...prev,
                    stock_rotation_policy: val,
                  }))
                }}
              />
            </div>
          </div>
          <div className="flex justify-end gap-2">
            <Button variant="secondary" onClick={handleCloseCreateModal}>
              Cancel
            </Button>
            <Button
              variant="primary"
              startIcon={isLoading && <Spinner size={4} />}
              onClick={handleProInvSubmit}
            >
              Confirm
            </Button>
          </div>
        </div>
      ) : isViewOpen ? (
        <ProductViewModal
          isViewOpen={isViewOpen}
          value={1}
          data={productInventoryData}
          handleCloseModal={handleCloseModal}
        />
      ) : isUpdateOpen ? (
        <ProductInventoryUpdateModal
          data={updateProductInventoryData}
          stockRotationPolicyData={stockRotationPolicyData}
          productLists={productLists}
          uomLists={uomLists}
          value={1}
          isUpdateOpen={isUpdateOpen}
          handleCloseModal={handleCloseModal}
          handleReFetchProInvApi={handleReFetchProInvApi}
        />
      ) : (
        <div className="space-y-10">
          <div className="">
            {isPageLoading ? (
              <div className="flex justify-center items-center space-x-2">
                <div className="w-5 h-5 border-2 border-t-blue-500 border-blue-200 rounded-full animate-spin"></div>
                <span className="text-sm text-gray-500">Loading...</span>
              </div>
            ) : (
              <AdvancedDataTable
                data={productInventory || []}
                columns={columns}
                onView={handleSelfView}
                onEdit={handleEdit}
                onDelete={handleDelete}
              />
            )}
          </div>
        </div>
      )}
    </>
  )
}

export default ProductInventoryList;