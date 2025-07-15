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
  tax_code: string
  tax_description: string
  tax_type: string
  tax_rate: string
  effective_date:string
  tax_calculation_method:string
  tax_authority:string
  notes:string
  status: number
  [key: string]: any
}

interface Errors {
  tax_code?: string
  tax_description?: string
  tax_type?: string
  tax_rate?: string
  effective_date?: string
  tax_calculation_method?: string
  tax_authority?: string
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
    selector: (row: RowData) => row.tax_code,
    sortable: true,
  },
  {
    name: 'Description',
    selector: (row: RowData) => row.tax_description,
    sortable: true,
  },
  {
    name: 'Type',
    selector: (row: RowData) => row.tax_type || '-',
    sortable: true,
  },
  {
    name: 'Rate',
    selector: (row: RowData) => row.tax_rate || '-',
    sortable: true,
  },
  {
    name: 'Effective Date',
    selector: (row: RowData) => row.effective_date || '-',
    sortable: true,
  },
  {
    name: 'Tax Calculation Method',
    selector: (row: RowData) => row.tax_calculation_method || '-',
    sortable: true,
  },
  {
    name: 'Tax Authority',
    selector: (row: RowData) => row.tax_authority || '-',
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
  const [taxLists, setTaxLists] = useState<any>([])
  const [tax, setTax] = useState<any>({})
  const [isPageLoading, setIsPageLoading] = useState(false)
  const [isLoading, setIsLoading] = useState(false)
  const { showToast } = provideUtility()

  const taxTypeData = [
    { id: 1, value: 'VAT' },
    { id: 2, value: 'Withholding' },
    { id: 3, value: 'Income' },
    { id: 4, value: 'SGT' },
    { id: 5, value: 'Property' },
  ]

  const taxCalculationMethodData = [
    { id: 1, value: 'Percentage' },
  ]

  const [formData, setFormData] = useState({
    tax_code:  '',
    tax_description:  '',
    tax_type:  '',
    tax_rate:  '',
    effective_date: '',
    tax_calculation_method: '',
    tax_authority: '',
    notes: '',
    status: 1
  })

  const [updateFormData, setUpdateFormData] = useState({
    id: '',
    tax_code: '',
    tax_description: '',
    tax_type: '',
    tax_rate: '',
    effective_date: '',
    tax_calculation_method: '',
    tax_authority: '',
    notes: '',
    status: '',
  })

  useEffect(() => {
    setIsPageLoading(true)
    fetchTaxLists()
  }, [])

  const fetchTaxLists = async () => {
    try {
      setIsPageLoading(true)
      const res = await http.fetchDataWithToken('taxes')
      console.log(res)
      setTaxLists(res.data?.data || [])
    } catch (err) {
      setIsPageLoading(false)
      console.error('Failed to fetch Tax lists:', err)
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
    setTax(taxLists?.find((x:any) => x.id === row.id))
    setIsViewOpen(true)
  }

  const handleCreate = () => {
    setErrors({
        tax_code: '',
        tax_description: '',
        tax_type: '',
        tax_rate: '',
        effective_date: '',
        tax_calculation_method: '',
        tax_authority: '',
        status: ''
    })
    setIsCreateOpen(true)
  }

  const handleEdit = (row: any) => {
    setIsUpdateOpen(true)
    setErrors({
        tax_code: '',
        tax_description: '',
        tax_type: '',
        tax_rate: '',
        effective_date: '',
        tax_calculation_method: '',
        tax_authority: '',
        status: ''
    })
    const tax_data = taxLists.find((x: any) => x.id === row.id)
    if (tax_data) {
      setUpdateFormData({
        id: tax_data.id,
        tax_code: tax_data.tax_code,
        tax_description: tax_data.tax_description,
        tax_type: tax_data.tax_type,
        tax_rate: tax_data.tax_rate,
        effective_date: tax_data.effective_date,
        tax_calculation_method: tax_data.tax_calculation_method,
        tax_authority: tax_data.tax_authority,
        notes: tax_data.notes,
        status: tax_data.status,
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
      const response = await http.deleteDataWithToken(`/taxes/${row.id}`)
      console.log(response)
      if(response.status == true){
        Swal.fire({
          title: 'Deleted!',
          text: 'Tax has been deleted.',
          icon: 'success',
        })
        fetchTaxLists()
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
        '/taxes',
        formData
      )
      if(response.status === true){
          setIsCreateOpen(false)
          showToast('', 'Create Shipping Carrier successful', 'top-right', 'success')
          setFormData({
            tax_code: '',
            tax_description: '',
            tax_type: '',
            tax_rate: '',
            effective_date: '',
            tax_calculation_method: '',
            tax_authority: '',
            notes: '',
            status: 1,
          })
          fetchTaxLists()
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
        showToast('', 'Create Tax failed!', 'top-right', 'error')
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
        `/taxes/${updateFormData.id}`,
        updateFormData
      )
      if (response.status === true) {
        setIsUpdateOpen(false)
        showToast(
          '',
          'Update Tax successful',
          'top-right',
          'success'
        )
        setUpdateFormData({
            id: '',
            tax_code:  '',
            tax_description:  '',
            tax_type:  '',
            tax_rate:  '',
            effective_date: '',
            tax_calculation_method: '',
            tax_authority: '',
            notes: '',
            status:''
        })
        fetchTaxLists()
      } else {
        showToast('', 'Something went wrong!.', 'top-right', 'error')
      }
    } catch (err: any) {
      if (err?.status === 422) {
        showToast('', err?.message, 'top-right', 'error')
        const apiErrors: Errors = err?.errors
        setErrors(apiErrors)
      } else {
        showToast('', 'Update Tax failed!', 'top-right', 'error')
      }
      console.error(err)
    } finally {
      setIsLoading(false)
    }
  }

  const [filterText, setFilterText] = React.useState('')
    const filteredData = useMemo(() => {
        if (!filterText) return taxLists

        return taxLists.filter((item: any) =>
        Object.values(item).some(
            (val) =>
            val && val.toString().toLowerCase().includes(filterText.toLowerCase())
        )
        )
    }, [filterText, taxLists])

  return (
    <div className="space-y-10">
      <div className="flex justify-between items-center">
        <h1 className="text-xl font-semibold">Tax Lists</h1>
        <Button variant="primary" size="sm" onClick={handleCreate}>
          Add Tax
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
                placeholder="Search Taxes…"
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
            <h2 className="text-xl font-semibold text-gray-800">Add New Tax</h2>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <Label>
                  Tax Code<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={formData.tax_code}
                  onChange={handleChange('tax_code')}
                  onKeyUp={() => handleRemove('tax_code')}
                  error={!!errors.tax_code}
                  hint={errors.tax_code}
                />
              </div>
              <div>
                <Label>
                  Tax Description<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={formData.tax_description}
                  onChange={handleChange('tax_description')}
                  onKeyUp={() => handleRemove('tax_description')}
                  error={!!errors.tax_description}
                  hint={errors.tax_description}
                />
              </div>

              <div>
                <Label>Tax Type</Label>
                <SingleSelectInput
                  options={taxTypeData}
                  valueKey="value"
                  value={formData.tax_type}
                  getOptionLabel={(item) => `${item.value}`}
                  onSingleSelectChange={(val) => {
                    handleRemove('tax_type')
                    setFormData((prev) => ({
                      ...prev,
                      tax_type: val,
                    }))
                  }}
                  error={!!errors.tax_type}
                  hint={errors.tax_type}
                />
              </div>
              <div>
                <Label>Tax Rate</Label>
                <Input
                  type="text"
                  value={formData.tax_rate}
                  onChange={handleChange('tax_rate')}
                  onKeyUp={() => handleRemove('tax_rate')}
                  error={!!errors.tax_rate}
                  hint={errors.tax_rate}
                />
              </div>
              <div>
                <Label>Effective Date</Label>
                <Input
                  type="date"
                  value={formData.effective_date}
                  onChange={handleChange('effective_date')}
                  onKeyUp={() => handleRemove('effective_date')}
                  error={!!errors.effective_date}
                  hint={errors.effective_date}
                />
              </div>
              <div>
                <Label>Tax Calculation Method</Label>
                <SingleSelectInput
                  options={taxCalculationMethodData}
                  valueKey="value"
                  value={formData.tax_calculation_method}
                  getOptionLabel={(item) => `${item.value}`}
                  onSingleSelectChange={(val) => {
                    handleRemove('tax_calculation_method')
                    setFormData((prev) => ({
                      ...prev,
                      tax_calculation_method: val,
                    }))
                  }}
                  error={!!errors.tax_calculation_method}
                  hint={errors.tax_calculation_method}
                />
              </div>

              <div>
                <Label>Tax Authority</Label>
                <Input
                  type="text"
                  value={formData.tax_authority}
                  onChange={handleChange('tax_authority')}
                  onKeyUp={() => handleRemove('tax_authority')}
                  error={!!errors.tax_authority}
                  hint={errors.tax_authority}
                />
              </div>
              <div>
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
            <h2 className="text-xl font-semibold text-gray-800">Add New Tax</h2>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <Label>
                  Tax Code<span className="text-error-500">*</span>
                </Label>
                <Input type="text" value={tax.tax_code} disabled={true} />
              </div>
              <div>
                <Label>
                  Tax Description<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={tax.tax_description}
                  disabled={true}
                />
              </div>

              <div>
                <Label>Tax Type</Label>
                <Input type="text" value={tax.tax_type} disabled={true} />
              </div>
              <div>
                <Label>Tax Rate</Label>
                <Input type="text" value={tax.tax_rate} disabled={true} />
              </div>
              <div>
                <Label>Effective Date</Label>
                <Input type="date" value={tax.effective_date} disabled={true} />
              </div>
              <div>
                <Label>Tax Calculation Method</Label>
                <Input
                  type="text"
                  value={tax.tax_calculation_method}
                  disabled={true}
                />
              </div>

              <div>
                <Label>Tax Authority</Label>
                <Input type="text" value={tax.tax_authority} disabled={true} />
              </div>
              <div>
                <Label>Notes</Label>
                <TextAreaInput value={tax.notes} disabled={true} />
              </div>
              <div>
                <Label>Status</Label>
                <ToggleSwitchInput
                  label="Enable Active"
                  defaultChecked={!!tax.status}
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
            <h2 className="text-xl font-semibold text-gray-800">Edit Tax</h2>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <Label>
                  Tax Code<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={updateFormData.tax_code}
                  onChange={handleChange('tax_code')}
                  onKeyUp={() => handleRemove('tax_code')}
                  error={!!errors.tax_code}
                  hint={errors.tax_code}
                />
              </div>
              <div>
                <Label>
                  Tax Description<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={updateFormData.tax_description}
                  onChange={handleChange('tax_description')}
                  onKeyUp={() => handleRemove('tax_description')}
                  error={!!errors.tax_description}
                  hint={errors.tax_description}
                />
              </div>

              <div>
                <Label>Tax Type</Label>
                <SingleSelectInput
                  options={taxTypeData}
                  valueKey="value"
                  value={updateFormData.tax_type}
                  getOptionLabel={(item) => `${item.value}`}
                  onSingleSelectChange={(val) => {
                    handleRemove('tax_type')
                    setUpdateFormData((prev) => ({
                      ...prev,
                      tax_type: val,
                    }))
                  }}
                  error={!!errors.tax_type}
                  hint={errors.tax_type}
                />
              </div>
              <div>
                <Label>Tax Rate</Label>
                <Input
                  type="text"
                  value={updateFormData.tax_rate}
                  onChange={handleChange('tax_rate')}
                  onKeyUp={() => handleRemove('tax_rate')}
                  error={!!errors.tax_rate}
                  hint={errors.tax_rate}
                />
              </div>
              <div>
                <Label>Effective Date</Label>
                <Input
                  type="date"
                  value={updateFormData.effective_date}
                  onChange={handleChange('effective_date')}
                  onKeyUp={() => handleRemove('effective_date')}
                  error={!!errors.effective_date}
                  hint={errors.effective_date}
                />
              </div>
              <div>
                <Label>Tax Calculation Method</Label>
                <SingleSelectInput
                  options={taxCalculationMethodData}
                  valueKey="value"
                  value={updateFormData.tax_calculation_method}
                  getOptionLabel={(item) => `${item.value}`}
                  onSingleSelectChange={(val) => {
                    handleRemove('tax_calculation_method')
                    setUpdateFormData((prev) => ({
                      ...prev,
                      tax_calculation_method: val,
                    }))
                  }}
                  error={!!errors.tax_calculation_method}
                  hint={errors.tax_calculation_method}
                />
              </div>

              <div>
                <Label>Tax Authority</Label>
                <Input
                  type="text"
                  value={updateFormData.tax_authority}
                  onChange={handleChange('tax_authority')}
                  onKeyUp={() => handleRemove('tax_authority')}
                  error={!!errors.tax_authority}
                  hint={errors.tax_authority}
                />
              </div>
              <div>
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
  )
}

export default List


