import React,{useState} from 'react'
import Button from '../../../../../../components/ui/button/Button'
import BaseModal from '../../../../../../components/ui/modal'
import Label from '../../../../../../components/form/Label'
import Input from '../../../../../../components/form/input/InputField'
import TextAreaInput from '../../../../../../components/form/form-elements/TextAreaInput'
import SingleSelectInput from '../../../../../../components/form/form-elements/SelectInputs'
import Spinner from '../../../../../../components/ui/loading/spinner'
import { createZoneLocation } from '../services/zoneLocationApi'
import { useAppDispatch, useAppSelector } from '../../../../../../store/hook'
import { RootState } from '../../../../../../store/store'
import provideUtility from '../../../../../../utils/toast'
import Tooltip from '@mui/material/Tooltip'
import Slider, {
  SliderThumb,
  SliderValueLabelProps,
} from '@mui/material/Slider'
import { styled } from '@mui/material/styles'

function ValueLabelComponent(props: SliderValueLabelProps) {
  const { children, value } = props

  return (
    <Tooltip enterTouchDelay={0} placement="top" title={value}>
      {children}
    </Tooltip>
  )
}

const iOSBoxShadow =
  '0 3px 1px rgba(0,0,0,0.1),0 4px 8px rgba(0,0,0,0.13),0 0 0 1px rgba(0,0,0,0.02)'

const IOSSlider = styled(Slider)(({ theme }) => ({
  color: '#007bff',
  height: 5,
  padding: '15px 0',
  '& .MuiSlider-thumb': {
    height: 20,
    width: 20,
    backgroundColor: '#fff',
    boxShadow: '0 0 2px 0px rgba(0, 0, 0, 0.1)',
    '&:focus, &:hover, &.Mui-active': {
      boxShadow: '0px 0px 3px 1px rgba(0, 0, 0, 0.1)',
      // Reset on touch devices, it doesn't add specificity
      '@media (hover: none)': {
        boxShadow: iOSBoxShadow,
      },
    },
    '&:before': {
      boxShadow:
        '0px 0px 1px 0px rgba(0,0,0,0.2), 0px 0px 0px 0px rgba(0,0,0,0.14), 0px 0px 1px 0px rgba(0,0,0,0.12)',
    },
  },
  '& .MuiSlider-valueLabel': {
    fontSize: 12,
    fontWeight: 'normal',
    top: -6,
    backgroundColor: 'unset',
    color: theme.palette.text.primary,
    '&::before': {
      display: 'none',
    },
    '& *': {
      background: 'transparent',
      color: '#000',
      ...theme.applyStyles('dark', {
        color: '#fff',
      }),
    },
  },
  '& .MuiSlider-track': {
    border: 'none',
    height: 5,
  },
  '& .MuiSlider-rail': {
    opacity: 0.5,
    boxShadow: 'inset 0px 0px 4px -2px #000',
    backgroundColor: '#d0d0d0',
  },
  ...theme.applyStyles('dark', {
    color: '#0a84ff',
  }),
}))

interface Props {
    isCreateOpen: true | false
    handleCloseModal: () => void
    zoneId:any
}

interface Errors {
  location_code?: string
  location_name?: string
  location_type?: string
//   zone_id?: any
  capacity?: any
  capacity_unit?: any
  status?: any
}

const LocationCreateModal:React.FC<Props> = ({isCreateOpen,handleCloseModal,zoneId}) => {
   const dispatch = useAppDispatch()
   const [isLoading, setIsLoading] = useState<any>(false)
   const [errors, setErrors] = useState<Errors>({})
   const zoneLists = useAppSelector((state: RootState) => state.zone?.content)


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
   
     const [formData, setFormData] = useState<any>({
       location_code: '',
       location_name: '',
       location_type: '',
       zone_id:zoneId,
       aisle:'',
       row: '',
       level: '',
       bin: '',
       capacity:'',
       capacity_unit: '',
       restrictions:'',
       bar_code:'',
       description:'',
       utilization:0,
       status: '',
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
      const complete = await dispatch(createZoneLocation(formData,zoneId))
      setIsLoading(complete?.status)
      if(complete?.error.status == 422){
        setErrors(complete?.error.errors)
      }else{
        handleCloseModal()
        showToast('', 'Create Location Successfully', 'top-right', 'success')
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
          <h2 className="text-xl font-semibold text-gray-800">
            Add New Location By Zone
          </h2>
          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <Label>
                Location Code<span className="text-error-500">*</span>
              </Label>
              <Input
                type="text"
                value={formData.location_code}
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
                value={formData.location_name}
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
                value={formData.location_type}
                getOptionLabel={(item) => `${item.value}`}
                onSingleSelectChange={(val) => {
                  console.log('value - ' + val)
                  handleRemove('location_type')
                  setFormData((prev: any) => ({
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
              <Input
                type="text"
                value={
                  zoneLists?.find((x: any) => x.id == zoneId)?.zone_code || '-'
                }
                disabled={true}
              />
            </div>
            <div className="">
              <Label>Aisle</Label>
              <Input
                type="text"
                value={formData.aisle}
                onChange={handleChange('aisle')}
                onKeyUp={() => handleRemove('aisle')}
              />
            </div>
            <div>
              <Label>Row</Label>
              <Input
                type="text"
                value={formData.row}
                onChange={handleChange('row')}
                onKeyUp={() => handleRemove('row')}
              />
            </div>
            <div>
              <Label>Level</Label>
              <Input
                type="text"
                value={formData.level}
                onChange={handleChange('level')}
                onKeyUp={() => handleRemove('level')}
              />
            </div>
            <div>
              <Label>Bin</Label>
              <Input
                type="text"
                value={formData.bin}
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
                value={formData.capacity}
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
                value={formData.capacity_unit}
                getOptionLabel={(item) => `${item.value}`}
                onSingleSelectChange={(val) => {
                  console.log('value - ' + val)
                  handleRemove('capacity_unit')
                  setFormData((prev: any) => ({
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
            <div>
              <Label>Restriction</Label>
              <SingleSelectInput
                options={restrictionData}
                valueKey="value"
                value={formData.restrictions}
                getOptionLabel={(item) => `${item.value}`}
                onSingleSelectChange={(val) => {
                  console.log('value - ' + val)
                  handleRemove('restrictions')
                  setFormData((prev: any) => ({
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
                value={formData.bar_code}
                onChange={handleChange('bar_code')}
                onKeyUp={() => handleRemove('bar_code')}
              />
            </div>
            <div>
              <Label>Utilization</Label>
              <IOSSlider
                aria-label="utilization"
                value={formData.utilization}
                onChange={(event, newValue) =>
                  setFormData({ ...formData, utilization: Number(newValue) })
                }
                valueLabelDisplay="on"
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

export default LocationCreateModal