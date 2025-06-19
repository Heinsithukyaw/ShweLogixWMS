import React,{useState, useEffect} from 'react'
import Button from '../../../../components/ui/button/Button'
import BaseModal from '../../../../components/ui/modal'
import Label from '../../../../components/form/Label'
import Input from '../../../../components/form/input/InputField'
import TextAreaInput from '../../../../components/form/form-elements/TextAreaInput'
import SingleSelectInput from '../../../../components/form/form-elements/SelectInputs'
import Spinner from '../../../../components/ui/loading/spinner'
import { updateLocation } from '../services/locationApi'
import { useAppDispatch, useAppSelector } from '../../../../store/hook'
import { RootState } from '../../../../store/store'
import provideUtility from '../../../../utils/toast'

interface Props {
    isUpdateOpen: true | false
    handleCloseModal: () => void
}

interface Errors {
  location_code?: string
  location_name?: string
  location_type?: string
  zone_id?: any
  capacity?: any
  capacity_unit?: any
  status?: any
}

const LocationUpdateModal:React.FC<Props> = ({isUpdateOpen,handleCloseModal}) => {
   const dispatch = useAppDispatch()
   const [isLoading, setIsLoading] = useState<any>(false)
   const [errors, setErrors] = useState<Errors>({})
   const zoneLists = useAppSelector((state: RootState) => state.zone?.content)
   const location = useAppSelector((state: RootState) => state.mLocation?.data)


   const { showToast } = provideUtility()

     const capacityUnitData = [
       { id: 1, value: 'items' },
       { id: 2, value: 'kg' },
       { id: 3, value: 'm²' },
       { id: 4, value: 'liters' },
       { id: 5, value: 'pallets' },
     ]
   
     const locationTypeData = [
       { id: 1, value: 'Bin' },
       { id: 2, value: 'Rack' },
       { id: 3, value: 'Floor' },
       { id: 4, value: 'Shelf' },
       { id: 5, value: 'Pallet' },
     ]
   
     const statusData = [
       { id: 1, value: 'Available' },
       { id: 2, value: 'Occupied' },
       { id: 3, value: 'Reserved' },
       { id: 4, value: 'Under Maintenance' },
     ]
   
     const restrictionData = [
       { id: 1, value: 'None' },
       { id: 2, value: 'Hazmat' },
       { id: 3, value: 'Cold Storage' },
       { id: 4, value: 'Fragile' },
       { id: 5, value: 'High Value' },
     ]
   
     const [updateFormData, setUpdateFormData] = useState<any>({
       location_code: '',
       location_name: '',
       location_type: '',
       zone_id:'',
       aisle:'',
       row: '',
       level: '',
       bin: '',
       capacity:'',
       capacity_unit: '',
       restrictions:'',
       bar_code:'',
       description:'',
       status: '',
     })

     useEffect(() => {
        if (location) {
          setUpdateFormData({
            id: location.id,
            location_code: location.location_code,
            location_name: location.location_name,
            location_type: location.location_type,
            zone_id: location.zone_id,
            aisle: location.aisle,
            row: location.row,
            level: location.level,
            bin: location.bin,
            capacity: location.capacity,
            capacity_unit: location.capacity_unit,
            restrictions: location.restrictions,
            bar_code: location.bar_code,
            description: location.description,
            status: location.status_value,
          })
        }
    },[location])

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
        const result = (await dispatch(updateLocation(updateFormData, location?.id))) as any

        setIsLoading(false)
        console.log(result?.status)
        if (result?.error.status === 422) {
        setErrors(result?.error.errors)
        } else if (result?.status === true) {
        handleCloseModal()
        showToast('', 'Updated Location Successfully', 'top-right', 'success')
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
          <h2 className="text-xl font-semibold text-gray-800">Edit Location</h2>
          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <Label>
                Location Code<span className="text-error-500">*</span>
              </Label>
              <Input
                type="text"
                value={updateFormData.location_code}
                onChange={handleChange('location_code')}
                onKeyUp={() => handleRemove('location_code')}
                error={!!errors.location_code}
                hint={errors.location_code}
              />
            </div>
            <div>
              <Label>
                Location Name<span className="text-error-500">*</span>
              </Label>
              <Input
                type="text"
                value={updateFormData.location_name}
                onChange={handleChange('location_name')}
                onKeyUp={() => handleRemove('location_name')}
                error={!!errors.location_name}
                hint={errors.location_name}
              />
            </div>

            <div>
              <Label>Location Type</Label>
              <SingleSelectInput
                options={locationTypeData}
                valueKey="value"
                value={updateFormData.location_type}
                getOptionLabel={(item) => `${item.value}`}
                onSingleSelectChange={(val) => {
                  console.log('value - ' + val)
                  handleRemove('location_type')
                  setUpdateFormData((prev: any) => ({
                    ...prev,
                    location_type: val,
                  }))
                }}
                error={!!errors.location_type}
                hint={errors.location_type}
              />
            </div>
            <div>
              <Label>Zone Code</Label>
              <SingleSelectInput
                options={zoneLists}
                valueKey="id"
                value={updateFormData.zone_id}
                getOptionLabel={(item) =>
                  `${item.zone_code} (${item.zone_type})`
                }
                onSingleSelectChange={(val) => {
                  console.log('value - ' + val)
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
            <div className="">
              <Label>Aisle</Label>
              <Input
                type="text"
                value={updateFormData.aisle}
                onChange={handleChange('aisle')}
                onKeyUp={() => handleRemove('aisle')}
              />
            </div>
            <div>
              <Label>Row</Label>
              <Input
                type="text"
                value={updateFormData.row}
                onChange={handleChange('row')}
                onKeyUp={() => handleRemove('row')}
              />
            </div>
            <div>
              <Label>Level</Label>
              <Input
                type="text"
                value={updateFormData.level}
                onChange={handleChange('level')}
                onKeyUp={() => handleRemove('level')}
              />
            </div>
            <div>
              <Label>Bin</Label>
              <Input
                type="text"
                value={updateFormData.bin}
                onChange={handleChange('bin')}
                onKeyUp={() => handleRemove('bin')}
              />
            </div>
            <div>
              <Label>
                Capacity<span className="text-error-500">*</span>
              </Label>
              <Input
                type="number"
                value={updateFormData.capacity}
                onChange={handleChange('capacity')}
                onKeyUp={() => handleRemove('capacity')}
                error={!!errors.capacity}
                hint={errors.capacity}
              />
            </div>
            <div>
              <Label>
                Capacity Unit<span className="text-error-500">*</span>
              </Label>
              <SingleSelectInput
                options={capacityUnitData}
                valueKey="value"
                value={updateFormData.capacity_unit}
                getOptionLabel={(item) => `${item.value}`}
                onSingleSelectChange={(val) => {
                  console.log('value - ' + val)
                  handleRemove('capacity_unit')
                  setUpdateFormData((prev: any) => ({
                    ...prev,
                    capacity_unit: val,
                  }))
                }}
                error={!!errors.capacity_unit}
                hint={errors.capacity_unit}
              />
            </div>
            <div>
              <Label>
                Status<span className="text-error-500">*</span>
              </Label>
              <SingleSelectInput
                options={statusData}
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
            <div>
              <Label>Restriction</Label>
              <SingleSelectInput
                options={restrictionData}
                valueKey="value"
                value={updateFormData.restrictions}
                getOptionLabel={(item) => `${item.value}`}
                onSingleSelectChange={(val) => {
                  console.log('value - ' + val)
                  handleRemove('restrictions')
                  setUpdateFormData((prev: any) => ({
                    ...prev,
                    restrictions: val,
                  }))
                }}
              />
            </div>
            <div>
              <Label>Bar Code</Label>
              <Input
                type="text"
                value={updateFormData.bar_code}
                onChange={handleChange('bar_code')}
                onKeyUp={() => handleRemove('bar_code')}
              />
            </div>
            <div className="col-span-full">
              <Label>Description</Label>
              <TextAreaInput
                value={updateFormData.description}
                onChange={(value) =>
                  handleChange('description')({
                    target: { value },
                  } as React.ChangeEvent<any>)
                }
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

export default LocationUpdateModal