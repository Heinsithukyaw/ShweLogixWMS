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

const PutAwayTaskViewModal: React.FC<Props> = ({
  isViewOpen,
  handleCloseModal,
}) => {
  const task = useAppSelector((state: RootState) => state.putAwayTask?.data)

  return (
    <>
      <BaseModal
        isOpen={isViewOpen}
        onClose={handleCloseModal}
        isFullscreen={false}
      >
        <div className="space-y-6">
          <h2 className="text-xl font-semibold text-gray-800">Putaway Task</h2>
          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <Label>
                Putaway Task Code<span className="text-error-500">*</span>
              </Label>
              <Input
                type="text"
                value={task?.put_away_task_code}
                disabled={true}
              />
            </div>
            <div>
              <Label>
                Detail<span className="text-error-500">*</span>
              </Label>
              <Input
                type="text"
                value={task?.inbound_shipment_detail_code}
                disabled={true}
              />
            </div>
            <div>
              <Label>
                Assigned To<span className="text-error-500">*</span>
              </Label>
              <Input
                type="text"
                value={task?.assigned_to_code}
                disabled={true}
              />
            </div>

            <div>
              <Label>Created Date</Label>
              <Input type="date" value={task?.created_date} disabled={true} />
            </div>
            <div>
              <Label>Due Date</Label>
              <Input type="date" value={task?.due_date} disabled={true} />
            </div>
            <div>
              <Label>Start Time</Label>
              <Input type="date" value={task?.start_time} disabled={true} />
            </div>
            <div>
              <Label>Complete Time</Label>
              <Input type="date" value={task?.complete_time} disabled={true} />
            </div>
            <div>
              <Label>
                Source Location<span className="text-error-500">*</span>
              </Label>
              <Input
                type="text"
                value={task?.source_location_code}
                disabled={true}
              />
            </div>
            <div>
              <Label>
                Destination Location<span className="text-error-500">*</span>
              </Label>
              <Input
                type="text"
                value={task?.destination_location_code}
                disabled={true}
              />
            </div>

            <div>
              <Label>Qty</Label>
              <Input type="number" value={task?.qty} disabled={true} />
            </div>
            <div>
              <Label>Priority</Label>
              <Input type="text" value={task?.priority} disabled={true} />
            </div>

            <div>
              <Label>
                Status<span className="text-error-500">*</span>
              </Label>
              <Input type="text" value={task?.status} disabled={true} />
            </div>
          </div>
        </div>
      </BaseModal>
    </>
  )
}

export default PutAwayTaskViewModal
