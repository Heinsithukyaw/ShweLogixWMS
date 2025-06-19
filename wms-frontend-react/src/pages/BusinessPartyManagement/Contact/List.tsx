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
  contact_code: string
  contact_name: string
  business_party_code: string
  business_party_name: string
  designation: string
  department: string
  phone_number:string
  email:string
  address:string
  country:string
  preferred_contact_method:string
  notes:string
  status: number
  [key: string]: any
}

interface Errors {
  contact_code?: string
  contact_name?: string
  business_party_id?: string
  business_party_name?: string
  designation?: string
  phone_number?: string
  email?: string
  preferred_contact_method?: string
}

const columns: TableColumn<RowData>[] = [
  {
    name: 'No.',
    selector: (_row: any, index: any) => index + 1,
    width: '70px',
    sortable: false,
  },
  {
    name: 'Contact Code',
    selector: (row: RowData) => row.contact_code,
    sortable: true,
  },
  {
    name: 'Contact Name',
    selector: (row: RowData) => row.contact_name,
    sortable: true,
  },
  {
    name: 'Business Party Code',
    selector: (row: RowData) => row.business_party_code || '-',
    sortable: true,
  },
  {
    name: 'Business Party Name',
    selector: (row: RowData) => row.business_party_name || '-',
    sortable: true,
  },
  {
    name: 'Designation',
    selector: (row: RowData) => row.designation || '-',
    sortable: true,
  },
  {
    name: 'Department',
    selector: (row: RowData) => row.department || '-',
    sortable: true,
  },
  {
    name: 'Phone Number',
    selector: (row: RowData) => row.phone_number || '-',
    sortable: true,
  },
  {
    name: 'Email Address',
    selector: (row: RowData) => row.email || '-',
    sortable: true,
  },
  {
    name: 'Address',
    selector: (row: RowData) => row.address,
    sortable: true,
  },
  {
    name: 'Country',
    selector: (row: RowData) => row.country || '-',
    sortable: true,
  },
  {
    name: 'Preferred Contact',
    selector: (row: RowData) => row.preferred_contact_method,
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
        {row.status === 1 ? 'Active' : 'In Active'}
      </span>
    ),
    sortable: true,
  },
]

