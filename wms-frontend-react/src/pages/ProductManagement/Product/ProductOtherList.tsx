import React, { useState } from 'react'
import http from '../../../lib/http'
import provideUtility from '../../../utils/toast'
import Spinner from '../../../components/ui/loading/spinner'
import AdvancedDataTable from '../../../components/ui/dataTable'
import Button from '../../../components/ui/button/Button'
import Label from '../../../components/form/Label'
import Input from '../../../components/form/input/InputField'
import TextAreaInput from '../../../components/form/form-elements/TextAreaInput'
import SingleSelectInput from '../../../components/form/form-elements/SelectInputs'
import { ProductOtherType, ProductListsType } from '../../../type/types'
import { TableColumn } from 'react-data-table-component'
import { showConfirm } from '../../../utils/alert'
import Swal from 'sweetalert2'


import ProductViewModal from './ProductModal/ViewModal'
import ProductOtherUpdateModal from './ProductModal/UpdateProductOtherModal'

interface Props {
  isPageLoading: any
  productOther: ProductOtherType[]
  productLists: ProductListsType[]
  isCreateOpen: true | false
  handleCloseCreateModal: () => void
  openViewModal: (row: any) => void
  handleReFetchProOtherApi: () => void
}

interface RowData {
  product_code: string
  manufacture_date: string
  expire_date: string
  abc_category_value: any
  abc_category_activity: any
  remark: string
  custom_attributes: string
  [key: string]: any
}

interface Errors {
  product_id?: any
  manufacture_date?: string
  expire_date?: string
  abc_category_value?: any
  abc_category_activity?: any
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
    name: 'Manufacture Date',
    selector: (row: RowData) => row.manufacture_date || '-',
    sortable: true,
  },
  {
    name: 'Expire Date',
    selector: (row: RowData) => row.expire_date || '-',
    sortable: true,
  },
  {
    name: 'ABC Category Value',
    selector: (row: RowData) => row.abc_category_value,
    sortable: true,
  },
  {
    name: 'ABC Category Activity',
    selector: (row: RowData) => row.abc_category_activity,
    sortable: true,
  },
  
  
]

