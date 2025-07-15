import React,{useState,useEffect} from 'react'
import Button from '../../../../components/ui/button/Button'
import BaseModal from '../../../../components/ui/modal'
import Label from '../../../../components/form/Label'
import Input from '../../../../components/form/input/InputField'
import SingleSelectInput from '../../../../components/form/form-elements/SelectInputs'
import Spinner from '../../../../components/ui/loading/spinner'
import { createShipmentDetail } from '../services/shipmentDetailApi'
import { useAppDispatch, useAppSelector } from '../../../../store/hook'
import { RootState } from '../../../../store/store'
import provideUtility from '../../../../utils/toast'

interface Props {
    isCreateOpen: true | false
    handleCloseModal: () => void
}

interface Errors {
  inbound_detail_code?: string
  inbound_shipment_id?: any
  product_id?: any
  location_id?: any
  status?: any
}

const ShipmentDetailCreateModal:React.FC<Props> = ({isCreateOpen,handleCloseModal}) => {
   const dispatch = useAppDispatch()
   const [isLoading, setIsLoading] = useState<any>(false)
   const [errors, setErrors] = useState<Errors>({})
   const shipmentLists = useAppSelector((state: RootState) => state.shipment?.content)
   const productLists = useAppSelector((state: RootState) => state.product?.content)
   const locationLists = useAppSelector((state: RootState) => state.location?.content)
   const [storageLocations, setStorageLocations] = useState<any>([])

   const { showToast } = provideUtility()

   const asnStatus = [
     {
       id: 0,
       value: 'Exception',
     },
     {
       id: 1,
       value: 'Expected',
     },
     {
       id: 2,
       value: 'Received',
     },
   ]
   
   const [formData, setFormData] = useState<any>({
     inbound_detail_code: '',
     inbound_shipment_id: '',
     product_id: '',
     purchase_order_id: '',
     expected_qty: '',
     received_qty: '',
     damaged_qty: '',
     lot_number: '',
     expiration_date: '',
     received_by: '',
     location_id: '',
     status: 0,
   })

   useEffect(() => {
    if(locationLists){
      setStorageLocations(locationLists.filter((x:any) => x.zone_type == 'Storage'))
    }
    console.log(locationLists)
   },[locationLists])

   const handleChange = (field: string) => (e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement>) => {
        const value = e.target.value
        setFormData((prev:any) => ({
            ...prev,
            [field]: value,
        }))
    }

    const handleRemove = (field: string) => {
      setErrors((prev) => ({
        ...prev,
        [field]: null,
      }))
    }

    const handleSubmit = async () => {
      setIsLoading(true)
      const complete = await dispatch(createShipmentDetail(formData))
      setIsLoading(complete?.status)
      if(complete?.error.status == 422){
        setErrors(complete?.error.errors)
      }else{
        handleCloseModal()
        showToast('', 'Create Shipment Detail Successfully', 'top-right', 'success')
      }
    }

  return (
    <>
      <BaseModal
        isOpen={isCreateOpen}
        onClose={handleCloseModal}
        isFullscreen={false}
      >
        <div className="space-y-6">
          <h2 className="text-xl font-semibold text-gray-800">
            Add New Shipment Detail
          </h2>
          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <Label>
                Shipment Detail Code<span className="text-error-500">*</span>
              </Label>
              <Input
                type="text"
                value={formData.inbound_detail_code}
                onChange={handleChange('inbound_detail_code')}
                onKeyUp={() => handleRemove('inbound_detail_code')}
                error={!!errors.inbound_detail_code}
                hint={errors.inbound_detail_code}
              />
            </div>
            <div>
              <Label>
                Shipment Code<span className="text-error-500">*</span>
              </Label>
              <SingleSelectInput
                options={shipmentLists}
                valueKey="id"
                value={formData.inbound_shipment_id}
                getOptionLabel={(item) => `${item.shipment_code}`}
                onSingleSelectChange={(val) => {
                  handleRemove('inbound_shipment_id')
                  setFormData((prev: any) => ({
                    ...prev,
                    inbound_shipment_id: val,
                  }))
                }}
                error={!!errors.inbound_shipment_id}
                hint={errors.inbound_shipment_id}
              />
            </div>
            <div>
              <Label>
                Product<span className="text-error-500">*</span>
              </Label>
              <SingleSelectInput
                options={productLists}
                valueKey="id"
                value={formData.product_id}
                getOptionLabel={(item) => `${item.product_code}`}
                onSingleSelectChange={(val) => {
                  handleRemove('product_id')
                  setFormData((prev: any) => ({
                    ...prev,
                    product_id: val,
                  }))
                }}
                error={!!errors.product_id}
                hint={errors.product_id}
              />
            </div>
            <div>
              <Label>Purchase Order Number</Label>
              <Input
                type="string"
                value={formData.purchase_order_number}
                onChange={handleChange('purchase_order_number')}
                onKeyUp={() => handleRemove('purchase_order_number')}
                disabled={true}
              />
            </div>
            <div>
              <Label>Expected Qty</Label>
              <Input
                type="number"
                value={formData.expected_qty}
                onChange={handleChange('expected_qty')}
                onKeyUp={() => handleRemove('expected_qty')}
              />
            </div>
            <div>
              <Label>Received Qty</Label>
              <Input
                type="number"
                value={formData.received_qty}
                onChange={handleChange('received_qty')}
                onKeyUp={() => handleRemove('received_qty')}
              />
            </div>
            <div>
              <Label>Damaged Qty</Label>
              <Input
                type="number"
                value={formData.damaged_qty}
                onChange={handleChange('damaged_qty')}
                onKeyUp={() => handleRemove('damaged_qty')}
              />
            </div>
            <div>
              <Label>Lot Number</Label>
              <Input
                type="text"
                value={formData.lot_number}
                onChange={handleChange('lot_number')}
                onKeyUp={() => handleRemove('lot_number')}
              />
            </div>
            <div>
              <Label>Expiration Date</Label>
              <Input
                type="date"
                value={formData.expiration_date}
                onChange={handleChange('expiration_date')}
                onKeyUp={() => handleRemove('expiration_date')}
              />
            </div>
            <div>
              <Label>Received By</Label>
              <Input
                type="text"
                value={formData.received_by}
                onChange={handleChange('received_by')}
                onKeyUp={() => handleRemove('received_by')}
              />
            </div>
            <div>
              <Label>Received Date</Label>
              <Input
                type="date"
                value={formData.received_date}
                onChange={handleChange('received_date')}
                onKeyUp={() => handleRemove('received_date')}
              />
            </div>
            <div>
              <Label>
                Location<span className="text-error-500">*</span>
              </Label>
              <SingleSelectInput
                options={storageLocations}
                valueKey="id"
                value={formData.location_id}
                getOptionLabel={(item) => `${item.location_code}`}
                onSingleSelectChange={(val) => {
                  handleRemove('location_id')
                  setFormData((prev: any) => ({
                    ...prev,
                    location_id: val,
                  }))
                }}
                error={!!errors.location_id}
                hint={errors.location_id}
              />
            </div>
            <div>
              <Label>
                Status<span className="text-error-500">*</span>
              </Label>
              <SingleSelectInput
                options={asnStatus}
                valueKey="id"
                value={formData.status}
                getOptionLabel={(item) => `${item.value}`}
                onSingleSelectChange={(val) => {
                  console.log('value - ' + val)
                  handleRemove('status')
                  setFormData((prev: any) => ({
                    ...prev,
                    status: val,
                  }))
                }}
                error={!!errors.status}
                hint={errors.status}
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
              onClick={handleSubmit}
            >
              Confirm
            </Button>
          </div>
        </div>
      </BaseModal>
    </>
  )
}

export default ShipmentDetailCreateModal