import React,{useState, useEffect} from 'react'
import Button from '../../../../components/ui/button/Button'
import BaseModal from '../../../../components/ui/modal'
import Label from '../../../../components/form/Label'
import Input from '../../../../components/form/input/InputField'
import TextAreaInput from '../../../../components/form/form-elements/TextAreaInput'
import SingleSelectInput from '../../../../components/form/form-elements/SelectInputs'
import Spinner from '../../../../components/ui/loading/spinner'
import { updateReceivingEquipment } from '../services/receivingEquipmentApi'
import { useAppDispatch, useAppSelector } from '../../../../store/hook'
import { RootState } from '../../../../store/store'
import provideUtility from '../../../../utils/toast'

interface Props {
    isUpdateOpen: true | false
    handleCloseModal: () => void
}

interface Errors {
  receiving_equipment_code?: string
  receiving_equipment_name?: string
  receiving_equipment_type?: any
  status?: any
}

const ReceivingEquipmentUpdateModal:React.FC<Props> = ({isUpdateOpen,handleCloseModal}) => {
   const dispatch = useAppDispatch()
   const [isLoading, setIsLoading] = useState<any>(false)
   const [errors, setErrors] = useState<Errors>({})
   const empLists = useAppSelector((state: RootState) => state.employee?.content)
   const receivingEquipment = useAppSelector((state: RootState) => state.receivingEquipment?.data)

   const { showToast } = provideUtility()

   const equipmentStatus = [
     {id: 0,value: 'In Use',},
     {id: 1,value: 'Maintenance',}, 
     {id: 2,value: 'Available',},
   ]

   const equipmentTypeData = [
     { id: 0, value: 'Forklift' },
     { id: 1, value: 'Pallet Jack' },
     { id: 2, value: 'Scanner' },
     { id: 3, value: 'Conveyor' },
     { id: 4, value: 'Hand Truck' },
     { id: 5, value: 'Scales' },
   ]

   useEffect(() => {
    if (receivingEquipment) {
      console.log(receivingEquipment)
      setUpdateFormData({
        id: receivingEquipment.id,
        receiving_equipment_code: receivingEquipment.receiving_equipment_code,
        receiving_equipment_name: receivingEquipment.receiving_equipment_name,
        receiving_equipment_type: receivingEquipment.receiving_equipment_type,
        assigned_to_id: receivingEquipment.assigned_to_id,
        last_maintenance_date: receivingEquipment.last_maintenance_date,
        days_since_maintenance: receivingEquipment.days_since_maintenance,
        notes: receivingEquipment.notes,
        version_control: receivingEquipment.version_control,
        status: receivingEquipment.status,
      })
    }
  }, [receivingEquipment])

   const [updateFormData, setUpdateFormData] = useState<any>({
     receiving_equipment_code: '',
     receiving_equipment_name: '',
     receiving_equipment_type: '',
     assigned_to_id: '',
     last_maintenance_date: '',
     notes: '',
     days_since_maintenance:'',
     version_control:0,
     status: '',
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
        const complete = await dispatch(updateReceivingEquipment(updateFormData,receivingEquipment?.id))
        setIsLoading(complete?.status)
        if(complete?.error == null){
          handleCloseModal()
          showToast(
            '',
            'Update Receiving Equipment Successfully',
            'top-right',
            'success'
          )
        }else if(complete?.error.status == 422){
          setErrors(complete?.error.errors)
        }else{
          showToast(
            '',
            'Failed Receiving Equipment Updated!',
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
          <h2 className="text-xl font-semibold text-gray-800">
            Edit Equipment
          </h2>
          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <Label>
                Receiving Equipment Code
                <span className="text-error-500">*</span>
              </Label>
              <Input
                type="text"
                value={updateFormData.receiving_equipment_code}
                onChange={handleChange('receiving_equipment_code')}
                onKeyUp={() => handleRemove('receiving_equipment_code')}
                error={!!errors.receiving_equipment_code}
                hint={errors.receiving_equipment_code}
              />
            </div>
            <div>
              <Label>
                Receiving Equipment Name
                <span className="text-error-500">*</span>
              </Label>
              <Input
                type="text"
                value={updateFormData.receiving_equipment_name}
                onChange={handleChange('receiving_equipment_name')}
                onKeyUp={() => handleRemove('receiving_equipment_name')}
                error={!!errors.receiving_equipment_name}
                hint={errors.receiving_equipment_name}
              />
            </div>
            <div>
              <Label>
                Receiving Equipment Type
                <span className="text-error-500">*</span>
              </Label>
              <SingleSelectInput
                options={equipmentTypeData}
                valueKey="value"
                value={updateFormData.receiving_equipment_type}
                getOptionLabel={(item) => `${item.value}`}
                onSingleSelectChange={(val) => {
                  handleRemove('receiving_equipment_type')
                  setUpdateFormData((prev: any) => ({
                    ...prev,
                    receiving_equipment_type: val,
                  }))
                }}
                error={!!errors.receiving_equipment_type}
                hint={errors.receiving_equipment_type}
              />
            </div>
            <div>
              <Label>
                Assigned To
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
              />
            </div>
            <div>
              <Label>
                Last Maintenance Date
              </Label>
              <Input
                type="date"
                value={updateFormData.last_maintenance_date}
                onChange={handleChange('last_maintenance_date')}
                onKeyUp={() => handleRemove('last_maintenance_date')}
              />
            </div>
            <div>
              <Label>
                Days Since Maintenance
              </Label>
              <Input
                type="number"
                value={updateFormData.days_since_maintenance}
                onChange={handleChange('days_since_maintenance')}
                onKeyUp={() => handleRemove('days_since_maintenance')}
              />
            </div>
            <div className="">
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
              <Label>
                Status<span className="text-error-500">*</span>
              </Label>
              <SingleSelectInput
                options={equipmentStatus}
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

export default ReceivingEquipmentUpdateModal