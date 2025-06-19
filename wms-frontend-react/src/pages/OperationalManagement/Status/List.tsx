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
  status_code: string
  status_name: string
  entity_type: string
  category: string
  creation_date:string
  created_by:string
  last_modified_date:string
  last_modified_by:string
  analytics_flag:string
  status: number
  [key: string]: any
}

interface Errors {
  status_code?: string
  status_name?: string
  entity_type?: string
  analytics_flag?: string
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
    selector: (row: RowData) => row.status_code,
    sortable: true,
  },
  {
    name: 'Name',
    selector: (row: RowData) => row.status_name,
    sortable: true,
  },
  {
    name: 'Entity Type',
    selector: (row: RowData) => row.entity_type || '-',
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
    name: 'Analytics Flag',
    selector: (row: RowData) => row.analytics_flag || '-',
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
  const [statusLists, setStatusLists] = useState<any>([])
  const [eachStatus, setEachStatus] = useState<any>({})
  const [isPageLoading, setIsPageLoading] = useState(false)
  const [isLoading, setIsLoading] = useState(false)
  const { showToast } = provideUtility()

  const entityTypeData = [
    { id: 1, value: 'Order' },
    { id: 2, value: 'Shipment' },
    { id: 3, value: 'Inventory' },
  ]

  const categoryData = [
    { id: 1, value: 'In Progress' },
    { id: 2, value: 'Completed' },
    { id: 3, value: 'Available' },
    { id: 4, value: 'Unavailable' },
    { id: 5, value: 'Pending' },
    { id: 6, value: 'Cancelled' },
  ]

  const analyticsFlagData = [
    { id: 0, value: 'No' },
    { id: 1, value: 'Yes' },
  ]

  const [formData, setFormData] = useState({
    status_code: '',
    status_name: '',
    entity_type: '',
    category:'',
    description:'',
    analytics_flag: '',
    creation_date: '',
    created_by: '',
    last_modified_date: '',
    last_modified_by: '',
    status: 1,
  })

  const [updateFormData, setUpdateFormData] = useState({
    id: '',
    status_code: '',
    status_name: '',
    entity_type: '',
    category:'',
    analytics_flag: '',
    description:'',
    creation_date: '',
    created_by: '',
    last_modified_date: '',
    last_modified_by: '',
    status: '',
  })

  useEffect(() => {
    setIsPageLoading(true)
    fetchStatusLists()
  }, [])

  const fetchStatusLists = async () => {
    try {
      setIsPageLoading(true)
      const res = await http.fetchDataWithToken('statuses')
      console.log(res)
      setStatusLists(res.data?.data || [])
    } catch (err) {
      setIsPageLoading(false)
      console.error('Failed to fetch Status lists:', err)
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
    setEachStatus(statusLists?.find((x: any) => x.id === row.id))
    setIsViewOpen(true)
  }

  const handleCreate = () => {
    setErrors({
        status_code: '',
        status_name: '',
        entity_type: '',
        analytics_flag: '',
        status: ''
    })
    setIsCreateOpen(true)
  }

  const handleEdit = (row: any) => {
    setIsUpdateOpen(true)
    setErrors({
      status_code: '',
      status_name: '',
      entity_type: '',
      analytics_flag: '',
      status: '',
    })
    const status_data = statusLists.find((x: any) => x.id === row.id)
    if (status_data) {
      setUpdateFormData({
        id: status_data.id,
        status_code: status_data.status_code,
        status_name: status_data.status_name,
        entity_type: status_data.entity_type,
        description: status_data.description,
        category: status_data.category,
        analytics_flag: status_data.analytics_flag,
        creation_date: status_data.creation_date,
        created_by: status_data.created_by,
        last_modified_date: status_data.last_modified_date,
        last_modified_by: status_data.last_modified_by,
        status: status_data.status,
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
      const response = await http.deleteDataWithToken(`/statuses/${row.id}`)
      console.log(response)
      if (response.status == true) {
        Swal.fire({
          title: 'Deleted!',
          text: 'Status has been deleted.',
          icon: 'success',
        })
        fetchStatusLists()
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
      const response = await http.postDataWithToken('/statuses', formData)
      if (response.status === true) {
        setIsCreateOpen(false)
        showToast('', 'Create Status successful', 'top-right', 'success')
        setFormData({
          status_code: '',
          status_name: '',
          entity_type: '',
          category: '',
          description: '',
          analytics_flag: '',
          creation_date: '',
          created_by: '',
          last_modified_date: '',
          last_modified_by: '',
          status: 1,
        })
        fetchStatusLists()
      } else {
        showToast('', 'Something went wrong!.', 'top-right', 'error')
      }
    } catch (err: any) {
      if (err?.status === 422) {
        showToast('', err?.data.message, 'top-right', 'error')
        const apiErrors: Errors = err?.data.errors
        setErrors(apiErrors)
      } else {
        showToast('', 'Create Status failed!', 'top-right', 'error')
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
        `/statuses/${updateFormData.id}`,
        updateFormData
      )
      if (response.status === true) {
        setIsUpdateOpen(false)
        showToast('', 'Update Status successful', 'top-right', 'success')
        setUpdateFormData({
          id: '',
          status_code: '',
          status_name: '',
          entity_type: '',
          category: '',
          description: '',
          analytics_flag: '',
          creation_date: '',
          created_by: '',
          last_modified_date: '',
          last_modified_by: '',
          status: '',
        })
        fetchStatusLists()
      } else {
        showToast('', 'Something went wrong!.', 'top-right', 'error')
      }
    } catch (err: any) {
      if (err?.status === 422) {
        showToast('', err?.message, 'top-right', 'error')
        const apiErrors: Errors = err?.errors
        setErrors(apiErrors)
      } else {
        showToast('', 'Update Status failed!', 'top-right', 'error')
      }
      console.error(err)
    } finally {
      setIsLoading(false)
    }
  }

  const [filterText, setFilterText] = React.useState('')
  const filteredData = useMemo(() => {
    if (!filterText) return statusLists

    return statusLists.filter((item: any) =>
      Object.values(item).some(
        (val) =>
          val && val.toString().toLowerCase().includes(filterText.toLowerCase())
      )
    )
  }, [filterText, statusLists])

  return (
    <div className="space-y-10">
      <div className="flex justify-between items-center">
        <h1 className="text-xl font-semibold">Status Lists</h1>
        <Button variant="primary" size="sm" onClick={handleCreate}>
          Add Status
        </Button>
      </div>

      <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 md:gap-6 xl:grid-cols-4">
        <div className="rounded-md shadow-lg border border-gray-200 bg-white p-2">
          <div className="rounded-2xl border border-gray-200 bg-white p-5 ">
            <p className="text-gray-500 text-theme-sm dark:text-gray-400">
              Total Statuses
            </p>
            <div className="flex items-end justify-between mt-3">
              <div className="">
                <h4 className="text-2xl font-bold text-gray-800 dark:text-white/90">
                  {statusLists.length ?? 0}
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
              Order Statuses
            </p>
            <div className="flex items-end justify-between mt-3">
              <div className="">
                <h4 className="text-2xl font-bold text-gray-800 dark:text-white/90">
                  {
                    statusLists.filter((x: any) => x.entity_type == 'Order')
                      .length
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
              Shipment Statuses
            </p>
            <div className="flex items-end justify-between mt-3">
              <div className="">
                <h4 className="text-2xl font-bold text-gray-800 dark:text-white/90">
                  {
                    statusLists.filter((x: any) => x.entity_type == 'Shipment')
                      .length
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
              Inventory Statuses
            </p>
            <div className="flex items-end justify-between mt-3">
              <div className="">
                <h4 className="text-2xl font-bold text-gray-800 dark:text-white/90">
                  {
                    statusLists.filter((x: any) => x.entity_type == 'Inventory')
                      .length
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
                placeholder="Search Statuses…"
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
              Add New Status
            </h2>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <Label>
                  Status Code<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={formData.status_code}
                  onChange={handleChange('status_code')}
                  onKeyUp={() => handleRemove('status_code')}
                  error={!!errors.status_code}
                  hint={errors.status_code}
                />
              </div>
              <div>
                <Label>
                  Status Name<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={formData.status_name}
                  onChange={handleChange('status_name')}
                  onKeyUp={() => handleRemove('status_name')}
                  error={!!errors.status_name}
                  hint={errors.status_name}
                />
              </div>
              <div>
                <Label>
                  Entity Type<span className="text-error-500">*</span>
                </Label>
                <SingleSelectInput
                  options={entityTypeData}
                  valueKey="value"
                  value={formData.entity_type}
                  getOptionLabel={(item) => `${item.value}`}
                  onSingleSelectChange={(val) => {
                    handleRemove('entity_type')
                    setFormData((prev) => ({
                      ...prev,
                      entity_type: val,
                    }))
                  }}
                  error={!!errors.entity_type}
                  hint={errors.entity_type}
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
                <Label>Analytics Flag</Label>
                <SingleSelectInput
                  options={analyticsFlagData}
                  valueKey="value"
                  value={formData.analytics_flag}
                  getOptionLabel={(item) => `${item.value}`}
                  onSingleSelectChange={(val) => {
                    handleRemove('analytics_flag')
                    setFormData((prev) => ({
                      ...prev,
                      analytics_flag: val,
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
            <h2 className="text-xl font-semibold text-gray-800">Status</h2>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <Label>
                  Status Code<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={eachStatus.status_code}
                  disabled={true}
                />
              </div>
              <div>
                <Label>
                  Status Name<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={eachStatus.status_name}
                  disabled={true}
                />
              </div>
              <div>
                <Label>
                  Entity Type<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={eachStatus.entity_type}
                  disabled={true}
                />
              </div>
              <div>
                <Label>Category</Label>
                <Input type="text" value={eachStatus.category} disabled={true} />
              </div>
              <div className="col-span-full">
                <Label>Description</Label>
                <TextAreaInput value={eachStatus.description} disabled={true} />
              </div>
              <div>
                <Label>Analytics Flag</Label>
                <Input
                  type="text"
                  value={eachStatus.analytics_flag}
                  disabled={true}
                />
              </div>

              <div>
                <Label>Status</Label>
                <ToggleSwitchInput
                  label="Enable Active"
                  defaultChecked={!!eachStatus.status}
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
            <h2 className="text-xl font-semibold text-gray-800">Edit Status</h2>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <Label>
                  Status Code<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={updateFormData.status_code}
                  onChange={handleChange('status_code')}
                  onKeyUp={() => handleRemove('status_code')}
                  error={!!errors.status_code}
                  hint={errors.status_code}
                />
              </div>
              <div>
                <Label>
                  Status Name<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={updateFormData.status_name}
                  onChange={handleChange('status_name')}
                  onKeyUp={() => handleRemove('status_name')}
                  error={!!errors.status_name}
                  hint={errors.status_name}
                />
              </div>
              <div>
                <Label>
                  Entity Type<span className="text-error-500">*</span>
                </Label>
                <SingleSelectInput
                  options={entityTypeData}
                  valueKey="value"
                  value={updateFormData.entity_type}
                  getOptionLabel={(item) => `${item.value}`}
                  onSingleSelectChange={(val) => {
                    handleRemove('entity_type')
                    setUpdateFormData((prev) => ({
                      ...prev,
                      entity_type: val,
                    }))
                  }}
                  error={!!errors.entity_type}
                  hint={errors.entity_type}
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
                <Label>Analytics Flag</Label>
                <SingleSelectInput
                  options={analyticsFlagData}
                  valueKey="value"
                  value={updateFormData.analytics_flag}
                  getOptionLabel={(item) => `${item.value}`}
                  onSingleSelectChange={(val) => {
                    handleRemove('analytics_flag')
                    setUpdateFormData((prev) => ({
                      ...prev,
                      analytics_flag: val,
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
