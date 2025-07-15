import React,{ useState, useEffect } from 'react'
import Button from '../../../components/ui/button/Button'

import { RootState } from '../../../store/store'
import { useAppDispatch, useAppSelector } from '../../../store/hook'

import ReceivingExceptionTable from './components/Table'
import ReceivingExceptionCreateModal from './components/CreateModal'

import { fetchReceivingExceptionLists } from './services/receivingExceptionApi'

function ReceivingExceptionPage() {

    const [isCreateOpen, setIsCreateOpen] = useState<boolean>(false)

    const isLoading = useAppSelector((state: RootState) => state.receivingException?.loading)
    const error = useAppSelector((state: RootState) => state.receivingException?.error)
    const dispatch = useAppDispatch()
    const receivingExceptionLists = useAppSelector((state: RootState) => state.receivingException?.content || [])
    const isFetched = useAppSelector((state: RootState) => state.receivingException?.isFetched)

    useEffect(() => {
        if(!isFetched){
            dispatch(fetchReceivingExceptionLists())
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
    if (error) return <p>Failed to load Receiving Exception.</p>
  return (
    <>
      <div className="space-y-10">
        <div className="flex justify-between items-center">
          <h1 className="text-xl font-semibold">
            Receiving Exception Lists
          </h1>
          <Button
            variant="primary"
            size="sm"
            onClick={() => setIsCreateOpen(true)}
          >
            Add Exception
          </Button>
        </div>

        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 md:gap-6 xl:grid-cols-6">
          <div className="rounded-md shadow-lg border border-gray-200 bg-white p-2">
            <div className="rounded-2xl border-gray-200 bg-white p-5 ">
              <p className="text-gray-500 text-theme-sm dark:text-gray-400">
                Total Exceptions
              </p>
              <div className="flex justify-between mt-3">
                <div className="">
                  <h4 className="text-2xl font-bold text-gray-800 dark:text-white/90">
                    {receivingExceptionLists.length}
                  </h4>
                </div>
              </div>
            </div>
          </div>

          <div className="rounded-md shadow-lg border border-gray-300 bg-white p-2">
            <div className="rounded-2xl border-gray-200 bg-white p-5 ">
              <p className="text-gray-500 text-theme-sm dark:text-gray-400">
                Open
              </p>
              <div className="flex justify-between mt-3">
                <div className="">
                  <h4 className="text-2xl font-bold text-gray-800 dark:text-white/90">
                    {receivingExceptionLists.filter((x: any) => x.status == 3).length}
                  </h4>
                </div>
              </div>
            </div>
          </div>

          <div className="rounded-md shadow-lg border border-gray-200 bg-white p-2">
            <div className="rounded-2xl  border-gray-200 bg-white p-5 ">
              <p className="text-gray-500 text-theme-sm dark:text-gray-400">
                In Progress
              </p>
              <div className="flex justify-between mt-3">
                <div className="">
                  <h4 className="text-2xl font-bold text-gray-800 dark:text-white/90">
                    {receivingExceptionLists.filter((x: any) => x.status == 2).length}
                  </h4>
                </div>
              </div>
            </div>
          </div>

          <div className="rounded-md shadow-lg border border-gray-200 bg-white p-2">
            <div className="rounded-2xl border-gray-200 bg-white p-5 ">
              <p className="text-gray-500 text-theme-sm dark:text-gray-400">
                Resolved
              </p>
              <div className="flex justify-between mt-3">
                <div className="">
                  <h4 className="text-2xl font-bold text-gray-800 dark:text-white/90">
                    {receivingExceptionLists.filter((x: any) => x.status == 0).length}
                  </h4>
                </div>
              </div>
            </div>
          </div>

          <div className="rounded-md shadow-lg border border-gray-200 bg-white p-2">
            <div className="rounded-2xl border-gray-200 bg-white p-5 ">
              <p className="text-gray-500 text-theme-sm dark:text-gray-400">
                Critical
              </p>
              <div className="flex justify-between mt-3">
                <div className="">
                  <h4 className="text-2xl font-bold text-gray-800 dark:text-white/90">
                    {receivingExceptionLists.filter((x: any) => x.status == 1).length}
                  </h4>
                </div>
              </div>
            </div>
          </div>

          <div className="rounded-md shadow-lg border border-gray-200 bg-white p-2">
            <div className="rounded-2xl border-gray-200 bg-white p-5 ">
              <p className="text-gray-500 text-theme-sm dark:text-gray-400">
                High Priority
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
          {receivingExceptionLists && (
            <ReceivingExceptionTable
              isLoading={isLoading}
              receivingExceptionLists={receivingExceptionLists}
            />
          )}
        </div>
      </div>
      {isCreateOpen && (
        <ReceivingExceptionCreateModal
          isCreateOpen={isCreateOpen}
          handleCloseModal={handleCloseModal}
        />
      )}
    </>
  )
}

export default ReceivingExceptionPage