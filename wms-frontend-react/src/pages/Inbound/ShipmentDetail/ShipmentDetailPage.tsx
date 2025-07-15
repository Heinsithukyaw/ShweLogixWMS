import React,{ useState, useEffect } from 'react'
import Button from '../../../components/ui/button/Button'

import { RootState } from '../../../store/store'
import { useAppDispatch, useAppSelector } from '../../../store/hook'

import ShipmentDetailTable from './components/Table'
import ShipmentDetailCreateModal from './components/CreateModal'

import { fetchShipmentDetailLists } from './services/shipmentDetailApi'

function ShipmentDetailPage() {

    const [isCreateOpen, setIsCreateOpen] = useState<boolean>(false)

    const isLoading = useAppSelector((state: RootState) => state.shipmentDetail?.loading)
    const error = useAppSelector((state: RootState) => state.shipmentDetail?.error)
    const dispatch = useAppDispatch()
    const shipmentDetailLists = useAppSelector((state: RootState) => state.shipmentDetail?.content || [])
    const isFetched = useAppSelector((state: RootState) => state.shipmentDetail?.isFetched)

    useEffect(() => {
        if(!isFetched){
            dispatch(fetchShipmentDetailLists())
        }
    }, [dispatch,isFetched])

    const handleCloseModal = () => {
      setIsCreateOpen(false)
    }

    if (isLoading) return (
      <div className="flex justify-center items-center space-x-2">
        <div className="w-5 h-5 border-2 border-t-blue-500 border-blue-200 rounded-full animate-spin"></div>
        <span className="text-sm text-gray-500">Loading...</span>
      </div>
    )
    if (error) return <p>Failed to load Inbound Shipment Details.</p>
  return (
    <>
      <div className="space-y-10">
        <div className="flex justify-between items-center">
          <h1 className="text-xl font-semibold">
            Inbound Shipment Detail Lists
          </h1>
          <Button
            variant="primary"
            size="sm"
            onClick={() => setIsCreateOpen(true)}
          >
            Add Shipment Detail
          </Button>
        </div>

        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 md:gap-6 xl:grid-cols-4">
          <div className="rounded-md shadow-lg border border-gray-200 bg-white p-2">
            <div className="rounded-2xl border-gray-200 bg-white p-5 ">
              <p className="text-gray-500 text-theme-sm dark:text-gray-400">
                Total Line Items
              </p>
              <div className="flex justify-between mt-3">
                <div className="">
                  <h4 className="text-2xl font-bold text-gray-800 dark:text-white/90">
                    {shipmentDetailLists.length}
                  </h4>
                </div>
              </div>
            </div>
          </div>

          <div className="rounded-md shadow-lg border border-gray-200 bg-white p-2">
            <div className="rounded-2xl border-gray-200 p-5 ">
              <p className="text-gray-500 text-theme-sm dark:text-gray-400">
                Total Items Received
              </p>
              <div className="flex justify-between mt-3">
                <div className="">
                  <h4 className="text-2xl font-bold text-gray-800 dark:text-white/90">
                    {shipmentDetailLists.filter((x: any) => x.status == 3).length}
                  </h4>
                </div>
              </div>
            </div>
          </div>

          <div className="rounded-md shadow-lg border border-gray-200 bg-white p-2">
            <div className="rounded-2xl  border-gray-200 bg-white p-5 ">
              <p className="text-gray-500 text-theme-sm dark:text-gray-400">
                Damaged Items
              </p>
              <div className="flex justify-between mt-3">
                <div className="">
                  <h4 className="text-2xl font-bold text-gray-800 dark:text-white/90">
                    {shipmentDetailLists.filter((x: any) => x.status == 2).length}
                  </h4>
                </div>
              </div>
            </div>
          </div>

          <div className="rounded-md shadow-lg border border-gray-200 bg-white p-2">
            <div className="rounded-2xl border-gray-200 bg-white p-5 ">
              <p className="text-gray-500 text-theme-sm dark:text-gray-400">
                Exception
              </p>
              <div className="flex justify-between mt-3">
                <div className="">
                  <h4 className="text-2xl font-bold text-gray-800 dark:text-white/90">
                    {shipmentDetailLists.filter((x: any) => x.status == 0).length}
                  </h4>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div className="">
          {shipmentDetailLists && (
            <ShipmentDetailTable
              isLoading={isLoading}
              shipmentDetailLists={shipmentDetailLists}
            />
          )}
        </div>
      </div>
      {isCreateOpen && (
        <ShipmentDetailCreateModal
          isCreateOpen={isCreateOpen}
          handleCloseModal={handleCloseModal}
        />
      )}
    </>
  )
}

export default ShipmentDetailPage