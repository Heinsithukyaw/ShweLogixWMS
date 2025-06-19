import React,{useState, useEffect} from 'react'
import { useParams } from 'react-router-dom'
import { getZoneLocationContentData } from '../../../../../store/features/masterData/LocationSlice'
import { useAppDispatch, useAppSelector } from '../../../../../store/hook'
import { RootState } from '../../../../../store/store'
import { useNavigate } from 'react-router-dom'

import ZoneLocationTable from './components/Table'
import ZoneLocationCreateModal from './components/CreateModal'

import Button from '../../../../../components/ui/button/Button'
import { fetchZoneLocationLists } from './services/zoneLocationApi'


const ZoneLocationPage: React.FC = () => {
  const { zoneId } = useParams()
  const dispatch = useAppDispatch()
  const navigate = useNavigate()
  const zoneLocationLists = useAppSelector((state: RootState) => state.mLocation?.zoneContent)
  const [isCreateOpen, setIsCreateOpen] = useState<boolean>(false)
  const [isLoading, setIsLoading] = useState<boolean>(false)

  useEffect(() => {
    if (zoneId) {
      dispatch(getZoneLocationContentData(parseInt(zoneId)))
    }
  }, [zoneId, dispatch])

  useEffect(() => {
    if(zoneId){
      dispatch(fetchZoneLocationLists(zoneId))
    }
  }, [dispatch,zoneId])

  useEffect(() => {
    setIsLoading(true)

    const interval = setInterval(() => {
      setIsLoading(true) 
      setTimeout(() => setIsLoading(false), 2000)
    }, 10000)

    return () => clearInterval(interval)
  }, [])

  const handleCloseModal = () => {
    setIsCreateOpen(false)
  }

  const handleBackToZone = () => {
    navigate('/warehouse-management/zone')
  }
    
  return (
    <>
      <div className="space-y-10">
        <div className="flex justify-between items-center">
          <h1 className="text-xl font-semibold">Zone Location Lists</h1>
          <div className="flex justify-center items-center gap-4">
            <Button
              variant="secondary"
              size="sm"
              onClick={handleBackToZone}
            >
              Back To Zone
            </Button>
            <Button
              variant="primary"
              size="sm"
              onClick={() => setIsCreateOpen(true)}
            >
              Add Location
            </Button>
          </div>
        </div>

        {/* <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 md:gap-6 xl:grid-cols-3">
          <div className="rounded-md shadow-lg border border-gray-200 bg-white p-2">
            <div className="rounded-2xl border border-gray-200 bg-white p-5 ">
              <p className="text-gray-500 text-theme-sm dark:text-gray-400">
                Pending ASNs
              </p>
              <div className="flex justify-between mt-3">
                <div className="">
                  <h4 className="text-2xl font-bold text-gray-800 dark:text-white/90">
                    0
                  </h4>
                  <p className="text-gray-500 text-theme-sm dark:text-gray-400">
                    Awaiting receipt
                  </p>
                </div>
                <div className="flex items-center gap-1">
                  <span className="">
                    <FaTruck style={{ fontSize: 40, color: '#1E2F55' }} />
                  </span>
                </div>
              </div>
            </div>
          </div>

          <div className="rounded-md shadow-lg border border-gray-200 bg-white p-2">
            <div className="rounded-2xl border border-gray-200 bg-white p-5 ">
              <p className="text-gray-500 text-theme-sm dark:text-gray-400">
                Operational Docks
              </p>
              <div className="flex justify-between mt-3">
                <div className="">
                  <h4 className="text-2xl font-bold text-gray-800 dark:text-white/90">
                    4
                  </h4>
                  <p className="text-gray-500 text-theme-sm dark:text-gray-400">
                    Across All ASNs
                  </p>
                </div>
                <div className="flex items-center gap-1">
                  <span className="">
                    <span className="text-xs">
                      <FaBoxArchive
                        style={{ fontSize: 40, color: '#0D74C6' }}
                      />
                    </span>
                  </span>
                </div>
              </div>
            </div>
          </div>

          <div className="rounded-md shadow-lg border border-gray-200 bg-white p-2">
            <div className="rounded-2xl border border-gray-200 bg-white p-5 ">
              <p className="text-gray-500 text-theme-sm dark:text-gray-400">
                Total Pallets
              </p>
              <div className="flex justify-between mt-3">
                <div className="">
                  <h4 className="text-2xl font-bold text-gray-800 dark:text-white/90">
                    2
                  </h4>
                  <p className="text-gray-500 text-theme-sm dark:text-gray-400">
                    Units Expected
                  </p>
                </div>
                <div className="flex items-center gap-1">
                  <span className="">
                    <AiFillFileText
                      style={{ fontSize: 40, color: '#4CAF50' }}
                    />
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div> */}

        <div className="">
          {zoneLocationLists && (
            <ZoneLocationTable
              isLoading={isLoading}
              zoneLocationLists={zoneLocationLists}
              zoneId={zoneId}
            />
          )}
        </div>
      </div>
      {isCreateOpen && (
        <ZoneLocationCreateModal
          isCreateOpen={isCreateOpen}
          handleCloseModal={handleCloseModal}
          zoneId={zoneId}
        />
      )}
    </>
  )
}

export default ZoneLocationPage