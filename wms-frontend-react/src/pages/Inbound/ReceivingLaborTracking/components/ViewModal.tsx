import React from 'react'
import BaseModal from '../../../../components/ui/modal'
import Label from '../../../../components/form/Label'
import Input from '../../../../components/form/input/InputField'
import ToggleSwitchInput from '../../../../components/form/form-elements/ToggleSwitch'
import TextAreaInput from '../../../../components/form/form-elements/TextAreaInput'
import { useAppSelector } from '../../../../store/hook'
import { RootState } from '../../../../store/store'

interface Props {
    isViewOpen: true | false
    handleCloseModal: () => void
}

const ReceivingLaborTrackingViewModal:React.FC<Props> = ({isViewOpen,handleCloseModal}) => {
   
   const receivingLaborTracking = useAppSelector(
     (state: RootState) => state.receivingLaborTracking?.data
   )

  //  const taskTypeData = [
  //    { id: 0, value: 'Putaway' },
  //    { id: 1, value: 'Unloading' },
  //    { id: 2, value: 'Inspection' },
  //    { id: 3, value: 'Cross-Dock' },
  //    { id: 4, value: 'Packaging Damage' },
  //    { id: 5, value: 'Sorting' },
  //    { id: 6, value: 'Labeling' },
  //  ]

  return (
    <>
      <BaseModal
        isOpen={isViewOpen}
        onClose={handleCloseModal}
        isFullscreen={false}
      >
        <div className="space-y-6">
          <h2 className="text-xl font-semibold text-gray-800">
            Receiving Labor Tracking
          </h2>
          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <Label>
                Labor Entry Code<span className="text-error-500">*</span>
              </Label>
              <Input
                type="text"
                value={receivingLaborTracking?.labor_entry_code}
                disabled={true}
              />
            </div>
            <div>
              <Label>
                Task Type<span className="text-error-500">*</span>
              </Label>
              <Input type="text" value={receivingLaborTracking?.task_type} disabled={true} />
            </div>
            <div>
              <Label>
                Shipment<span className="text-error-500">*</span>
              </Label>
              <Input
                type="text"
                value={receivingLaborTracking?.inbound_shipment_code}
                disabled={true}
              />
            </div>
            <div>
              <Label>
                Employee<span className="text-error-500">*</span>
              </Label>
              <Input
                type="text"
                value={receivingLaborTracking?.emp_code}
                disabled={true}
              />
            </div>

            <div className="">
              <Label>Start Time</Label>
              <Input
                type="date"
                value={receivingLaborTracking?.start_time}
                disabled={true}
              />
            </div>
            <div>
              <Label>End Time</Label>
              <Input
                type="date"
                value={receivingLaborTracking?.end_time}
                disabled={true}
              />
            </div>
            <div>
              <Label>Duration (min)</Label>
              <Input
                type="number"
                value={receivingLaborTracking?.duration_min}
                disabled={true}
              />
            </div>

            <div>
              <Label>Items Processed</Label>
              <Input
                type="number"
                value={receivingLaborTracking?.items_processed}
                disabled={true}
              />
            </div>

            <div>
              <Label>Pallets Processed</Label>
              <Input
                type="number"
                value={receivingLaborTracking?.pallets_processed}
                disabled={true}
              />
            </div>
            <div>
              <Label>Items/Minute</Label>
              <Input
                type="string"
                value={receivingLaborTracking?.items_min}
                disabled={true}
              />
            </div>
            <div className="col-span-full">
              <Label>Notes</Label>
              <TextAreaInput
                value={receivingLaborTracking?.notes}
                disabled={true}
              />
            </div>

            <div>
              <Label>Status</Label>
              <ToggleSwitchInput
                label="Enable Active"
                defaultChecked={!!receivingLaborTracking?.status}
                disabled={true}              
                />
            </div>
          </div>
        </div>
      </BaseModal>
    </>
  )
}

export default ReceivingLaborTrackingViewModal