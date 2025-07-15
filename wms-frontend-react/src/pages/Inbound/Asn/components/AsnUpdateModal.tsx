import React,{useState, useEffect} from 'react'
import Button from '../../../../components/ui/button/Button'
import BaseModal from '../../../../components/ui/modal'
import Label from '../../../../components/form/Label'
import Input from '../../../../components/form/input/InputField'
import TextAreaInput from '../../../../components/form/form-elements/TextAreaInput'
import SingleSelectInput from '../../../../components/form/form-elements/SelectInputs'
import Spinner from '../../../../components/ui/loading/spinner'
import { updateAsn } from '../services/asnApi'
import { useAppDispatch, useAppSelector } from '../../../../store/hook'
import { RootState } from '../../../../store/store'
import provideUtility from '../../../../utils/toast'

interface Props {
    isUpdateOpen: true | false
    handleCloseModal: () => void
}

interface Errors {
  asn_code?: string
  supplier_id?: any
  carrier_id?: any
  status?: any
}

const AsnUpdateModal:React.FC<Props> = ({isUpdateOpen,handleCloseModal}) => {
   const dispatch = useAppDispatch()
   const [isLoading, setIsLoading] = useState<any>(false)
   const [errors, setErrors] = useState<Errors>({})
   const suppliers = useAppSelector((state: RootState) => state.supplier?.content)
   const carriers = useAppSelector((state: RootState) => state.carrier?.content)
   const asn = useAppSelector((state: RootState) => state.asn?.data)

   const { showToast } = provideUtility()

   useEffect(() => {
    if(asn){
        setUpdateFormData({
          id: asn.id,
          asn_code: asn.asn_code,
          supplier_id: asn.supplier_id,
          carrier_id: asn.carrier_id,
          purchase_order_number: asn.purchase_order_number,
          expected_arrival: asn.expected_arrival,
          tracking_number: asn.tracking_number,
          total_items: asn.total_items,
          total_pallet: asn.total_pallet,
          notes: asn.notes,
          received_date: asn.received_date,
          status: asn.status,
        })
    }
   },[asn])

   const asnStatus = [
     {
       id: 0,
       value: 'Pending',
     },
     {
       id: 2,
       value: 'Received',
     },
     {
       id: 1,
       value: 'Verified',
     },
   ]
   
   const [updateFormData, setUpdateFormData] = useState<any>({
       id:'',
       asn_code : '',
       supplier_id : '',
       purchase_order_id : '',
       expected_arrival : '',
       carrier_id : '',
       tracking_number : '',
       total_items : '',
       total_pallet : '',
       notes : '',
       received_date : '',
       status : 0,
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
      const result = (await dispatch(updateAsn(updateFormData, asn?.id))) as any

      setIsLoading(false)
        console.log(result?.status)
      if (result?.error.status === 422) {
        setErrors(result?.error.errors)
      } else if (result?.status === true) {
        handleCloseModal()
        showToast('', 'Updated ASN Successfully', 'top-right', 'success')
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
          <h2 className="text-xl font-semibold text-gray-800">Edit ASN</h2>
          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <Label>
                ASN Code<span className="text-error-500">*</span>
              </Label>
              <Input
                type="text"
                value={updateFormData.asn_code}
                onChange={handleChange('asn_code')}
                onKeyUp={() => handleRemove('asn_code')}
                error={!!errors.asn_code}
                hint={errors.asn_code}
              />
            </div>
            <div>
              <Label>
                Supplier<span className="text-error-500">*</span>
              </Label>
              <SingleSelectInput
                options={suppliers}
                valueKey="id"
                value={updateFormData.supplier_id}
                getOptionLabel={(item) => `${item.party_code}`}
                onSingleSelectChange={(val) => {
                  handleRemove('supplier_id')
                  setUpdateFormData((prev: any) => ({
                    ...prev,
                    supplier_id: val,
                  }))
                }}
                error={!!errors.supplier_id}
                hint={errors.supplier_id}
              />
            </div>
            {/* <div>
            <Label>
                Warehouse Code<span className="text-error-500">*</span>
            </Label>
            <SingleSelectInput
                options={warehouseLists}
                valueKey="id"
                value={updateFormData.warehouse_id}
                getOptionLabel={(item) => `${item.warehouse_code}`}
                onSingleSelectChange={(val) => {
                console.log('value - ' + val)
                handleRemove('warehouse_id')
                getArea(val)
                setupdateFormData((prev) => ({
                    ...prev,
                    warehouse_id: val,
                }))
                }}
                error={!!errors.warehouse_id}
                hint={errors.warehouse_id}
            />
            </div> */}

            <div>
              <Label>Purchase Order Number</Label>
              <Input
                type="text"
                value={updateFormData.purchase_order_id}
                onChange={handleChange('purchase_order_id')}
                onKeyUp={() => handleRemove('purchase_order_id')}
                disabled={true}
              />
            </div>
            <div>
              <Label>
                Carrier<span className="text-error-500">*</span>
              </Label>
              <SingleSelectInput
                options={carriers}
                valueKey="id"
                value={updateFormData.carrier_id}
                getOptionLabel={(item) => `${item.carrier_code}`}
                onSingleSelectChange={(val) => {
                  handleRemove('carrier_id')
                  setUpdateFormData((prev: any) => ({
                    ...prev,
                    carrier_id: val,
                  }))
                }}
                error={!!errors.carrier_id}
                hint={errors.carrier_id}
              />
            </div>

            <div>
              <Label>Tracking Number</Label>
              <Input
                type="text"
                value={updateFormData.tracking_number}
                onChange={handleChange('tracking_number')}
                onKeyUp={() => handleRemove('tracking_number')}
              />
            </div>
            <div>
              <Label>Total Items</Label>
              <Input
                type="number"
                value={updateFormData.total_items}
                onChange={handleChange('total_items')}
                onKeyUp={() => handleRemove('total_items')}
              />
            </div>
            <div>
              <Label>Total Pallets</Label>
              <Input
                type="number"
                value={updateFormData.total_pallet}
                onChange={handleChange('total_pallet')}
                onKeyUp={() => handleRemove('total_pallet')}
              />
            </div>

            <div>
              <Label>Expected Arrival</Label>
              <Input
                type="date"
                value={updateFormData.expected_arrival}
                onChange={handleChange('expected_arrival')}
                onKeyUp={() => handleRemove('expected_arrival')}
              />
            </div>

            <div>
              <Label>Received Date</Label>
              <Input
                type="date"
                value={updateFormData.received_date}
                onChange={handleChange('received_date')}
                onKeyUp={() => handleRemove('received_date')}
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

export default AsnUpdateModal