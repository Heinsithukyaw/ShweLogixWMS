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
  dimensionUseData: any
  value: 0 | 1 | 2 | 3 | 4
  isUpdateOpen: true | false
  handleCloseModal: () => void
  handleReFetchProDimApi: () => void
}

interface Errors {
  product_id?: any
  dimension_use?: any
}

const ProductDimensionUpdateModal: React.FC<Props> = ({
  value,
  data,
  productLists,
  dimensionUseData,
  isUpdateOpen,
  handleCloseModal,
  handleReFetchProDimApi,
}) => {
  const { showToast } = provideUtility()

  const [isLoading, setIsLoading] = useState(false)
  const [errors, setErrors] = useState<Errors>({})

  const [updateFormData, setUpdateFormData] = useState({
    id:'',
    product_id: '',
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

  // function

  useEffect(() => {
    if (value == 2) {
      setErrors({
        product_id: '',
        dimension_use: ''
      })

      if (data) {
        console.log(data)
        setUpdateFormData({
          id: data.id,
          product_id: data.product_id,
          product_code: data.product_code,
          dimension_use: data.dimension_use,
          length: data.length ?? '',
          width: data.width ?? '',
          height: data.height ?? '',
          weight: data.weight ?? '',
          volume: data.volume ?? '',
          storage_volume: data.storage_volume ?? '',
          space_area: data.space_area ?? '',
          units_per_box: data.units_per_box ?? '',
          boxes_per_pallet: data.boxes_per_pallet ?? '',
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
        `/product-dimensions/${updateFormData.id}`,
        updateFormData
      )
      if (response.status === true) {
        handleCloseModal()
        showToast(
          '',
          'Update Product Dimension successful',
          'top-right',
          'success'
        )
        setUpdateFormData({
          id: '',
          product_id: '',
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
      } else {
        showToast('', 'Something went wrong!.', 'top-right', 'error')
      }
    } catch (err: any) {
      if (err?.status === 422) {
        showToast('', err?.message, 'top-right', 'error')
        const apiErrors: Errors = err?.errors
        setErrors(apiErrors)
      } else {
        showToast('', 'Update Product Dimension failed!', 'top-right', 'error')
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
            Update Product Dimension
          </h2>
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
              <Label>Dimension Use</Label>
              <SingleSelectInput
                options={dimensionUseData}
                valueKey="value"
                value={updateFormData.dimension_use}
                getOptionLabel={(item) => `${item.id} - ${item.value}`}
                onSingleSelectChange={(val) => {
                  handleRemove('dimension_use')
                  setUpdateFormData((prev) => ({
                    ...prev,
                    dimension: val,
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
                value={updateFormData.length}
                onChange={handleChange('length')}
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
                value={updateFormData.width}
                onChange={handleChange('width')}
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
                value={updateFormData.height}
                onChange={handleChange('height')}
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
                value={updateFormData.weight}
                onChange={handleChange('weight')}
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
                value={updateFormData.volume}
                onChange={handleChange('volume')}
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
                value={updateFormData.storage_volume}
                onChange={handleChange('storage_volume')}
              />
              <span className="absolute right-3 top-[65%] -translate-y-1/2 text-gray-500 text-md flex">
                cm³
              </span>
            </div>
            <div className='relative'>
              <Label>Space Area</Label>
              <Input
                type="number"
                value={updateFormData.space_area}
                onChange={handleChange('space_area')}
              />
              <span className="absolute right-3 top-[65%] -translate-y-1/2 text-gray-500 text-md flex">
                cm³
              </span>
            </div>
            <div>
              <Label>Units Per Box</Label>
              <Input
                type="number"
                value={updateFormData.units_per_box}
                onChange={handleChange('units_per_box')}
              />
            </div>
            <div>
              <Label>Boxes Per Pallet</Label>
              <Input
                type="number"
                value={updateFormData.boxes_per_pallet}
                onChange={handleChange('boxes_per_pallet')}
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
      </BaseModal>
    </>
  )
}

export default ProductDimensionUpdateModal