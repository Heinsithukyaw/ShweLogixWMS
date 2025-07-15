import React, { useState, useEffect } from 'react'
import Button from '../../../../components/ui/button/Button'
import BaseModal from '../../../../components/ui/modal'
import Label from '../../../../components/form/Label'
import Input from '../../../../components/form/input/InputField'
import TextAreaInput from '../../../../components/form/form-elements/TextAreaInput'
import SingleSelectInput from '../../../../components/form/form-elements/SelectInputs'
import Spinner from '../../../../components/ui/loading/spinner'
import { updateShipment } from '../services/shipmentApi'
import { useAppDispatch, useAppSelector } from '../../../../store/hook'
import { RootState } from '../../../../store/store'
import provideUtility from '../../../../utils/toast'

interface Props {
  isUpdateOpen: true | false
  handleCloseModal: () => void
}

interface Errors {
  shipment_code?: string
  supplier_id?: any
  carrier_id?: any
  status?: any
}

const ShipmentUpdateModal: React.FC<Props> = ({
  isUpdateOpen,
  handleCloseModal,
}) => {
  const dispatch = useAppDispatch()
  const [isLoading, setIsLoading] = useState<any>(false)
  const [errors, setErrors] = useState<Errors>({})
  const suppliers = useAppSelector(
    (state: RootState) => state.supplier?.content
  )
  const carriers = useAppSelector((state: RootState) => state.carrier?.content)
  const shipment = useAppSelector((state: RootState) => state.shipment?.data)
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

  useEffect(() => {
    if (shipment) {
      setUpdateFormData({
        id: shipment.id,
        shipment_code: shipment.shipment_code,
        supplier_id: shipment.supplier_id,
        carrier_id: shipment.carrier_id,
        purchase_order_number: shipment.purchase_order_number,
        staging_location_id:shipment.staging_location_id,
        expected_arrival: shipment.expected_arrival,
        actual_arrival: shipment.actual_arrival,
        trailer_number: shipment.trailer_number,
        seal_number: shipment.seal_number,
        version_control: shipment.version_control,
        total_weight: shipment.total_weight,
        total_pallet: shipment.total_pallet,
        notes: shipment.notes,
        status: shipment.status,
      })
    }
  }, [shipment])

  const [updateFormData, setUpdateFormData] = useState<any>({
    shipment_code: '',
    supplier_id: '',
    carrier_id: '',
    purchase_order_id: '',
    staging_location_id:'',
    expected_arrival: '',
    actual_arrival: '',
    status: '',
    version_control: '',
    trailer_number: '',
    seal_number: '',
    total_pallet: '',
    total_weight: '',
    notes: '',
  })

  const handleChange =
    (field: string) =>
    (e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement>) => {
      const value = e.target.value
      setUpdateFormData((prev: any) => ({
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

  const handleUpdate = async () => {
    setIsLoading(true)
    const result = (await dispatch(
      updateShipment(updateFormData, shipment?.id)
    )) as any

    setIsLoading(false)
    console.log(result?.status)
    if (result?.error.status === 422) {
      setErrors(result?.error.errors)
    } else if (result?.status === true) {
      handleCloseModal()
      showToast('', 'Updated Shipment Successfully', 'top-right', 'success')
    } else {
      showToast('Error', 'Failed to update Inbound Shipment', 'top-right', 'error')
    }
  }

  return (
    <>
      <BaseModal
        isOpen={isUpdateOpen}
        onClose={handleCloseModal}
        isFullscreen={false}
      >
        <div className="space-y-6">
          <h2 className="text-xl font-semibold text-gray-800">Edit Shipment</h2>
          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <Label>
                Shipment Code<span className="text-error-500">*</span>
              </Label>
              <Input
                type="text"
                value={updateFormData.shipment_code}
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
                value={updateFormData.supplier_id}
                getOptionLabel={(item) => `${item.party_code}`}
                onSingleSelectChange={(val) => {
                  handleRemove('supplier_id')
                  setUpdateFormData((prev: any) => ({
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
                value={updateFormData.version_control}
                getOptionLabel={(item) => `${item.value}`}
                onSingleSelectChange={(val) => {
                  console.log('value - ' + val)
                  handleRemove('version_control')
                  setUpdateFormData((prev: any) => ({
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
                value={updateFormData.purchase_order_id}
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
                value={updateFormData.carrier_id}
                getOptionLabel={(item) => `${item.carrier_code}`}
                onSingleSelectChange={(val) => {
                  handleRemove('carrier_id')
                  setUpdateFormData((prev: any) => ({
                    ...prev,
                    carrier_id: val,
                  }))
                }}
                error={!!errors.carrier_id}
                hint={errors.carrier_id}
              />
            </div>
            <div>
              <Label>Staging Location</Label>
              <SingleSelectInput
                options={stagingLocationLists}
                valueKey="id"
                value={updateFormData.staging_location_id}
                getOptionLabel={(item) => `${item.staging_location_code}`}
                onSingleSelectChange={(val) => {
                  handleRemove('staging_location_id')
                  setUpdateFormData((prev: any) => ({
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
                value={updateFormData.expected_arrival}
                onChange={handleChange('expected_arrival')}
                onKeyUp={() => handleRemove('expected_arrival')}
              />
            </div>
            <div>
              <Label>Actual Arrival</Label>
              <Input
                type="date"
                value={updateFormData.actual_arrival}
                onChange={handleChange('actual_arrival')}
                onKeyUp={() => handleRemove('actual_arrival')}
              />
            </div>
            <div>
              <Label>Trailer Number</Label>
              <Input
                type="text"
                value={updateFormData.trailer_number}
                onChange={handleChange('trailer_number')}
                onKeyUp={() => handleRemove('trailer_number')}
              />
            </div>
            <div>
              <Label>Seal Number</Label>
              <Input
                type="text"
                value={updateFormData.seal_number}
                onChange={handleChange('seal_number')}
                onKeyUp={() => handleRemove('seal_number')}
              />
            </div>
            <div>
              <Label>Total Pallets</Label>
              <Input
                type="number"
                value={updateFormData.total_pallet}
                onChange={handleChange('total_pallet')}
                onKeyUp={() => handleRemove('total_pallet')}
              />
            </div>
            <div>
              <Label>Total Weight</Label>
              <Input
                type="number"
                value={updateFormData.total_weight}
                onChange={handleChange('total_weight')}
                onKeyUp={() => handleRemove('total_weight')}
              />
            </div>
            <div className="col-span-full">
              <Label>notes</Label>
              <TextAreaInput
                value={updateFormData.notes}
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
                value={updateFormData.status}
                getOptionLabel={(item) => `${item.value}`}
                onSingleSelectChange={(val) => {
                  console.log('value - ' + val)
                  handleRemove('status')
                  setUpdateFormData((prev: any) => ({
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

export default ShipmentUpdateModal
