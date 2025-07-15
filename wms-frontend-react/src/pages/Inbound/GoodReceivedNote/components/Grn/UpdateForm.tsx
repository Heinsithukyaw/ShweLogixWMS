import React,{useState, useEffect} from 'react'
import Button from '../../../../../components/ui/button/Button'
import Label from '../../../../../components/form/Label'
import Input from '../../../../../components/form/input/InputField'
import TextAreaInput from '../../../../../components/form/form-elements/TextAreaInput'
import SingleSelectInput from '../../../../../components/form/form-elements/SelectInputs'
import Spinner from '../../../../../components/ui/loading/spinner'
import { updateGrn } from '../../services/grnApi'
import { useAppDispatch, useAppSelector } from '../../../../../store/hook'
import { RootState } from '../../../../../store/store'
import provideUtility from '../../../../../utils/toast'
import GrnItemUpdateForm from '../GrnItems/UpdateForm'

interface Props {
    isUpdateOpen: true | false
    handleShowLists:() => void
}

interface Errors {
  grn_code?: string
  inbound_shipment_id?:any
  supplier_id?: any
  status?: any
}

const GrnUpdateForm:React.FC<Props> = ({isUpdateOpen,handleShowLists}) => {
   const dispatch = useAppDispatch()
   const [isLoading, setIsLoading] = useState<any>(false)
   const [errors, setErrors] = useState<Errors>({})
   const suppliers = useAppSelector((state: RootState) => state.supplier?.content)
   const shipmentLists = useAppSelector((state: RootState) => state.shipment?.content)
   const grn = useAppSelector((state: RootState) => state.grn?.data)

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

   useEffect(() => {
        if(grn){
            setUpdateFormData({
                id: grn.id,
                grn_code: grn.grn_code,
                supplier_id: grn.supplier_id,
                inbound_shipment_id: grn.inbound_shipment_id,
                purchase_order_id: grn.purchase_order_id,
                received_date: grn.received_date,
                notes: grn.notes,
                status: grn.status,
            })
        }
    },[grn])
   
   const [updateFormData, setUpdateFormData] = useState<any>({
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
       status : '',
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
        const result = (await dispatch(updateGrn(updateFormData, grn?.id))) as any

        setIsLoading(false)
        console.log(result?.status)
        if (result?.error.status === 422) {
        setErrors(result?.error.errors)
        } else if (result?.status === true) {
        // handleShowLists()
        showToast('', 'Updated Good Received Note Successfully', 'top-right', 'success')
        } else {
        showToast('Error', 'Failed to update Good Received Note', 'top-right', 'error')
        }
    }

  return (
    <>
      <div className="space-y-6">
        <h2 className="text-xl font-semibold text-gray-800">
          Edit Good Received Note
        </h2>
        <div className="max-w-auto rounded-2xl overflow-hidden shadow-lg bg-white p-6">
          <div className="grid grid-cols-1 md:grid-cols-4 gap-6">
            <div>
              <Label>
                Grn Code<span className="text-error-500">*</span>
              </Label>
              <Input
                type="text"
                value={updateFormData.grn_code}
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
            <div>
              <Label>
                Shipment<span className="text-error-500">*</span>
              </Label>
              <SingleSelectInput
                options={shipmentLists}
                valueKey="id"
                value={updateFormData.inbound_shipment_id}
                getOptionLabel={(item) => `${item.shipment_code}`}
                onSingleSelectChange={(val) => {
                  handleRemove('inbound_shipment_id')
                  setUpdateFormData((prev: any) => ({
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
                value={updateFormData.purchase_order_id}
                onChange={handleChange('purchase_order_id')}
                onKeyUp={() => handleRemove('purchase_order_id')}
                disabled={true}
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

            <div className="">
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
            <div className="flex justify-center items-center gap-4">
              <Button variant="secondary" onClick={handleShowLists}>
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
        </div>
        <hr />
        <GrnItemUpdateForm
          isOpen={true}
          handleShowLists={handleShowLists}
          getGrnId={grn?.id}
        />
      </div>
    </>
  )
}

export default GrnUpdateForm