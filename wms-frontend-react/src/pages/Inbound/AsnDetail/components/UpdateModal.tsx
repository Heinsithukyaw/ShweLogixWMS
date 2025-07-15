import React,{useState, useEffect} from 'react'
import Button from '../../../../components/ui/button/Button'
import BaseModal from '../../../../components/ui/modal'
import Label from '../../../../components/form/Label'
import Input from '../../../../components/form/input/InputField'
import TextAreaInput from '../../../../components/form/form-elements/TextAreaInput'
import SingleSelectInput from '../../../../components/form/form-elements/SelectInputs'
import Spinner from '../../../../components/ui/loading/spinner'
import { updateAsnDetail } from '../services/asnDetailApi'
import { useAppDispatch, useAppSelector } from '../../../../store/hook'
import { RootState } from '../../../../store/store'
import provideUtility from '../../../../utils/toast'

interface Props {
    isUpdateOpen: true | false
    handleCloseModal: () => void
}

interface Errors {
  asn_detail_code?: string
  asn_id?: any
  item_id?: any
  expected_qty?: number
  uom_id?: any
  status?: any
}

const AsnDetailUpdateModal:React.FC<Props> = ({isUpdateOpen,handleCloseModal}) => {
   const dispatch = useAppDispatch()
   const [isLoading, setIsLoading] = useState<any>(false)
   const [errors, setErrors] = useState<Errors>({})
   const asnLists = useAppSelector((state: RootState) => state.asn?.content)
   const itemLists = useAppSelector((state: RootState) => state.product?.content)
   const uomLists = useAppSelector((state: RootState) => state.uom?.content)
   const palletLists = useAppSelector((state: RootState) => state.pallet?.content)
   const locationZoneLists = useAppSelector((state: RootState) => state.zone?.content)
   const [zoneLocations, setZoneLocations] = useState<any>([])
   const asnDetail = useAppSelector((state: RootState) => state.asnDetail?.data)

   const { showToast } = provideUtility()

   const asnStatus = [
     {
       id: 0,
       value: 'Pending',
     },
     {
       id: 1,
       value: 'Missing',
     },
     {
       id: 2,
       value: 'Partial',
     },
     {
       id: 3,
       value: 'Received',
     },
   ]
   
   const [updateFormData, setUpdateFormData] = useState<any>({
       asn_detail_code : '',
       asn_id : '',
       item_id : '',
       item_description:'',
       expected_qty : '',
       uom_id : '',
       lot_number : '',
       expiration_date : '',
       received_qty : '',
       variance: '',
       notes : '',
       pallet_id : '',
       location_id:'',
       status : 0,
     })

     useEffect(() => {
      if(asnDetail){
          setUpdateFormData({
            id: asnDetail.id,
            asn_detail_code: asnDetail.asn_detail_code,
            asn_id: asnDetail.asn_id,
            item_id: asnDetail.item_id,
            item_description: asnDetail.item_description,
            expected_qty: asnDetail.expected_qty,
            uom_id: asnDetail.uom_id,
            lot_number: asnDetail.lot_number,
            expiration_date: asnDetail.expiration_date,
            notes: asnDetail.notes,
            received_qty: asnDetail.received_qty,
            variance: asnDetail.variance,
            pallet_id:asnDetail.pallet_id,
            location_id:asnDetail.location_id,
            status: asnDetail.status,
          })
      }
    },[asnDetail])

    useEffect(() => {
        if (locationZoneLists) {
          setZoneLocations(
            locationZoneLists.filter((x: any) => x.zone_type == 'Receiving')
          )
        }
      }, [locationZoneLists])

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
      const result = (await dispatch(updateAsnDetail(updateFormData, asnDetail?.id))) as any

      setIsLoading(false)
        console.log(result?.status)
      if (result?.error.status === 422) {
        setErrors(result?.error.errors)
      } else if (result?.status === true) {
        handleCloseModal()
        showToast('', 'Updated ASN Detail Successfully', 'top-right', 'success')
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
          <h2 className="text-xl font-semibold text-gray-800">
            Edit ASN Detail
          </h2>
          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <Label>
                ASN Detail Code<span className="text-error-500">*</span>
              </Label>
              <Input
                type="text"
                value={updateFormData.asn_detail_code}
                onChange={handleChange('asn_detail_code')}
                onKeyUp={() => handleRemove('asn_detail_code')}
                error={!!errors.asn_detail_code}
                hint={errors.asn_detail_code}
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
              <Label>Expected Qty</Label>
              <Input
                type="number"
                value={updateFormData.expected_qty}
                onChange={handleChange('expected_qty')}
                onKeyUp={() => handleRemove('expected_qty')}
              />
            </div>
            <div>
              <Label>
                UOM<span className="text-error-500">*</span>
              </Label>
              <SingleSelectInput
                options={uomLists}
                valueKey="id"
                value={updateFormData.uom_id}
                getOptionLabel={(item) => `${item.uom_code}`}
                onSingleSelectChange={(val) => {
                  handleRemove('uom_id')
                  setUpdateFormData((prev: any) => ({
                    ...prev,
                    uom_id: val,
                  }))
                }}
                error={!!errors.uom_id}
                hint={errors.uom_id}
              />
            </div>
            <div>
              <Label>Lot Number</Label>
              <Input
                type="text"
                value={updateFormData.lot_number}
                onChange={handleChange('lot_number')}
                onKeyUp={() => handleRemove('lot_number')}
                
              />
            </div>
            <div>
              <Label>Expiration Date</Label>
              <Input
                type="date"
                value={updateFormData.expiration_date}
                onChange={handleChange('expiration_date')}
                onKeyUp={() => handleRemove('expiration_date')}
                
              />
            </div>
            <div>
              <Label>Received Qty</Label>
              <Input
                type="number"
                value={updateFormData.received_qty}
                onChange={handleChange('received_qty')}
                onKeyUp={() => handleRemove('received_qty')}
                
              />
            </div>
            <div>
              <Label>Variance</Label>
              <Input
                type="number"
                value={updateFormData.variance}
                onChange={handleChange('variance')}
                onKeyUp={() => handleRemove('variance')}
                
              />
            </div>
            <div>
              <Label>
                Pallet
              </Label>
              <SingleSelectInput
                options={palletLists}
                valueKey="id"
                value={updateFormData.pallet_id}
                getOptionLabel={(item) => `${item.pallet_code}`}
                onSingleSelectChange={(val) => {
                  handleRemove('pallet_id')
                  setUpdateFormData((prev: any) => ({
                    ...prev,
                    pallet_id: val,
                  }))
                }}
              />
            </div>
            <div>
              <Label>
                Location
              </Label>
              <SingleSelectInput
                options={zoneLocations}
                valueKey="id"
                value={updateFormData.location_id}
                getOptionLabel={(item) => `${item.zone_code} - ${item.zone_type}`}
                onSingleSelectChange={(val) => {
                  handleRemove('location_id')
                  setUpdateFormData((prev: any) => ({
                    ...prev,
                    location_id: val,
                  }))
                }}
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

export default AsnDetailUpdateModal