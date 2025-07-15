import React,{useState} from 'react'
import Button from '../../../../components/ui/button/Button'
import BaseModal from '../../../../components/ui/modal'
import Label from '../../../../components/form/Label'
import Input from '../../../../components/form/input/InputField'
import ToggleSwitchInput from '../../../../components/form/form-elements/ToggleSwitch'
import TextAreaInput from '../../../../components/form/form-elements/TextAreaInput'
import SingleSelectInput from '../../../../components/form/form-elements/SelectInputs'
import Spinner from '../../../../components/ui/loading/spinner'
import { createReceivingLaborTracking } from '../services/receivingLaborTrackingApi'
import { useAppDispatch, useAppSelector } from '../../../../store/hook'
import { RootState } from '../../../../store/store'
import provideUtility from '../../../../utils/toast'

interface Props {
    isCreateOpen: true | false
    handleCloseModal: () => void
}

interface Errors {
  labor_entry_code?: string
  emp_id?: any
  inbound_shipment_id?: any
  task_type?: any
}

const ReceivingLaborTrackingCreateModal:React.FC<Props> = ({isCreateOpen,handleCloseModal}) => {
   const dispatch = useAppDispatch()
   const [isLoading, setIsLoading] = useState<any>(false)
   const [errors, setErrors] = useState<Errors>({})
   const shipmentLists = useAppSelector((state: RootState) => state.shipment?.content)
   const empLists = useAppSelector((state: RootState) => state.employee?.content)

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
   
   const [formData, setFormData] = useState<any>({
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
     version_control: 0,
     status: 1,
   })

   const handleToggle = (checked: boolean) => {
     const is_active = checked ? 1 : 0
       setFormData((prev: any) => ({
         ...prev,
         status: is_active,
       }))
   }

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
      const complete = await dispatch(createReceivingLaborTracking(formData))
      setIsLoading(complete?.status)
      if(complete?.error == null){
        handleCloseModal()
        showToast(
          '',
          'Create Receiving Labor Tracking Successfully',
          'top-right',
          'success'
        )
      }else if(complete?.error.status == 422){
        setErrors(complete?.error.errors)
      }else{
        showToast(
          '',
          'Failed Receiving Labor Tracking Created!',
          'top-right',
          'error'
        )
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
          <h2 className="text-xl font-semibold text-gray-800">Add New Labor</h2>
          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <Label>
                Labor Entry Code<span className="text-error-500">*</span>
              </Label>
              <Input
                type="text"
                value={formData.labor_entry_code}
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
                value={formData.task_type}
                getOptionLabel={(item) => `${item.value}`}
                onSingleSelectChange={(val) => {
                  handleRemove('task_type')
                  setFormData((prev: any) => ({
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
                Employee<span className="text-error-500">*</span>
              </Label>
              <SingleSelectInput
                options={empLists}
                valueKey="id"
                value={formData.emp_id}
                getOptionLabel={(item) =>
                  `${item.employee_code} - ${item.employee_name}`
                }
                onSingleSelectChange={(val) => {
                  handleRemove('emp_id')
                  setFormData((prev: any) => ({
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
                value={formData.start_time}
                onChange={handleChange('start_time')}
                onKeyUp={() => handleRemove('start_time')}
              />
            </div>
            <div>
              <Label>End Time</Label>
              <Input
                type="date"
                value={formData.end_time}
                onChange={handleChange('end_time')}
                onKeyUp={() => handleRemove('end_time')}
              />
            </div>
            <div>
              <Label>Duration (min)</Label>
              <Input
                type="number"
                value={formData.duration_min}
                onChange={handleChange('duration_min')}
                onKeyUp={() => handleRemove('duration_min')}
              />
            </div>

            <div>
              <Label>Items Processed</Label>
              <Input
                type="number"
                value={formData.items_processed}
                onChange={handleChange('items_processed')}
                onKeyUp={() => handleRemove('items_processed')}
              />
            </div>

            <div>
              <Label>Pallets Processed</Label>
              <Input
                type="number"
                value={formData.pallets_processed}
                onChange={handleChange('pallets_processed')}
                onKeyUp={() => handleRemove('pallets_processed')}
              />
            </div>
            <div>
              <Label>Items/Minute</Label>
              <Input
                type="string"
                value={formData.items_min}
                onChange={handleChange('items_min')}
                onKeyUp={() => handleRemove('items_min')}
              />
            </div>
            <div className="col-span-full">
              <Label>Notes</Label>
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
              <Label>Status</Label>
              <ToggleSwitchInput
                label="Enable Active"
                defaultChecked={!!formData.status}
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

export default ReceivingLaborTrackingCreateModal