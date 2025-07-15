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

const ReceivingAppointmentViewModal: React.FC<Props> = ({
  isViewOpen,
  handleCloseModal,
}) => {
 
  const receivingAppointment = useAppSelector((state: RootState) => state.receivingAppointment?.data)

  return (
    <>
      <BaseModal
        isOpen={isViewOpen}
        onClose={handleCloseModal}
        isFullscreen={false}
      >
        <div className="space-y-6">
          <h2 className="text-xl font-semibold text-gray-800">
            Receiving Appointment
          </h2>
          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <Label>
                Appointment Code<span className="text-error-500">*</span>
              </Label>
              <Input
                type="text"
                value={receivingAppointment?.appointment_code}
                disabled={true}
              />
            </div>
            <div>
              <Label>
                Shipment Code<span className="text-error-500">*</span>
              </Label>
              <Input
                type="text"
                value={receivingAppointment?.inbound_shipment_code}
                disabled={true}
              />
            </div>
            <div>
              <Label>
                Supplier<span className="text-error-500">*</span>
              </Label>
              <Input
                type="text"
                value={receivingAppointment?.supplier_code}
                disabled={true}
              />
            </div>
            <div>
              <Label>
                Version Control<span className="text-error-500">*</span>
              </Label>
              <Input
                type="text"
                value={receivingAppointment?.version_control}
                disabled={true}
              />
            </div>
            <div>
              <Label>Purchase Order Number</Label>
              <Input
                type="text"
                value={receivingAppointment?.purchase_order_number}
                disabled={true}
              />
            </div>
            <div>
              <Label>Scheduled Date</Label>
              <Input
                type="text"
                value={receivingAppointment?.scheduled_date}
                disabled={true}
              />
            </div>
            <div>
              <Label>Start Time</Label>
              <Input type="date" value={receivingAppointment?.start_time} disabled={true} />
            </div>
            <div>
              <Label>Ent Time</Label>
              <Input type="date" value={receivingAppointment?.end_time} disabled={true} />
            </div>
            <div>
              <Label>
                Dock<span className="text-error-500">*</span>
              </Label>
              <Input type="text" value={receivingAppointment?.dock_code} disabled={true} />
            </div>

            <div>
              <Label>Carrier Name</Label>
              <Input
                type="text"
                value={receivingAppointment?.carrier_name}
                disabled={true}
              />
            </div>
            <div>
              <Label>Driver Name</Label>
              <Input
                type="text"
                value={receivingAppointment?.driver_name}
                disabled={true}
              />
            </div>
            <div>
              <Label>Driver Phone Number</Label>
              <Input
                type="number"
                value={receivingAppointment?.driver_phone_number}
                disabled={true}
              />
            </div>
            <div>
              <Label>Estimated Pallets</Label>
              <Input
                type="number"
                value={receivingAppointment?.estimated_pallet}
                disabled={true}
              />
            </div>
            <div>
              <Label>Trailer Number</Label>
              <Input
                type="text"
                value={receivingAppointment?.trailer_number}
                disabled={true}
              />
            </div>
            <div>
              <Label>Check In Time</Label>
              <Input
                type="date"
                value={receivingAppointment?.check_in_time}
                disabled={true}
              />
            </div>

            <div>
              <Label>Check Out Time</Label>
              <Input
                type="date"
                value={receivingAppointment?.check_out_time}
                disabled={true}
              />
            </div>

            <div>
              <Label>
                Status<span className="text-error-500">*</span>
              </Label>
              <Input
                type="text"
                value={receivingAppointment?.status}
                disabled={true}
              />
            </div>
          </div>
        </div>
      </BaseModal>
    </>
  )
}

export default ReceivingAppointmentViewModal