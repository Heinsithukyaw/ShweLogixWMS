import React, { useState } from 'react'
import http from '../../../lib/http'
import provideUtility from '../../../utils/toast'
import Spinner from '../../../components/ui/loading/spinner'
import AdvancedDataTable from '../../../components/ui/dataTable'
import Button from '../../../components/ui/button/Button'
import Label from '../../../components/form/Label'
import Input from '../../../components/form/input/InputField'
import SingleSelectInput from '../../../components/form/form-elements/SelectInputs'
import { ProductCommercialType, ProductListsType } from '../../../type/types'
import { TableColumn } from 'react-data-table-component'
import { showConfirm } from '../../../utils/alert'
import Swal from 'sweetalert2'


import ProductViewModal from './ProductModal/ViewModal'
import ProductCommercialUpdateModal from './ProductModal/UpdateProductCommercialModal'

interface Props {
  isPageLoading: any
  productCommercial: ProductCommercialType[]
  productLists: ProductListsType[]
  supplierLists: any
  isCreateOpen: true | false
  handleCloseCreateModal: () => void
  openViewModal: (row: any) => void
  handleReFetchProComApi: () => void
}

interface RowData {
  product_code: string
  customer_code: string
  bar_code: string
  cost_price: any
  standard_price: any
  currency: string
  discount: string
  supplier:any
  manufacturer:string
  country_code:any
  [key: string]: any
}

interface Errors {
  product_id?: any
  customer_code?: string
  bar_code?: string
  cost_price?: any
  standard_price?: any
  currency?: any
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
    name: 'Customer Code',
    selector: (row: RowData) => row.customer_code || '-',
    sortable: true,
  },
  {
    name: 'Bar Code/GTIN',
    selector: (row: RowData) => row.bar_code || '-',
    sortable: true,
  },
  {
    name: 'Cost Price',
    selector: (row: RowData) => (row.cost_price != null ? `${row.cost_price} $` : '-'),
    sortable: true,
  },
  {
    name: 'Standard Price',
    selector: (row: RowData) => (row.standard_price != null ? `${row.standard_price} $` : '-'),
    sortable: true,
  },
  {
    name: 'Currency',
    selector: (row: RowData) => row.currency || '-',
    sortable: true,
  },
  {
    name: 'Discount',
    selector: (row: RowData) =>
      row.discount != null ? `${row.discount} %` : '-',
    sortable: true,
  },
  {
    name: 'Supplier',
    selector: (row: RowData) => row.supplier_code || '-',
    sortable: true,
  },
  {
    name: 'Manufacturer',
    selector: (row: RowData) => row.manufacturer || '-',
    sortable: true,
  },
  {
    name: 'Country Code',
    selector: (row: RowData) => row.country_code || '-',
    sortable: true,
  },
  
]

