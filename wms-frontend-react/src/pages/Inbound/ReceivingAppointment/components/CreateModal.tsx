import React,{useState} from 'react'
import Button from '../../../../components/ui/button/Button'
import BaseModal from '../../../../components/ui/modal'
import Label from '../../../../components/form/Label'
import Input from '../../../../components/form/input/InputField'
import SingleSelectInput from '../../../../components/form/form-elements/SelectInputs'
import Spinner from '../../../../components/ui/loading/spinner'
import { createReceivingAppointment } from '../services/receivingAppointmentApi'
import { useAppDispatch, useAppSelector } from '../../../../store/hook'
import { RootState } from '../../../../store/store'
import provideUtility from '../../../../utils/toast'

interface Props {
    isCreateOpen: true | false
    handleCloseModal: () => void
}

interface Errors {
  appointment_code?: string
  inbound_shipment_id?: any
  supplier_id?: any
  dock_id?: any
  status?: any
}

const ReceivingAppointmentCreateModal: React.FC<Props> = ({
  isCreateOpen,
  handleCloseModal,
}) => {
  const dispatch = useAppDispatch()
  const [isLoading, setIsLoading] = useState<any>(false)
  const [errors, setErrors] = useState<Errors>({})
  const supplierLists = useAppSelector((state: RootState) => state.supplier?.content)
  const dockLists = useAppSelector((state: RootState) => state.dock?.content)
  const shipmentLists = useAppSelector((state: RootState) => state.shipment?.content)


  const { showToast } = provideUtility()

  const asnStatus = [
    {
      id: 0,
      value: 'Scheduled',
    },
    {
      id: 1,
      value: 'Confirmed',
    },
    {
      id: 2,
      value: 'In Progress',
    },
    {
      id: 3,
      value: 'Completed',
    },
    {
      id: 4,
      value: 'Cancelled',
    },
  ]

  const versionControlData = [
    {
      id: 0,
      value: 'Lite',
    },
    {
      id: 1,
      value: 'Pro',
    },
    {
      id: 2,
      value: 'Legend',
    },
  ]

  const [formData, setFormData] = useState<any>({
    appointment_code: '',
    inbound_shipment_id: '',
    supplier_id: '',
    dock_id: '',
    purchase_order_number: '',
    scheduled_date: '',
    start_time: '',
    end_time: '',
    carrier_name: '',
    driver_name: '',
    driver_phone_number: '',
    trailer_number: '',
    estimated_pallet: '',
    check_in_time:'',
    check_out_time:'',
    version_control:0,
    status: 0,
  })

  const handleChange =
    (field: string) =>
    (e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement>) => {
      const value = e.target.value
      setFormData((prev: any) => ({
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
    const complete = await dispatch(createReceivingAppointment(formData))
    setIsLoading(complete?.status)
    if (complete?.error.status == 422) {
      setErrors(complete?.error.errors)
    } else {
      handleCloseModal()
      showToast('', 'Create Receiving Appointment Successfully', 'top-right', 'success')
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
            Add New Receiving Appointment
          </h2>
          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <Label>
                Appointment Code<span className="text-error-500">*</span>
              </Label>
              <Input
                type="text"
                value={formData.appointment_code}
                onChange={handleChange('appointment_code')}
                onKeyUp={() => handleRemove('appointment_code')}
                error={!!errors.appointment_code}
                hint={errors.appointment_code}
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
                Supplier<span className="text-error-500">*</span>
              </Label>
              <SingleSelectInput
                options={supplierLists}
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
              <Label>
                Version Control<span className="text-error-500">*</span>
              </Label>
              <SingleSelectInput
                options={versionControlData}
                valueKey="id"
                value={formData.version_control}
                getOptionLabel={(item) => `${item.value}`}
                onSingleSelectChange={(val) => {
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
                value={formData.purchase_order_number}
                onChange={handleChange('purchase_order_number')}
                onKeyUp={() => handleRemove('purchase_order_number')}
                disabled={true}
              />
            </div>
            <div>
              <Label>Scheduled Date</Label>
              <Input
                type="date"
                value={formData.scheduled_date}
                onChange={handleChange('scheduled_date')}
                onKeyUp={() => handleRemove('scheduled_date')}
              />
            </div>
            <div>
              <Label>Start Time</Label>
              <Input
                type="date"
                value={formData.start_time}
                onChange={handleChange('start_time')}
                onKeyUp={() => handleRemove('start_time')}
              />
            </div>
            <div>
              <Label>Ent Time</Label>
              <Input
                type="date"
                value={formData.end_time}
                onChange={handleChange('end_time')}
                onKeyUp={() => handleRemove('end_time')}
              />
            </div>
            <div>
              <Label>
                Dock<span className="text-error-500">*</span>
              </Label>
              <SingleSelectInput
                options={dockLists}
                valueKey="id"
                value={formData.dock_id}
                getOptionLabel={(item) => `${item.dock_code}`}
                onSingleSelectChange={(val) => {
                  handleRemove('dock_id')
                  setFormData((prev: any) => ({
                    ...prev,
                    dock_id: val,
                  }))
                }}
                error={!!errors.dock_id}
                hint={errors.dock_id}
              />
            </div>

            <div>
              <Label>Carrier Name</Label>
              <Input
                type="text"
                value={formData.carrier_name}
                onChange={handleChange('carrier_name')}
                onKeyUp={() => handleRemove('carrier_name')}
              />
            </div>
            <div>
              <Label>Driver Name</Label>
              <Input
                type="text"
                value={formData.driver_name}
                onChange={handleChange('driver_name')}
                onKeyUp={() => handleRemove('driver_name')}
              />
            </div>
            <div>
              <Label>Driver Phone Number</Label>
              <Input
                type="number"
                value={formData.driver_phone_number}
                onChange={handleChange('driver_phone_number')}
                onKeyUp={() => handleRemove('driver_phone_number')}
              />
            </div>
            <div>
              <Label>Estimated Pallets</Label>
              <Input
                type="number"
                value={formData.estimated_pallet}
                onChange={handleChange('estimated_pallet')}
                onKeyUp={() => handleRemove('estimated_pallet')}
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
              <Label>Check In Time</Label>
              <Input
                type="date"
                value={formData.check_in_time}
                onChange={handleChange('check_in_time')}
                onKeyUp={() => handleRemove('check_in_time')}
              />
            </div>

            <div>
              <Label>Check Out Time</Label>
              <Input
                type="date"
                value={formData.check_out_time}
                onChange={handleChange('check_out_time')}
                onKeyUp={() => handleRemove('check_out_time')}
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

export default ReceivingAppointmentCreateModal