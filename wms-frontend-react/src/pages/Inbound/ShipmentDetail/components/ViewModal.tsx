import React from 'react'
import BaseModal from '../../../../components/ui/modal'
import Label from '../../../../components/form/Label'
import Input from '../../../../components/form/input/InputField'
import { useAppSelector } from '../../../../store/hook'
import { RootState } from '../../../../store/store'

interface Props {
    isViewOpen: true | false
    handleCloseModal: () => void
}

const ShipmentDetailViewModal:React.FC<Props> = ({isViewOpen,handleCloseModal}) => {

   const shipmentDetail = useAppSelector((state: RootState) => state.shipmentDetail?.data)

  return (
    <>
      <BaseModal
        isOpen={isViewOpen}
        onClose={handleCloseModal}
        isFullscreen={false}
      >
        <div className="space-y-6">
          <h2 className="text-xl font-semibold text-gray-800">
            Inbound Shipment Detail
          </h2>
          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <Label>
                Shipment Detail Code<span className="text-error-500">*</span>
              </Label>
              <Input
                type="text"
                value={shipmentDetail?.inbound_detail_code}
                disabled={true}
              />
            </div>
            <div>
              <Label>
                Shipment Code<span className="text-error-500">*</span>
              </Label>
              <Input
                type="text"
                value={shipmentDetail?.inbound_shipment_code}
                disabled={true}
              />
            </div>
            <div>
              <Label>
                Product<span className="text-error-500">*</span>
              </Label>
              <Input
                type="text"
                value={shipmentDetail?.product_code}
                disabled={true}
              />
            </div>
            <div>
              <Label>Purchase Order Number</Label>
              <Input
                type="string"
                value={shipmentDetail?.purchase_order_number}
                disabled={true}
              />
            </div>
            <div>
              <Label>Expected Qty</Label>
              <Input
                type="number"
                value={shipmentDetail?.expected_qty}
                disabled={true}
              />
            </div>
            <div>
              <Label>Received Qty</Label>
              <Input
                type="number"
                value={shipmentDetail?.received_qty}
                disabled={true}
              />
            </div>
            <div>
              <Label>Damaged Qty</Label>
              <Input
                type="number"
                value={shipmentDetail?.damaged_qty}
                disabled={true}
              />
            </div>
            <div>
              <Label>Lot Number</Label>
              <Input type="text" value={shipmentDetail?.lot_number} disabled={true} />
            </div>
            <div>
              <Label>Expiration Date</Label>
              <Input
                type="date"
                value={shipmentDetail?.expiration_date}
                disabled={true}
              />
            </div>
            <div>
              <Label>Received By</Label>
              <Input type="text" value={shipmentDetail?.received_by} disabled={true} />
            </div>
            <div>
              <Label>Received Date</Label>
              <Input
                type="date"
                value={shipmentDetail?.received_date}
                disabled={true}
              />
            </div>
            <div>
              <Label>Location</Label>
              <Input
                type="text"
                value={shipmentDetail?.location_code}
                disabled={true}
              />
            </div>
            <div>
              <Label>
                Status<span className="text-error-500">*</span>
              </Label>
              <Input
                type="text"
                value={shipmentDetail?.status}
                disabled={true}
              />
            </div>
          </div>
        </div>
      </BaseModal>
    </>
  )
}

export default ShipmentDetailViewModal