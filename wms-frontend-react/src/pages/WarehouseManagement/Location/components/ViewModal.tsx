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

const LocationViewModal:React.FC<Props> = ({isViewOpen,handleCloseModal}) => {
   const location = useAppSelector((state: RootState) => state.mLocation?.data)

  return (
    <>
      <BaseModal
        isOpen={isViewOpen}
        onClose={handleCloseModal}
        isFullscreen={false}
      >
        <div className="space-y-6">
          <h2 className="text-xl font-semibold text-gray-800">Location</h2>
          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <Label>
                Location Code<span className="text-error-500">*</span>
              </Label>
              <Input
                type="text"
                value={location?.location_code}
                disabled={true}
              />
            </div>
            <div>
              <Label>
                Location Name<span className="text-error-500">*</span>
              </Label>
              <Input
                type="text"
                value={location?.location_name}
                disabled={true}
              />
            </div>

            <div>
              <Label>Location Type</Label>
              <Input
                type="text"
                value={location?.location_type}
                disabled={true}
              />
            </div>
            <div>
              <Label>Zone Code</Label>
              <Input type="text" value={location?.zone_code} disabled={true} />
            </div>
            <div className="">
              <Label>Aisle</Label>
              <Input type="text" value={location?.aisle} disabled={true} />
            </div>
            <div>
              <Label>Row</Label>
              <Input type="text" value={location?.row} disabled={true} />
            </div>
            <div>
              <Label>Level</Label>
              <Input type="text" value={location?.level} disabled={true} />
            </div>
            <div>
              <Label>Bin</Label>
              <Input type="text" value={location?.bin} disabled={true} />
            </div>
            <div>
              <Label>Capacity</Label>
              <Input type="text" value={location?.capacity} disabled={true} />
            </div>
            <div>
              <Label>Capacity Unit</Label>
              <Input
                type="text"
                value={location?.capacity_unit}
                disabled={true}
              />
            </div>
            <div>
              <Label>Status</Label>
              <Input type="text" value={location?.status} disabled={true} />
            </div>
            <div>
              <Label>Restriction</Label>
              <Input
                type="text"
                value={location?.restriction}
                disabled={true}
              />
            </div>
            <div>
              <Label>Bar Code</Label>
              <Input
                type="text"
                value={location?.bar_code}
                disabled={true}
              />
            </div>
            <div className="col-span-full">
              <Label>Description</Label>
              <TextAreaInput
                value={location?.description}
                disabled={true}
              />
            </div>
          </div>
        </div>
      </BaseModal>
    </>
  )
}

export default LocationViewModal