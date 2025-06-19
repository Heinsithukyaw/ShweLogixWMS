import React,{useState, useEffect} from 'react'
import http from '../../../../lib/http'
import Spinner from '../../../../components/ui/loading/spinner'
import provideUtility from '../../../../utils/toast'
import BaseModal from '../../../../components/ui/modal'
import Label from '../../../../components/form/Label'
import Input from '../../../../components/form/input/InputField'
import SingleSelectInput from '../../../../components/form/form-elements/SelectInputs'
import Button from '../../../../components/ui/button/Button'

interface Props {
  data: any
  productLists: any
  supplierLists: any
  countryCodeData: any
  currencyData: any
  value: 0 | 1 | 2 | 3 | 4
  isUpdateOpen: true | false
  handleCloseModal: () => void
  handleReFetchProComApi: () => void
}

interface Errors {
  product_id?: any
  customer_code?: string
  bar_code?: string
  cost_price?: any
  standard_price?: any
  currency?: any
}

const ProductCommercialUpdateModal: React.FC<Props> = ({
  value,
  data,
  productLists,
  supplierLists,
  countryCodeData,
  currencyData,
  isUpdateOpen,
  handleCloseModal,
  handleReFetchProComApi,
}) => {
  const { showToast } = provideUtility()

  const [isLoading, setIsLoading] = useState(false)
  const [errors, setErrors] = useState<Errors>({})

  const [updateFormData, setUpdateFormData] = useState({
    id:'',
    product_id:'',
    product_code: '',
    customer_code: '',
    bar_code: '',
    cost_price: '',
    standard_price: '',
    currency: '',
    discount: '',
    supplier_id:'',
    manufacturer:'',
    country_code:'',
  })

  // function

  useEffect(() => {
    console.log(supplierLists)
    if (value == 3) {
      setErrors({
        product_id: '',
        customer_code: '',
        bar_code: '',
        cost_price: '',
        standard_price: '',
        currency: '',
      })

      if (data) {
        console.log(data)
        setUpdateFormData({
          id: data.id,
          product_id: data.product_id,
          product_code: data.product_code,
          customer_code: data.customer_code,
          bar_code: data.bar_code ?? '',
          cost_price: data.cost_price ?? '',
          standard_price: data.standard_price ?? '',
          currency: data.currency ?? '',
          discount: data.discount ?? '',
          supplier_id: data.supplier_id ?? '',
          manufacturer: data.manufacturer ?? '',
          country_code: data.country_code ?? '',
        })
      }
    }
  }, [data])

  const handleRemove = (field: string) => {
    setErrors((prev) => ({
      ...prev,
      [field]: null,
    }))
  }

  const handleChange =
    (field: string) =>
    (e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement>) => {
      const value = e.target.value
      setUpdateFormData((prev) => ({
        ...prev,
        [field]: value,
      }))
    }

  const handleUpdate = async () => {
    setIsLoading(true)
    setErrors({})
    try {
      const response = await http.putDataWithToken(
        `/product-commercials/${updateFormData.id}`,
        updateFormData
      )
      if (response.status === true) {
        handleCloseModal()
        showToast(
          '',
          'Update Product Commercial successful',
          'top-right',
          'success'
        )
        setUpdateFormData({
          id: '',
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
      } else {
        showToast('', 'Something went wrong!.', 'top-right', 'error')
      }
    } catch (err: any) {
      if (err?.status === 422) {
        showToast('', err?.message, 'top-right', 'error')
        const apiErrors: Errors = err?.errors
        setErrors(apiErrors)
      } else {
        showToast('', 'Update Product Commercial failed!', 'top-right', 'error')
      }
      console.error(err)
    } finally {
      setIsLoading(false)
    }
  }

  return (
    <>
      <BaseModal
        isOpen={isUpdateOpen}
        onClose={handleCloseModal}
        isFullscreen={false}
      >
        <div className="">
          <h2 className="text-xl font-semibold text-gray-800">
            Update Product Commercial
          </h2>
          <div className="">
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <Label>Product</Label>
                <SingleSelectInput
                  options={productLists}
                  valueKey="id"
                  value={updateFormData.product_id}
                  getOptionLabel={(item) => `${item.id} - ${item.product_name}`}
                  onSingleSelectChange={(val) => {
                    handleRemove('product_id')
                    setUpdateFormData((prev) => ({
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
                  value={updateFormData.customer_code}
                  onChange={handleChange('customer_code')}
                  onKeyUp={() => handleRemove('customer_code')}
                  error={!!errors.customer_code}
                  hint={errors.customer_code}
                />
              </div>
              <div>
                <Label>Bar Code</Label>
                <Input
                  type="text"
                  value={updateFormData.bar_code}
                  onChange={handleChange('bar_code')}
                  onKeyUp={() => handleRemove('bar_code')}
                  error={!!errors.bar_code}
                  hint={errors.bar_code}
                />
              </div>
              <div className="relative">
                <Label>Cost Price</Label>
                <Input
                  type="number"
                  value={updateFormData.cost_price}
                  onChange={handleChange('cost_price')}
                  onKeyUp={() => handleRemove('cost_price')}
                  error={!!errors.cost_price}
                  hint={errors.cost_price}
                />
                <span className="absolute right-3 top-[65%] -translate-y-1/2 text-gray-500 text-md flex">
                  $
                </span>
              </div>
              <div className="relative">
                <Label>Standard Price</Label>
                <Input
                  type="number"
                  value={updateFormData.standard_price}
                  onChange={handleChange('standard_price')}
                  onKeyUp={() => handleRemove('standard_price')}
                />
                <span className="absolute right-3 top-[65%] -translate-y-1/2 text-gray-500 text-md flex">
                  $
                </span>
              </div>
              <div className="relative">
                <Label>Currency</Label>
                <SingleSelectInput
                  options={currencyData}
                  valueKey="value"
                  value={updateFormData.currency}
                  getOptionLabel={(item) => `${item.id} - ${item.value}`}
                  onSingleSelectChange={(val) => {
                    handleRemove('currency')
                    setUpdateFormData((prev) => ({
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
                  value={updateFormData.discount}
                  onChange={handleChange('discount')}
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
                  value={updateFormData.supplier_id}
                  getOptionLabel={(item) => `${item.party_name}`}
                  onSingleSelectChange={(val) => {
                    handleRemove('currency')
                    setUpdateFormData((prev) => ({
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
                  value={updateFormData.manufacturer}
                  onChange={handleChange('manufacturer')}
                  onKeyUp={() => handleRemove('manufacturer')}
                />
              </div>
              <div className="relative">
                <Label>Country Code</Label>
                <SingleSelectInput
                  options={countryCodeData}
                  valueKey="code"
                  value={updateFormData.country_code}
                  getOptionLabel={(item) => `${item.id} - ${item.value}`}
                  onSingleSelectChange={(val) => {
                    handleRemove('currency')
                    setUpdateFormData((prev) => ({
                      ...prev,
                      country_code: val,
                    }))
                  }}
                />
              </div>
            </div>
            <div className="flex justify-end gap-2">
              <Button variant="secondary" onClick={handleCloseModal}>
                Cancel
              </Button>
              <Button
                variant="primary"
                startIcon={isLoading && <Spinner size={4} />}
                onClick={handleUpdate}
              >
                Update
              </Button>
            </div>
          </div>
        </div>
      </BaseModal>
    </>
  )
}

export default ProductCommercialUpdateModal