const ProductCommercialList: React.FC<Props> = ({
  isPageLoading,
  productCommercial,
  productLists,
  supplierLists,
  isCreateOpen,
  handleCloseCreateModal,
  handleReFetchProComApi,
}) => {
  const [errors, setErrors] = useState<Errors>({})
  const [isLoading, setIsLoading] = useState(false)
  const [productCommercialData, setProductCommercialData] = useState<any>({})
  const [isViewOpen, setIsViewOpen] = useState(false)
  const [isUpdateOpen, setIsUpdateOpen] = useState(false)
  // const [currencySign, setCurrencySign] = useState('$')
  const [updateProductCommercialData, setUpdateProductCommercialData] =
    useState<any>({})
  const { showToast } = provideUtility()

  const currencyData = [
    { id: 1, value: 'USD' },
    { id: 2, value: 'EUR' },
    { id: 3, value: 'GBD' },
    { id: 4, value: 'JPY' },
    { id: 5, value: 'CNY' },
  ]

  const countryCodeData = [
    { id: 1, value: 'United States', code: 'US' },
    { id: 2, value: 'China', code: 'CN' },
    { id: 3, value: 'India', code: 'IN' },
    { id: 4, value: 'Japan', code: 'JP' },
    { id: 5, value: 'Germany', code: 'DE' },
    { id: 5, value: 'United Kingdom', code: 'UK' },
  ]

  // const currencySymbols: Record<string, string> = {
  //   USD: '$',
  //   EUR: '€',
  //   GBD: '£',
  //   JPY: '¥',
  //   CNY: 'CN¥',
  // }

  const [proComFormData, setProComFormData] = useState({
    product_id: '',
    product_code: '',
    customer_code: '',
    bar_code: '',
    cost_price: '',
    standard_price: '',
    currency: '',
    discount: '',
    supplier_id: '',
    manufacturer: '',
    country_code: '',
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
    const productCom = productCommercial.find((x: any) => x.id === row.id)
    if (productCom) {
      setProductCommercialData(productCom)
      setIsViewOpen(true)
    }
  }

  const handleEdit = (row: any) => {
    setIsUpdateOpen(true)
    const product_data = productCommercial.find((x: any) => x.id === row.id)
    console.log(product_data)
    setUpdateProductCommercialData(product_data)
  }

  const handleDelete = async (row: any) => {
    const confirmed = await showConfirm(
      'Are you sure?',
      'This action cannot be undone.'
    )

    if (!confirmed) return

    try {
      const response = await http.deleteDataWithToken(
        `/product-commercials/${row.id}`
      )
      console.log(response)
      if (response.status == true) {
        Swal.fire({
          title: 'Deleted!',
          text: 'Product Commercial has been deleted.',
          icon: 'success',
        })
        handleReFetchProComApi()
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

  const handleProComChange =
    (field: string) =>
    (e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement>) => {
      const value = e.target.value

      setProComFormData((prev) => ({
        ...prev,
        [field]: value,
      }))
    }

  const handleProComSubmit = async () => {
    setIsLoading(true)
    setErrors({})
    try {
      const response = await http.postDataWithToken(
        '/product-commercials',
        proComFormData
      )
      if (response.status === true) {
        showToast(
          '',
          'Create Product Commercial successful',
          'top-right',
          'success'
        )
        setProComFormData({
          product_id: '',
          product_code: '',
          customer_code: '',
          bar_code: '',
          cost_price: '',
          standard_price: '',
          currency: '',
          discount: '',
          supplier_id: '',
          manufacturer: '',
          country_code: '',
        })
        handleReFetchProComApi()
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

  // const handleChangeCurrencySign = (value: string) => {
  //   const symbol = currencySymbols[value]
  //   if (symbol) {
  //     setCurrencySign(symbol)
  //   }
  // }

  

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
                value={proComFormData.product_id}
                getOptionLabel={(item) => `${item.id} - ${item.product_name}`}
                onSingleSelectChange={(val) => {
                  handleRemove('product_id')
                  setProComFormData((prev) => ({
                    ...prev,
                    product_id: val,
                  }))
                }}
                error={!!errors.product_id}
                hint={errors.product_id}
              />
            </div>
            <div>
              <Label>Customer Code</Label>
              <Input
                type="text"
                value={proComFormData.customer_code}
                onChange={handleProComChange('customer_code')}
                onKeyUp={() => handleRemove('customer_code')}
                error={!!errors.customer_code}
                hint={errors.customer_code}
              />
            </div>
            <div>
              <Label>Bar Code</Label>
              <Input
                type="text"
                value={proComFormData.bar_code}
                onChange={handleProComChange('bar_code')}
                onKeyUp={() => handleRemove('bar_code')}
                error={!!errors.bar_code}
                hint={errors.bar_code}
              />
            </div>
            <div className="relative">
              <Label>Cost Price</Label>
              <Input
                type="number"
                value={proComFormData.cost_price}
                onChange={handleProComChange('cost_price')}
                onKeyUp={() => handleRemove('cost_price')}
                error={!!errors.cost_price}
                hint={errors.cost_price}
              />
              <span className="absolute right-3 top-[65%] -translate-y-1/2 text-gray-500 text-md flex">
                {/* {currencySign} */}$
              </span>
            </div>
            <div className="relative">
              <Label>Standard Price</Label>
              <Input
                type="number"
                value={proComFormData.standard_price}
                onChange={handleProComChange('standard_price')}
                onKeyUp={() => handleRemove('standard_price')}
                error={!!errors.standard_price}
                hint={errors.standard_price}
              />
              <span className="absolute right-3 top-[65%] -translate-y-1/2 text-gray-500 text-md flex">
                {/* {currencySign} */} $
              </span>
            </div>
            <div className="relative">
              <Label>Currency</Label>
              <SingleSelectInput
                options={currencyData}
                valueKey="value"
                value={proComFormData.currency}
                getOptionLabel={(item) => `${item.id} - ${item.value}`}
                onSingleSelectChange={(val) => {
                  handleRemove('currency')
                  // handleChangeCurrencySign(val)
                  setProComFormData((prev) => ({
                    ...prev,
                    currency: val,
                  }))
                }}
                error={!!errors.currency}
                hint={errors.currency}
              />
            </div>
            <div className="relative">
              <Label>Discount</Label>
              <Input
                type="number"
                value={proComFormData.discount}
                onChange={handleProComChange('discount')}
                onKeyUp={() => handleRemove('discount')}
              />
              <span className="absolute right-3 top-[65%] -translate-y-1/2 text-gray-500 text-md flex">
                %
              </span>
            </div>
            <div className="relative">
              <Label>Supplier</Label>
              <SingleSelectInput
                options={supplierLists}
                valueKey="id"
                value={proComFormData.supplier_id}
                getOptionLabel={(item) => `${item.party_name}`}
                onSingleSelectChange={(val) => {
                  handleRemove('suppplier_id')
                  setProComFormData((prev) => ({
                    ...prev,
                    supplier_id: val,
                  }))
                }}
              />
            </div>
            <div className="relative">
              <Label>Manufacturer</Label>
              <Input
                type="text"
                value={proComFormData.manufacturer}
                onChange={handleProComChange('manufacturer')}
                onKeyUp={() => handleRemove('manufacturer')}
              />
            </div>
            <div className="relative">
              <Label>Country Code</Label>
              <SingleSelectInput
                options={countryCodeData}
                valueKey="code"
                value={proComFormData.country_code}
                getOptionLabel={(item) => `${item.id} - ${item.value}`}
                onSingleSelectChange={(val) => {
                  handleRemove('currency')
                  setProComFormData((prev) => ({
                    ...prev,
                    country_code: val,
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
              onClick={handleProComSubmit}
            >
              Confirm
            </Button>
          </div>
        </div>
      ) : isViewOpen ? (
        <ProductViewModal
          isViewOpen={isViewOpen}
          value={3}
          data={productCommercialData}
          handleCloseModal={handleCloseModal}
        />
      ) : isUpdateOpen ? (
        <ProductCommercialUpdateModal
          data={updateProductCommercialData}
          currencyData={currencyData}
          countryCodeData={countryCodeData}
          productLists={productLists}
          supplierLists={supplierLists}
          value={3}
          isUpdateOpen={isUpdateOpen}
          handleCloseModal={handleCloseModal}
          handleReFetchProComApi={handleReFetchProComApi}
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
                data={productCommercial || []}
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

export default ProductCommercialList;