import React, { useState, useEffect, useMemo } from 'react'
import http from '../../../lib/http'
import provideUtility from '../../../utils/toast'
import Spinner from '../../../components/ui/loading/spinner'
import AdvancedDataTable from '../../../components/ui/dataTable'
import Button from '../../../components/ui/button/Button'
import BaseModal from '../../../components/ui/modal'
import Label from '../../../components/form/Label'
import Input from '../../../components/form/input/InputField'
import TextAreaInput from '../../../components/form/form-elements/TextAreaInput'
import SingleSelectInput from '../../../components/form/form-elements/SelectInputs'
import ToggleSwitchInput from '../../../components/form/form-elements/ToggleSwitch'
import { TableColumn } from 'react-data-table-component'
import { showConfirm } from '../../../utils/alert'
import Swal from 'sweetalert2'

import Tabs from '@mui/material/Tabs'
import Tab from '@mui/material/Tab'
import Box from '@mui/material/Box'

import ZoneConfiguration from './ZoneConfiguration/List'
import ZoneMapping from './ZoneMapping/List'

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
 
interface RowData {
  zone_code: string
  zone_name: string
  zone_type: string
  area_code: string
  priority: any
  status: number
  [key: string]: any
}

interface Errors {
  zone_code?: string
  zone_name?: string
  zone_type?: string
  area_id?: any
  priority?: any
}

const columns: TableColumn<RowData>[] = [
  {
    name: 'No.',
    selector: (_row: any, index: any) => index + 1,
    width: '70px',
    sortable: false,
  },
  {
    name: 'Code',
    selector: (row: RowData) => row.zone_code,
    sortable: true,
  },
  {
    name: 'Name',
    selector: (row: RowData) => row.zone_name,
    sortable: true,
  },
  {
    name: 'Type',
    cell: (row: RowData) => (
      <span className="px-2 py-1 text-xs font-semibold rounded-full text-white bg-blue-900">
        {row.zone_type || '-'}
      </span>
    ),
    sortable: true,
  },
  {
    name: 'Area Code',
    selector: (row: RowData) =>
      row.area_code ? `${row.area_code} - ${row.area_type}` : '-',
    sortable: true,
  },
  {
    name: 'Priority',
    selector: (row: RowData) => {
      if (row.priority === 0) return 'Lower'
      if (row.priority) return 'High'
      return '-'
    },
    sortable: true,
  },

  {
    name: 'Status',
    // selector: (row: RowData) => row.status == 0?'In Active':'Active',
    cell: (row: RowData) => (
      <span
        className={`px-2 py-1 text-xs font-semibold rounded-full ${
          row.status === 1
            ? 'bg-green-100 text-green-700'
            : 'bg-red-100 text-red-700'
        }`}
      >
        {row.status === 1 ? 'Active' : 'Inactive'}
      </span>
    ),
    sortable: true,
  },
]

