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

const UnloadingSessionViewModal:React.FC<Props> = ({isViewOpen,handleCloseModal}) => {
   
   const unloadingSession = useAppSelector((state: RootState) => state.unloadingSession?.data)


  return (
    <>
      <BaseModal
        isOpen={isViewOpen}
        onClose={handleCloseModal}
        isFullscreen={false}
      >
        <div className="space-y-6">
          <h2 className="text-xl font-semibold text-gray-800">
            Add New Unloading Session
          </h2>
          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <Label>
                Unloading Session Code<span className="text-error-500">*</span>
              </Label>
              <Input
                type="text"
                value={unloadingSession?.unloading_session_code}
                disabled={true}
              />
            </div>
            <div>
              <Label>
                Shipment Code<span className="text-error-500">*</span>
              </Label>
              <Input
                type="text"
                value={unloadingSession?.inbound_shipment_code}
                disabled={true}
              />
            </div>
            <div>
              <Label>
                Dock<span className="text-error-500">*</span>
              </Label>
              <Input type="text" value={unloadingSession?.dock_code} disabled={true} />
            </div>
            <div>
              <Label>
                Supervisor<span className="text-error-500">*</span>
              </Label>
              <Input
                type="text"
                value={unloadingSession?.supervisor_code}
                disabled={true}
              />
            </div>
            <div>
              <Label>Start Time</Label>
              <Input type="date" value={unloadingSession?.start_time} disabled={true} />
            </div>
            <div>
              <Label>End Time</Label>
              <Input type="date" value={unloadingSession?.end_time} disabled={true} />
            </div>
            <div>
              <Label>Total Pallets Unloaded</Label>
              <Input
                type="number"
                value={unloadingSession?.total_pallets_unloaded}
                disabled={true}
              />
            </div>
            <div>
              <Label>Total Items Unloaded</Label>
              <Input
                type="number"
                value={unloadingSession?.total_items_unloaded}
                disabled={true}
              />
            </div>
            <div>
              <Label>Equipment Used</Label>
              <Input
                type="text"
                value={unloadingSession?.equipment_used}
                disabled={true}
              />
            </div>
            <div className="col-span-full">
              <Label>notes</Label>
              <TextAreaInput value={unloadingSession?.notes} disabled={true} />
            </div>

            <div>
              <Label>
                Status<span className="text-error-500">*</span>
              </Label>
              <Input
                type="text"
                value={unloadingSession?.status}
                disabled={true}
              />
            </div>
          </div>
        </div>
      </BaseModal>
    </>
  )
}

export default UnloadingSessionViewModal