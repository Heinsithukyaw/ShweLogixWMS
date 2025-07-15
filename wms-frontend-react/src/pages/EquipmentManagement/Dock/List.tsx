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
import MultiSelectInput from '../../../components/form/form-elements/MultiSelectInputs'
import SingleSelectInput from '../../../components/form/form-elements/SelectInputs'
import { TableColumn } from 'react-data-table-component'
import { showConfirm } from '../../../utils/alert'
import Swal from 'sweetalert2'

interface RowData {
  dock_code: string
  dock_name: string
  dock_type: string
  warehouse_code: string
  area: string
  dock_number: string
  capacity: string
  capacity_unit:string
  dimensions: string
  equipment_features: string
  last_maintenance_date: string
  assigned_staff: string
  operating_hours: string
  remarks:string,
  custom_attributes:string, 
  status: any
  [key: string]: any
}

interface Errors {
  dock_code?: string
  dock_name?: string
  dock_type?: string
  warehouse_id?: string
  area_id?:string
  dock_number?:string
  capacity?:string
  capacity_unit?:string
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
    selector: (row: RowData) => row.dock_code,
    sortable: true,
  },
  {
    name: 'Name',
    selector: (row: RowData) => row.dock_name,
    sortable: true,
  },
  {
    name: 'Type',
    selector: (row: RowData) => row.dock_type || '-',
    sortable: true,
  },
  {
    name: 'Warehouse Code',
    selector: (row: RowData) => row.warehouse_code || '-',
    sortable: true,
  },
  {
    name: 'Area Code',
    selector: (row: RowData) => row.area_code || '-',
    sortable: true,
  },
  {
    name: 'Dock Number',
    selector: (row: RowData) => row.dock_number || '-',
    sortable: true,
  },
  {
    name: 'Capacity',
    selector: (row: RowData) => row.capacity || '-',
    sortable: true,
  },
  {
    name: 'Capacity Unit',
    selector: (row: RowData) => row.capacity_unit || '-',
    sortable: true,
  },
  {
    name: 'Dimensions',
    selector: (row: RowData) => row.dimensions || '-',
    sortable: true,
  },
  {
    name: 'Equipment Features',
    selector: (row: RowData) =>
      Array.isArray(JSON.parse(row?.equipment_features ?? '[]'))
        ? JSON.parse(row?.equipment_features).join(', ')
        : '-',
    sortable: true,
  },
  {
    name: 'Last Maintenance Date',
    selector: (row: RowData) => row.last_maintenance_date || '-',
    sortable: true,
  },
  {
    name: 'Next Maintenance Date',
    selector: (row: RowData) => row.next_maintenance_date || '-',
    sortable: true,
  },
  {
    name: 'Assigned Staff',
    selector: (row: RowData) => row.assigned_staff || '-',
    sortable: true,
  },
  {
    name: 'Operating Hours',
    selector: (row: RowData) => row.operating_hours || '-',
    sortable: true,
  },
  {
    name: 'Status',
    selector: (row: RowData) => row.status,
    sortable: true,
  },
]

