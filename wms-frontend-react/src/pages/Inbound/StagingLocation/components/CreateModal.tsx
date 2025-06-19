import React,{useState} from 'react'
import Button from '../../../../components/ui/button/Button'
import BaseModal from '../../../../components/ui/modal'
import Label from '../../../../components/form/Label'
import Input from '../../../../components/form/input/InputField'
import TextAreaInput from '../../../../components/form/form-elements/TextAreaInput'
import SingleSelectInput from '../../../../components/form/form-elements/SelectInputs'
import Spinner from '../../../../components/ui/loading/spinner'
import { createStagingLocation } from '../services/stagingLocationApi'
import { useAppDispatch, useAppSelector } from '../../../../store/hook'
import { RootState } from '../../../../store/store'
import provideUtility from '../../../../utils/toast'

interface Props {
    isCreateOpen: true | false
    handleCloseModal: () => void
}

interface Errors {
  staging_location_code?: string
  staging_location_name?: any
  warehouse_id?:any
  zone_id?:any
  area_id?:any
  type?: any
  status?: any
}

const StagingLocationCreateModal:React.FC<Props> = ({isCreateOpen,handleCloseModal}) => {
   const dispatch = useAppDispatch()
   const [isLoading, setIsLoading] = useState<any>(false)
   const [errors, setErrors] = useState<Errors>({})
   const [areas, setAreas] = useState<any>([])
   const [zones, setZones] = useState<any>([])

   const warehouseLists = useAppSelector((state: RootState) => state.warehouse?.content)
   const areaLists = useAppSelector((state: RootState) => state.area?.content)
   const zoneLists = useAppSelector((state: RootState) => state.zone?.content)
  

   const { showToast } = provideUtility()

   const stagingLocationStatus = [
     { id: 0, value: 'In Active' },
     { id: 1, value: 'Maintenance' },
     { id: 2, value: 'Active' },
   ]

   const stagingLocationTypeData = [
     { id: 0, value: 'General' },
     { id: 1, value: 'Cold Storage' },
     { id: 2, value: 'Hazardous Materials' },
   ]
   
   const [formData, setFormData] = useState<any>({
     staging_location_code: '',
     staging_location_name: '',
     type: '',
     warehouse_id:'',
     area_id:'',
     zone_id:'',
     capacity: '',
     description: '',
     current_usage: '',
     status: 0,
     last_updated: '',
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

    const getAreas = (id:any) => {
      setAreas(areaLists.filter((x:any) => x.warehouse_id == id))
      formData.zone_id = ''
    }

    const getZones = (id: any) => {
      setZones(zoneLists.filter((x: any) => x.area_id == id))
    }

    const handleSubmit = async () => {
      setIsLoading(true)
      const complete = await dispatch(createStagingLocation(formData))
      setIsLoading(complete?.status)
      if(complete?.error.status == 422){
        setErrors(complete?.error.errors)
      }else{
        handleCloseModal()
        showToast('', 'Create Staging Location Successfully', 'top-right', 'success')
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
          <h2 className="text-xl font-semibold text-gray-800">Add Staging</h2>
          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <Label>
                Staging Location Code<span className="text-error-500">*</span>
              </Label>
              <Input
                type="text"
                value={formData.staging_location_code}
                onChange={handleChange('staging_location_code')}
                onKeyUp={() => handleRemove('staging_location_code')}
                error={!!errors.staging_location_code}
                hint={errors.staging_location_code}
              />
            </div>

            <div>
              <Label>Staging Location Name</Label>
              <Input
                type="text"
                value={formData.staging_location_name}
                onChange={handleChange('staging_location_name')}
                onKeyUp={() => handleRemove('staging_location_name')}
                error={!!errors.staging_location_name}
                hint={errors.staging_location_name}
              />
            </div>
            <div>
              <Label>
                Type<span className="text-error-500">*</span>
              </Label>
              <SingleSelectInput
                options={stagingLocationTypeData}
                valueKey="value"
                value={formData.type}
                getOptionLabel={(item) => `${item.value}`}
                onSingleSelectChange={(val) => {
                  handleRemove('type')
                  setFormData((prev: any) => ({
                    ...prev,
                    type: val,
                  }))
                }}
                error={!!errors.type}
                hint={errors.type}
              />
            </div>
            <div>
              <Label>
                Warehouse<span className="text-error-500">*</span>
              </Label>
              <SingleSelectInput
                options={warehouseLists}
                valueKey="id"
                value={formData.warehouse_id}
                getOptionLabel={(item) => `${item.warehouse_code}`}
                onSingleSelectChange={(val) => {
                  handleRemove('warehouse_id')
                  getAreas(val)
                  setFormData((prev: any) => ({
                    ...prev,
                    warehouse_id: val,
                  }))
                }}
                error={!!errors.warehouse_id}
                hint={errors.warehouse_id}
              />
            </div>
            <div>
              <Label>
                Area<span className="text-error-500">*</span>
              </Label>
              <SingleSelectInput
                options={areas}
                valueKey="id"
                value={formData.area_id}
                getOptionLabel={(item) =>
                  `${item.area_code} (${item.area_type})`
                }
                onSingleSelectChange={(val) => {
                  handleRemove('area_id')
                  getZones(val)
                  setFormData((prev: any) => ({
                    ...prev,
                    area_id: val,
                  }))
                }}
                error={!!errors.area_id}
                hint={errors.area_id}
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
              <Label>Capacity</Label>
              <Input
                type="number"
                value={formData.capacity}
                onChange={handleChange('capacity')}
                onKeyUp={() => handleRemove('capacity')}
              />
            </div>
            <div>
              <Label>Current Usage</Label>
              <Input
                type="number"
                value={formData.current_usage}
                onChange={handleChange('current_usage')}
                onKeyUp={() => handleRemove('current_usage')}
              />
            </div>
            <div className="col-span-full">
              <Label>Description</Label>
              <TextAreaInput
                value={formData.description}
                onChange={(value) =>
                  handleChange('description')({
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
                options={stagingLocationStatus}
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

export default StagingLocationCreateModal