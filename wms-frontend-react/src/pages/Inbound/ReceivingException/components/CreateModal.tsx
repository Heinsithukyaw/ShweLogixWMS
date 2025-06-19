import React,{useState} from 'react'
import Button from '../../../../components/ui/button/Button'
import BaseModal from '../../../../components/ui/modal'
import Label from '../../../../components/form/Label'
import Input from '../../../../components/form/input/InputField'
import TextAreaInput from '../../../../components/form/form-elements/TextAreaInput'
import SingleSelectInput from '../../../../components/form/form-elements/SelectInputs'
import Spinner from '../../../../components/ui/loading/spinner'
import { createReceivingException } from '../services/receivingExceptionApi'
import { useAppDispatch, useAppSelector } from '../../../../store/hook'
import { RootState } from '../../../../store/store'
import provideUtility from '../../../../utils/toast'

interface Props {
    isCreateOpen: true | false
    handleCloseModal: () => void
}

interface Errors {
  exception_code?: string
  asn_id?: any
  asn_detail_id?: any
  exception_type?:any
  item_id?: any
  severity?:any
  status?: any
}

const ReceivingExceptionCreateModal:React.FC<Props> = ({isCreateOpen,handleCloseModal}) => {
   const dispatch = useAppDispatch()
   const [isLoading, setIsLoading] = useState<any>(false)
   const [errors, setErrors] = useState<Errors>({})
   const [asnDetails, setAsnDetails] = useState<any[]>([])
   const asnLists = useAppSelector((state: RootState) => state.asn?.content)
   const empLists = useAppSelector((state: RootState) => state.employee?.content)
   const asnDetailLists = useAppSelector((state: RootState) => state.asnDetail?.content)
   const itemLists = useAppSelector((state: RootState) => state.product?.content)

   const { showToast } = provideUtility()

   const exceptionStatus = [
     {
       id: 0,
       value: 'Pending Info',
     },
     {
       id: 1,
       value: 'In Progress',
     },
     {
       id: 2,
       value: 'Open',
     },
     {
       id: 3,
       value: 'Resolved',
     },
   ]

   const severityData = [
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

   const exceptionTypeData = [
     { id: 0, value: 'Quantity Mismatch' },
     { id: 1, value: 'Quality Issue' },
     { id: 2, value: 'Documentation Issue' },
     { id: 3, value: 'Item Mismatch' },
     { id: 4, value: 'Packaging Damage' },
     { id: 5, value: 'Missing Item' },
     { id: 6, value: 'Extra Item' },
     { id: 7, value: 'Late Delivery' },
     { id: 8, value: 'Wrong Location' },
     { id: 9, value: 'Other' },
   ]
   
   const [formData, setFormData] = useState<any>({
       exception_code:'',
       asn_detail_id : '',
       asn_id : '',
       item_id : '',
       item_description:'',
       exception_type : '',
       reported_by_id : '',
       assigned_to_id : '',
       reported_date : '',
       resolved_date : '',
       severity: '',
       description:'',
       status : 0,
     })

   const handleGetDetail = (id:any) => {
      setAsnDetails(asnDetailLists?.filter((x:any) => x.asn_id == id))
   }

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
      const complete = await dispatch(createReceivingException(formData))
      setIsLoading(complete?.status)
      if(complete?.error == null){
        handleCloseModal()
        showToast(
          '',
          'Create Receiving Exception Successfully',
          'top-right',
          'success'
        )
      }else if(complete?.error.status == 422){
        setErrors(complete?.error.errors)
      }else{
        showToast(
          '',
          'Failed Receiving Exception Created!',
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
          <h2 className="text-xl font-semibold text-gray-800">
            Add New Exception
          </h2>
          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <Label>
                Exception Code<span className="text-error-500">*</span>
              </Label>
              <Input
                type="text"
                value={formData.exception_code}
                onChange={handleChange('exception_code')}
                onKeyUp={() => handleRemove('exception_code')}
                error={!!errors.exception_code}
                hint={errors.exception_code}
              />
            </div>
            <div>
              <Label>
                Exception Type<span className="text-error-500">*</span>
              </Label>
              <SingleSelectInput
                options={exceptionTypeData}
                valueKey="value"
                value={formData.exception_type}
                getOptionLabel={(item) => `${item.value}`}
                onSingleSelectChange={(val) => {
                  handleRemove('exception_type')
                  setFormData((prev: any) => ({
                    ...prev,
                    exception_type: val,
                  }))
                }}
                error={!!errors.exception_type}
                hint={errors.exception_type}
              />
            </div>
            <div>
              <Label>
                ASN<span className="text-error-500">*</span>
              </Label>
              <SingleSelectInput
                options={asnLists}
                valueKey="id"
                value={formData.asn_id}
                getOptionLabel={(item) => `${item.asn_code}`}
                onSingleSelectChange={(val) => {
                  handleRemove('asn_id')
                  handleGetDetail(val)
                  setFormData((prev: any) => ({
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
                  value={formData.asn_detail_id}
                  getOptionLabel={(item) => `${item.asn_detail_code}`}
                  onSingleSelectChange={(val) => {
                    handleRemove('asn_detail_id')
                    setFormData((prev: any) => ({
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
                value={formData.item_id}
                getOptionLabel={(item) => `${item.product_code}`}
                onSingleSelectChange={(val) => {
                  handleRemove('item_id')
                  setFormData((prev: any) => ({
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
                value={formData.item_description}
                onChange={(value) =>
                  handleChange('item_description')({
                    target: { value },
                  } as React.ChangeEvent<any>)
                }
              />
            </div>
            <div>
              <Label>Reported By</Label>
              <SingleSelectInput
                options={empLists}
                valueKey="id"
                value={formData.reported_by_id}
                getOptionLabel={(item) => `${item.employee_code}`}
                onSingleSelectChange={(val) => {
                  handleRemove('reported_by_id')
                  setFormData((prev: any) => ({
                    ...prev,
                    reported_by_id: val,
                  }))
                }}
              />
            </div>
            <div>
              <Label>Assigned To</Label>
              <SingleSelectInput
                options={empLists}
                valueKey="id"
                value={formData.assigned_to_id}
                getOptionLabel={(item) => `${item.employee_code}`}
                onSingleSelectChange={(val) => {
                  handleRemove('assigned_to_id')
                  setFormData((prev: any) => ({
                    ...prev,
                    assigned_to_id: val,
                  }))
                }}
              />
            </div>
            <div>
              <Label>Reported Date</Label>
              <Input
                type="date"
                value={formData.reported_date}
                onChange={handleChange('reported_date')}
                onKeyUp={() => handleRemove('reported_date')}
              />
            </div>
            <div>
              <Label>Resolved Date</Label>
              <Input
                type="date"
                value={formData.resolved_date}
                onChange={handleChange('resolved_date')}
                onKeyUp={() => handleRemove('resolved_date')}
              />
            </div>

            <div>
              <Label>Severity</Label>
              <SingleSelectInput
                options={severityData}
                valueKey="id"
                value={formData.severity}
                getOptionLabel={(item) => `${item.value}`}
                onSingleSelectChange={(val) => {
                  handleRemove('severity')
                  setFormData((prev: any) => ({
                    ...prev,
                    severity: val,
                  }))
                }}
                error={!!errors.severity}
                hint={errors.severity}
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
                options={exceptionStatus}
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

export default ReceivingExceptionCreateModal