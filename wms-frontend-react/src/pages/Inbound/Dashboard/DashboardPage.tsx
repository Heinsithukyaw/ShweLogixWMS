import React,{ useState, useEffect } from 'react'
import Button from '../../../components/ui/button/Button'
import { Link } from 'react-router-dom'

import { RootState } from '../../../store/store'
import { useAppSelector } from '../../../store/hook'

import Timeline from '@mui/lab/Timeline'
import TimelineItem, { timelineItemClasses } from '@mui/lab/TimelineItem'
import TimelineSeparator from '@mui/lab/TimelineSeparator'
import TimelineConnector from '@mui/lab/TimelineConnector'
import TimelineContent from '@mui/lab/TimelineContent'
import TimelineDot from '@mui/lab/TimelineDot'

import AssignmentIcon from '@mui/icons-material/Assignment'
import InventoryIcon from '@mui/icons-material/Inventory'
import MoveToInboxIcon from '@mui/icons-material/MoveToInbox'
import ManageAccountsIcon from '@mui/icons-material/ManageAccounts'
import TrendingUpIcon from '@mui/icons-material/TrendingUp'
import TrendingDownIcon from '@mui/icons-material/TrendingDown'
import LocalShippingIcon from '@mui/icons-material/LocalShipping'
function InboundDashboardPage() {

    const asnTasks = useAppSelector((state: RootState) => state.asn?.content)
    const inboundTasks = useAppSelector((state: RootState) => state.shipment?.content)
    const unloadingTasks = useAppSelector((state: RootState) => state.unloadingSession?.content)

    const isLoading = useAppSelector((state: RootState) => state.asnDetail?.loading)
    const error = useAppSelector((state: RootState) => state.asnDetail?.error)


    if (isLoading) return (
      <div className="flex justify-center items-center space-x-2">
        <div className="w-5 h-5 border-2 border-t-blue-500 border-blue-200 rounded-full animate-spin"></div>
        <span className="text-sm text-gray-500">Loading...</span>
      </div>
    )
    if (error) return <p>Failed to load Dashboard Pages.</p>
  return (
    <>
      <div className="space-y-10">
        <div className="flex justify-between items-center">
          <h1 className="text-xl font-semibold">
            Inbound Operation - Dashboard
          </h1>
        </div>
        <div className="grid grid-cols-1  gap-2 md:grid-cols-2 xl:grid-cols-4">
          <div className="rounded-md shadow-lg border border-gray-200 bg-white">
            <div className="divide-y divide-gray-200">
              <div className="flex items-center gap-3 px-5 py-4">
                <AssignmentIcon className="text-blue-600" fontSize="large" />
                <h1 className="text-lg font-semibold text-gray-800">
                  1. Planning & Preparation
                </h1>
              </div>

              <div className="px-5 py-4">
                <div className="flex justify-start">
                  <Timeline
                    sx={{
                      [`& .${timelineItemClasses.root}:before`]: {
                        flex: 0,
                        padding: 0,
                      },
                    }}
                  >
                    <TimelineItem>
                      <TimelineSeparator>
                        <TimelineDot />
                        <TimelineConnector />
                      </TimelineSeparator>
                      <TimelineContent>
                        <Link
                          to="/inbound-operation/asn"
                          className="text-gray-600 hover:underline"
                        >
                          <div className="flex flex-col">
                            <h1 className="text-base font-semibold text-gray-800">
                              ASN Headers
                            </h1>
                            <p className="text-sm text-gray-600">
                              Starting Point for advance shipment notices
                            </p>
                          </div>
                        </Link>
                      </TimelineContent>
                    </TimelineItem>
                    <TimelineItem>
                      <TimelineSeparator>
                        <TimelineDot />
                        <TimelineConnector />
                      </TimelineSeparator>
                      <TimelineContent>
                        <Link
                          to="/inbound-operation/asn-detail"
                          className="text-gray-600 hover:underline"
                        >
                          <div className="flex flex-col">
                            <h1 className="text-base font-semibold text-gray-800">
                              ASN Details
                            </h1>
                            <p className="text-sm text-gray-600">
                              Line Items in the advance shipment notices
                            </p>
                          </div>
                        </Link>
                      </TimelineContent>
                    </TimelineItem>
                    <TimelineItem>
                      <TimelineSeparator>
                        <TimelineDot />
                        <TimelineConnector />
                      </TimelineSeparator>
                      <TimelineContent>
                        <Link
                          to="/inbound-operation/inbound-shipment"
                          className="text-gray-600 hover:underline"
                        >
                          <div className="flex flex-col">
                            <h1 className="text-base font-semibold text-gray-800">
                              Inbound Shipments
                            </h1>
                            <p className="text-sm text-gray-600">
                              Master record for physical good movement
                            </p>
                          </div>
                        </Link>
                      </TimelineContent>
                    </TimelineItem>
                    <TimelineItem>
                      <TimelineSeparator>
                        <TimelineDot />
                        <TimelineConnector />
                      </TimelineSeparator>
                      <TimelineContent>
                        <Link
                          to="/inbound-operation/inbound-shipment-detail"
                          className="text-gray-600 hover:underline"
                        >
                          <div className="flex flex-col">
                            <h1 className="text-base font-semibold text-gray-800">
                              Inbound Details
                            </h1>
                            <p className="text-sm text-gray-600">
                              Line Items in the inbound shipments
                            </p>
                          </div>
                        </Link>
                      </TimelineContent>
                    </TimelineItem>
                    <TimelineItem>
                      <TimelineSeparator>
                        <TimelineDot />
                      </TimelineSeparator>
                      <TimelineContent>
                        <Link
                          to="/inbound-operation/receiving-appointment"
                          className="text-gray-600 hover:underline"
                        >
                          <div className="flex flex-col">
                            <h1 className="text-base font-semibold text-gray-800">
                              Appointments
                            </h1>
                            <p className="text-sm text-gray-600">
                              Schedule arrivals and dock assignments
                            </p>
                          </div>
                        </Link>
                      </TimelineContent>
                    </TimelineItem>
                  </Timeline>
                </div>
              </div>
            </div>
          </div>

          <div className="rounded-md shadow-lg border border-gray-200 bg-white">
            <div className="divide-y divide-gray-200">
              <div className="flex items-center gap-3 px-5 py-4">
                <InventoryIcon className="text-green-600" fontSize="large" />
                <h1 className="text-lg font-semibold text-gray-800">
                  2. Physical Receiving
                </h1>
              </div>

              <div className="px-5 py-4">
                <div className="flex justify-start">
                  <Timeline
                    sx={{
                      [`& .${timelineItemClasses.root}:before`]: {
                        flex: 0,
                        padding: 0,
                      },
                    }}
                  >
                    <TimelineItem>
                      <TimelineSeparator>
                        <TimelineDot />
                        <TimelineConnector />
                      </TimelineSeparator>
                      <TimelineContent>
                        <Link
                          to="/inbound-operation/receiving-dock"
                          className="text-gray-600 hover:underline"
                        >
                          <div className="flex flex-col">
                            <h1 className="text-base font-semibold text-gray-800">
                              Receiving Docks
                            </h1>
                            <p className="text-sm text-gray-600">
                              Assign Physical Locations for unloading
                            </p>
                          </div>
                        </Link>
                      </TimelineContent>
                    </TimelineItem>
                    <TimelineItem>
                      <TimelineSeparator>
                        <TimelineDot />
                        <TimelineConnector />
                      </TimelineSeparator>
                      <TimelineContent>
                        <Link
                          to="/inbound-operation/unloading-session"
                          className="text-gray-600 hover:underline"
                        >
                          <div className="flex flex-col">
                            <h1 className="text-base font-semibold text-gray-800">
                              Unloading Sessions
                            </h1>
                            <p className="text-sm text-gray-600">
                              Track Unloading process and time
                            </p>
                          </div>
                        </Link>
                      </TimelineContent>
                    </TimelineItem>
                    <TimelineItem>
                      <TimelineSeparator>
                        <TimelineDot />
                        <TimelineConnector />
                      </TimelineSeparator>
                      <TimelineContent>
                        <Link
                          to="/inbound-operation/quality-inspection"
                          className="text-gray-600 hover:underline"
                        >
                          <div className="flex flex-col">
                            <h1 className="text-base font-semibold text-gray-800">
                              Quality Inspections
                            </h1>
                            <p className="text-sm text-gray-600">
                              Verify product quality standards
                            </p>
                          </div>
                        </Link>
                      </TimelineContent>
                    </TimelineItem>
                    <TimelineItem>
                      <TimelineSeparator>
                        <TimelineDot />
                        <TimelineConnector />
                      </TimelineSeparator>
                      <TimelineContent>
                        <Link
                          to="/inbound-operation/good-received-note"
                          className="text-gray-600 hover:underline"
                        >
                          <div className="flex flex-col">
                            <h1 className="text-base font-semibold text-gray-800">
                              Good Receive Note
                            </h1>
                            <p className="text-sm text-gray-600">
                              Official documentation of received items
                            </p>
                          </div>
                        </Link>
                      </TimelineContent>
                    </TimelineItem>
                    <TimelineItem>
                      <TimelineSeparator>
                        <TimelineDot />
                      </TimelineSeparator>
                      <TimelineContent>
                        <Link
                          to="/inbound-operation/receiving-exception"
                          className="text-gray-600 hover:underline"
                        >
                          <div className="flex flex-col">
                            <h1 className="text-base font-semibold text-gray-800">
                              Exceptions
                            </h1>
                            <p className="text-sm text-gray-600">
                              Record and manage discrepancies
                            </p>
                          </div>
                        </Link>
                      </TimelineContent>
                    </TimelineItem>
                  </Timeline>
                </div>
              </div>
            </div>
          </div>

          <div className="rounded-md shadow-lg border border-gray-200 bg-white">
            <div className="divide-y divide-gray-200">
              <div className="flex items-center gap-3 px-5 py-4">
                <MoveToInboxIcon className="text-yellow-500" fontSize="large" />
                <h1 className="text-lg font-semibold text-gray-800">
                  3. Post Receiving
                </h1>
              </div>

              <div className="px-5 py-4">
                <div className="flex justify-start">
                  <Timeline
                    sx={{
                      [`& .${timelineItemClasses.root}:before`]: {
                        flex: 0,
                        padding: 0,
                      },
                    }}
                  >
                    <TimelineItem>
                      <TimelineSeparator>
                        <TimelineDot />
                        <TimelineConnector />
                      </TimelineSeparator>
                      <TimelineContent>
                        <Link
                          to="/inbound-operation/staging-location"
                          className="text-gray-600 hover:underline"
                        >
                          <div className="flex flex-col">
                            <h1 className="text-base font-semibold text-gray-800">
                              Staging Locations
                            </h1>
                            <p className="text-sm text-gray-600">
                              Temporary Storage before final placement
                            </p>
                          </div>
                        </Link>
                      </TimelineContent>
                    </TimelineItem>
                    <TimelineItem>
                      <TimelineSeparator>
                        <TimelineDot />
                        <TimelineConnector />
                      </TimelineSeparator>
                      <TimelineContent>
                        <Link
                          to="/inbound-operation/putaway-task"
                          className="text-gray-600 hover:underline"
                        >
                          <div className="flex flex-col">
                            <h1 className="text-base font-semibold text-gray-800">
                              Putaway Tasks
                            </h1>
                            <p className="text-sm text-gray-600">
                              Move goods to final storage locations
                            </p>
                          </div>
                        </Link>
                      </TimelineContent>
                    </TimelineItem>
                    <TimelineItem>
                      <TimelineSeparator>
                        <TimelineDot />
                      </TimelineSeparator>
                      <TimelineContent>
                        <Link
                          to="/inbound-operation/cross-docking-task"
                          className="text-gray-600 hover:underline"
                        >
                          <div className="flex flex-col">
                            <h1 className="text-base font-semibold text-gray-800">
                              Cross Docking Tasks
                            </h1>
                            <p className="text-sm text-gray-600">
                              Direct Transfer to outbound without storage
                            </p>
                          </div>
                        </Link>
                      </TimelineContent>
                    </TimelineItem>
                  </Timeline>
                </div>
              </div>
            </div>
          </div>

          <div className="rounded-md shadow-lg border border-gray-200 bg-white">
            <div className="divide-y divide-gray-200">
              <div className="flex items-center gap-3 px-5 py-4">
                <ManageAccountsIcon
                  className="text-blue-700"
                  fontSize="large"
                />
                <h1 className="text-lg font-semibold text-gray-800">
                  4. Management & Tracking
                </h1>
              </div>

              <div className="px-5 py-4">
                <div className="flex justify-start">
                  <Timeline
                    sx={{
                      [`& .${timelineItemClasses.root}:before`]: {
                        flex: 0,
                        padding: 0,
                      },
                    }}
                  >
                    <TimelineItem>
                      <TimelineSeparator>
                        <TimelineDot />
                        <TimelineConnector />
                      </TimelineSeparator>
                      <TimelineContent>
                        <Link
                          to="/inbound-operation/staging-location"
                          className="text-gray-600 hover:underline"
                        >
                          <div className="flex flex-col">
                            <h1 className="text-base font-semibold text-gray-800">
                              Labor Tracking
                            </h1>
                            <p className="text-sm text-gray-600">
                              Monitor productivity and labor costs
                            </p>
                          </div>
                        </Link>
                      </TimelineContent>
                    </TimelineItem>
                    <TimelineItem>
                      <TimelineSeparator>
                        <TimelineDot />
                        <TimelineConnector />
                      </TimelineSeparator>
                      <TimelineContent>
                        <Link
                          to="/inbound-operation/putaway-task"
                          className="text-gray-600 hover:underline"
                        >
                          <div className="flex flex-col">
                            <h1 className="text-base font-semibold text-gray-800">
                              Equipment Management
                            </h1>
                            <p className="text-sm text-gray-600">
                              Manage receiving equipment and resources
                            </p>
                          </div>
                        </Link>
                      </TimelineContent>
                    </TimelineItem>
                    <TimelineItem>
                      <TimelineSeparator>
                        <TimelineDot />
                      </TimelineSeparator>
                      <TimelineContent>
                        <Link
                          to="/inbound-operation/cross-docking-task"
                          className="text-gray-600 hover:underline"
                        >
                          <div className="flex flex-col">
                            <h1 className="text-base font-semibold text-gray-800">
                              Putaway Planner
                            </h1>
                            <p className="text-sm text-gray-600">
                              Optimize storage location assignments
                            </p>
                          </div>
                        </Link>
                      </TimelineContent>
                    </TimelineItem>
                  </Timeline>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div className="flex flex-col md:flex-row gap-2 w-full">
          <div className="w-full md:w-2/3">
            <div className="rounded-md shadow-lg border border-gray-200 bg-white">
              <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-2 p-5">
                <div className="flex justify-center items-center rounded-md shadow-2xl border border-gray-200 bg-white p-3">
                  <div className="flex flex-col items-center justify-center gap-1 text-center">
                    <h1 className="text-sm font-medium text-gray-700">
                      ASN Processing Rate
                    </h1>
                    <span className="text-2xl font-bold text-gray-900">
                      98.2%
                    </span>
                    <span className="text-green-600 flex items-center gap-1 text-sm">
                      <TrendingUpIcon fontSize="small" />
                      +2.1%
                    </span>
                  </div>
                </div>

                <div className="flex justify-center items-center rounded-md shadow-2xl border border-gray-200 bg-white p-3">
                  <div className="flex flex-col items-center justify-center gap-1 text-center">
                    <h1 className="text-sm font-medium text-gray-700">
                      Quality Pass Rate
                    </h1>
                    <span className="text-2xl font-bold text-gray-900">
                      87.5%
                    </span>
                    <span className="text-red-600 flex items-center gap-1 text-sm">
                      <TrendingDownIcon fontSize="small" />
                      -1.3%
                    </span>
                  </div>
                </div>

                <div className="flex justify-center items-center rounded-md shadow-2xl border border-gray-200 bg-white p-3">
                  <div className="flex flex-col items-center justify-center gap-1 text-center">
                    <h1 className="text-sm font-medium text-gray-700">
                      On-Time Arrivals
                    </h1>
                    <span className="text-2xl font-bold text-gray-900">
                      85%
                    </span>
                    <span className="text-green-600 flex items-center gap-1 text-sm">
                      <TrendingUpIcon fontSize="small" />
                      +3.2%
                    </span>
                  </div>
                </div>

                <div className="flex justify-center items-center rounded-md shadow-2xl border border-gray-200 bg-white p-3">
                  <div className="flex flex-col items-center justify-center gap-1 text-center">
                    <h1 className="text-sm font-medium text-gray-700">
                      Putaway Accuracy
                    </h1>
                    <span className="text-2xl font-bold text-gray-900">
                      99.1%
                    </span>
                    <span className="text-green-600 flex items-center gap-1 text-sm">
                      <TrendingUpIcon fontSize="small" />
                      +0.5%
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div className="w-full md:w-1/3">
            <div className="rounded-md shadow-lg border border-gray-200 bg-white p-5">
              <h1 className="text-lg font-semibold mb-4">Inbound Tasks</h1>
              <div className="flex flex-col justify-center items-center gap-2">
                <div className="w-full h-auto border border-blue-900 rounded-xl p-3">
                  <div className="flex flex-wrap justify-between mb-2">
                    <h1 className="text-base font-semibold">
                      <LocalShippingIcon
                        fontSize="medium"
                        className="text-blue-900 me-2"
                      />
                      Unload-1001
                    </h1>
                    <span className="inline-block px-2 py-0.5 text-xs font-medium text-blue-800 bg-blue-100 rounded-full">
                      In Progress
                    </span>
                  </div>
                  <div className="flex flex-row text-gray-700 text-sm">
                    <p>ASN-10045 • 42 items John D. • 15 min remaining</p>
                  </div>
                </div>

                <div className="w-full h-auto border border-blue-900 rounded-xl p-3">
                  <div className="flex flex-wrap justify-between mb-2">
                    <h1 className="text-base font-semibold">
                      <LocalShippingIcon
                        fontSize="medium"
                        className="text-blue-900 me-2"
                      />
                      Unload-1001
                    </h1>
                    <span className="inline-block px-2 py-0.5 text-xs font-medium text-blue-800 bg-blue-100 rounded-full">
                      In Progress
                    </span>
                  </div>
                  <div className="flex flex-row text-gray-700 text-sm">
                    <p>ASN-10045 • 42 items John D. • 15 min remaining</p>
                  </div>
                </div>

                <div className="w-full h-auto border border-blue-900 rounded-xl p-3">
                  <div className="flex flex-wrap justify-between mb-2">
                    <h1 className="text-base font-semibold">
                      <LocalShippingIcon
                        fontSize="medium"
                        className="text-blue-900 me-2"
                      />
                      Unload-1001
                    </h1>
                    <span className="inline-block px-2 py-0.5 text-xs font-medium text-blue-800 bg-blue-100 rounded-full">
                      In Progress
                    </span>
                  </div>
                  <div className="flex flex-row text-gray-700 text-sm">
                    <p>ASN-10045 • 42 items John D. • 15 min remaining</p>
                  </div>
                </div>

                <div className="w-full h-auto border border-blue-900 rounded-xl p-3">
                  <div className="flex flex-wrap justify-between mb-2">
                    <h1 className="text-base font-semibold">
                      <LocalShippingIcon
                        fontSize="medium"
                        className="text-blue-900 me-2"
                      />
                      Unload-1001
                    </h1>
                    <span className="inline-block px-2 py-0.5 text-xs font-medium text-blue-800 bg-blue-100 rounded-full">
                      In Progress
                    </span>
                  </div>
                  <div className="flex flex-row text-gray-700 text-sm">
                    <p>ASN-10045 • 42 items John D. • 15 min remaining</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </>
  )
}

export default InboundDashboardPage