import React,{ useState, useEffect } from 'react'
import Button from '../../../components/ui/button/Button'
import { FaTruck } from 'react-icons/fa'
import { FaBoxArchive } from 'react-icons/fa6'
import { AiFillFileText } from 'react-icons/ai'

import { RootState } from '../../../store/store'
import { useAppDispatch, useAppSelector } from '../../../store/hook'

import AsnTable from './components/AsnTable'
import AsnCreateModal from './components/AsnCreateModal'

import { fetchAsnLists } from './services/asnApi'

function AsnPage() {

    const [isCreateOpen, setIsCreateOpen] = useState<boolean>(false)

    const isLoading = useAppSelector((state: RootState) => state.asn?.loading)
    const error = useAppSelector((state: RootState) => state.asn?.error)
    const dispatch = useAppDispatch()
    const asnLists = useAppSelector((state: RootState) => state.asn?.content || [])
    const isFetched = useAppSelector((state: RootState) => state.asn?.isFetched)

    useEffect(() => {
        if(!isFetched){
            dispatch(fetchAsnLists())
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
    if (error) return <p>Failed to load Advanced Shipping Notes.</p>
  return (
    <>
      <div className="space-y-10">
        <div className="flex justify-between items-center">
          <h1 className="text-xl font-semibold">
            Advanced Shipping Notice Lists
          </h1>
          <Button
            variant="primary"
            size="sm"
            onClick={() => setIsCreateOpen(true)}
          >
            Add ASN
          </Button>
        </div>

        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 md:gap-6 xl:grid-cols-3">
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
        </div>

        <div className="">
          {asnLists && <AsnTable isLoading={isLoading} asnLists={asnLists}/>}
        </div>
      </div>
      {isCreateOpen && (
        <AsnCreateModal
          isCreateOpen={isCreateOpen}
          handleCloseModal={handleCloseModal}
        />
      )}
      
    </>
  )
}

export default AsnPage