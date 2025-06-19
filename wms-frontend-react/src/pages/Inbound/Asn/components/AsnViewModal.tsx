import React from 'react'
import BaseModal from '../../../../components/ui/modal'
import Label from '../../../../components/form/Label'
import Input from '../../../../components/form/input/InputField'
import TextAreaInput from '../../../../components/form/form-elements/TextAreaInput'
import { useAppSelector } from '../../../../store/hook'
import { RootState } from '../../../../store/store'

interface Props {
    isViewOpen: true | false
    handleCloseModal: () => void
}

const AsnViewModal:React.FC<Props> = ({isViewOpen,handleCloseModal}) => {
   const asn = useAppSelector((state: RootState) => state.asn?.data)
   
  return (
    <>
      <BaseModal
        isOpen={isViewOpen}
        onClose={handleCloseModal}
        isFullscreen={false}
      >
        <div className="space-y-6">
          <h2 className="text-xl font-semibold text-gray-800">
            Advanced Shipping Notice
          </h2>
          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <Label>
                ASN Code<span className="text-error-500">*</span>
              </Label>
              <Input type="text" value={asn?.asn_code} disabled={true} />
            </div>
            <div>
              <Label>
                Supplier<span className="text-error-500">*</span>
              </Label>
              <Input type="text" value={asn?.supplier_code} disabled={true} />
            </div>

            <div>
              <Label>Purchase Order Number</Label>
              <Input
                type="text"
                value={asn?.purchase_order_id}
                disabled={true}
              />
            </div>
            <div>
              <Label>
                Carrier<span className="text-error-500">*</span>
              </Label>
              <Input type="text" value={asn?.carrier_code} disabled={true} />
            </div>
            <div>
              <Label>Tracking Number</Label>
              <Input
                type="text"
                value={asn?.tracking_number}             
                disabled={true}
              />
            </div>
            <div>
              <Label>Total Items</Label>
              <Input
                type="number"
                value={asn?.total_items}
                disabled={true}
              />
            </div>
            <div>
              <Label>Total Pallets</Label>
              <Input
                type="number"
                value={asn?.total_pallet} 
                disabled={true}
              />
            </div>
            <div>
              <Label>Expected Arrival</Label>
              <Input type="date" value={asn?.expected_arrival} disabled />
            </div>

            <div>
              <Label>Received Date</Label>
              <Input type="date" value={asn?.received_date} disabled={true} />
            </div>

            <div className="col-span-full">
              <Label>notes</Label>
              <TextAreaInput value={asn?.notes} disabled={true} />
            </div>

            <div>
              <Label>
                Status<span className="text-error-500">*</span>
              </Label>
              <Input type="text" value={asn?.status} disabled={true} />
            </div>
          </div>
        </div>
      </BaseModal>
    </>
  )
}

export default AsnViewModal