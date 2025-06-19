import React,{ useState, useEffect } from 'react'
import Button from '../../../components/ui/button/Button'

import Tabs from '@mui/material/Tabs'
import Tab from '@mui/material/Tab'
import Box from '@mui/material/Box'

import { RootState } from '../../../store/store'
import { useAppDispatch, useAppSelector } from '../../../store/hook'

import LocationTable from './components/Table'
import LocationCreateModal from './components/CreateModal'

import { fetchMainLocationLists } from './services/locationApi'

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

function LocationPage() {

    const [isCreateOpen, setIsCreateOpen] = useState<boolean>(false)
    const [value, setValue] = useState(0)
    const isLoading = useAppSelector((state: RootState) => state.mLocation?.loading)
    const error = useAppSelector((state: RootState) => state.mLocation?.error)
    const dispatch = useAppDispatch()
    const locationLists = useAppSelector((state: RootState) => state.mLocation?.content || [])
    const isFetched = useAppSelector((state: RootState) => state.mLocation?.isFetched)

    useEffect(() => {
        if(!isFetched){
            dispatch(fetchMainLocationLists())
        }
    }, [dispatch,isFetched])

    const handleCloseModal = () => {
      setIsCreateOpen(false)
    }

    const handleTabChange = (event: React.SyntheticEvent, newValue: number) => {
        setValue(newValue)
    }

    if (isLoading) return (
      <div className="flex justify-center items-center space-x-2">
        <div className="w-5 h-5 border-2 border-t-blue-500 border-blue-200 rounded-full animate-spin"></div>
        <span className="text-sm text-gray-500">Loading...</span>
      </div>
    )
    if (error) return <p>Failed to load Location Lists.</p>
  return (
    <>
      <div className="space-y-10">
        <div className="flex justify-between items-center">
          <h1 className="text-xl font-semibold">Location Lists</h1>
          <Button
            variant="primary"
            size="sm"
            onClick={() => setIsCreateOpen(true)}
          >
            Add Location
          </Button>
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
                aria-label="basic tabs example"
              >
                <Tab
                  sx={{ fontWeight: 'bold' }}
                  label="Location"
                  {...a11yProps(0)}
                />
                <Tab
                  sx={{ fontWeight: 'bold' }}
                  label="Heatmap View"
                  {...a11yProps(1)}
                />
              </Tabs>
            </Box>
            <CustomTabPanel value={value} index={0}>
              <div className="">
                {locationLists && (
                  <LocationTable
                    isLoading={isLoading}
                    locationLists={locationLists}
                  />
                )}
              </div>
            </CustomTabPanel>
            <CustomTabPanel value={value} index={1}>
              Coming soon...
            </CustomTabPanel>
          </Box>
        </div>
      </div>
      {isCreateOpen && (
        <LocationCreateModal
          isCreateOpen={isCreateOpen}
          handleCloseModal={handleCloseModal}
        />
      )}
    </>
  )
}

export default LocationPage