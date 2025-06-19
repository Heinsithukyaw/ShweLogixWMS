import React,{useState, useEffect} from 'react'
import http from '../../../../lib/http'
import Spinner from '../../../../components/ui/loading/spinner'
import provideUtility from '../../../../utils/toast'
import BaseModal from '../../../../components/ui/modal'
import Label from '../../../../components/form/Label'
import Input from '../../../../components/form/input/InputField'
import SingleSelectInput from '../../../../components/form/form-elements/SelectInputs'
import Button from '../../../../components/ui/button/Button'
import TextAreaInput from '../../../../components/form/form-elements/TextAreaInput'

interface Props {
  data: any
  productLists: any
  abcCategoryValueData: any
  abcCategoryActivityData: any
  value: 0 | 1 | 2 | 3 | 4
  isUpdateOpen: true | false
  handleCloseModal: () => void
  handleReFetchProComApi: () => void
}

interface Errors {
  product_id?: any
  manufacture_date?: string
  expire_date?: string
  abc_category_value?: any
  abc_category_activity?: any
}

const ProductOtherUpdateModal: React.FC<Props> = ({
  value,
  data,
  productLists,
  abcCategoryValueData,
  abcCategoryActivityData,
  isUpdateOpen,
  handleCloseModal,
  handleReFetchProComApi,
}) => {
  const { showToast } = provideUtility()

  const [isLoading, setIsLoading] = useState(false)
  const [errors, setErrors] = useState<Errors>({})

  const [updateFormData, setUpdateFormData] = useState({
    id: '',
    product_id: '',
    product_code: '',
    manufacture_date: '',
    expire_date: '',
    abc_category_value: '',
    abc_category_activity: '',
    remark: '',
    custom_attributes: '',
  })

  // function

  useEffect(() => {
    if (value == 4) {
      setErrors({
        product_id: '',
        manufacture_date: '',
        expire_date: '',
        abc_category_value: '',
        abc_category_activity: '',
      })

      if (data) {
        console.log(data)
        setUpdateFormData({
          id: data.id,
          product_id: data.product_id,
          product_code: data.product_code,
          manufacture_date: data.manufacture_date,
          expire_date: data.expire_date ?? '',
          abc_category_value: data.abc_category_value ?? '',
          abc_category_activity: data.abc_category_activity ?? '',
          remark:data.remark,
          custom_attributes:data.custom_attributes 
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
        `/product-others/${updateFormData.id}`,
        updateFormData
      )
      if (response.status === true) {
        handleCloseModal()
        showToast(
          '',
          'Update Product Other successful',
          'top-right',
          'success'
        )
        setUpdateFormData({
          id: '',
          product_id: '',
          product_code: '',
          manufacture_date: '',
          expire_date: '',
          abc_category_value: '',
          abc_category_activity: '',
          remark: '',
          custom_attributes: '',
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
        showToast('', 'Update Product Other failed!', 'top-right', 'error')
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
        <div className="space-y-10">
          <h2 className="text-xl font-semibold text-gray-800">
            Update Product Other Info
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
              <Label>Manufacture Date</Label>
              <Input
                type="date"
                value={updateFormData.manufacture_date}
                onChange={handleChange('manufacture_date')}
                onKeyUp={() => handleRemove('manufacture_date')}
                error={!!errors.manufacture_date}
                hint={errors.manufacture_date}
              />
            </div>
            <div>
              <Label>Expire Date</Label>
              <Input
                type="date"
                value={updateFormData.expire_date}
                onChange={handleChange('expire_date')}
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
                value={updateFormData.abc_category_value}
                getOptionLabel={(item) => `${item.value} - ${item.data}`}
                onSingleSelectChange={(val) => {
                  handleRemove('abc_category_value')
                  setUpdateFormData((prev) => ({
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
                value={updateFormData.abc_category_activity}
                getOptionLabel={(item) => `${item.id} - ${item.value}`}
                onSingleSelectChange={(val) => {
                  handleRemove('abc_category_activity')
                  setUpdateFormData((prev) => ({
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
                value={updateFormData.remark}
                onChange={(value: any) =>
                  handleChange('remark')({
                    target: { value },
                  } as React.ChangeEvent<any>)
                }
              />
            </div>
            <div className="relative">
              <Label>Custom Attributes</Label>
              <TextAreaInput
                value={updateFormData.custom_attributes}
                onChange={(value) =>
                  handleChange('custom_attributes')({
                    target: { value },
                  } as React.ChangeEvent<any>)
                }
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

export default ProductOtherUpdateModal