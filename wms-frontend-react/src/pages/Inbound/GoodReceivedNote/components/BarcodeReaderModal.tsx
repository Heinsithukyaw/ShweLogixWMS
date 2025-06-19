import React, { useState } from 'react'
import Button from '../../../../components/ui/button/Button'
import BaseModal from '../../../../components/ui/modal'
import Label from '../../../../components/form/Label'
import Input from '../../../../components/form/input/InputField'
import Spinner from '../../../../components/ui/loading/spinner'
import BarcodeScanner from '../../../../components/BarcodeScanner'
import provideUtility from '../../../../utils/toast'
import { FaBarcode } from 'react-icons/fa6'
import { FaCamera } from 'react-icons/fa'

interface Props {
  isBarcodeModalOpen: true | false
  handleCloseModal: () => void
}

const BarcodeReaderModal: React.FC<Props> = ({
  isBarcodeModalOpen,
  handleCloseModal,
}) => {
  const [isLoading, setIsLoading] = useState<any>(false)
  const [barcode, setBarcode] = useState<string>('')
  const [isManual, setIsManual] = useState<any>(true)

  const { showToast } = provideUtility()

  return (
    <>
      <BaseModal
        isOpen={isBarcodeModalOpen}
        onClose={handleCloseModal}
        isFullscreen={false}
      >
        <div className="space-y-6">
          <h2 className="text-xl font-semibold text-gray-800">Scan Barcode</h2>
          <div className="flex flex-row">
            <Button
              variant="primary"
              startIcon={''}
              onClick={() => setIsManual(true)}
            >
              <FaBarcode />
              Manual Entry
            </Button>
            <button
              className="flex flex-row justify-center items-center bg-white border border-blue-600 rounded-lg p-3 ms-2"
              onClick={() => setIsManual(false)}
            >
              <FaCamera className="me-2" />
              Camera Scan
            </button>
          </div>
          {isManual ? (
            <div className="grid grid-cols-1">
              <div>
                <Label>Barcode</Label>
                <Input
                  type="text"
                  value={barcode}
                  onChange={() => setBarcode(barcode)}
                />
              </div>
            </div>
          ) : (
            <div className="grid grid-cols-1">
              <div>
                <BarcodeScanner />
              </div>
            </div>
          )}

          <div className="flex justify-end gap-2">
            <Button variant="secondary" onClick={handleCloseModal}>
              Cancel
            </Button>
            <Button
              variant="primary"
              startIcon={isLoading && <Spinner size={4} />}
            >
              Submit
            </Button>
          </div>
        </div>
      </BaseModal>
    </>
  )
}

export default BarcodeReaderModal
