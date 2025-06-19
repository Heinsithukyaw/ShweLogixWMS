import React, { useState, useEffect} from 'react'
import Button from '../../../components/ui/button/Button'
import { RootState } from '../../../store/store'
import { useAppDispatch, useAppSelector } from '../../../store/hook'
import GrnTable from './components/Grn/Table'
import GrnCreateForm from './components/Grn/CreateForm'
import GrnUpdateForm from './components/Grn/UpdateForm'
import BarcodeReaderModal from './components/barcodeReaderModal'
import { fetchGrnLists } from './services/grnApi'

import Tabs from '@mui/material/Tabs'
import Tab from '@mui/material/Tab'
import Box from '@mui/material/Box'
import { BiBarcodeReader } from 'react-icons/bi'


interface TabPanelProps {
  children?: React.ReactNode
  index: number
  value: number
}

function CustomTabPanel(props: TabPanelProps) {
  const { children, value, index, ...other } = props

  return (
    <div
      role="tabpanel"
      hidden={value !== index}
      id={`simple-tabpanel-${index}`}
      aria-labelledby={`simple-tab-${index}`}
      {...other}
    >
      {value === index && <Box sx={{ p: 3 }}>{children}</Box>}
    </div>
  )
}

function a11yProps(index: number) {
  return {
    id: `simple-tab-${index}`,
    'aria-controls': `simple-tabpanel-${index}`,
  }
}




function GoodReceivedNotePage() {
    const [value, setValue] = React.useState(0)
    const isLoading = useAppSelector((state: RootState) => state.grn?.loading)
    const error = useAppSelector((state: RootState) => state.grn?.error)
    const dispatch = useAppDispatch()
    const grnLists = useAppSelector((state: RootState) => state.grn?.content || [])
    const isFetched = useAppSelector((state: RootState) => state.grn?.isFetched)
    const [isCreateOpen, setIsCreateOpen] = useState<boolean>(false)
    const [isUpdateOpen, setIsUpdateOpen] = useState<boolean>(false)
    const [isBarcodeModalOpen, setIsBarcodeModalOpen] = useState<boolean>(false)

    useEffect(() => {
        if(!isFetched){
            dispatch(fetchGrnLists())
        }
    }, [dispatch,isFetched])

    const handleCloseModal = () => {
      setIsCreateOpen(false)
      setIsBarcodeModalOpen(false)
    }

    const handleTabChange = (event: React.SyntheticEvent, newValue: number) => {
      setValue(newValue)
      setIsCreateOpen(true)
      setIsUpdateOpen(false)
    }

    const handleCreate = () => {
        setValue(1)
        setIsCreateOpen(true)
        setIsUpdateOpen(false)
    }

    const handleUpdateOpen = () => {
      setValue(1)
      setIsCreateOpen(false)
      setIsUpdateOpen(true)
      console.log(isCreateOpen)
    }

    const handleShowLists = () => {
        setValue(0)
    }

    const handleBarcodeOpen = () => {
      setIsBarcodeModalOpen(true)
    }

    if (isLoading)
      return (
        <div className="flex justify-center items-center space-x-2">
          <div className="w-5 h-5 border-2 border-t-blue-500 border-blue-200 rounded-full animate-spin"></div>
          <span className="text-sm text-gray-500">Loading...</span>
        </div>
      )
    if (error) return <p>Failed to load Good Received Notes.</p>

  return (
    <>
      <div className="space-y-10">
        <div className="flex justify-between items-center">
          <h1 className="text-xl font-semibold">Good Received Note Lists</h1>
          <div className="flex justify-between items-center gap-3">
            <button
              className="bg-white border-2 rounded-lg p-1 shadow-lg border-slate-500"
              onClick={handleBarcodeOpen}
            >
              <BiBarcodeReader className="w-6 h-6 sm:w-8 sm:h-8 lg:w-7 lg:h-auto" />
            </button>
            <Button variant="primary" size="sm" onClick={handleCreate}>
              Add GRN
            </Button>
          </div>
        </div>

        <div className="">
          <Box sx={{ width: '100%' }}>
            <Box
              sx={{
                borderBottom: 1,
                borderColor: 'divider',
                fontWeight: 'bold',
              }}
            >
              <Tabs
                value={value}
                onChange={handleTabChange}
                aria-label="good received note"
              >
                <Tab
                  sx={{ fontWeight: 'bold' }}
                  label="Lists"
                  {...a11yProps(0)}
                />
                <Tab
                  sx={{ fontWeight: 'bold' }}
                  label="Detail"
                  {...a11yProps(1)}
                />
              </Tabs>
            </Box>
            <CustomTabPanel value={value} index={0}>
              <div className="">
                {grnLists && (
                  <GrnTable
                    isLoading={isLoading}
                    grnLists={grnLists}
                    handleShowLists={handleShowLists}
                    handleShowDetail={() => setValue(1)}
                    handleUpdateOpen={handleUpdateOpen}
                  />
                )}
              </div>
            </CustomTabPanel>
            <CustomTabPanel value={value} index={1}>
              {isCreateOpen && (
                <GrnCreateForm
                  isCreateOpen={isCreateOpen}
                  handleCloseModal={handleCloseModal}
                  handleShowLists={handleShowLists}
                />
              )}
              {isUpdateOpen && (
                <GrnUpdateForm
                  isUpdateOpen={isUpdateOpen}
                  handleShowLists={handleShowLists}
                />
              )}
            </CustomTabPanel>
          </Box>
        </div>
      </div>
      {isBarcodeModalOpen && (
        <BarcodeReaderModal handleCloseModal={handleCloseModal} isBarcodeModalOpen={isBarcodeModalOpen}/>
      )}
    </>
  )
}

export default GoodReceivedNotePage