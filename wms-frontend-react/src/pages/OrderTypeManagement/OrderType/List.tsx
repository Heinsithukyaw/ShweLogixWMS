import React, { useState, useEffect, useMemo} from 'react'
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
  order_type_code: string
  order_type_name: string
  direction: string
  priority_level: string
  default_workflow:string
  status: number
  [key: string]: any
}

interface Errors {
  order_type_code?: string
  order_type_name?: string
  direction?: string
  priority_level?: string
  default_workflow?: string
  status?:any
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
    selector: (row: RowData) => row.order_type_code,
    sortable: true,
  },
  {
    name: 'Name',
    selector: (row: RowData) => row.order_type_name,
    sortable: true,
  },
  {
    name: 'Direction',
    selector: (row: RowData) => row.direction || '-',
    sortable: true,
  },
  {
    name: 'Priority Level',
    selector: (row: RowData) => row.priority_level || '-',
    sortable: true,
  },
  {
    name: 'Default Workflow',
    selector: (row: RowData) => row.default_workflow || '-',
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
  const [orderTypeLists, setOrderTypeLists] = useState<any>([])
  const [orderType, setOrderType] = useState<any>({})
  const [isPageLoading, setIsPageLoading] = useState(false)
  const [isLoading, setIsLoading] = useState(false)
  const { showToast } = provideUtility()

  const directionData = [
    { id: 1, value: 'Inbound' },
    { id: 2, value: 'Outbound' },
    { id: 3, value: 'Internal' },

  ]

  const priorityLevelData = [
    { id: 1, value: 'Low' },
    { id: 2, value: 'Medium' },
    { id: 3, value: 'High' },
  ]

  const [formData, setFormData] = useState({
    order_type_code: '',
    order_type_name: '',
    direction:'',
    priority_level:'',
    default_workflow:'',
    description:'',
    status: 1
  })

  const [updateFormData, setUpdateFormData] = useState({
    id: '',
    order_type_code: '',
    order_type_name: '',
    direction:'',
    priority_level:'',
    default_workflow:'',
    description:'',
    status: '',
  })

  useEffect(() => {
    setIsPageLoading(true)
    fetchOrderTypeLists()
  }, [])

  const fetchOrderTypeLists = async () => {
    try {
      setIsPageLoading(true)
      const res = await http.fetchDataWithToken('order-types')
      console.log(res)
      setOrderTypeLists(res.data?.data || [])
    } catch (err) {
      setIsPageLoading(false)
      console.error('Failed to fetch Order Type lists:', err)
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
    const is_active = checked?1:0
    if(isCreateOpen){
      setFormData((prev: any) => ({
        ...prev,
        status: is_active,
      }))
    }else{
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
    setOrderType(orderTypeLists?.find((x:any) => x.id === row.id))
    setIsViewOpen(true)
  }

  const handleCreate = () => {
    setErrors({
      order_type_code: '',
      order_type_name: '',
      direction: '',
      priority_level: '',
      default_workflow: '',
      status:''
    })
    setIsCreateOpen(true)
  }

  const handleEdit = (row: any) => {
    setIsUpdateOpen(true)
    setErrors({
      order_type_code: '',
      order_type_name: '',
      direction: '',
      priority_level: '',
      default_workflow: '',
      status: '',
    })
    const order_type_data = orderTypeLists.find((x: any) => x.id === row.id)
    if (order_type_data) {
      setUpdateFormData({
        id: order_type_data.id,
        order_type_code: order_type_data.order_type_code,
        order_type_name: order_type_data.order_type_name,
        direction: order_type_data.direction,
        priority_level: order_type_data.priority_level,
        default_workflow: order_type_data.default_workflow,
        description: order_type_data.description,
        status: order_type_data.status,
      })
    }
  }

  const handleDelete = async (row:any) => {
    const confirmed = await showConfirm(
      'Are you sure?',
      'This action cannot be undone.'
    )

    if (!confirmed) return

    try {
      const response = await http.deleteDataWithToken(`/order-types/${row.id}`)
      console.log(response)
      if(response.status == true){
        Swal.fire({
          title: 'Deleted!',
          text: 'Employee has been deleted.',
          icon: 'success',
        })
        fetchOrderTypeLists()
      }else{
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

  const handleChange = (field: string) => (e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement>) => {
    const value = e.target.value
    if(isCreateOpen){
      setFormData((prev) => ({
        ...prev,
        [field]: value,
      }))
    }else{
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
      const response = await http.postDataWithToken(
        '/order-types',
        formData
      )
      if(response.status === true){
          setIsCreateOpen(false)
          showToast('', 'Create Employee successful', 'top-right', 'success')
          setFormData({
            order_type_code: '',
            order_type_name: '',
            direction: '',
            priority_level: '',
            default_workflow: '',
            description: '',
            status: 1,
          })
          fetchOrderTypeLists()
        }else{
        
          showToast(
            '',
            'Something went wrong!.',
            'top-right',
            'error'
          )
      }
    } catch (err:any) {
      if(err?.status === 422){
        showToast('', err?.data.message, 'top-right', 'error')
        const apiErrors: Errors = err?.data.errors
        setErrors(apiErrors)
      }else{
        showToast('', 'Create Order Type failed!', 'top-right', 'error')
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
        `/order-types/${updateFormData.id}`,
        updateFormData
      )
      if (response.status === true) {
        setIsUpdateOpen(false)
        showToast(
          '',
          'Update Order Type successful',
          'top-right',
          'success'
        )
        setUpdateFormData({
          id: '',
          order_type_code: '',
          order_type_name: '',
          direction:'',
          priority_level:'',
          default_workflow:'',
          description:'',
          status: '',
        })
        fetchOrderTypeLists()
      } else {
        showToast('', 'Something went wrong!.', 'top-right', 'error')
      }
    } catch (err: any) {
      if (err?.status === 422) {
        showToast('', err?.message, 'top-right', 'error')
        const apiErrors: Errors = err?.errors
        setErrors(apiErrors)
      } else {
        showToast('', 'Update Order Type failed!', 'top-right', 'error')
      }
      console.error(err)
    } finally {
      setIsLoading(false)
    }
  }

  const [filterText, setFilterText] = React.useState('')
    const filteredData = useMemo(() => {
        if (!filterText) return orderTypeLists

        return orderTypeLists.filter((item: any) =>
        Object.values(item).some(
            (val) =>
            val && val.toString().toLowerCase().includes(filterText.toLowerCase())
        )
        )
    }, [filterText, orderTypeLists])

  return (
    <div className="space-y-10">
      <div className="flex justify-between items-center">
        <h1 className="text-xl font-semibold">Order Type Lists</h1>
        <Button variant="primary" size="sm" onClick={handleCreate}>
          Add Order Type
        </Button>
      </div>

      <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 md:gap-6 xl:grid-cols-4">
        <div className="rounded-md shadow-lg border border-gray-200 bg-white p-2">
          <div className="rounded-2xl border border-gray-200 bg-white p-5 ">
            <p className="text-gray-500 text-theme-sm dark:text-gray-400">
              Total Order Types
            </p>
            <div className="flex items-end justify-between mt-3">
              <div className="">
                <h4 className="text-2xl font-bold text-gray-800 dark:text-white/90">
                  {orderTypeLists.length ?? 0}
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
              Active Order Types
            </p>
            <div className="flex items-end justify-between mt-3">
              <div className="">
                <h4 className="text-2xl font-bold text-gray-800 dark:text-white/90">
                  {orderTypeLists.filter((x: any) => x.status == 1).length}
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
              Avg. Processing Times
            </p>
            <div className="flex items-end justify-between mt-3">
              <div className="">
                <h4 className="text-2xl font-bold text-gray-800 dark:text-white/90">
                  0hrs
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
              Integrated Systems
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
                placeholder="Search Order Types…"
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
              Add New Order Type
            </h2>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <Label>
                  Order Type Code<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={formData.order_type_code}
                  onChange={handleChange('order_type_code')}
                  onKeyUp={() => handleRemove('order_type_code')}
                  error={!!errors.order_type_code}
                  hint={errors.order_type_code}
                />
              </div>
              <div>
                <Label>
                  Order Type Name<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={formData.order_type_name}
                  onChange={handleChange('order_type_name')}
                  onKeyUp={() => handleRemove('order_type_name')}
                  error={!!errors.order_type_name}
                  hint={errors.order_type_name}
                />
              </div>
              <div>
                <Label>Dierection</Label>
                <SingleSelectInput
                  options={directionData}
                  valueKey="value"
                  value={formData.direction}
                  getOptionLabel={(item) => `${item.value}`}
                  onSingleSelectChange={(val) => {
                    handleRemove('direction')
                    setFormData((prev) => ({
                      ...prev,
                      direction: val,
                    }))
                  }}
                  error={!!errors.direction}
                  hint={errors.direction}
                />
              </div>
              <div>
                <Label>Priority Level</Label>
                <SingleSelectInput
                  options={priorityLevelData}
                  valueKey="value"
                  value={formData.priority_level}
                  getOptionLabel={(item) => `${item.value}`}
                  onSingleSelectChange={(val) => {
                    handleRemove('priority_level')
                    setFormData((prev) => ({
                      ...prev,
                      priority_level: val,
                    }))
                  }}
                  error={!!errors.direction}
                  hint={errors.direction}
                />
              </div>
              <div>
                <Label>
                  Default Workflow<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={formData.default_workflow}
                  onChange={handleChange('default_workflow')}
                  onKeyUp={() => handleRemove('default_workflow')}
                  error={!!errors.default_workflow}
                  hint={errors.default_workflow}
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
            <h2 className="text-xl font-semibold text-gray-800">Order Type</h2>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <Label>
                  Order Type Code<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={orderType.order_type_code}
                  disabled={true}
                />
              </div>
              <div>
                <Label>
                  Order Type Name<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={orderType.order_type_name}
                  disabled={true}
                />
              </div>
              <div>
                <Label>Direction</Label>
                <Input
                  type="text"
                  value={orderType.direction}
                  disabled={true}
                />
              </div>
              <div>
                <Label>Priority Level</Label>
                <Input
                  type="text"
                  value={orderType.priority_level}
                  disabled={true}
                />
              </div>
              <div>
                <Label>
                  Default Workflow<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={orderType.default_workflow}
                  disabled={true}
                />
              </div>
              <div className="col-span-full">
                <Label>Description</Label>
                <TextAreaInput value={orderType.description} disabled={true} />
              </div>
              <div>
                <Label>Status</Label>
                <ToggleSwitchInput
                  label="Enable Active"
                  defaultChecked={!!orderType.status}
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
              Edit Order Type
            </h2>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <Label>
                  Order Type Code<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={updateFormData.order_type_code}
                  onChange={handleChange('order_type_code')}
                  onKeyUp={() => handleRemove('order_type_code')}
                  error={!!errors.order_type_code}
                  hint={errors.order_type_code}
                />
              </div>
              <div>
                <Label>
                  Order Type Name<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={updateFormData.order_type_name}
                  onChange={handleChange('order_type_name')}
                  onKeyUp={() => handleRemove('order_type_name')}
                  error={!!errors.order_type_name}
                  hint={errors.order_type_name}
                />
              </div>
              <div>
                <Label>Dierection</Label>
                <SingleSelectInput
                  options={directionData}
                  valueKey="value"
                  value={updateFormData.direction}
                  getOptionLabel={(item) => `${item.value}`}
                  onSingleSelectChange={(val) => {
                    handleRemove('direction')
                    setUpdateFormData((prev) => ({
                      ...prev,
                      direction: val,
                    }))
                  }}
                  error={!!errors.direction}
                  hint={errors.direction}
                />
              </div>
              <div>
                <Label>Priority Level</Label>
                <SingleSelectInput
                  options={priorityLevelData}
                  valueKey="value"
                  value={updateFormData.priority_level}
                  getOptionLabel={(item) => `${item.value}`}
                  onSingleSelectChange={(val) => {
                    handleRemove('priority_level')
                    setUpdateFormData((prev) => ({
                      ...prev,
                      priority_level: val,
                    }))
                  }}
                  error={!!errors.direction}
                  hint={errors.direction}
                />
              </div>
              <div>
                <Label>
                  Order Type Code<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={updateFormData.default_workflow}
                  onChange={handleChange('default_workflow')}
                  onKeyUp={() => handleRemove('default_workflow')}
                  error={!!errors.default_workflow}
                  hint={errors.default_workflow}
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


