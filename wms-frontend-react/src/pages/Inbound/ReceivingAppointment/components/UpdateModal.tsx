import React,{useState, useEffect} from 'react'
import Button from '../../../../components/ui/button/Button'
import BaseModal from '../../../../components/ui/modal'
import Label from '../../../../components/form/Label'
import Input from '../../../../components/form/input/InputField'
import SingleSelectInput from '../../../../components/form/form-elements/SelectInputs'
import Spinner from '../../../../components/ui/loading/spinner'
import { updateReceivingAppointment } from '../services/receivingAppointmentApi'
import { useAppDispatch, useAppSelector } from '../../../../store/hook'
import { RootState } from '../../../../store/store'
import provideUtility from '../../../../utils/toast'

interface Props {
    isUpdateOpen: true | false
    handleCloseModal: () => void
}

interface Errors {
  appointment_code?: string
  inbound_shipment_id?: any
  supplier_id?: any
  dock_id?: any
  status?: any
}

const ReceivingAppointmentUpdateModal: React.FC<Props> = ({
  isUpdateOpen,
  handleCloseModal,
}) => {
  const dispatch = useAppDispatch()
  const [isLoading, setIsLoading] = useState<any>(false)
  const [errors, setErrors] = useState<Errors>({})
  const supplierLists = useAppSelector((state: RootState) => state.supplier?.content)
  const dockLists = useAppSelector((state: RootState) => state.dock?.content)
  const shipmentLists = useAppSelector((state: RootState) => state.shipment?.content)
  const receivingAppointment = useAppSelector((state: RootState) => state.receivingAppointment?.data)


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

  useEffect(() => {
        if(receivingAppointment){
            setUpdateFormData({
              id: receivingAppointment.id,
              appointment_code: receivingAppointment.appointment_code,
              inbound_shipment_id: receivingAppointment.inbound_shipment_id,
              supplier_id: receivingAppointment.supplier_id,
              dock_id: receivingAppointment.dock_id,
              purchase_order_number: receivingAppointment.purchase_order_number,
              scheduled_date: receivingAppointment.scheduled_date,
              start_time: receivingAppointment.start_time,
              end_time: receivingAppointment.end_time,
              carrier_name: receivingAppointment.carrier_name,
              driver_name: receivingAppointment.driver_name,
              driver_phone_number: receivingAppointment.driver_phone_number,
              trailer_number: receivingAppointment.trailer_number,
              estimated_pallet: receivingAppointment.estimated_pallet,
              check_in_time: receivingAppointment.check_in_time,
              check_out_time: receivingAppointment.check_out_time,
              version_control: receivingAppointment.version_control,
              status: receivingAppointment.status,
            })
        }
    },[receivingAppointment])

  const [updateFormData, setUpdateFormData] = useState<any>({
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
    version_control:'',
    status: '',
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
        const result = (await dispatch(updateReceivingAppointment(updateFormData, receivingAppointment?.id))) as any

        setIsLoading(false)
            console.log(result?.status)
        if (result?.error.status === 422) {
            setErrors(result?.error.errors)
        } else if (result?.status === true) {
            handleCloseModal()
            showToast('', 'Updated Receiving Appointment Successfully', 'top-right', 'success')
        } else {
            showToast('Error', 'Failed to update ASN', 'top-right', 'error')
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
          <h2 className="text-xl font-semibold text-gray-800">
            Edit Receiving Appointment
          </h2>
          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <Label>
                Appointment Code<span className="text-error-500">*</span>
              </Label>
              <Input
                type="text"
                value={updateFormData.appointment_code}
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
                value={updateFormData.inbound_shipment_id}
                getOptionLabel={(item) => `${item.shipment_code}`}
                onSingleSelectChange={(val) => {
                  handleRemove('inbound_shipment_id')
                  setUpdateFormData((prev: any) => ({
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
              <Label>
                Version Control<span className="text-error-500">*</span>
              </Label>
              <SingleSelectInput
                options={versionControlData}
                valueKey="id"
                value={updateFormData.version_control}
                getOptionLabel={(item) => `${item.value}`}
                onSingleSelectChange={(val) => {
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
                value={updateFormData.purchase_order_number}
                onChange={handleChange('purchase_order_number')}
                onKeyUp={() => handleRemove('purchase_order_number')}
                disabled={true}
              />
            </div>
            <div>
              <Label>Scheduled Date</Label>
              <Input
                type="date"
                value={updateFormData.scheduled_date}
                onChange={handleChange('scheduled_date')}
                onKeyUp={() => handleRemove('scheduled_date')}
              />
            </div>
            <div>
              <Label>Start Time</Label>
              <Input
                type="date"
                value={updateFormData.start_time}
                onChange={handleChange('start_time')}
                onKeyUp={() => handleRemove('start_time')}
              />
            </div>
            <div>
              <Label>Ent Time</Label>
              <Input
                type="date"
                value={updateFormData.end_time}
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
                value={updateFormData.dock_id}
                getOptionLabel={(item) => `${item.dock_code}`}
                onSingleSelectChange={(val) => {
                  handleRemove('dock_id')
                  setUpdateFormData((prev: any) => ({
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
                value={updateFormData.carrier_name}
                onChange={handleChange('carrier_name')}
                onKeyUp={() => handleRemove('carrier_name')}
              />
            </div>
            <div>
              <Label>Driver Name</Label>
              <Input
                type="text"
                value={updateFormData.driver_name}
                onChange={handleChange('driver_name')}
                onKeyUp={() => handleRemove('driver_name')}
              />
            </div>
            <div>
              <Label>Driver Phone Number</Label>
              <Input
                type="number"
                value={updateFormData.driver_phone_number}
                onChange={handleChange('driver_phone_number')}
                onKeyUp={() => handleRemove('driver_phone_number')}
              />
            </div>
            <div>
              <Label>Estimated Pallets</Label>
              <Input
                type="number"
                value={updateFormData.estimated_pallet}
                onChange={handleChange('estimated_pallet')}
                onKeyUp={() => handleRemove('estimated_pallet')}
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
              <Label>Check In Time</Label>
              <Input
                type="date"
                value={updateFormData.check_in_time}
                onChange={handleChange('check_in_time')}
                onKeyUp={() => handleRemove('check_in_time')}
              />
            </div>

            <div>
              <Label>Check Out Time</Label>
              <Input
                type="date"
                value={updateFormData.check_out_time}
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

export default ReceivingAppointmentUpdateModal