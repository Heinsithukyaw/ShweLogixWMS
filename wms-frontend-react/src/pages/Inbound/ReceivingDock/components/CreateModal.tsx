import React,{useState, useEffect} from 'react'
import Button from '../../../../components/ui/button/Button'
import BaseModal from '../../../../components/ui/modal'
import Label from '../../../../components/form/Label'
import Input from '../../../../components/form/input/InputField'
import TextAreaInput from '../../../../components/form/form-elements/TextAreaInput'
import SingleSelectInput from '../../../../components/form/form-elements/SelectInputs'
import MultiSelectInput from '../../../../components/form/form-elements/MultiSelectInputs'
import Spinner from '../../../../components/ui/loading/spinner'
import { createReceivingDock } from '../services/receivingDockApi'
import { useAppDispatch, useAppSelector } from '../../../../store/hook'
import { RootState } from '../../../../store/store'
import provideUtility from '../../../../utils/toast'

interface Props {
    isCreateOpen: true | false
    handleCloseModal: () => void
}

interface Errors {
  dock_code?: string
  dock_number?: string
  dock_type?: any
  zone_id?:any
  status?: any
}

const ReceivingDockCreateModal:React.FC<Props> = ({isCreateOpen,handleCloseModal}) => {
   const dispatch = useAppDispatch()
   const [isLoading, setIsLoading] = useState<any>(false)
   const [errors, setErrors] = useState<Errors>({})
   const zoneLists = useAppSelector((state: RootState) => state.zone?.content)
   const [zones, setZones] = useState<any>([])

   const { showToast } = provideUtility()

   const dockStatus = [
     {id: 0,value: 'Out Of Services',},
     {id: 1,value: 'In Use',},
     {id: 2,value: 'Available',},
   ]

   const dockTypeData = [
    {id: 0,value: 'Truck',},
    {id: 1,value: 'Container',},
   ]

   const dockFeaturesData = [
     { id: 0, value: 'Standard Dock' },
     { id: 1, value: 'Level Adjuster' },
     { id: 2, value: 'Temperature Controlled' },
     { id: 3, value: 'Double Wide' },
     { id: 4, value: 'High Capacity' },
     { id: 5, value: 'Container Unloading' },
   ]
   
   const [formData, setFormData] = useState<any>({
       dock_code:'',
       dock_number : '',
       dock_type : '',
       zone_id : '',
       features : '',
       additional_features : '',
       status : 0,
     })

     useEffect(() => {
       if (zoneLists) {
         setZones(
           zoneLists.filter(
             (x: any) =>
               (x.area_type === 'Receiving' && x.zone_type === 'Receiving') ||
               (x.area_type === 'Shipping' && x.zone_type === 'Shipping')
           )
         )
       }
       console.log(zoneLists)
     }, [zoneLists])
    

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
      const complete = await dispatch(createReceivingDock(formData))
      setIsLoading(complete?.status)
      if(complete?.error == null){
        handleCloseModal()
        showToast(
          '',
          'Create Receiving Dock Successfully',
          'top-right',
          'success'
        )
      }else if(complete?.error.status == 422){
        setErrors(complete?.error.errors)
      }else{
        showToast(
          '',
          'Failed Receiving Dock Created!',
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
          <h2 className="text-xl font-semibold text-gray-800">Add New Dock</h2>
          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <Label>
                Dock Code<span className="text-error-500">*</span>
              </Label>
              <Input
                type="text"
                value={formData.dock_code}
                onChange={handleChange('dock_code')}
                onKeyUp={() => handleRemove('dock_code')}
                error={!!errors.dock_code}
                hint={errors.dock_code}
              />
            </div>
            <div>
              <Label>
                Dock Number<span className="text-error-500">*</span>
              </Label>
              <Input
                type="text"
                value={formData.dock_number}
                onChange={handleChange('dock_number')}
                onKeyUp={() => handleRemove('dock_number')}
                error={!!errors.dock_number}
                hint={errors.dock_number}
              />
            </div>
            <div>
              <Label>
                Dock Type<span className="text-error-500">*</span>
              </Label>
              <SingleSelectInput
                options={dockTypeData}
                valueKey="value"
                value={formData.dock_type}
                getOptionLabel={(item) => `${item.value}`}
                onSingleSelectChange={(val) => {
                  handleRemove('dock_type')
                  setFormData((prev: any) => ({
                    ...prev,
                    dock_type: val,
                  }))
                }}
                error={!!errors.dock_type}
                hint={errors.dock_type}
              />
            </div>
            <div>
              <Label>
                Zone<span className="text-error-500">*</span>
              </Label>
              <SingleSelectInput
                options={zones}
                valueKey="id"
                value={formData.zone_id}
                getOptionLabel={(item) => `${item.zone_code} (${item.zone_type})`}
                onSingleSelectChange={(val) => {
                  handleRemove('zone_id')
                  setFormData((prev: any) => ({
                    ...prev,
                    zone_id: val,
                  }))
                }}
                error={!!errors.zone_id}
                hint={errors.zone_id}
              />
            </div>
            <div>
              <Label>Features</Label>
              <MultiSelectInput
                options={dockFeaturesData}
                valueKey="value"
                getOptionLabel={(item) => item.value}
                value={formData.features}
                onMultiSelectChange={(val: any) => {
                  handleRemove('features')
                  setFormData((prev: any) => ({
                    ...prev,
                    features: val,
                  }))
                }}
              />
            </div>
            <div className="">
              <Label>Additional Features</Label>
              <TextAreaInput
                value={formData.additional_features}
                onChange={(value) =>
                  handleChange('additional_features')({
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
                options={dockStatus}
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

export default ReceivingDockCreateModal