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
import MultiSelectInput from '../../../components/form/form-elements/MultiSelectInputs'
import { TableColumn } from 'react-data-table-component'
import { showConfirm } from '../../../utils/alert'
import Swal from 'sweetalert2'

interface RowData {
  mhe_code: string
  mhe_name: string
  mhe_type: string
  manufacturer: string
  model: string
  serial_number: string
  purchase_date: string
  warranty_expire_date: string
  capacity: string
  capacity_unit: string
  current_location_detail: string
  home_location: string
  shift_availability: string
  operator_assigned: string
  maintenance_schedule_type: string
  maintenance_frequency: string
  last_maintenance_date: string
  last_service_type: string
  last_maintenance_due_date: string
  safety_inspection_due_date: string
  safety_certification_expire_date: string
  safety_features: string
  uptime_percentage_monthly: string
  maintenance_cost: string
  energy_consumption_per_hour: string
  depreciation_start_date: string
  depreciation_method: string
  estimated_useful_life_years: string
  supplier_id: string
  supplier_contact_id: string
  expected_replacement_date: string
  disposal_date: string
  replacement_mhe_code: string
  usage_status: any
  status: any
  [key: string]: any
}

interface Errors {
  mhe_code?: string
  mhe_name?: string
  mhe_type?: string
  manufacturer?: string
  model?: string
  serial_number?: string
  purchase_date?: string
  warranty_expire_date?: string
  capacity?: string
  capacity_unit?: string
  usage_status?:string
  status?:string
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
    selector: (row: RowData) => row.mhe_code,
    sortable: true,
  },
  {
    name: 'Name',
    selector: (row: RowData) => row.mhe_name,
    sortable: true,
  },
  {
    name: 'Type',
    selector: (row: RowData) => row.mhe_type || '-',
    sortable: true,
  },
  {
    name: 'Manufacturer',
    selector: (row: RowData) => row.manufacturer || '-',
    sortable: true,
  },
  {
    name: 'Model',
    selector: (row: RowData) => row.model || '-',
    sortable: true,
  },
  {
    name: 'Serial Number',
    selector: (row: RowData) => row.serial_number || '-',
    sortable: true,
  },
  {
    name: 'Purchase Date',
    selector: (row: RowData) => row.purchase_date || '-',
    sortable: true,
  },
  {
    name: 'Warranty Expire Date',
    selector: (row: RowData) => row.warranty_expire_date || '-',
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
    name: 'Current Location Detail',
    selector: (row: RowData) => row.current_location_detail || '-',
    sortable: true,
  },
  {
    name: 'Home Location',
    selector: (row: RowData) => row.home_location || '-',
    sortable: true,
  },
  {
    name: 'Shift Availability',
    selector: (row: RowData) => row.shift_availability || '-',
    sortable: true,
  },
  {
    name: 'Operator Assigned',
    selector: (row: RowData) => row.operator_assigned || '-',
    sortable: true,
  },
  {
    name: 'Maintenance Schedule Type',
    selector: (row: RowData) => row.maintenance_schedule_type || '-',
    sortable: true,
  },
  {
    name: 'Maintenance Frequency',
    selector: (row: RowData) => row.maintenance_frequency || '-',
    sortable: true,
  },
  {
    name: 'last_maintenance_date',
    selector: (row: RowData) => row.last_maintenance_date || '-',
    sortable: true,
  },
  {
    name: 'Last Service Type',
    selector: (row: RowData) => row.last_service_type || '-',
    sortable: true,
  },
  {
    name: 'Last Maintenance Due Date',
    selector: (row: RowData) => row.last_maintenance_due_date || '-',
    sortable: true,
  },
  {
    name: 'Safety Inspection Due Date',
    selector: (row: RowData) => row.safety_inspection_due_date || '-',
    sortable: true,
  },
  {
    name: 'Safety Certification Expire Date',
    selector: (row: RowData) => row.safety_certification_expire_date || '-',
    sortable: true,
  },
  {
    name: 'Safety Features',
    selector: (row: RowData) =>
      Array.isArray(JSON.parse(row?.safety_features ?? '[]'))
        ? JSON.parse(row?.safety_features).join(', ')
        : '-',
    sortable: true,
  },

  {
    name: 'Uptime Percentage(Monthly)',
    selector: (row: RowData) => row.uptime_percentage_monthly || '-',
    sortable: true,
  },
  {
    name: 'Maintenance Cost',
    selector: (row: RowData) => row.maintenance_cost || '-',
    sortable: true,
  },
  {
    name: 'Energy Consumption (per hours)',
    selector: (row: RowData) => row.energy_consumption_per_hour || '-',
    sortable: true,
  },
  {
    name: 'Depreciation Start Date',
    selector: (row: RowData) => row.depreciation_start_date || '-',
    sortable: true,
  },
  {
    name: 'Depreciation Method',
    selector: (row: RowData) => row.depreciation_method || '-',
    sortable: true,
  },
  {
    name: 'Estimated Useful Life (Years)',
    selector: (row: RowData) => row.estimated_useful_life_years || '-',
    sortable: true,
  },
  {
    name: 'Supplier',
    selector: (row: RowData) => row.supplier_code || '-',
    sortable: true,
  },
  {
    name: 'Supplier Contact',
    selector: (row: RowData) => {
      if (row.supplier_contact_name || row.supplier_contact_email) {
        return `${row.supplier_contact_name} (${
          row.supplier_contact_email == null ? '-' : row.supplier_contact_email
        })`
      }
      return '-'
    },
    sortable: true,
  },
  {
    name: 'Expected Replacement Date',
    selector: (row: RowData) => row.expected_replacement_date || '-',
    sortable: true,
  },
  {
    name: 'Disposal Date',
    selector: (row: RowData) => row.disposal_date || '-',
    sortable: true,
  },
  {
    name: 'Replacement MHE Code',
    selector: (row: RowData) => row.replacement_mhe_code || '-',
    sortable: true,
  },
  {
    name: 'Usage Status',
    selector: (row: RowData) => row.usage_status || '-',
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
  const [materialHandlingLists, setMaterialHandlingLists] = useState<any>([])
  const [supplierLists, setSupplierLists] = useState<any>([])
  const [supplierContactLists, setSupplierContactLists] = useState<any>([])
  const [supplierContacts,setSupplierContacts] = useState([])
  const [materialHandling, setMaterialHandling] = useState<any>({})
  const [isPageLoading, setIsPageLoading] = useState(false)
  const [isLoading, setIsLoading] = useState(false)
  const { showToast } = provideUtility()

  const mheTypeData = [
    { id: 1, value: 'Electric Stacker' },
    { id: 2, value: 'Electric Tow Tractor' },
    { id: 3, value: 'Electric Scissor Lift' },
    { id: 4, value: 'Manual Pallet Jack' },
    { id: 5, value: 'Electric Utility Cart' },
    { id: 6, value: 'Articulating Boom Lift' },
    { id: 7, value: 'Electric Tugger Train' },
    { id: 8, value: 'Electric Platform Truck' },
    { id: 9, value: 'Electric Walkie Stacker' },
    { id: 10, value: 'Tugger Train' },
  ]

  const depreciationMethodData = [
    { id: 1, value: 'Straight-Line' },
    { id: 2, value: 'Declining Balance' },
  ]

  const lastServiceTypeData = [
    { id: 1, value: 'Hydraulic System Overhaul' },
    { id: 2, value: 'Wheel & Hydraulic Check' },
    { id: 3, value: 'Battery & Motor Check' },
    { id: 4, value: 'Boom & Hydraulic Inspection' },
    { id: 5, value: 'Towing System Check' },
    { id: 6, value: 'Platform & Drive Check' },
    { id: 7, value: 'Lift Mechanism Check' },
    { id: 8, value: 'Engine & Brake System Check' },
  ]

  const shiftAvaData = [
    { id: 1, value: 'All Shifts' },
    { id: 2, value: 'Day Shifts' },
  ]

  const safetyFeaturesData = [
    { id: 1, value: 'Guard Retails' },
    { id: 2, value: 'Emergency Lowering' },
    { id: 3, value: 'Ergonomic Handle' },
    { id: 4, value: 'Load Guard' },
    { id: 5, value: 'HeadLights' },
    { id: 6, value: 'Brake Lights' },
    { id: 7, value: 'Horn' },
    { id: 8, value: 'Platform Controls' },
    { id: 9, value: 'Ground Controls' },
    { id: 10, value: 'Emergency Stop' },
    { id: 11, value: 'Warning Lights' },
    { id: 12, value: 'Speed Controls' },
    { id: 13, value: 'Load Sensor' },
    { id: 14, value: 'Load Backrest' },
    { id: 15, value: 'Foot Protection' },
    { id: 16, value: 'Multiple Trailers' },
    { id: 17, value: 'Tow Hitch' },
    { id: 18, value: 'Signal Lights' },
  ]

  const usageStatusData = [
    { id: 1, value: 'Available' },
    { id: 2, value: 'Maintenance' },
    { id: 3, value: 'In Use' },
  ]

  const statusData = [
    { id: 1, value: 'Operational' },
    { id: 2, value: 'Under Maintenance' },
  ]

  const maintenanceScheduleTypeData = [
    { id: 1, value: 'Time-based' },
    { id: 2, value: 'Usage-based' },
  ]

  const currencyData = [
    { id: 1, value: 'USD' },
    { id: 2, value: 'EUR' },
    { id: 3, value: 'GBD' },
    { id: 4, value: 'JPY' },
    { id: 5, value: 'CNY' },
  ]

  const capacityUnitData = [
    { id: 1, value: 'items' },
    { id: 2, value: 'kg' },
    { id: 3, value: 'm²' },
    { id: 4, value: 'liters' },
    { id: 5, value: 'pallets' },
  ]

  const [formData, setFormData] = useState({
    mhe_code: '',
    mhe_name: '',
    mhe_type: '',
    manufacturer: '',
    model: '',
    serial_number: '',
    purchase_date: '',
    warranty_expire_date: '',
    capacity: '',
    capacity_unit: '',
    current_location_detail: '',
    home_location: '',
    shift_availability: '',
    operator_assigned: '',
    maintenance_schedule_type: '',
    maintenance_frequency: '',
    last_maintenance_date: '',
    last_service_type: '',
    last_maintenance_due_date: '',
    safety_inspection_due_date: '',
    safety_certification_expire_date: '',
    safety_features: [],
    uptime_percentage_monthly: '',
    maintenance_cost: '',
    currency: '',
    energy_consumption_per_hour: '',
    depreciation_start_date: '',
    depreciation_method: '',
    estimated_useful_life_year: '',
    supplier_id: '',
    supplier_contact_id: '',
    expected_replacement_date: '',
    disposal_date: '',
    replacement_mhe_id: '',
    usage_status: '',
    remark: '',
    custom_attributes: '',
    status: '',
  })

  const [updateFormData, setUpdateFormData] = useState({
    id: '',
    mhe_code: '',
    mhe_name: '',
    mhe_type: '',
    manufacturer: '',
    model: '',
    serial_number: '',
    purchase_date: '',
    warranty_expire_date: '',
    capacity: '',
    capacity_unit: '',
    current_location_detail: '',
    home_location: '',
    shift_availability: '',
    operator_assigned: '',
    maintenance_schedule_type: '',
    maintenance_frequency: '',
    last_maintenance_date: '',
    last_service_type: '',
    last_maintenance_due_date: '',
    safety_inspection_due_date: '',
    safety_certification_expire_date: '',
    safety_features: [],
    uptime_percentage_monthly: '',
    maintenance_cost: '',
    currency: '',
    energy_consumption_per_hour: '',
    depreciation_start_date: '',
    depreciation_method: '',
    estimated_useful_life_year: '',
    supplier_id: '',
    supplier_contact_id: '',
    expected_replacement_date: '',
    disposal_date: '',
    replacement_mhe_id: '',
    usage_status: '',
    remark: '',
    custom_attributes: '',
    status: '',

  })

  useEffect(() => {
    setIsPageLoading(true)
    fetchMaterialHandlingLists()
    fetchBusinessPartyLists()
    fetchBusinessContactLists()
  }, [])

  const fetchMaterialHandlingLists = async () => {
    try {
      setIsPageLoading(true)
      const res = await http.fetchDataWithToken('material-handling-eqs')
      console.log(res)

      setMaterialHandlingLists(res.data?.data || [])
    } catch (err) {
      setIsPageLoading(false)
      console.error('Failed to fetch Material Handling lists:', err)
    } finally {
      setIsPageLoading(false)
    }
  }

  const fetchBusinessContactLists = async () => {
    try {
      setIsPageLoading(true)
      const res = await http.fetchDataWithToken('business-contacts')
      console.log(res.data)
      setSupplierContactLists(res.data?.data || [])
    } catch (err) {
      setIsPageLoading(false)
      console.error('Failed to fetch Supplier Contact lists:', err)
    } finally {
      setIsPageLoading(false)
    }
  }

  const fetchBusinessPartyLists = async () => {
    try {
      setIsPageLoading(true)
      const res = await http.fetchDataWithToken('business-parties')
      console.log(res.data)
      setSupplierLists(
        res.data?.data.filter((x: any) => x.party_type === 'Supplier') || []
      )
    } catch (err) {
      setIsPageLoading(false)
      console.error('Failed to fetch Supplier lists:', err)
    } finally {
      setIsPageLoading(false)
    }
  }

  const getSupplierContact = (val:any) => {
    setSupplierContacts(supplierContactLists.filter((x:any) => x.business_party_id == val))
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
    setMaterialHandling(
      materialHandlingLists?.find((x: any) => x.id === row.id)
    )
    setIsViewOpen(true)
  }

  const handleCreate = () => {
    setErrors({
      mhe_code: '',
      mhe_name: '',
      mhe_type: '',
      manufacturer: '',
      model: '',
      serial_number: '',
      purchase_date: '',
      warranty_expire_date: '',
      capacity: '',
      capacity_unit: '',
      usage_status:'',
      status:''
    })
    setIsCreateOpen(true)
  }

  const handleEdit = (row: any) => {
    setIsUpdateOpen(true)
    setErrors({
      mhe_code: '',
      mhe_name: '',
      mhe_type: '',
      manufacturer: '',
      model: '',
      serial_number: '',
      purchase_date: '',
      warranty_expire_date: '',
      capacity: '',
      capacity_unit: '',
      usage_status: '',
      status: '',
    })
    const material_handling_data = materialHandlingLists.find(
      (x: any) => x.id === row.id
    )

    if (material_handling_data) {
        console.log(material_handling_data.safety_features)
        getSupplierContact(material_handling_data.supplier_id)
        setUpdateFormData({
          id: material_handling_data.id,
          mhe_code: material_handling_data.mhe_code,
          mhe_name: material_handling_data.mhe_name,
          mhe_type: material_handling_data.mhe_type,
          manufacturer: material_handling_data.manufacturer,
          model: material_handling_data.model,
          serial_number: material_handling_data.serial_number,
          purchase_date: material_handling_data.purchase_date,
          warranty_expire_date: material_handling_data.warranty_expire_date,
          capacity: material_handling_data.capacity,
          capacity_unit: material_handling_data.capacity_unit,
          current_location_detail:
            material_handling_data.current_location_detail,
          home_location: material_handling_data.home_location,
          shift_availability: material_handling_data.shift_availability,
          operator_assigned: material_handling_data.operator_assigned,
          maintenance_schedule_type:
            material_handling_data.maintenance_schedule_type,
          maintenance_frequency: material_handling_data.maintenance_frequency,
          last_maintenance_date: material_handling_data.last_maintenance_date,
          last_service_type: material_handling_data.last_service_type,
          last_maintenance_due_date:
            material_handling_data.last_maintenance_due_date,
          safety_inspection_due_date:
            material_handling_data.safety_inspection_due_date,
          safety_certification_expire_date:
            material_handling_data.safety_certification_expire_date,
          safety_features: JSON.parse(material_handling_data.safety_features),
          uptime_percentage_monthly:
            material_handling_data.uptime_percentage_monthly,
          maintenance_cost: material_handling_data.maintenance_cost,
          currency: material_handling_data.currency,
          energy_consumption_per_hour:
            material_handling_data.energy_consumption_per_hour,
          depreciation_start_date:
            material_handling_data.depreciation_start_date,
          depreciation_method: material_handling_data.depreciation_method,
          estimated_useful_life_year:
            material_handling_data.estimated_useful_life_year,
          supplier_id: material_handling_data.supplier_id,

          supplier_contact_id: material_handling_data.supplier_contact_id,
          expected_replacement_date:
            material_handling_data.expected_replacement_date,
          disposal_date: material_handling_data.disposal_date,
          replacement_mhe_id: material_handling_data.replacement_mhe_id,
          usage_status: material_handling_data.usage_status_value,

          remark: material_handling_data.remark,
          custom_attributes: material_handling_data.custom_attributes,
          status: material_handling_data.status_value,
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
      const response = await http.deleteDataWithToken(
        `/material-handling-eqs/${row.id}`
      )
      console.log(response)
      if (response.status == true) {
        Swal.fire({
          title: 'Deleted!',
          text: 'Material Handling has been deleted.',
          icon: 'success',
        })
        fetchMaterialHandlingLists()
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
        '/material-handling-eqs',
        formData
      )
      if (response.status === true) {
        setIsCreateOpen(false)
        showToast('', 'Create Material Handling successful', 'top-right', 'success')
        setFormData({
          mhe_code: '',
          mhe_name: '',
          mhe_type: '',
          manufacturer: '',
          model: '',
          serial_number: '',
          purchase_date: '',
          warranty_expire_date: '',
          capacity: '',
          capacity_unit: '',
          current_location_detail: '',
          home_location: '',
          shift_availability: '',
          operator_assigned: '',
          maintenance_schedule_type: '',
          maintenance_frequency: '',
          last_maintenance_date: '',
          last_service_type: '',
          last_maintenance_due_date: '',
          safety_inspection_due_date: '',
          safety_certification_expire_date: '',
          safety_features: [],
          uptime_percentage_monthly: '',
          maintenance_cost: '',
          currency: '',
          energy_consumption_per_hour: '',
          depreciation_start_date: '',
          depreciation_method: '',
          estimated_useful_life_year: '',
          supplier_id: '',
          supplier_contact_id: '',
          expected_replacement_date: '',
          disposal_date: '',
          replacement_mhe_id: '',
          usage_status: '',
          remark: '',
          custom_attributes: '',
          status: '',
        })
        fetchMaterialHandlingLists()
      } else {
        showToast('', 'Something went wrong!.', 'top-right', 'error')
      }
    } catch (err: any) {
      if (err?.status === 422) {
        showToast('', err?.data.message, 'top-right', 'error')
        const apiErrors: Errors = err?.data.errors
        setErrors(apiErrors)
      } else {
        showToast('', 'Create Material Handling failed!', 'top-right', 'error')
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
        `/material-handling-eqs/${updateFormData.id}`,
        updateFormData
      )
      if (response.status === true) {
        setIsUpdateOpen(false)
        showToast('', 'Update Material Handling successful', 'top-right', 'success')
        setUpdateFormData({
          id: '',
          mhe_code: '',
          mhe_name: '',
          mhe_type: '',
          manufacturer: '',
          model: '',
          serial_number: '',
          purchase_date: '',
          warranty_expire_date: '',
          capacity: '',
          capacity_unit: '',
          current_location_detail: '',
          home_location: '',
          shift_availability: '',
          operator_assigned: '',
          maintenance_schedule_type: '',
          maintenance_frequency: '',
          last_maintenance_date: '',
          last_service_type: '',
          last_maintenance_due_date: '',
          safety_inspection_due_date: '',
          safety_certification_expire_date: '',
          safety_features: [],
          uptime_percentage_monthly: '',
          maintenance_cost: '',
          currency: '',
          energy_consumption_per_hour: '',
          depreciation_start_date: '',
          depreciation_method: '',
          estimated_useful_life_year: '',
          supplier_id: '',
          supplier_contact_id: '',
          expected_replacement_date: '',
          disposal_date: '',
          replacement_mhe_id: '',
          usage_status: '',
         
          remark: '',
          custom_attributes: '',
          status: '',
         
        })
        fetchMaterialHandlingLists()
      } else {
        showToast('', 'Something went wrong!.', 'top-right', 'error')
      }
    } catch (err: any) {
      if (err?.status === 422) {
        showToast('', err?.message, 'top-right', 'error')
        const apiErrors: Errors = err?.errors
        setErrors(apiErrors)
      } else {
        showToast('', 'Update Material Handling failed!', 'top-right', 'error')
      }
      console.error(err)
    } finally {
      setIsLoading(false)
    }
  }

  const [filterText, setFilterText] = React.useState('')
  const filteredData = useMemo(() => {
    if (!filterText) return materialHandlingLists

    return materialHandlingLists.filter((item: any) =>
      Object.values(item).some(
        (val) =>
          val && val.toString().toLowerCase().includes(filterText.toLowerCase())
      )
    )
  }, [filterText, materialHandlingLists])

  return (
    <div className="space-y-10">
      <div className="flex justify-between items-center">
        <h1 className="text-xl font-semibold">MHE Lists</h1>
        <Button variant="primary" size="sm" onClick={handleCreate}>
          Add MHE
        </Button>
      </div>

      <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 md:gap-6 xl:grid-cols-4">
        <div className="rounded-md shadow-lg border border-gray-200 bg-white p-2">
          <div className="rounded-2xl border border-gray-200 bg-white p-5 ">
            <p className="text-gray-500 text-theme-sm dark:text-gray-400">
              Total MHEs
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
              Operational MHEs
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
              Under Maintenance
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
              Available for Use
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
                placeholder="Search MHEs…"
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
            <h2 className="text-xl font-semibold text-gray-800">Add New MHE</h2>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <Label>
                  MHE Code<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={formData.mhe_code}
                  onChange={handleChange('mhe_code')}
                  onKeyUp={() => handleRemove('mhe_code')}
                  error={!!errors.mhe_code}
                  hint={errors.mhe_code}
                />
              </div>
              <div>
                <Label>
                  MHE Name<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={formData.mhe_name}
                  onChange={handleChange('mhe_name')}
                  onKeyUp={() => handleRemove('mhe_name')}
                  error={!!errors.mhe_name}
                  hint={errors.mhe_name}
                />
              </div>

              <div>
                <Label>
                  MHE Type<span className="text-error-500">*</span>
                </Label>
                <SingleSelectInput
                  options={mheTypeData}
                  valueKey="value"
                  value={formData.mhe_type}
                  getOptionLabel={(item) => `${item.value}`}
                  onSingleSelectChange={(val) => {
                    console.log('value - ' + val)
                    handleRemove('mhe_type')
                    setFormData((prev) => ({
                      ...prev,
                      mhe_type: val,
                    }))
                  }}
                  error={!!errors.mhe_type}
                  hint={errors.mhe_type}
                />
              </div>
              <div>
                <Label>
                  Manufacturer<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={formData.manufacturer}
                  onChange={handleChange('manufacturer')}
                  onKeyUp={() => handleRemove('manufacturer')}
                  error={!!errors.manufacturer}
                  hint={errors.manufacturer}
                />
              </div>
              <div className="col-span-full">
                <Label>
                  Model<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={formData.model}
                  onChange={handleChange('model')}
                  onKeyUp={() => handleRemove('model')}
                  error={!!errors.model}
                  hint={errors.model}
                />
              </div>
              <div>
                <Label>
                  Serial Number<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="number"
                  value={formData.serial_number}
                  onChange={handleChange('serial_number')}
                  onKeyUp={() => handleRemove('serial_number')}
                  error={!!errors.model}
                  hint={errors.model}
                />
              </div>
              <div>
                <Label>
                  Purchase Date<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="date"
                  value={formData.purchase_date}
                  onChange={handleChange('purchase_date')}
                  onKeyUp={() => handleRemove('purchase_date')}
                  error={!!errors.purchase_date}
                  hint={errors.purchase_date}
                />
              </div>
              <div>
                <Label>
                  Warranty Expire Date<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="date"
                  value={formData.warranty_expire_date}
                  onChange={handleChange('warranty_expire_date')}
                  onKeyUp={() => handleRemove('warranty_expire_date')}
                  error={!!errors.warranty_expire_date}
                  hint={errors.warranty_expire_date}
                />
              </div>
              <div>
                <Label>
                  Capacity<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
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
                    console.log('value - ' + val)
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
                <Label>Current Location Detail</Label>
                <Input
                  type="text"
                  value={formData.current_location_detail}
                  onChange={handleChange('current_location_detail')}
                  onKeyUp={() => handleRemove('current_location_detail')}
                />
              </div>
              <div>
                <Label>Home Location</Label>
                <Input
                  type="text"
                  value={formData.home_location}
                  onChange={handleChange('home_location')}
                  onKeyUp={() => handleRemove('home_location')}
                />
              </div>
              <div>
                <Label>Shift Availability</Label>
                <SingleSelectInput
                  options={shiftAvaData}
                  valueKey="value"
                  value={formData.shift_availability}
                  getOptionLabel={(item) => `${item.value}`}
                  onSingleSelectChange={(val) => {
                    console.log('value - ' + val)
                    handleRemove('shift_availability')
                    setFormData((prev) => ({
                      ...prev,
                      shift_availability: val,
                    }))
                  }}
                />
              </div>
              <div>
                <Label>Operator Assigned</Label>
                <Input
                  type="text"
                  value={formData.operator_assigned}
                  onChange={handleChange('operator_assigned')}
                  onKeyUp={() => handleRemove('operator_assigned')}
                />
              </div>
              <div>
                <Label>Maintenance Schedule Type</Label>
                <SingleSelectInput
                  options={maintenanceScheduleTypeData}
                  valueKey="value"
                  value={formData.maintenance_schedule_type}
                  getOptionLabel={(item) => `${item.value}`}
                  onSingleSelectChange={(val) => {
                    console.log('value - ' + val)
                    handleRemove('maintenance_schedule_type')
                    setFormData((prev) => ({
                      ...prev,
                      maintenance_schedule_type: val,
                    }))
                  }}
                />
              </div>
              <div>
                <Label>Maintenance Frequency</Label>
                <Input
                  type="text"
                  value={formData.maintenance_frequency}
                  onChange={handleChange('maintenance_frequency')}
                  onKeyUp={() => handleRemove('maintenance_frequency')}
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
                <Label>Last Service Type</Label>
                <SingleSelectInput
                  options={lastServiceTypeData}
                  valueKey="value"
                  value={formData.last_service_type}
                  getOptionLabel={(item) => `${item.value}`}
                  onSingleSelectChange={(val) => {
                    console.log('value - ' + val)
                    handleRemove('last_service_type')
                    setFormData((prev) => ({
                      ...prev,
                      last_service_type: val,
                    }))
                  }}
                />
              </div>
              <div>
                <Label>Last Maintenance Due Date</Label>
                <Input
                  type="date"
                  value={formData.last_maintenance_due_date}
                  onChange={handleChange('last_maintenance_due_date')}
                  onKeyUp={() => handleRemove('last_maintenance_due_date')}
                />
              </div>
              <div>
                <Label>Safety Inspection Due Date</Label>
                <Input
                  type="date"
                  value={formData.safety_inspection_due_date}
                  onChange={handleChange('safety_inspection_due_date')}
                  onKeyUp={() => handleRemove('safety_inspection_due_date')}
                />
              </div>
              <div>
                <Label>Safety Certification Expire Date</Label>
                <Input
                  type="date"
                  value={formData.safety_certification_expire_date}
                  onChange={handleChange('safety_certification_expire_date')}
                  onKeyUp={() =>
                    handleRemove('safety_certification_expire_date')
                  }
                />
              </div>
              <div>
                <Label>Safety Features</Label>
                <MultiSelectInput
                  options={safetyFeaturesData}
                  valueKey="value"
                  getOptionLabel={(item) => item.value}
                  value={formData.safety_features}
                  onMultiSelectChange={(val: any) => {
                    handleRemove('safety_features')
                    setFormData((prev) => ({
                      ...prev,
                      safety_features: val,
                    }))
                  }}
                />
              </div>
              <div>
                <Label>Uptime Percentage(Monthly)</Label>
                <Input
                  type="text"
                  value={formData.uptime_percentage_monthly}
                  onChange={handleChange('uptime_percentage_monthly')}
                  onKeyUp={() => handleRemove('uptime_percentage_monthly')}
                />
              </div>
              <div>
                <Label>Maintenance Cost</Label>
                <Input
                  type="number"
                  value={formData.maintenance_cost}
                  onChange={handleChange('maintenance_cost')}
                  onKeyUp={() => handleRemove('maintenance_cost')}
                />
              </div>
              <div>
                <Label>Currency</Label>
                <SingleSelectInput
                  options={currencyData}
                  valueKey="value"
                  value={formData.currency}
                  getOptionLabel={(item) => `${item.id} - ${item.value}`}
                  onSingleSelectChange={(val) => {
                    handleRemove('currency')
                    setFormData((prev) => ({
                      ...prev,
                      currency: val,
                    }))
                  }}
                />
              </div>
              <div>
                <Label>Energy Consumption(per hours)</Label>
                <Input
                  type="text"
                  value={formData.energy_consumption_per_hour}
                  onChange={handleChange('energy_consumption_per_hour')}
                  onKeyUp={() => handleRemove('energy_consumption_per_hour')}
                />
              </div>
              <div>
                <Label>Depreciation Start Date</Label>
                <Input
                  type="date"
                  value={formData.depreciation_start_date}
                  onChange={handleChange('depreciation_start_date')}
                  onKeyUp={() => handleRemove('depreciation_start_date')}
                />
              </div>
              <div>
                <Label>Depreciation Methods</Label>
                <SingleSelectInput
                  options={depreciationMethodData}
                  valueKey="value"
                  value={formData.depreciation_method}
                  getOptionLabel={(item) => `${item.value}`}
                  onSingleSelectChange={(val) => {
                    handleRemove('depreciation_method')
                    setFormData((prev) => ({
                      ...prev,
                      depreciation_method: val,
                    }))
                  }}
                />
              </div>
              <div>
                <Label>Estimated Useful Life Years</Label>
                <Input
                  type="number"
                  value={formData.estimated_useful_life_year}
                  onChange={handleChange('estimated_useful_life_year')}
                  onKeyUp={() => handleRemove('estimated_useful_life_year')}
                />
              </div>
              <div>
                <Label>Supplier</Label>
                <SingleSelectInput
                  options={supplierLists}
                  valueKey="id"
                  value={formData.supplier_id}
                  getOptionLabel={(item) => `${item.party_code}`}
                  onSingleSelectChange={(val) => {
                    handleRemove('supplier_id')
                    getSupplierContact(val)
                    setFormData((prev) => ({
                      ...prev,
                      supplier_id: val,
                    }))
                  }}
                />
              </div>
              <div>
                <Label>Supplier Contact Lists</Label>
                <SingleSelectInput
                  options={supplierContacts}
                  valueKey="id"
                  value={formData.supplier_contact_id}
                  getOptionLabel={(item) =>
                    `${item.contact_name}(${item.email})`
                  }
                  onSingleSelectChange={(val) => {
                    console.log('value - ' + val)
                    handleRemove('supplier_contact_id')
                    setFormData((prev) => ({
                      ...prev,
                      supplier_contact_id: val,
                    }))
                  }}
                />
              </div>
              <div>
                <Label>Expected Replacement Date</Label>
                <Input
                  type="date"
                  value={formData.expected_replacement_date}
                  onChange={handleChange('expected_replacement_date')}
                  onKeyUp={() => handleRemove('expected_replacement_date')}
                />
              </div>
              <div>
                <Label>Disposal Date</Label>
                <Input
                  type="date"
                  value={formData.disposal_date}
                  onChange={handleChange('disposal_date')}
                  onKeyUp={() => handleRemove('disposal_date')}
                />
              </div>
              <div>
                <Label>Replacement MHE</Label>
                <SingleSelectInput
                  options={materialHandlingLists}
                  valueKey="id"
                  value={formData.replacement_mhe_id}
                  getOptionLabel={(item) => `${item.mhe_code}`}
                  onSingleSelectChange={(val) => {
                    console.log('value - ' + val)
                    handleRemove('replacement_mhe_id')
                    setFormData((prev) => ({
                      ...prev,
                      replacement_mhe_id: val,
                    }))
                  }}
                />
              </div>
              <div>
                <Label>Usage Status</Label>
                <SingleSelectInput
                  options={usageStatusData}
                  valueKey="id"
                  value={formData.usage_status}
                  getOptionLabel={(item) => `${item.value}`}
                  onSingleSelectChange={(val) => {
                    handleRemove('usage_status')
                    setFormData((prev) => ({
                      ...prev,
                      usage_status: val,
                    }))
                  }}
                  error={!!errors.usage_status}
                  hint={errors.usage_status}
                />
              </div>
              <div className="col-span-full">
                <Label>Remark</Label>
                <TextAreaInput
                  value={formData.remark}
                  onChange={(value) =>
                    handleChange('remark')({
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
            <h2 className="text-xl font-semibold text-gray-800">MHE Info</h2>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <Label>
                  MHE Code<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={materialHandling.mhe_code}
                  disabled={true}
                />
              </div>
              <div>
                <Label>
                  MHE Name<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={materialHandling.mhe_name}
                  disabled={true}
                />
              </div>

              <div>
                <Label>
                  MHE Type<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={materialHandling.mhe_type}
                  disabled={true}
                />
              </div>
              <div>
                <Label>
                  Manufacturer<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={materialHandling.manufacturer}
                  disabled={true}
                />
              </div>
              <div className="col-span-full">
                <Label>
                  Model<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={materialHandling.model}
                  disabled={true}
                />
              </div>
              <div>
                <Label>
                  Serial Number<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="number"
                  value={materialHandling.serial_number}
                  disabled={true}
                />
              </div>
              <div>
                <Label>
                  Purchase Date<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="date"
                  value={materialHandling.purchase_date}
                  disabled={true}
                />
              </div>
              <div>
                <Label>
                  Warranty Expire Date<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="date"
                  value={materialHandling.warranty_expire_date}
                  disabled={true}
                />
              </div>
              <div>
                <Label>
                  Capacity<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={materialHandling.capacity}
                  disabled={true}
                />
              </div>
              <div>
                <Label>
                  Capacity Unit<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={materialHandling.capacity_unit}
                  disabled={true}
                />
              </div>
              <div>
                <Label>Current Location Detail</Label>
                <Input
                  type="text"
                  value={materialHandling.current_location_detail}
                  disabled={true}
                />
              </div>
              <div>
                <Label>Home Location</Label>
                <Input
                  type="text"
                  value={materialHandling.home_location}
                  disabled={true}
                />
              </div>
              <div>
                <Label>Shift Availability</Label>
                <Input
                  type="text"
                  value={materialHandling.shift_availability}
                  disabled={true}
                />
              </div>
              <div>
                <Label>Operator Assigned</Label>
                <Input
                  type="text"
                  value={materialHandling.operator_assigned}
                  disabled={true}
                />
              </div>
              <div>
                <Label>Maintenance Schedule Type</Label>
                <Input
                  type="text"
                  value={materialHandling.maintenance_schedule_type}
                  disabled={true}
                />
              </div>
              <div>
                <Label>Maintenance Frequency</Label>
                <Input
                  type="text"
                  value={materialHandling.maintenance_frequency}
                  disabled={true}
                />
              </div>
              <div>
                <Label>Last Maintenance Date</Label>
                <Input
                  type="date"
                  value={materialHandling.last_maintenance_date}
                  disabled={true}
                />
              </div>
              <div>
                <Label>Last Service Type</Label>
                <Input
                  type="text"
                  value={materialHandling.last_service_type}
                  disabled={true}
                />
              </div>
              <div>
                <Label>Last Maintenance Due Date</Label>
                <Input
                  type="date"
                  value={materialHandling.last_maintenance_due_date}
                  disabled={true}
                />
              </div>
              <div>
                <Label>Safety Inspection Due Date</Label>
                <Input
                  type="date"
                  value={materialHandling.safety_inspection_due_date}
                  disabled={true}
                />
              </div>
              <div>
                <Label>Safety Certification Expire Date</Label>
                <Input
                  type="date"
                  value={materialHandling.safety_certification_expire_date}
                  disabled={true}
                />
              </div>
              <div>
                <Label>Safety Features</Label>
                <Input
                  type="text"
                  value={materialHandling?.safety_features}
                  disabled={true}
                />
              </div>
              <div>
                <Label>Uptime Percentage(Monthly)</Label>
                <Input
                  type="text"
                  value={materialHandling.uptime_percentage_monthly}
                  disabled={true}
                />
              </div>
              <div>
                <Label>Maintenance Cost</Label>
                <Input
                  type="number"
                  value={materialHandling.maintenance_cost}
                  disabled={true}
                />
              </div>
              <div>
                <Label>Currency</Label>
                <Input
                  type="number"
                  value={materialHandling.currency_unit}
                  disabled={true}
                />
              </div>
              <div>
                <Label>Energy Consumption(per hours)</Label>
                <Input
                  type="text"
                  value={materialHandling.energy_consumption_per_hour}
                  disabled={true}
                />
              </div>
              <div>
                <Label>Depreciation Start Date</Label>
                <Input
                  type="date"
                  value={materialHandling.depreciation_start_date}
                  disabled={true}
                />
              </div>
              <div>
                <Label>Depreciation Methods</Label>
                <Input
                  type="number"
                  value={materialHandling.depreciation_method}
                  disabled={true}
                />
              </div>
              <div>
                <Label>Estimated Useful Life Years</Label>
                <Input
                  type="number"
                  value={materialHandling.estimated_useful_life_year}
                  disabled={true}
                />
              </div>
              <div>
                <Label>Supplier</Label>
                <Input
                  type="text"
                  value={materialHandling.supplier_code}
                  disabled={true}
                />
              </div>
              <div>
                <Label>Supplier Contact</Label>
                <Input
                  type="text"
                  value={materialHandling.supplier_contact_name}
                  disabled={true}
                />
              </div>
              <div>
                <Label>Expected Replacement Date</Label>
                <Input
                  type="date"
                  value={materialHandling.expected_replacement_date}
                  disabled={true}
                />
              </div>
              <div>
                <Label>Disposal Date</Label>
                <Input
                  type="date"
                  value={materialHandling.disposal_date}
                  disabled={true}
                />
              </div>
              <div>
                <Label>Replacement MHE</Label>
                <Input
                  type="text"
                  value={
                    materialHandlingLists.find(
                      (x: any) => x.id === materialHandling.replacement_mhe_id
                    )?.mhe_code || ''
                  }
                  disabled={true}
                />
              </div>
              <div>
                <Label>Usage Status</Label>
                <Input
                  type="text"
                  value={materialHandling.usage_status}
                  disabled={true}
                />
              </div>
              <div className="col-span-full">
                <Label>Remark</Label>
                <TextAreaInput
                  value={materialHandling.remark}
                  disabled={true}
                />
              </div>
              <div className="col-span-full">
                <Label>Custom Attributes</Label>
                <TextAreaInput
                  value={materialHandling.custom_attributes}
                  disabled={true}
                />
              </div>
              <div>
                <Label>Status</Label>
                <Input
                  type="text"
                  value={materialHandling.status}
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
            <h2 className="text-xl font-semibold text-gray-800">Edit MHE</h2>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <Label>
                  MHE Code<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={updateFormData.mhe_code}
                  onChange={handleChange('mhe_code')}
                  onKeyUp={() => handleRemove('mhe_code')}
                  error={!!errors.mhe_code}
                  hint={errors.mhe_code}
                />
              </div>
              <div>
                <Label>
                  MHE Name<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={updateFormData.mhe_name}
                  onChange={handleChange('mhe_name')}
                  onKeyUp={() => handleRemove('mhe_name')}
                  error={!!errors.mhe_name}
                  hint={errors.mhe_name}
                />
              </div>

              <div>
                <Label>
                  MHE Type<span className="text-error-500">*</span>
                </Label>
                <SingleSelectInput
                  options={mheTypeData}
                  valueKey="value"
                  value={updateFormData.mhe_type}
                  getOptionLabel={(item) => `${item.value}`}
                  onSingleSelectChange={(val) => {
                    console.log('value - ' + val)
                    handleRemove('mhe_type')
                    setUpdateFormData((prev) => ({
                      ...prev,
                      mhe_type: val,
                    }))
                  }}
                  error={!!errors.mhe_type}
                  hint={errors.mhe_type}
                />
              </div>
              <div>
                <Label>
                  Manufacturer<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={updateFormData.manufacturer}
                  onChange={handleChange('manufacturer')}
                  onKeyUp={() => handleRemove('manufacturer')}
                  error={!!errors.manufacturer}
                  hint={errors.manufacturer}
                />
              </div>
              <div className="col-span-full">
                <Label>
                  Model<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={updateFormData.model}
                  onChange={handleChange('model')}
                  onKeyUp={() => handleRemove('model')}
                  error={!!errors.model}
                  hint={errors.model}
                />
              </div>
              <div>
                <Label>
                  Serial Number<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="number"
                  value={updateFormData.serial_number}
                  onChange={handleChange('serial_number')}
                  onKeyUp={() => handleRemove('serial_number')}
                  error={!!errors.model}
                  hint={errors.model}
                />
              </div>
              <div>
                <Label>
                  Purchase Date<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="date"
                  value={updateFormData.purchase_date}
                  onChange={handleChange('purchase_date')}
                  onKeyUp={() => handleRemove('purchase_date')}
                  error={!!errors.purchase_date}
                  hint={errors.purchase_date}
                />
              </div>
              <div>
                <Label>
                  Warranty Expire Date<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="date"
                  value={updateFormData.warranty_expire_date}
                  onChange={handleChange('warranty_expire_date')}
                  onKeyUp={() => handleRemove('warranty_expire_date')}
                  error={!!errors.warranty_expire_date}
                  hint={errors.warranty_expire_date}
                />
              </div>
              <div>
                <Label>
                  Capacity<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
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
                    console.log('value - ' + val)
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
                <Label>Current Location Detail</Label>
                <Input
                  type="text"
                  value={updateFormData.current_location_detail}
                  onChange={handleChange('current_location_detail')}
                  onKeyUp={() => handleRemove('current_location_detail')}
                />
              </div>
              <div>
                <Label>Home Location</Label>
                <Input
                  type="text"
                  value={updateFormData.home_location}
                  onChange={handleChange('home_location')}
                  onKeyUp={() => handleRemove('home_location')}
                />
              </div>
              <div>
                <Label>Shift Availability</Label>
                <SingleSelectInput
                  options={shiftAvaData}
                  valueKey="value"
                  value={updateFormData.shift_availability}
                  getOptionLabel={(item) => `${item.value}`}
                  onSingleSelectChange={(val) => {
                    console.log('value - ' + val)
                    handleRemove('shift_availability')
                    setUpdateFormData((prev) => ({
                      ...prev,
                      shift_availability: val,
                    }))
                  }}
                />
              </div>
              <div>
                <Label>Operator Assigned</Label>
                <Input
                  type="text"
                  value={updateFormData.operator_assigned}
                  onChange={handleChange('operator_assigned')}
                  onKeyUp={() => handleRemove('operator_assigned')}
                />
              </div>
              <div>
                <Label>Maintenance Schedule Type</Label>
                <SingleSelectInput
                  options={maintenanceScheduleTypeData}
                  valueKey="value"
                  value={updateFormData.maintenance_schedule_type}
                  getOptionLabel={(item) => `${item.value}`}
                  onSingleSelectChange={(val) => {
                    console.log('value - ' + val)
                    handleRemove('maintenance_schedule_type')
                    setUpdateFormData((prev) => ({
                      ...prev,
                      maintenance_schedule_type: val,
                    }))
                  }}
                />
              </div>
              <div>
                <Label>Maintenance Frequency</Label>
                <Input
                  type="text"
                  value={updateFormData.maintenance_frequency}
                  onChange={handleChange('maintenance_frequency')}
                  onKeyUp={() => handleRemove('maintenance_frequency')}
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
                <Label>Last Service Type</Label>
                <SingleSelectInput
                  options={lastServiceTypeData}
                  valueKey="value"
                  value={updateFormData.last_service_type}
                  getOptionLabel={(item) => `${item.value}`}
                  onSingleSelectChange={(val) => {
                    console.log('value - ' + val)
                    handleRemove('last_service_type')
                    setUpdateFormData((prev) => ({
                      ...prev,
                      last_service_type: val,
                    }))
                  }}
                />
              </div>
              <div>
                <Label>Last Maintenance Due Date</Label>
                <Input
                  type="date"
                  value={updateFormData.last_maintenance_due_date}
                  onChange={handleChange('last_maintenance_due_date')}
                  onKeyUp={() => handleRemove('last_maintenance_due_date')}
                />
              </div>
              <div>
                <Label>Safety Inspection Due Date</Label>
                <Input
                  type="date"
                  value={updateFormData.safety_inspection_due_date}
                  onChange={handleChange('safety_inspection_due_date')}
                  onKeyUp={() => handleRemove('safety_inspection_due_date')}
                />
              </div>
              <div>
                <Label>Safety Certification Expire Date</Label>
                <Input
                  type="date"
                  value={updateFormData.safety_certification_expire_date}
                  onChange={handleChange('safety_certification_expire_date')}
                  onKeyUp={() =>
                    handleRemove('safety_certification_expire_date')
                  }
                />
              </div>
              <div>
                <Label>Safety Features</Label>
                <MultiSelectInput
                  options={safetyFeaturesData}
                  valueKey="value"
                  getOptionLabel={(item) => item.value}
                  value={updateFormData.safety_features}
                  onMultiSelectChange={(val: any) => {
                    handleRemove('safety_features')
                    setUpdateFormData((prev) => ({
                      ...prev,
                      safety_features: val,
                    }))
                  }}
                />
              </div>
              <div>
                <Label>Uptime Percentage(Monthly)</Label>
                <Input
                  type="text"
                  value={updateFormData.uptime_percentage_monthly}
                  onChange={handleChange('uptime_percentage_monthly')}
                  onKeyUp={() => handleRemove('uptime_percentage_monthly')}
                />
              </div>
              <div>
                <Label>Maintenance Cost</Label>
                <Input
                  type="number"
                  value={updateFormData.maintenance_cost}
                  onChange={handleChange('maintenance_cost')}
                  onKeyUp={() => handleRemove('maintenance_cost')}
                />
              </div>
              <div>
                <Label>Currency</Label>
                <SingleSelectInput
                  options={currencyData}
                  valueKey="value"
                  value={updateFormData.currency}
                  getOptionLabel={(item) => `${item.id} - ${item.value}`}
                  onSingleSelectChange={(val) => {
                    handleRemove('currency')
                    setUpdateFormData((prev) => ({
                      ...prev,
                      currency: val,
                    }))
                  }}
                />
              </div>
              <div>
                <Label>Energy Consumption(per hours)</Label>
                <Input
                  type="text"
                  value={updateFormData.energy_consumption_per_hour}
                  onChange={handleChange('energy_consumption_per_hour')}
                  onKeyUp={() => handleRemove('energy_consumption_per_hour')}
                />
              </div>
              <div>
                <Label>Depreciation Start Date</Label>
                <Input
                  type="date"
                  value={updateFormData.depreciation_start_date}
                  onChange={handleChange('depreciation_start_date')}
                  onKeyUp={() => handleRemove('depreciation_start_date')}
                />
              </div>
              <div>
                <Label>Depreciation Methods</Label>
                <SingleSelectInput
                  options={depreciationMethodData}
                  valueKey="value"
                  value={updateFormData.depreciation_method}
                  getOptionLabel={(item) => `${item.value}`}
                  onSingleSelectChange={(val) => {
                    handleRemove('depreciation_method')
                    setUpdateFormData((prev) => ({
                      ...prev,
                      depreciation_method: val,
                    }))
                  }}
                />
              </div>
              <div>
                <Label>Estimated Useful Life Years</Label>
                <Input
                  type="number"
                  value={updateFormData.estimated_useful_life_year}
                  onChange={handleChange('estimated_useful_life_year')}
                  onKeyUp={() => handleRemove('estimated_useful_life_year')}
                />
              </div>
              <div>
                <Label>Supplier</Label>
                <SingleSelectInput
                  options={supplierLists}
                  valueKey="id"
                  value={updateFormData.supplier_id}
                  getOptionLabel={(item) => `${item.party_code}`}
                  onSingleSelectChange={(val) => {
                    handleRemove('supplier_id')
                    getSupplierContact(val)
                    setUpdateFormData((prev) => ({
                      ...prev,
                      supplier_id: val,
                    }))
                  }}
                />
              </div>
              <div>
                <Label>Supplier Contact Lists</Label>
                <SingleSelectInput
                  options={supplierContacts}
                  valueKey="id"
                  value={updateFormData.supplier_contact_id}
                  getOptionLabel={(item) =>
                    `${item.contact_name}(${item.email})`
                  }
                  onSingleSelectChange={(val) => {
                    console.log('value - ' + val)
                    handleRemove('supplier_contact_id')
                    setUpdateFormData((prev) => ({
                      ...prev,
                      supplier_contact_id: val,
                    }))
                  }}
                />
              </div>
              <div>
                <Label>Expected Replacement Date</Label>
                <Input
                  type="date"
                  value={updateFormData.expected_replacement_date}
                  onChange={handleChange('expected_replacement_date')}
                  onKeyUp={() => handleRemove('expected_replacement_date')}
                />
              </div>
              <div>
                <Label>Disposal Date</Label>
                <Input
                  type="date"
                  value={updateFormData.disposal_date}
                  onChange={handleChange('disposal_date')}
                  onKeyUp={() => handleRemove('disposal_date')}
                />
              </div>
              <div>
                <Label>Replacement MHE</Label>
                <SingleSelectInput
                  options={materialHandlingLists.filter(
                    (x: any) => x.id !== updateFormData.id
                  )}
                  valueKey="id"
                  value={updateFormData.replacement_mhe_id}
                  getOptionLabel={(item) => `${item.mhe_code}`}
                  onSingleSelectChange={(val) => {
                    console.log('value - ' + val)
                    handleRemove('material_handling_id')
                    setUpdateFormData((prev) => ({
                      ...prev,
                      replacement_mhe_id: val,
                    }))
                  }}
                />
              </div>
              <div>
                <Label>Usage Status</Label>
                <SingleSelectInput
                  options={usageStatusData}
                  valueKey="id"
                  value={updateFormData.usage_status}
                  getOptionLabel={(item) => `${item.value}`}
                  onSingleSelectChange={(val) => {
                    handleRemove('usage_status')
                    setUpdateFormData((prev) => ({
                      ...prev,
                      usage_status: val,
                    }))
                  }}
                  error={!!errors.usage_status}
                  hint={errors.usage_status}
                />
              </div>
              <div className="col-span-full">
                <Label>Remark</Label>
                <TextAreaInput
                  value={updateFormData.remark}
                  onChange={(value) =>
                    handleChange('remark')({
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
                <Label>Status</Label>
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
