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
import { TableColumn } from 'react-data-table-component'
import { showConfirm } from '../../../utils/alert'
import Swal from 'sweetalert2'

interface RowData {
  area_code: string
  area_name: string
  area_type: string
  warehouse_code: string
  responsible_person: string
  phone_number: string
  email: string
  location_description: string
  capacity: string
  dimensions: string
  environmental_conditions: string
  equipment: string
  status: any
  [key: string]: any
}

interface Errors {
  area_code?: string
  area_name?: string
  area_type?: string
  warehouse_id?: any
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
    selector: (row: RowData) => row.area_code,
    sortable: true,
  },
  {
    name: 'Name',
    selector: (row: RowData) => row.area_name,
    sortable: true,
  },
  {
    name: 'Type',
    cell: (row: RowData) => (
      <span className='px-2 py-1 text-xs font-semibold rounded-full text-white bg-blue-900'>
        {row.area_type || '-'}
      </span>
    ),
    sortable: true,
  },
  {
    name: 'Warehouse Code',
    selector: (row: RowData) => row.warehouse_code || '-',
    sortable: true,
  },
  {
    name: 'Responsible Person',
    selector: (row: RowData) => row.responsible_person || '-',
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
    name: 'Location Description',
    selector: (row: RowData) => row.location_description || '-',
    sortable: true,
  },
  {
    name: 'Capacity',
    selector: (row: RowData) => row.capacity || '-',
    sortable: true,
  },
  {
    name: 'Dimensions',
    selector: (row: RowData) => row.dimensions || '-',
    sortable: true,
  },
  {
    name: 'Environmental Conditions',
    selector: (row: RowData) => row.environmental_conditions || '-',
    sortable: true,
  },
  {
    name: 'Equipment',
    selector: (row: RowData) => row.equipment || '-',
    sortable: true,
  },
  {
    name: 'Status',
    selector: (row: RowData) => row.status || '-',

    sortable: true,
  },
]

