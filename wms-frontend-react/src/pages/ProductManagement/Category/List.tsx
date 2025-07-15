import React, { useState, useEffect} from 'react'
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
  category_code: string
  category_name: string
  parent: string
  hierarchy_level: string
  applicable_industry: string
  storage_condition: string
  handling_instructions: string
  tax_category?: string
  uom_id: string
  status: number
  [key: string]: any
}

interface Errors {
  category_code?: string
  category_name?: string
  hierarchy_level?: any
  applicable_industry?: string
  storage_condition?: string
  handling_instructions?: string
  tax_category?: string
  uom_id?: any
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
    selector: (row: RowData) => row.category_code,
    sortable: true,
  },
  {
    name: 'Name',
    selector: (row: RowData) => row.category_name,
    sortable: true,
  },
  {
    name: 'Parent Category',
    selector: (row: RowData) => row.parent_category?.category_name || '-',
    sortable: true,
  },
  {
    name: 'Hierarchy Level',
    selector: (row: RowData) => row.hierarchy_level || '-',
    sortable: true,
  },
  {
    name: 'Applicable Industry',
    selector: (row: RowData) => row.applicable_industry,
    sortable: true,
  },
  {
    name: 'Storage Conditions',
    selector: (row: RowData) => row.storage_condition,
    sortable: true,
  },
  {
    name: 'Handling Instructions',
    selector: (row: RowData) => row.handling_instructions,
    sortable: true,
  },
  {
    name: 'Tax Category',
    selector: (row: RowData) => row.tax_category,
    sortable: true,
  },
  {
    name: 'Default UOM',
    selector: (row: RowData) =>
      `${row.unit_of_measure?.uom_name} (${row.unit_of_measure?.uom_code})`,
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

const CategoryList: React.FC = () => {
  const [isCreateOpen, setIsCreateOpen] = useState(false)
  const [isViewOpen, setIsViewOpen] = useState(false)
  const [isUpdateOpen, setIsUpdateOpen] = useState(false)
  const [errors, setErrors] = useState<Errors>({})
  const [categoryLists, setCategoryLists] = useState<any>([])
  const [uomLists, setUomLists] = useState<any>([])
  const [category, setCategory] = useState<any>({})
  const [isPageLoading, setIsPageLoading] = useState(false)
  const [isLoading, setIsLoading] = useState(false)
  const { showToast } = provideUtility()

  const [formData, setFormData] = useState({
    category_code: '',
    category_name: '',
    parent_id: '',
    hierarchy_level: '',
    applicable_industry: '',
    storage_condition: '',
    handling_instructions: '',
    tax_category: '',
    uom_id: '',
    description: '',
    status: 1,
  })

  const [updateFormData, setUpdateFormData] = useState({
    id: '',
    category_code: '',
    category_name: '',
    parent_id: '',
    hierarchy_level: '',
    applicable_industry: '',
    storage_condition: '',
    handling_instructions: '',
    tax_category: '',
    uom_id: '',
    description: '',
    status: '',
  })

  useEffect(() => {
    setIsPageLoading(true)
    fetchCategoryLists()
    fetchUomLists()
  }, [])

  const fetchUomLists = async () => {
    try {
      setIsLoading(true)
      const res = await http.fetchDataWithToken('unit_of_measures')
      console.log(res)
      setUomLists(res.data?.data || [])
    } catch (err) {
      console.error('Failed to fetch UOM lists:', err)
    } finally {
      setIsLoading(false)
    }
  }

  const fetchCategoryLists = async () => {
    try {
      setIsPageLoading(true)
      const res = await http.fetchDataWithToken('categories')
      console.log(res.data)
      setCategoryLists(res.data?.data || [])
    } catch (err) {
      setIsPageLoading(false)
      console.error('Failed to fetch Category lists:', err)
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
    setCategory(categoryLists.find((x:any) => x.id === row.id))
    setIsViewOpen(true)
  }

  const handleCreate = () => {
    setErrors({
      category_code: '',
      category_name: '',
      hierarchy_level: '',
      applicable_industry: '',
      storage_condition: '',
      handling_instructions: '',
      tax_category: '',
      uom_id: '',
    })
    setIsCreateOpen(true)
  }

  const handleEdit = (row: any) => {
    setIsUpdateOpen(true)
    setErrors({
      category_code: '',
      category_name: '',
      hierarchy_level: '',
      applicable_industry: '',
      storage_condition: '',
      handling_instructions: '',
      tax_category: '',
      uom_id: '',
    })
    const category_data = categoryLists.find((x: any) => x.id === row.id)
    if (category_data) {
      setUpdateFormData({
        id: category_data.id || '',
        category_code: category_data.category_code || '',
        category_name: category_data.category_name || '',
        parent_id: category_data.parent_id || '',
        hierarchy_level: category_data.hierarchy_level || '',
        applicable_industry: category_data.applicable_industry || '',
        storage_condition: category_data.storage_condition || '',
        handling_instructions: category_data.handling_instructions || '',
        tax_category: category_data.tax_category || '',
        uom_id: category_data.uom_id?.toString() || '',
        description: category_data.description || '',
        status: category_data.status || '',
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
      const response = await http.deleteDataWithToken(`/categories/${row.id}`)
      console.log(response)
      if(response.status == true){
        Swal.fire({
          title: 'Deleted!',
          text: 'Category has been deleted.',
          icon: 'success',
        })
        fetchCategoryLists()
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
        '/categories',
        formData
      )
      if(response.status === true){
          setIsCreateOpen(false)
          showToast('', 'Create Category successful', 'top-right', 'success')
          setFormData({
            category_code: '',
            category_name: '',
            parent_id: '',
            hierarchy_level: '',
            applicable_industry: '',
            storage_condition: '',
            handling_instructions: '',
            tax_category: '',
            uom_id: '',
            description: '',
            status: 1,
          })
          fetchCategoryLists()
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
        showToast('', 'Create Category failed!', 'top-right', 'error')
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
        `/categories/${updateFormData.id}`,
        updateFormData
      )
      if (response.status === true) {
        setIsUpdateOpen(false)
        showToast(
          '',
          'Update Category successful',
          'top-right',
          'success'
        )
        setUpdateFormData({
          id: '',
          category_code: '',
          category_name: '',
          parent_id: '',
          hierarchy_level: '',
          applicable_industry: '',
          storage_condition: '',
          handling_instructions: '',
          tax_category: '',
          uom_id: '',
          description: '',
          status: '',
        })
        fetchCategoryLists()
      } else {
        showToast('', 'Something went wrong!.', 'top-right', 'error')
      }
    } catch (err: any) {
      if (err?.status === 422) {
        showToast('', err?.message, 'top-right', 'error')
        const apiErrors: Errors = err?.errors
        setErrors(apiErrors)
      } else {
        showToast('', 'Update Category failed!', 'top-right', 'error')
      }
      console.error(err)
    } finally {
      setIsLoading(false)
    }
  }

  return (
    <div className="space-y-10">
      <div className="flex justify-between items-center">
        <h1 className="text-xl font-semibold">Category Lists</h1>
        <Button
          variant="primary"
          size="sm"
          onClick={handleCreate}
        >
          Add Category
        </Button>
      </div>

      {isPageLoading ? (
        <div className="flex justify-center items-center space-x-2">
          <div className="w-5 h-5 border-2 border-t-blue-500 border-blue-200 rounded-full animate-spin"></div>
          <span className="text-sm text-gray-500">Loading...</span>
        </div>
      ) : (
        <AdvancedDataTable
          data={categoryLists || []}
          columns={columns}
          onView={handleView}
          onEdit={handleEdit}
          onDelete={handleDelete}
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
              Add New Category
            </h2>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <Label>
                  Category Code<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={formData.category_code}
                  onChange={handleChange('category_code')}
                  onKeyUp={() => handleRemove('category_code')}
                  error={!!errors.category_code}
                  hint={errors.category_code}
                />
              </div>
              <div>
                <Label>
                  Category Name<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={formData.category_name}
                  onChange={handleChange('category_name')}
                  onKeyUp={() => handleRemove('category_name')}
                  error={!!errors.category_name}
                  hint={errors.category_name}
                />
              </div>

              <div>
                <Label>Parent Category</Label>
                <SingleSelectInput
                  options={categoryLists}
                  valueKey="id"
                  value={formData.parent_id}
                  getOptionLabel={(item) => `${item.category_name}`}
                  onSingleSelectChange={(val) => {
                    setFormData((prev) => ({ ...prev, parent_id: val }))
                  }}
                />
              </div>
              <div>
                <Label>Hierarchy level</Label>
                <Input
                  type="text"
                  value={formData.hierarchy_level}
                  onChange={handleChange('hierarchy_level')}
                  onKeyUp={() => handleRemove('hierarchy_level')}
                  error={!!errors.hierarchy_level}
                  hint={errors.hierarchy_level}
                />
              </div>
              <div>
                <Label>
                  Applicable Industry<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={formData.applicable_industry}
                  onChange={handleChange('applicable_industry')}
                  onKeyUp={() => handleRemove('applicable_industry')}
                  error={!!errors.applicable_industry}
                  hint={errors.applicable_industry}
                />
              </div>
              <div>
                <Label>
                  Storage Conditions<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={formData.storage_condition}
                  onChange={handleChange('storage_condition')}
                  onKeyUp={() => handleRemove('storage_condition')}
                  error={!!errors.storage_condition}
                  hint={errors.storage_condition}
                />
              </div>
              <div>
                <Label>
                  Handling Instruction<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={formData.handling_instructions}
                  onChange={handleChange('handling_instructions')}
                  onKeyUp={() => handleRemove('handling_instructions')}
                  error={!!errors.handling_instructions}
                  hint={errors.handling_instructions}
                />
              </div>
              <div>
                <Label>
                  Tax Category<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={formData.tax_category}
                  onChange={handleChange('tax_category')}
                  onKeyUp={() => handleRemove('tax_category')}
                  error={!!errors.tax_category}
                  hint={errors.tax_category}
                />
              </div>
              <div>
                <Label>
                  Default UOM<span className="text-error-500">*</span>
                </Label>
                <SingleSelectInput
                  options={uomLists}
                  valueKey="id"
                  value={formData.uom_id}
                  getOptionLabel={(item) =>
                    `${item.uom_code} - ${item.uom_name}`
                  }
                  onSingleSelectChange={(val) => {
                    handleRemove('uom_id')
                    setFormData((prev) => ({ ...prev, uom_id: val }))
                  }}
                  error={!!errors.uom_id}
                  hint={errors.uom_id}
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
            <h2 className="text-xl font-semibold text-gray-800">Category</h2>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <Label>
                  Category Code<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={category.category_code}
                  disabled={true}
                />
              </div>
              <div>
                <Label>
                  Category Name<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={category.category_name}
                  disabled={true}
                />
              </div>

              <div>
                <Label>Parent Category</Label>
                <Input
                  type="text"
                  value={category?.parent?.category_name}
                  disabled={true}
                />
              </div>
              <div>
                <Label>Hierarchy level</Label>
                <Input
                  type="text"
                  value={category.hierarchy_level}
                  disabled={true}
                />
              </div>
              <div>
                <Label>
                  Applicable Industry<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={category.applicable_industry}
                  disabled={true}
                />
              </div>
              <div>
                <Label>
                  Storage Conditions<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={category.storage_condition}
                  disabled={true}
                />
              </div>
              <div>
                <Label>
                  Handling Instruction<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={category.handling_instructions}
                  disabled={true}
                />
              </div>
              <div>
                <Label>
                  Tax Category<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={category.tax_category}
                  disabled={true}
                />
              </div>
              <div>
                <Label>
                  Default UOM<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={category?.unit_of_measure.uom_name}
                  disabled={true}
                />
              </div>
              <div className="col-span-full">
                {/* <Label>Description</Label> */}
                <TextAreaInput
                  value={category.description}
                  onChange={(value) =>
                    handleChange('description')({
                      target: { value },
                    } as React.ChangeEvent<any>)
                  }
                  disabled={true}
                />
              </div>
              <div>
                <Label>Status</Label>
                <ToggleSwitchInput
                  label="Enable Active"
                  defaultChecked={!!category.status}
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
              Update Category
            </h2>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <Label>
                  Category Code<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={updateFormData.category_code}
                  onChange={handleChange('category_code')}
                  onKeyUp={() => handleRemove('category_code')}
                  error={!!errors.category_code}
                  hint={errors.category_code}
                />
              </div>
              <div>
                <Label>
                  Category Name<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={updateFormData.category_name}
                  onChange={handleChange('category_name')}
                  onKeyUp={() => handleRemove('category_name')}
                  error={!!errors.category_name}
                  hint={errors.category_name}
                />
              </div>

              <div>
                <Label>Parent Category</Label>
                <SingleSelectInput
                  options={categoryLists.filter(
                    (x: any) => x.id !== updateFormData?.id
                  )}
                  valueKey="id"
                  value={updateFormData.parent_id}
                  getOptionLabel={(item) => `${item.category_name}`}
                  onSingleSelectChange={(val) => {
                    setUpdateFormData((prev) => ({ ...prev, parent_id: val }))
                  }}
                />
              </div>
              <div>
                <Label>Hierarchy level</Label>
                <Input
                  type="text"
                  value={updateFormData.hierarchy_level}
                  onChange={handleChange('hierarchy_level')}
                  onKeyUp={() => handleRemove('hierarchy_level')}
                  error={!!errors.hierarchy_level}
                  hint={errors.hierarchy_level}
                />
              </div>
              <div>
                <Label>
                  Applicable Industry<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={updateFormData.applicable_industry}
                  onChange={handleChange('applicable_industry')}
                  onKeyUp={() => handleRemove('applicable_industry')}
                  error={!!errors.applicable_industry}
                  hint={errors.applicable_industry}
                />
              </div>
              <div>
                <Label>
                  Storage Conditions<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={updateFormData.storage_condition}
                  onChange={handleChange('storage_condition')}
                  onKeyUp={() => handleRemove('storage_condition')}
                  error={!!errors.storage_condition}
                  hint={errors.storage_condition}
                />
              </div>
              <div>
                <Label>
                  Handling Instruction<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={updateFormData.handling_instructions}
                  onChange={handleChange('handling_instructions')}
                  onKeyUp={() => handleRemove('handling_instructions')}
                  error={!!errors.handling_instructions}
                  hint={errors.handling_instructions}
                />
              </div>
              <div>
                <Label>
                  Tax Category<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={updateFormData.tax_category}
                  onChange={handleChange('tax_category')}
                  onKeyUp={() => handleRemove('tax_category')}
                  error={!!errors.tax_category}
                  hint={errors.tax_category}
                />
              </div>
              <div>
                <Label>
                  Default UOM<span className="text-error-500">*</span>
                </Label>
                <SingleSelectInput
                  options={uomLists}
                  valueKey="id"
                  value={updateFormData.uom_id}
                  getOptionLabel={(item) =>
                    `${item.uom_code} - ${item.uom_name}`
                  }
                  onSingleSelectChange={(val) => {
                    handleRemove('uom_id')
                    setUpdateFormData((prev) => ({ ...prev, uom_id: val }))
                  }}
                  error={!!errors.uom_id}
                  hint={errors.uom_id}
                />
              </div>
              <div className="col-span-full">
                {/* <Label>Description</Label> */}
                <TextAreaInput
                  value={updateFormData.description}
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

export default CategoryList
