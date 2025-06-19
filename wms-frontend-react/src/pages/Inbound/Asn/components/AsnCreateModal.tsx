import React,{useState} from 'react'
import Button from '../../../../components/ui/button/Button'
import BaseModal from '../../../../components/ui/modal'
import Label from '../../../../components/form/Label'
import Input from '../../../../components/form/input/InputField'
import TextAreaInput from '../../../../components/form/form-elements/TextAreaInput'
import SingleSelectInput from '../../../../components/form/form-elements/SelectInputs'
import Spinner from '../../../../components/ui/loading/spinner'
import { createAsn } from '../services/asnApi'
import { useAppDispatch, useAppSelector } from '../../../../store/hook'
import { RootState } from '../../../../store/store'
import provideUtility from '../../../../utils/toast'
// import asnStatus from '../../../../utils/globalJson/asnStatus.json'

interface Props {
    isCreateOpen: true | false
    handleCloseModal: () => void
}

interface Errors {
  asn_code?: string
  supplier_id?: any
  carrier_id?: any
  status?: any
}

const AsnCreateModal:React.FC<Props> = ({isCreateOpen,handleCloseModal}) => {
   const dispatch = useAppDispatch()
   const [isLoading, setIsLoading] = useState<any>(false)
   const [errors, setErrors] = useState<Errors>({})
   const suppliers = useAppSelector((state: RootState) => state.supplier?.content)
   const carriers = useAppSelector((state: RootState) => state.carrier?.content)

   const { showToast } = provideUtility()

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
   
   const [formData, setFormData] = useState<any>({
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
      const complete = await dispatch(createAsn(formData))
      setIsLoading(complete?.status)
      if(complete?.error.status == 422){
        setErrors(complete?.error.errors)
      }else{
        handleCloseModal()
        showToast('', 'Create ASN Successfully', 'top-right', 'success')
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
          <h2 className="text-xl font-semibold text-gray-800">Add New ASN</h2>
          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <Label>
                ASN Code<span className="text-error-500">*</span>
              </Label>
              <Input
                type="text"
                value={formData.asn_code}
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
                value={formData.supplier_id}
                getOptionLabel={(item) => `${item.party_code}`}
                onSingleSelectChange={(val) => {
                  handleRemove('supplier_id')
                  setFormData((prev: any) => ({
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
                value={formData.warehouse_id}
                getOptionLabel={(item) => `${item.warehouse_code}`}
                onSingleSelectChange={(val) => {
                console.log('value - ' + val)
                handleRemove('warehouse_id')
                getArea(val)
                setFormData((prev) => ({
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
                value={formData.purchase_order_id}
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
                value={formData.carrier_id}
                getOptionLabel={(item) => `${item.carrier_code}`}
                onSingleSelectChange={(val) => {
                  handleRemove('carrier_id')
                  setFormData((prev: any) => ({
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
                value={formData.tracking_number}
                onChange={handleChange('tracking_number')}
                onKeyUp={() => handleRemove('tracking_number')}
                
              />
            </div>
            <div>
              <Label>Total Items</Label>
              <Input
                type="number"
                value={formData.total_items}
                onChange={handleChange('total_items')}
                onKeyUp={() => handleRemove('total_items')}
                
              />
            </div>
            <div>
              <Label>Total Pallets</Label>
              <Input
                type="number"
                value={formData.total_pallet}
                onChange={handleChange('total_pallet')}
                onKeyUp={() => handleRemove('total_pallet')}
                
              />
            </div>
            <div>
              <Label>Expected Arrival</Label>
              <Input
                type="date"
                value={formData.expected_arrival}
                onChange={handleChange('expected_arrival')}
                onKeyUp={() => handleRemove('expected_arrival')}
              />
            </div>

            <div>
              <Label>Received Date</Label>
              <Input
                type="date"
                value={formData.received_date}
                onChange={handleChange('received_date')}
                onKeyUp={() => handleRemove('received_date')}
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

export default AsnCreateModal