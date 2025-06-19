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

const AsnDetailViewModal:React.FC<Props> = ({isViewOpen,handleCloseModal}) => {
    const asnDetail = useAppSelector((state: RootState) => state.asnDetail?.data)


  return (
    <>
      <BaseModal
        isOpen={isViewOpen}
        onClose={handleCloseModal}
        isFullscreen={false}
      >
        <div className="space-y-6">
          <h2 className="text-xl font-semibold text-gray-800">ASN Detail</h2>
          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <Label>
                ASN Detail Code<span className="text-error-500">*</span>
              </Label>
              <Input
                type="text"
                value={asnDetail?.asn_detail_code}
                disabled={true}
              />
            </div>
            <div>
              <Label>
                ASN<span className="text-error-500">*</span>
              </Label>
              <Input type="text" value={asnDetail?.asn_code} disabled={true} />
            </div>
            <div>
              <Label>
                Item<span className="text-error-500">*</span>
              </Label>
              <Input type="text" value={asnDetail?.item_code} disabled={true} />
            </div>
            <div className="">
              <Label>Item Description</Label>
              <TextAreaInput
                value={asnDetail?.item_description}
                disabled={true}
              />
            </div>
            <div>
              <Label>Expected Qty</Label>
              <Input
                type="number"
                value={asnDetail?.expected_qty}
                disabled={true}
              />
            </div>
            <div>
              <Label>
                UOM<span className="text-error-500">*</span>
              </Label>
              <Input type="text" value={asnDetail?.uom_code} disabled={true} />
            </div>
            <div>
              <Label>Lot Number</Label>
              <Input type="text" value={asnDetail?.lot_number} disabled={true} />
            </div>
            <div>
              <Label>Expiration Date</Label>
              <Input
                type="date"
                value={asnDetail?.expiration_date}
                disabled={true}
              />
            </div>
            <div>
              <Label>Received Qty</Label>
              <Input
                type="number"
                value={asnDetail?.received_qty}
                disabled={true}
              />
            </div>
            <div>
              <Label>Variance</Label>
              <Input type="number" value={asnDetail?.variance} disabled={true} />
            </div>
            <div>
              <Label>Pallet</Label>
              <Input type="text" value={asnDetail?.pallet_code} disabled={true} />
            </div>
            <div>
              <Label>Location</Label>
              <Input
                type="text"
                value={asnDetail?.location_code}
                disabled={true}
              />
            </div>

            <div className="col-span-full">
              <Label>notes</Label>
              <TextAreaInput value={asnDetail?.notes} disabled={true} />
            </div>

            <div>
              <Label>
                Status<span className="text-error-500">*</span>
              </Label>
              <Input
                type="text"
                value={asnDetail?.status}
                disabled={true}
              />
            </div>
          </div>
        </div>
      </BaseModal>
    </>
  )
}

export default AsnDetailViewModal