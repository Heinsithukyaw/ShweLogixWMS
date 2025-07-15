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

const CrossDockingTaskViewModal:React.FC<Props> = ({isViewOpen,handleCloseModal}) => {
   
   const crossDockingTask = useAppSelector((state: RootState) => state.crossDockingTask?.data)

  return (
    <>
      <BaseModal
        isOpen={isViewOpen}
        onClose={handleCloseModal}
        isFullscreen={false}
      >
        <div className="space-y-6">
          <h2 className="text-xl font-semibold text-gray-800">
            CrossDocking Task
          </h2>
          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <Label>
                CrossDocking Task Code<span className="text-error-500">*</span>
              </Label>
              <Input
                type="text"
                value={crossDockingTask?.cross_docking_task_code}
                disabled={true}
              />
            </div>
            <div>
              <Label>
                ASN<span className="text-error-500">*</span>
              </Label>
              <Input type="text" value={crossDockingTask?.asn_code} disabled={true} />
            </div>
            <div>
              <Label>
                ASN Detail<span className="text-error-500">*</span>
              </Label>
              <Input
                type="text"
                value={crossDockingTask?.asn_detail_code}
                disabled={true}
              />
            </div>
            <div>
              <Label>
                Item<span className="text-error-500">*</span>
              </Label>
              <Input type="text" value={crossDockingTask?.item_code} disabled={true} />
            </div>
            <div className="">
              <Label>Item Description</Label>
              <Input
                type="text"
                value={crossDockingTask?.item_description}
                disabled={true}
              />
            </div>
            <div>
              <Label>Assigned To</Label>
              <Input
                type="text"
                value={crossDockingTask?.assigned_to_code}
                disabled={true}
              />
            </div>
            <div>
              <Label>
                Source Location<span className="text-error-500">*</span>
              </Label>
              <Input
                type="text"
                value={crossDockingTask?.source_location_code}
                disabled={true}
              />
            </div>
            <div>
              <Label>
                Destination Location<span className="text-error-500">*</span>
              </Label>
              <Input
                type="text"
                value={crossDockingTask?.destination_location_code}
                disabled={true}
              />
            </div>
            <div>
              <Label>Created Date</Label>
              <Input
                type="date"
                value={crossDockingTask?.created_date}
                disabled={true}
              />
            </div>
            <div>
              <Label>Start Time</Label>
              <Input type="date" value={crossDockingTask?.start_time} disabled={true} />
            </div>
            <div>
              <Label>Complete Time</Label>
              <Input
                type="date"
                value={crossDockingTask?.complete_time}
                disabled={true}
              />
            </div>
            <div>
              <Label>Priority</Label>
              <Input type="text" value={crossDockingTask?.priority} disabled={true} />
            </div>

            <div>
              <Label>
                Status<span className="text-error-500">*</span>
              </Label>
              <Input
                type="text"
                value={crossDockingTask?.status}
                disabled={true}
              />
            </div>
          </div>
        </div>
      </BaseModal>
    </>
  )
}

export default CrossDockingTaskViewModal