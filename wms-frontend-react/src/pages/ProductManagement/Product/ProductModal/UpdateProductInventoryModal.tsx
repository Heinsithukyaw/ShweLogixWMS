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
  uomLists: any
  stockRotationPolicyData: any
  value: 0 | 1 | 2 | 3 | 4
  isUpdateOpen: true | false
  handleCloseModal: () => void
  handleReFetchProInvApi: () => void
}

interface Errors {
  product_id?: any
  uom_id?: any
  batch_no?: string
  lot_no?: string
}

const ProductInventoryUpdateModal: React.FC<Props> = ({
  value,
  data,
  productLists,
  uomLists,
  stockRotationPolicyData,
  isUpdateOpen,
  handleCloseModal,
  handleReFetchProInvApi,
}) => {
  const { showToast } = provideUtility()

  const [isLoading, setIsLoading] = useState(false)
  const [errors, setErrors] = useState<Errors>({})

  const [updateFormData, setUpdateFormData] = useState({
    id:'',
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

  // function

  useEffect(() => {
    if (value == 1) {
      setErrors({
        product_id: '',
        uom_id: '',
        batch_no: '',
        lot_no: '',
      })

      if (data) {
     
        setUpdateFormData({
          id:data.id,
          product_id: data.product_id,
          uom_id: data.uom_id,
          warehouse_code: data.warehouse_code,
          location: data.location,
          batch_no: data.batch_no,
          lot_no: data.lot_no,
          packing_qty: data.packing_qty,
          whole_qty: data.whole_qty,
          loose_qty: data.loose_qty,
          reorder_level: data.reorder_level,
          stock_rotation_policy: data.stock_rotation_policy,
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
        `/product-inventories/${updateFormData.id}`,
        updateFormData
      )
      if (response.status === true) {
        handleCloseModal()
        showToast(
          '',
          'Update Product Inventory successful',
          'top-right',
          'success'
        )
        setUpdateFormData({
          id:'',
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
      } else {
        showToast('', 'Something went wrong!.', 'top-right', 'error')
      }
    } catch (err: any) {
      if (err?.status === 422) {
        showToast('', err?.message, 'top-right', 'error')
        const apiErrors: Errors = err?.errors
        setErrors(apiErrors)
      } else {
        showToast('', 'Update Product Inventory failed!', 'top-right', 'error')
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
            Update Product Inventory
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
              <Label>Unit Of Measure</Label>
              <SingleSelectInput
                options={uomLists}
                valueKey="id"
                value={updateFormData.uom_id}
                getOptionLabel={(item) => `${item.id} - ${item.uom_name}`}
                onSingleSelectChange={(val) => {
                  handleRemove('uom_id')
                  setUpdateFormData((prev) => ({
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
                value={updateFormData.warehouse_code}
                onChange={handleChange('warehouse_code')}
                onKeyUp={() => handleRemove('warehouse_code')}
              />
            </div>
            <div>
              <Label>Location</Label>
              <Input
                type="text"
                value={updateFormData.location}
                onChange={handleChange('location')}
                onKeyUp={() => handleRemove('location')}
              />
            </div>
            <div>
              <Label>
                Reorder Level<span className="text-error-500">*</span>
              </Label>
              <Input
                type="number"
                value={updateFormData.reorder_level}
                onChange={handleChange('reorder_level')}
                onKeyUp={() => handleRemove('reorder_level')}
              />
            </div>
            <div>
              <Label>
                Batch No<span className="text-error-500">*</span>
              </Label>
              <Input
                type="text"
                value={updateFormData.batch_no}
                onChange={handleChange('batch_no')}
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
                value={updateFormData.lot_no}
                onChange={handleChange('lot_no')}
                onKeyUp={() => handleRemove('lot_no')}
                error={!!errors.lot_no}
                hint={errors.lot_no}
              />
            </div>
            <div>
              <Label>Packing Qty</Label>
              <Input
                type="number"
                value={updateFormData.packing_qty}
                onChange={handleChange('packing_qty')}
              />
            </div>
            <div>
              <Label>Whole Qty</Label>
              <Input
                type="number"
                value={updateFormData.whole_qty}
                onChange={handleChange('whole_qty')}
              />
            </div>
            <div>
              <Label>Loose Qty</Label>
              <Input
                type="number"
                value={updateFormData.loose_qty}
                onChange={handleChange('loose_qty')}
              />
            </div>
            <div>
              <Label>Stock Rotation Policy</Label>
              <SingleSelectInput
                options={stockRotationPolicyData}
                valueKey="value"
                value={updateFormData.stock_rotation_policy}
                getOptionLabel={(item) => `${item.id} - ${item.value}`}
                onSingleSelectChange={(val) => {
                  handleRemove('stock_rotation_policy')
                  setUpdateFormData((prev) => ({
                    ...prev,
                    stock_rotation_policy: val,
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
      </BaseModal>
    </>
  )
}

export default ProductInventoryUpdateModal