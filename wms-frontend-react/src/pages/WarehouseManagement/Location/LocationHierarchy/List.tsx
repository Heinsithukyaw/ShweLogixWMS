import React, {useState, useEffect} from 'react'
import http from '../../../../lib/http'

import Accordion from '@mui/material/Accordion'
import AccordionSummary from '@mui/material/AccordionSummary'
import AccordionDetails from '@mui/material/AccordionDetails'
import Typography from '@mui/material/Typography'
import ExpandMoreIcon from '@mui/icons-material/ExpandMore'
import List from '@mui/material/List'

import CategoryIcon from '@mui/icons-material/Category'
import CheckCircleOutlineIcon from '@mui/icons-material/CheckCircleOutline'
import AdjustIcon from '@mui/icons-material/Adjust'
import CircleIcon from '@mui/icons-material/Circle'
import AccountTreeIcon from '@mui/icons-material/AccountTree'

interface Warehouse {
  id: string
  warehouse_code: string
  warehouse_name: string
}

interface Area {
  id: string
  area_code: string
  area_name: string
}

interface Zone {
  id: string
  zone_code: string
  zone_name: string
}

interface Location {
  id: string
  location_code: string
  location_name: string
}


const LocationHierarchy: React.FC = () => {

  const [warehouseLists, setWarehouseLists] = useState<Warehouse[]>([])
  const [areaLists, setAreaLists] = useState<Area[]>([])
  const [zoneLists, setZoneLists] = useState<Zone[]>([])
  const [locationLists, setLocationLists] = useState<Location[]>([])

  const [isPageLoading, setIsPageLoading] = useState(false)


  useEffect(() => {
    fetchWarehouseLists()
    fetchAreaLists()
    fetchZoneLists()
    fetchLocationLists()
  },[])

  const fetchWarehouseLists = async () => {
    try {
      setIsPageLoading(true)
      const res = await http.fetchDataWithToken('warehouses')
      console.log(res.data)
      const warehouses = res.data?.data?.filter((x:any) => x.parent_id == null)
      setWarehouseLists(warehouses || [])
    } catch (err) {
      setIsPageLoading(false)
      console.error('Failed to fetch Warehouse lists:', err)
    } finally {
      setIsPageLoading(false)
    }
  }

  const fetchAreaLists = async () => {
    try {
      setIsPageLoading(true)
      const res = await http.fetchDataWithToken('areas')
      console.log(res.data)
      
      setAreaLists(res.data?.data || [])
    } catch (err) {
      setIsPageLoading(false)
      console.error('Failed to fetch Area lists:', err)
    } finally {
      setIsPageLoading(false)
    }
  }

  const fetchZoneLists = async () => {
    try {
      setIsPageLoading(true)
      const res = await http.fetchDataWithToken('zones')
      console.log(res.data)
      setZoneLists(res?.data?.data || [])
    } catch (err) {
      setIsPageLoading(false)
      console.error('Failed to fetch Zone lists:', err)
    } finally {
      setIsPageLoading(false)
    }
  }

  const fetchLocationLists = async () => {
    try {
      setIsPageLoading(true)
      const res = await http.fetchDataWithToken('locations')
      console.log(res.data)
      setLocationLists(res?.data?.data || [])
    } catch (err) {
      setIsPageLoading(false)
      console.error('Failed to fetch Location lists:', err)
    } finally {
      setIsPageLoading(false)
    }
  }

  return (
    <>
      <div className="space-y-10">
        <div className="flex justify-between items-center">
          <h1 className="text-xl font-semibold">Location Hierarchy Lists</h1>
        </div>

        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 md:gap-6 xl:grid-cols-3">
          <div className="rounded-md shadow-lg border border-gray-200 bg-white p-2">
            <div className="rounded-2xl border border-gray-200 bg-white p-5 ">
              <div className="text-gray-500 text-theme-sm dark:text-gray-400 flex items-center justify-start font-semibold">
                <div className="bg-blue-800 rounded-full text-white flex justify-center items-center w-[3em] h-[3em] me-2">
                  <CategoryIcon className="" />
                </div>
                Total Location Hierarchies
              </div>
              <div className="flex items-end justify-between mt-3">
                <div className="">
                  <h4 className="text-2xl font-bold text-gray-800 dark:text-white/90">
                    0
                  </h4>
                </div>
              </div>
            </div>
          </div>

          <div className="rounded-md shadow-lg border border-gray-200 bg-white p-2">
            <div className="rounded-2xl border border-gray-200 bg-white p-5 ">
              <div className="text-gray-500 text-theme-sm dark:text-gray-400 flex items-center justify-start font-semibold">
                <div className="bg-green-600 rounded-full text-white flex justify-center items-center w-[3em] h-[3em] me-2">
                  <CheckCircleOutlineIcon className="" />
                </div>
                Active Location Hierarchies
              </div>
              <div className="flex items-end justify-between mt-3">
                <div className="">
                  <h4 className="text-2xl font-bold text-gray-800 dark:text-white/90">
                    0
                  </h4>
                </div>
              </div>
            </div>
          </div>

          <div className="rounded-md shadow-lg border border-gray-200 bg-white p-2">
            <div className="rounded-2xl border border-gray-200 bg-white p-5 ">
              <div className="text-gray-500 text-theme-sm dark:text-gray-400 flex items-center justify-start font-semibold">
                <div className="bg-blue-400 rounded-full text-white flex justify-center items-center w-[3em] h-[3em] me-2">
                  <AccountTreeIcon className="" />
                </div>
                Total Level Location Hierarchies
              </div>
              <div className="flex items-end justify-between mt-3">
                <div className="">
                  <h4 className="text-2xl font-bold text-gray-800 dark:text-white/90">
                    0
                  </h4>
                </div>
              </div>
            </div>
          </div>
        </div>
        {isPageLoading ? (
          <div className="flex justify-center items-center space-x-2">
            <div className="w-5 h-5 border-2 border-t-blue-500 border-blue-200 rounded-full animate-spin"></div>
            <span className="text-sm text-gray-500">Loading...</span>
          </div>
        ) : (
          <div>
            {warehouseLists.map((warehouse: any) => (
              <Accordion>
                <AccordionSummary
                  expandIcon={<ExpandMoreIcon />}
                  aria-controls="panel1-content"
                  id="panel1-header"
                >
                  <Typography component="span">
                    <CategoryIcon className="me-2" />
                    {warehouse.warehouse_code} - {warehouse.warehouse_name}
                    <span className="inline-block ms-1 rounded-full bg-green-100 px-3 py-1 text-sm font-semibold text-green-800">
                      Warehouse
                    </span>
                  </Typography>
                </AccordionSummary>
                <AccordionDetails>
                  {/* Area */}
                  {areaLists
                    .filter((y: any) => y.warehouse_id == warehouse.id)
                    .map((area: any) => (
                      <Accordion>
                        <AccordionSummary
                          expandIcon={<ExpandMoreIcon />}
                          aria-controls="panel1-content"
                          id="panel1-header"
                        >
                          <Typography component="span">
                            <AdjustIcon className="me-2" />
                            {area.area_code} - {area.area_name}
                            <span className="inline-block ms-1 rounded-full bg-green-100 px-3 py-1 text-sm font-semibold text-green-800">
                              Area
                            </span>
                          </Typography>
                        </AccordionSummary>
                        <AccordionDetails>
                          {/* Zone */}
                          {zoneLists
                            .filter((b: any) => b.area_id == area.id)
                            .map((zone: any) => (
                              <Accordion>
                                <AccordionSummary
                                  expandIcon={<ExpandMoreIcon />}
                                  aria-controls="panel1-content"
                                  id="panel1-header"
                                >
                                  <Typography component="span">
                                    <CircleIcon className="me-2" />
                                    {zone.zone_code} - {zone.zone_name}
                                    <span className="inline-block ms-1 rounded-full bg-green-100 px-3 py-1 text-sm font-semibold text-green-800">
                                      Zone
                                    </span>
                                  </Typography>
                                </AccordionSummary>
                                <AccordionDetails>
                                  {/* Location */}

                                  <List>
                                    {locationLists
                                      .filter((p: any) => p.zone_id == zone.id)
                                      .map((location: any, index: number) => (
                                        <div
                                          key={location.id}
                                          className="ms-10"
                                        >
                                          {index + 1}.
                                          <span className="ms-2">
                                            {location.location_code} -{' '}
                                            {location.location_name}
                                          </span>
                                          <span className="inline-block ms-1 rounded-full bg-green-100 px-3 py-1 text-sm font-semibold text-green-800">
                                            Location
                                          </span>
                                        </div>
                                      ))}
                                  </List>
                                </AccordionDetails>
                              </Accordion>
                            ))}
                        </AccordionDetails>
                      </Accordion>
                    ))}
                </AccordionDetails>
              </Accordion>
            ))}
          </div>
        )}
      </div>
    </>
  )
}

export default LocationHierarchy
