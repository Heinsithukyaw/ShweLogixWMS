import React,{ useState, useEffect } from 'react'
import Button from '../../../components/ui/button/Button'
import { FaShoppingCart } from 'react-icons/fa'
import { FaClipboardList } from 'react-icons/fa'
import { AiFillDollarCircle } from 'react-icons/ai'

import { RootState } from '../../../store/store'
import { useAppDispatch, useAppSelector } from '../../../store/hook'

import SalesOrderTable from './components/SalesOrderTable'
import SalesOrderCreateModal from './components/SalesOrderCreateModal'

import { fetchSalesOrderLists } from './services/salesOrderApi'

function SalesOrderPage() {

    const [isCreateOpen, setIsCreateOpen] = useState<boolean>(false)

    const isLoading = useAppSelector((state: RootState) => state.salesOrder?.loading)
    const error = useAppSelector((state: RootState) => state.salesOrder?.error)
    const dispatch = useAppDispatch()
    const salesOrderLists = useAppSelector((state: RootState) => state.salesOrder?.content || [])
    const isFetched = useAppSelector((state: RootState) => state.salesOrder?.isFetched)

    useEffect(() => {
        if(!isFetched){
            dispatch(fetchSalesOrderLists())
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
    if (error) return <p>Failed to load Sales Orders.</p>
  return (
    <>
      <div className="space-y-10">
        <div className="flex justify-between items-center">
          <h1 className="text-xl font-semibold">
            Sales Order Management
          </h1>
          <Button
            variant="primary"
            size="sm"
            onClick={() => setIsCreateOpen(true)}
          >
            Create Order
          </Button>
        </div>

        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 md:gap-6 xl:grid-cols-3">
          <div className="rounded-md shadow-lg border border-gray-200 bg-white p-2">
            <div className="rounded-2xl border border-gray-200 bg-white p-5 ">
              <p className="text-gray-500 text-theme-sm dark:text-gray-400">
                Pending Orders
              </p>
              <div className="flex justify-between mt-3">
                <div className="">
                  <h4 className="text-2xl font-bold text-gray-800 dark:text-white/90">
                    0
                  </h4>
                  <p className="text-gray-500 text-theme-sm dark:text-gray-400">
                    Awaiting processing
                  </p>
                </div>
                <div className="flex items-center gap-1">
                  <span className="">
                    <FaShoppingCart style={{ fontSize: 40, color: '#1E2F55' }} />
                  </span>
                </div>
              </div>
            </div>
          </div>

          <div className="rounded-md shadow-lg border border-gray-200 bg-white p-2">
            <div className="rounded-2xl border border-gray-200 bg-white p-5 ">
              <p className="text-gray-500 text-theme-sm dark:text-gray-400">
                Active Pick Waves
              </p>
              <div className="flex justify-between mt-3">
                <div className="">
                  <h4 className="text-2xl font-bold text-gray-800 dark:text-white/90">
                    0
                  </h4>
                  <p className="text-gray-500 text-theme-sm dark:text-gray-400">
                    In progress
                  </p>
                </div>
                <div className="flex items-center gap-1">
                  <span className="">
                    <span className="text-xs">
                      <FaClipboardList
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
                Total Revenue
              </p>
              <div className="flex justify-between mt-3">
                <div className="">
                  <h4 className="text-2xl font-bold text-gray-800 dark:text-white/90">
                    $0
                  </h4>
                  <p className="text-gray-500 text-theme-sm dark:text-gray-400">
                    This month
                  </p>
                </div>
                <div className="flex items-center gap-1">
                  <span className="">
                    <AiFillDollarCircle
                      style={{ fontSize: 40, color: '#4CAF50' }}
                    />
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div className="">
          {salesOrderLists && <SalesOrderTable isLoading={isLoading} salesOrderLists={salesOrderLists}/>}
        </div>
      </div>
      {isCreateOpen && (
        <SalesOrderCreateModal
          isCreateOpen={isCreateOpen}
          handleCloseModal={handleCloseModal}
        />
      )}
      
    </>
  )
}

export default SalesOrderPage 