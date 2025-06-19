import React,{useState, useEffect} from 'react'
import Button from '../../../../components/ui/button/Button'
import BaseModal from '../../../../components/ui/modal'
import Label from '../../../../components/form/Label'
import Input from '../../../../components/form/input/InputField'
import TextAreaInput from '../../../../components/form/form-elements/TextAreaInput'
import SingleSelectInput from '../../../../components/form/form-elements/SelectInputs'
import Spinner from '../../../../components/ui/loading/spinner'
import { updateCrossDockingTask } from '../services/crossDockingTaskApi'
import { useAppDispatch, useAppSelector } from '../../../../store/hook'
import { RootState } from '../../../../store/store'
import provideUtility from '../../../../utils/toast'

interface Props {
    isUpdateOpen: true | false
    handleCloseModal: () => void
}

interface Errors {
  cross_docking_task_code?: string
  asn_id?: any
  asn_detail_id?: any
  assigned_to_id?: any
  source_location_id?: any
  destination_location_id?: any
  item_id?: any
  priority?: any
  status?: any
}

const CrossDockingTaskUpdateModal:React.FC<Props> = ({isUpdateOpen,handleCloseModal}) => {
   const dispatch = useAppDispatch()
   const [isLoading, setIsLoading] = useState<any>(false)
   const [errors, setErrors] = useState<Errors>({})
   const [sourceLocations, setSourceLocations] = useState<any[]>([])
   const [destinationLocations, setDestinationLocations] = useState<any[]>([])
   const [asnDetails, setAsnDetails] = useState<any[]>([])
   const asnLists = useAppSelector((state: RootState) => state.asn?.content)
   const empLists = useAppSelector((state: RootState) => state.employee?.content)
   const asnDetailLists = useAppSelector((state: RootState) => state.asnDetail?.content)
   const itemLists = useAppSelector((state: RootState) => state.product?.content)
   const locationLists = useAppSelector((state: RootState) => state.location?.content)
   const crossDockingTask = useAppSelector((state: RootState) => state.crossDockingTask?.data)

   const { showToast } = provideUtility()

   const exceptionStatus = [
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
     {
       id: 3,
       value: 'Delayed',
     },
   ]

   const priorityData = [
     {
       id: 0,
       value: 'Low',
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

   useEffect(() => {
      if (crossDockingTask) {
        setAsnDetails(asnDetailLists?.filter((x:any) => x.asn_id == crossDockingTask?.asn_id))
        console.log(crossDockingTask?.exception_type)
        setUpdateFormData({
          id: crossDockingTask.id,
          cross_docking_task_code: crossDockingTask.cross_docking_code,
          asn_id: crossDockingTask.asn_id,
          asn_detail_id: crossDockingTask.asn_detail_id,
          item_id: crossDockingTask.item_id,
          item_description: crossDockingTask.item_description,
          qty: crossDockingTask.qty,
          source_location_id: crossDockingTask.source_location_id,
          destination_location_id: crossDockingTask.destination_location_id,
          assigned_to_id: crossDockingTask.assigned_to_id,
          created_date: crossDockingTask.created_date,
          start_time: crossDockingTask.start_time,
          complete_time: crossDockingTask.complete_time,
          priority: crossDockingTask.priority,
          status: crossDockingTask.status,
        })
      }
    }, [crossDockingTask,asnDetailLists])

   const [updateFormData, setUpdateFormData] = useState<any>({
     cross_docking_task_code: '',
     asn_detail_id: '',
     asn_id: '',
     item_id: '',
     item_description: '',
     qty:'',
     source_location_id: '',
     destination_location_id: '',
     assigned_to_id: '',
     start_time: '',
     complete_time: '',
     created_date: '',
     priority: '',
     status: '',
   })

   useEffect(() => {
      if (locationLists) {
        setSourceLocations(
          locationLists.filter((x: any) => x.zone_type == 'Receiving' && x.area_type == 'Receiving')
        )
  
        setDestinationLocations(
          locationLists.filter(
            (x: any) => x.zone_type == 'Shipping' && x.area_type == 'Shipping'
          )
        )
      }
      console.log(locationLists)
    }, [locationLists])

   const handleGetDetail = (id:any) => {
      setAsnDetails(asnDetailLists?.filter((x:any) => x.asn_id == id))
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
      const complete = await dispatch(updateCrossDockingTask(updateFormData,crossDockingTask?.id))
      setIsLoading(complete?.status)
      if(complete?.error == null){
        handleCloseModal()
        showToast(
          '',
          'Update CrossDocking Task Successfully',
          'top-right',
          'success'
        )
      }else if(complete?.error.status == 422){
        setErrors(complete?.error.errors)
      }else{
        showToast(
          '',
          'Failed CrossDocking Task Created!',
          'top-right',
          'error'
        )
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
          <h2 className="text-xl font-semibold text-gray-800">Edit Task</h2>
          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <Label>
                CrossDocking Task Code<span className="text-error-500">*</span>
              </Label>
              <Input
                type="text"
                value={updateFormData.cross_docking_task_code}
                onChange={handleChange('cross_docking_task_code')}
                onKeyUp={() => handleRemove('cross_docking_task_code')}
                error={!!errors.cross_docking_task_code}
                hint={errors.cross_docking_task_code}
              />
            </div>
            <div>
              <Label>
                ASN<span className="text-error-500">*</span>
              </Label>
              <SingleSelectInput
                options={asnLists}
                valueKey="id"
                value={updateFormData.asn_id}
                getOptionLabel={(item) => `${item.asn_code}`}
                onSingleSelectChange={(val) => {
                  handleRemove('asn_id')
                  handleGetDetail(val)
                  setUpdateFormData((prev: any) => ({
                    ...prev,
                    asn_id: val,
                  }))
                }}
                error={!!errors.asn_id}
                hint={errors.asn_id}
              />
            </div>
            <div>
              <Label>
                ASN Detail<span className="text-error-500">*</span>
              </Label>
              {asnDetails ? (
                <SingleSelectInput
                  options={asnDetails}
                  valueKey="id"
                  value={updateFormData.asn_detail_id}
                  getOptionLabel={(item) => `${item.asn_detail_code}`}
                  onSingleSelectChange={(val) => {
                    handleRemove('asn_detail_id')
                    setUpdateFormData((prev: any) => ({
                      ...prev,
                      asn_detail_id: val,
                    }))
                  }}
                  error={!!errors.asn_detail_id}
                  hint={errors.asn_detail_id}
                />
              ) : (
                <Input type="text" value={''} disabled={true} />
              )}
            </div>
            <div>
              <Label>
                Item<span className="text-error-500">*</span>
              </Label>
              <SingleSelectInput
                options={itemLists}
                valueKey="id"
                value={updateFormData.item_id}
                getOptionLabel={(item) => `${item.product_code}`}
                onSingleSelectChange={(val) => {
                  handleRemove('item_id')
                  setUpdateFormData((prev: any) => ({
                    ...prev,
                    item_id: val,
                  }))
                }}
                error={!!errors.item_id}
                hint={errors.item_id}
              />
            </div>
            <div className="">
              <Label>Item Description</Label>
              <TextAreaInput
                value={updateFormData.item_description}
                onChange={(value) =>
                  handleChange('item_description')({
                    target: { value },
                  } as React.ChangeEvent<any>)
                }
              />
            </div>
            <div>
              <Label>Quantity</Label>
              <Input
                type="number"
                value={updateFormData.qty}
                onChange={handleChange('qty')}
                onKeyUp={() => handleRemove('qty')}
              />
            </div>
            <div>
              <Label>
                Assigned To<span className="text-error-500">*</span>
              </Label>
              <SingleSelectInput
                options={empLists}
                valueKey="id"
                value={updateFormData.assigned_to_id}
                getOptionLabel={(item) => `${item.employee_code}`}
                onSingleSelectChange={(val) => {
                  handleRemove('assigned_to_id')
                  setUpdateFormData((prev: any) => ({
                    ...prev,
                    assigned_to_id: val,
                  }))
                }}
                error={!!errors.assigned_to_id}
                hint={errors.assigned_to_id}
              />
            </div>
            <div>
              <Label>
                Source Location<span className="text-error-500">*</span>
              </Label>
              <SingleSelectInput
                options={sourceLocations}
                valueKey="id"
                value={updateFormData.source_location_id}
                getOptionLabel={(item) => `${item.location_code}`}
                onSingleSelectChange={(val) => {
                  handleRemove('source_location_id')
                  setUpdateFormData((prev: any) => ({
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
                options={destinationLocations}
                valueKey="id"
                value={updateFormData.destination_location_id}
                getOptionLabel={(item) => `${item.location_code}`}
                onSingleSelectChange={(val) => {
                  handleRemove('destination_location_id')
                  setUpdateFormData((prev: any) => ({
                    ...prev,
                    destination_location_id: val,
                  }))
                }}
                error={!!errors.destination_location_id}
                hint={errors.destination_location_id}
              />
            </div>
            <div>
              <Label>Created Date</Label>
              <Input
                type="date"
                value={updateFormData.created_date}
                onChange={handleChange('created_date')}
                onKeyUp={() => handleRemove('created_date')}
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
              <Label>Complete Time</Label>
              <Input
                type="date"
                value={updateFormData.complete_time}
                onChange={handleChange('complete_time')}
                onKeyUp={() => handleRemove('complete_time')}
              />
            </div>
            <div>
              <Label>Priority</Label>
              <SingleSelectInput
                options={priorityData}
                valueKey="id"
                value={updateFormData.priority}
                getOptionLabel={(item) => `${item.value}`}
                onSingleSelectChange={(val) => {
                  handleRemove('priority')
                  setUpdateFormData((prev: any) => ({
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
                options={exceptionStatus}
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

export default CrossDockingTaskUpdateModal