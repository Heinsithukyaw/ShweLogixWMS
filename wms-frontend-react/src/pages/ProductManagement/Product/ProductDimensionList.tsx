import React, { useState } from 'react'
import http from '../../../lib/http'
import provideUtility from '../../../utils/toast'
import Spinner from '../../../components/ui/loading/spinner'
import AdvancedDataTable from '../../../components/ui/dataTable'
import Button from '../../../components/ui/button/Button'
import Label from '../../../components/form/Label'
import Input from '../../../components/form/input/InputField'
import SingleSelectInput from '../../../components/form/form-elements/SelectInputs'
import { ProductDimensionType, ProductListsType } from '../../../type/types'
import { TableColumn } from 'react-data-table-component'
import { showConfirm } from '../../../utils/alert'
import Swal from 'sweetalert2'


import ProductViewModal from './ProductModal/ViewModal'
import ProductDimensionUpdateModal from './ProductModal/UpdateProductDimensionModal'

interface Props {
  isPageLoading: any
  productDimension: ProductDimensionType[]
  productLists: ProductListsType[]
  isCreateOpen: true | false
  handleCloseCreateModal: () => void
  openViewModal: (row: any) => void
  handleReFetchProDimApi: () => void
}

interface RowData {
  product_code: string
  dimension_use: string
  length: string
  width: string
  height: string
  weight: string
  volume: string
  storage_volume:string
  space_area:string
  units_per_box:any
  boxes_per_pallet:any
  [key: string]: any
}

interface Errors {
  product_id?: any
  dimension_use?: any
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
    name: 'Dimension Use',
    selector: (row: RowData) => row.dimension_use || '-',
    sortable: true,
  },
  {
    name: 'Length',
    selector: (row: RowData) => (row.length != null ? `${row.length} cm` : '-'),
    sortable: true,
  },
  {
    name: 'Width',
    selector: (row: RowData) => (row.width != null ? `${row.width} cm` : '-'),
    sortable: true,
  },
  {
    name: 'Height',
    selector: (row: RowData) => (row.height != null ? `${row.height} cm` : '-'),
    sortable: true,
  },
  {
    name: 'Weight',
    selector: (row: RowData) => (row.weight != null ? `${row.weight} kg` : '-'),
    sortable: true,
  },
  {
    name: 'Volume',
    selector: (row: RowData) =>
      row.volume != null
        ? `${row.volume} cm³`
        : '-',
    sortable: true,
  },
  {
    name: 'Storage Volume',
    selector: (row: RowData) => row.storage_volume != null
        ? `${row.storage_volume} cm³`
        : '-',
    sortable: true,
  },
  {
    name: 'Space Area',
    selector: (row: RowData) => row.volume != null
        ? `${row.space_area} cm³`
        : '-',
    sortable: true,
  },
  {
    name: 'Units Per Box',
    selector: (row: RowData) => row.units_per_box,
    sortable: true,
  },
  {
    name: 'Boxes Per Pallet',
    selector: (row: RowData) => row.boxes_per_pallet,
    sortable: true,
  },
]

