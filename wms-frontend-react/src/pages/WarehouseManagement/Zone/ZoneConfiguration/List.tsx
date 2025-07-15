import React from 'react'
import { Link } from 'react-router-dom'
import http from '../../../../lib/http'
import { styled } from '@mui/material/styles'
import { useAppDispatch } from '../../../../store/hook'
import { getZoneLocationContentData } from '../../../../store/features/masterData/LocationSlice'

import LinearProgress, {
  linearProgressClasses,
} from '@mui/material/LinearProgress'

import LocationPinIcon from '@mui/icons-material/LocationPin'
import DeleteIcon from '@mui/icons-material/Delete'

import { showConfirm } from '../../../../utils/alert'
import Swal from 'sweetalert2'

const BorderLinearProgress = styled(LinearProgress)(({ theme }) => ({
  height: 10,
  borderRadius: 5,
  [`&.${linearProgressClasses.colorPrimary}`]: {
    backgroundColor: theme.palette.grey[200],
    ...theme.applyStyles('dark', {
      backgroundColor: theme.palette.grey[800],
    }),
  },
  [`& .${linearProgressClasses.bar}`]: {
    borderRadius: 5,
    backgroundColor: '#1a90ff',
    ...theme.applyStyles('dark', {
      backgroundColor: '#308fe8',
    }),
  },
}))

interface Props {
  zoneLists: any
  isPageLoading:any
  handleReFetchZoneListsApi: () => void
}

