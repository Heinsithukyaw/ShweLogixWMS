import React,{ useState, useEffect } from 'react'
import Button from '../../../components/ui/button/Button'

import { RootState } from '../../../store/store'
import { useAppDispatch, useAppSelector } from '../../../store/hook'

import UnloadingSessionTable from './components/Table'
import UnloadingSessionCreateModal from './components/CreateModal'

import { fetchUnloadingSessionLists } from './services/unloadingSessionApi'

function UnloadingSessionPage() {

    const [isCreateOpen, setIsCreateOpen] = useState<boolean>(false)

    const isLoading = useAppSelector((state: RootState) => state.asnDetail?.loading)
    const error = useAppSelector((state: RootState) => state.asnDetail?.error)
    const dispatch = useAppDispatch()
    const unloadingSessionLists = useAppSelector((state: RootState) => state.unloadingSession?.content || [])
    const isFetched = useAppSelector((state: RootState) => state.asnDetail?.isFetched)

    useEffect(() => {
        if(!isFetched){
            dispatch(fetchUnloadingSessionLists())
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
    if (error) return <p>Failed to load Unloading Session.</p>
  return (
    <>
      <div className="space-y-10">
        <div className="flex justify-between items-center">
          <h1 className="text-xl font-semibold">
            Unloading Session Lists
          </h1>
          <Button
            variant="primary"
            size="sm"
            onClick={() => setIsCreateOpen(true)}
          >
            Add Unloading Session
          </Button>
        </div>

        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 md:gap-6 xl:grid-cols-6">
          <div className="rounded-md shadow-lg border border-gray-200 bg-white p-2">
            <div className="rounded-2xl border-gray-200 bg-white p-5 ">
              <p className="text-gray-500 text-theme-sm dark:text-gray-400">
                Total Sessions
              </p>
              <div className="flex justify-between mt-3">
                <div className="">
                  <h4 className="text-2xl font-bold text-gray-800 dark:text-white/90">
                    {unloadingSessionLists.length}
                  </h4>
                </div>
              </div>
            </div>
          </div>

          <div className="rounded-md shadow-lg border border-green-300 bg-green-300 p-2">
            <div className="rounded-2xl bg-green-300 p-5 ">
              <p className="text-green-500 text-theme-sm dark:text-gray-400">
                Completed
              </p>
              <div className="flex justify-between mt-3">
                <div className="">
                  <h4 className="text-2xl font-bold text-green-800 dark:text-white/90">
0
                  </h4>
                </div>
              </div>
            </div>
          </div>

          <div className="rounded-md shadow-lg border border-gray-200 bg-white p-2">
            <div className="rounded-2xl  border-gray-200 bg-white p-5 ">
              <p className="text-gray-500 text-theme-sm dark:text-gray-400">
                Planned
              </p>
              <div className="flex justify-between mt-3">
                <div className="">
                  <h4 className="text-2xl font-bold text-gray-800 dark:text-white/90">
                    0
                  </h4>
                </div>
              </div>
            </div>
          </div>

          <div className="rounded-md shadow-lg border border-gray-200 bg-white p-2">
            <div className="rounded-2xl border-gray-200 bg-white p-5 ">
              <p className="text-gray-500 text-theme-sm dark:text-gray-400">
                In Progress
              </p>
              <div className="flex justify-between mt-3">
                <div className="">
                  <h4 className="text-2xl font-bold text-gray-800 dark:text-white/90">
                    {unloadingSessionLists.filter((x: any) => x.status == 0).length}
                  </h4>
                </div>
              </div>
            </div>
          </div>

          <div className="rounded-md shadow-lg border border-gray-200 bg-white p-2">
            <div className="rounded-2xl border-gray-200 bg-white p-5 ">
              <p className="text-gray-500 text-theme-sm dark:text-gray-400">
                Pallets Unloaded
              </p>
              <div className="flex justify-between mt-3">
                <div className="">
                  <h4 className="text-2xl font-bold text-gray-800 dark:text-white/90">
0
                  </h4>
                </div>
              </div>
            </div>
          </div>

          <div className="rounded-md shadow-lg border border-gray-200 bg-white p-2">
            <div className="rounded-2xl border-gray-200 bg-white p-5 ">
              <p className="text-gray-500 text-theme-sm dark:text-gray-400">
                Items Unloaded
              </p>
              <div className="flex justify-between mt-3">
                <div className="">
                  <h4 className="text-2xl font-bold text-gray-800 dark:text-white/90">
                    0
                  </h4>
                </div>
              </div>
            </div>
          </div>

      
        </div>

        <div className="">
          {unloadingSessionLists && (
            <UnloadingSessionTable
              isLoading={isLoading}
              unloadingSessionLists={unloadingSessionLists}
            />
          )}
        </div>
      </div>
      {isCreateOpen && (
        <UnloadingSessionCreateModal
          isCreateOpen={isCreateOpen}
          handleCloseModal={handleCloseModal}
        />
      )}
    </>
  )
}

export default UnloadingSessionPage