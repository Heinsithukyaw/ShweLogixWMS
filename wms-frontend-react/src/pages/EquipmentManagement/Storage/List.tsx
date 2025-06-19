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
  storage_equipment_code: string
  storage_equipment_name: string
  storage_equipment_type: string
  manufacturer: string
  model: string
  serial_number: string
  purchase_date: string
  warranty_expire_date: string
  zone_id:string
  aisle: string
  bay: string
  level: string
  installation_date: string
  last_inspection_date: string
  next_inspection_due_date: string
  inspection_frequency: string
  max_weight_capacity: string
  max_volume_capacity: string
  length: string
  width: string
  height: string
  shelves_tiers_number: string
  adjustability: string
  safety_features: string
  load_type: string
  accessibility: string
  uptime_percentage_monthly: string
  maintenance_cost: string
  currency: string
  depreciation_start_date: string
  depreciation_method: string
  estimated_useful_life_year: string
  supplier_id: string
  supplier_contact_id: string
  expected_replacement_date: string
  disposal_date: string
  replacement_mhe_code: string
  remark:string,
  custom_attributes:string,
  status: any
  [key: string]: any
}

interface Errors {
  storage_equipment_code?: string
  storage_equipment_name?: string
  storage_equipment_type?: string
  manufacturer?: string
  model?: string
  serial_number?: string
  purchase_date?: string
  warranty_expire_date?: string
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
    selector: (row: RowData) => row.storage_equipment_code,
    sortable: true,
  },
  {
    name: 'Name',
    selector: (row: RowData) => row.storage_equipment_name,
    sortable: true,
  },
  {
    name: 'Type',
    selector: (row: RowData) => row.storage_equipment_type || '-',
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
    name: 'Aisle',
    selector: (row: RowData) => row.aisle || '-',
    sortable: true,
  },
  {
    name: 'Bay',
    selector: (row: RowData) => row.bay || '-',
    sortable: true,
  },
  {
    name: 'Level',
    selector: (row: RowData) => row.level || '-',
    sortable: true,
  },
  {
    name: 'Installation Date',
    selector: (row: RowData) => row.installation_date || '-',
    sortable: true,
  },
  {
    name: 'Last Inspection Date',
    selector: (row: RowData) => row.last_inspection_date || '-',
    sortable: true,
  },
  {
    name: 'Next Inspection Due Date',
    selector: (row: RowData) => row.next_inspection_due_date || '-',
    sortable: true,
  },
  {
    name: 'Inspection Frequency',
    selector: (row: RowData) => row.inspection_frequency || '-',
    sortable: true,
  },
  {
    name: 'max_weight_capacity',
    selector: (row: RowData) =>
      row.max_volume_capacity != null ? `${row.max_volume_capacity} kg` : '-',
    sortable: true,
  },
  {
    name: 'Length',
    selector: (row: RowData) => (row.length != null ? `${row.length} m` : '-'),
    sortable: true,
  },
  {
    name: 'Width',
    selector: (row: RowData) => (row.width != null ? `${row.width} kg` : '-'),
    sortable: true,
  },
  {
    name: 'Height',
    selector: (row: RowData) => (row.height != null ? `${row.height} kg` : '-'),
    sortable: true,
  },
  {
    name: 'Number of Shelves/Tiers',
    selector: (row: RowData) => row.shelves_tiers_number || '-',
    sortable: true,
  },
  {
    name: 'Adjustability',
    selector: (row: RowData) => row.adjustability || '-',
    sortable: true,
  },
  {
    name: 'Load Type',
    selector: (row: RowData) => row.load_type || '-',
    sortable: true,
  },
  {
    name: 'Accessibility',
    selector: (row: RowData) => row.accessibility || '-',
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
    name: 'Currency',
    selector: (row: RowData) => row.currency || '-',
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
    name: 'Estimated Useful Life(Years)',
    selector: (row: RowData) => row.estimated_useful_life_year || '-',
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
  const [storageEquipmentLists, setStorageEquipmentLists] = useState<any>([])
  const [supplierLists, setSupplierLists] = useState<any>([])
  const [supplierContactLists, setSupplierContactLists] = useState<any>([])
  const [supplierContacts,setSupplierContacts] = useState([])
  const [storageEquipment, setStorageEquipment] = useState<any>({})
  const [materialHandlingLists, setMaterialHandlingLists] = useState<any>({})

  const [isPageLoading, setIsPageLoading] = useState(false)
  const [isLoading, setIsLoading] = useState(false)
  const { showToast } = provideUtility()

  const storageEquipmentTypeData = [
    { id: 1, value: 'Pallet Racking' },
    { id: 2, value: 'Wire Shelving' },
    { id: 3, value: 'Mezzanine' },
    { id: 4, value: 'Carton Flow Rack' },
    { id: 5, value: 'Mobile Shelving' },
    { id: 6, value: 'Drive-In Racking' },
    { id: 7, value: 'Cantilever Racking' },
    { id: 8, value: 'Vertical Carousel' },
    { id: 9, value: 'Pallet Flow Rack' },
    { id: 10, value: 'Push Back Racking' },
    { id: 11, value: 'Boltless Shelving' },
    { id: 12, value: 'Wide Span Shelving' },
  ]

  const inspectionFrequencyData = [
    { id: 1, value: 'Annually' },
    { id: 2, value: 'Semi-Annually' },
  ]

  const adjustabilityData = [
    { id: 1, value: 'Adjustable Beams' },
    { id: 2, value: 'Adjustable Shelves' },
    { id: 3, value: 'Fixed' },
    { id: 4, value: 'Adjustable Lanes' },
    { id: 5, value: 'Fixed Lanes' },
    { id: 6, value: 'Adjustable Carriers' },
    { id: 7, value: 'Cart & Carrier System' },
  ]

  const loadTypeData = [
    { id: 1, value: 'Cases' },
    { id: 2, value: 'Pallets' },
    { id: 3, value: 'Long Items (Lumber,Pipes)' },
    { id: 4, value: 'Small Parts' },
    { id: 5, value: 'Bulky Items' },
  ]

  const accessibilityData = [
    { id: 1, value: 'Forklift Accessible' },
    { id: 2, value: 'Manual Access' },
    { id: 3, value: 'Forkilft & Manual Access' },
    { id: 4, value: 'Forklift Access(Drive-in)' },
    { id: 5, value: 'Forklift Access(Side Loading)' },
    { id: 5, value: 'Manual Access (Automated Retrieval)' },
    { id: 5, value: 'Forklift Loading,Manual Picking' },
    { id: 5, value: 'Forklift Access' },
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

  const statusData = [
    { id: 1, value: 'Operational' },
    { id: 2, value: 'Under Maintenance' },
  ]

  const currencyData = [
    { id: 1, value: 'USD' },
    { id: 2, value: 'EUR' },
    { id: 3, value: 'GBD' },
    { id: 4, value: 'JPY' },
    { id: 5, value: 'CNY' },
  ]

  const depreciationMethodData = [
    { id: 1, value: 'Straight-Line' },
    { id: 2, value: 'Declining Balance' },
  ]

  const [formData, setFormData] = useState({
    storage_equipment_code: '',
    storage_equipment_name: '',
    storage_equipment_type: '',
    manufacturer: '',
    model: '',
    serial_number: '',
    purchase_date: '',
    warranty_expire_date: '',
    aisle: '',
    bay: '',
    level: '',
    installation_date: '',
    last_inspection_date: '',
    next_inspection_due_date: '',
    inspection_frequency: '',
    max_weight_capacity: '',
    max_volume_capacity: '',
    length: '',
    width: '',
    height: '',
    shelves_tiers_number: '',
    adjustability: '',
    safety_features: [],
    load_type:'',
    accessibility: '',
    uptime_percentage_monthly: '',
    maintenance_cost:'',
    currency: '',
    depreciation_start_date: '',
    depreciation_method: '',
    estimated_useful_life_year: '',
    supplier_id: '',
    supplier_contact_id:'',
    expected_replacement_date:'',
    disposal_date: '',
    replacement_mhe_id: '',
    remark: '',
    custom_attributes: '',
    status: ''
  })

  const [updateFormData, setUpdateFormData] = useState({
    id: '',
    storage_equipment_code: '',
    storage_equipment_name: '',
    storage_equipment_type: '',
    manufacturer: '',
    model: '',
    serial_number: '',
    purchase_date: '',
    warranty_expire_date: '',
    aisle: '',
    bay: '',
    level: '',
    installation_date: '',
    last_inspection_date: '',
    next_inspection_due_date: '',
    inspection_frequency: '',
    max_weight_capacity: '',
    max_volume_capacity: '',
    length: '',
    width: '',
    height: '',
    shelves_tiers_number: '',
    adjustability: '',
    safety_features: [],
    load_type: '',
    accessibility: '',
    uptime_percentage_monthly: '',
    maintenance_cost: '',
    currency: '',
    depreciation_start_date: '',
    depreciation_method: '',
    estimated_useful_life_year: '',
    supplier_id: '',
    supplier_contact_id: '',
    expected_replacement_date: '',
    disposal_date: '',
    replacement_mhe_id: '',
    remark: '',
    custom_attributes: '',
    status: '',
  })

  useEffect(() => {
    setIsPageLoading(true)
    fetchStorageEquipmentLists()
    fetchMaterialHandlingLists()
    fetchBusinessPartyLists()
    fetchBusinessContactLists()
  }, [])

  const fetchStorageEquipmentLists = async () => {
    try {
      setIsPageLoading(true)
      const res = await http.fetchDataWithToken('storage-equipments')
      console.log(res)

      setStorageEquipmentLists(res.data?.data || [])
    } catch (err) {
      setIsPageLoading(false)
      console.error('Failed to fetch Storage Equipment lists:', err)
    } finally {
      setIsPageLoading(false)
    }
  }

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
    setStorageEquipment(
      storageEquipmentLists?.find((x: any) => x.id === row.id)
    )
    setIsViewOpen(true)
  }

  const handleCreate = () => {
    setErrors({
      storage_equipment_code: '',
      storage_equipment_name: '',
      storage_equipment_type: '',
      manufacturer: '',
      model: '',
      serial_number: '',
      purchase_date: '',
      warranty_expire_date: '',
      status:''
    })
    setIsCreateOpen(true)
  }

  const handleEdit = (row: any) => {
    setIsUpdateOpen(true)
    setErrors({
      storage_equipment_code: '',
      storage_equipment_name: '',
      storage_equipment_type: '',
      manufacturer: '',
      model: '',
      serial_number: '',
      purchase_date: '',
      warranty_expire_date: '',
      status:''
    })
    const storage_equipment_data = storageEquipmentLists.find(
      (x: any) => x.id === row.id
    )

    if (storage_equipment_data) {
        getSupplierContact(storage_equipment_data.supplier_id)
        setUpdateFormData({
          id: storage_equipment_data.id,
          storage_equipment_code: storage_equipment_data.storage_equipment_code,
          storage_equipment_name: storage_equipment_data.storage_equipment_name,
          storage_equipment_type: storage_equipment_data.storage_equipment_type,
          manufacturer: storage_equipment_data.manufacturer,
          model: storage_equipment_data.model,
          serial_number: storage_equipment_data.serial_number,
          purchase_date: storage_equipment_data.purchase_date,
          warranty_expire_date: storage_equipment_data.warranty_expire_date,
          aisle: storage_equipment_data.aisle,
          bay: storage_equipment_data.bay,
          level: storage_equipment_data.level,
          installation_date: storage_equipment_data.installation_date,
          last_inspection_date: storage_equipment_data.last_inspection_date,
          next_inspection_due_date:
            storage_equipment_data.next_inspection_due_date,
          inspection_frequency: storage_equipment_data.inspection_frequency,
          max_weight_capacity: storage_equipment_data.max_weight_capacity,
          max_volume_capacity: storage_equipment_data.max_volume_capacity,
          length: storage_equipment_data.length,
          width: storage_equipment_data.width,
          height: storage_equipment_data.height,
          shelves_tiers_number: storage_equipment_data.shelves_tiers_number,
          adjustability: storage_equipment_data.adjustability,
          safety_features: JSON.parse(storage_equipment_data.safety_features),
          load_type: storage_equipment_data.load_type,
          accessibility: storage_equipment_data.accessibility,
          uptime_percentage_monthly:
            storage_equipment_data.uptime_percentage_monthly,
          maintenance_cost: storage_equipment_data.maintenance_cost,
          currency: storage_equipment_data.currency,
          depreciation_start_date:
            storage_equipment_data.depreciation_start_date,
          depreciation_method: storage_equipment_data.depreciation_method,
          estimated_useful_life_year:
            storage_equipment_data.estimated_useful_life_year,
          supplier_id: storage_equipment_data.supplier_id,
          supplier_contact_id: storage_equipment_data.supplier_contact_id,
          expected_replacement_date:
            storage_equipment_data.expected_replacement_date,
          disposal_date: storage_equipment_data.disposal_date,
          replacement_mhe_id: storage_equipment_data.replacement_mhe_id,
          remark: storage_equipment_data.remark,
          custom_attributes: storage_equipment_data.custom_attributes,
          status: storage_equipment_data.status_value,
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
        `/storage-equipments/${row.id}`
      )
      console.log(response)
      if (response.status == true) {
        Swal.fire({
          title: 'Deleted!',
          text: 'Storage Equipment has been deleted.',
          icon: 'success',
        })
        fetchStorageEquipmentLists()
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
        '/storage-equipments',
        formData
      )
      if (response.status === true) {
        setIsCreateOpen(false)
        showToast('', 'Create Storage Equipment successful', 'top-right', 'success')
        setFormData({
          storage_equipment_code: '',
          storage_equipment_name: '',
          storage_equipment_type: '',
          manufacturer: '',
          model: '',
          serial_number: '',
          purchase_date: '',
          warranty_expire_date: '',
          aisle: '',
          bay: '',
          level: '',
          installation_date: '',
          last_inspection_date: '',
          next_inspection_due_date: '',
          inspection_frequency: '',
          max_weight_capacity: '',
          max_volume_capacity: '',
          length: '',
          width: '',
          height: '',
          shelves_tiers_number: '',
          adjustability: '',
          safety_features: [],
          load_type: '',
          accessibility: '',
          uptime_percentage_monthly: '',
          maintenance_cost: '',
          currency: '',
          depreciation_start_date: '',
          depreciation_method: '',
          estimated_useful_life_year: '',
          supplier_id: '',
          supplier_contact_id: '',
          expected_replacement_date: '',
          disposal_date: '',
          replacement_mhe_id: '',
          remark: '',
          custom_attributes: '',
          status: '',
        })
        fetchStorageEquipmentLists()
      } else {
        showToast('', 'Something went wrong!.', 'top-right', 'error')
      }
    } catch (err: any) {
      if (err?.status === 422) {
        showToast('', err?.data.message, 'top-right', 'error')
        const apiErrors: Errors = err?.data.errors
        setErrors(apiErrors)
      } else {
        showToast('', 'Create Storage Equipment failed!', 'top-right', 'error')
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
        `/storage-equipments/${updateFormData.id}`,
        updateFormData
      )
      if (response.status === true) {
        setIsUpdateOpen(false)
        showToast('', 'Update Storage Equipment successful', 'top-right', 'success')
        setUpdateFormData({
          id: '',
          storage_equipment_code: '',
          storage_equipment_name: '',
          storage_equipment_type: '',
          manufacturer: '',
          model: '',
          serial_number: '',
          purchase_date: '',
          warranty_expire_date: '',
          aisle: '',
          bay: '',
          level: '',
          installation_date: '',
          last_inspection_date: '',
          next_inspection_due_date: '',
          inspection_frequency: '',
          max_weight_capacity: '',
          max_volume_capacity: '',
          length: '',
          width: '',
          height: '',
          shelves_tiers_number: '',
          adjustability: '',
          safety_features: [],
          load_type: '',
          accessibility: '',
          uptime_percentage_monthly: '',
          maintenance_cost: '',
          currency: '',
          depreciation_start_date: '',
          depreciation_method: '',
          estimated_useful_life_year: '',
          supplier_id: '',
          supplier_contact_id: '',
          expected_replacement_date: '',
          disposal_date: '',
          replacement_mhe_id: '',
          remark: '',
          custom_attributes: '',
          status: '',
        })
        fetchStorageEquipmentLists()
      } else {
        showToast('', 'Something went wrong!.', 'top-right', 'error')
      }
    } catch (err: any) {
      if (err?.status === 422) {
        showToast('', err?.message, 'top-right', 'error')
        const apiErrors: Errors = err?.errors
        setErrors(apiErrors)
      } else {
        showToast('', 'Update Storage Equipment failed!', 'top-right', 'error')
      }
      console.error(err)
    } finally {
      setIsLoading(false)
    }
  }

  const [filterText, setFilterText] = React.useState('')
  const filteredData = useMemo(() => {
    if (!filterText) return storageEquipmentLists

    return storageEquipmentLists.filter((item: any) =>
      Object.values(item).some(
        (val) =>
          val && val.toString().toLowerCase().includes(filterText.toLowerCase())
      )
    )
  }, [filterText, storageEquipmentLists])

  return (
    <div className="space-y-10">
      <div className="flex justify-between items-center">
        <h1 className="text-xl font-semibold">Storage Equipment Lists</h1>
        <Button variant="primary" size="sm" onClick={handleCreate}>
          Add Storage Equipment
        </Button>
      </div>

      <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 md:gap-6 xl:grid-cols-4">
        <div className="rounded-md shadow-lg border border-gray-200 bg-white p-2">
          <div className="rounded-2xl border border-gray-200 bg-white p-5 ">
            <p className="text-gray-500 text-theme-sm dark:text-gray-400">
              Total Equipment
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
              Total Capacity
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
              Average Utilization
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
              Maintenance Due
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
                placeholder="Search Storage Equipments..."
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
                  Storage Equipment Code
                  <span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={formData.storage_equipment_code}
                  onChange={handleChange('storage_equipment_code')}
                  onKeyUp={() => handleRemove('storage_equipment_code')}
                  error={!!errors.storage_equipment_code}
                  hint={errors.storage_equipment_code}
                />
              </div>
              <div>
                <Label>
                  Storage Equipment Name
                  <span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={formData.storage_equipment_name}
                  onChange={handleChange('storage_equipment_name')}
                  onKeyUp={() => handleRemove('storage_equipment_name')}
                  error={!!errors.storage_equipment_name}
                  hint={errors.storage_equipment_name}
                />
              </div>

              <div>
                <Label>
                  Storage Equipment Type
                  <span className="text-error-500">*</span>
                </Label>
                <SingleSelectInput
                  options={storageEquipmentTypeData}
                  valueKey="value"
                  value={formData.storage_equipment_type}
                  getOptionLabel={(item) => `${item.value}`}
                  onSingleSelectChange={(val) => {
                    console.log('value - ' + val)
                    handleRemove('storage_equipment_type')
                    setFormData((prev) => ({
                      ...prev,
                      storage_equipment_type: val,
                    }))
                  }}
                  error={!!errors.storage_equipment_type}
                  hint={errors.storage_equipment_type}
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
                  type="text"
                  value={formData.serial_number}
                  onChange={handleChange('serial_number')}
                  onKeyUp={() => handleRemove('serial_number')}
                  error={!!errors.serial_number}
                  hint={errors.serial_number}
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
                <Label>Aisle</Label>
                <Input
                  type="text"
                  value={formData.aisle}
                  onChange={handleChange('aisle')}
                  onKeyUp={() => handleRemove('aisle')}
                />
              </div>
              <div>
                <Label>Bay</Label>
                <Input
                  type="text"
                  value={formData.bay}
                  onChange={handleChange('bay')}
                  onKeyUp={() => handleRemove('bay')}
                />
              </div>
              <div>
                <Label>Level</Label>
                <Input
                  type="text"
                  value={formData.level}
                  onChange={handleChange('level')}
                  onKeyUp={() => handleRemove('level')}
                />
              </div>
              <div>
                <Label>Installation Date</Label>
                <Input
                  type="date"
                  value={formData.installation_date}
                  onChange={handleChange('installation_date')}
                  onKeyUp={() => handleRemove('installation_date')}
                />
              </div>
              <div>
                <Label>Last Inspection Date</Label>
                <Input
                  type="date"
                  value={formData.last_inspection_date}
                  onChange={handleChange('last_inspection_date')}
                  onKeyUp={() => handleRemove('last_inspection_date')}
                />
              </div>
              <div>
                <Label>Next Inspection Due Date</Label>
                <Input
                  type="date"
                  value={formData.next_inspection_due_date}
                  onChange={handleChange('next_inspection_due_date')}
                  onKeyUp={() => handleRemove('next_inspection_due_date')}
                />
              </div>

              <div>
                <Label>
                  Inspection Frequency<span className="text-error-500">*</span>
                </Label>
                <SingleSelectInput
                  options={inspectionFrequencyData}
                  valueKey="value"
                  value={formData.inspection_frequency}
                  getOptionLabel={(item) => `${item.value}`}
                  onSingleSelectChange={(val) => {
                    console.log('value - ' + val)
                    handleRemove('inspection_frequency')
                    setFormData((prev) => ({
                      ...prev,
                      inspection_frequency: val,
                    }))
                  }}
                />
              </div>

              <div>
                <Label>Max Weight Capacity</Label>
                <Input
                  type="number"
                  value={formData.max_weight_capacity}
                  onChange={handleChange('max_weight_capacity')}
                  onKeyUp={() => handleRemove('max_weight_capacity')}
                />
              </div>

              <div>
                <Label>Max Volume Capacity</Label>
                <Input
                  type="number"
                  value={formData.max_volume_capacity}
                  onChange={handleChange('max_volume_capacity')}
                  onKeyUp={() => handleRemove('max_volume_capacity')}
                />
              </div>

              <div>
                <Label>Length</Label>
                <Input
                  type="number"
                  value={formData.length}
                  onChange={handleChange('length')}
                  onKeyUp={() => handleRemove('length')}
                />
              </div>

              <div>
                <Label>Width</Label>
                <Input
                  type="number"
                  value={formData.width}
                  onChange={handleChange('width')}
                  onKeyUp={() => handleRemove('width')}
                />
              </div>

              <div>
                <Label>Height</Label>
                <Input
                  type="number"
                  value={formData.height}
                  onChange={handleChange('height')}
                  onKeyUp={() => handleRemove('height')}
                />
              </div>

              <div>
                <Label>Shelves/Tiers Number</Label>
                <Input
                  type="number"
                  value={formData.shelves_tiers_number}
                  onChange={handleChange('shelves_tiers_number')}
                  onKeyUp={() => handleRemove('shelves_tiers_number')}
                />
              </div>

              <div>
                <Label>Adjustability</Label>
                <SingleSelectInput
                  options={adjustabilityData}
                  valueKey="value"
                  value={formData.adjustability}
                  getOptionLabel={(item) => `${item.value}`}
                  onSingleSelectChange={(val) => {
                    console.log('value - ' + val)
                    handleRemove('adjustability')
                    setFormData((prev) => ({
                      ...prev,
                      adjustability: val,
                    }))
                  }}
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
                <Label>Load Type</Label>
                <SingleSelectInput
                  options={loadTypeData}
                  valueKey="value"
                  value={formData.load_type}
                  getOptionLabel={(item) => `${item.value}`}
                  onSingleSelectChange={(val) => {
                    console.log('value - ' + val)
                    handleRemove('load_type')
                    setFormData((prev) => ({
                      ...prev,
                      load_type: val,
                    }))
                  }}
                />
              </div>

              <div>
                <Label>Accessibility</Label>
                <SingleSelectInput
                  options={accessibilityData}
                  valueKey="value"
                  value={formData.accessibility}
                  getOptionLabel={(item) => `${item.value}`}
                  onSingleSelectChange={(val) => {
                    console.log('value - ' + val)
                    handleRemove('accessibility')
                    setFormData((prev) => ({
                      ...prev,
                      accessibility: val,
                    }))
                  }}
                />
              </div>

              <div>
                <Label>Uptime Percentage (Monthly)</Label>
                <Input
                  type="number"
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
            <h2 className="text-xl font-semibold text-gray-800">
              Storage Equipment
            </h2>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <Label>
                  Storage Equipment Code
                  <span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={storageEquipment.storage_equipment_code}
                  disabled={true}
                />
              </div>
              <div>
                <Label>
                  Storage Equipment Name
                  <span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={storageEquipment.storage_equipment_name}
                  disabled={true}
                />
              </div>

              <div>
                <Label>
                  Storage Equipment Type
                  <span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={storageEquipment.storage_equipment_type}
                  disabled={true}
                />
              </div>
              <div>
                <Label>
                  Manufacturer<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={storageEquipment.manufacturer}
                  disabled={true}
                />
              </div>
              <div className="col-span-full">
                <Label>
                  Model<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={storageEquipment.model}
                  disabled={true}
                />
              </div>
              <div>
                <Label>
                  Serial Number<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={storageEquipment.serial_number}
                  disabled={true}
                />
              </div>
              <div>
                <Label>
                  Purchase Date<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="date"
                  value={storageEquipment.purchase_date}
                  disabled={true}
                />
              </div>
              <div>
                <Label>
                  Warranty Expire Date<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="date"
                  value={storageEquipment.warranty_expire_date}
                  disabled={true}
                />
              </div>
              <div>
                <Label>Aisle</Label>
                <Input
                  type="text"
                  value={storageEquipment.aisle}
                  disabled={true}
                />
              </div>
              <div>
                <Label>Bay</Label>
                <Input
                  type="text"
                  value={storageEquipment.bay}
                  disabled={true}
                />
              </div>
              <div>
                <Label>Level</Label>
                <Input
                  type="text"
                  value={storageEquipment.level}
                  disabled={true}
                />
              </div>
              <div>
                <Label>Installation Date</Label>
                <Input
                  type="date"
                  value={storageEquipment.installation_date}
                  disabled={true}
                />
              </div>
              <div>
                <Label>Last Inspection Date</Label>
                <Input
                  type="date"
                  value={storageEquipment.last_inspection_date}
                  disabled={true}
                />
              </div>
              <div>
                <Label>Next Inspection Due Date</Label>
                <Input
                  type="date"
                  value={storageEquipment.next_inspection_due_date}
                  disabled={true}
                />
              </div>

              <div>
                <Label>
                  Inspection Frequency<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={storageEquipment.inspection_frequency}
                  disabled={true}
                />
              </div>

              <div>
                <Label>Max Weight Capacity</Label>
                <Input
                  type="number"
                  value={storageEquipment.max_weight_capacity}
                  disabled={true}
                />
              </div>

              <div>
                <Label>Max Volume Capacity</Label>
                <Input
                  type="number"
                  value={storageEquipment.max_volume_capacity}
                  disabled={true}
                />
              </div>

              <div>
                <Label>Length</Label>
                <Input
                  type="number"
                  value={storageEquipment.length}
                  disabled={true}
                />
              </div>

              <div>
                <Label>Width</Label>
                <Input
                  type="number"
                  value={storageEquipment.width}
                  disabled={true}
                />
              </div>

              <div>
                <Label>Height</Label>
                <Input
                  type="number"
                  value={storageEquipment.height}
                  disabled={true}
                />
              </div>

              <div>
                <Label>Shelves/Tiers Number</Label>
                <Input
                  type="number"
                  value={storageEquipment.shelves_tiers_number}
                  disabled={true}
                />
              </div>

              <div>
                <Label>Adjustability</Label>
                <Input
                  type="text"
                  value={storageEquipment.adjustability}
                  disabled={true}
                />
              </div>

              <div>
                <Label>Safety Features</Label>
                <Input
                  type="text"
                  value={storageEquipment.safety_features}
                  disabled={true}
                />
              </div>

              <div>
                <Label>Load Type</Label>
                <Input
                  type="text"
                  value={storageEquipment.load_type}
                  disabled={true}
                />
              </div>

              <div>
                <Label>Accessibility</Label>
                <Input
                  type="text"
                  value={storageEquipment.accessibility}
                  disabled={true}
                />
              </div>

              <div>
                <Label>Uptime Percentage (Monthly)</Label>
                <Input
                  type="text"
                  value={storageEquipment.uptime_percentage_monthly}
                  disabled={true}
                />
              </div>

              <div>
                <Label>Maintenance Cost</Label>
                <Input
                  type="number"
                  value={storageEquipment.maintenance_cost}
                  disabled={true}
                />
              </div>
              <div>
                <Label>Currency</Label>
                <Input
                  type="text"
                  value={storageEquipment.currency}
                  disabled={true}
                />
              </div>
              <div>
                <Label>Depreciation Start Date</Label>
                <Input
                  type="date"
                  value={storageEquipment.depreciation_start_date}
                  disabled={true}
                />
              </div>
              <div>
                <Label>Depreciation Methods</Label>
                <Input
                  type="text"
                  value={storageEquipment.depreciation_method}
                  disabled={true}
                />
              </div>
              <div>
                <Label>Estimated Useful Life Years</Label>
                <Input
                  type="number"
                  value={storageEquipment.estimated_useful_life_year}
                  disabled={true}
                />
              </div>
              <div>
                <Label>Supplier</Label>
                <Input
                  type="number"
                  value={storageEquipment.supplier_code}
                  disabled={true}
                />
              </div>
              <div>
                <Label>Supplier Contact Lists</Label>
                <Input
                  type="number"
                  value={storageEquipment.supplier_contact_name}
                  disabled={true}
                />
              </div>
              <div>
                <Label>Expected Replacement Date</Label>
                <Input
                  type="date"
                  value={storageEquipment.expected_replacement_date}
                  disabled={true}
                />
              </div>
              <div>
                <Label>Disposal Date</Label>
                <Input
                  type="date"
                  value={storageEquipment.disposal_date}
                  disabled={true}
                />
              </div>
              <div>
                <Label>Replacement MHE</Label>
                <Input
                  type="date"
                  value={storageEquipment.replacement_mhe_code}
                  disabled={true}
                />
              </div>
              <div className="col-span-full">
                <Label>Remark</Label>
                <TextAreaInput
                  value={storageEquipment.remark}
                  disabled={true}
                />
              </div>
              <div className="col-span-full">
                <Label>Custom Attributes</Label>
                <TextAreaInput
                  value={storageEquipment.custom_attributes}
                  disabled={true}
                />
              </div>
              <div>
                <Label>Status</Label>
                <Input
                  type="text"
                  value={storageEquipment.status}
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
              Edit Storage Equipment
            </h2>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <Label>
                  Storage Equipment Code
                  <span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={updateFormData.storage_equipment_code}
                  onChange={handleChange('storage_equipment_code')}
                  onKeyUp={() => handleRemove('storage_equipment_code')}
                  error={!!errors.storage_equipment_code}
                  hint={errors.storage_equipment_code}
                />
              </div>
              <div>
                <Label>
                  Storage Equipment Name
                  <span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={updateFormData.storage_equipment_name}
                  onChange={handleChange('storage_equipment_name')}
                  onKeyUp={() => handleRemove('storage_equipment_name')}
                  error={!!errors.storage_equipment_name}
                  hint={errors.storage_equipment_name}
                />
              </div>

              <div>
                <Label>
                  Storage Equipment Type
                  <span className="text-error-500">*</span>
                </Label>
                <SingleSelectInput
                  options={storageEquipmentTypeData}
                  valueKey="value"
                  value={updateFormData.storage_equipment_type}
                  getOptionLabel={(item) => `${item.value}`}
                  onSingleSelectChange={(val) => {
                    console.log('value - ' + val)
                    handleRemove('storage_equipment_type')
                    setUpdateFormData((prev) => ({
                      ...prev,
                      storage_equipment_type: val,
                    }))
                  }}
                  error={!!errors.storage_equipment_type}
                  hint={errors.storage_equipment_type}
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
                  type="text"
                  value={updateFormData.serial_number}
                  onChange={handleChange('serial_number')}
                  onKeyUp={() => handleRemove('serial_number')}
                  error={!!errors.serial_number}
                  hint={errors.serial_number}
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
                <Label>Aisle</Label>
                <Input
                  type="text"
                  value={updateFormData.aisle}
                  onChange={handleChange('aisle')}
                  onKeyUp={() => handleRemove('aisle')}
                />
              </div>
              <div>
                <Label>Bay</Label>
                <Input
                  type="text"
                  value={updateFormData.bay}
                  onChange={handleChange('bay')}
                  onKeyUp={() => handleRemove('bay')}
                />
              </div>
              <div>
                <Label>Level</Label>
                <Input
                  type="text"
                  value={updateFormData.level}
                  onChange={handleChange('level')}
                  onKeyUp={() => handleRemove('level')}
                />
              </div>
              <div>
                <Label>Installation Date</Label>
                <Input
                  type="date"
                  value={updateFormData.installation_date}
                  onChange={handleChange('installation_date')}
                  onKeyUp={() => handleRemove('installation_date')}
                />
              </div>
              <div>
                <Label>Last Inspection Date</Label>
                <Input
                  type="date"
                  value={updateFormData.last_inspection_date}
                  onChange={handleChange('last_inspection_date')}
                  onKeyUp={() => handleRemove('last_inspection_date')}
                />
              </div>
              <div>
                <Label>Next Inspection Due Date</Label>
                <Input
                  type="date"
                  value={updateFormData.next_inspection_due_date}
                  onChange={handleChange('next_inspection_due_date')}
                  onKeyUp={() => handleRemove('next_inspection_due_date')}
                />
              </div>

              <div>
                <Label>
                  Inspection Frequency<span className="text-error-500">*</span>
                </Label>
                <SingleSelectInput
                  options={inspectionFrequencyData}
                  valueKey="value"
                  value={updateFormData.inspection_frequency}
                  getOptionLabel={(item) => `${item.value}`}
                  onSingleSelectChange={(val) => {
                    console.log('value - ' + val)
                    handleRemove('inspection_frequency')
                    setUpdateFormData((prev: any) => ({
                      ...prev,
                      inspection_frequency: val,
                    }))
                  }}
                />
              </div>

              <div>
                <Label>Max Weight Capacity</Label>
                <Input
                  type="number"
                  value={updateFormData.max_weight_capacity}
                  onChange={handleChange('max_weight_capacity')}
                  onKeyUp={() => handleRemove('max_weight_capacity')}
                />
              </div>

              <div>
                <Label>Max Volume Capacity</Label>
                <Input
                  type="number"
                  value={updateFormData.max_volume_capacity}
                  onChange={handleChange('max_volume_capacity')}
                  onKeyUp={() => handleRemove('max_volume_capacity')}
                />
              </div>

              <div>
                <Label>Length</Label>
                <Input
                  type="number"
                  value={updateFormData.length}
                  onChange={handleChange('length')}
                  onKeyUp={() => handleRemove('length')}
                />
              </div>

              <div>
                <Label>Width</Label>
                <Input
                  type="number"
                  value={updateFormData.width}
                  onChange={handleChange('width')}
                  onKeyUp={() => handleRemove('width')}
                />
              </div>

              <div>
                <Label>Height</Label>
                <Input
                  type="number"
                  value={updateFormData.height}
                  onChange={handleChange('height')}
                  onKeyUp={() => handleRemove('height')}
                />
              </div>

              <div>
                <Label>Shelves/Tiers Number</Label>
                <Input
                  type="number"
                  value={updateFormData.shelves_tiers_number}
                  onChange={handleChange('shelves_tiers_number')}
                  onKeyUp={() => handleRemove('shelves_tiers_number')}
                />
              </div>

              <div>
                <Label>Adjustability</Label>
                <SingleSelectInput
                  options={adjustabilityData}
                  valueKey="value"
                  value={updateFormData.adjustability}
                  getOptionLabel={(item) => `${item.value}`}
                  onSingleSelectChange={(val) => {
                    console.log('value - ' + val)
                    handleRemove('adjustability')
                    setUpdateFormData((prev) => ({
                      ...prev,
                      adjustability: val,
                    }))
                  }}
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
                <Label>Load Type</Label>
                <SingleSelectInput
                  options={loadTypeData}
                  valueKey="value"
                  value={updateFormData.load_type}
                  getOptionLabel={(item) => `${item.value}`}
                  onSingleSelectChange={(val) => {
                    console.log('value - ' + val)
                    handleRemove('load_type')
                    setUpdateFormData((prev) => ({
                      ...prev,
                      load_type: val,
                    }))
                  }}
                />
              </div>

              <div>
                <Label>Accessibility</Label>
                <SingleSelectInput
                  options={accessibilityData}
                  valueKey="value"
                  value={updateFormData.accessibility}
                  getOptionLabel={(item) => `${item.value}`}
                  onSingleSelectChange={(val) => {
                    console.log('value - ' + val)
                    handleRemove('accessibility')
                    setUpdateFormData((prev) => ({
                      ...prev,
                      accessibility: val,
                    }))
                  }}
                />
              </div>

              <div>
                <Label>Uptime Percentage (Monthly)</Label>
                <Input
                  type="number"
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
                  options={materialHandlingLists}
                  valueKey="id"
                  value={updateFormData.replacement_mhe_id}
                  getOptionLabel={(item) => `${item.mhe_code}`}
                  onSingleSelectChange={(val) => {
                    console.log('value - ' + val)
                    handleRemove('replacement_mhe_id')
                    setUpdateFormData((prev) => ({
                      ...prev,
                      replacement_mhe_id: val,
                    }))
                  }}
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
