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
import { TableColumn } from 'react-data-table-component'
import { showConfirm } from '../../../utils/alert'
import Swal from 'sweetalert2'

interface RowData {
  pallet_code: string
  pallet_name: string
  pallet_type: string
  material: string
  manufacturer: string
  length: string
  width: string
  height: string
  weight_capacity: string
  empty_weight: string
  condition: string
  current_location: string
  purchase_date: string
  last_inspection_date: string
  next_inspection_date: string
  pooled_pallet: any
  pool_provider: string
  cost_per_unit: string
  expected_lifespan_year: string
  rfid_tag: string
  barcode: string
  currently_assigned: string
  assigned_shipment: string
  notes: string
  status: any
  [key: string]: any
}

interface Errors {
  pallet_code?: string
  pallet_name?: string
  pallet_type?: string
  material?: string
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
    selector: (row: RowData) => row.pallet_code,
    sortable: true,
  },
  {
    name: 'Name',
    selector: (row: RowData) => row.pallet_name,
    sortable: true,
  },
  {
    name: 'Type',
    selector: (row: RowData) => row.pallet_type || '-',
    sortable: true,
  },
  {
    name: 'Material',
    selector: (row: RowData) => row.material || '-',
    sortable: true,
  },
  {
    name: 'Manufacturer',
    selector: (row: RowData) => row.manufacturer || '-',
    sortable: true,
  },
  {
    name: 'Length (m)',
    selector: (row: RowData) => row.length || '-',
    sortable: true,
  },
  {
    name: 'Width (m)',
    selector: (row: RowData) => row.width || '-',
    sortable: true,
  },
  {
    name: 'Height (m)',
    selector: (row: RowData) => row.height || '-',
    sortable: true,
  },
  {
    name: 'Weight Capacity',
    selector: (row: RowData) =>
      row.weight_capacity != null ? `${row.weight_capacity} kg` : '-',
    sortable: true,
  },
  {
    name: 'Empty Weight',
    selector: (row: RowData) =>
      row.empty_weight != null ? `${row.empty_weight} kg` : '-',
    sortable: true,
  },
  {
    name: 'Condition',
    selector: (row: RowData) => row.condition || '-',
    sortable: true,
  },
  {
    name: 'Current Location',
    selector: (row: RowData) => row.current_location || '-',
    sortable: true,
  },
  {
    name: 'Purchase Date',
    selector: (row: RowData) => row.purchase_date || '-',
    sortable: true,
  },
  {
    name: 'Last Inspection Date',
    selector: (row: RowData) => row.last_inspection_date || '-',
    sortable: true,
  },
  {
    name: 'Next Inspection Date',
    selector: (row: RowData) => row.next_inspection_date || '-',
    sortable: true,
  },
  {
    name: 'Pooled Pallet',
    selector: (row: RowData) => row.pooled_pallet || '-',
    sortable: true,
  },
  {
    name: 'Pool Provider',
    selector: (row: RowData) => row.pool_provider || '-',
    sortable: true,
  },
  {
    name: 'Cost Per Unit',
    selector: (row: RowData) => row.cost_per_unit || '-',
    sortable: true,
  },
  {
    name: 'Expected Lifespan (Years)',
    selector: (row: RowData) => row.expected_lifespan_year || '-',
    sortable: true,
  },
  {
    name: 'RFID Tag',
    selector: (row: RowData) => row.rfid_tag || '-',
    sortable: true,
  },
  {
    name: 'Barcode',
    selector: (row: RowData) => row.barcode || '-',
    sortable: true,
  },
  {
    name: 'Currently Assigned',
    selector: (row: RowData) => row.currently_assigned || '-',
    sortable: true,
  },
  {
    name: 'Assigned Shipment',
    selector: (row: RowData) => row.assigned_shipment || '-',
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
  const [isDisabled, setIsDisabled] = useState(true)
  const [errors, setErrors] = useState<Errors>({})
  const [palletEquipmentLists, setPalletEquipmentLists] = useState<any>([])
  
  const [palletEquipment, setPalletEquipment] = useState<any>({})
  const [isPageLoading, setIsPageLoading] = useState(false)
  const [isLoading, setIsLoading] = useState(false)
  const { showToast } = provideUtility()

  const palletTypeData = [
    { id: 1, value: 'Standard 4-way' },
    { id: 2, value: 'Heavy Duty 4-way' },
    { id: 3, value: 'EUR / EPAL' },
    { id: 4, value: 'Block' },
    { id: 5, value: 'Stringer' },
    { id: 6, value: 'Nestable' },
    { id: 7, value: 'Stackable' },
    { id: 8, value: 'Rackable' },
    { id: 9, value: 'Hygiene' },
    { id: 10, value: 'Heavy Duty 4-way' },
    { id: 11, value: 'Drum' },
    { id: 12, value: 'Display' },
    { id: 13, value: 'Custom' },
  ]

  const materialTypeData = [
    { id: 1, value: 'Wood' },
    { id: 2, value: 'Plastic' },
    { id: 3, value: 'Metal' },
    { id: 4, value: 'Aluminum' },
    { id: 5, value: 'Composite' },
    { id: 6, value: 'Presswood' },
    { id: 7, value: 'Cardboard' },

  ]

  const pooledPalletData = [
    { id: 0, value: 'No' },
    { id: 1, value: 'Yes' },
  ]

  const conditionData = [
    { id: 1, value: 'Good' },
    { id: 2, value: 'Excellent' },
    { id: 1, value: 'Fair' },
    { id: 2, value: 'Poor' },
    { id: 1, value: 'Damaged' },
  ]

  const statusData = [
    { id: 1, value: 'Available' },
    { id: 2, value: 'In Use' },
    { id: 1, value: 'Reserved' },
    { id: 2, value: 'Under Repair' },
    { id: 1, value: 'Quarantined' },
    { id: 2, value: 'Disposed' },
  ]

  const [formData, setFormData] = useState({
    pallet_code: '',
    pallet_name: '',
    pallet_type: '',
    material: '',
    manufacturer: '',
    length: '',
    width: '',
    height: '',
    weight_capacity: '',
    empty_weight: '',
    condition: '',
    current_location: '',
    purchase_date: '',
    last_inspection_date: '',
    next_inspection_date: '',
    pooled_pallet: '',
    pool_provider: '',
    cost_per_unit: '',
    expected_lifespan_year: '',
    rfid_tag: '',
    barcode: '',
    currently_assigned: '',
    assigned_shipment: '',
    status: '',
    notes: '',
  })

  const [updateFormData, setUpdateFormData] = useState({
    id: '',
    pallet_code: '',
    pallet_name: '',
    pallet_type: '',
    material: '',
    manufacturer: '',
    length: '',
    width: '',
    height: '',
    weight_capacity: '',
    empty_weight: '',
    condition: '',
    current_location: '',
    purchase_date: '',
    last_inspection_date: '',
    next_inspection_date: '',
    pooled_pallet: '',
    pool_provider: '',
    cost_per_unit: '',
    expected_lifespan_year: '',
    rfid_tag: '',
    barcode: '',
    currently_assigned: '',
    assigned_shipment: '',
    status: '',
    notes: '',
  })

  useEffect(() => {
    setIsPageLoading(true)
    fetchPalletEquipmentLists()
  }, [])

  const fetchPalletEquipmentLists = async () => {
    try {
      setIsPageLoading(true)
      const res = await http.fetchDataWithToken('pallet-equipments')
      console.log(res)

      setPalletEquipmentLists(res.data?.data || [])
    } catch (err) {
      setIsPageLoading(false)
      console.error('Failed to fetch Pallet Equipment lists:', err)
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
    setPalletEquipment(
      palletEquipmentLists?.find((x: any) => x.id === row.id)
    )
    setIsViewOpen(true)
  }

  const handleCreate = () => {
    setErrors({
      pallet_code: '',
      pallet_name: '',
      pallet_type: '',
      material: '',
      status:''
    })
    setIsCreateOpen(true)
  }

  const handleEdit = (row: any) => {
    setIsUpdateOpen(true)
    setErrors({
      pallet_code: '',
      pallet_name: '',
      pallet_type: '',
      material: '',
      status:''
    })
    const pallet_equipment_data = palletEquipmentLists.find(
      (x: any) => x.id === row.id
    )

    if (pallet_equipment_data) {
        // getSupplierContact(material_handling_data.supplier_id)
        if (
          pallet_equipment_data.pooled_pallet === null ||
          pallet_equipment_data.pooled_pallet === 0
        ) {
          setIsDisabled(true)
        } else {
          setIsDisabled(false)
        }
        setUpdateFormData({
          id: pallet_equipment_data.id,
          pallet_code: pallet_equipment_data.pallet_code,
          pallet_name: pallet_equipment_data.pallet_name,
          pallet_type: pallet_equipment_data.pallet_type,
          material: pallet_equipment_data.material,
          manufacturer: pallet_equipment_data.manufacturer,
          length: pallet_equipment_data.length,
          width: pallet_equipment_data.width,
          height: pallet_equipment_data.height,
          weight_capacity: pallet_equipment_data.weight_capacity,
          empty_weight: pallet_equipment_data.empty_weight,
          condition: pallet_equipment_data.condition,
          current_location: pallet_equipment_data.current_location,
          purchase_date: pallet_equipment_data.purchase_date,
          last_inspection_date: pallet_equipment_data.last_inspection_date,
          next_inspection_date: pallet_equipment_data.next_inspection_date,
          pooled_pallet: pallet_equipment_data.pooled_pallet,
          pool_provider: pallet_equipment_data.pool_provider,
          cost_per_unit: pallet_equipment_data.cost_per_unit,
          expected_lifespan_year: pallet_equipment_data.expected_lifespan_year,
          rfid_tag: pallet_equipment_data.rfid_tag,
          barcode: pallet_equipment_data.bar_code,
          currently_assigned: pallet_equipment_data.currently_assigned,
          assigned_shipment: pallet_equipment_data.assigned_shipment,
          notes: pallet_equipment_data.notes,
          status: pallet_equipment_data.status,
        })
      
    }
  }

  const handleDisabled = (val:any) => {
      if(val == 1){
        setIsDisabled(false)
      }else{
        setIsDisabled(true)
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
        `/pallet-equipments/${row.id}`
      )
      console.log(response)
      if (response.status == true) {
        Swal.fire({
          title: 'Deleted!',
          text: 'Pallet Equipment has been deleted.',
          icon: 'success',
        })
        fetchPalletEquipmentLists()
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
        '/pallet-equipments',
        formData
      )
      if (response.status === true) {
        setIsCreateOpen(false)
        showToast('', 'Create Pallet Equipment successful', 'top-right', 'success')
        setFormData({
          pallet_code: '',
          pallet_name: '',
          pallet_type: '',
          material: '',
          manufacturer: '',
          length: '',
          width: '',
          height: '',
          weight_capacity: '',
          empty_weight: '',
          condition: '',
          current_location: '',
          purchase_date: '',
          last_inspection_date: '',
          next_inspection_date: '',
          pooled_pallet: '',
          pool_provider: '',
          cost_per_unit: '',
          expected_lifespan_year: '',
          rfid_tag: '',
          barcode: '',
          currently_assigned: '',
          assigned_shipment: '',
          status: '',
          notes: '',
        })
        fetchPalletEquipmentLists()
      } else {
        showToast('', 'Something went wrong!.', 'top-right', 'error')
      }
    } catch (err: any) {
      if (err?.status === 422) {
        showToast('', err?.data.message, 'top-right', 'error')
        const apiErrors: Errors = err?.data.errors
        setErrors(apiErrors)
      } else {
        showToast('', 'Create Pallet failed!', 'top-right', 'error')
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
        `/pallet-equipments/${updateFormData.id}`,
        updateFormData
      )
      if (response.status === true) {
        setIsUpdateOpen(false)
        showToast('', 'Update Pallet Equipment successful', 'top-right', 'success')
        setUpdateFormData({
          id: '',
          pallet_code: '',
          pallet_name: '',
          pallet_type: '',
          material: '',
          manufacturer: '',
          length: '',
          width: '',
          height: '',
          weight_capacity: '',
          empty_weight: '',
          condition: '',
          current_location: '',
          purchase_date: '',
          last_inspection_date: '',
          next_inspection_date: '',
          pooled_pallet: '',
          pool_provider: '',
          cost_per_unit: '',
          expected_lifespan_year: '',
          rfid_tag: '',
          barcode: '',
          currently_assigned: '',
          assigned_shipment: '',
          status: '',
          notes: '',
        })
        fetchPalletEquipmentLists()
      } else {
        showToast('', 'Something went wrong!.', 'top-right', 'error')
      }
    } catch (err: any) {
      if (err?.status === 422) {
        showToast('', err?.message, 'top-right', 'error')
        const apiErrors: Errors = err?.errors
        setErrors(apiErrors)
      } else {
        showToast('', 'Update Pallet Equipment failed!', 'top-right', 'error')
      }
      console.error(err)
    } finally {
      setIsLoading(false)
    }
  }

  const [filterText, setFilterText] = React.useState('')
  const filteredData = useMemo(() => {
    if (!filterText) return palletEquipmentLists

    return palletEquipmentLists.filter((item: any) =>
      Object.values(item).some(
        (val) =>
          val && val.toString().toLowerCase().includes(filterText.toLowerCase())
      )
    )
  }, [filterText, palletEquipmentLists])

  return (
    <div className="space-y-10">
      <div className="flex justify-between items-center">
        <h1 className="text-xl font-semibold">Pallet Equipment Lists</h1>
        <Button variant="primary" size="sm" onClick={handleCreate}>
          Add Pallet
        </Button>
      </div>

      <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 md:gap-6 xl:grid-cols-4">
        <div className="rounded-md shadow-lg border border-gray-200 bg-white p-2">
          <div className="rounded-2xl border border-gray-200 bg-white p-5 ">
            <p className="text-gray-500 text-theme-sm dark:text-gray-400">
              Total Pallets
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
              Available Pallets
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
              Pallets In Use
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
              Pallets Under Repair
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
                  Pallet Code<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={formData.pallet_code}
                  onChange={handleChange('pallet_code')}
                  onKeyUp={() => handleRemove('pallet_code')}
                  error={!!errors.pallet_code}
                  hint={errors.pallet_code}
                />
              </div>
              <div>
                <Label>
                  Pallet Name<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={formData.pallet_name}
                  onChange={handleChange('pallet_name')}
                  onKeyUp={() => handleRemove('pallet_name')}
                  error={!!errors.pallet_name}
                  hint={errors.pallet_name}
                />
              </div>

              <div>
                <Label>
                  Pallet Type<span className="text-error-500">*</span>
                </Label>
                <SingleSelectInput
                  options={palletTypeData}
                  valueKey="value"
                  value={formData.pallet_type}
                  getOptionLabel={(item) => `${item.value}`}
                  onSingleSelectChange={(val) => {
                    console.log('value - ' + val)
                    handleRemove('pallet_type')
                    setFormData((prev) => ({
                      ...prev,
                      pallet_type: val,
                    }))
                  }}
                  error={!!errors.pallet_type}
                  hint={errors.pallet_type}
                />
              </div>
              <div>
                <Label>
                  Material<span className="text-error-500">*</span>
                </Label>
                <SingleSelectInput
                  options={materialTypeData}
                  valueKey="value"
                  value={formData.material}
                  getOptionLabel={(item) => `${item.value}`}
                  onSingleSelectChange={(val) => {
                    console.log('value - ' + val)
                    handleRemove('material')
                    setFormData((prev) => ({
                      ...prev,
                      material: val,
                    }))
                  }}
                  error={!!errors.material}
                  hint={errors.material}
                />
              </div>
              <div>
                <Label>Manufacturer</Label>
                <Input
                  type="text"
                  value={formData.manufacturer}
                  onChange={handleChange('manufacturer')}
                  onKeyUp={() => handleRemove('manufacturer')}
                />
              </div>

              <div>
                <Label>Length(m)</Label>
                <Input
                  type="number"
                  value={formData.length}
                  onChange={handleChange('length')}
                  onKeyUp={() => handleRemove('length')}
                />
              </div>
              <div>
                <Label>Width(m)</Label>
                <Input
                  type="number"
                  value={formData.width}
                  onChange={handleChange('width')}
                  onKeyUp={() => handleRemove('width')}
                />
              </div>
              <div>
                <Label>Height(m)</Label>
                <Input
                  type="number"
                  value={formData.height}
                  onChange={handleChange('height')}
                  onKeyUp={() => handleRemove('height')}
                />
              </div>
              <div>
                <Label>Weight Capacity(kg)</Label>
                <Input
                  type="number"
                  value={formData.weight_capacity}
                  onChange={handleChange('weight_capacity')}
                  onKeyUp={() => handleRemove('weight_capacity')}
                />
              </div>
              <div>
                <Label>Empty Weight</Label>
                <Input
                  type="number"
                  value={formData.empty_weight}
                  onChange={handleChange('empty_weight')}
                  onKeyUp={() => handleRemove('empty_weight')}
                />
              </div>
              <div>
                <Label>Condition</Label>
                <SingleSelectInput
                  options={conditionData}
                  valueKey="id"
                  value={formData.condition}
                  getOptionLabel={(item) => `${item.value}`}
                  onSingleSelectChange={(val) => {
                    console.log('value - ' + val)
                    handleRemove('condition')
                    setFormData((prev) => ({
                      ...prev,
                      condition: val,
                    }))
                  }}
                />
              </div>
              <div>
                <Label>Current Location</Label>
                <Input
                  type="text"
                  value={formData.current_location}
                  onChange={handleChange('current_location')}
                  onKeyUp={() => handleRemove('current_location')}
                />
              </div>
              <div>
                <Label>Pooled Pallet</Label>
                <SingleSelectInput
                  options={pooledPalletData}
                  valueKey="id"
                  value={formData.pooled_pallet}
                  getOptionLabel={(item) => `${item.value}`}
                  onSingleSelectChange={(val) => {
                    console.log('value - ' + val)
                    handleRemove('pooled_pallet')
                    handleDisabled(val)
                    setFormData((prev) => ({
                      ...prev,
                      pooled_pallet: val,
                    }))
                  }}
                />
              </div>
              <div>
                <Label>Pooled Provider</Label>
                <Input
                  type="text"
                  value={formData.pool_provider}
                  onChange={handleChange('pool_provider')}
                  onKeyUp={() => handleRemove('pool_provider')}
                  disabled={isDisabled}
                />
              </div>
              <div>
                <Label>Cost Per Unit</Label>
                <Input
                  type="text"
                  value={formData.cost_per_unit}
                  onChange={handleChange('cost_per_unit')}
                  onKeyUp={() => handleRemove('cost_per_unit')}
                />
              </div>
              <div>
                <Label>Purchase Date</Label>
                <Input
                  type="date"
                  value={formData.purchase_date}
                  onChange={handleChange('purchase_date')}
                  onKeyUp={() => handleRemove('purchase_date')}
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
                <Label>Next Inspection Date</Label>
                <Input
                  type="date"
                  value={formData.next_inspection_date}
                  onChange={handleChange('next_inspection_date')}
                  onKeyUp={() => handleRemove('next_inspection_date')}
                />
              </div>
              <div>
                <Label>RFID Tag</Label>
                <Input
                  type="text"
                  value={formData.rfid_tag}
                  onChange={handleChange('rfid_tag')}
                  onKeyUp={() => handleRemove('rfid_tag')}
                />
              </div>
              <div>
                <Label>Barcode</Label>
                <Input
                  type="text"
                  value={formData.barcode}
                  onChange={handleChange('barcode')}
                  onKeyUp={() => handleRemove('barcode')}
                />
              </div>

              <div>
                <Label>Expected Lifespan (Years)</Label>
                <Input
                  type="number"
                  value={formData.expected_lifespan_year}
                  onChange={handleChange('expected_lifespan_year')}
                  onKeyUp={() => handleRemove('expected_lifespan_year')}
                />
              </div>

              <div>
                <Label>Currently Assigned</Label>
                <Input
                  type="text"
                  value={formData.currently_assigned}
                  onChange={handleChange('currently_assigned')}
                  onKeyUp={() => handleRemove('currently_assigned')}
                />
              </div>

              <div className="col-span-full">
                <Label>Notes</Label>
                <TextAreaInput
                  value={formData.notes}
                  onChange={(value) =>
                    handleChange('note')({
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
            <h2 className="text-xl font-semibold text-gray-800">Add New MHE</h2>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <Label>
                  Pallet Code<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={palletEquipment.pallet_code}
                  disabled={true}
                />
              </div>
              <div>
                <Label>
                  Pallet Name<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={palletEquipment.pallet_name}
                  disabled={true}
                />
              </div>

              <div>
                <Label>
                  Pallet Type<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={palletEquipment.pallet_type}
                  disabled={true}
                />
              </div>
              <div>
                <Label>
                  Material<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={palletEquipment.material}
                  disabled={true}
                />
              </div>
              <div>
                <Label>Manufacturer</Label>
                <Input
                  type="text"
                  value={palletEquipment.manufacturer}
                  disabled={true}
                />
              </div>

              <div>
                <Label>Length(m)</Label>
                <Input
                  type="number"
                  value={palletEquipment.length}
                  disabled={true}
                />
              </div>
              <div>
                <Label>Width(m)</Label>
                <Input
                  type="number"
                  value={palletEquipment.width}
                  disabled={true}
                />
              </div>
              <div>
                <Label>Height(m)</Label>
                <Input
                  type="number"
                  value={palletEquipment.height}
                  disabled={true}
                />
              </div>
              <div>
                <Label>Weight Capacity(kg)</Label>
                <Input
                  type="number"
                  value={palletEquipment.weight_capacity}
                  disabled={true}
                />
              </div>
              <div>
                <Label>Empty Weight</Label>
                <Input
                  type="number"
                  value={palletEquipment.empty_weight}
                  disabled={true}
                />
              </div>
              <div>
                <Label>Condition</Label>
                <Input
                  type="number"
                  value={palletEquipment.condition}
                  disabled={true}
                />
              </div>
              <div>
                <Label>Current Location</Label>
                <Input
                  type="text"
                  value={palletEquipment.current_location}
                  disabled={true}
                />
              </div>
              <div>
                <Label>Pooled Pallet</Label>
                <Input
                  type="number"
                  value={palletEquipment.pooled_pallet}
                  disabled={true}
                />
              </div>
              <div>
                <Label>Pooled Provider</Label>
                <Input
                  type="text"
                  value={palletEquipment.pool_provider}
                  disabled={true}
                />
              </div>
              <div>
                <Label>Cost Per Unit</Label>
                <Input
                  type="text"
                  value={palletEquipment.cost_per_unit}
                  disabled={true}
                />
              </div>
              <div>
                <Label>Purchase Date</Label>
                <Input
                  type="date"
                  value={palletEquipment.purchase_date}
                  disabled={true}
                />
              </div>
              <div>
                <Label>Last Inspection Date</Label>
                <Input
                  type="date"
                  value={palletEquipment.last_inspection_date}
                  disabled={true}
                />
              </div>
              <div>
                <Label>Next Inspection Date</Label>
                <Input
                  type="date"
                  value={palletEquipment.next_inspection_date}
                  disabled={true}
                />
              </div>
              <div>
                <Label>RFID Tag</Label>
                <Input
                  type="text"
                  value={palletEquipment.rfid_tag}
                  disabled={true}
                />
              </div>
              <div>
                <Label>Barcode</Label>
                <Input
                  type="text"
                  value={palletEquipment.barcode}
                  disabled={true}
                />
              </div>

              <div>
                <Label>Expected Lifespan (Years)</Label>
                <Input
                  type="number"
                  value={palletEquipment.expected_lifespan_year}
                  disabled={true}
                />
              </div>

              <div>
                <Label>Currently Assigned</Label>
                <Input
                  type="text"
                  value={palletEquipment.currently_assigned}
                  disabled={true}
                />
              </div>

              <div className="col-span-full">
                <Label>Notes</Label>
                <TextAreaInput value={palletEquipment.notes} disabled={true} />
              </div>

              <div>
                <Label>
                  Status<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="number"
                  value={palletEquipment.status}
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
                  Pallet Code<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={updateFormData.pallet_code}
                  onChange={handleChange('pallet_code')}
                  onKeyUp={() => handleRemove('pallet_code')}
                  error={!!errors.pallet_code}
                  hint={errors.pallet_code}
                />
              </div>
              <div>
                <Label>
                  Pallet Name<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={updateFormData.pallet_name}
                  onChange={handleChange('pallet_name')}
                  onKeyUp={() => handleRemove('pallet_name')}
                  error={!!errors.pallet_name}
                  hint={errors.pallet_name}
                />
              </div>

              <div>
                <Label>
                  Pallet Type<span className="text-error-500">*</span>
                </Label>
                <SingleSelectInput
                  options={palletTypeData}
                  valueKey="value"
                  value={updateFormData.pallet_type}
                  getOptionLabel={(item) => `${item.value}`}
                  onSingleSelectChange={(val) => {
                    console.log('value - ' + val)
                    handleRemove('pallet_type')
                    setUpdateFormData((prev) => ({
                      ...prev,
                      pallet_type: val,
                    }))
                  }}
                  error={!!errors.pallet_type}
                  hint={errors.pallet_type}
                />
              </div>
              <div>
                <Label>
                  Material<span className="text-error-500">*</span>
                </Label>
                <SingleSelectInput
                  options={materialTypeData}
                  valueKey="value"
                  value={updateFormData.material}
                  getOptionLabel={(item) => `${item.value}`}
                  onSingleSelectChange={(val) => {
                    console.log('value - ' + val)
                    handleRemove('material')
                    setUpdateFormData((prev) => ({
                      ...prev,
                      material: val,
                    }))
                  }}
                  error={!!errors.material}
                  hint={errors.material}
                />
              </div>
              <div>
                <Label>Manufacturer</Label>
                <Input
                  type="text"
                  value={updateFormData.manufacturer}
                  onChange={handleChange('manufacturer')}
                  onKeyUp={() => handleRemove('manufacturer')}
                />
              </div>

              <div>
                <Label>Length(m)</Label>
                <Input
                  type="number"
                  value={updateFormData.length}
                  onChange={handleChange('length')}
                  onKeyUp={() => handleRemove('length')}
                />
              </div>
              <div>
                <Label>Width(m)</Label>
                <Input
                  type="number"
                  value={updateFormData.width}
                  onChange={handleChange('width')}
                  onKeyUp={() => handleRemove('width')}
                />
              </div>
              <div>
                <Label>Height(m)</Label>
                <Input
                  type="number"
                  value={updateFormData.height}
                  onChange={handleChange('height')}
                  onKeyUp={() => handleRemove('height')}
                />
              </div>
              <div>
                <Label>Weight Capacity(kg)</Label>
                <Input
                  type="number"
                  value={updateFormData.weight_capacity}
                  onChange={handleChange('weight_capacity')}
                  onKeyUp={() => handleRemove('weight_capacity')}
                />
              </div>
              <div>
                <Label>Empty Weight</Label>
                <Input
                  type="number"
                  value={updateFormData.empty_weight}
                  onChange={handleChange('empty_weight')}
                  onKeyUp={() => handleRemove('empty_weight')}
                />
              </div>
              <div>
                <Label>Condition</Label>
                <SingleSelectInput
                  options={conditionData}
                  valueKey="id"
                  value={updateFormData.condition}
                  getOptionLabel={(item) => `${item.value}`}
                  onSingleSelectChange={(val) => {
                    console.log('value - ' + val)
                    handleRemove('condition')
                    setUpdateFormData((prev) => ({
                      ...prev,
                      condition: val,
                    }))
                  }}
                />
              </div>
              <div>
                <Label>Current Location</Label>
                <Input
                  type="text"
                  value={updateFormData.current_location}
                  onChange={handleChange('current_location')}
                  onKeyUp={() => handleRemove('current_location')}
                />
              </div>
              <div>
                <Label>Pooled Pallet</Label>
                <SingleSelectInput
                  options={pooledPalletData}
                  valueKey="id"
                  value={updateFormData.pooled_pallet}
                  getOptionLabel={(item) => `${item.value}`}
                  onSingleSelectChange={(val) => {
                    console.log('value - ' + val)
                    handleRemove('pooled_pallet')
                    handleDisabled(val)
                    setUpdateFormData((prev) => ({
                      ...prev,
                      pooled_pallet: val,
                    }))
                  }}
                />
              </div>
              <div>
                <Label>Pooled Provider</Label>
                <Input
                  type="text"
                  value={updateFormData.pool_provider}
                  onChange={handleChange('pool_provider')}
                  onKeyUp={() => handleRemove('pool_provider')}
                  disabled={isDisabled}
                />
              </div>
              <div>
                <Label>Cost Per Unit</Label>
                <Input
                  type="text"
                  value={updateFormData.cost_per_unit}
                  onChange={handleChange('cost_per_unit')}
                  onKeyUp={() => handleRemove('cost_per_unit')}
                />
              </div>
              <div>
                <Label>Purchase Date</Label>
                <Input
                  type="date"
                  value={updateFormData.purchase_date}
                  onChange={handleChange('purchase_date')}
                  onKeyUp={() => handleRemove('purchase_date')}
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
                <Label>Next Inspection Date</Label>
                <Input
                  type="date"
                  value={updateFormData.next_inspection_date}
                  onChange={handleChange('next_inspection_date')}
                  onKeyUp={() => handleRemove('next_inspection_date')}
                />
              </div>
              <div>
                <Label>RFID Tag</Label>
                <Input
                  type="text"
                  value={updateFormData.rfid_tag}
                  onChange={handleChange('rfid_tag')}
                  onKeyUp={() => handleRemove('rfid_tag')}
                />
              </div>
              <div>
                <Label>Barcode</Label>
                <Input
                  type="text"
                  value={updateFormData.barcode}
                  onChange={handleChange('barcode')}
                  onKeyUp={() => handleRemove('barcode')}
                />
              </div>

              <div>
                <Label>Expected Lifespan (Years)</Label>
                <Input
                  type="number"
                  value={updateFormData.expected_lifespan_year}
                  onChange={handleChange('expected_lifespan_year')}
                  onKeyUp={() => handleRemove('expected_lifespan_year')}
                />
              </div>

              <div>
                <Label>Currently Assigned</Label>
                <Input
                  type="text"
                  value={updateFormData.currently_assigned}
                  onChange={handleChange('currently_assigned')}
                  onKeyUp={() => handleRemove('currently_assigned')}
                />
              </div>

              <div className="col-span-full">
                <Label>Notes</Label>
                <TextAreaInput
                  value={updateFormData.notes}
                  onChange={(value) =>
                    handleChange('note')({
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
