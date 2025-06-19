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

interface UOMOption {
  id: string
  short_code: string
  name: string
}

interface RowData {
  uom_code: string
  uom_name: string
  base_uom_id: string
  conversion_factor: string
  status: number
  [key: string]: any
}

interface Errors {
  uom_code?: string
  uom_name?: string
  base_uom_id?: any
  conversion_factor?: any
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
    selector: (row: RowData) => row.uom_code,
    sortable: true,
  },
  {
    name: 'Name',
    selector: (row: RowData) => row.uom_name,
    sortable: true,
  },
  {
    name: 'Base UOM',
    selector: (row: RowData) =>
      `${row.base_uom.short_code} - ${row.base_uom.name}`,
    sortable: true,
  },
  {
    name: 'Conversion Factor',
    selector: (row: RowData) => row.conversion_factor,
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

const UnitOfMeasure: React.FC = () => {
  const [isCreateOpen, setIsCreateOpen] = useState(false)
  const [isViewOpen, setIsViewOpen] = useState(false)
  const [isUpdateOpen, setIsUpdateOpen] = useState(false)
  const [errors, setErrors] = useState<Errors>({})
  const [baseUomLists, setBaseUomLists] = useState<UOMOption[]>([])
  const [uomLists, setUomLists] = useState<any>([])
  const [unitOfMeasure, setUnitOfMeasure] = useState<any>({})
  const [isLoading, setIsLoading] = useState(false)
  const [isPageLoading, setIsPageLoading] = useState(false)
  const { showToast } = provideUtility()

  const [formData, setFormData] = useState({
    uom_code: '',
    uom_name: '',
    conversion_factor: '',
    base_uom_id: '',
    description: '',
    status:1
  })

  const [updateFormData, setUpdateFormData] = useState({
    id: '',
    uom_code: '',
    uom_name: '',
    conversion_factor: '',
    base_uom_id: '',
    description: '',
    status: '',
  })

  useEffect(() => {
    setIsPageLoading(true)
    fetchBaseUomLists()
    fetchUomLists()
  }, [])

  const fetchBaseUomLists = async () => {
    try {
      setIsLoading(true)
      const res = await http.fetchDataWithToken('get-base-uom-lists')
      setBaseUomLists(res.data?.data || [])
    } catch (err) {
      console.error('Failed to fetch UOM lists:', err)
    } finally {
      setIsLoading(false)
    }
  }

  const fetchUomLists = async () => {
    try {
      setIsPageLoading(true)
      const res = await http.fetchDataWithToken('unit_of_measures')
      console.log(res.data)
      setUomLists(res.data?.data || [])
    } catch (err) {
      setIsPageLoading(false)
      console.error('Failed to fetch UOM lists:', err)
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
    setUnitOfMeasure(uomLists.find((x:any) => x.id === row.id))
    setIsViewOpen(true)
  }

  const handleCreate = () => {
  setErrors({
    uom_code: '',
    uom_name: '',
    base_uom_id: '',
    conversion_factor: '',
  })
    setIsCreateOpen(true)
  }

  const handleEdit = (row: any) => {
    setIsUpdateOpen(true)
    setErrors({
      uom_code: '',
      uom_name: '',
      base_uom_id: '',
      conversion_factor: '',
    })
    const uom_data = uomLists.find((x: any) => x.id === row.id)

    if (uom_data) {
      setUpdateFormData({
        id:uom_data.id || '',
        uom_code: uom_data.uom_code || '',
        uom_name: uom_data.uom_name || '',
        conversion_factor: uom_data.conversion_factor?.toString() || '',
        base_uom_id: uom_data.base_uom_id?.toString() || '',
        description: uom_data.description || '',
        status: uom_data.status || '',
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
      const response = await http.deleteDataWithToken(`/unit_of_measures/${row.id}`)
      console.log(response)
      if(response.status == true){
        Swal.fire({
          title: 'Deleted!',
          text: 'Unit of Measure has been deleted.',
          icon: 'success',
        })
        fetchUomLists()
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
        '/unit_of_measures',
        formData
      )
      if(response.status === true){
          setIsCreateOpen(false)
          showToast('', 'Create Unit Of Measure successful', 'top-right', 'success')
          setFormData({
            uom_code: '',
            uom_name: '',
            base_uom_id: '',
            conversion_factor: '',
            description: '',
            status: 1,
          })
          fetchUomLists()
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
        showToast('', 'Create Unit of Measure failed!', 'top-right', 'error')
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
        `/unit_of_measures/${updateFormData.id}`,
        updateFormData
      )
      if (response.status === true) {
        setIsUpdateOpen(false)
        showToast(
          '',
          'Update Unit Of Measure successful',
          'top-right',
          'success'
        )
        setUpdateFormData({
          id:'',
          uom_code: '',
          uom_name: '',
          base_uom_id: '',
          conversion_factor: '',
          description: '',
          status: '',
        })
        fetchUomLists()
      } else {
        showToast('', 'Something went wrong!.', 'top-right', 'error')
      }
    } catch (err: any) {
      if (err?.status === 422) {
        showToast('', err?.message, 'top-right', 'error')
        const apiErrors: Errors = err?.errors
        setErrors(apiErrors)
      } else {
        showToast('', 'Update Unit of Measure failed!', 'top-right', 'error')
      }
      console.error(err)
    } finally {
      setIsLoading(false)
    }
  }

  const [filterText, setFilterText] = React.useState('')
  const filteredData = useMemo(() => {
  if (!filterText) return uomLists

  return uomLists.filter((item:any) =>
    Object.values(item).some(
      (val) =>
        val && val.toString().toLowerCase().includes(filterText.toLowerCase())
    )
  )
 }, [filterText, uomLists])

  return (
    <div className="space-y-10">
      <div className="flex justify-between items-center">
        <h1 className="text-xl font-semibold">Unit Of Measure Lists</h1>
        <Button variant="primary" size="sm" onClick={handleCreate}>
          Add UOM
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
                placeholder="Search UOMs…"
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
              Add New Unit Of Measure
            </h2>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <Label>
                  UOM Code<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={formData.uom_code}
                  onChange={handleChange('uom_code')}
                  onKeyUp={() => handleRemove('uom_code')}
                  error={!!errors.uom_code}
                  hint={errors.uom_code}
                />
              </div>
              <div>
                <Label>
                  UOM Name<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={formData.uom_name}
                  onChange={handleChange('uom_name')}
                  onKeyUp={() => handleRemove('uom_name')}
                  error={!!errors.uom_name}
                  hint={errors.uom_name}
                />
              </div>
              <div>
                <Label>
                  Base UOM<span className="text-error-500">*</span>
                </Label>
                <SingleSelectInput
                  options={baseUomLists}
                  valueKey="id"
                  value={formData.base_uom_id}
                  getOptionLabel={(item) => `${item.short_code} - ${item.name}`}
                  onSingleSelectChange={(val) => {
                    handleRemove('base_uom_id')
                    setFormData((prev) => ({ ...prev, base_uom_id: val }))
                  }}
                  error={!!errors.base_uom_id}
                  hint={errors.base_uom_id}
                />
              </div>
              <div>
                <Label>
                  Conversion Factor<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="number"
                  value={formData.conversion_factor}
                  onChange={handleChange('conversion_factor')}
                  onKeyUp={() => handleRemove('conversion_factor')}
                  error={!!errors.conversion_factor}
                  hint={errors.conversion_factor}
                />
              </div>
              <div className="col-span-full">
                {/* <Label>Description</Label> */}
                <TextAreaInput
                  value={formData.description}
                  onChange={(value) =>
                    handleChange('description')({
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
              Add New Unit Of Measure
            </h2>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <Label>UOM Code</Label>
                <Input
                  type="text"
                  value={unitOfMeasure.uom_code}
                  disabled={true}
                />
              </div>
              <div>
                <Label>UOM Name</Label>
                <Input
                  type="text"
                  value={unitOfMeasure.uom_name}
                  disabled={true}
                />
              </div>
              <div>
                <Label>Base UOM</Label>
                <Input
                  type="text"
                  value={
                    baseUomLists.find(
                      (x: any) => x.id == unitOfMeasure.base_uom
                    )?.name || ''
                  }
                  disabled={true}
                />
              </div>
              <div>
                <Label>Conversion Factor</Label>
                <Input
                  type="number"
                  value={unitOfMeasure.conversion_factor}
                  disabled={true}
                />
              </div>
              <div className="col-span-full">
                {/* <Label>Description</Label> */}
                <TextAreaInput
                  value={unitOfMeasure.description}
                  onChange={handleChange('description')}
                  disabled={true}
                />
              </div>
              <div>
                <Label>Status</Label>
                <ToggleSwitchInput
                  label="Enable Active"
                  defaultChecked={unitOfMeasure.status}
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
              Update Unit Of Measure
            </h2>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <Label>
                  UOM Code<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={updateFormData.uom_code}
                  onChange={handleChange('uom_code')}
                  onKeyUp={() => handleRemove('uom_code')}
                  error={!!errors.uom_code}
                  hint={errors.uom_code}
                />
              </div>
              <div>
                <Label>
                  UOM Name<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={updateFormData.uom_name}
                  onChange={handleChange('uom_name')}
                  onKeyUp={() => handleRemove('uom_name')}
                  error={!!errors.uom_name}
                  hint={errors.uom_name}
                />
              </div>
              <div>
                <Label>
                  Base UOM<span className="text-error-500">*</span>
                </Label>
                <SingleSelectInput
                  options={baseUomLists}
                  valueKey="id"
                  value={updateFormData.base_uom_id}
                  getOptionLabel={(item) => `${item.short_code} - ${item.name}`}
                  onSingleSelectChange={(val) => {
                    handleRemove('base_uom_id')
                    setUpdateFormData((prev) => ({ ...prev, base_uom_id: val }))
                  }}
                  error={!!errors.base_uom_id}
                  hint={errors.base_uom_id}
                />
              </div>
              <div>
                <Label>
                  Conversion Factor<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="number"
                  value={updateFormData.conversion_factor}
                  onChange={handleChange('conversion_factor')}
                  onKeyUp={() => handleRemove('conversion_factor')}
                  error={!!errors.conversion_factor}
                  hint={errors.conversion_factor}
                />
              </div>
              <div className="col-span-full">
                {/* <Label>Description</Label> */}
                <TextAreaInput
                  value={updateFormData.description}
                  onChange={handleChange('description')}
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

export default UnitOfMeasure