function List() {

  const [filterText, setFilterText] = React.useState('')
  const [isCreateOpen, setIsCreateOpen] = useState(false)
  const [isViewOpen, setIsViewOpen] = useState(false)
  const [isUpdateOpen, setIsUpdateOpen] = useState(false)
  const [errors, setErrors] = useState<Errors>({})
  const [isLoading, setIsLoading] = useState(false)
  const [isPageLoading, setIsPageLoading] = useState(false)
  const [businessContactLists, setBusinessContactLists] = useState<any>([])
  const [businessPartyLists, setBusinessPartyLists] = useState<any>([])
  const [businessContact, setBusinessContact] = useState<any>({})
  const { showToast } = provideUtility()

  const departmentData = [
    { id: 1, value: 'Sales' },
    { id: 2, value: 'Purchasing' },
    { id: 3, value: 'Logistics' },
    { id: 4, value: 'Manufacturing' },
    { id: 5, value: 'Transport' },
    { id: 6, value: 'Retail Operations' },
    { id: 7, value: 'Credit Department' },
    { id: 8, value: 'Finance' },
    { id: 9, value: 'Marketing' },
    { id: 10, value: 'Customer Service' },
    { id: 11, value: 'Technical Support' },
    { id: 12, value: 'Operations' },
    { id: 13, value: 'Human Resources' },
    { id: 14, value: 'Adminstration' },
  ]

  const countryData = [
    { id: 1, value: 'USA' },
    { id: 2, value: 'UK' },
    { id: 3, value: 'India' },
    { id: 4, value: 'Singapore' },
    { id: 5, value: 'Germany' },
    { id: 6, value: 'Australia' },
    { id: 7, value: 'France' },
    { id: 8, value: 'China' },
    { id: 9, value: 'Japan' },
    { id: 10, value: 'Canada' },
  ]

  const preferredContactMethodData = [
    { id: 1, value: 'Email' },
    { id: 2, value: 'Phone' },
    { id: 3, value: 'Mail' },
    { id: 4, value: 'In-Person' },
    { id: 5, value: 'Video Call' },
  ]

  const [formData, setFormData] = useState({
    contact_code: '',
    contact_name: '',
    business_party_id: '',
    business_party_name: '',
    designation: '',
    department: '',
    phone_number:'',
    email:'',
    address:'',
    country:'',
    preferred_contact_method:'',
    notes:'',
    status:1
  })
  
  const [updateFormData, setUpdateFormData] = useState({
    id: '',
    contact_code: '',
    contact_name: '',
    business_party_id: '',
    business_party_name: '',
    business_party_code:'',
    designation: '',
    department: '',
    phone_number: '',
    email: '',
    address: '',
    country: '',
    preferred_contact_method: '',
    notes: '',
    status: '',
  })

  const filteredData = useMemo(() => {
  if (!filterText) return businessContactLists

  return businessContactLists.filter((item:any) =>
    Object.values(item).some(
      (val) =>
        val && val.toString().toLowerCase().includes(filterText.toLowerCase())
    )
  )
  }, [filterText, businessContactLists])

  useEffect(() => {
    fetchBusinessContactLists()
    fetchBusinessPartyLists()
  },[])

  const fetchBusinessContactLists = async () => {
      try {
        setIsPageLoading(true)
        const res = await http.fetchDataWithToken('business-contacts')
        console.log(res.data)
        setBusinessContactLists(res.data?.data || [])
      } catch (err) {
        setIsPageLoading(false)
        console.error('Failed to fetch Business Contact lists:', err)
      } finally {
        setIsPageLoading(false)
      }
    }

    const fetchBusinessPartyLists = async () => {
      try {
        setIsPageLoading(true)
        const res = await http.fetchDataWithToken('business-parties')
        console.log(res.data)
        setBusinessPartyLists(res.data?.data || [])
      } catch (err) {
        setIsPageLoading(false)
        console.error('Failed to fetch Business Party lists:', err)
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
      console.log(row)
      setBusinessContact(businessContactLists.find((x:any) => x.id === row.id))
      setIsViewOpen(true)
    }
  
    const handleCreate = () => {
    setErrors({
      contact_code: '',
      contact_name: '',
      business_party_id: '',
      business_party_name: '',
      designation: '',
      phone_number: '',
      email: '',
      preferred_contact_method: '',
    })
      setIsCreateOpen(true)
    }
  
    const handleEdit = (row: any) => {
      setIsUpdateOpen(true)
      setErrors({
        contact_code: '',
        contact_name: '',
        business_party_id: '',
        business_party_name: '',
        designation: '',
        phone_number: '',
        email: '',
        preferred_contact_method: '',
      })
      const business_contact_data = businessContactLists.find((x: any) => x.id === row.id)
  
      if (business_contact_data) {
        setUpdateFormData({
          id: business_contact_data.id || '',
          contact_code: business_contact_data.contact_code || '',
          contact_name: business_contact_data.contact_name || '',
          business_party_id: business_contact_data.business_party_id || '',
          business_party_code: business_contact_data.business_party_code || '',
          business_party_name: business_contact_data.business_party_name || '',
          designation: business_contact_data.designation || '',
          department: business_contact_data.department || '',
          phone_number: business_contact_data.phone_number || '',
          email: business_contact_data.email || '',
          address: business_contact_data.address || '',
          country: business_contact_data.country || '',
          preferred_contact_method:
            business_contact_data.preferred_contact_method || '',
          notes: business_contact_data.notes || '',
          status: business_contact_data.status || '',
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
        const response = await http.deleteDataWithToken(`/business-contacts/${row.id}`)
        console.log(response)
        if(response.status == true){
          Swal.fire({
            title: 'Deleted!',
            text: 'Business Contact has been deleted.',
            icon: 'success',
          })
          fetchBusinessContactLists()
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
          '/business-contacts',
          formData
        )
        if(response.status === true){
            setIsCreateOpen(false)
            showToast('', 'Create Business Party successful', 'top-right', 'success')
            setFormData({
              contact_code: '',
              contact_name: '',
              business_party_id: '',
              business_party_name: '',
              designation: '',
              department: '',
              phone_number: '',
              email: '',
              address: '',
              country: '',
              preferred_contact_method: '',
              notes: '',
              status: 1,
            })
            fetchBusinessContactLists()
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
          showToast('', 'Create Business Contact failed!', 'top-right', 'error')
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
          `/business-contacts/${updateFormData.id}`,
          updateFormData
        )
        if (response.status === true) {
          setIsUpdateOpen(false)
          showToast(
            '',
            'Update Business Contact successful',
            'top-right',
            'success'
          )
          setUpdateFormData({
            id: '',
            contact_code: '',
            contact_name: '',
            business_party_id: '',
            business_party_code:'',
            business_party_name: '',
            designation: '',
            department: '',
            phone_number:'',
            email:'',
            address:'',
            country:'',
            preferred_contact_method:'',
            notes:'',
            status: '',
          })
          fetchBusinessContactLists()
        } else {
          showToast('', 'Something went wrong!.', 'top-right', 'error')
        }
      } catch (err: any) {
        if (err?.status === 422) {
          showToast('', err?.message, 'top-right', 'error')
          const apiErrors: Errors = err?.errors
          setErrors(apiErrors)
        } else {
          showToast('', 'Update Business Party failed!', 'top-right', 'error')
        }
        console.error(err)
      } finally {
        setIsLoading(false)
      }
    }

    const handleGetPartyName = (id: any) => {
      const party = businessPartyLists.find((x: any) => x.id == id)
      if (isCreateOpen) {
        setFormData((prev: any) => ({
          ...prev,
          ['business_party_name']: party?.party_name,
        }))
      } else if (isUpdateOpen) {
        setUpdateFormData((prev: any) => ({
          ...prev,
          ['business_party_name']: party?.party_name,
        }))
      }
    }

  return (
    <>
      <div className="space-y-10">
        <div className="flex justify-between items-center">
          <h1 className="text-xl font-semibold">
            Business Contact Person Lists
          </h1>
          <Button variant="primary" size="sm" onClick={handleCreate}>
            Add Contact Person
          </Button>
        </div>

        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 md:gap-6 xl:grid-cols-4">
          <div className="rounded-md shadow-lg border border-gray-200 bg-white p-2">
            <div className="rounded-2xl border border-gray-200 bg-white p-5 ">
              <p className="text-gray-500 text-theme-sm dark:text-gray-400">
                Total Contacts
              </p>
              <div className="flex items-end justify-between mt-3">
                <div className="">
                  <h4 className="text-2xl font-bold text-gray-800 dark:text-white/90">
                    {businessContactLists?.length || 0}
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
                Email Contacts
              </p>
              <div className="flex items-end justify-between mt-3">
                <div className="">
                  <h4 className="text-2xl font-bold text-gray-800 dark:text-white/90">
                    {businessContactLists?.filter((x: any) => x.email)
                      ?.length || 0}
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
                Phone Contacts
              </p>
              <div className="flex items-end justify-between mt-3">
                <div className="">
                  <h4 className="text-2xl font-bold text-gray-800 dark:text-white/90">
                    {businessContactLists?.filter((x: any) => x.phone_number)
                      ?.length || 0}
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
                Business Partners
              </p>
              <div className="flex items-end justify-between mt-3">
                <div className="">
                  <h4 className="text-2xl font-bold text-gray-800 dark:text-white/90">
                    0
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
        </div>

        <div className="">
          <div className="space-y-10">
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
                      placeholder="Search Contact Person…"
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
                    Add Business Contact Person
                  </h2>
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                      <Label>
                        Contact Code<span className="text-error-500">*</span>
                      </Label>
                      <Input
                        type="text"
                        value={formData.contact_code}
                        onChange={handleChange('contact_code')}
                        onKeyUp={() => handleRemove('contact_code')}
                        error={!!errors.contact_code}
                        hint={errors.contact_code}
                      />
                    </div>
                    <div>
                      <Label>
                        Contact Name<span className="text-error-500">*</span>
                      </Label>
                      <Input
                        type="text"
                        value={formData.contact_name}
                        onChange={handleChange('contact_name')}
                        onKeyUp={() => handleRemove('contact_name')}
                        error={!!errors.contact_name}
                        hint={errors.contact_name}
                      />
                    </div>
                    <div>
                      <Label>
                        Business Party<span className="text-error-500">*</span>
                      </Label>
                      <SingleSelectInput
                        options={businessPartyLists}
                        valueKey="id"
                        value={formData.business_party_id}
                        getOptionLabel={(item) => `${item.party_code}`}
                        onSingleSelectChange={(val) => {
                          handleRemove('business_party_id')
                          handleGetPartyName(val)
                          setFormData((prev) => ({
                            ...prev,
                            business_party_id: val,
                          }))
                        }}
                        error={!!errors.business_party_id}
                        hint={errors.business_party_id}
                      />
                    </div>
                    <div>
                      <Label>Business Party Name</Label>
                      <Input
                        type="text"
                        value={formData.business_party_name}
                        disabled={true}
                      />
                    </div>
                    <div>
                      <Label>Designation</Label>
                      <Input
                        type="text"
                        value={formData.designation}
                        onChange={handleChange('designation')}
                        onKeyUp={() => handleRemove('designation')}
                        error={!!errors.designation}
                        hint={errors.designation}
                      />
                    </div>
                    <div>
                      <Label>Department</Label>
                      <SingleSelectInput
                        options={departmentData}
                        valueKey="value"
                        value={formData.department}
                        getOptionLabel={(item) => `${item.value}`}
                        onSingleSelectChange={(val) => {
                          handleRemove('department')
                          setFormData((prev) => ({
                            ...prev,
                            department: val,
                          }))
                        }}
                      />
                    </div>
                    <div>
                      <Label>Phone Number</Label>
                      <Input
                        type="number"
                        value={formData.phone_number}
                        onChange={handleChange('phone_number')}
                        onKeyUp={() => handleRemove('phone_number')}
                      />
                    </div>
                    <div>
                      <Label>Email</Label>
                      <Input
                        type="email"
                        value={formData.email}
                        onChange={handleChange('email')}
                        onKeyUp={() => handleRemove('email')}
                        error={!!errors.email}
                        hint={errors.email}
                      />
                    </div>
                    <div className="col-span-full">
                      <Label>Address</Label>
                      <TextAreaInput
                        value={formData.address}
                        onChange={(value) =>
                          handleChange('address')({
                            target: { value },
                          } as React.ChangeEvent<any>)
                        }
                      />
                    </div>
                    <div>
                      <Label>Country</Label>
                      <SingleSelectInput
                        options={countryData}
                        valueKey="value"
                        value={formData.country}
                        getOptionLabel={(item) => `${item.value}`}
                        onSingleSelectChange={(val) => {
                          handleRemove('country')
                          setFormData((prev) => ({ ...prev, country: val }))
                        }}
                      />
                    </div>
                    <div>
                      <Label>Preferred Contact Method</Label>
                      <SingleSelectInput
                        options={preferredContactMethodData}
                        valueKey="value"
                        value={formData.preferred_contact_method}
                        getOptionLabel={(item) => `${item.value}`}
                        onSingleSelectChange={(val) => {
                          handleRemove('preferred_contact_method')
                          setFormData((prev) => ({
                            ...prev,
                            preferred_contact_method: val,
                          }))
                        }}
                        error={!!errors.preferred_contact_method}
                        hint={errors.preferred_contact_method}
                      />
                    </div>
                    <div className="col-span-full">
                      <Label>Notes</Label>
                      <TextAreaInput
                        value={formData.notes}
                        onChange={(value) =>
                          handleChange('notes')({
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
                  <h2 className="text-xl font-semibold text-gray-800">
                    Business Contact
                  </h2>
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                      <Label>
                        Contact Code<span className="text-error-500">*</span>
                      </Label>
                      <Input
                        type="text"
                        value={businessContact.contact_code}
                        disabled={true}
                      />
                    </div>
                    <div>
                      <Label>
                        Contact Name<span className="text-error-500">*</span>
                      </Label>
                      <Input
                        type="text"
                        value={businessContact.contact_name}
                        disabled={true}
                      />
                    </div>
                    <div>
                      <Label>
                        Business Party<span className="text-error-500">*</span>
                      </Label>
                      <Input
                        type="text"
                        value={businessContact.business_party_code}
                        disabled={true}
                      />
                    </div>
                    <div>
                      <Label>Business Party Name</Label>
                      <Input
                        type="text"
                        value={businessContact.business_party_name}
                        disabled={true}
                      />
                    </div>
                    <div>
                      <Label>Designation</Label>
                      <Input
                        type="text"
                        value={businessContact.designation}
                        disabled={true}
                      />
                    </div>
                    <div>
                      <Label>Department</Label>
                      <Input
                        type="text"
                        value={businessContact.department}
                        disabled={true}
                      />
                    </div>
                    <div>
                      <Label>Phone Number</Label>
                      <Input
                        type="text"
                        value={businessContact.phone_number}
                        disabled
                      />
                    </div>
                    <div>
                      <Label>Email</Label>
                      <Input
                        type="email"
                        value={businessContact.email}
                        disabled={true}
                      />
                    </div>
                    <div className="col-span-full">
                      <Label>Address</Label>
                      <TextAreaInput
                        value={businessContact.address}
                        disabled={true}
                      />
                    </div>
                    <div>
                      <Label>Country</Label>
                      <Input
                        type="text"
                        value={businessContact.country}
                        disabled={true}
                      />
                    </div>
                    <div>
                      <Label>Preferred Contact Method</Label>
                      <Input
                        type="text"
                        value={businessContact.preferred_contact_method}
                        disabled={true}
                      />
                    </div>
                    <div className="col-span-full">
                      <Label>Notes</Label>
                      <TextAreaInput
                        value={businessContact.notes}
                        disabled={true}
                      />
                    </div>
                    <div>
                      <Label>Status</Label>
                      <ToggleSwitchInput
                        label="Enable Active"
                        defaultChecked={!!businessContact.status}
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
                    Update Business Contact
                  </h2>
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                      <Label>
                        Contact Code<span className="text-error-500">*</span>
                      </Label>
                      <Input
                        type="text"
                        value={updateFormData.contact_code}
                        onChange={handleChange('contact_code')}
                        onKeyUp={() => handleRemove('contact_code')}
                        error={!!errors.contact_code}
                        hint={errors.contact_code}
                      />
                    </div>
                    <div>
                      <Label>
                        Contact Name<span className="text-error-500">*</span>
                      </Label>
                      <Input
                        type="text"
                        value={updateFormData.contact_name}
                        onChange={handleChange('contact_name')}
                        onKeyUp={() => handleRemove('contact_name')}
                        error={!!errors.contact_name}
                        hint={errors.contact_name}
                      />
                    </div>
                    <div>
                      <Label>
                        Business Party<span className="text-error-500">*</span>
                      </Label>
                      <SingleSelectInput
                        options={businessPartyLists}
                        valueKey="id"
                        value={updateFormData.business_party_id}
                        getOptionLabel={(item) => `${item.party_code}`}
                        onSingleSelectChange={(val) => {
                          handleRemove('business_party_id')
                          setUpdateFormData((prev) => ({
                            ...prev,
                            business_party_id: val,
                          }))
                        }}
                        error={!!errors.business_party_id}
                        hint={errors.business_party_id}
                      />
                    </div>
                    <div>
                      <Label>Business Party Name</Label>
                      <Input
                        type="text"
                        value={updateFormData.business_party_name}
                        disabled={true}
                      />
                    </div>
                    <div>
                      <Label>Designation</Label>
                      <Input
                        type="number"
                        value={updateFormData.designation}
                        onChange={handleChange('designation')}
                        onKeyUp={() => handleRemove('designation')}
                        error={!!errors.designation}
                        hint={errors.designation}
                      />
                    </div>
                    <div>
                      <Label>Department</Label>
                      <SingleSelectInput
                        options={departmentData}
                        valueKey="value"
                        value={updateFormData.department}
                        getOptionLabel={(item) => `${item.value}`}
                        onSingleSelectChange={(val) => {
                          handleRemove('department')
                          setUpdateFormData((prev) => ({
                            ...prev,
                            department: val,
                          }))
                        }}
                      />
                    </div>
                    <div>
                      <Label>Phone Number</Label>
                      <Input
                        type="number"
                        value={updateFormData.phone_number}
                        onChange={handleChange('phone_number')}
                        onKeyUp={() => handleRemove('phone_number')}
                      />
                    </div>
                    <div>
                      <Label>Email</Label>
                      <Input
                        type="email"
                        value={updateFormData.email}
                        onChange={handleChange('email')}
                        onKeyUp={() => handleRemove('email')}
                        error={!!errors.email}
                        hint={errors.email}
                      />
                    </div>
                    <div className="col-span-full">
                      <Label>Address</Label>
                      <TextAreaInput
                        value={updateFormData.address}
                        onChange={(value) =>
                          handleChange('address')({
                            target: { value },
                          } as React.ChangeEvent<any>)
                        }
                      />
                    </div>
                    <div>
                      <Label>Country</Label>
                      <SingleSelectInput
                        options={countryData}
                        valueKey="value"
                        value={updateFormData.country}
                        getOptionLabel={(item) => `${item.value}`}
                        onSingleSelectChange={(val) => {
                          handleRemove('country')
                          setFormData((prev) => ({ ...prev, country: val }))
                        }}
                      />
                    </div>
                    <div>
                      <Label>Preferred Contact Method</Label>
                      <SingleSelectInput
                        options={preferredContactMethodData}
                        valueKey="value"
                        value={updateFormData.preferred_contact_method}
                        getOptionLabel={(item) => `${item.value}`}
                        onSingleSelectChange={(val) => {
                          handleRemove('preferred_contact_method')
                          setUpdateFormData((prev) => ({
                            ...prev,
                            preferred_contact_method: val,
                          }))
                        }}
                        error={!!errors.preferred_contact_method}
                        hint={errors.preferred_contact_method}
                      />
                    </div>
                    <div className="col-span-full">
                      <Label>Notes</Label>
                      <TextAreaInput
                        value={updateFormData.notes}
                        onChange={(value) =>
                          handleChange('notes')({
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
        </div>
      </div>
    </>
  )
}

export default List