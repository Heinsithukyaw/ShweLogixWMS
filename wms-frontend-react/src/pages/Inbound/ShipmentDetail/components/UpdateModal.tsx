import React,{useState, useEffect} from 'react'
import Button from '../../../../components/ui/button/Button'
import BaseModal from '../../../../components/ui/modal'
import Label from '../../../../components/form/Label'
import Input from '../../../../components/form/input/InputField'
import SingleSelectInput from '../../../../components/form/form-elements/SelectInputs'
import Spinner from '../../../../components/ui/loading/spinner'
import { updateShipmentDetail } from '../services/shipmentDetailApi'
import { useAppDispatch, useAppSelector } from '../../../../store/hook'
import { RootState } from '../../../../store/store'
import provideUtility from '../../../../utils/toast'

interface Props {
    isUpdateOpen: true | false
    handleCloseModal: () => void
}

interface Errors {
  inbound_detail_code?: string
  inbound_shipment_id?: any
  product_id?: any
  location_id?: any
  status?: any
}

const ShipmentDetailUpdateModal:React.FC<Props> = ({isUpdateOpen,handleCloseModal}) => {
   const dispatch = useAppDispatch()
   const [isLoading, setIsLoading] = useState<any>(false)
   const [errors, setErrors] = useState<Errors>({})
   const [storageLocations, setStorageLocations] = useState<any>([])
   const shipmentLists = useAppSelector((state: RootState) => state.shipment?.content)
   const productLists = useAppSelector((state: RootState) => state.product?.content)
   const locationLists = useAppSelector((state: RootState) => state.location?.content)
   const shipmentDetail = useAppSelector((state: RootState) => state.shipmentDetail?.data)

   const { showToast } = provideUtility()

   const asnStatus = [
     {
       id: 0,
       value: 'Exception',
     },
     {
       id: 1,
       value: 'Expected',
     },
     {
       id: 2,
       value: 'Received',
     },
   ]

   useEffect(() => {
        if(shipmentDetail){
            setUpdateFormData({
              id: shipmentDetail.id,
              inbound_detail_code: shipmentDetail.inbound_detail_code,
              inbound_shipment_id: shipmentDetail.inbound_shipment_id,
              product_id: shipmentDetail.product_id,
              purchase_order_id: shipmentDetail.purchase_order_id,
              purchase_order_number: shipmentDetail.purchase_order_number,
              expected_qty: shipmentDetail.expected_qty,
              received_qty: shipmentDetail.received_qty,
              damaged_qty: shipmentDetail.damaged_qty,
              lot_number: shipmentDetail.lot_number,
              expiration_date: shipmentDetail.expiration_date,
              received_by: shipmentDetail.received_by,
              location_id: shipmentDetail.location_id,
              received_date: shipmentDetail.received_date,
              status: shipmentDetail.status,
            })
        }
    },[shipmentDetail])

  useEffect(() => {
      if(locationLists){
        setStorageLocations(locationLists.filter((x:any) => x.zone_type == 'Storage'))
      }
      console.log(locationLists)
      },[locationLists])
   
   const [updateFormData, setUpdateFormData] = useState<any>({
     inbound_detail_code: '',
     inbound_shipment_id: '',
     product_id: '',
     purchase_order_id: '',
     expected_qty: '',
     received_qty: '',
     damaged_qty: '',
     lot_number: '',
     expiration_date: '',
     received_by: '',
     location_id: '',
     status: 0,
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
        const result = (await dispatch(updateShipmentDetail(updateFormData, shipmentDetail?.id))) as any

        setIsLoading(false)
        console.log(result?.status)
        if (result?.error.status === 422) {
        setErrors(result?.error.errors)
        } else if (result?.status === true) {
        handleCloseModal()
        showToast('', 'Updated Shipment Detail Successfully', 'top-right', 'success')
        } else {
        showToast('Error', 'Failed to update Shipment Detail', 'top-right', 'error')
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
            Edit Shipment Detail
          </h2>
          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <Label>
                Shipment Detail Code<span className="text-error-500">*</span>
              </Label>
              <Input
                type="text"
                value={updateFormData.inbound_detail_code}
                onChange={handleChange('inbound_detail_code')}
                onKeyUp={() => handleRemove('inbound_detail_code')}
                error={!!errors.inbound_detail_code}
                hint={errors.inbound_detail_code}
              />
            </div>
            <div>
              <Label>
                Shipment Code<span className="text-error-500">*</span>
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
              <Label>
                Product<span className="text-error-500">*</span>
              </Label>
              <SingleSelectInput
                options={productLists}
                valueKey="id"
                value={updateFormData.product_id}
                getOptionLabel={(item) => `${item.product_code}`}
                onSingleSelectChange={(val) => {
                  handleRemove('product_id')
                  setUpdateFormData((prev: any) => ({
                    ...prev,
                    product_id: val,
                  }))
                }}
                error={!!errors.product_id}
                hint={errors.product_id}
              />
            </div>
            <div>
              <Label>Purchase Order Number</Label>
              <Input
                type="string"
                value={updateFormData.purchase_order_number}
                onChange={handleChange('purchase_order_number')}
                onKeyUp={() => handleRemove('purchase_order_number')}
                disabled={true}
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
              <Label>Received Qty</Label>
              <Input
                type="number"
                value={updateFormData.received_qty}
                onChange={handleChange('received_qty')}
                onKeyUp={() => handleRemove('received_qty')}
              />
            </div>
            <div>
              <Label>Damaged Qty</Label>
              <Input
                type="number"
                value={updateFormData.damaged_qty}
                onChange={handleChange('damaged_qty')}
                onKeyUp={() => handleRemove('damaged_qty')}
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
              <Label>Received By</Label>
              <Input
                type="text"
                value={updateFormData.received_by}
                onChange={handleChange('received_by')}
                onKeyUp={() => handleRemove('received_by')}
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
            <div>
              <Label>
                Location<span className="text-error-500">*</span>
              </Label>
              <SingleSelectInput
                options={storageLocations}
                valueKey="id"
                value={updateFormData.location_id}
                getOptionLabel={(item) => `${item.location_code}`}
                onSingleSelectChange={(val) => {
                  handleRemove('location_id')
                  setUpdateFormData((prev: any) => ({
                    ...prev,
                    location_id: val,
                  }))
                }}
                error={!!errors.location_id}
                hint={errors.location_id}
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

export default ShipmentDetailUpdateModal