const ProductOtherList: React.FC<Props> = ({
  isPageLoading,
  productOther,
  productLists,
  isCreateOpen,
  handleCloseCreateModal,
  handleReFetchProOtherApi,
}) => {
  const [errors, setErrors] = useState<Errors>({})
  const [isLoading, setIsLoading] = useState(false)
  const [productOtherData, setProductOtherData] = useState<any>({})
  const [isViewOpen, setIsViewOpen] = useState(false)
  const [isUpdateOpen, setIsUpdateOpen] = useState(false)
  const [updateProductOtherData, setUpdateProductOtherData] = useState<any>({})
  const { showToast } = provideUtility()

  const abcCategoryValueData = [
    { id: 1, value: 'A', data: 'High Value' },
    { id: 2, value: 'B', data: 'Medium Value' },
    { id: 3, value: 'C', data: 'Low Value' },

  ]

  const abcCategoryActivityData = [
    { id: 1, value: 'High Activity' },
    { id: 2, value: 'Medium Activity' },
    { id: 3, value: 'Low Activity' },
    { id: 4, value: 'Seasonal' },
  ]

  const [proOtherFormData, setProOtherFormData] = useState({
    product_id:'',
    product_code: '',
    manufacture_date: '',
    expire_date: '',
    abc_category_value: '',
    abc_category_activity: '',
    remark: '',
    custom_attributes: '',
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
   const productOth = productOther.find((x: any) => x.id === row.id)
   if (productOth) {
    setProductOtherData(productOth)
    setIsViewOpen(true)
   }
 }


 const handleEdit = (row: any) => {
   setIsUpdateOpen(true)
   const product_data = productOther.find((x: any) => x.id === row.id)
   console.log(product_data)
   setUpdateProductOtherData(product_data)
 }

  const handleDelete = async (row: any) => {
    const confirmed = await showConfirm(
      'Are you sure?',
      'This action cannot be undone.'
    )

    if (!confirmed) return

    try {
      const response = await http.deleteDataWithToken(`/product-others/${row.id}`)
      console.log(response)
      if (response.status == true) {
        Swal.fire({
          title: 'Deleted!',
          text: 'Product Other has been deleted.',
          icon: 'success',
        })
        handleReFetchProOtherApi()
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

  const handleProOtherChange =
    (field: string) =>
    (e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement>) => {
      const value = e.target.value
   
        setProOtherFormData((prev) => ({
          ...prev,
          [field]: value,
        }))
    }

  const handleProOtherSubmit = async () => {
    setIsLoading(true)
    setErrors({})
    try {
      const response = await http.postDataWithToken(
        '/product-others',
        proOtherFormData
      )
      if (response.status === true) {
        showToast(
          '',
          'Create Product Other successful',
          'top-right',
          'success'
        )
        setProOtherFormData({
          product_id: '',
          product_code: '',
          manufacture_date: '',
          expire_date: '',
          abc_category_value: '',
          abc_category_activity: '',
          remark: '',
          custom_attributes: '',
        })
        handleReFetchProOtherApi()
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
        showToast('', 'Create Product Dimension failed!', 'top-right', 'error')
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
                value={proOtherFormData.product_id}
                getOptionLabel={(item) => `${item.id} - ${item.product_name}`}
                onSingleSelectChange={(val) => {
                  handleRemove('product_id')
                  setProOtherFormData((prev) => ({
                    ...prev,
                    product_id: val,
                  }))
                }}
                error={!!errors.product_id}
                hint={errors.product_id}
              />
            </div>
            <div>
              <Label>Manufacture Date</Label>
              <Input
                type="date"
                value={proOtherFormData.manufacture_date}
                onChange={handleProOtherChange('manufacture_date')}
                onKeyUp={() => handleRemove('manufacture_date')}
                error={!!errors.manufacture_date}
                hint={errors.manufacture_date}
              />
            </div>
            <div>
              <Label>Expire Date</Label>
              <Input
                type="date"
                value={proOtherFormData.expire_date}
                onChange={handleProOtherChange('expire_date')}
                onKeyUp={() => handleRemove('expire_date')}
                error={!!errors.expire_date}
                hint={errors.expire_date}
              />
            </div>
            <div className="relative">
              <Label>ABC Category Value</Label>
              <SingleSelectInput
                options={abcCategoryValueData}
                valueKey="value"
                value={proOtherFormData.abc_category_value}
                getOptionLabel={(item) => `${item.value} - ${item.data}`}
                onSingleSelectChange={(val) => {
                  handleRemove('abc_category_value')
                  setProOtherFormData((prev) => ({
                    ...prev,
                    abc_category_value: val,
                  }))
                }}
                error={!!errors.abc_category_value}
                hint={errors.abc_category_value}
              />
            </div>
            <div className="relative">
              <Label>ABC Category Activity</Label>
              <SingleSelectInput
                options={abcCategoryActivityData}
                valueKey="value"
                value={proOtherFormData.abc_category_activity}
                getOptionLabel={(item) => `${item.id} - ${item.value}`}
                onSingleSelectChange={(val) => {
                  handleRemove('abc_category_activity')
                  setProOtherFormData((prev) => ({
                    ...prev,
                    abc_category_activity: val,
                  }))
                }}
                error={!!errors.abc_category_activity}
                hint={errors.abc_category_activity}
              />
            </div>
            <div className="relative">
              <Label>Remark</Label>
              <TextAreaInput
                value={proOtherFormData.remark}
                onChange={(value) =>
                handleProOtherChange('remark')({
                target: { value },
                } as React.ChangeEvent<any>)
                }
                />
              
            </div>
            <div className="relative">
              <Label>Custom Attributes</Label>
              <TextAreaInput
                value={proOtherFormData.custom_attributes}
                onChange={(value) =>
                handleProOtherChange('custom_attributes')({
                target: { value },
                } as React.ChangeEvent<any>)
                }
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
              onClick={handleProOtherSubmit}
            >
              Confirm
            </Button>
          </div>
        </div>
      ) : isViewOpen ? (
        <ProductViewModal
          isViewOpen={isViewOpen}
          value={4}
          data={productOtherData}
          handleCloseModal={handleCloseModal}
        />
      ) : isUpdateOpen ? (
        <ProductOtherUpdateModal
          data={updateProductOtherData}
          abcCategoryValueData={abcCategoryValueData}
          abcCategoryActivityData={abcCategoryActivityData}
          productLists={productLists}
          value={4}
          isUpdateOpen={isUpdateOpen}
          handleCloseModal={handleCloseModal}
          handleReFetchProComApi={handleReFetchProOtherApi}
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
                data={productOther || []}
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

export default ProductOtherList;