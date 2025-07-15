import React, { useState, useEffect, useMemo} from 'react'
import http from '../../../lib/http'
import provideUtility from '../../../utils/toast'
import Spinner from '../../../components/ui/loading/spinner'
import AdvancedDataTable from '../../../components/ui/dataTable'
import Button from '../../../components/ui/button/Button'
import BaseModal from '../../../components/ui/modal'
import Label from '../../../components/form/Label'
import Input from '../../../components/form/input/InputField'
import SingleSelectInput from '../../../components/form/form-elements/SelectInputs'
import ToggleSwitchInput from '../../../components/form/form-elements/ToggleSwitch'
import { TableColumn } from 'react-data-table-component'
import { showConfirm } from '../../../utils/alert'
import Swal from 'sweetalert2'

interface RowData {
  employee_code: string
  employee_name: string
  email: string
  phone_number: string
  dob:string
  gender:string
  nationality:string
  address:string
  department:string
  job_title:string
  employee_type:string
  shift:string
  hire_date:string
  salary:string
  currency:string
  is_supervisor: any
  status: number
  [key: string]: any
}

interface Errors {
  employee_code?: string
  employee_name?: string
  email?: any
  is_supervisor?: any
  phone_number?: string
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
    selector: (row: RowData) => row.employee_code,
    sortable: true,
  },
  {
    name: 'Name',
    selector: (row: RowData) => row.employee_name,
    sortable: true,
  },
  {
    name: 'Email',
    selector: (row: RowData) => row.email || '-',
    sortable: true,
  },
  {
    name: 'Phone Number',
    selector: (row: RowData) => row.phone_number || '-',
    sortable: true,
  },
  {
    name: 'Date Of Birth',
    selector: (row: RowData) => row.dob || '-',
    sortable: true,
  },
  {
    name: 'Gender',
    selector: (row: RowData) => row.gender || '-',
    sortable: true,
  },
  {
    name: 'Nationality',
    selector: (row: RowData) => row.nationality || '-',
    sortable: true,
  },
  {
    name: 'Address',
    selector: (row: RowData) => row.address || '-',
    sortable: true,
  },
  {
    name: 'Job Title',
    selector: (row: RowData) => row.job_title || '-',
    sortable: true,
  },
  {
    name: 'Employee Type',
    selector: (row: RowData) => row.employee_type || '-',
    sortable: true,
  },
  {
    name: 'Is Supervisor',
    cell: (row: RowData) => (
      <span
        className={`px-2 py-1 text-xs font-semibold rounded-full ${
          row.is_supervisor === 1
            ? 'bg-green-100 text-green-700'
            : 'bg-red-100 text-red-700'
        }`}
      >
        {row.is_supervisor === 0 ? 'No' : 'Yes'}
      </span>
    ),
    sortable: true,
  },
  {
    name: 'Shift',
    selector: (row: RowData) => row.shift || '-',
    sortable: true,
  },
  {
    name: 'Hire Date',
    selector: (row: RowData) => row.hire_date || '-',
    sortable: true,
  },
  {
    name: 'Salary',
    selector: (row: RowData) =>
      row.salary != null && row.currency
        ? `${row.salary} ${row.currency}`
        : '-',
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
  const [employeeLists, setEmployeeLists] = useState<any>([])
  const [employee, setEmployee] = useState<any>({})
  const [isPageLoading, setIsPageLoading] = useState(false)
  const [isLoading, setIsLoading] = useState(false)
  const { showToast } = provideUtility()

  const genderData = [
    { id: 1, value: 'Male' },
    { id: 2, value: 'Female' },
    { id: 3, value: 'Other' },

  ]

  const supervisorData = [
    { id: 0, value: 'No' },
    { id: 1, value: 'Yes' },
  ]

  const employmentTypeData = [
    { id: 1, value: 'Full Time' },
    { id: 2, value: 'Part Time' },
    { id: 3, value: 'Contract' },
    { id: 4, value: 'Temporary' },
  ]

  const shiftData = [
    { id: 1, value: 'Day Shift' },
    { id: 2, value: 'Night Shift' },
    { id: 3, value: 'Rotating Shift' },
  ]

  const [formData, setFormData] = useState({
    employee_code: '',
    employee_name: '',
    email: '',
    phone_number: '',
    dob: '',
    gender: '',
    nationality: '',
    address: '',
    department_id: '',
    job_title: '',
    employment_type: '',
    shift: '',
    hire_date: '',
    salary: '',
    currency: '',
    is_supervisor:0,
    status: 1
  })

  const [updateFormData, setUpdateFormData] = useState({
    id: '',
    employee_code: '',
    employee_name: '',
    email: '',
    phone_number: '',
    dob: '',
    gender: '',
    nationality: '',
    address: '',
    department_id: '',
    job_title: '',
    employment_type: '',
    shift: '',
    hire_date: '',
    salary: '',
    currency: '',
    is_supervisor:'',
    status: '',
  })

  useEffect(() => {
    setIsPageLoading(true)
    fetchEmployeeLists()
  }, [])

  const fetchEmployeeLists = async () => {
    try {
      setIsPageLoading(true)
      const res = await http.fetchDataWithToken('employees')
      console.log(res)
      setEmployeeLists(res.data?.data || [])
    } catch (err) {
      setIsPageLoading(false)
      console.error('Failed to fetch Employee lists:', err)
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
    setEmployee(employeeLists?.find((x:any) => x.id === row.id))
    setIsViewOpen(true)
  }

  const handleCreate = () => {
    setErrors({
        employee_code: '',
        employee_name: '',
        email: '',
        phone_number: ''
    })
    setIsCreateOpen(true)
  }

  const handleEdit = (row: any) => {
    setIsUpdateOpen(true)
    setErrors({
      employee_code: '',
      employee_name: '',
      email: '',
      phone_number: '',
      is_supervisor:''
    })
    const employee_data = employeeLists.find((x: any) => x.id === row.id)
    if (employee_data) {
      setUpdateFormData({
        id: employee_data.id,
        employee_code: employee_data.employee_code,
        employee_name: employee_data.employee_name,
        email: employee_data.email,
        phone_number: employee_data.phone_number,
        dob: employee_data.dob,
        gender: employee_data.gender,
        nationality: employee_data.nationality,
        address: employee_data.address,
        department_id: employee_data.department_id,
        job_title: employee_data.job_title,
        employment_type: employee_data.employment_type,
        shift: employee_data.shift,
        is_supervisor: employee_data.is_supervisor,
        hire_date: employee_data.hire_date,
        salary: employee_data.salary,
        currency: employee_data.currency,
        status: employee_data.status,
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
      const response = await http.deleteDataWithToken(`/employees/${row.id}`)
      console.log(response)
      if(response.status == true){
        Swal.fire({
          title: 'Deleted!',
          text: 'Employee has been deleted.',
          icon: 'success',
        })
        fetchEmployeeLists()
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
        '/employees',
        formData
      )
      if(response.status === true){
          setIsCreateOpen(false)
          showToast('', 'Create Employee successful', 'top-right', 'success')
          setFormData({
            employee_code: '',
            employee_name: '',
            email: '',
            phone_number: '',
            dob: '',
            gender: '',
            nationality: '',
            address: '',
            department_id: '',
            job_title: '',
            employment_type: '',
            shift: '',
            hire_date: '',
            salary: '',
            currency: '',
            is_supervisor:0,
            status: 1,
          })
          fetchEmployeeLists()
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
        showToast('', 'Create Employee failed!', 'top-right', 'error')
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
        `/employees/${updateFormData.id}`,
        updateFormData
      )
      if (response.status === true) {
        setIsUpdateOpen(false)
        showToast(
          '',
          'Update Employee successful',
          'top-right',
          'success'
        )
        setUpdateFormData({
          id: '',
          employee_code: '',
          employee_name: '',
          email: '',
          phone_number: '',
          dob: '',
          gender: '',
          nationality: '',
          address: '',
          department_id: '',
          job_title: '',
          employment_type: '',
          shift: '',
          hire_date: '',
          salary: '',
          is_supervisor:'',
          currency: '',
          status: '',
        })
        fetchEmployeeLists()
      } else {
        showToast('', 'Something went wrong!.', 'top-right', 'error')
      }
    } catch (err: any) {
      if (err?.status === 422) {
        showToast('', err?.message, 'top-right', 'error')
        const apiErrors: Errors = err?.errors
        setErrors(apiErrors)
      } else {
        showToast('', 'Update Employee failed!', 'top-right', 'error')
      }
      console.error(err)
    } finally {
      setIsLoading(false)
    }
  }

  const [filterText, setFilterText] = React.useState('')
    const filteredData = useMemo(() => {
        if (!filterText) return employeeLists

        return employeeLists.filter((item: any) =>
        Object.values(item).some(
            (val) =>
            val && val.toString().toLowerCase().includes(filterText.toLowerCase())
        )
        )
    }, [filterText, employeeLists])

  return (
    <div className="space-y-10">
      <div className="flex justify-between items-center">
        <h1 className="text-xl font-semibold">Brand Lists</h1>
        <Button variant="primary" size="sm" onClick={handleCreate}>
          Add Employee
        </Button>
      </div>

      <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 md:gap-6 xl:grid-cols-4">
        <div className="rounded-md shadow-lg border border-gray-200 bg-white p-2">
          <div className="rounded-2xl border border-gray-200 bg-white p-5 ">
            <p className="text-gray-500 text-theme-sm dark:text-gray-400">
              Total Employees
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
          <div className="rounded-2xl border border-gray-200 bg-white p-5 ">
            <p className="text-gray-500 text-theme-sm dark:text-gray-400">
              Active Employees
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
              Average Performance
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
          <div className="rounded-2xl border border-gray-200 bg-white p-5 ">
            <p className="text-gray-500 text-theme-sm dark:text-gray-400">
              Certified Employees
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
                placeholder="Search Employees…"
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
              Add New Employee
            </h2>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <Label>
                  Employee Code<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={formData.employee_code}
                  onChange={handleChange('employee_code')}
                  onKeyUp={() => handleRemove('employee_code')}
                  error={!!errors.employee_code}
                  hint={errors.employee_code}
                />
              </div>
              <div>
                <Label>
                  Employee Name<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={formData.employee_name}
                  onChange={handleChange('employee_name')}
                  onKeyUp={() => handleRemove('employee_name')}
                  error={!!errors.employee_name}
                  hint={errors.employee_name}
                />
              </div>
              <div>
                <Label>
                  Email<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={formData.email}
                  onChange={handleChange('email')}
                  onKeyUp={() => handleRemove('email')}
                  error={!!errors.email}
                  hint={errors.email}
                />
              </div>
              <div>
                <Label>
                  Phone Number<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={formData.phone_number}
                  onChange={handleChange('phone_number')}
                  onKeyUp={() => handleRemove('phone_number')}
                  error={!!errors.phone_number}
                  hint={errors.phone_number}
                />
              </div>
              <div>
                <Label>Date Of Birth</Label>
                <Input
                  type="date"
                  value={formData.dob}
                  onChange={handleChange('dob')}
                  onKeyUp={() => handleRemove('dob')}
                />
              </div>
              <div>
                <Label>Gender</Label>
                <SingleSelectInput
                  options={genderData}
                  valueKey="value"
                  value={formData.gender}
                  getOptionLabel={(item) => `${item.value}`}
                  onSingleSelectChange={(val) => {
                    handleRemove('gender')
                    setFormData((prev) => ({
                      ...prev,
                      gender: val,
                    }))
                  }}
                />
              </div>
              <div>
                <Label>Department</Label>
                <Input
                  type="text"
                  value={formData.department_id}
                  disabled={true}
                />
              </div>
              <div>
                <Label>Job Title</Label>
                <Input
                  type="text"
                  value={formData.job_title}
                  onChange={handleChange('job_title')}
                  onKeyUp={() => handleRemove('job_title')}
                />
              </div>
              <div>
                <Label>Hire Date</Label>
                <Input
                  type="date"
                  value={formData.hire_date}
                  onChange={handleChange('hire_date')}
                  onKeyUp={() => handleRemove('hire_date')}
                />
              </div>
              <div>
                <Label>Employment Type</Label>
                <SingleSelectInput
                  options={employmentTypeData}
                  valueKey="value"
                  value={formData.employment_type}
                  getOptionLabel={(item) => `${item.value}`}
                  onSingleSelectChange={(val) => {
                    handleRemove('employment_type')
                    setFormData((prev) => ({
                      ...prev,
                      employment_type: val,
                    }))
                  }}
                />
              </div>
              <div>
                <Label>Shift</Label>
                <SingleSelectInput
                  options={shiftData}
                  valueKey="value"
                  value={formData.shift}
                  getOptionLabel={(item) => `${item.value}`}
                  onSingleSelectChange={(val) => {
                    handleRemove('shift')
                    setFormData((prev) => ({
                      ...prev,
                      shift: val,
                    }))
                  }}
                />
              </div>
              <div>
                <Label>Is Supervisor</Label>
                <SingleSelectInput
                  options={supervisorData}
                  valueKey="id"
                  value={formData.is_supervisor}
                  getOptionLabel={(item) => `${item.value}`}
                  onSingleSelectChange={(val) => {
                    handleRemove('is_supervisor')
                    setFormData((prev: any) => ({
                      ...prev,
                      is_supervisor: val,
                    }))
                  }}
                  error={!!errors.is_supervisor}
                  hint={errors.is_supervisor}
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
            <h2 className="text-xl font-semibold text-gray-800">Employee</h2>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <Label>
                  Employee Code<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={employee.employee_code}
                  disabled={true}
                />
              </div>
              <div>
                <Label>
                  Employee Name<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={employee.employee_name}
                  disabled={true}
                />
              </div>
              <div>
                <Label>
                  Email<span className="text-error-500">*</span>
                </Label>
                <Input type="text" value={employee.email} disabled={true} />
              </div>
              <div>
                <Label>
                  Phone Number<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={employee.phone_number}
                  disabled={true}
                />
              </div>
              <div>
                <Label>Date Of Birth</Label>
                <Input type="date" value={employee.dob} disabled={true} />
              </div>
              <div>
                <Label>Gender</Label>
                <Input type="date" value={employee.gender} disabled={true} />
              </div>
              <div>
                <Label>Department</Label>
                <Input
                  type="text"
                  value={employee.department_id}
                  disabled={true}
                />
              </div>
              <div>
                <Label>Job Title</Label>
                <Input type="text" value={employee.job_title} disabled={true} />
              </div>
              <div>
                <Label>Hire Date</Label>
                <Input type="text" value={formData.hire_date} disabled={true} />
              </div>
              <div>
                <Label>Employment Type</Label>
                <Input
                  type="date"
                  value={employee.employment_type}
                  disabled={true}
                />
              </div>
              <div>
                <Label>Shift</Label>
                <Input type="date" value={employee.shift} disabled={true} />
              </div>
              <div>
                <Label>Is Supervisor</Label>
                <Input type="date" value={employee.is_supervisor} disabled={true} />
              </div>
              <div>
                <Label>Status</Label>
                <ToggleSwitchInput
                  label="Enable Active"
                  defaultChecked={!!employee.status}
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
              Edit Employee
            </h2>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <Label>
                  Employee Code<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={updateFormData.employee_code}
                  onChange={handleChange('employee_code')}
                  onKeyUp={() => handleRemove('employee_code')}
                  error={!!errors.employee_code}
                  hint={errors.employee_code}
                />
              </div>
              <div>
                <Label>
                  Employee Name<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={updateFormData.employee_name}
                  onChange={handleChange('employee_name')}
                  onKeyUp={() => handleRemove('employee_name')}
                  error={!!errors.employee_name}
                  hint={errors.employee_name}
                />
              </div>
              <div>
                <Label>
                  Email<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={updateFormData.email}
                  onChange={handleChange('email')}
                  onKeyUp={() => handleRemove('email')}
                  error={!!errors.email}
                  hint={errors.email}
                />
              </div>
              <div>
                <Label>
                  Phone Number<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={updateFormData.phone_number}
                  onChange={handleChange('phone_number')}
                  onKeyUp={() => handleRemove('phone_number')}
                  error={!!errors.phone_number}
                  hint={errors.phone_number}
                />
              </div>
              <div>
                <Label>Date Of Birth</Label>
                <Input
                  type="date"
                  value={updateFormData.dob}
                  onChange={handleChange('dob')}
                  onKeyUp={() => handleRemove('dob')}
                />
              </div>
              <div>
                <Label>Gender</Label>
                <SingleSelectInput
                  options={genderData}
                  valueKey="value"
                  value={updateFormData.gender}
                  getOptionLabel={(item) => `${item.value}`}
                  onSingleSelectChange={(val) => {
                    handleRemove('gender')
                    setUpdateFormData((prev) => ({
                      ...prev,
                      gender: val,
                    }))
                  }}
                />
              </div>
              <div>
                <Label>Department</Label>
                <Input
                  type="text"
                  value={updateFormData.department_id}
                  disabled={true}
                />
              </div>
              <div>
                <Label>Job Title</Label>
                <Input
                  type="text"
                  value={updateFormData.job_title}
                  onChange={handleChange('job_title')}
                  onKeyUp={() => handleRemove('job_title')}
                />
              </div>
              <div>
                <Label>Hire Date</Label>
                <Input
                  type="text"
                  value={updateFormData.hire_date}
                  onChange={handleChange('hire_date')}
                  onKeyUp={() => handleRemove('hire_date')}
                />
              </div>
              <div>
                <Label>Employment Type</Label>
                <SingleSelectInput
                  options={employmentTypeData}
                  valueKey="value"
                  value={updateFormData.employment_type}
                  getOptionLabel={(item) => `${item.value}`}
                  onSingleSelectChange={(val) => {
                    handleRemove('employment_type')
                    setUpdateFormData((prev) => ({
                      ...prev,
                      employment_type: val,
                    }))
                  }}
                />
              </div>
              <div>
                <Label>Shift</Label>
                <SingleSelectInput
                  options={shiftData}
                  valueKey="value"
                  value={updateFormData.shift}
                  getOptionLabel={(item) => `${item.value}`}
                  onSingleSelectChange={(val) => {
                    handleRemove('shift')
                    setUpdateFormData((prev) => ({
                      ...prev,
                      shift: val,
                    }))
                  }}
                />
              </div>
              <div>
                <Label>Is Supervisor</Label>
                <SingleSelectInput
                  options={supervisorData}
                  valueKey="id"
                  value={updateFormData.is_supervisor}
                  getOptionLabel={(item) => `${item.value}`}
                  onSingleSelectChange={(val) => {
                    handleRemove('is_supervisor')
                    setUpdateFormData((prev: any) => ({
                      ...prev,
                      is_supervisor: val,
                    }))
                  }}
                  error={!!errors.is_supervisor}
                  hint={errors.is_supervisor}
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
