import React,{useState} from 'react'
import Button from '../../../../../components/ui/button/Button'
import Label from '../../../../../components/form/Label'
import Input from '../../../../../components/form/input/InputField'
import TextAreaInput from '../../../../../components/form/form-elements/TextAreaInput'
import SingleSelectInput from '../../../../../components/form/form-elements/SelectInputs'
import Spinner from '../../../../../components/ui/loading/spinner'
import { createGrn } from '../../services/grnApi'
import { useAppDispatch, useAppSelector } from '../../../../../store/hook'
import { RootState } from '../../../../../store/store'
import provideUtility from '../../../../../utils/toast'
import GrnItemCreateForm from '../GrnItems/CreateForm'

interface Props {
    isCreateOpen: true | false
    handleCloseModal: () => void
    handleShowLists:() => void
}

interface Errors {
  grn_code?: string
  inbound_shipment_id?:any
  supplier_id?: any
  status?: any
}

const GrnCreateForm:React.FC<Props> = ({isCreateOpen,handleCloseModal,handleShowLists}) => {
   const dispatch = useAppDispatch()
   const [isLoading, setIsLoading] = useState<any>(false)
   const [isCreated, setIsCreated] = useState<any>(false)
   const [isOpen, setIsOpen] = useState<any>(false)
   const [errors, setErrors] = useState<Errors>({})
   const suppliers = useAppSelector((state: RootState) => state.supplier?.content)
   const shipmentLists = useAppSelector(
     (state: RootState) => state.shipment?.content
   )

   const { showToast } = provideUtility()

   const asnStatus = [
     {
       id: 0,
       value: 'Pending',
     },
     {
       id: 2,
       value: 'Rejected',
     },
     {
       id: 1,
       value: 'Approved',
     },
   ]
   
   const [formData, setFormData] = useState<any>({
       grn_code : '',
       inbound_shipment_id:'',
       supplier_id : '',
       purchase_order_id : '',
       created_by : '',
       approved_by:'',
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
      const complete = await dispatch(createGrn(formData))
      setIsLoading(complete?.status)
      if(complete?.error.status == 422){
        setErrors(complete?.error.errors)
      }else{
        showToast('', 'Create GRN Successfully', 'top-right', 'success')
        setIsCreated(true)
        setIsOpen(true)
      }
    }

  return (
    <>
      <div className="space-y-6">
        <h2 className="text-xl font-semibold text-gray-800">Add New GRN</h2>
        <div className="max-w-auto rounded-2xl overflow-hidden shadow-lg bg-white p-6">
          <div className="grid grid-cols-1 md:grid-cols-4 gap-6">
            <div>
              <Label>
                Grn Code<span className="text-error-500">*</span>
              </Label>
              <Input
                type="text"
                value={formData.grn_code}
                onChange={handleChange('grn_code')}
                onKeyUp={() => handleRemove('grn_code')}
                error={!!errors.grn_code}
                hint={errors.grn_code}
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
            <div>
              <Label>
                Shipment<span className="text-error-500">*</span>
              </Label>
              <SingleSelectInput
                options={shipmentLists}
                valueKey="id"
                value={formData.inbound_shipment_id}
                getOptionLabel={(item) => `${item.shipment_code}`}
                onSingleSelectChange={(val) => {
                  handleRemove('inbound_shipment_id')
                  setFormData((prev: any) => ({
                    ...prev,
                    inbound_shipment_id: val,
                  }))
                }}
                error={!!errors.inbound_shipment_id}
                hint={errors.inbound_shipment_id}
              />
            </div>
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
              <Label>Received Date</Label>
              <Input
                type="date"
                value={formData.received_date}
                onChange={handleChange('received_date')}
                onKeyUp={() => handleRemove('received_date')}
              />
            </div>

            <div className="">
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
          <div className="flex justify-end items-center gap-4">
            <Button variant="secondary" onClick={handleShowLists}>
              Cancel
            </Button>
            <Button
              variant="primary"
              startIcon={isLoading && <Spinner size={4} />}
              onClick={handleSubmit}
              disabled={isCreated}
            >
              Confirm
            </Button>
          </div>
        </div>
        <hr />
        <GrnItemCreateForm isOpen={isOpen} handleShowLists={handleShowLists} />
      </div>
    </>
  )
}

export default GrnCreateForm