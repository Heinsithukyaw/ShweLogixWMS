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
  party_code: string
  party_name: string
  party_type: string
  contact_person: string
  phone_number: string
  email: string
  address:string
  country:string
  tax_vat:string
  business_registration_no:string
  payment_terms:string
  credit_limit:any
  custom_attributes:string
  status: number
  [key: string]: any
}

interface Errors {
  party_code?: string
  party_name?: string
  party_type?: string
  email?:string
}

const columns: TableColumn<RowData>[] = [
  {
    name: 'No.',
    selector: (_row: any, index: any) => index + 1,
    width: '70px',
    sortable: false,
  },
  {
    name: 'Party Code',
    selector: (row: RowData) => row.party_code,
    sortable: true,
  },
  {
    name: 'Party Name',
    selector: (row: RowData) => row.party_name,
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
    selector: (row: RowData) => row.address,
    sortable: true,
  },
  {
    name: 'Country',
    selector: (row: RowData) => row.country || '-',
    sortable: true,
  },
  {
    name: 'Tax ID/ VAT No',
    selector: (row: RowData) => row.tax_vat || '-',
    sortable: true,
  },
  {
    name: 'Business Registration No',
    selector: (row: RowData) => row.business_registration_no,
    sortable: true,
  },
  {
    name: 'Payment Terms',
    selector: (row: RowData) => row.payment_terms || '-',
    sortable: true,
  },
  {
    name: 'Credit Limit',
    selector: (row: RowData) => row.credit_limit,
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
  const [businessPartyLists, setBusinessPartyLists] = useState<any>([])
  const [businessParty, setBusinessParty] = useState<any>({})
  const { showToast } = provideUtility()

  const partyTypeData = [
    {id:1, value:'Supplier'},
    {id:2, value:'Customer'},
    {id:3, value:'Warehouse'},
    {id:4, value:'Distributor'},
    {id:5, value:'Manufacturer'},
    {id:6, value:'Logistics Provider'},
    {id:7, value:'Retailer'},
    {id:8, value:'Financial Institution'},
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
    party_code: '',
    party_name: '',
    party_type: '',
    contact_person: '',
    phone_number: '',
    email:'',
    address:'',
    country:'',
    tax_vat:'',
    business_registration_no:'',
    payment_terms:'',
    credit_limit:'',
    custom_attributes:'',
    status:1
  })
  
  const [updateFormData, setUpdateFormData] = useState({
    id: '',
    party_code: '',
    party_name: '',
    party_type: '',
    contact_person: '',
    phone_number: '',
    email: '',
    address: '',
    country: '',
    tax_vat: '',
    business_registration_no: '',
    payment_terms: '',
    credit_limit: '',
    custom_attributes: '',
    status: '',
  })

  const filteredData = useMemo(() => {
  if (!filterText) return businessPartyLists

  return businessPartyLists.filter((item:any) =>
    Object.values(item).some(
      (val) =>
        val && val.toString().toLowerCase().includes(filterText.toLowerCase())
    )
  )
  }, [filterText, businessPartyLists])

  useEffect(() => {
    fetchBusinessPartyLists()
  },[])

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
      setBusinessParty(businessPartyLists.find((x:any) => x.id === row.id))
      setIsViewOpen(true)
    }
  
    const handleCreate = () => {
    setErrors({
      party_code: '',
      party_name: '',
      party_type: '',
      email:''
    })
      setIsCreateOpen(true)
    }
  
    const handleEdit = (row: any) => {
      setIsUpdateOpen(true)
      setErrors({
        party_code: '',
        party_name: '',
        party_type: '',
        email:''
      })
      const business_party_data = businessPartyLists.find((x: any) => x.id === row.id)
  
      if (business_party_data) {
        setUpdateFormData({
          id: business_party_data.id || '',
          party_code: business_party_data.party_code || '',
          party_name: business_party_data.party_name || '',
          party_type: business_party_data.party_type || '',
          contact_person: business_party_data.contact_person || '',
          phone_number: business_party_data.phone_number || '',
          email: business_party_data.email || '',
          address: business_party_data.address || '',
          country: business_party_data.country || '',
          tax_vat: business_party_data.tax_vat || '',
          business_registration_no:business_party_data.business_registration_no || '',
          payment_terms: business_party_data.payment_terms?.toString() || '',
          credit_limit: business_party_data.credit_limit || '',
          custom_attributes: business_party_data.custom_attributes || '',
          status: business_party_data.status || '',
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
        const response = await http.deleteDataWithToken(`/business-parties/${row.id}`)
        console.log(response)
        if(response.status == true){
          Swal.fire({
            title: 'Deleted!',
            text: 'Business Party has been deleted.',
            icon: 'success',
          })
          fetchBusinessPartyLists()
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
          '/business-parties',
          formData
        )
        if(response.status === true){
            setIsCreateOpen(false)
            showToast('', 'Create Business Party successful', 'top-right', 'success')
            setFormData({
              party_code: '',
              party_name: '',
              party_type: '',
              contact_person: '',
              phone_number: '',
              email: '',
              address: '',
              country: '',
              tax_vat: '',
              business_registration_no: '',
              payment_terms: '',
              credit_limit: '',
              custom_attributes: '',
              status: 1,
            })
            fetchBusinessPartyLists()
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
          showToast('', 'Create Business Party failed!', 'top-right', 'error')
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
          `/business-parties/${updateFormData.id}`,
          updateFormData
        )
        if (response.status === true) {
          setIsUpdateOpen(false)
          showToast(
            '',
            'Update Business Party successful',
            'top-right',
            'success'
          )
          setUpdateFormData({
            id: '',
            party_code: '',
            party_name: '',
            party_type: '',
            contact_person: '',
            phone_number: '',
            email: '',
            address: '',
            country: '',
            tax_vat: '',
            business_registration_no: '',
            payment_terms: '',
            credit_limit: '',
            custom_attributes: '',
            status: '',
          })
          fetchBusinessPartyLists()
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

  return (
    <>
      <div className="space-y-10">
        <div className="flex justify-between items-center">
          <h1 className="text-xl font-semibold">Business Party Lists</h1>
          <Button variant="primary" size="sm" onClick={handleCreate}>
            Add Party
          </Button>
        </div>

        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 md:gap-6 xl:grid-cols-4">
          <div className="rounded-md shadow-lg border border-gray-200 bg-white p-2">
            <div className="rounded-2xl border border-gray-200 bg-white p-5 ">
              <p className="text-gray-500 text-theme-sm dark:text-gray-400">
                Total Business Parties
              </p>
              <div className="flex items-end justify-between mt-3">
                <div className="">
                  <h4 className="text-2xl font-bold text-gray-800 dark:text-white/90">
                    {businessPartyLists?.length || 0}
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
                Suppliers
              </p>
              <div className="flex items-end justify-between mt-3">
                <div className="">
                  <h4 className="text-2xl font-bold text-gray-800 dark:text-white/90">
                    {
                      businessPartyLists.filter(
                        (x: any) => x.party_type === 'Supplier'
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
                Customers
              </p>
              <div className="flex items-end justify-between mt-3">
                <div className="">
                  <h4 className="text-2xl font-bold text-gray-800 dark:text-white/90">
                    {
                      businessPartyLists.filter(
                        (x: any) => x.party_type === 'Customer'
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
                Other Partners
              </p>
              <div className="flex items-end justify-between mt-3">
                <div className="">
                  <h4 className="text-2xl font-bold text-gray-800 dark:text-white/90">
                    {
                      businessPartyLists.filter(
                        (x: any) => x.party_type !== 'Supplier' && x.party_type !== 'Customer'
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
                      placeholder="Search Parties…"
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
                    Add Business Party
                  </h2>
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                      <Label>
                        Party Code<span className="text-error-500">*</span>
                      </Label>
                      <Input
                        type="text"
                        value={formData.party_code}
                        onChange={handleChange('party_code')}
                        onKeyUp={() => handleRemove('party_code')}
                        error={!!errors.party_code}
                        hint={errors.party_code}
                      />
                    </div>
                    <div>
                      <Label>
                        Party Name<span className="text-error-500">*</span>
                      </Label>
                      <Input
                        type="text"
                        value={formData.party_name}
                        onChange={handleChange('party_name')}
                        onKeyUp={() => handleRemove('party_name')}
                        error={!!errors.party_name}
                        hint={errors.party_name}
                      />
                    </div>
                    <div>
                      <Label>
                        Party Type<span className="text-error-500">*</span>
                      </Label>
                      <SingleSelectInput
                        options={partyTypeData}
                        valueKey="value"
                        value={formData.party_type}
                        getOptionLabel={(item) => `${item.value}`}
                        onSingleSelectChange={(val) => {
                          handleRemove('party_type')
                          setFormData((prev) => ({ ...prev, party_type: val }))
                        }}
                        error={!!errors.party_type}
                        hint={errors.party_type}
                      />
                    </div>
                    <div>
                      <Label>Contact Person</Label>
                      <Input
                        type="text"
                        value={formData.contact_person}
                        onChange={handleChange('contact_person')}
                        onKeyUp={() => handleRemove('contact_person')}
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
                      <Label>Tax ID / VAT No</Label>
                      <Input
                        type="text"
                        value={formData.tax_vat}
                        onChange={handleChange('tax_vat')}
                        onKeyUp={() => handleRemove('tax_vat')}
                      />
                    </div>
                    <div>
                      <Label>Business Registration No</Label>
                      <Input
                        type="text"
                        value={formData.business_registration_no}
                        onChange={handleChange('business_registration_no')}
                        onKeyUp={() => handleRemove('business_registration_no')}
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
                      <Label>Credit Limit</Label>
                      <Input
                        type="number"
                        value={formData.credit_limit}
                        onChange={handleChange('credit_limit')}
                        onKeyUp={() => handleRemove('credit_limit')}
                      />
                    </div>
                    <div className="col-span-full">
                      <Label>Custom Attributes</Label>
                      <TextAreaInput
                        value={formData.custom_attributes}
                        onChange={(value) =>
                          handleChange('custom_attributes')({
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
                    Business Party
                  </h2>
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                      <Label>
                        Party Code<span className="text-error-500">*</span>
                      </Label>
                      <Input
                        type="text"
                        value={businessParty?.party_code}
                        disabled={true}
                      />
                    </div>
                    <div>
                      <Label>
                        Party Name<span className="text-error-500">*</span>
                      </Label>
                      <Input
                        type="text"
                        value={businessParty?.party_name}
                        disabled={true}
                      />
                    </div>
                    <div>
                      <Label>
                        Party Type<span className="text-error-500">*</span>
                      </Label>
                      <Input
                        type="text"
                        value={businessParty?.party_type}
                        disabled={true}
                      />
                    </div>
                    <div>
                      <Label>Contact Person</Label>
                      <Input
                        type="text"
                        value={businessParty?.contact_person}
                        disabled={true}
                      />
                    </div>
                    <div>
                      <Label>Phone Number</Label>
                      <Input
                        type="number"
                        value={businessParty?.phone_number}
                        disabled={true}
                      />
                    </div>
                    <div>
                      <Label>Email</Label>
                      <Input
                        type="text"
                        value={businessParty?.email}
                        disabled={true}
                      />
                    </div>
                    <div className="col-span-full">
                      <Label>Address</Label>
                      <TextAreaInput
                        value={businessParty?.address}
                        disabled={true}
                      />
                    </div>
                    <div>
                      <Label>Country</Label>
                      <Input
                        type="text"
                        value={businessParty?.country}
                        disabled={true}
                      />
                    </div>
                    <div>
                      <Label>Tax ID / VAT No</Label>
                      <Input
                        type="text"
                        value={businessParty?.tax_vat}
                        disabled={true}
                      />
                    </div>
                    <div>
                      <Label>Business Registration No</Label>
                      <Input
                        type="text"
                        value={businessParty?.business_registration_no}
                        disabled={true}
                      />
                    </div>
                    <div>
                      <Label>Payment Terms</Label>
                      <Input
                        type="text"
                        value={businessParty?.payment_terms}
                        disabled={true}
                      />
                    </div>
                    <div>
                      <Label>Credit Limit</Label>
                      <Input
                        type="number"
                        value={businessParty?.credit_limit}
                        disabled={true}
                      />
                    </div>
                    <div className="col-span-full">
                      <Label>Custom Attributes</Label>
                      <TextAreaInput
                        value={businessParty?.custom_attributes}
                        disabled={true}
                      />
                    </div>
                    <div>
                      <Label>Status</Label>
                      <ToggleSwitchInput
                        label="Enable Active"
                        defaultChecked={!!businessParty?.status}
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
                    Update Business Party
                  </h2>
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                      <Label>
                        Party Code<span className="text-error-500">*</span>
                      </Label>
                      <Input
                        type="text"
                        value={updateFormData.party_code}
                        onChange={handleChange('party_code')}
                        onKeyUp={() => handleRemove('party_code')}
                        error={!!errors.party_code}
                        hint={errors.party_code}
                      />
                    </div>
                    <div>
                      <Label>
                        Party Name<span className="text-error-500">*</span>
                      </Label>
                      <Input
                        type="text"
                        value={updateFormData.party_name}
                        onChange={handleChange('party_name')}
                        onKeyUp={() => handleRemove('party_name')}
                        error={!!errors.party_name}
                        hint={errors.party_name}
                      />
                    </div>
                    <div>
                      <Label>
                        Party Type<span className="text-error-500">*</span>
                      </Label>
                      <SingleSelectInput
                        options={partyTypeData}
                        valueKey="value"
                        value={updateFormData.party_type}
                        getOptionLabel={(item) => `${item.value}`}
                        onSingleSelectChange={(val) => {
                          handleRemove('party_type')
                          setUpdateFormData((prev) => ({
                            ...prev,
                            party_type: val,
                          }))
                        }}
                        error={!!errors.party_type}
                        hint={errors.party_type}
                      />
                    </div>
                    <div>
                      <Label>Contact Person</Label>
                      <Input
                        type="text"
                        value={updateFormData.contact_person}
                        onChange={handleChange('contact_person')}
                        onKeyUp={() => handleRemove('contact_person')}
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
                        type="text"
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
                          setUpdateFormData((prev) => ({
                            ...prev,
                            country: val,
                          }))
                        }}
                      />
                    </div>
                    <div>
                      <Label>Tax ID / VAT No</Label>
                      <Input
                        type="text"
                        value={updateFormData.tax_vat}
                        onChange={handleChange('tax_vat')}
                        onKeyUp={() => handleRemove('tax_vat')}
                      />
                    </div>
                    <div>
                      <Label>Business Registration No</Label>
                      <Input
                        type="text"
                        value={updateFormData.business_registration_no}
                        onChange={handleChange('business_registration_no')}
                        onKeyUp={() => handleRemove('business_registration_no')}
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
                      <Label>Credit Limit</Label>
                      <Input
                        type="number"
                        value={updateFormData.credit_limit}
                        onChange={handleChange('credit_limit')}
                        onKeyUp={() => handleRemove('credit_limit')}
                      />
                    </div>
                    <div className="col-span-full">
                      <Label>Custom Attributes</Label>
                      <TextAreaInput
                        value={updateFormData.custom_attributes}
                        onChange={(value) =>
                          handleChange('custom_attributes')({
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