const List: React.FC<Props> = ({ zoneLists, handleReFetchZoneListsApi, isPageLoading }) => {
  
  const dispatch = useAppDispatch()
  
  const handleDelete = async (id: any) => {
    const confirmed = await showConfirm(
      'Are you sure?',
      'This action cannot be undone.'
    )

    if (!confirmed) return

    try {
      const response = await http.deleteDataWithToken(`/zones/${id}`)
      console.log(response)
      if (response.status == true) {
        Swal.fire({
          title: 'Deleted!',
          text: 'Zone has been deleted.',
          icon: 'success',
        })
        handleReFetchZoneListsApi()
      } else {
        Swal.fire({
          title: 'Error!',
          text: 'Failed to delete item.',
          icon: 'error',
        })
      }
    } catch (error: any) {
      Swal.fire({
        title: 'Error!',
        text: error.message || 'Failed to delete item.',
        icon: 'error',
      })
    }
  }

  const handleZoneLocation = (zone_id:any) => {
    dispatch(getZoneLocationContentData(zone_id))
  }

  return (
    <>
      <div className="space-y-10">
        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 md:gap-6 xl:grid-cols-5">
          <div className="rounded-md shadow-lg border border-gray-200 bg-white p-2">
            <div className="rounded-2xl border-gray-200 bg-white p-5 ">
              <p className="text-gray-500 text-theme-sm dark:text-gray-400">
                Total Zones
              </p>
              <div className="flex items-end justify-between mt-3">
                <div className="">
                  <h4 className="text-2xl font-bold text-gray-800 dark:text-white/90">
                    0
                  </h4>
                </div>
                <div className="flex items-center gap-1">
                  <span className="inline-flex items-center px-2.5 py-0.5 justify-center gap-1 rounded-full font-medium text-sm bg-success-50 text-success-600">
                    <span className="text-md">Active</span>
                  </span>
                  {/* <span className="text-gray-500 text-theme-xs ">
                    Vs last month
                  </span> */}
                </div>
              </div>
            </div>
          </div>

          <div className="rounded-md shadow-lg border border-gray-200 bg-white p-2">
            <div className="rounded-2xl border-gray-200 bg-white p-5 ">
              <p className="text-gray-500 text-theme-sm dark:text-gray-400">
                Active Zones
              </p>
              <div className="flex items-end justify-between mt-3">
                <div className="">
                  <h4 className="text-2xl font-bold text-gray-800 dark:text-white/90">
                    4
                  </h4>
                </div>
                <div className="flex items-center gap-1">
                  <span className="inline-flex items-center px-2.5 py-0.5 justify-center gap-1 rounded-full font-medium text-sm bg-success-50 text-success-600">
                    <span className="text-xs">
                      <button className="rounded-2xl bg-blue-700 text-white p-2">
                        View All
                      </button>
                    </span>
                  </span>
                </div>
              </div>
            </div>
          </div>

          <div className="rounded-md shadow-lg border border-gray-200 bg-white p-2">
            <div className="rounded-2xl border-gray-200 bg-white p-5 ">
              <p className="text-gray-500 text-theme-sm dark:text-gray-400">
                Avg. Utilization
              </p>
              <div className="flex items-end justify-between mt-3">
                <div className="">
                  <h4 className="text-2xl font-bold text-gray-800 dark:text-white/90">
                    2
                  </h4>
                </div>
                <div className="flex items-center gap-1">
                  <span className="inline-flex items-center px-2.5 py-0.5 justify-center gap-1 rounded-full font-medium text-sm bg-success-50 text-yellow-600">
                    <span className="text-md">Alert</span>
                  </span>
                  {/* <span className="text-gray-500 text-theme-xs ">
                    Vs last month
                  </span> */}
                </div>
              </div>
            </div>
          </div>

          <div className="rounded-md shadow-lg border border-gray-200 bg-white p-2">
            <div className="rounded-2xl border-gray-200 bg-white p-5 ">
              <p className="text-gray-500 text-theme-sm dark:text-gray-400">
                High Utilization
              </p>
              <div className="flex items-end justify-between mt-3">
                <div className="">
                  <h4 className="text-2xl font-bold text-gray-800 dark:text-white/90">
                    0
                  </h4>
                </div>
                <div className="flex items-center gap-1">
                  <span className="inline-flex items-center px-2.5 py-0.5 justify-center gap-1 rounded-full font-medium text-sm bg-success-50 text-success-600">
                    <span className="text-md">Good</span>
                  </span>
                  {/* <span className="text-gray-500 text-theme-xs ">
                    Vs last month
                  </span> */}
                </div>
              </div>
            </div>
          </div>

          <div className="rounded-md shadow-lg border border-gray-200 bg-white p-2">
            <div className="rounded-2xl border-gray-200 bg-white p-5 ">
              <p className="text-gray-500 text-theme-sm dark:text-gray-400">
                Low Utilization
              </p>
              <div className="flex items-end justify-between mt-3">
                <div className="">
                  <h4 className="text-2xl font-bold text-gray-800 dark:text-white/90">
                    0
                  </h4>
                </div>
                <div className="flex items-center gap-1">
                  <span className="inline-flex items-center px-2.5 py-0.5 justify-center gap-1 rounded-full font-medium text-sm bg-success-50 text-success-600">
                    <span className="text-md">Good</span>
                  </span>
                  {/* <span className="text-gray-500 text-theme-xs ">
                    Vs last month
                  </span> */}
                </div>
              </div>
            </div>
          </div>
        </div>

        <div className="grid grid-cols-1 lg:grid-cols-3 gap-2">
          {zoneLists &&
            zoneLists.map((zone: any, index: number) => (
              <div
                key={index}
                className="rounded-lg shadow-lg border-gray-200 bg-white p-5"
              >
                <div className="flex justify-between items-center">
                  <h1 className="text-base font-semibold">{zone.zone_name}</h1>
                  {zone.status == 0 ? (
                    <span className="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-700">
                      In Active
                    </span>
                  ) : (
                    <span className="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-700">
                      Active
                    </span>
                  )}
                </div>
                <div className="flex flex-col my-3">
                  <h2 className="text-base text-gray-500">{zone.zone_code}</h2>
                  <p>{zone.description || ''}</p>
                  <div className="my-10">
                    <span className='text-sm text-gray-400'>{zone?.utilization || 0}%</span>
                    <BorderLinearProgress variant="determinate" value={zone.utilization} />
                  </div>
                  <div className="flex flex-row justify-between items-center">
                    <Link to={`/warehouse-management/zone-location/${zone.id}`}
                      className="flex justify-center items-center"
                      onClick={() => handleZoneLocation(zone.id)}
                    >
                      <LocationPinIcon
                        className="text-blue-600 me-1"
                        fontSize="small"
                      />
                      Location
                    </Link>
                    <button
                      className="flex justify-center items-center"
                      onClick={() => {
                        handleDelete(zone.id)
                      }}
                    >
                      <DeleteIcon
                        className="text-red-600 me-1"
                        fontSize="small"
                      />
                      Delete
                    </button>
                  </div>
                </div>
              </div>
            ))}
        </div>
      </div>
    
    </>
  )
}

export default List
