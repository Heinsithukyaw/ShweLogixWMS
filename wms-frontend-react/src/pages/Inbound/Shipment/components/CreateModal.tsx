import React,{useState} from 'react'
import Button from '../../../../components/ui/button/Button'
import BaseModal from '../../../../components/ui/modal'
import Label from '../../../../components/form/Label'
import Input from '../../../../components/form/input/InputField'
import TextAreaInput from '../../../../components/form/form-elements/TextAreaInput'
import SingleSelectInput from '../../../../components/form/form-elements/SelectInputs'
import Spinner from '../../../../components/ui/loading/spinner'
import { createShipment } from '../services/shipmentApi'
import { useAppDispatch, useAppSelector } from '../../../../store/hook'
import { RootState } from '../../../../store/store'
import provideUtility from '../../../../utils/toast'

interface Props {
    isCreateOpen: true | false
    handleCloseModal: () => void
}

interface Errors {
  shipment_code?: string
  supplier_id?: any
  carrier_id?: any
  status?: any
}

const ShipmentCreateModal:React.FC<Props> = ({isCreateOpen,handleCloseModal}) => {
   const dispatch = useAppDispatch()
   const [isLoading, setIsLoading] = useState<any>(false)
   const [errors, setErrors] = useState<Errors>({})
   const suppliers = useAppSelector((state: RootState) => state.supplier?.content)
   const carriers = useAppSelector((state: RootState) => state.carrier?.content)
   const stagingLocationLists = useAppSelector((state: RootState) => state.stagingLocation?.content)

   const { showToast } = provideUtility()

   const asnStatus = [
     { id: 0, value: 'Expected' },
     { id: 1, value: 'In Transit' },
     { id: 2, value: 'Arrived' },
     { id: 3, value: 'Unloaded' },
     { id: 4, value: 'Received' },
   ]

   const versionControlData = [
     { id: 0, value: 'Lite' },
     { id: 1, value: 'Pro' },
     { id: 2, value: 'Legend' },
   ]
   
   const [formData, setFormData] = useState<any>({
        shipment_code: '',
        supplier_id: '',
        carrier_id: '',
        purchase_order_id: '',
        expected_arrival: '',
        actual_arrival: '',
        staging_location_id:'',
        status: 0,
        version_control: 0,
        trailer_number: '',
        seal_number: '',
        total_pallet: '',
        total_weight: '',
        notes: '',
     })

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
      const complete = await dispatch(createShipment(formData))
      setIsLoading(complete?.status)
      if(complete?.error.status == 422){
        setErrors(complete?.error.errors)
      }else{
        handleCloseModal()
        showToast('', 'Create Inbound Shipment Successfully', 'top-right', 'success')
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
            Add New Shipment
          </h2>
          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <Label>
                Shipment Code<span className="text-error-500">*</span>
              </Label>
              <Input
                type="text"
                value={formData.shipment_code}
                onChange={handleChange('shipment_code')}
                onKeyUp={() => handleRemove('shipment_code')}
                error={!!errors.shipment_code}
                hint={errors.shipment_code}
              />
            </div>
            <div>
              <Label>
                Supplier<span className="text-error-500">*</span>
              </Label>
              <SingleSelectInput
                options={suppliers}
                valueKey="id"
                value={formData.supplier_id}
                getOptionLabel={(item) => `${item.party_code}`}
                onSingleSelectChange={(val) => {
                  handleRemove('supplier_id')
                  setFormData((prev: any) => ({
                    ...prev,
                    supplier_id: val,
                  }))
                }}
                error={!!errors.supplier_id}
                hint={errors.supplier_id}
              />
            </div>
            <div>
              <Label>Version Control</Label>
              <SingleSelectInput
                options={versionControlData}
                valueKey="id"
                value={formData.version_control}
                getOptionLabel={(item) => `${item.value}`}
                onSingleSelectChange={(val) => {
                  console.log('value - ' + val)
                  handleRemove('version_control')
                  setFormData((prev: any) => ({
                    ...prev,
                    version_control: val,
                  }))
                }}
              />
            </div>

            <div>
              <Label>Purchase Order Number</Label>
              <Input
                type="text"
                value={formData.purchase_order_id}
                onChange={handleChange('purchase_order_id')}
                onKeyUp={() => handleRemove('purchase_order_id')}
                disabled={true}
              />
            </div>
            <div>
              <Label>
                Carrier<span className="text-error-500">*</span>
              </Label>
              <SingleSelectInput
                options={carriers}
                valueKey="id"
                value={formData.carrier_id}
                getOptionLabel={(item) => `${item.carrier_code}`}
                onSingleSelectChange={(val) => {
                  handleRemove('carrier_id')
                  setFormData((prev: any) => ({
                    ...prev,
                    carrier_id: val,
                  }))
                }}
                error={!!errors.carrier_id}
                hint={errors.carrier_id}
              />
            </div>
            <div>
              <Label>
                Staging Location
              </Label>
              <SingleSelectInput
                options={stagingLocationLists}
                valueKey="id"
                value={formData.staging_location_id}
                getOptionLabel={(item) => `${item.staging_location_code}`}
                onSingleSelectChange={(val) => {
                  handleRemove('staging_location_id')
                  setFormData((prev: any) => ({
                    ...prev,
                    staging_location_id: val,
                  }))
                }}
              />
            </div>
            <div>
              <Label>Expected Arrival</Label>
              <Input
                type="date"
                value={formData.expected_arrival}
                onChange={handleChange('expected_arrival')}
                onKeyUp={() => handleRemove('expected_arrival')}
              />
            </div>
            <div>
              <Label>Actual Arrival</Label>
              <Input
                type="date"
                value={formData.actual_arrival}
                onChange={handleChange('actual_arrival')}
                onKeyUp={() => handleRemove('actual_arrival')}
              />
            </div>
            <div>
              <Label>Trailer Number</Label>
              <Input
                type="text"
                value={formData.trailer_number}
                onChange={handleChange('trailer_number')}
                onKeyUp={() => handleRemove('trailer_number')}
              />
            </div>
            <div>
              <Label>Seal Number</Label>
              <Input
                type="text"
                value={formData.seal_number}
                onChange={handleChange('seal_number')}
                onKeyUp={() => handleRemove('seal_number')}
              />
            </div>
            <div>
              <Label>Total Pallets</Label>
              <Input
                type="number"
                value={formData.total_pallet}
                onChange={handleChange('total_pallet')}
                onKeyUp={() => handleRemove('total_pallet')}
              />
            </div>
            <div>
              <Label>Total Weight</Label>
              <Input
                type="number"
                value={formData.total_weight}
                onChange={handleChange('total_weight')}
                onKeyUp={() => handleRemove('total_weight')}
              />
            </div>
            <div className="col-span-full">
              <Label>notes</Label>
              <TextAreaInput
                value={formData.notes}
                onChange={(value) =>
                  handleChange('notes')({
                    target: { value },
                  } as React.ChangeEvent<any>)
                }
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

export default ShipmentCreateModal