const List: React.FC = () => {
  const [isCreateOpen, setIsCreateOpen] = useState(false)
  const [isViewOpen, setIsViewOpen] = useState(false)
  const [isUpdateOpen, setIsUpdateOpen] = useState(false)
  const [errors, setErrors] = useState<Errors>({})
  const [areaLists, setAreaLists] = useState<any>([])
  const [warehouseLists, setWarehouseLists] = useState<any>([])
  const [area, setArea] = useState<any>({})
  const [isPageLoading, setIsPageLoading] = useState(false)
  const [isLoading, setIsLoading] = useState(false)
  const { showToast } = provideUtility()

  const areaTypeData = [
    { id: 1, value: 'Receiving' },
    { id: 2, value: 'Storage' },
    { id: 3, value: 'Packing' },
    { id: 4, value: 'Shipping' },
    { id: 5, value: 'Cross-Docking' },
    { id: 6, value: 'Quality Control' },
    { id: 7, value: 'Returns' },
    { id: 8, value: 'Value-Added Services' },
    { id: 9, value: 'Office' },
    { id: 10, value: 'Other' },
  ]

  const environmentalConditionsData = [
    { id: 1, value: 'Ambient' },
    { id: 2, value: 'Refrigerated (0-4°C)' },
    { id: 3, value: 'Frozen (-18°C or below)' },
    { id: 4, value: 'Temperature Controlled (15-25°C)' },
    { id: 5, value: 'Humidity Controlled' },
    { id: 6, value: 'Hazardous Materials' },
    { id: 7, value: 'Dust-Free' },
    { id: 8, value: 'High Security' },
  ]

  const statusData = [
    { id: 1, value: 'Active' },
    { id: 0, value: 'In Active' },
    { id: 2, value: 'Under Maintenance' },
    { id: 3, value: 'Planned' },
    { id: 4, value: 'Decommissioned' },
  ]

  const [formData, setFormData] = useState({
    area_code: '',
    area_name: '',
    area_type: '',
    warehouse_id:'',
    responsible_person:'',
    phone_number: '',
    email: '',
    location_description: '',
    capacity:'',
    dimensions: '',
    environmental_conditions: '',
    equipment: '',
    custom_attributes:'',
    status: '',
  })

  const [updateFormData, setUpdateFormData] = useState({
    id: '',
    area_code: '',
    area_name: '',
    area_type: '',
    warehouse_id:'',
    responsible_person:'',
    phone_number: '',
    email: '',
    location_description: '',
    capacity:'',
    dimensions: '',
    environmental_conditions: '',
    equipment: '',
    custom_attributes:'',
    status: '',
  })

  useEffect(() => {
    setIsPageLoading(true)
    fetchAreaLists()
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

  const handleCloseModal = () => {
    setIsCreateOpen(false)
    setIsViewOpen(false)
    setIsUpdateOpen(false)
  }

  const handleView = (row: any) => {
    console.log(row)
    setArea(areaLists?.find((x:any) => x.id === row.id))
    setIsViewOpen(true)
  }

  const handleCreate = () => {
    setErrors({
    area_code: '',
    area_name: '',
    area_type: '',
    warehouse_id: '',
    })
    setIsCreateOpen(true)
  }

  const handleEdit = (row: any) => {
    setIsUpdateOpen(true)
    setErrors({
      area_code: '',
      area_name: '',
      area_type: '',
      warehouse_id: ''
    })
    const area_data = areaLists.find((x: any) => x.id === row.id)
      
    if (area_data) {
      setUpdateFormData({
        id: area_data.id,
        area_code: area_data.area_code,
        area_name: area_data.area_name,
        area_type: area_data.area_type,
        warehouse_id: area_data.warehouse_id,
        responsible_person: area_data.responsible_person,
        phone_number: area_data.phone_number,
        email: area_data.email,
        location_description: area_data.location_description,
        capacity: area_data.capacity,
        dimensions: area_data.dimensions,
        environmental_conditions: area_data.environmental_conditions,
        equipment: area_data.equipment,
        custom_attributes: area_data.custom_attributes,
        status: area_data.status_value,
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
      const response = await http.deleteDataWithToken(`/areas/${row.id}`)
      console.log(response)
      if(response.status == true){
        Swal.fire({
          title: 'Deleted!',
          text: 'Area has been deleted.',
          icon: 'success',
        })
        fetchAreaLists()
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
        '/areas',
        formData
      )
      if(response.status === true){
          setIsCreateOpen(false)
          showToast('', 'Create Area successful', 'top-right', 'success')
          setFormData({
            area_code: '',
            area_name: '',
            area_type: '',
            warehouse_id: '',
            responsible_person: '',
            phone_number: '',
            email: '',
            location_description: '',
            capacity: '',
            dimensions: '',
            environmental_conditions: '',
            equipment: '',
            custom_attributes: '',
            status: '',
          })
          fetchAreaLists()
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
        `/areas/${updateFormData.id}`,
        updateFormData
      )
      if (response.status === true) {
        setIsUpdateOpen(false)
        showToast(
          '',
          'Update Area successful',
          'top-right',
          'success'
        )
        setUpdateFormData({
          id: '',
          area_code: '',
          area_name: '',
          area_type: '',
          warehouse_id: '',
          responsible_person: '',
          phone_number: '',
          email: '',
          location_description: '',
          capacity: '',
          dimensions: '',
          environmental_conditions: '',
          equipment: '',
          custom_attributes: '',
          status: '',
        })
        fetchAreaLists()
      } else {
        showToast('', 'Something went wrong!.', 'top-right', 'error')
      }
    } catch (err: any) {
      if (err?.status === 422) {
        showToast('', err?.message, 'top-right', 'error')
        const apiErrors: Errors = err?.errors
        setErrors(apiErrors)
      } else {
        showToast('', 'Update Area failed!', 'top-right', 'error')
      }
      console.error(err)
    } finally {
      setIsLoading(false)
    }
  }

  const [filterText, setFilterText] = React.useState('')
    const filteredData = useMemo(() => {
    if (!filterText) return areaLists
  
    return areaLists.filter((item:any) =>
      Object.values(item).some(
        (val) =>
          val && val.toString().toLowerCase().includes(filterText.toLowerCase())
      )
    )
   }, [filterText, areaLists])

  return (
    <div className="space-y-10">
      <div className="flex justify-between items-center">
        <h1 className="text-xl font-semibold">Area Lists</h1>
        <Button variant="primary" size="sm" onClick={handleCreate}>
          Add Area
        </Button>
      </div>

      <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 md:gap-6 xl:grid-cols-4">
        <div className="rounded-md shadow-lg border border-gray-200 bg-white p-2">
          <div className="rounded-2xl border-gray-200 bg-white p-5 ">
            <p className="text-gray-500 text-theme-sm dark:text-gray-400">
              Total Areas
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
          <div className="rounded-2xl border-gray-200 bg-white p-5 ">
            <p className="text-gray-500 text-theme-sm dark:text-gray-400">
              Storage Areas
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
          <div className="rounded-2xl border-gray-200 bg-white p-5 ">
            <p className="text-gray-500 text-theme-sm dark:text-gray-400">
              Total Pallet Capacity
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
          <div className="rounded-2xl border-gray-200 bg-white p-5 ">
            <p className="text-gray-500 text-theme-sm dark:text-gray-400">
              Special Environments
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
                placeholder="Search Areas…"
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
              Add New Area
            </h2>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <Label>
                  Area Code<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={formData.area_code}
                  onChange={handleChange('area_code')}
                  onKeyUp={() => handleRemove('area_code')}
                  error={!!errors.area_code}
                  hint={errors.area_code}
                />
              </div>
              <div>
                <Label>
                  Area Name<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={formData.area_name}
                  onChange={handleChange('area_name')}
                  onKeyUp={() => handleRemove('area_name')}
                  error={!!errors.area_name}
                  hint={errors.area_name}
                />
              </div>

              <div>
                <Label>Area Type</Label>
                <SingleSelectInput
                  options={areaTypeData}
                  valueKey="value"
                  value={formData.area_type}
                  getOptionLabel={(item) => `${item.value}`}
                  onSingleSelectChange={(val) => {
                    console.log('value - ' + val)
                    handleRemove('area_type')
                    setFormData((prev) => ({
                      ...prev,
                      area_type: val,
                    }))
                  }}
                  error={!!errors.area_type}
                  hint={errors.area_type}
                />
              </div>
              <div>
                <Label>Warehouse Code</Label>
                <SingleSelectInput
                  options={warehouseLists}
                  valueKey="id"
                  value={formData.warehouse_id}
                  getOptionLabel={(item) => `${item.warehouse_code}`}
                  onSingleSelectChange={(val) => {
                    console.log('value - ' + val)
                    handleRemove('warehouse_id')
                    setFormData((prev) => ({
                      ...prev,
                      warehouse_id: val,
                    }))
                  }}
                  error={!!errors.warehouse_id}
                  hint={errors.warehouse_id}
                />
              </div>
              <div className="col-span-full">
                <Label>Responsible Person</Label>
                <Input
                  type="text"
                  value={formData.responsible_person}
                  onChange={handleChange('responsible_person')}
                  onKeyUp={() => handleRemove('responsible_person')}
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
                <Label>Email Address</Label>
                <Input
                  type="text"
                  value={formData.email}
                  onChange={handleChange('email')}
                  onKeyUp={() => handleRemove('email')}
                />
              </div>
              <div>
                <Label>Location Description</Label>
                <Input
                  type="text"
                  value={formData.location_description}
                  onChange={handleChange('location_description')}
                  onKeyUp={() => handleRemove('location_description')}
                />
              </div>
              <div>
                <Label>Capacity</Label>
                <Input
                  type="text"
                  value={formData.capacity}
                  onChange={handleChange('capacity')}
                  onKeyUp={() => handleRemove('capacity')}
                />
              </div>
              <div>
                <Label>Dimensions</Label>
                <Input
                  type="text"
                  value={formData.dimensions}
                  onChange={handleChange('dimensions')}
                  onKeyUp={() => handleRemove('dimensions')}
                />
              </div>
              <div>
                <Label>Environmental Conditions</Label>
                <SingleSelectInput
                  options={environmentalConditionsData}
                  valueKey="value"
                  value={formData.environmental_conditions}
                  getOptionLabel={(item) => `${item.value}`}
                  onSingleSelectChange={(val) => {
                    console.log('value - ' + val)
                    handleRemove('environmental_conditions')
                    setFormData((prev) => ({
                      ...prev,
                      environmental_conditions: val,
                    }))
                  }}
                />
              </div>
              <div>
                <Label>Equipment</Label>
                <Input
                  type="text"
                  value={formData.equipment}
                  onChange={handleChange('equipment')}
                  onKeyUp={() => handleRemove('equipment')}
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
                <SingleSelectInput
                  options={statusData}
                  valueKey="id"
                  value={formData.status}
                  getOptionLabel={(item) => `${item.value}`}
                  onSingleSelectChange={(val) => {
                    console.log('value - ' + val)
                    handleRemove('status')
                    setFormData((prev) => ({
                      ...prev,
                      status: val,
                    }))
                  }}
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
            <h2 className="text-xl font-semibold text-gray-800">Area Info</h2>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <Label>
                  Area Code<span className="text-error-500">*</span>
                </Label>
                <Input type="text" value={area.area_code} disabled={true} />
              </div>
              <div>
                <Label>
                  Area Name<span className="text-error-500">*</span>
                </Label>
                <Input type="text" value={area.area_name} disabled={true} />
              </div>

              <div>
                <Label>Area Type</Label>
                <Input type="text" value={area.area_type} disabled={true} />
              </div>
              <div>
                <Label>Warehouse Code</Label>
                <Input
                  type="text"
                  value={area.warehouse_code}
                  disabled={true}
                />
              </div>
              <div className="col-span-full">
                <Label>Responsible Person</Label>
                <Input
                  type="text"
                  value={area.responsible_person}
                  disabled={true}
                />
              </div>
              <div>
                <Label>Phone Number</Label>
                <Input
                  type="number"
                  value={area.phone_number}
                  disabled={true}
                />
              </div>
              <div>
                <Label>Email Address</Label>
                <Input type="text" value={area.email} disabled={true} />
              </div>
              <div>
                <Label>Location Description</Label>
                <Input
                  type="text"
                  value={area.location_description}
                  disabled={true}
                />
              </div>
              <div>
                <Label>Capacity</Label>
                <Input type="text" value={area.capacity} disabled={true} />
              </div>
              <div>
                <Label>Dimensions</Label>
                <Input type="text" value={area.dimensions} disabled={true} />
              </div>
              <div>
                <Label>Environmental Conditions</Label>
                <Input
                  type="text"
                  value={area.environmental_conditions}
                  disabled={true}
                />
              </div>
              <div>
                <Label>Equipment</Label>
                <Input type="text" value={area.equipment} disabled={true} />
              </div>
              <div className="col-span-full">
                <Label>Custom Attributes</Label>
                <TextAreaInput value={area.custom_attributes} disabled={true} />
              </div>
              <div>
                <Label>Status</Label>
                <Input type="text" value={area.status} disabled={true} />
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
            <h2 className="text-xl font-semibold text-gray-800">Update Area</h2>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <Label>
                  Area Code<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={updateFormData.area_code}
                  onChange={handleChange('area_code')}
                  onKeyUp={() => handleRemove('area_code')}
                  error={!!errors.area_code}
                  hint={errors.area_code}
                />
              </div>
              <div>
                <Label>
                  Area Name<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={updateFormData.area_name}
                  onChange={handleChange('area_name')}
                  onKeyUp={() => handleRemove('area_name')}
                  error={!!errors.area_name}
                  hint={errors.area_name}
                />
              </div>

              <div>
                <Label>Area Type</Label>
                <SingleSelectInput
                  options={areaTypeData}
                  valueKey="value"
                  value={updateFormData.area_type}
                  getOptionLabel={(item) => `${item.value}`}
                  onSingleSelectChange={(val) => {
                    console.log('value - ' + val)
                    handleRemove('area_type')
                    setUpdateFormData((prev) => ({
                      ...prev,
                      area_type: val,
                    }))
                  }}
                  error={!!errors.area_type}
                  hint={errors.area_type}
                />
              </div>
              <div>
                <Label>Warehouse Code</Label>
                <SingleSelectInput
                  options={warehouseLists}
                  valueKey="id"
                  value={updateFormData.warehouse_id}
                  getOptionLabel={(item) => `${item.warehouse_code}`}
                  onSingleSelectChange={(val) => {
                    console.log('value - ' + val)
                    handleRemove('warehouse_id')
                    setUpdateFormData((prev) => ({
                      ...prev,
                      warehouse_id: val,
                    }))
                  }}
                  error={!!errors.warehouse_id}
                  hint={errors.warehouse_id}
                />
              </div>
              <div className="col-span-full">
                <Label>Responsible Person</Label>
                <Input
                  type="text"
                  value={updateFormData.responsible_person}
                  onChange={handleChange('responsible_person')}
                  onKeyUp={() => handleRemove('responsible_person')}
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
                <Label>Email Address</Label>
                <Input
                  type="text"
                  value={updateFormData.email}
                  onChange={handleChange('email')}
                  onKeyUp={() => handleRemove('email')}
                />
              </div>
              <div>
                <Label>Location Description</Label>
                <Input
                  type="text"
                  value={updateFormData.location_description}
                  onChange={handleChange('location_description')}
                  onKeyUp={() => handleRemove('location_description')}
                />
              </div>
              <div>
                <Label>Capacity</Label>
                <Input
                  type="text"
                  value={updateFormData.capacity}
                  onChange={handleChange('capacity')}
                  onKeyUp={() => handleRemove('capacity')}
                />
              </div>
              <div>
                <Label>Dimensions</Label>
                <Input
                  type="text"
                  value={updateFormData.dimensions}
                  onChange={handleChange('dimensions')}
                  onKeyUp={() => handleRemove('dimensions')}
                />
              </div>
              <div>
                <Label>Environmental Conditions</Label>
                <SingleSelectInput
                  options={environmentalConditionsData}
                  valueKey="value"
                  value={updateFormData.environmental_conditions}
                  getOptionLabel={(item) => `${item.value}`}
                  onSingleSelectChange={(val) => {
                    console.log('value - ' + val)
                    handleRemove('environmental_conditions')
                    setUpdateFormData((prev) => ({
                      ...prev,
                      environmental_conditions: val,
                    }))
                  }}
                />
              </div>
              <div>
                <Label>Equipment</Label>
                <Input
                  type="text"
                  value={updateFormData.equipment}
                  onChange={handleChange('equipment')}
                  onKeyUp={() => handleRemove('equipment')}
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
                <Label>Status{updateFormData.status}</Label>
                <SingleSelectInput
                  options={statusData}
                  valueKey="id"
                  value={updateFormData.status}
                  getOptionLabel={(item) => `${item.value}`}
                  onSingleSelectChange={(val) => {
                    console.log('value - ' + val)
                    handleRemove('status')
                    setUpdateFormData((prev) => ({
                      ...prev,
                      status: val,
                    }))
                  }}
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
