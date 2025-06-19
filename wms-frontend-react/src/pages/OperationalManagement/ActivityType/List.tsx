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

interface RowData {
  activity_type_code: string
  activity_type_name: string
  category: string
  default_duration:any
  creation_date:string
  created_by:string
  last_modified_date:string
  last_modified_by:string
  ai_insight_flag:string
  status: number
  [key: string]: any
}

interface Errors {
  activity_type_code?: string
  activity_type_name?: string
  status?: any
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
    selector: (row: RowData) => row.activity_type_code,
    sortable: true,
  },
  {
    name: 'Name',
    selector: (row: RowData) => row.activity_type_name,
    sortable: true,
  },
  {
    name: 'Default Duration',
    selector: (row: RowData) =>
      typeof row.default_duration === 'number'
        ? `${row.discount_percent} mins`
        : '-',
    sortable: true,
  },
  {
    name: 'Creation Date',
    selector: (row: RowData) => row.creation_date || '-',
    sortable: true,
  },
  {
    name: 'Created By',
    selector: (row: RowData) => row.created_by || '-',
    sortable: true,
  },
  {
    name: 'Last Modified Date',
    selector: (row: RowData) => row.last_modified_date || '-',
    sortable: true,
  },
  {
    name: 'Last Modified By',
    selector: (row: RowData) => row.last_modified_by || '-',
    sortable: true,
  },
  {
    name: 'AI Insight Flag',
    selector: (row: RowData) => row.ai_insight_flag || '-',
    sortable: true,
  },
  {
    name: 'Status',
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
  const [isCreateOpen, setIsCreateOpen] = useState(false)
  const [isViewOpen, setIsViewOpen] = useState(false)
  const [isUpdateOpen, setIsUpdateOpen] = useState(false)
  const [errors, setErrors] = useState<Errors>({})
  const [activityTypeLists, setActivityTypeLists] = useState<any>([])
  const [activityType, setActivityType] = useState<any>({})
  const [isPageLoading, setIsPageLoading] = useState(false)
  const [isLoading, setIsLoading] = useState(false)
  const { showToast } = provideUtility()

  const categoryData = [
    { id: 1, value: 'Inbound' },
    { id: 2, value: 'Outbound' },
    { id: 3, value: 'Internal' },
    { id: 4, value: 'Administrative' },
    { id: 5, value: 'Maintenance' },
  ]

  const aiInsightFlagData = [
    { id: 0, value: 'No' },
    { id: 1, value: 'Yes' },
  ]

  const [formData, setFormData] = useState({
    activity_type_code: '',
    activity_type_name: '',
    default_duration: '',
    category:'',
    description:'',
    ai_insight_flag: '',
    creation_date: '',
    created_by: '',
    last_modified_date: '',
    last_modified_by: '',
    status: 1,
  })

  const [updateFormData, setUpdateFormData] = useState({
    id: '',
    activity_type_code: '',
    activity_type_name: '',
    default_duration: '',
    category: '',
    description: '',
    ai_insight_flag: '',
    creation_date: '',
    created_by: '',
    last_modified_date: '',
    last_modified_by: '',
    status: '',
  })

  useEffect(() => {
    setIsPageLoading(true)
    fetchActivityTypeLists()
  }, [])

  const fetchActivityTypeLists = async () => {
    try {
      setIsPageLoading(true)
      const res = await http.fetchDataWithToken('activity-types')
      console.log(res)
      setActivityTypeLists(res.data?.data || [])
    } catch (err) {
      setIsPageLoading(false)
      console.error('Failed to fetch Activity Type lists:', err)
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
    setActivityType(activityTypeLists?.find((x: any) => x.id === row.id))
    setIsViewOpen(true)
  }

  const handleCreate = () => {
    setErrors({
        activity_type_code: '',
        activity_type_name: '',
        status: ''
    })
    setIsCreateOpen(true)
  }

  const handleEdit = (row: any) => {
    setIsUpdateOpen(true)
    setErrors({
      activity_type_code: '',
      activity_type_name: '',
      status: '',
    })
    const activity_type_data = activityTypeLists.find((x: any) => x.id === row.id)
    if (activity_type_data) {
      setUpdateFormData({
        id: activity_type_data.id,
        activity_type_code: activity_type_data.activity_type_code,
        activity_type_name: activity_type_data.activity_type_name,
        default_duration: activity_type_data.default_duration,
        description: activity_type_data.description,
        category: activity_type_data.category,
        ai_insight_flag: activity_type_data.ai_insight_flag,
        creation_date: activity_type_data.creation_date,
        created_by: activity_type_data.created_by,
        last_modified_date: activity_type_data.last_modified_date,
        last_modified_by: activity_type_data.last_modified_by,
        status: activity_type_data.status,
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
      const response = await http.deleteDataWithToken(`/activity-types/${row.id}`)
      console.log(response)
      if (response.status == true) {
        Swal.fire({
          title: 'Deleted!',
          text: 'Activity Type has been deleted.',
          icon: 'success',
        })
        fetchActivityTypeLists()
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
      const response = await http.postDataWithToken('/activity-types', formData)
      if (response.status === true) {
        setIsCreateOpen(false)
        showToast('', 'Create Activity Type successful', 'top-right', 'success')
        setFormData({
          activity_type_code: '',
          activity_type_name: '',
          default_duration: '',
          category: '',
          description: '',
          ai_insight_flag: '',
          creation_date: '',
          created_by: '',
          last_modified_date: '',
          last_modified_by: '',
          status: 1,
        })
        fetchActivityTypeLists()
      } else {
        showToast('', 'Something went wrong!.', 'top-right', 'error')
      }
    } catch (err: any) {
      if (err?.status === 422) {
        showToast('', err?.data.message, 'top-right', 'error')
        const apiErrors: Errors = err?.data.errors
        setErrors(apiErrors)
      } else {
        showToast('', 'Create Activity Type failed!', 'top-right', 'error')
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
        `/activity-types/${updateFormData.id}`,
        updateFormData
      )
      if (response.status === true) {
        setIsUpdateOpen(false)
        showToast('', 'Update Activity Type successful', 'top-right', 'success')
        setUpdateFormData({
          id: '',
          activity_type_code: '',
          activity_type_name: '',
          default_duration: '',
          category: '',
          description: '',
          ai_insight_flag: '',
          creation_date: '',
          created_by: '',
          last_modified_date: '',
          last_modified_by: '',
          status: '',
        })
        fetchActivityTypeLists()
      } else {
        showToast('', 'Something went wrong!.', 'top-right', 'error')
      }
    } catch (err: any) {
      if (err?.status === 422) {
        showToast('', err?.message, 'top-right', 'error')
        const apiErrors: Errors = err?.errors
        setErrors(apiErrors)
      } else {
        showToast('', 'Update Activity Type failed!', 'top-right', 'error')
      }
      console.error(err)
    } finally {
      setIsLoading(false)
    }
  }

  const [filterText, setFilterText] = React.useState('')
  const filteredData = useMemo(() => {
    if (!filterText) return activityTypeLists

    return activityTypeLists.filter((item: any) =>
      Object.values(item).some(
        (val) =>
          val && val.toString().toLowerCase().includes(filterText.toLowerCase())
      )
    )
  }, [filterText, activityTypeLists])

  return (
    <div className="space-y-10">
      <div className="flex justify-between items-center">
        <h1 className="text-xl font-semibold">Activity Type Lists</h1>
        <Button variant="primary" size="sm" onClick={handleCreate}>
          Add Activity Type
        </Button>
      </div>

      <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 md:gap-6 xl:grid-cols-4">
        <div className="rounded-md shadow-lg border border-gray-200 bg-white p-2">
          <div className="rounded-2xl border border-gray-200 bg-white p-5 ">
            <p className="text-gray-500 text-theme-sm dark:text-gray-400">
              Total Activity Types
            </p>
            <div className="flex items-end justify-between mt-3">
              <div className="">
                <h4 className="text-2xl font-bold text-gray-800 dark:text-white/90">
                  {activityTypeLists.length ?? 0}
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
          <div className="rounded-2xl border border-gray-200 bg-white p-5 ">
            <p className="text-gray-500 text-theme-sm dark:text-gray-400">
              Inbound Activities
            </p>
            <div className="flex items-end justify-between mt-3">
              <div className="">
                <h4 className="text-2xl font-bold text-gray-800 dark:text-white/90">
                  {
                    activityTypeLists.filter(
                      (x: any) => x.category == 'Inbound'
                    ).length
                  }
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
                {/* <span className="text-gray-500 text-theme-xs ">
                    Vs last month
                  </span> */}
              </div>
            </div>
          </div>
        </div>

        <div className="rounded-md shadow-lg border border-gray-200 bg-white p-2">
          <div className="rounded-2xl border border-gray-200 bg-white p-5 ">
            <p className="text-gray-500 text-theme-sm dark:text-gray-400">
              Outbound Activities
            </p>
            <div className="flex items-end justify-between mt-3">
              <div className="">
                <h4 className="text-2xl font-bold text-gray-800 dark:text-white/90">
                  {
                    activityTypeLists.filter(
                      (x: any) => x.category == 'Outbound'
                    ).length
                  }
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
          <div className="rounded-2xl border border-gray-200 bg-white p-5 ">
            <p className="text-gray-500 text-theme-sm dark:text-gray-400">
              Internal Activities
            </p>
            <div className="flex items-end justify-between mt-3">
              <div className="">
                <h4 className="text-2xl font-bold text-gray-800 dark:text-white/90">
                  {
                    activityTypeLists.filter(
                      (x: any) => x.category == 'Internal'
                    ).length
                  }
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
      </div>

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
                placeholder="Search Activity Types…"
                value={filterText}
                onChange={(e) => setFilterText(e.target.value)}
                className="w-full max-w-sm"
              />
            </div>
          }
        />
      )}

      {isCreateOpen && (
        <BaseModal
          isOpen={isCreateOpen}
          onClose={handleCloseModal}
          isFullscreen={false}
        >
          <div className="space-y-6">
            <h2 className="text-xl font-semibold text-gray-800">
              Add New Activity Type
            </h2>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <Label>
                  Activity Type Code<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={formData.activity_type_code}
                  onChange={handleChange('activity_type_code')}
                  onKeyUp={() => handleRemove('activity_type_code')}
                  error={!!errors.activity_type_code}
                  hint={errors.activity_type_code}
                />
              </div>
              <div>
                <Label>
                  Activity Type Name<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={formData.activity_type_name}
                  onChange={handleChange('activity_type_name')}
                  onKeyUp={() => handleRemove('activity_type_name')}
                  error={!!errors.activity_type_name}
                  hint={errors.activity_type_name}
                />
              </div>
              <div>
                <Label>Category</Label>
                <SingleSelectInput
                  options={categoryData}
                  valueKey="value"
                  value={formData.category}
                  getOptionLabel={(item) => `${item.value}`}
                  onSingleSelectChange={(val) => {
                    handleRemove('category')
                    setFormData((prev) => ({
                      ...prev,
                      category: val,
                    }))
                  }}
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
                <Label>AI Insight Flag</Label>
                <SingleSelectInput
                  options={aiInsightFlagData}
                  valueKey="value"
                  value={formData.ai_insight_flag}
                  getOptionLabel={(item) => `${item.value}`}
                  onSingleSelectChange={(val) => {
                    handleRemove('ai_insight_flag')
                    setFormData((prev) => ({
                      ...prev,
                      ai_insight_flag: val,
                    }))
                  }}
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
            <h2 className="text-xl font-semibold text-gray-800">
              Activity Type
            </h2>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <Label>
                  Activity Type Code<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={activityType.activity_type_code}
                  disabled={true}
                />
              </div>
              <div>
                <Label>
                  Activity Type Name<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={activityType.activity_type_name}
                  disabled={true}
                />
              </div>
              <div>
                <Label>Category</Label>
                <Input type="text" value={activityType.category} disabled={true} />
              </div>
              <div className="col-span-full">
                <Label>Description</Label>
                <TextAreaInput value={activityType.description} disabled={true} />
              </div>
              <div>
                <Label>AI Insight Flag</Label>
                <Input
                  type="text"
                  value={activityType.ai_insight_flag}
                  disabled={true}
                />
              </div>

              <div>
                <Label>Status</Label>
                <ToggleSwitchInput
                  label="Enable Active"
                  defaultChecked={!!activityType.status}
                  onToggleChange={handleToggle}
                  disabled={true}
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
            <h2 className="text-xl font-semibold text-gray-800">
              Edit Activity Type
            </h2>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <Label>
                  Activity Type Code<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={updateFormData.activity_type_code}
                  onChange={handleChange('activity_type_code')}
                  onKeyUp={() => handleRemove('activity_type_code')}
                  error={!!errors.activity_type_code}
                  hint={errors.activity_type_code}
                />
              </div>
              <div>
                <Label>
                  Activity Type Name<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={updateFormData.activity_type_name}
                  onChange={handleChange('activity_type_name')}
                  onKeyUp={() => handleRemove('activity_type_name')}
                  error={!!errors.activity_type_name}
                  hint={errors.activity_type_name}
                />
              </div>
              <div>
                <Label>Category</Label>
                <SingleSelectInput
                  options={categoryData}
                  valueKey="value"
                  value={updateFormData.category}
                  getOptionLabel={(item) => `${item.value}`}
                  onSingleSelectChange={(val) => {
                    handleRemove('category')
                    setUpdateFormData((prev) => ({
                      ...prev,
                      category: val,
                    }))
                  }}
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
                <Label>AI Insight Flag</Label>
                <SingleSelectInput
                  options={aiInsightFlagData}
                  valueKey="value"
                  value={updateFormData.ai_insight_flag}
                  getOptionLabel={(item) => `${item.value}`}
                  onSingleSelectChange={(val) => {
                    handleRemove('ai_insight_flag')
                    setUpdateFormData((prev) => ({
                      ...prev,
                      ai_insight_flag: val,
                    }))
                  }}
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
