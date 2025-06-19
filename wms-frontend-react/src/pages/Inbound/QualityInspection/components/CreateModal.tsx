import React,{useState} from 'react'
import Button from '../../../../components/ui/button/Button'
import BaseModal from '../../../../components/ui/modal'
import Label from '../../../../components/form/Label'
import Input from '../../../../components/form/input/InputField'
import FileInput from '../../../../components/form/input/FileInput'
import TextAreaInput from '../../../../components/form/form-elements/TextAreaInput'
import SingleSelectInput from '../../../../components/form/form-elements/SelectInputs'
import Spinner from '../../../../components/ui/loading/spinner'
import { createQualityInspection } from '../services/qualityInspectionApi'
import { useAppDispatch, useAppSelector } from '../../../../store/hook'
import { RootState } from '../../../../store/store'
import provideUtility from '../../../../utils/toast'

interface Props {
    isCreateOpen: true | false
    handleCloseModal: () => void
}

interface Errors {
  quality_inspection_code?: string
  inbound_shipment_detail_id?: any
  inspector_name?: string
  status?: any
}

const QualityInspectionCreateModal: React.FC<Props> = ({
  isCreateOpen,
  handleCloseModal,
}) => {
  const dispatch = useAppDispatch()
  const [isLoading, setIsLoading] = useState<any>(false)
  const [errors, setErrors] = useState<Errors>({})
 
  const { showToast } = provideUtility()
  const shipmentDetailLists = useAppSelector((state: RootState) => state.shipmentDetail?.content)

  const asnStatus = [
    {
      id: 0,
      value: 'Pending',
    },
    {
      id: 1,
      value: 'Failed',
    },
    {
      id: 2,
      value: 'Passed',
    },
  ]

  const [formData, setFormData] = useState<any>({
    quality_inspection_code: '',
    inbound_shipment_detail_id: '',
    inspector_name: '',
    inspection_date: '',
    sample_size: '',
    rejection_reason: '',
    corrective_action: '',
    notes: '',
    image: '',
    inspector_id:1,
    status: 0,
  })

  const handleChange =
    (field: string) =>
    (e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement>) => {
      const value = e.target.value
      setFormData((prev: any) => ({
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

  const handleFileChange = (event: React.ChangeEvent<HTMLInputElement>) => {
    const file = event.target.files?.[0]
    setFormData((prev: any) => ({
      ...prev,
      image: file,
    }))
    if (file) {
      console.log('Selected file:', file)
    }
  }

  const handleSubmit = async () => {
    setIsLoading(true)
    const complete = await dispatch(createQualityInspection(formData))
    setIsLoading(complete?.status)
    if (complete?.error.status == 422) {
      setErrors(complete?.error.errors)
    } else {
      handleCloseModal()
      showToast(
        '',
        'Create Quality Inspection Successfully',
        'top-right',
        'success'
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
          <h2 className="text-xl font-semibold text-gray-800">
            Add New Inspection
          </h2>
          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <Label>
                Inspection Code<span className="text-error-500">*</span>
              </Label>
              <Input
                type="text"
                value={formData.quality_inspection_code}
                onChange={handleChange('quality_inspection_code')}
                onKeyUp={() => handleRemove('quality_inspection_code')}
                error={!!errors.quality_inspection_code}
                hint={errors.quality_inspection_code}
              />
            </div>
            <div>
              <Label>
                Inbound Detail<span className="text-error-500">*</span>
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
              <Label>Inspector Name</Label>
              <Input
                type="text"
                value={formData.inspector_name}
                onChange={handleChange('inspector_name')}
                onKeyUp={() => handleRemove('inspector_name')}
                error={!!errors.inspector_name}
                hint={errors.inspector_name}
              />
            </div>
            <div className="">
              <Label>Inspection Date</Label>
              <Input
                type="date"
                value={formData.inspection_date}
                onChange={handleChange('inspection_date')}
                onKeyUp={() => handleRemove('inspection_date')}
              />
            </div>

            <div>
              <Label>Sample Size</Label>
              <Input
                type="number"
                value={formData.sample_size}
                onChange={handleChange('sample_size')}
                onKeyUp={() => handleRemove('sample_size')}
              />
            </div>
            <div>
              <Label>Rejection Reason</Label>
              <Input
                type="text"
                value={formData.rejection_reason}
                onChange={handleChange('rejection_reason')}
                onKeyUp={() => handleRemove('rejection_reason')}
              />
            </div>
            <div>
              <Label>Corrective Action</Label>
              <Input
                type="text"
                value={formData.corrective_action}
                onChange={handleChange('corrective_action')}
                onKeyUp={() => handleRemove('corrective_action')}
              />
            </div>

            <div className="col-span-full">
              <Label>notes</Label>
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
              <Label>Upload file</Label>
              <FileInput onChange={handleFileChange} className="custom-class" />
            </div>
            <div>
              <Label>
                Status<span className="text-error-500">*</span>
              </Label>
              <SingleSelectInput
                options={asnStatus}
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

export default QualityInspectionCreateModal