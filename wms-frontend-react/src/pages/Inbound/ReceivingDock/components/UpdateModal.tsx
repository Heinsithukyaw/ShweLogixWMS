import React,{useState, useEffect} from 'react'
import Button from '../../../../components/ui/button/Button'
import BaseModal from '../../../../components/ui/modal'
import Label from '../../../../components/form/Label'
import Input from '../../../../components/form/input/InputField'
import TextAreaInput from '../../../../components/form/form-elements/TextAreaInput'
import SingleSelectInput from '../../../../components/form/form-elements/SelectInputs'
import MultiSelectInput from '../../../../components/form/form-elements/MultiSelectInputs'
import Spinner from '../../../../components/ui/loading/spinner'
import { updateReceivingDock } from '../services/receivingDockApi'
import { useAppDispatch, useAppSelector } from '../../../../store/hook'
import { RootState } from '../../../../store/store'
import provideUtility from '../../../../utils/toast'

interface Props {
    isUpdateOpen: true | false
    handleCloseModal: () => void
}

interface Errors {
  dock_code?: string
  dock_number?: string
  dock_type?: any
  zone_id?:any
  status?: any
}

const ReceivingDockUpdateModal:React.FC<Props> = ({isUpdateOpen,handleCloseModal}) => {
   const dispatch = useAppDispatch()
   const [zones, setZones] = useState<any>([])
  
   const [isLoading, setIsLoading] = useState<any>(false)
   const [errors, setErrors] = useState<Errors>({})
   const zoneLists = useAppSelector((state: RootState) => state.zone?.content)
   const receivingDock = useAppSelector((state: RootState) => state.receivingDock?.data)


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

   useEffect(() => {
      if (receivingDock) {
        setUpdateFormData({
          id: receivingDock.id,
          dock_code: receivingDock.dock_code,
          dock_number: receivingDock.dock_number,
          dock_type: receivingDock.dock_type,
          zone_id: receivingDock.zone_id,
          features: JSON.parse(receivingDock.features),
          additional_features: receivingDock.additional_features,
          status: receivingDock.status,
        })
      }
    }, [receivingDock])


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
   
   const [updateFormData, setUpdateFormData] = useState<any>({
       dock_code:'',
       dock_number : '',
       dock_type : '',
       zone_id : '',
       features : '',
       additional_features : '',
       status : '',
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
      const result = await dispatch(
        updateReceivingDock(updateFormData, receivingDock?.id)
      )
      setIsLoading(false)
        console.log(result?.status)
      if (result?.error.status === 422) {
        setErrors(result?.error.errors)
      } else if (result?.status === true) {
        handleCloseModal()
        showToast('', 'Updated Receiving Dock Successfully', 'top-right', 'success')
      } else {
        showToast('Error', 'Failed to update Receiving Dock', 'top-right', 'error')
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
          <h2 className="text-xl font-semibold text-gray-800">Edit Dock</h2>
          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <Label>
                Dock Code<span className="text-error-500">*</span>
              </Label>
              <Input
                type="text"
                value={updateFormData.dock_code}
                onChange={handleChange('dock_code')}
                onKeyUp={() => handleRemove('dock_code')}
                error={!!errors.dock_code}
                hint={errors.dock_code}
              />
            </div>
            <div>
              <Label>
                Dock Name<span className="text-error-500">*</span>
              </Label>
              <Input
                type="text"
                value={updateFormData.dock_number}
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
                value={updateFormData.dock_type}
                getOptionLabel={(item) => `${item.value}`}
                onSingleSelectChange={(val) => {
                  handleRemove('dock_type')
                  setUpdateFormData((prev: any) => ({
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
                value={updateFormData.zone_id}
                getOptionLabel={(item) => `${item.zone_code} (${item.zone_type})`}
                onSingleSelectChange={(val) => {
                  handleRemove('zone_id')
                  setUpdateFormData((prev: any) => ({
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
                value={updateFormData.features}
                onMultiSelectChange={(val: any) => {
                  handleRemove('features')
                  setUpdateFormData((prev: any) => ({
                    ...prev,
                    features: val,
                  }))
                }}
              />
            </div>
            <div className="">
              <Label>Additional Features</Label>
              <TextAreaInput
                value={updateFormData.additional_features}
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

export default ReceivingDockUpdateModal