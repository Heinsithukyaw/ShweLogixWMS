import React,{useState, useEffect} from 'react'
import Button from '../../../../components/ui/button/Button'
import BaseModal from '../../../../components/ui/modal'
import Label from '../../../../components/form/Label'
import Input from '../../../../components/form/input/InputField'
import FileInput from '../../../../components/form/input/FileInput'
import TextAreaInput from '../../../../components/form/form-elements/TextAreaInput'
import SingleSelectInput from '../../../../components/form/form-elements/SelectInputs'
import Spinner from '../../../../components/ui/loading/spinner'
import { updateQualityInspection } from '../services/qualityInspectionApi'
import { useAppDispatch, useAppSelector } from '../../../../store/hook'
import { RootState } from '../../../../store/store'
import provideUtility from '../../../../utils/toast'

interface Props {
    isUpdateOpen: true | false
    handleCloseModal: () => void
}

interface Errors {
  quality_inspection_code?: string
  inbound_shipment_detail_id?: any
  inspector_name?: string
  status?: any
}

const QualityInspectionUpdateModal: React.FC<Props> = ({
  isUpdateOpen,
  handleCloseModal,
}) => {
  const dispatch = useAppDispatch()
  const [isLoading, setIsLoading] = useState<any>(false)
  const [errors, setErrors] = useState<Errors>({})
  const [previewUrl, setPreviewUrl] = useState<string | null>(null)
  const { showToast } = provideUtility()
  const shipmentDetailLists = useAppSelector((state: RootState) => state.shipmentDetail?.content)
  const qualityInspection = useAppSelector((state: RootState) => state.qualityInspection?.data)

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

  useEffect(() => {
        if(qualityInspection){
            setUpdateFormData({
            id: qualityInspection.id,
            quality_inspection_code: qualityInspection.quality_inspection_code,
            inbound_shipment_detail_id: qualityInspection.inbound_shipment_detail_id,
            inspector_name: qualityInspection.inspector_name,
            inspection_date: qualityInspection.inspection_date,
            sample_size: qualityInspection.sample_size,
            rejection_reason: qualityInspection.rejection_reason,
            corrective_action: qualityInspection.corrective_action,
            expiration_date: qualityInspection.expiration_date,
            notes: qualityInspection.notes,
            image: qualityInspection.image,
            status: qualityInspection.status,
            })
        }
    },[qualityInspection])

  const [updateFormData, setUpdateFormData] = useState<any>({
    quality_inspection_code: '',
    inbound_shipment_detail_id: '',
    inspector_name: '',
    inspection_date: '',
    sample_size: '',
    rejection_reason: '',
    corrective_action: '',
    notes: '',
    image: '',
    status: '',
  })

  const handleChange =
    (field: string) =>
    (e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement>) => {
      const value = e.target.value
      setUpdateFormData((prev: any) => ({
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
    setUpdateFormData((prev: any) => ({
      ...prev,
      image: file,
    }))
    
    if (file) {
        setPreviewUrl(URL.createObjectURL(file))
    }
  }

    const handleUpdate = async () => {
       setIsLoading(true)
      //  const formData = new FormData()

      // formData.append('id', updateFormData.id)
      // formData.append('quality_inspection_code', updateFormData.quality_inspection_code)
      // formData.append('inbound_shipment_detail_id', updateFormData.inbound_shipment_detail_id)
      // formData.append('inspector_name', updateFormData.inspector_name)
      // formData.append('inspection_date', updateFormData.inspection_date)
      // formData.append('sample_size', updateFormData.sample_size)
      // formData.append('rejection_reason', updateFormData.rejection_reason)
      // formData.append('corrective_action', updateFormData.corrective_action)
      // formData.append('expiration_date', updateFormData.expiration_date)
      // formData.append('notes', updateFormData.notes)
      // formData.append('image', updateFormData.image)
      // formData.append('status', updateFormData.status)
      const result = (await dispatch(updateQualityInspection(updateFormData, qualityInspection?.id))) as any

      
       setIsLoading(false)
         console.log(result?.status)
       if (result?.error.status === 422) {
         setErrors(result?.error.errors)
       } else if (result?.status === true) {
         handleCloseModal()
         showToast(
           '',
           'Updated Quality Inspection Successfully',
           'top-right',
           'success'
         )
       } else {
         showToast(
           'Error',
           'Failed to update Quality Inspection',
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
            Edit Inspection
          </h2>
          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <Label>
                Inspection Code<span className="text-error-500">*</span>
              </Label>
              <Input
                type="text"
                value={updateFormData.quality_inspection_code}
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
                value={updateFormData.inbound_shipment_detail_id}
                getOptionLabel={(item) => `${item.inbound_detail_code}`}
                onSingleSelectChange={(val) => {
                  handleRemove('inbound_shipment_detail_id')
                  setUpdateFormData((prev: any) => ({
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
                value={updateFormData.inspector_name}
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
                value={updateFormData.inspection_date}
                onChange={handleChange('inspection_date')}
                onKeyUp={() => handleRemove('inspection_date')}
              />
            </div>

            <div>
              <Label>Sample Size</Label>
              <Input
                type="number"
                value={updateFormData.sample_size}
                onChange={handleChange('sample_size')}
                onKeyUp={() => handleRemove('sample_size')}
              />
            </div>
            <div>
              <Label>Rejection Reason</Label>
              <Input
                type="text"
                value={updateFormData.rejection_reason}
                onChange={handleChange('rejection_reason')}
                onKeyUp={() => handleRemove('rejection_reason')}
              />
            </div>
            <div>
              <Label>Corrective Action</Label>
              <Input
                type="text"
                value={updateFormData.corrective_action}
                onChange={handleChange('corrective_action')}
                onKeyUp={() => handleRemove('corrective_action')}
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
              {(updateFormData.image || previewUrl) && (
                <div className="mt-2">
                  <p className="text-sm text-gray-600 mb-1">Preview:</p>
                  <img
                    src={
                      previewUrl
                        ? previewUrl
                        : typeof updateFormData.image === 'string'
                        ? updateFormData.image
                        : ''
                    }
                    alt="Preview"
                    className="max-w-xs max-h-64 rounded-lg border border-gray-300 shadow"
                  />
                </div>
              )}

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

export default QualityInspectionUpdateModal