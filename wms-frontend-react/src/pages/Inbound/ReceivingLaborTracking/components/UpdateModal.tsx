import React,{useState, useEffect} from 'react'
import Button from '../../../../components/ui/button/Button'
import BaseModal from '../../../../components/ui/modal'
import Label from '../../../../components/form/Label'
import Input from '../../../../components/form/input/InputField'
import ToggleSwitchInput from '../../../../components/form/form-elements/ToggleSwitch'
import TextAreaInput from '../../../../components/form/form-elements/TextAreaInput'
import SingleSelectInput from '../../../../components/form/form-elements/SelectInputs'
import Spinner from '../../../../components/ui/loading/spinner'
import { updateReceivingLaborTracking } from '../services/receivingLaborTrackingApi'
import { useAppDispatch, useAppSelector } from '../../../../store/hook'
import { RootState } from '../../../../store/store'
import provideUtility from '../../../../utils/toast'

interface Props {
    isUpdateOpen: true | false
    handleCloseModal: () => void
}

interface Errors {
  labor_entry_code?: string
  emp_id?: any
  inbound_shipment_id?: any
  task_type?: any
}

const ReceivingLaborTrackingUpdateModal:React.FC<Props> = ({isUpdateOpen,handleCloseModal}) => {
   const dispatch = useAppDispatch()
   const [isLoading, setIsLoading] = useState<any>(false)
   const [errors, setErrors] = useState<Errors>({})
   const shipmentLists = useAppSelector((state: RootState) => state.shipment?.content)
   const empLists = useAppSelector((state: RootState) => state.employee?.content)
   const receivingLaborTracking = useAppSelector((state: RootState) => state.receivingLaborTracking?.data)

   const { showToast } = provideUtility()

   const taskTypeData = [
     { id: 0, value: 'Putaway' },
     { id: 1, value: 'Unloading' },
     { id: 2, value: 'Inspection' },
     { id: 3, value: 'Cross-Dock' },
     { id: 4, value: 'Packaging Damage' },
     { id: 5, value: 'Sorting' },
     { id: 6, value: 'Labeling' },
   ]

   useEffect(() => {
      if (receivingLaborTracking) {
        setUpdateFormData({
          id: receivingLaborTracking.id,
          labor_entry_code: receivingLaborTracking.labor_entry_code,
          emp_id: receivingLaborTracking.emp_id,
          inbound_shipment_id: receivingLaborTracking.inbound_shipment_id,
          task_type: receivingLaborTracking.task_type,
          start_time: receivingLaborTracking.start_time,
          end_time: receivingLaborTracking.end_time,
          duration_min: receivingLaborTracking.duration_min,
          items_processed: receivingLaborTracking.items_processed,
          pallets_processed: receivingLaborTracking.pallets_processed,
          items_min: receivingLaborTracking.items_min,
          notes: receivingLaborTracking.notes,
          version_control: receivingLaborTracking.version_control,
          status: receivingLaborTracking.status,
        })
      }
    }, [receivingLaborTracking])
   
   const [updateFormData, setUpdateFormData] = useState<any>({
     labor_entry_code: '',
     emp_id: '',
     inbound_shipment_id: '',
     task_type: '',
     start_time: '',
     end_time: '',
     duration_min: '',
     items_processed: '',
     pallets_processed: '',
     items_min: '',
     notes: '',
     version_control: '',
     status: '',
   })

   const handleToggle = (checked: boolean) => {
     const is_active = checked ? 1 : 0
       setUpdateFormData((prev: any) => ({
         ...prev,
         status: is_active,
       }))
   }

   const handleChange = (field: string) => (e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement>) => {
        const value = e.target.value
        setUpdateFormData((prev:any) => ({
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
      const result = await dispatch(
        updateReceivingLaborTracking(updateFormData, receivingLaborTracking?.id)
      )

      setIsLoading(false)
        console.log(result?.status)
      if (result?.error.status === 422) {
        setErrors(result?.error.errors)
      } else if (result?.status === true) {
        handleCloseModal()
        showToast('', 'Updated Receiving Labor Tracking Successfully', 'top-right', 'success')
      } else {
        showToast('Error', 'Failed to update Receiving Labor Tracking', 'top-right', 'error')
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
          <h2 className="text-xl font-semibold text-gray-800">Edit Labor</h2>
          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <Label>
                Labor Entry Code<span className="text-error-500">*</span>
              </Label>
              <Input
                type="text"
                value={updateFormData.labor_entry_code}
                onChange={handleChange('labor_entry_code')}
                onKeyUp={() => handleRemove('labor_entry_code')}
                error={!!errors.labor_entry_code}
                hint={errors.labor_entry_code}
              />
            </div>
            <div>
              <Label>
                Task Type<span className="text-error-500">*</span>
              </Label>
              <SingleSelectInput
                options={taskTypeData}
                valueKey="value"
                value={updateFormData.task_type}
                getOptionLabel={(item) => `${item.value}`}
                onSingleSelectChange={(val) => {
                  handleRemove('task_type')
                  setUpdateFormData((prev: any) => ({
                    ...prev,
                    task_type: val,
                  }))
                }}
                error={!!errors.task_type}
                hint={errors.task_type}
              />
            </div>
            <div>
              <Label>
                Shipment<span className="text-error-500">*</span>
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
                Employee<span className="text-error-500">*</span>
              </Label>
              <SingleSelectInput
                options={empLists}
                valueKey="id"
                value={updateFormData.emp_id}
                getOptionLabel={(item) =>
                  `${item.employee_code} - ${item.employee_name}`
                }
                onSingleSelectChange={(val) => {
                  handleRemove('emp_id')
                  setUpdateFormData((prev: any) => ({
                    ...prev,
                    emp_id: val,
                  }))
                }}
                error={!!errors.emp_id}
                hint={errors.emp_id}
              />
            </div>

            <div className="">
              <Label>Start Time</Label>
              <Input
                type="date"
                value={updateFormData.start_time}
                onChange={handleChange('start_time')}
                onKeyUp={() => handleRemove('start_time')}
              />
            </div>
            <div>
              <Label>End Time</Label>
              <Input
                type="date"
                value={updateFormData.end_time}
                onChange={handleChange('end_time')}
                onKeyUp={() => handleRemove('end_time')}
              />
            </div>
            <div>
              <Label>Duration (min)</Label>
              <Input
                type="number"
                value={updateFormData.duration_min}
                onChange={handleChange('duration_min')}
                onKeyUp={() => handleRemove('duration_min')}
              />
            </div>

            <div>
              <Label>Items Processed</Label>
              <Input
                type="number"
                value={updateFormData.items_processed}
                onChange={handleChange('items_processed')}
                onKeyUp={() => handleRemove('items_processed')}
              />
            </div>

            <div>
              <Label>Pallets Processed</Label>
              <Input
                type="number"
                value={updateFormData.pallets_processed}
                onChange={handleChange('pallets_processed')}
                onKeyUp={() => handleRemove('pallets_processed')}
              />
            </div>
            <div>
              <Label>Items/Minute</Label>
              <Input
                type="string"
                value={updateFormData.items_min}
                onChange={handleChange('items_min')}
                onKeyUp={() => handleRemove('items_min')}
              />
            </div>
            <div className="col-span-full">
              <Label>Notes</Label>
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
              <Label>Status</Label>
              <ToggleSwitchInput
                label="Enable Active"
                defaultChecked={!!updateFormData.status}
                onToggleChange={handleToggle}
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

export default ReceivingLaborTrackingUpdateModal