const ProductDimensionList: React.FC<Props> = ({
  isPageLoading,
  productDimension,
  productLists,
  isCreateOpen,
  handleCloseCreateModal,
  handleReFetchProDimApi,
}) => {
  const [errors, setErrors] = useState<Errors>({})
  const [isLoading, setIsLoading] = useState(false)
  const [productDimensionData, setProductDimensionData] = useState<any>({})
  const [isViewOpen, setIsViewOpen] = useState(false)
  const [isUpdateOpen, setIsUpdateOpen] = useState(false)
  const [updateProductDimensionData, setUpdateProductDimensionData] = useState<any>({})
  const { showToast } = provideUtility()

  const dimensionUseData = [
    { id: 1, value: 'Dimension' },
    { id: 2, value: 'Volume' },
    { id: 3, value: 'Weight' },
  ]

  const [proDimFormData, setProDimFormData] = useState({
    product_id:'',
    product_code: '',
    dimension_use: '',
    length: '',
    width: '',
    height: '',
    weight: '',
    volume: '',
    storage_volume:'',
    space_area:'',
    units_per_box:'',
    boxes_per_pallet:''
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
   const productDim = productDimension.find((x: any) => x.id === row.id)
   if (productDim) {
    setProductDimensionData(productDim)
    setIsViewOpen(true)
   }
 }


 const handleEdit = (row: any) => {
   setIsUpdateOpen(true)
   const product_data = productDimension.find((x: any) => x.id === row.id)
   console.log(product_data)
   setUpdateProductDimensionData(product_data)
 }

  const handleDelete = async (row: any) => {
    const confirmed = await showConfirm(
      'Are you sure?',
      'This action cannot be undone.'
    )

    if (!confirmed) return

    try {
      const response = await http.deleteDataWithToken(`/product-dimensions/${row.id}`)
      console.log(response)
      if (response.status == true) {
        Swal.fire({
          title: 'Deleted!',
          text: 'Product Dimensions has been deleted.',
          icon: 'success',
        })
        handleReFetchProDimApi()
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

  const handleProDimChange =
    (field: string) =>
    (e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement>) => {
      const value = e.target.value
   
        setProDimFormData((prev) => ({
          ...prev,
          [field]: value,
        }))
    }

  const handleProDimSubmit = async () => {
    setIsLoading(true)
    setErrors({})
    try {
      const response = await http.postDataWithToken(
        '/product-dimensions',
        proDimFormData
      )
      if (response.status === true) {
        showToast(
          '',
          'Create Product Dimension successful',
          'top-right',
          'success'
        )
        setProDimFormData({
          product_id:'',
          product_code: '',
          dimension_use: '',
          length: '',
          width: '',
          height: '',
          weight: '',
          volume: '',
          storage_volume: '',
          space_area: '',
          units_per_box: '',
          boxes_per_pallet: '',
        })
        handleReFetchProDimApi()
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
                value={proDimFormData.product_id}
                getOptionLabel={(item) => `${item.id} - ${item.product_name}`}
                onSingleSelectChange={(val) => {
                  handleRemove('product_id')
                  setProDimFormData((prev) => ({
                    ...prev,
                    product_id: val,
                  }))
                }}
                error={!!errors.product_id}
                hint={errors.product_id}
              />
            </div>
            <div>
              <Label>Dimension Use</Label>
              <SingleSelectInput
                options={dimensionUseData}
                valueKey="value"
                value={proDimFormData.dimension_use}
                getOptionLabel={(item) => `${item.id} - ${item.value}`}
                onSingleSelectChange={(val) => {
                  handleRemove('dimension_use')
                  setProDimFormData((prev) => ({
                    ...prev,
                    dimension_use: val,
                  }))
                }}
                error={!!errors.dimension_use}
                hint={errors.dimension_use}
              />
            </div>
            <div className="relative">
              <Label>Length</Label>
              <Input
                type="number"
                value={proDimFormData.length}
                onChange={handleProDimChange('length')}
                onKeyUp={() => handleRemove('length')}
              />
              <span className="absolute right-3 top-[65%] -translate-y-1/2 text-gray-500 text-md flex">
                cm
              </span>
            </div>
            <div className="relative">
              <Label>Width</Label>
              <Input
                type="number"
                value={proDimFormData.width}
                onChange={handleProDimChange('width')}
                onKeyUp={() => handleRemove('width')}
              />
              <span className="absolute right-3 top-[65%] -translate-y-1/2 text-gray-500 text-md flex">
                cm
              </span>
            </div>
            <div className="relative">
              <Label>Height</Label>
              <Input
                type="number"
                value={proDimFormData.height}
                onChange={handleProDimChange('height')}
                onKeyUp={() => handleRemove('height')}
              />
              <span className="absolute right-3 top-[65%] -translate-y-1/2 text-gray-500 text-md flex">
                cm
              </span>
            </div>
            <div className="relative">
              <Label>Weight</Label>
              <Input
                type="text"
                value={proDimFormData.weight}
                onChange={handleProDimChange('weight')}
                onKeyUp={() => handleRemove('weight')}
              />
              <span className="absolute right-3 top-[65%] -translate-y-1/2 text-gray-500 text-md flex">
                kg
              </span>
            </div>
            <div className="relative">
              <Label>Volume</Label>
              <Input
                type="text"
                value={proDimFormData.volume}
                onChange={handleProDimChange('volume')}
                onKeyUp={() => handleRemove('volume')}
              />
              <span className="absolute right-3 top-[65%] -translate-y-1/2 text-gray-500 text-md flex">
                cm³
              </span>
            </div>
            <div className="relative">
              <Label>Storage Volume</Label>
              <Input
                type="number"
                value={proDimFormData.storage_volume}
                onChange={handleProDimChange('storage_volume')}
              />
              <span className="absolute right-3 top-[65%] -translate-y-1/2 text-gray-500 text-md flex">
                cm³
              </span>
            </div>
            <div className='relative'>
              <Label>Space Area</Label>
              <Input
                type="number"
                value={proDimFormData.space_area}
                onChange={handleProDimChange('space_area')}
              />
              <span className="absolute right-3 top-[65%] -translate-y-1/2 text-gray-500 text-md flex">
                cm³
              </span>
            </div>
            <div>
              <Label>Units Per Box</Label>
              <Input
                type="number"
                value={proDimFormData.units_per_box}
                onChange={handleProDimChange('units_per_box')}
              />
            </div>
            <div>
              <Label>Boxes Per Pallet</Label>
              <Input
                type="number"
                value={proDimFormData.boxes_per_pallet}
                onChange={handleProDimChange('boxes_per_pallet')}
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
              onClick={handleProDimSubmit}
            >
              Confirm
            </Button>
          </div>
        </div>
      ) : isViewOpen ? (
        <ProductViewModal
          isViewOpen={isViewOpen}
          value={2}
          data={productDimensionData}
          handleCloseModal={handleCloseModal}
        />
      ) : isUpdateOpen ? (
        <ProductDimensionUpdateModal
          data={updateProductDimensionData}
          dimensionUseData={dimensionUseData}
          productLists={productLists}
          value={2}
          isUpdateOpen={isUpdateOpen}
          handleCloseModal={handleCloseModal}
          handleReFetchProDimApi={handleReFetchProDimApi}
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
                data={productDimension || []}
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

export default ProductDimensionList;