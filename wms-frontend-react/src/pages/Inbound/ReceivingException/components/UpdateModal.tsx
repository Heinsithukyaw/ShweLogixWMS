import React,{useState, useEffect} from 'react'
import Button from '../../../../components/ui/button/Button'
import BaseModal from '../../../../components/ui/modal'
import Label from '../../../../components/form/Label'
import Input from '../../../../components/form/input/InputField'
import TextAreaInput from '../../../../components/form/form-elements/TextAreaInput'
import SingleSelectInput from '../../../../components/form/form-elements/SelectInputs'
import Spinner from '../../../../components/ui/loading/spinner'
import { updateReceivingException } from '../services/receivingExceptionApi'
import { useAppDispatch, useAppSelector } from '../../../../store/hook'
import { RootState } from '../../../../store/store'
import provideUtility from '../../../../utils/toast'

interface Props {
    isUpdateOpen: true | false
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

const ReceivingExceptionUpdateModal:React.FC<Props> = ({isUpdateOpen,handleCloseModal}) => {
   const dispatch = useAppDispatch()
   const [isLoading, setIsLoading] = useState<any>(false)
   const [errors, setErrors] = useState<Errors>({})
   const [asnDetails, setAsnDetails] = useState<any[]>([])
   const asnLists = useAppSelector((state: RootState) => state.asn?.content)
   const empLists = useAppSelector((state: RootState) => state.employee?.content)
   const asnDetailLists = useAppSelector((state: RootState) => state.asnDetail?.content)
   const itemLists = useAppSelector((state: RootState) => state.product?.content)
   const receivingException = useAppSelector((state: RootState) => state.receivingException?.data)

   const { showToast } = provideUtility()

   useEffect(() => {
     if (receivingException) {
       setAsnDetails(asnDetailLists?.filter((x:any) => x.asn_id == receivingException?.asn_id))
       console.log(receivingException?.exception_type)
       setUpdateFormData({
         id: receivingException.id,
         exception_code: receivingException.exception_code,
         asn_id: receivingException.asn_id,
         asn_detail_id: receivingException.asn_detail_id,
         item_id: receivingException.item_id,
         item_description: receivingException.item_description,
         exception_type: receivingException.exception_type,
         reported_by_id: receivingException.reported_by_id,
         assigned_to_id: receivingException.assigned_to_id,
         reported_date: receivingException.reported_date,
         resolved_date: receivingException.resolved_date,
         description: receivingException.description,
         severity: receivingException.severity,
         status: receivingException.status,
       })
     }
   }, [receivingException,asnDetailLists])

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
   
   const [updateFormData, setUpdateFormData] = useState<any>({
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
       severity: 0,
       description:'',
       status : 0,
     })

   const handleGetDetail = (id:any) => {
      setAsnDetails(asnDetailLists?.filter((x:any) => x.asn_id == id))
   }

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
      const result = (await dispatch(updateReceivingException(updateFormData, receivingException?.id))) as any

      setIsLoading(false)
        console.log(result?.status)
      if (result?.error.status === 422) {
        setErrors(result?.error.errors)
      } else if (result?.status === true) {
        handleCloseModal()
        showToast('', 'Updated Receiving Exception Successfully', 'top-right', 'success')
      } else {
        showToast('Error', 'Failed to update Receiving Exception', 'top-right', 'error')
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
            Edit Exception
          </h2>
          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <Label>
                Exception Code<span className="text-error-500">*</span>
              </Label>
              <Input
                type="text"
                value={updateFormData.exception_code}
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
                value={updateFormData.exception_type}
                getOptionLabel={(item) => `${item.value}`}
                onSingleSelectChange={(val) => {
                  handleRemove('exception_type')
                  setUpdateFormData((prev: any) => ({
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
                value={updateFormData.asn_id}
                getOptionLabel={(item) => `${item.asn_code}`}
                onSingleSelectChange={(val) => {
                  handleRemove('asn_id')
                  handleGetDetail(val)
                  setUpdateFormData((prev: any) => ({
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
                  value={updateFormData.asn_detail_id}
                  getOptionLabel={(item) => `${item.asn_detail_code}`}
                  onSingleSelectChange={(val) => {
                    handleRemove('asn_detail_id')
                    setUpdateFormData((prev: any) => ({
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
                value={updateFormData.item_id}
                getOptionLabel={(item) => `${item.product_code}`}
                onSingleSelectChange={(val) => {
                  handleRemove('item_id')
                  setUpdateFormData((prev: any) => ({
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
                value={updateFormData.item_description}
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
                value={updateFormData.reported_by_id}
                getOptionLabel={(item) => `${item.employee_code}`}
                onSingleSelectChange={(val) => {
                  handleRemove('reported_by_id')
                  setUpdateFormData((prev: any) => ({
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
              <Label>Reported Date</Label>
              <Input
                type="date"
                value={updateFormData.reported_date}
                onChange={handleChange('reported_date')}
                onKeyUp={() => handleRemove('reported_date')}
              />
            </div>
            <div>
              <Label>Resolved Date</Label>
              <Input
                type="date"
                value={updateFormData.resolved_date}
                onChange={handleChange('resolved_date')}
                onKeyUp={() => handleRemove('resolved_date')}
              />
            </div>

            <div>
              <Label>Severity</Label>
              <SingleSelectInput
                options={severityData}
                valueKey="id"
                value={updateFormData.severity}
                getOptionLabel={(item) => `${item.value}`}
                onSingleSelectChange={(val) => {
                  handleRemove('severity')
                  setUpdateFormData((prev: any) => ({
                    ...prev,
                    severity: val,
                  }))
                }}
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

            <div>
              <Label>
                Status<span className="text-error-500">*</span>
              </Label>
              <SingleSelectInput
                options={exceptionStatus}
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

export default ReceivingExceptionUpdateModal