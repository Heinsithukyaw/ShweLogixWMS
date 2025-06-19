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

const ReceivingExceptionViewModal:React.FC<Props> = ({isViewOpen,handleCloseModal}) => {

  const receivingException = useAppSelector((state: RootState) => state.receivingException?.data)

  return (
    <>
      <BaseModal
        isOpen={isViewOpen}
        onClose={handleCloseModal}
        isFullscreen={false}
      >
        <div className="space-y-6">
          <h2 className="text-xl font-semibold text-gray-800">
            Receiving Exception
          </h2>
          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <Label>
                Exception Code<span className="text-error-500">*</span>
              </Label>
              <Input
                type="text"
                value={receivingException?.exception_code}
                disabled={true}
              />
            </div>
            <div>
              <Label>
                ASN<span className="text-error-500">*</span>
              </Label>
              <Input type="text" value={receivingException?.asn_code} disabled={true} />
            </div>
            <div>
              <Label>
                ASN Detail<span className="text-error-500">*</span>
              </Label>
              <Input
                type="text"
                value={receivingException?.asn_detail_code}
                disabled={true}
              />
            </div>
            <div>
              <Label>
                Item<span className="text-error-500">*</span>
              </Label>
              <Input type="text" value={receivingException?.item_code} disabled={true} />
            </div>
            <div className="">
              <Label>Item Description</Label>
              <TextAreaInput
                value={receivingException?.item_description}
                disabled={true}
              />
            </div>
            <div>
              <Label>Reported By</Label>
              <Input
                type="text"
                value={receivingException?.reported_by_code}
                disabled={true}
              />
            </div>
            <div>
              <Label>Assigned To</Label>
              <Input
                type="text"
                value={receivingException?.assigned_to_code}
                disabled={true}
              />
            </div>
            <div>
              <Label>Reported Date</Label>
              <Input
                type="date"
                value={receivingException?.reported_date}
                disabled={true}
              />
            </div>
            <div>
              <Label>Resolved Date</Label>
              <Input
                type="date"
                value={receivingException?.resolved_date}
                disabled={true}
              />
            </div>

            <div>
              <Label>Severity</Label>
              <Input type="text" value={receivingException?.severity} disabled={true} />
            </div>

            <div className="col-span-full">
              <Label>Description</Label>
              <TextAreaInput value={receivingException?.description} disabled={true} />
            </div>

            <div>
              <Label>
                Status<span className="text-error-500">*</span>
              </Label>
              <Input type="text" value={receivingException?.status} disabled={true} />
            </div>
          </div>
        </div>
      </BaseModal>
    </>
  )
}

export default ReceivingExceptionViewModal