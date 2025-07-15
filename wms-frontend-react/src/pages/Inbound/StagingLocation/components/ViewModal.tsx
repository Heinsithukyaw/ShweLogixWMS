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

const StagingLocationViewModal:React.FC<Props> = ({isViewOpen,handleCloseModal}) => {

  const stagingLocation = useAppSelector((state: RootState) => state.stagingLocation?.data)

  //  const stagingLocationStatus = [
  //    { id: 0, value: 'In Active' },
  //    { id: 1, value: 'Maintenance' },
  //    { id: 2, value: 'Active' },
  //  ]

  //  const stagingLocationTypeData = [
  //    { id: 0, value: 'General' },
  //    { id: 1, value: 'Cold Storage' },
  //    { id: 2, value: 'Hazardous Materials' },
  //  ]
  return (
    <>
      <BaseModal
        isOpen={isViewOpen}
        onClose={handleCloseModal}
        isFullscreen={false}
      >
        <div className="space-y-6">
          <h2 className="text-xl font-semibold text-gray-800">Add Staging</h2>
          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <Label>
                Staging Location Code<span className="text-error-500">*</span>
              </Label>
              <Input
                type="text"
                value={stagingLocation?.staging_location_code}
                disabled={true}
              />
            </div>

            <div>
              <Label>Staging Location Name</Label>
              <Input
                type="text"
                value={stagingLocation?.staging_location_name}
                disabled={true}
              />
            </div>
            <div>
              <Label>
                Type<span className="text-error-500">*</span>
              </Label>
              <Input
                type="text"
                value={stagingLocation?.type}
                disabled={true}
              />
            </div>
            <div>
              <Label>
                Warehouse<span className="text-error-500">*</span>
              </Label>
              <Input
                type="text"
                value={stagingLocation?.warehouse_code}
                disabled={true}
              />
            </div>
            <div>
              <Label>
                Area<span className="text-error-500">*</span>
              </Label>
              <Input
                type="text"
                value={stagingLocation?.area_code}
                disabled={true}
              />
            </div>
            <div>
              <Label>
                Zone<span className="text-error-500">*</span>
              </Label>
              <Input
                type="text"
                value={stagingLocation?.zone_code}
                disabled={true}
              />
            </div>
            <div>
              <Label>Capacity</Label>
              <Input
                type="number"
                value={stagingLocation?.capacity}
                disabled={true}
              />
            </div>
            <div>
              <Label>Current Usage</Label>
              <Input
                type="number"
                value={stagingLocation?.current_usage}
                disabled={true}
              />
            </div>
            <div className="col-span-full">
              <Label>Description</Label>
              <TextAreaInput
                value={stagingLocation?.description}
                disabled={true}
              />
            </div>

            <div>
              <Label>
                Status<span className="text-error-500">*</span>
              </Label>
              <Input
                type="text"
                value={stagingLocation?.status}
                disabled={true}
              />
            </div>
          </div>
        </div>
      </BaseModal>
    </>
  )
}

export default StagingLocationViewModal