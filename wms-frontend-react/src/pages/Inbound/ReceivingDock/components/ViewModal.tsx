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

const ReceivingDockViewModal:React.FC<Props> = ({isViewOpen,handleCloseModal}) => {

   const receivingDock = useAppSelector((state: RootState) => state.receivingDock?.data)


//    const dockStatus = [
//      {id: 0,value: 'Out Of Services',},
//      {id: 1,value: 'In Use',},
//      {id: 2,value: 'Available',},
//    ]

//    const dockTypeData = [
//     {id: 0,value: 'Truck',},
//     {id: 1,value: 'Container',},
//    ]

//    const dockFeaturesData = [
//      { id: 0, value: 'Standard Dock' },
//      { id: 1, value: 'Level Adjuster' },
//      { id: 2, value: 'Temperature Controlled' },
//      { id: 3, value: 'Double Wide' },
//      { id: 4, value: 'High Capacity' },
//      { id: 5, value: 'Container Unloading' },
//    ]

  return (
    <>
      <BaseModal
        isOpen={isViewOpen}
        onClose={handleCloseModal}
        isFullscreen={false}
      >
        <div className="space-y-6">
          <h2 className="text-xl font-semibold text-gray-800">Add New Dock</h2>
          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <Label>
                Dock Code<span className="text-error-500">*</span>
              </Label>
              <Input
                type="text"
                value={receivingDock?.dock_code}
                disabled={true}
              />
            </div>
            <div>
              <Label>
                Dock Name<span className="text-error-500">*</span>
              </Label>
              <Input
                type="text"
                value={receivingDock?.dock_number}
                disabled={true}
              />
            </div>
            <div>
              <Label>
                Dock Type<span className="text-error-500">*</span>
              </Label>
              <Input
                type="text"
                value={receivingDock?.dock_type}
                disabled={true}
              />
            </div>
            <div>
              <Label>
                Zone<span className="text-error-500">*</span>
              </Label>
              <Input
                type="text"
                value={receivingDock?.zone_code}
                disabled={true}
              />
            </div>
            <div>
              <Label>Features</Label>
              <Input
                type="text"
                value={receivingDock?.features}
                disabled={true}
              />
            </div>
            <div className="">
              <Label>Additional Features</Label>
              <TextAreaInput
                value={receivingDock?.additional_features}
                disabled={true}
              />
            </div>
            <div>
              <Label>
                Status<span className="text-error-500">*</span>
              </Label>
              <Input
                type="text"
                value={receivingDock?.status}
                disabled={true}
              />
            </div>
          </div>
        </div>
      </BaseModal>
    </>
  )
}

export default ReceivingDockViewModal