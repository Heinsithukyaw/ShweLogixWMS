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
  warehouse_code: string
  warehouse_name: string
  warehouse_type: string
  address: string
  city: string
  state_region: string,
  country:string,
  postal_code: string,
  phone_number: string,
  email: string,
  contact_person: string,
  manager_name: string,
  storage_capacity: string,
  status: number,
  [key: string]: any
}

interface Errors {
  warehouse_code?: string
  warehouse_name?: string
  warehouse_type?: string
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
    selector: (row: RowData) => row.warehouse_code,
    sortable: true,
  },
  {
    name: 'Name',
    selector: (row: RowData) => row.warehouse_name,
    sortable: true,
  },
  {
    name: 'Type',
    selector: (row: RowData) => row.warehouse_type || '-',
    sortable: true,
  },
  {
    name: 'Address',
    selector: (row: RowData) => row.address || '-',
    sortable: true,
  },
  {
    name: 'City',
    selector: (row: RowData) => row.city || '-',
    sortable: true,
  },
  {
    name: 'State/Region',
    selector: (row: RowData) => row.state_region || '-',
    sortable: true,
  },
  {
    name: 'Country',
    selector: (row: RowData) => row.country || '-',
    sortable: true,
  },
  {
    name: 'Postal Code',
    selector: (row: RowData) => row.postal_code || '-',
    sortable: true,
  },
  {
    name: 'Phone Number',
    selector: (row: RowData) => row.phone_number || '-',
    sortable: true,
  },
  {
    name: 'Email',
    selector: (row: RowData) => row.email || '-',
    sortable: true,
  },
  {
    name: 'Contact Person',
    selector: (row: RowData) => row.contact_person || '-',
    sortable: true,
  },
  {
    name: 'Manager Name',
    selector: (row: RowData) => row.manager_level || '-',
    sortable: true,
  },
  {
    name: 'Storage Capacity',
    selector: (row: RowData) => row.storage_capacity || '-',
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
  const [isCreateOpen, setIsCreateOpen] = useState(false)
  const [isViewOpen, setIsViewOpen] = useState(false)
  const [isUpdateOpen, setIsUpdateOpen] = useState(false)
  const [errors, setErrors] = useState<Errors>({})
  const [warehouseLists, setWarehouseLists] = useState<any>([])
  const [warehouse, setWarehouse] = useState<any>({})
  const [isPageLoading, setIsPageLoading] = useState(false)
  const [isLoading, setIsLoading] = useState(false)
  const { showToast } = provideUtility()

  const warehouseTypeData = [
    { id: 1, value: 'General Storage' },
    { id: 2, value: 'Distribution' },
    { id: 3, value: 'Dry Storage' },
    { id: 4, value: 'Cold Storage' },
    { id: 5, value: 'Hazardous Materials' },
    { id: 6, value: 'Raw Materials' },
    { id: 7, value: 'Finished Goods' },
    { id: 8, value: 'Cross-Docking' },
    { id: 9, value: 'Fullfillment Center' },
    { id: 10, value: 'Bonded Warehouse' },
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

  const operatingHoursData = [
    { id: 1, value: '24/7 Operation' },
    { id: 2, value: 'Mon-Fri:8am-6pm' },
    { id: 3, value: 'Mon-Sat:6am-10pm' },
    { id: 4, value: 'Mon-Fri:9am-5pm' },
    { id: 5, value: 'Cusom Hours' },
  ]

  const [formData, setFormData] = useState({
    warehouse_code: '',
    warehouse_name: '',
    warehouse_type: '',
    description:'',
    address: '',
    city: '',
    state_region: '',
    country:'',
    postal_code: '',
    phone_number: '',
    email: '',
    contact_person: '',
    manager_name: '',
    storage_capacity: '',
    operating_hours:'',
    custom_attributes:'',
    status: 1,
  })

  const [updateFormData, setUpdateFormData] = useState({
    id: '',
    warehouse_code: '',
    warehouse_name: '',
    warehouse_type: '',
    description: '',
    address: '',
    city: '',
    state_region: '',
    country: '',
    postal_code: '',
    phone_number: '',
    email: '',
    contact_person: '',
    manager_name: '',
    storage_capacity: '',
    operating_hours: '',
    custom_attributes:'',
    status: '',
  })

  useEffect(() => {
    setIsPageLoading(true)
    fetchWarehouseLists()
  }, [])

  const fetchWarehouseLists = async () => {
    try {
      setIsPageLoading(true)
      const res = await http.fetchDataWithToken('warehouses')
      console.log(res)
      setWarehouseLists(res.data?.data || [])
    } catch (err) {
      setIsPageLoading(false)
      console.error('Failed to fetch Warehouse lists:', err)
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
    setWarehouse(warehouseLists?.find((x:any) => x.id === row.id))
    setIsViewOpen(true)
  }

  const handleCreate = () => {
    setErrors({
    warehouse_code: '',
    warehouse_name: '',
    warehouse_type: ''
    })
    setIsCreateOpen(true)
  }

  const handleEdit = (row: any) => {
    setIsUpdateOpen(true)
    setErrors({
      warehouse_code: '',
      warehouse_name: '',
      warehouse_type: '',
    })
    const warehouse_data = warehouseLists.find((x: any) => x.id === row.id)
      
    if (warehouse_data) {
      setUpdateFormData({
        id: warehouse_data.id,
        warehouse_code: warehouse_data.warehouse_code,
        warehouse_name: warehouse_data.warehouse_name,
        warehouse_type: warehouse_data.warehouse_type,
        description: warehouse_data.description,
        address: warehouse_data.address,
        city: warehouse_data.city,
        state_region: warehouse_data.state_region,
        country: warehouse_data.country,
        postal_code: warehouse_data.postal_code,
        phone_number: warehouse_data.phone_number,
        email: warehouse_data.email,
        contact_person: warehouse_data.contact_person,
        manager_name: warehouse_data.manager_name,
        storage_capacity: warehouse_data.storage_capacity,
        operating_hours: warehouse_data.operation_hours,
        custom_attributes: warehouse_data.custom_attributes,
        status: warehouse_data.status,
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
      const response = await http.deleteDataWithToken(`/warehouses/${row.id}`)
      console.log(response)
      if(response.status == true){
        Swal.fire({
          title: 'Deleted!',
          text: 'Warehouse has been deleted.',
          icon: 'success',
        })
        fetchWarehouseLists()
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
        '/warehouses',
        formData
      )
      if(response.status === true){
          setIsCreateOpen(false)
          showToast('', 'Create Warehouse successful', 'top-right', 'success')
          setFormData({
            warehouse_code: '',
            warehouse_name: '',
            warehouse_type: '',
            description: '',
            address: '',
            city: '',
            state_region: '',
            country: '',
            postal_code: '',
            phone_number: '',
            email: '',
            contact_person: '',
            manager_name: '',
            storage_capacity: '',
            operating_hours: '',
            custom_attributes:'',
            status: 1,
          })
          fetchWarehouseLists()
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
        showToast('', 'Create Warehouse failed!', 'top-right', 'error')
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
        `/warehouses/${updateFormData.id}`,
        updateFormData
      )
      if (response.status === true) {
        setIsUpdateOpen(false)
        showToast(
          '',
          'Update Warehouse successful',
          'top-right',
          'success'
        )
        setUpdateFormData({
          id: '',
          warehouse_code: '',
          warehouse_name: '',
          warehouse_type: '',
          description: '',
          address: '',
          city: '',
          state_region: '',
          country: '',
          postal_code: '',
          phone_number: '',
          email: '',
          contact_person: '',
          manager_name: '',
          storage_capacity: '',
          operating_hours: '',
          custom_attributes:'',
          status: '',
        })
        fetchWarehouseLists()
      } else {
        showToast('', 'Something went wrong!.', 'top-right', 'error')
      }
    } catch (err: any) {
      if (err?.status === 422) {
        showToast('', err?.message, 'top-right', 'error')
        const apiErrors: Errors = err?.errors
        setErrors(apiErrors)
      } else {
        showToast('', 'Update Warehouse failed!', 'top-right', 'error')
      }
      console.error(err)
    } finally {
      setIsLoading(false)
    }
  }

  const [filterText, setFilterText] = React.useState('')
    const filteredData = useMemo(() => {
    if (!filterText) return warehouseLists
  
    return warehouseLists.filter((item:any) =>
      Object.values(item).some(
        (val) =>
          val && val.toString().toLowerCase().includes(filterText.toLowerCase())
      )
    )
   }, [filterText, warehouseLists])

  return (
    <div className="space-y-10">
      <div className="flex justify-between items-center">
        <h1 className="text-xl font-semibold">Warehouse Lists</h1>
        <Button variant="primary" size="sm" onClick={handleCreate}>
          Add Warehouse
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
                    placeholder="Search Warehouses…"
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
              Add New Warehouse
            </h2>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <Label>
                  Warehouse Code<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={formData.warehouse_code}
                  onChange={handleChange('warehouse_code')}
                  onKeyUp={() => handleRemove('warehouse_code')}
                  error={!!errors.warehouse_code}
                  hint={errors.warehouse_code}
                />
              </div>
              <div>
                <Label>
                  Warehouse Name<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={formData.warehouse_name}
                  onChange={handleChange('warehouse_name')}
                  onKeyUp={() => handleRemove('warehouse_name')}
                  error={!!errors.warehouse_name}
                  hint={errors.warehouse_name}
                />
              </div>

              <div>
                <Label>Warehouse Type</Label>
                <SingleSelectInput
                  options={warehouseTypeData}
                  valueKey="value"
                  value={formData.warehouse_type}
                  getOptionLabel={(item) => `${item.value}`}
                  onSingleSelectChange={(val) => {
                    console.log('value - ' + val)
                    handleRemove('warehouse_type')
                    setFormData((prev) => ({
                        ...prev,
                        warehouse_type: val,
                    }))
                  }}
                  error={!!errors.warehouse_type}
                  hint={errors.warehouse_type}
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
                <Label>City</Label>
                <Input
                  type="text"
                  value={formData.city}
                  onChange={handleChange('city')}
                  onKeyUp={() => handleRemove('city')}
                />
              </div>
              <div>
                <Label>State/Region</Label>
                <Input
                  type="text"
                  value={formData.state_region}
                  onChange={handleChange('state_region')}
                  onKeyUp={() => handleRemove('state_region')}
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
                    setFormData((prev) => ({
                        ...prev,
                        country: val,
                    }))
                    handleRemove('country')
                  }}
                />
              </div>
              <div>
                <Label>Postal Code</Label>
                <Input
                  type="text"
                  value={formData.postal_code}
                  onChange={handleChange('postal_code')}
                  onKeyUp={() => handleRemove('postal_code')}
                />
              </div>
              <div>
                <Label>Phone Number</Label>
                <Input
                  type="text"
                  value={formData.phone_number}
                  onChange={handleChange('phone_number')}
                  onKeyUp={() => handleRemove('phone_number')}
                />
              </div>
              <div>
                <Label>Email Address</Label>
                <Input
                  type="text"
                  value={formData.email}
                  onChange={handleChange('email')}
                  onKeyUp={() => handleRemove('email')}
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
                <Label>Manager Name</Label>
                <Input
                  type="text"
                  value={formData.manager_name}
                  onChange={handleChange('manager_name')}
                  onKeyUp={() => handleRemove('manager_name')}
                />
              </div>
              <div>
                <Label>Storage Capacity</Label>
                <Input
                  type="text"
                  value={formData.storage_capacity}
                  onChange={handleChange('storage_capacity')}
                  onKeyUp={() => handleRemove('storage_capacity')}
                />
              </div>
              <div>
                <Label>Operating Hours</Label>
                <SingleSelectInput
                  options={operatingHoursData}
                  valueKey="value"
                  value={formData.operating_hours}
                  getOptionLabel={(item) => `${item.value}`}
                  onSingleSelectChange={(val) => {
                    console.log('value - ' + val)
                    handleRemove('operating_hours')
                    setFormData((prev) => ({
                        ...prev,
                        operating_hours: val,
                    }))

                  }}
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
              Warehouse Info
            </h2>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <Label>
                  Warehouse Code<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={warehouse.warehouse_code}
                  disabled={true}
                />
              </div>
              <div>
                <Label>
                  Warehouse Name<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={warehouse.warehouse_name}
                  disabled={true}
                />
              </div>

              <div>
                <Label>Warehouse Type</Label>
                <Input
                  type="text"
                  value={warehouse.warehouse_type}
                  disabled={true}
                />
              </div>
              <div className="col-span-full">
                <Label>Description</Label>
                <TextAreaInput value={warehouse.description} disabled={true} />
              </div>
              <div className="col-span-full">
                <Label>Address</Label>
                <TextAreaInput value={warehouse.address} disabled={true} />
              </div>
              <div>
                <Label>City</Label>
                <Input
                  type="text"
                  value={warehouse.city}
                  disabled={true}
                />
              </div>
              <div>
                <Label>State/Region</Label>
                <Input
                  type="text"
                  value={warehouse.state_region}
                  disabled={true}
                />
              </div>
              <div>
                <Label>Country</Label>
                <Input type="text" value={warehouse.country} disabled={true} />
              </div>
              <div>
                <Label>Postal Code</Label>
                <Input
                  type="text"
                  value={warehouse.postal_code}
                  disabled={true}
                />
              </div>
              <div>
                <Label>Phone Number</Label>
                <Input
                  type="text"
                  value={warehouse.phone_number}
                  disabled={true}
                />
              </div>
              <div>
                <Label>Email Address</Label>
                <Input type="text" value={warehouse.email} disabled={true} />
              </div>
              <div>
                <Label>Contact Person</Label>
                <Input
                  type="text"
                  value={warehouse.contact_person}
                  disabled={true}
                />
              </div>
              <div>
                <Label>Manager Name</Label>
                <Input
                  type="text"
                  value={warehouse.manager_name}
                  disabled={true}
                />
              </div>
              <div>
                <Label>Storage Capacity</Label>
                <Input
                  type="text"
                  value={warehouse.storage_capacity}
                  disabled={true}
                />
              </div>
              <div>
                <Label>Operating Hours</Label>
                <Input
                  type="text"
                  value={warehouse.operating_hours}
                  disabled={true}
                />
              </div>
              <div className="col-span-full">
                <Label>Custom Attributes</Label>
                <TextAreaInput
                  value={warehouse.custom_attributes}
                  disabled={true}
                />
              </div>
              <div>
                <Label>Status</Label>
                <ToggleSwitchInput
                  label="Enable Active"
                  defaultChecked={!!warehouse.status}
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
              Update Brand
            </h2>
             <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <Label>
                  Warehouse Code<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={updateFormData.warehouse_code}
                  onChange={handleChange('warehouse_code')}
                  onKeyUp={() => handleRemove('warehouse_code')}
                  error={!!errors.warehouse_code}
                  hint={errors.warehouse_code}
                />
              </div>
              <div>
                <Label>
                  Warehouse Name<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={updateFormData.warehouse_name}
                  onChange={handleChange('warehouse_name')}
                  onKeyUp={() => handleRemove('warehouse_name')}
                  error={!!errors.warehouse_name}
                  hint={errors.warehouse_name}
                />
              </div>

              <div>
                <Label>Warehouse Type</Label>
                <SingleSelectInput
                  options={warehouseTypeData}
                  valueKey="value"
                  value={updateFormData.warehouse_type}
                  getOptionLabel={(item) => `${item.value}`}
                  onSingleSelectChange={(val) => {
                    console.log('value - ' + val)
                    handleRemove('warehouse_type')
                    setUpdateFormData((prev) => ({
                        ...prev,
                        warehouse_type: val,
                    }))

                  }}
                  error={!!errors.warehouse_type}
                  hint={errors.warehouse_type}
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
                <Label>City</Label>
                <Input
                  type="text"
                  value={updateFormData.city}
                  onChange={handleChange('city')}
                  onKeyUp={() => handleRemove('city')}
                />
              </div>
              <div>
                <Label>State/Region</Label>
                <Input
                  type="text"
                  value={updateFormData.state_region}
                  onChange={handleChange('state_region')}
                  onKeyUp={() => handleRemove('state_region')}
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
                    console.log('value - ' + val)
                    handleRemove('country')
                    setFormData((prev) => ({
                        ...prev,
                        country: val,
                    }))

                  }}
                />
              </div>
              <div>
                <Label>Postal Code</Label>
                <Input
                  type="text"
                  value={updateFormData.postal_code}
                  onChange={handleChange('postal_code')}
                  onKeyUp={() => handleRemove('postal_code')}
                />
              </div>
              <div>
                <Label>Phone Number</Label>
                <Input
                  type="text"
                  value={updateFormData.phone_number}
                  onChange={handleChange('phone_number')}
                  onKeyUp={() => handleRemove('phone_number')}
                />
              </div>
              <div>
                <Label>Email Address</Label>
                <Input
                  type="text"
                  value={updateFormData.email}
                  onChange={handleChange('email')}
                  onKeyUp={() => handleRemove('email')}
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
                <Label>Manager Name</Label>
                <Input
                  type="text"
                  value={updateFormData.manager_name}
                  onChange={handleChange('manager_name')}
                  onKeyUp={() => handleRemove('manager_name')}
                />
              </div>
              <div>
                <Label>Storage Capacity</Label>
                <Input
                  type="text"
                  value={updateFormData.storage_capacity}
                  onChange={handleChange('storage_capacity')}
                  onKeyUp={() => handleRemove('storage_capacity')}
                />
              </div>
              <div>
                <Label>Operating Hours</Label>
                <SingleSelectInput
                  options={operatingHoursData}
                  valueKey="value"
                  value={updateFormData.operating_hours}
                  getOptionLabel={(item) => `${item.value}`}
                  onSingleSelectChange={(val) => {
                    console.log('value - ' + val)
                    handleRemove('operating_hours')
                    setFormData((prev) => ({
                        ...prev,
                        operating_hours: val,
                    }))

                  }}
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
  )
}

export default List
