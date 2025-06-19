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
  carrier_code: string
  carrier_name: string
  contact_person: string
  phone_number: string
  email:string
  address:string
  country:string
  contract_details:string
  payment_terms:string
  service_type:string
  tracking_url:string
  performance_rating:string
  capabilities:string
  creation_date:string
  created_by:string
  last_modified_date:string
  last_modified_by:string
  status: number
  [key: string]: any
}

interface Errors {
  carrier_code?: string
  carrier_name?: string
  contact_person?: string
  phone_number?: string
  email?: string
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
    selector: (row: RowData) => row.carrier_code,
    sortable: true,
  },
  {
    name: 'Name',
    selector: (row: RowData) => row.carrier_name,
    sortable: true,
  },
  {
    name: 'Contact Person',
    selector: (row: RowData) => row.contact_person || '-',
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
    selector: (row: RowData) => row.address || '-',
    sortable: true,
  },
  {
    name: 'Country',
    selector: (row: RowData) => row.country || '-',
    sortable: true,
  },
  {
    name: 'Contract Details',
    selector: (row: RowData) => row.contract_details || '-',
    sortable: true,
  },
  {
    name: 'Payment Terms',
    selector: (row: RowData) => row.payment_terms || '-',
    sortable: true,
  },
  {
    name: 'Service Type',
    selector: (row: RowData) => row.service_type || '-',
    sortable: true,
  },
  {
    name: 'Tracking URL',
    selector: (row: RowData) => row.tracking_url || '-',
    sortable: true,
  },
  {
    name: 'Performance Rating',
    selector: (row: RowData) => row.performance_rating || '-',
    sortable: true,
  },
  {
    name: 'Capabilities',
    selector: (row: RowData) => row.capabilities || '-',
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

const List: React.FC = () => {
  const [isCreateOpen, setIsCreateOpen] = useState(false)
  const [isViewOpen, setIsViewOpen] = useState(false)
  const [isUpdateOpen, setIsUpdateOpen] = useState(false)
  const [errors, setErrors] = useState<Errors>({})
  const [carrierLists, setCarrierLists] = useState<any>([])
  const [carrier, setCarrier] = useState<any>({})
  const [isPageLoading, setIsPageLoading] = useState(false)
  const [isLoading, setIsLoading] = useState(false)
  const { showToast } = provideUtility()

  const serviceTypeData = [
    { id: 1, value: 'Ground' },
    { id: 2, value: 'Air' },
    { id: 3, value: 'Express' },

  ]

  const performanceRatingData = [
    { id: 1, value: 'Fair' },
    { id: 2, value: 'Good' },
    { id: 3, value: 'Excellent' },
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

  const paymentTermsData = [
    { id: 1, value: 'Net 15' },
    { id: 2, value: 'Net 30' },
    { id: 3, value: 'Net 45' },
    { id: 4, value: 'Net 60' },
    { id: 5, value: 'Net 90' },
    { id: 6, value: 'Cash On Delivery' },
    { id: 7, value: 'Advance Payment' },
    { id: 8, value: 'N/A' },
  ]

  const [formData, setFormData] = useState({
    carrier_code: '',
    carrier_name: '',
    contact_person:  '',
    phone_number:  '',
    email: '',
    address: '',
    country: '',
    contract_details: '',
    payment_terms: '',
    service_type: '',
    tracking_url: '',
    performance_rating: '',
    capabilities: '',
    creation_date: '',
    created_by: '',
    last_modified_date: '',
    last_modified_by: '',
    status: 1
  })

  const [updateFormData, setUpdateFormData] = useState({
    id: '',
    carrier_code: '',
    carrier_name: '',
    contact_person: '',
    phone_number: '',
    email: '',
    address: '',
    country: '',
    contract_details: '',
    payment_terms: '',
    service_type: '',
    tracking_url: '',
    performance_rating: '',
    capabilities: '',
    creation_date: '',
    created_by: '',
    last_modified_date: '',
    last_modified_by: '',
    status: '',
  })

  useEffect(() => {
    setIsPageLoading(true)
    fetchShippingCarrierLists()
  }, [])

  const fetchShippingCarrierLists = async () => {
    try {
      setIsPageLoading(true)
      const res = await http.fetchDataWithToken('shipping-carriers')
      console.log(res)
      setCarrierLists(res.data?.data || [])
    } catch (err) {
      setIsPageLoading(false)
      console.error('Failed to fetch Shipping Carrier lists:', err)
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
    setCarrier(carrierLists?.find((x:any) => x.id === row.id))
    setIsViewOpen(true)
  }

  const handleCreate = () => {
    setErrors({
        carrier_code: '',
        carrier_name: '',
        contact_person: '',
        phone_number: '',
        email: '',
        status:''
    })
    setIsCreateOpen(true)
  }

  const handleEdit = (row: any) => {
    setIsUpdateOpen(true)
    setErrors({
      carrier_code: '',
      carrier_name: '',
      contact_person: '',
      phone_number: '',
      email: '',
      status: '',
    })
    const carrier_data = carrierLists.find((x: any) => x.id === row.id)
    if (carrier_data) {
      setUpdateFormData({
        id: carrier_data.id,
        carrier_code: carrier_data.carrier_code,
        carrier_name: carrier_data.carrier_name,
        contact_person: carrier_data.contact_person,
        phone_number: carrier_data.phone_number,
        email: carrier_data.email,
        address: carrier_data.address,
        country: carrier_data.country,
        contract_details: carrier_data.contract_details,
        payment_terms: carrier_data.payment_terms,
        service_type: carrier_data.service_type,
        tracking_url: carrier_data.tracking_url,
        performance_rating: carrier_data.performance_rating,
        capabilities: carrier_data.capabilities,
        creation_date: carrier_data.creation_date,
        created_by: carrier_data.created_by,
        last_modified_date: carrier_data.last_modified_date,
        last_modified_by: carrier_data.last_modified_by,
        status: carrier_data.status,
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
      const response = await http.deleteDataWithToken(`/shipping-carriers/${row.id}`)
      console.log(response)
      if(response.status == true){
        Swal.fire({
          title: 'Deleted!',
          text: 'Shipping Carrier has been deleted.',
          icon: 'success',
        })
        fetchShippingCarrierLists()
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
        '/shipping-carriers',
        formData
      )
      if(response.status === true){
          setIsCreateOpen(false)
          showToast('', 'Create Shipping Carrier successful', 'top-right', 'success')
          setFormData({
            carrier_code: '',
            carrier_name: '',
            contact_person: '',
            phone_number: '',
            email: '',
            address: '',
            country: '',
            contract_details: '',
            payment_terms: '',
            service_type: '',
            tracking_url: '',
            performance_rating: '',
            capabilities: '',
            creation_date: '',
            created_by: '',
            last_modified_date: '',
            last_modified_by: '',
            status: 1,
          })
          fetchShippingCarrierLists()
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
        showToast('', 'Create Shipping Carrier failed!', 'top-right', 'error')
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
        `/shipping-carriers/${updateFormData.id}`,
        updateFormData
      )
      if (response.status === true) {
        setIsUpdateOpen(false)
        showToast(
          '',
          'Update Shipping Carrier successful',
          'top-right',
          'success'
        )
        setUpdateFormData({
            id: '',
            carrier_code: '',
            carrier_name: '',
            contact_person:  '',
            phone_number:  '',
            email: '',
            address: '',
            country: '',
            contract_details: '',
            payment_terms: '',
            service_type: '',
            tracking_url: '',
            performance_rating: '',
            capabilities: '',
            creation_date: '',
            created_by: '',
            last_modified_date: '',
            last_modified_by: '',
            status: '',
        })
        fetchShippingCarrierLists()
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
        if (!filterText) return carrierLists

        return carrierLists.filter((item: any) =>
        Object.values(item).some(
            (val) =>
            val && val.toString().toLowerCase().includes(filterText.toLowerCase())
        )
        )
    }, [filterText, carrierLists])

  return (
    <div className="space-y-10">
      <div className="flex justify-between items-center">
        <h1 className="text-xl font-semibold">Shipping Carrier Lists</h1>
        <Button variant="primary" size="sm" onClick={handleCreate}>
          Add Carrier
        </Button>
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
                placeholder="Search Shipping Carriers…"
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
              Add New Shipping Carrier
            </h2>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <Label>
                  Carrier Code<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={formData.carrier_code}
                  onChange={handleChange('carrier_code')}
                  onKeyUp={() => handleRemove('carrier_code')}
                  error={!!errors.carrier_code}
                  hint={errors.carrier_code}
                />
              </div>
              <div>
                <Label>
                  Carrier Name<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={formData.carrier_name}
                  onChange={handleChange('carrier_name')}
                  onKeyUp={() => handleRemove('carrier_name')}
                  error={!!errors.carrier_name}
                  hint={errors.carrier_name}
                />
              </div>
              <div>
                <Label>
                  Contact Person<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={formData.contact_person}
                  onChange={handleChange('contact_person')}
                  onKeyUp={() => handleRemove('contact_person')}
                  error={!!errors.contact_person}
                  hint={errors.contact_person}
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
                <Label>
                  Email Address<span className="text-error-500">*</span>
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
                    setFormData((prev) => ({
                      ...prev,
                      country: val,
                    }))
                  }}
                />
              </div>
              <div>
                <Label>Contract Details</Label>
                <Input
                  type="text"
                  value={formData.contract_details}
                  onChange={handleChange('contract_details')}
                  onKeyUp={() => handleRemove('contract_details')}
                />
              </div>
              <div>
                <Label>Payment Terms</Label>
                <SingleSelectInput
                  options={paymentTermsData}
                  valueKey="value"
                  value={formData.payment_terms}
                  getOptionLabel={(item) => `${item.value}`}
                  onSingleSelectChange={(val) => {
                    handleRemove('payment_terms')
                    setFormData((prev) => ({
                      ...prev,
                      payment_terms: val,
                    }))
                  }}
                />
              </div>
              <div>
                <Label>Service Type</Label>
                <SingleSelectInput
                  options={serviceTypeData}
                  valueKey="value"
                  value={formData.service_type}
                  getOptionLabel={(item) => `${item.value}`}
                  onSingleSelectChange={(val) => {
                    handleRemove('service_type')
                    setFormData((prev) => ({
                      ...prev,
                      service_type: val,
                    }))
                  }}
                />
              </div>
              <div>
                <Label>Tracking URL</Label>
                <Input
                  type="text"
                  value={formData.tracking_url}
                  onChange={handleChange('tracking_url')}
                  onKeyUp={() => handleRemove('tracking_url')}
                />
              </div>
              <div>
                <Label>Performance Rating</Label>
                <SingleSelectInput
                  options={performanceRatingData}
                  valueKey="value"
                  value={formData.performance_rating}
                  getOptionLabel={(item) => `${item.value}`}
                  onSingleSelectChange={(val) => {
                    handleRemove('performance_rating')
                    setFormData((prev) => ({
                      ...prev,
                      performance_rating: val,
                    }))
                  }}
                />
              </div>
              <div>
                <Label>Capabilities</Label>
                <Input
                  type="text"
                  value={formData.capabilities}
                  onChange={handleChange('capabilities')}
                  onKeyUp={() => handleRemove('capabilities')}
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
             Shipping Carrier
            </h2>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <Label>
                  Carrier Code<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={carrier.carrier_code}
                  disabled={true}
                />
              </div>
              <div>
                <Label>
                  Carrier Name<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={carrier.carrier_name}
                  disabled={true}
                />
              </div>
              <div>
                <Label>
                  Contact Person<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={carrier.contact_person}
                  disabled={true}
                />
              </div>
              <div>
                <Label>
                  Phone Number<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={carrier.phone_number}
                  disabled={true}
                />
              </div>
              <div>
                <Label>
                  Email Address<span className="text-error-500">*</span>
                </Label>
                <Input type="text" value={carrier.email} disabled={true} />
              </div>
              <div>
                <Label>Address</Label>
                <TextAreaInput value={carrier.address} disabled={true} />
              </div>
              <div>
                <Label>Country</Label>
                <Input type="text" value={carrier.country} disabled={true} />
              </div>
              <div>
                <Label>Contract Details</Label>
                <Input
                  type="text"
                  value={carrier.contract_details}
                  disabled={true}
                />
              </div>
              <div>
                <Label>Payment Terms</Label>
                <Input
                  type="text"
                  value={carrier.payment_terms}
                  disabled={true}
                />
              </div>
              <div>
                <Label>Service Type</Label>
                <Input
                  type="text"
                  value={carrier.service_type}
                  disabled={true}
                />
              </div>
              <div>
                <Label>Tracking URL</Label>
                <Input
                  type="text"
                  value={carrier.tracking_url}
                  disabled={true}
                />
              </div>
              <div>
                <Label>Performance Rating</Label>
                <Input type="text" value={carrier.performance_rating} disabled={true} />
              </div>
              <div>
                <Label>Capabilities</Label>
                <Input
                  type="text"
                  value={carrier.capabilities}
                  disabled={true}
                />
              </div>
              <div>
                <Label>Status</Label>
                <ToggleSwitchInput
                  label="Enable Active"
                  defaultChecked={!!carrier.status}
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
              Edit Shipping Carrier
            </h2>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <Label>
                  Carrier Code<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={updateFormData.carrier_code}
                  onChange={handleChange('carrier_code')}
                  onKeyUp={() => handleRemove('carrier_code')}
                  error={!!errors.carrier_code}
                  hint={errors.carrier_code}
                />
              </div>
              <div>
                <Label>
                  Carrier Name<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={updateFormData.carrier_name}
                  onChange={handleChange('carrier_name')}
                  onKeyUp={() => handleRemove('carrier_name')}
                  error={!!errors.carrier_name}
                  hint={errors.carrier_name}
                />
              </div>
              <div>
                <Label>
                  Contact Person<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={updateFormData.contact_person}
                  onChange={handleChange('contact_person')}
                  onKeyUp={() => handleRemove('contact_person')}
                  error={!!errors.contact_person}
                  hint={errors.contact_person}
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
                <Label>
                  Email Address<span className="text-error-500">*</span>
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
                    setUpdateFormData((prev) => ({
                      ...prev,
                      country: val,
                    }))
                  }}
                />
              </div>
              <div>
                <Label>Contract Details</Label>
                <Input
                  type="text"
                  value={updateFormData.contract_details}
                  onChange={handleChange('contract_details')}
                  onKeyUp={() => handleRemove('contract_details')}
                />
              </div>
              <div>
                <Label>Payment Terms</Label>
                <SingleSelectInput
                  options={paymentTermsData}
                  valueKey="value"
                  value={updateFormData.payment_terms}
                  getOptionLabel={(item) => `${item.value}`}
                  onSingleSelectChange={(val) => {
                    handleRemove('payment_terms')
                    setUpdateFormData((prev) => ({
                      ...prev,
                      payment_terms: val,
                    }))
                  }}
                />
              </div>
              <div>
                <Label>Service Type</Label>
                <SingleSelectInput
                  options={serviceTypeData}
                  valueKey="value"
                  value={updateFormData.service_type}
                  getOptionLabel={(item) => `${item.value}`}
                  onSingleSelectChange={(val) => {
                    handleRemove('service_type')
                    setUpdateFormData((prev) => ({
                      ...prev,
                      service_type: val,
                    }))
                  }}
                />
              </div>
              <div>
                <Label>Tracking URL</Label>
                <Input
                  type="text"
                  value={updateFormData.tracking_url}
                  onChange={handleChange('tracking_url')}
                  onKeyUp={() => handleRemove('tracking_url')}
                />
              </div>
              <div>
                <Label>Performance Rating</Label>
                <SingleSelectInput
                  options={performanceRatingData}
                  valueKey="value"
                  value={updateFormData.performance_rating}
                  getOptionLabel={(item) => `${item.value}`}
                  onSingleSelectChange={(val) => {
                    handleRemove('performance_rating')
                    setUpdateFormData((prev) => ({
                      ...prev,
                      performance_rating: val,
                    }))
                  }}
                />
              </div>
              <div>
                <Label>Capabilities</Label>
                <Input
                  type="text"
                  value={updateFormData.capabilities}
                  onChange={handleChange('capabilities')}
                  onKeyUp={() => handleRemove('capabilities')}
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


