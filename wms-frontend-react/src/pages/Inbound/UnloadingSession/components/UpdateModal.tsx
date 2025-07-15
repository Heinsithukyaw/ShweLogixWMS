import React,{useState, useEffect} from 'react'
import Button from '../../../../components/ui/button/Button'
import BaseModal from '../../../../components/ui/modal'
import Label from '../../../../components/form/Label'
import Input from '../../../../components/form/input/InputField'
import SingleSelectInput from '../../../../components/form/form-elements/SelectInputs'
import TextAreaInput from '../../../../components/form/form-elements/TextAreaInput'
import MultiSelectInput from '../../../../components/form/form-elements/MultiSelectInputs'
import Spinner from '../../../../components/ui/loading/spinner'
import { updateUnloadingSession } from '../services/unloadingSessionApi'
import { useAppDispatch, useAppSelector } from '../../../../store/hook'
import { RootState } from '../../../../store/store'
import provideUtility from '../../../../utils/toast'

interface Props {
    isUpdateOpen: true | false
    handleCloseModal: () => void
}

interface Errors {
  unloading_session_code?: string
  inbound_shipment_id?: any
  dock_id?: any
  supervisor_id?: any
  status?: any
}

const UnloadingSessionUpdateModal:React.FC<Props> = ({isUpdateOpen,handleCloseModal}) => {
   const dispatch = useAppDispatch()
   const [isLoading, setIsLoading] = useState<any>(false)
   const [errors, setErrors] = useState<Errors>({})
   const dockLists = useAppSelector((state: RootState) => state.dock?.content)
   const shipmentLists = useAppSelector((state: RootState) => state.shipment?.content)
   const mheLists = useAppSelector((state: RootState) => state.materialHandlingEq?.content)
   const supervisorLists = useAppSelector((state: RootState) => state.supervisor?.content)
   const unloadingSession = useAppSelector((state: RootState) => state.unloadingSession?.data)

   const { showToast } = provideUtility()

   const asnStatus = [
     {
       id: 0,
       value: 'Planned',
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

   useEffect(() => {
        if(unloadingSession){
            setUpdateFormData({
                id: unloadingSession.id,
                unloading_session_code: unloadingSession.unloading_session_code,
                inbound_shipment_id: unloadingSession.inbound_shipment_id,
                dock_id: unloadingSession.dock_id,
                supervisor_id: unloadingSession.supervisor_id,
                start_time: unloadingSession.start_time,
                end_time: unloadingSession.end_time,
                equipment_used: JSON.parse(unloadingSession.equipment_used),
                total_items_unloaded: unloadingSession.total_items_unloaded,
                notes: unloadingSession.notes,
                status: unloadingSession.status,
            })
        }
    },[unloadingSession])
   
   const [updateFormData, setUpdateFormData] = useState<any>({
     unloading_session_code: '',
     inbound_shipment_id: '',
     dock_id: '',
     supervisor_id: '',
     start_time: '',
     end_time: '',
     total_pallets_unloaded: '',
     total_items_unloaded: '',
     equipment_used: [],
     notes: '',
     status: 0,
   })

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
        const result = (await dispatch(updateUnloadingSession(updateFormData, unloadingSession?.id))) as any

        setIsLoading(false)
        console.log(result?.status)
        if (result?.error.status === 422) {
        setErrors(result?.error.errors)
        } else if (result?.status === true) {
        handleCloseModal()
        showToast('', 'Updated Unloading Session Successfully', 'top-right', 'success')
        } else {
        showToast('Error', 'Failed to update Unloading Session', 'top-right', 'error')
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
            Edit Unloading Session
          </h2>
          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <Label>
                Unloading Session Code<span className="text-error-500">*</span>
              </Label>
              <Input
                type="text"
                value={updateFormData.unloading_session_code}
                onChange={handleChange('unloading_session_code')}
                onKeyUp={() => handleRemove('unloading_session_code')}
                error={!!errors.unloading_session_code}
                hint={errors.unloading_session_code}
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
              <Label>
                Supervisor<span className="text-error-500">*</span>
              </Label>
              <SingleSelectInput
                options={supervisorLists}
                valueKey="id"
                value={updateFormData.supervisor_id}
                getOptionLabel={(item) => `${item.employee_code}`}
                onSingleSelectChange={(val) => {
                  handleRemove('supervisor_id')
                  setUpdateFormData((prev: any) => ({
                    ...prev,
                    supervisor_id: val,
                  }))
                }}
                error={!!errors.supervisor_id}
                hint={errors.supervisor_id}
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
              <Label>End Time</Label>
              <Input
                type="date"
                value={updateFormData.end_time}
                onChange={handleChange('end_time')}
                onKeyUp={() => handleRemove('end_time')}
              />
            </div>
            <div>
              <Label>Total Pallets Unloaded</Label>
              <Input
                type="number"
                value={updateFormData.total_pallets_unloaded}
                onChange={handleChange('total_pallets_unloaded')}
                onKeyUp={() => handleRemove('total_pallets_unloaded')}
              />
            </div>
            <div>
              <Label>Total Items Unloaded</Label>
              <Input
                type="number"
                value={updateFormData.total_items_unloaded}
                onChange={handleChange('total_items_unloaded')}
                onKeyUp={() => handleRemove('total_items_unloaded')}
              />
            </div>
            <div>
              <Label>Equipment Used</Label>
              <MultiSelectInput
                options={mheLists}
                valueKey='id'
                getOptionLabel={(item) => item.mhe_code}
                value={updateFormData.equipment_used}
                onMultiSelectChange={(val: any) => {
                  handleRemove('equipment_used')
                  setUpdateFormData((prev: any) => ({
                    ...prev,
                    equipment_used: val,
                  }))
                }}
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

export default UnloadingSessionUpdateModal