const List: React.FC = () => {
  const [value, setValue] = useState(0)
  const [isCreateOpen, setIsCreateOpen] = useState(false)
  const [isViewOpen, setIsViewOpen] = useState(false)
  const [isUpdateOpen, setIsUpdateOpen] = useState(false)
  const [errors, setErrors] = useState<Errors>({})
  const [areaLists, setAreaLists] = useState<any>([])
  const [zoneLists, setZoneLists] = useState<any>([])
  const [zone, setZone] = useState<any>({})
  const [isPageLoading, setIsPageLoading] = useState(false)
  const [isLoading, setIsLoading] = useState(false)
  const { showToast } = provideUtility()

  const zoneTypeData = [
    { id: 1, value: 'Storage' },
    { id: 2, value: 'Picking' },
    { id: 3, value: 'Shipping' },
    { id: 4, value: 'Receiving' },
    { id: 5, value: 'Packing' },
    { id: 6, value: 'Cross Docking' },
    { id: 7, value: 'Returns' },
  ]

  const priorityData = [
    { id: 0, value: 'Lower' },
    { id: 1, value: 'High' },
  ]

  const [formData, setFormData] = useState({
    zone_code: '',
    zone_name: '',
    zone_type: '',
    area_id: '',
    priority: '',
    description: '',
    status: 1,
  })

  const [updateFormData, setUpdateFormData] = useState({
    id: '',
    zone_code: '',
    zone_name: '',
    zone_type: '',
    area_id: '',
    priority: '',
    description: '',
    status: '',
  })

  useEffect(() => {
    setIsPageLoading(true)
    fetchZoneLists()
    fetchAreaLists()
  }, [])

  const handleTabChange = (event: React.SyntheticEvent, newValue: number) => {
      setValue(newValue)
  }

  const fetchZoneLists = async () => {
    try {
      setIsPageLoading(true)
      const res = await http.fetchDataWithToken('zones')
      console.log("zone data are")
      console.log(res)
      setZoneLists(res.data?.data || [])
    } catch (err) {
      setIsPageLoading(false)
      console.error('Failed to fetch Zone lists:', err)
    } finally {
      setIsPageLoading(false)
    }
  }

  const fetchAreaLists = async () => {
    try {
      setIsPageLoading(true)
      const res = await http.fetchDataWithToken('areas')
      console.log(res)
      setAreaLists(res.data?.data || [])
    } catch (err) {
      setIsPageLoading(false)
      console.error('Failed to fetch Area lists:', err)
    } finally {
      setIsPageLoading(false)
    }
  }

  const handleRemove = (field: string) => {
    setErrors((prev) => ({
      ...prev,
      [field]: null,
    }))
  }

  const handleToggle = (checked: boolean) => {
    const is_active = checked ? 1 : 0
    if (isCreateOpen) {
      setFormData((prev: any) => ({
        ...prev,
        status: is_active,
      }))
    } else {
      setUpdateFormData((prev: any) => ({
        ...prev,
        status: is_active,
      }))
    }
  }

  const handleCloseModal = () => {
    setIsCreateOpen(false)
    setIsViewOpen(false)
    setIsUpdateOpen(false)
  }

  const handleView = (row: any) => {
    console.log(row)
    setZone(zoneLists?.find((x: any) => x.id === row.id))
    setIsViewOpen(true)
  }

  const handleCreate = () => {
    setErrors({
      zone_code: '',
      zone_name: '',
      zone_type: '',
      area_id: '',
      priority: ''
    })
    setIsCreateOpen(true)
  }

  const handleEdit = (row: any) => {
    setIsUpdateOpen(true)
    setErrors({
      zone_code: '',
      zone_name: '',
      zone_type: '',
      area_id:'',
      priority:''
    })
    const zone_data = zoneLists.find((x: any) => x.id === row.id)

    if (zone_data) {
      setUpdateFormData({
        id: zone_data.id,
        zone_code: zone_data.zone_code,
        zone_name: zone_data.zone_name,
        zone_type: zone_data.zone_type,
        area_id: zone_data.area_id,
        priority: zone_data.priority,
        description: zone_data.description,
        status: zone_data.status,
      })
    }
  }

  const handleDelete = async (row: any) => {
    const confirmed = await showConfirm(
      'Are you sure?',
      'This action cannot be undone.'
    )

    if (!confirmed) return

    try {
      const response = await http.deleteDataWithToken(`/zones/${row.id}`)
      console.log(response)
      if (response.status == true) {
        Swal.fire({
          title: 'Deleted!',
          text: 'Zone has been deleted.',
          icon: 'success',
        })
        fetchZoneLists()
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

  const handleChange =
    (field: string) =>
    (e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement>) => {
      const value = e.target.value
      if (isCreateOpen) {
        setFormData((prev) => ({
          ...prev,
          [field]: value,
        }))
      } else {
        setUpdateFormData((prev) => ({
          ...prev,
          [field]: value,
        }))
      }
    }

  const handleSubmit = async () => {
    setIsLoading(true)
    setErrors({})
    try {
      const response = await http.postDataWithToken('/zones', formData)
      if (response.status === true) {
        setIsCreateOpen(false)
        showToast('', 'Create Zone successful', 'top-right', 'success')
        setFormData({
          zone_code: '',
          zone_name: '',
          zone_type: '',
          area_id:'',
          priority:'',
          description: '',
          status: 1,
        })
        fetchZoneLists()
      } else {
        showToast('', 'Something went wrong!.', 'top-right', 'error')
      }
    } catch (err: any) {
      if (err?.status === 422) {
        showToast('', err?.data.message, 'top-right', 'error')
        const apiErrors: Errors = err?.data.errors
        setErrors(apiErrors)
      } else {
        showToast('', 'Create Zone failed!', 'top-right', 'error')
      }
      console.error(err)
    } finally {
      setIsLoading(false)
    }
  }

  const handleUpdate = async () => {
    setIsLoading(true)
    setErrors({})
    try {
      const response = await http.putDataWithToken(
        `/zones/${updateFormData.id}`,
        updateFormData
      )
      if (response.status === true) {
        setIsUpdateOpen(false)
        showToast('', 'Update Zone successful', 'top-right', 'success')
        setUpdateFormData({
          id: '',
          zone_code: '',
          zone_name: '',
          zone_type: '',
          area_id: '',
          priority: '',
          description: '',
          status: '',
        })
        fetchZoneLists()
      } else {
        showToast('', 'Something went wrong!.', 'top-right', 'error')
      }
    } catch (err: any) {
      if (err?.status === 422) {
        showToast('', err?.message, 'top-right', 'error')
        const apiErrors: Errors = err?.errors
        setErrors(apiErrors)
      } else {
        showToast('', 'Update Zone failed!', 'top-right', 'error')
      }
      console.error(err)
    } finally {
      setIsLoading(false)
    }
  }

  const [filterText, setFilterText] = React.useState('')
  const filteredData = useMemo(() => {
    if (!filterText) return zoneLists

    return zoneLists.filter((item: any) =>
      Object.values(item).some(
        (val) =>
          val && val.toString().toLowerCase().includes(filterText.toLowerCase())
      )
    )
  }, [filterText, zoneLists])

  return (
    <div className="space-y-10">
      <div className="flex justify-between items-center">
        <h1 className="text-xl font-semibold">Zone Lists</h1>
        <Button variant="primary" size="sm" onClick={handleCreate}>
          Add Zone
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
                label="Zones"
                {...a11yProps(0)}
              />
              <Tab
                sx={{ fontWeight: 'bold' }}
                label="Zone Configuration"
                {...a11yProps(1)}
              />
              <Tab
                sx={{ fontWeight: 'bold' }}
                label="Zone Mapping"
                {...a11yProps(2)}
              />
            </Tabs>
          </Box>
          <CustomTabPanel value={value} index={0}>
            <div className="">
              {isPageLoading ? (
                <div className="flex justify-center items-center space-x-2">
                  <div className="w-5 h-5 border-2 border-t-blue-500 border-blue-200 rounded-full animate-spin"></div>
                  <span className="text-sm text-gray-500">Loading...</span>
                </div>
              ) : (
                <AdvancedDataTable
                  data={filteredData || []}
                  columns={columns}
                  onView={handleView}
                  onEdit={handleEdit}
                  onDelete={handleDelete}
                  subHeader
                  subHeaderComponent={
                    <div className="w-full flex items-center justify-between px-0 py-2 bg-muted">
                      <Input
                        type="text"
                        placeholder="Search Zones…"
                        value={filterText}
                        onChange={(e) => setFilterText(e.target.value)}
                        className="w-full max-w-sm"
                      />
                    </div>
                  }
                />
              )}
            </div>
          </CustomTabPanel>
          <CustomTabPanel value={value} index={1}>
            <ZoneConfiguration
              zoneLists={zoneLists}
              handleReFetchZoneListsApi={fetchZoneLists}
              isPageLoading={isPageLoading}
            />
          </CustomTabPanel>
          <CustomTabPanel value={value} index={2}>
            <ZoneMapping
              zoneLists={zoneLists}
              handleReFetchZoneListsApi={fetchZoneLists}
            />
          </CustomTabPanel>
        </Box>
      </div>

      {isCreateOpen && (
        <BaseModal
          isOpen={isCreateOpen}
          onClose={handleCloseModal}
          isFullscreen={false}
        >
          <div className="space-y-6">
            <h2 className="text-xl font-semibold text-gray-800">
              Add New Zone
            </h2>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <Label>
                  Zone Code<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={formData.zone_code}
                  onChange={handleChange('zone_code')}
                  onKeyUp={() => handleRemove('zone_code')}
                  error={!!errors.zone_code}
                  hint={errors.zone_code}
                />
              </div>
              <div>
                <Label>
                  Zone Name<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={formData.zone_name}
                  onChange={handleChange('zone_name')}
                  onKeyUp={() => handleRemove('zone_name')}
                  error={!!errors.zone_name}
                  hint={errors.zone_name}
                />
              </div>

              <div>
                <Label>Zone Type</Label>
                <SingleSelectInput
                  options={zoneTypeData}
                  valueKey="value"
                  value={formData.zone_type}
                  getOptionLabel={(item) => `${item.value}`}
                  onSingleSelectChange={(val) => {
                    console.log('value - ' + val)
                    handleRemove('zone_type')
                    setFormData((prev) => ({
                      ...prev,
                      zone_type: val,
                    }))
                  }}
                  error={!!errors.zone_type}
                  hint={errors.zone_type}
                />
              </div>
              <div>
                <Label>Area Code</Label>
                <SingleSelectInput
                  options={areaLists}
                  valueKey="id"
                  value={formData.area_id}
                  getOptionLabel={(item) =>
                    `${item.area_code} (${item.area_type})`
                  }
                  onSingleSelectChange={(val) => {
                    console.log('value - ' + val)
                    handleRemove('area_id')
                    setFormData((prev) => ({
                      ...prev,
                      area_id: val,
                    }))
                  }}
                  error={!!errors.area_id}
                  hint={errors.area_id}
                />
              </div>
              <div>
                <Label>Priority</Label>
                <SingleSelectInput
                  options={priorityData}
                  valueKey="id"
                  value={formData.priority}
                  getOptionLabel={(item) => `${item.value}`}
                  onSingleSelectChange={(val) => {
                    console.log('value - ' + val)
                    handleRemove('priority')
                    setFormData((prev) => ({
                      ...prev,
                      priority: val,
                    }))
                  }}
                  error={!!errors.priority}
                  hint={errors.priority}
                />
              </div>
              <div className="col-span-full">
                <Label>Description</Label>
                <TextAreaInput
                  value={formData.description}
                  onChange={(value) =>
                    handleChange('description')({
                      target: { value },
                    } as React.ChangeEvent<any>)
                  }
                />
              </div>

              <div>
                <Label>Status</Label>
                <ToggleSwitchInput
                  label="Enable Active"
                  defaultChecked={!!formData.status}
                  onToggleChange={handleToggle}
                />
              </div>
            </div>
            <div className="flex justify-end gap-2">
              <Button variant="secondary" onClick={handleCloseModal}>
                Cancel
              </Button>
              <Button
                variant="primary"
                startIcon={isLoading && <Spinner size={4} />}
                onClick={handleSubmit}
              >
                Confirm
              </Button>
            </div>
          </div>
        </BaseModal>
      )}

      {isViewOpen && (
        <BaseModal
          isOpen={isViewOpen}
          onClose={handleCloseModal}
          isFullscreen={false}
        >
          <div className="space-y-6">
            <h2 className="text-xl font-semibold text-gray-800">Zone Info</h2>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <Label>
                  Zone Code<span className="text-error-500">*</span>
                </Label>
                <Input type="text" value={zone.zone_code} disabled={true} />
              </div>
              <div>
                <Label>
                  Zone Name<span className="text-error-500">*</span>
                </Label>
                <Input type="text" value={zone.zone_name} disabled={true} />
              </div>

              <div>
                <Label>Zone Type</Label>
                <Input type="text" value={zone.zone_type} disabled={true} />
              </div>
              <div>
                <Label>Area Code</Label>
                <Input type="text" value={zone.area_code} disabled={true} />
              </div>
              <div>
                <Label>Priority</Label>
                <Input type="text" value={zone.priority} disabled={true} />
              </div>
              <div className="col-span-full">
                <Label>Description</Label>
                <TextAreaInput value={zone.description} disabled={true} />
              </div>

              <div>
                <Label>Status</Label>
                <ToggleSwitchInput
                  label="Enable Active"
                  defaultChecked={!!zone.status}
                  onToggleChange={handleToggle}
                />
              </div>
            </div>
          </div>
        </BaseModal>
      )}

      {isUpdateOpen && (
        <BaseModal
          isOpen={isUpdateOpen}
          onClose={handleCloseModal}
          isFullscreen={false}
        >
          <div className="space-y-6">
            <h2 className="text-xl font-semibold text-gray-800">Update Zone</h2>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <Label>
                  Zone Code<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={updateFormData.zone_code}
                  onChange={handleChange('zone_code')}
                  onKeyUp={() => handleRemove('zone_code')}
                  error={!!errors.zone_code}
                  hint={errors.zone_code}
                />
              </div>
              <div>
                <Label>
                  Zone Name<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={updateFormData.zone_name}
                  onChange={handleChange('zone_name')}
                  onKeyUp={() => handleRemove('zone_name')}
                  error={!!errors.zone_name}
                  hint={errors.zone_name}
                />
              </div>

              <div>
                <Label>Zone Type</Label>
                <SingleSelectInput
                  options={zoneTypeData}
                  valueKey="value"
                  value={updateFormData.zone_type}
                  getOptionLabel={(item) => `${item.value}`}
                  onSingleSelectChange={(val) => {
                    console.log('value - ' + val)
                    handleRemove('zone_type')
                    setUpdateFormData((prev) => ({
                      ...prev,
                      zone_type: val,
                    }))
                  }}
                  error={!!errors.zone_type}
                  hint={errors.zone_type}
                />
              </div>
              <div>
                <Label>Area Code</Label>
                <SingleSelectInput
                  options={areaLists}
                  valueKey="id"
                  value={updateFormData.area_id}
                  getOptionLabel={(item) =>
                    `${item.area_code} (${item.area_type})`
                  }
                  onSingleSelectChange={(val) => {
                    console.log('value - ' + val)
                    handleRemove('area_id')
                    setUpdateFormData((prev) => ({
                      ...prev,
                      area_id: val,
                    }))
                  }}
                  error={!!errors.area_id}
                  hint={errors.area_id}
                />
              </div>
              <div>
                <Label>Priority</Label>
                <SingleSelectInput
                  options={priorityData}
                  valueKey="id"
                  value={updateFormData.priority}
                  getOptionLabel={(item) => `${item.value}`}
                  onSingleSelectChange={(val) => {
                    console.log('value - ' + val)
                    handleRemove('priority')
                    setUpdateFormData((prev) => ({
                      ...prev,
                      priority: val,
                    }))
                  }}
                  error={!!errors.priority}
                  hint={errors.priority}
                />
              </div>
              <div className="col-span-full">
                <Label>Description</Label>
                <TextAreaInput
                  value={updateFormData.description}
                  onChange={(value) =>
                    handleChange('description')({
                      target: { value },
                    } as React.ChangeEvent<any>)
                  }
                />
              </div>

              <div>
                <Label>Status</Label>
                <ToggleSwitchInput
                  label="Enable Active"
                  defaultChecked={!!updateFormData.status}
                  onToggleChange={handleToggle}
                />
              </div>
            </div>
            <div className="flex justify-end gap-2">
              <Button variant="secondary" onClick={handleCloseModal}>
                Cancel
              </Button>
              <Button
                variant="primary"
                startIcon={isLoading && <Spinner size={4} />}
                onClick={handleUpdate}
              >
                Update
              </Button>
            </div>
          </div>
        </BaseModal>
      )}
    </div>
  )
}

export default List
