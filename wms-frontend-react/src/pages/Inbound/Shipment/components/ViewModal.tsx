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

const ShipmentViewModal:React.FC<Props> = ({isViewOpen,handleCloseModal}) => {
   const shipment = useAppSelector((state: RootState) => state.shipment?.data)

  return (
    <>
      <BaseModal
        isOpen={isViewOpen}
        onClose={handleCloseModal}
        isFullscreen={false}
      >
        <div className="space-y-6">
          <h2 className="text-xl font-semibold text-gray-800">
            Inbound Shipment
          </h2>
          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <Label>
                Shipment Code<span className="text-error-500">*</span>
              </Label>
              <Input
                type="text"
                value={shipment?.shipment_code}
                disabled={true}
              />
            </div>
            <div>
              <Label>
                Supplier<span className="text-error-500">*</span>
              </Label>
              <Input
                type="text"
                value={shipment?.supplier_code}
                disabled={true}
              />
            </div>
            <div>
              <Label>Version Control</Label>
              <Input
                type="text"
                value={shipment?.version_control}
                disabled={true}
              />
            </div>

            <div>
              <Label>Purchase Order Number</Label>
              <Input
                type="text"
                value={shipment?.purchase_order_id}
                disabled={true}
              />
            </div>
            <div>
              <Label>
                Carrier<span className="text-error-500">*</span>
              </Label>
              <Input
                type="text"
                value={shipment?.carrier_code}
                disabled={true}
              />
            </div>
            <div>
              <Label>Staging Location</Label>
              <Input
                type="text"
                value={shipment?.staging_location_code}
                disabled={true}
              />
            </div>
            <div>
              <Label>Expected Arrival</Label>
              <Input
                type="date"
                value={shipment?.expected_arrival}
                disabled={true}
              />
            </div>
            <div>
              <Label>Actual Arrival</Label>
              <Input
                type="date"
                value={shipment?.actual_arrival}
                disabled={true}
              />
            </div>
            <div>
              <Label>Trailer Number</Label>
              <Input
                type="text"
                value={shipment?.trailer_number}
                disabled={true}
              />
            </div>
            <div>
              <Label>Seal Number</Label>
              <Input
                type="text"
                value={shipment?.seal_number}
                disabled={true}
              />
            </div>
            <div>
              <Label>Total Pallets</Label>
              <Input
                type="number"
                value={shipment?.total_pallet}
                disabled={true}
              />
            </div>
            <div>
              <Label>Total Weight</Label>
              <Input
                type="number"
                value={shipment?.total_weight}
                disabled={true}
              />
            </div>
            <div className="col-span-full">
              <Label>notes</Label>
              <TextAreaInput value={shipment?.notes} disabled={true} />
            </div>

            <div>
              <Label>
                Status<span className="text-error-500">*</span>
              </Label>
              <Input type="text" value={shipment?.status} disabled={true} />
            </div>
          </div>
        </div>
      </BaseModal>
    </>
  )
}

export default ShipmentViewModal