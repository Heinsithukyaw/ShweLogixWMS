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

const ReceivingEquipmentViewModal:React.FC<Props> = ({isViewOpen,handleCloseModal}) => {
   
   const receivingEquipment = useAppSelector((state: RootState) => state.receivingEquipment?.data)

  //  const equipmentStatus = [
  //    {id: 0,value: 'In Use',},
  //    {id: 1,value: 'Maintenance',},
  //    {id: 2,value: 'Available',},
  //  ]

  //  const equipmentTypeData = [
  //    { id: 0, value: 'Forklift' },
  //    { id: 1, value: 'Pallet Jack' },
  //    { id: 0, value: 'Scanner' },
  //    { id: 1, value: 'Conveyor' },
  //    { id: 0, value: 'Hand Truck' },
  //    { id: 1, value: 'Scales' },
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
            Receiving Equipment
          </h2>
          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <Label>
                Receiving Equipment Code
                <span className="text-error-500">*</span>
              </Label>
              <Input
                type="text"
                value={receivingEquipment?.receiving_equipment_code}
                disabled={true}
              />
            </div>
            <div>
              <Label>
                Receiving Equipment Name
                <span className="text-error-500">*</span>
              </Label>
              <Input
                type="text"
                value={receivingEquipment?.receiving_equipment_name}
                disabled={true}
              />
            </div>
            <div>
              <Label>
                Receiving Equipment Type
                <span className="text-error-500">*</span>
              </Label>
              <Input
                type="text"
                value={receivingEquipment?.receiving_equipment_type}
                disabled={true}
              />
            </div>
            <div>
              <Label>
                Assigned To<span className="text-error-500">*</span>
              </Label>
              <Input
                type="text"
                value={receivingEquipment?.assigned_to_code}
                disabled={true}
              />
            </div>
            <div>
              <Label>
                Last Maintenance Date
                <span className="text-error-500">*</span>
              </Label>
              <Input
                type="date"
                value={receivingEquipment?.last_maintenance_date}
                disabled={true}
              />
            </div>
            <div>
              <Label>
                Days Since Maintenance
                <span className="text-error-500">*</span>
              </Label>
              <Input
                type="number"
                value={receivingEquipment?.days_since_maintenance}
                disabled={true}
              />
            </div>
            <div className="">
              <Label>Notes</Label>
              <TextAreaInput value={receivingEquipment?.notes} disabled={true} />
            </div>

            <div>
              <Label>
                Status<span className="text-error-500">*</span>
              </Label>
              <Input
                type="text"
                value={receivingEquipment?.status}
                disabled={true}
              />
            </div>
          </div>
        </div>
      </BaseModal>
    </>
  )
}

export default ReceivingEquipmentViewModal