const List: React.FC = () => {
  const [isCreateOpen, setIsCreateOpen] = useState(false)
  const [isViewOpen, setIsViewOpen] = useState(false)
  const [isUpdateOpen, setIsUpdateOpen] = useState(false)
  const [errors, setErrors] = useState<Errors>({})
  const [warehouseLists, setWarehouseLists] = useState<any>([])
  const [areaLists, setAreaLists] = useState<any>([])
  const [areas, setAreas] = useState<any>([])

  const [dockEquipmentLists, setDockEquipmentLists] = useState<any>([])
  
  const [dockEquipment, setDockEquipment] = useState<any>({})
  const [isPageLoading, setIsPageLoading] = useState(false)
  const [isLoading, setIsLoading] = useState(false)
  const { showToast } = provideUtility()

  const dockTypeData = [
    { id: 1, value: 'Receiving' },
    { id: 2, value: 'Shipping' },
    { id: 3, value: 'Dual-Purpose' },
    { id: 4, value: 'Specialized' },
    { id: 5, value: 'Cross-Docking' },
    { id: 6, value: 'Container Dock' },
    { id: 7, value: 'Rail Dock' },
  ]

  const equipmentFeaturesData = [
    { id: 1, value: 'Dock Leveler' },
    { id: 2, value: 'Dock Shelter' },
    { id: 3, value: 'Dock Seal' },
    { id: 4, value: 'Dock Light' },
    { id: 5, value: 'Dock Bumpers' },
    { id: 6, value: 'Wheel Chocks' },
    { id: 7, value: 'Dock Lock System' },
    { id: 8, value: 'Air Curtain' },
    { id: 9, value: 'Traffic Light System' },
    { id: 10, value: 'Loading Ramp' },
  ]

  const operatingHoursData = [
    { id: 1, value: '24/7' },
    { id: 2, value: '6:00 - 18:00' },
    { id: 3, value: '7:00 - 19:00' },
    { id: 4, value: '8:00 - 20:00' },
    { id: 5, value: '6:00 - 14:00' },
    { id: 6, value: '14:00 - 22:00' },
    { id: 7, value: '22:00 - 6:00' },
  ]

  const statusData = [
    { id: 0, value: 'Under Maintenance' },
    { id: 1, value: 'Out Of Service' },
    { id: 2, value: 'Operational' },
    { id: 3, value: 'Scheduled Maintenance' },
    { id: 4, value: 'Reserved' },
  ]

  const capacityUnitData = [
    { id: 1, value: 'items' },
    { id: 2, value: 'kg' },
    { id: 3, value: 'm²' },
    { id: 4, value: 'liters' },
    { id: 5, value: 'pallets' },
  ]

  const [formData, setFormData] = useState({
    dock_code : '',
    dock_name : '',
    dock_type : '',
    warehouse_id : '',
    area_id : '',
    dock_number : '',
    capacity : '',
    capacity_unit : '',
    dimensions : '',
    equipment_features : [],
    last_maintenance_date : '',
    next_maintenance_date : '',
    assigned_staff : '',
    operating_hours : '',
    remarks : '',
    custom_attributes : '',
    status : '',
  })

  const [updateFormData, setUpdateFormData] = useState({
    id: '',
    dock_code: '',
    dock_name: '',
    dock_type: '',
    warehouse_id: '',
    area_id: '',
    dock_number: '',
    capacity: '',
    capacity_unit: '',
    dimensions: '',
    equipment_features: [],
    last_maintenance_date: '',
    next_maintenance_date: '',
    assigned_staff: '',
    operating_hours: '',
    remarks: '',
    custom_attributes: '',
    status: '',
  })

  useEffect(() => {
    setIsPageLoading(true)
    fetchDockEquipmentLists()
    fetchWarehouseLists()
    fetchAreaLists()
  }, [])

  const fetchDockEquipmentLists = async () => {
    console.log('dock dock')
    try {
      setIsPageLoading(true)
      const res = await http.fetchDataWithToken('dock-equipments')
      console.log(res)

      setDockEquipmentLists(res.data?.data || [])
    } catch (err) {
      setIsPageLoading(false)
      console.error('Failed to fetch Dock Equipment lists:', err)
    } finally {
      setIsPageLoading(false)
    }
  }

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
    setDockEquipment(
      dockEquipmentLists?.find((x: any) => x.id === row.id)
    )
    setIsViewOpen(true)
  }

  const handleCreate = () => {
    setErrors({
      dock_code: '',
      dock_name: '',
      dock_type: '',
      warehouse_id: '',
      area_id:'',
      dock_number:'',
      capacity:'',
      capacity_unit:'',
      status:''
    })
    setIsCreateOpen(true)
  }

  const handleEdit = (row: any) => {
    setIsUpdateOpen(true)
    setErrors({
      dock_code: '',
      dock_name: '',
      dock_type: '',
      warehouse_id: '',
      area_id: '',
      dock_number: '',
      capacity: '',
      capacity_unit: '',
      status: '',
    })
    const dock_equipment_data = dockEquipmentLists.find(
      (x: any) => x.id === row.id
    )

    if (dock_equipment_data) {
        getArea(dock_equipment_data.warehouse_id)
        setUpdateFormData({
          id: dock_equipment_data.id,
          dock_code: dock_equipment_data.dock_number,
          dock_name: dock_equipment_data.dock_name,
          dock_type: dock_equipment_data.dock_type,
          warehouse_id: dock_equipment_data.warehouse_id,
          area_id: dock_equipment_data.area_id,
          dock_number: dock_equipment_data.dock_number,
          capacity: dock_equipment_data.capacity,
          capacity_unit: dock_equipment_data.capacity_unit,
          dimensions: dock_equipment_data.dimensions,
          equipment_features: JSON.parse(dock_equipment_data.equipment_features),
          last_maintenance_date: dock_equipment_data.last_maintenance_date,
          next_maintenance_date: dock_equipment_data.next_maintenance_date,
          assigned_staff: dock_equipment_data.assigned_staff,
          operating_hours: dock_equipment_data.operating_hours,
          remarks: dock_equipment_data.remarks,
          custom_attributes: dock_equipment_data.custom_attributes,
          status: dock_equipment_data.status,
        })
      
    }
  }

  const getArea = (val: any) => {
    // alert(val)
    setAreas(areaLists.filter((x: any) => x.warehouse_id == val))
  }

  const handleDelete = async (row: any) => {
    const confirmed = await showConfirm(
      'Are you sure?',
      'This action cannot be undone.'
    )

    if (!confirmed) return

    try {
      const response = await http.deleteDataWithToken(
        `/dock-equipments/${row.id}`
      )
      console.log(response)
      if (response.status == true) {
        Swal.fire({
          title: 'Deleted!',
          text: 'Dock has been deleted.',
          icon: 'success',
        })
        fetchDockEquipmentLists()
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
      const response = await http.postDataWithToken(
        '/dock-equipments',
        formData
      )
      if (response.status === true) {
        setIsCreateOpen(false)
        showToast('', 'Create Dock Equipment successful', 'top-right', 'success')
        setFormData({
          dock_code: '',
          dock_name: '',
          dock_type: '',
          warehouse_id: '',
          area_id: '',
          dock_number: '',
          capacity: '',
          capacity_unit: '',
          dimensions: '',
          equipment_features: [],
          last_maintenance_date: '',
          next_maintenance_date: '',
          assigned_staff: '',
          operating_hours: '',
          remarks: '',
          custom_attributes: '',
          status: '',
        })
        fetchDockEquipmentLists()
      } else {
        showToast('', 'Something went wrong!.', 'top-right', 'error')
      }
    } catch (err: any) {
      if (err?.status === 422) {
        showToast('', err?.data.message, 'top-right', 'error')
        const apiErrors: Errors = err?.data.errors
        setErrors(apiErrors)
      } else {
        showToast('', 'Create Dock Equipment failed!', 'top-right', 'error')
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
        `/dock-equipments/${updateFormData.id}`,
        updateFormData
      )
      if (response.status === true) {
        setIsUpdateOpen(false)
        showToast('', 'Update Dock Equipement successful', 'top-right', 'success')
        setUpdateFormData({
          id: '',
          dock_code: '',
          dock_name: '',
          dock_type: '',
          warehouse_id: '',
          area_id: '',
          dock_number: '',
          capacity: '',
          capacity_unit: '',
          dimensions: '',
          equipment_features: [],
          last_maintenance_date: '',
          next_maintenance_date: '',
          assigned_staff: '',
          operating_hours: '',
          remarks: '',
          custom_attributes: '',
          status: '',
        })
        fetchDockEquipmentLists()
      } else {
        showToast('', 'Something went wrong!.', 'top-right', 'error')
      }
    } catch (err: any) {
      if (err?.status === 422) {
        showToast('', err?.message, 'top-right', 'error')
        const apiErrors: Errors = err?.errors
        setErrors(apiErrors)
      } else {
        showToast('', 'Update Dock Equipment failed!', 'top-right', 'error')
      }
      console.error(err)
    } finally {
      setIsLoading(false)
    }
  }

  const [filterText, setFilterText] = React.useState('')
  const filteredData = useMemo(() => {
    if (!filterText) return dockEquipmentLists

    return dockEquipmentLists.filter((item: any) =>
      Object.values(item).some(
        (val) =>
          val && val.toString().toLowerCase().includes(filterText.toLowerCase())
      )
    )
  }, [filterText, dockEquipmentLists])

  return (
    <div className="space-y-10">
      <div className="flex justify-between items-center">
        <h1 className="text-xl font-semibold">Dock Equipment Lists</h1>
        <Button variant="primary" size="sm" onClick={handleCreate}>
          Add Dock
        </Button>
      </div>

      <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 md:gap-6 xl:grid-cols-3">
        <div className="rounded-md shadow-lg border border-gray-200 bg-white p-2">
          <div className="rounded-2xl border border-gray-200 bg-white p-5 ">
            <p className="text-gray-500 text-theme-sm dark:text-gray-400">
              Total Docks
            </p>
            <div className="flex items-end justify-between mt-3">
              <div className="">
                <h4 className="text-2xl font-bold text-gray-800 dark:text-white/90">
                  {dockEquipmentLists.length ?? 0}
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
              Operational Docks
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
              Docks Under Maintenance
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
                placeholder="Search Docks…"
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
              Add New Dock
            </h2>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <Label>
                  Dock Code<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={formData.dock_code}
                  onChange={handleChange('dock_code')}
                  onKeyUp={() => handleRemove('dock_code')}
                  error={!!errors.dock_code}
                  hint={errors.dock_code}
                />
              </div>
              <div>
                <Label>
                  Dock Name<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={formData.dock_name}
                  onChange={handleChange('dock_name')}
                  onKeyUp={() => handleRemove('dock_name')}
                  error={!!errors.dock_name}
                  hint={errors.dock_name}
                />
              </div>

              <div>
                <Label>
                  Dock Type<span className="text-error-500">*</span>
                </Label>
                <SingleSelectInput
                  options={dockTypeData}
                  valueKey="value"
                  value={formData.dock_type}
                  getOptionLabel={(item) => `${item.value}`}
                  onSingleSelectChange={(val) => {
                    console.log('value - ' + val)
                    handleRemove('dock_type')
                    setFormData((prev) => ({
                      ...prev,
                      dock_type: val,
                    }))
                  }}
                  error={!!errors.dock_type}
                  hint={errors.dock_type}
                />
              </div>
              <div>
                <Label>
                  Warehouse Code<span className="text-error-500">*</span>
                </Label>
                <SingleSelectInput
                  options={warehouseLists}
                  valueKey="id"
                  value={formData.warehouse_id}
                  getOptionLabel={(item) => `${item.warehouse_code}`}
                  onSingleSelectChange={(val) => {
                    console.log('value - ' + val)
                    handleRemove('warehouse_id')
                    getArea(val)
                    setFormData((prev) => ({
                      ...prev,
                      warehouse_id: val,
                    }))
                  }}
                  error={!!errors.warehouse_id}
                  hint={errors.warehouse_id}
                />
              </div>
              <div>
                <Label>
                  Area Code<span className="text-error-500">*</span>
                </Label>
                <SingleSelectInput
                  options={areas}
                  valueKey="id"
                  value={formData.area_id}
                  getOptionLabel={(item) => `${item.area_code}`}
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
                <Label>
                  Dock Number<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={formData.dock_number}
                  onChange={handleChange('dock_number')}
                  onKeyUp={() => handleRemove('dock_number')}
                  error={!!errors.dock_number}
                  hint={errors.dock_number}
                />
              </div>

              <div>
                <Label>
                  Capacity<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="number"
                  value={formData.capacity}
                  onChange={handleChange('capacity')}
                  onKeyUp={() => handleRemove('capacity')}
                  error={!!errors.capacity}
                  hint={errors.capacity}
                />
              </div>
              <div>
                <Label>
                  Capacity Unit<span className="text-error-500">*</span>
                </Label>
                <SingleSelectInput
                  options={capacityUnitData}
                  valueKey="value"
                  value={formData.capacity_unit}
                  getOptionLabel={(item) => `${item.value}`}
                  onSingleSelectChange={(val) => {
                    handleRemove('capacity_unit')
                    setFormData((prev) => ({
                      ...prev,
                      capacity_unit: val,
                    }))
                  }}
                  error={!!errors.capacity_unit}
                  hint={errors.capacity_unit}
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
                <Label>Equipment Features</Label>
                <MultiSelectInput
                  options={equipmentFeaturesData}
                  valueKey="value"
                  getOptionLabel={(item) => item.value}
                  value={formData.equipment_features}
                  onMultiSelectChange={(val: any) => {
                    handleRemove('equipment_features')
                    setFormData((prev) => ({
                      ...prev,
                      equipment_features: val,
                    }))
                  }}
                />
              </div>
              <div>
                <Label>Last Maintenance Date</Label>
                <Input
                  type="date"
                  value={formData.last_maintenance_date}
                  onChange={handleChange('last_maintenance_date')}
                  onKeyUp={() => handleRemove('last_maintenance_date')}
                />
              </div>
              <div>
                <Label>Next Maintenance Date</Label>
                <Input
                  type="number"
                  value={formData.next_maintenance_date}
                  onChange={handleChange('next_maintenance_date')}
                  onKeyUp={() => handleRemove('next_maintenance_date')}
                />
              </div>

              <div>
                <Label>Assigned Staff</Label>
                <Input
                  type="text"
                  value={formData.assigned_staff}
                  onChange={handleChange('assigned_staff')}
                  onKeyUp={() => handleRemove('assigned_staff')}
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
                <Label>Remarks</Label>
                <TextAreaInput
                  value={formData.remarks}
                  onChange={(value) =>
                    handleChange('remarks')({
                      target: { value },
                    } as React.ChangeEvent<any>)
                  }
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
                <Label>
                  Status<span className="text-error-500">*</span>
                </Label>
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
                  error={!!errors.status}
                  hint={errors.status}
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
              Add New Dock
            </h2>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <Label>
                  Dock Code<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={dockEquipment.dock_code}
                  disabled={true}
                />
              </div>
              <div>
                <Label>
                  Dock Name<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={dockEquipment.dock_name}
                  disabled={true}
                />
              </div>

              <div>
                <Label>
                  Dock Type<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={dockEquipment.dock_type}
                  disabled={true}
                />
              </div>
              <div>
                <Label>
                  Warehouse Code<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={dockEquipment.warehouse_code}
                  disabled={true}
                />
              </div>
              <div>
                <Label>
                  Area Code<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={dockEquipment.area_code}
                  disabled={true}
                />
              </div>
              <div>
                <Label>Dock Number</Label>
                <Input
                  type="text"
                  value={dockEquipment.dock_number}
                  disabled={true}
                />
              </div>

              <div>
                <Label>Capacity</Label>
                <Input
                  type="number"
                  value={dockEquipment.capacity}
                  disabled={true}
                />
              </div>
              <div>
                <Label>Capacity Unit</Label>
                <Input
                  type="text"
                  value={dockEquipment.capacity_unit}
                  disabled={true}
                />
              </div>
              <div>
                <Label>Dimensions</Label>
                <Input
                  type="text"
                  value={dockEquipment.dimensions}
                  disabled={true}
                />
              </div>
              <div>
                <Label>Equipment Features</Label>
                <Input
                  type="text"
                  value={dockEquipment?.equipment_features || '-'}
                  disabled={true}
                />
              </div>
              <div>
                <Label>Last Maintenance Date</Label>
                <Input
                  type="date"
                  value={dockEquipment.last_maintenance_date}
                  disabled={true}
                />
              </div>
              <div>
                <Label>Next Maintenance Date</Label>
                <Input
                  type="number"
                  value={dockEquipment.next_maintenance_date}
                  disabled={true}
                />
              </div>

              <div>
                <Label>Assigned Staff</Label>
                <Input
                  type="text"
                  value={dockEquipment.assigned_staff}
                  disabled={true}
                />
              </div>
              <div>
                <Label>Operating Hours</Label>
                <Input
                  type="text"
                  value={dockEquipment.operating_hours}
                  disabled={true}
                />
              </div>

              <div className="col-span-full">
                <Label>Remarks</Label>
                <TextAreaInput value={dockEquipment.remarks} disabled={true} />
              </div>

              <div className="col-span-full">
                <Label>Custom Attributes</Label>
                <TextAreaInput
                  value={dockEquipment.custom_attributes}
                  disabled={true}
                />
              </div>

              <div>
                <Label>
                  Status<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={dockEquipment.status}
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
              Edit Dock
            </h2>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <Label>
                  Dock Code<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={updateFormData.dock_code}
                  onChange={handleChange('dock_code')}
                  onKeyUp={() => handleRemove('dock_code')}
                  error={!!errors.dock_code}
                  hint={errors.dock_code}
                />
              </div>
              <div>
                <Label>
                  Dock Name<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={updateFormData.dock_name}
                  onChange={handleChange('dock_name')}
                  onKeyUp={() => handleRemove('dock_name')}
                  error={!!errors.dock_name}
                  hint={errors.dock_name}
                />
              </div>

              <div>
                <Label>
                  Dock Type<span className="text-error-500">*</span>
                </Label>
                <SingleSelectInput
                  options={dockTypeData}
                  valueKey="value"
                  value={updateFormData.dock_type}
                  getOptionLabel={(item) => `${item.value}`}
                  onSingleSelectChange={(val) => {
                    console.log('value - ' + val)
                    handleRemove('dock_type')
                    setUpdateFormData((prev) => ({
                      ...prev,
                      dock_type: val,
                    }))
                  }}
                  error={!!errors.dock_type}
                  hint={errors.dock_type}
                />
              </div>
              <div>
                <Label>
                  Warehouse Code<span className="text-error-500">*</span>
                </Label>
                <SingleSelectInput
                  options={warehouseLists}
                  valueKey="id"
                  value={updateFormData.warehouse_id}
                  getOptionLabel={(item) => `${item.warehouse_code}`}
                  onSingleSelectChange={(val) => {
                    console.log('value - ' + val)
                    handleRemove('warehouse_id')
                    getArea(val)
                    setUpdateFormData((prev) => ({
                      ...prev,
                      warehouse_id: val,
                    }))
                  }}
                  error={!!errors.warehouse_id}
                  hint={errors.warehouse_id}
                />
              </div>
              <div>
                <Label>
                  Area Code<span className="text-error-500">*</span>
                </Label>
                <SingleSelectInput
                  options={areas}
                  valueKey="id"
                  value={updateFormData.area_id}
                  getOptionLabel={(item) => `${item.area_code}`}
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
                <Label>
                  Dock Number<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={updateFormData.dock_number}
                  onChange={handleChange('dock_number')}
                  onKeyUp={() => handleRemove('dock_number')}
                  error={!!errors.dock_number}
                  hint={errors.dock_number}
                />
              </div>

              <div>
                <Label>
                  Capacity<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="number"
                  value={updateFormData.capacity}
                  onChange={handleChange('capacity')}
                  onKeyUp={() => handleRemove('capacity')}
                  error={!!errors.capacity}
                  hint={errors.capacity}
                />
              </div>
              <div>
                <Label>
                  Capacity Unit<span className="text-error-500">*</span>
                </Label>
                <SingleSelectInput
                  options={capacityUnitData}
                  valueKey="value"
                  value={updateFormData.capacity_unit}
                  getOptionLabel={(item) => `${item.value}`}
                  onSingleSelectChange={(val) => {
                    handleRemove('capacity_unit')
                    setUpdateFormData((prev) => ({
                      ...prev,
                      capacity_unit: val,
                    }))
                  }}
                  error={!!errors.capacity_unit}
                  hint={errors.capacity_unit}
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
                <Label>Equipment Features</Label>
                <MultiSelectInput
                  options={equipmentFeaturesData}
                  valueKey="value"
                  getOptionLabel={(item) => item.value}
                  value={updateFormData.equipment_features}
                  onMultiSelectChange={(val: any) => {
                    handleRemove('equipment_features')
                    setUpdateFormData((prev) => ({
                      ...prev,
                      equipment_features: val,
                    }))
                  }}
                />
              </div>
              <div>
                <Label>Last Maintenance Date</Label>
                <Input
                  type="date"
                  value={updateFormData.last_maintenance_date}
                  onChange={handleChange('last_maintenance_date')}
                  onKeyUp={() => handleRemove('last_maintenance_date')}
                />
              </div>
              <div>
                <Label>Next Maintenance Date</Label>
                <Input
                  type="number"
                  value={updateFormData.next_maintenance_date}
                  onChange={handleChange('next_maintenance_date')}
                  onKeyUp={() => handleRemove('next_maintenance_date')}
                />
              </div>

              <div>
                <Label>Assigned Staff</Label>
                <Input
                  type="text"
                  value={updateFormData.assigned_staff}
                  onChange={handleChange('assigned_staff')}
                  onKeyUp={() => handleRemove('assigned_staff')}
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
                    setUpdateFormData((prev) => ({
                      ...prev,
                      operating_hours: val,
                    }))
                  }}
                />
              </div>

              <div className="col-span-full">
                <Label>Remarks</Label>
                <TextAreaInput
                  value={updateFormData.remarks}
                  onChange={(value) =>
                    handleChange('remarks')({
                      target: { value },
                    } as React.ChangeEvent<any>)
                  }
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
                <Label>
                  Status<span className="text-error-500">*</span>
                </Label>
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
                  error={!!errors.status}
                  hint={errors.status}
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
