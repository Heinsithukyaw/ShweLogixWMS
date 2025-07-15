import React,{useState} from 'react'
import Button from '../../../../components/ui/button/Button'
import BaseModal from '../../../../components/ui/modal'
import Label from '../../../../components/form/Label'
import Input from '../../../../components/form/input/InputField'
import SingleSelectInput from '../../../../components/form/form-elements/SelectInputs'
import Spinner from '../../../../components/ui/loading/spinner'
import { createPutAwayTask } from '../services/putAwayTaskApi'
import { useAppDispatch, useAppSelector } from '../../../../store/hook'
import { RootState } from '../../../../store/store'
import provideUtility from '../../../../utils/toast'

interface Props {
    isCreateOpen: true | false
    handleCloseModal: () => void
}

interface Errors {
  put_away_task_code?: string
  inbound_shipment_detail_id?: any
  assigned_to_id?: any
  source_location_id?: any
  destination_location_id?: any
  priority?:any
  status?: any
}

const PutAwayTaskCreateModal:React.FC<Props> = ({isCreateOpen,handleCloseModal}) => {
   const dispatch = useAppDispatch()
   const [isLoading, setIsLoading] = useState<any>(false)
   const [errors, setErrors] = useState<Errors>({})
   const shipmentDetailLists = useAppSelector((state: RootState) => state.shipmentDetail?.content)
   const empLists = useAppSelector((state: RootState) => state.employee?.content)
   const locationLists = useAppSelector((state: RootState) => state.location?.content)
   const stagingLocationLists = useAppSelector((state: RootState) => state.stagingLocation?.content)

   const { showToast } = provideUtility()

   const statusData = [
     {
       id: 0,
       value: 'Pending',
     },
     {
       id: 1,
       value: 'In Progress',
     },
     {
       id: 2,
       value: 'Completed',
     },
   ]

   const priorityData = [
     {
       id: 0,
       value: 'low',
     },
     {
       id: 1,
       value: 'Medium',
     },
     {
       id: 2,
       value: 'High',
     },
   ]
   
   const [formData, setFormData] = useState<any>({
     put_away_task_code: '',
     inbound_shipment_detail_id: '',
     assigned_to_id: '',
     created_date: '',
     due_date: '',
     start_time: '',
     complete_time: '',
     source_location_id: '',
     destination_location_id: '',
     qty: '',
     priority: 0,
     status: 0,
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
      const complete = await dispatch(createPutAwayTask(formData))
      setIsLoading(complete?.status)
      if(complete?.error.status == 422){
        setErrors(complete?.error.errors)
      }else{
        handleCloseModal()
        showToast('', 'Create Putaway Task Successfully', 'top-right', 'success')
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
          <h2 className="text-xl font-semibold text-gray-800">Add New Task</h2>
          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <Label>
                Putaway Task Code<span className="text-error-500">*</span>
              </Label>
              <Input
                type="text"
                value={formData.put_away_task_code}
                onChange={handleChange('put_away_task_code')}
                onKeyUp={() => handleRemove('put_away_task_code')}
                error={!!errors.put_away_task_code}
                hint={errors.put_away_task_code}
              />
            </div>
            <div>
              <Label>
                Detail<span className="text-error-500">*</span>
              </Label>
              <SingleSelectInput
                options={shipmentDetailLists}
                valueKey="id"
                value={formData.inbound_shipment_detail_id}
                getOptionLabel={(item) => `${item.inbound_detail_code}`}
                onSingleSelectChange={(val) => {
                  handleRemove('inbound_shipment_detail_id')
                  setFormData((prev: any) => ({
                    ...prev,
                    inbound_shipment_detail_id: val,
                  }))
                }}
                error={!!errors.inbound_shipment_detail_id}
                hint={errors.inbound_shipment_detail_id}
              />
            </div>
            <div>
              <Label>
                Assigned To<span className="text-error-500">*</span>
              </Label>
              <SingleSelectInput
                options={empLists}
                valueKey="id"
                value={formData.assigned_to_id}
                getOptionLabel={(item) => `${item.employee_code}`}
                onSingleSelectChange={(val) => {
                  handleRemove('assigned_to_id')
                  setFormData((prev: any) => ({
                    ...prev,
                    assigned_to_id: val,
                  }))
                }}
                error={!!errors.assigned_to_id}
                hint={errors.assigned_to_id}
              />
            </div>
            {/* <div className="">
              <Label>Item Description</Label>
              <TextAreaInput
                value={formData.item_description}
                onChange={(value) =>
                  handleChange('item_description')({
                    target: { value },
                  } as React.ChangeEvent<any>)
                }
              />
            </div> */}
            <div>
              <Label>Created Date</Label>
              <Input
                type="date"
                value={formData.created_date}
                onChange={handleChange('created_date')}
                onKeyUp={() => handleRemove('created_date')}
              />
            </div>
            <div>
              <Label>Due Date</Label>
              <Input
                type="date"
                value={formData.due_date}
                onChange={handleChange('due_date')}
                onKeyUp={() => handleRemove('due_date')}
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
              <Label>Complete Time</Label>
              <Input
                type="date"
                value={formData.complete_time}
                onChange={handleChange('complete_time')}
                onKeyUp={() => handleRemove('complete_time')}
              />
            </div>
            <div>
              <Label>
                Source Location<span className="text-error-500">*</span>
              </Label>
              <SingleSelectInput
                options={stagingLocationLists}
                valueKey="id"
                value={formData.source_location_id}
                getOptionLabel={(item) => `${item.staging_location_code}`}
                onSingleSelectChange={(val) => {
                  handleRemove('source_location_id')
                  setFormData((prev: any) => ({
                    ...prev,
                    source_location_id: val,
                  }))
                }}
                error={!!errors.source_location_id}
                hint={errors.source_location_id}
              />
            </div>
            <div>
              <Label>
                Destination Location<span className="text-error-500">*</span>
              </Label>
              <SingleSelectInput
                options={locationLists}
                valueKey="id"
                value={formData.destination_location_id}
                getOptionLabel={(item) => `${item.location_code}`}
                onSingleSelectChange={(val) => {
                  handleRemove('destination_location_id')
                  setFormData((prev: any) => ({
                    ...prev,
                    destination_location_id: val,
                  }))
                }}
                error={!!errors.destination_location_id}
                hint={errors.destination_location_id}
              />
            </div>

            <div>
              <Label>Qty</Label>
              <Input
                type="number"
                value={formData.qty}
                onChange={handleChange('qty')}
                onKeyUp={() => handleRemove('qty')}
              />
            </div>
            <div>
              <Label>Priority</Label>
              <SingleSelectInput
                options={priorityData}
                valueKey="id"
                value={formData.priority}
                getOptionLabel={(item) => `${item.value}`}
                onSingleSelectChange={(val) => {
                  handleRemove('priority')
                  setFormData((prev: any) => ({
                    ...prev,
                    priority: val,
                  }))
                }}
                error={!!errors.priority}
                hint={errors.priority}
              />
            </div>

            <div>
              <Label>
                Status<span className="text-error-500">*</span>
              </Label>
              <SingleSelectInput
                options={statusData}
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

export default PutAwayTaskCreateModal