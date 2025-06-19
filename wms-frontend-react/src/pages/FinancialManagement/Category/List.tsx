import React, { useState, useEffect, useMemo} from 'react'
import http from '../../../lib/http'
import provideUtility from '../../../utils/toast'
import Spinner from '../../../components/ui/loading/spinner'
import AdvancedDataTable from '../../../components/ui/dataTable'
import Button from '../../../components/ui/button/Button'
import BaseModal from '../../../components/ui/modal'
import Label from '../../../components/form/Label'
import Input from '../../../components/form/input/InputField'
import SingleSelectInput from '../../../components/form/form-elements/SelectInputs'
import ToggleSwitchInput from '../../../components/form/form-elements/ToggleSwitch'
import { TableColumn } from 'react-data-table-component'
import { showConfirm } from '../../../utils/alert'
import Swal from 'sweetalert2'

interface RowData {
  category_code: string
  category_name: string
  parent_id:any
  status: number
  [key: string]: any
}

interface Errors {
  category_code?: string
  category_name?: string
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
    selector: (row: RowData) => row.parent_category_code || '-',
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

const List: React.FC = () => {
  const [isCreateOpen, setIsCreateOpen] = useState(false)
  const [isViewOpen, setIsViewOpen] = useState(false)
  const [isUpdateOpen, setIsUpdateOpen] = useState(false)
  const [errors, setErrors] = useState<Errors>({})
  const [allCategoryLists, setAllCategoryLists] = useState<any>([])
  const [categoryLists, setCategoryLists] = useState<any>([])
  const [category, setCategory] = useState<any>({})
  const [isPageLoading, setIsPageLoading] = useState(false)
  const [isLoading, setIsLoading] = useState(false)
  const { showToast } = provideUtility()

  const [formData, setFormData] = useState({
    category_code: '',
    category_name: '',
    parent_id:'',
    status: 1
  })

  const [updateFormData, setUpdateFormData] = useState({
    id: '',
    category_code: '',
    category_name: '',
    parent_id:'',
    status: '',
  })

  useEffect(() => {
    setIsPageLoading(true)
    fetchFinancialCategoryLists()
  }, [])

  const fetchFinancialCategoryLists = async () => {
    try {
      setIsPageLoading(true)
      const res = await http.fetchDataWithToken('financial-categories')
      console.log(res)
      setAllCategoryLists(res.data?.data || [])
      setCategoryLists(allCategoryLists.filter((x:any) => x.parent == null))
    } catch (err) {
      setIsPageLoading(false)
      console.error('Failed to fetch Financial Category lists:', err)
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
    setCategory(categoryLists?.find((x:any) => x.id === row.id))
    setIsViewOpen(true)
  }

  const handleCreate = () => {
    setErrors({
      category_code: '',
      category_name: '',
      status:''
    })
    setIsCreateOpen(true)
  }

  const handleEdit = (row: any) => {
    setIsUpdateOpen(true)
    setErrors({
      category_code: '',
      category_name: '',
      status: '',
    })
    const category_data = allCategoryLists.find((x: any) => x.id === row.id)
    if (category_data) {
      setUpdateFormData({
        id: category_data.id,
        category_code: category_data.category_code,
        category_name: category_data.category_name,
        parent_id: category_data.parent_id,
        status: category_data.status,
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
      const response = await http.deleteDataWithToken(`/financial-categories/${row.id}`)
      console.log(response)
      if(response.status == true){
        Swal.fire({
          title: 'Deleted!',
          text: 'Financial Category has been deleted.',
          icon: 'success',
        })
        fetchFinancialCategoryLists()
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
        '/financial-categories',
        formData
      )
      if(response.status === true){
          setIsCreateOpen(false)
          showToast('', 'Create Financial Category successful', 'top-right', 'success')
          setFormData({
            category_code: '',
            category_name: '',
            parent_id:'',
            status: 1,
          })
          fetchFinancialCategoryLists()
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
        showToast('', 'Create Financial Category failed!', 'top-right', 'error')
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
        `/financial-categories/${updateFormData.id}`,
        updateFormData
      )
      if (response.status === true) {
        setIsUpdateOpen(false)
        showToast(
          '',
          'Update Financial Category successful',
          'top-right',
          'success'
        )
        setUpdateFormData({
          id: '',
          category_code: '',
          category_name: '',
          parent_id:'',
          status: '',
        })
        fetchFinancialCategoryLists()
      } else {
        showToast('', 'Something went wrong!.', 'top-right', 'error')
      }
    } catch (err: any) {
      if (err?.status === 422) {
        showToast('', err?.message, 'top-right', 'error')
        const apiErrors: Errors = err?.errors
        setErrors(apiErrors)
      } else {
        showToast('', 'Update Financial Category failed!', 'top-right', 'error')
      }
      console.error(err)
    } finally {
      setIsLoading(false)
    }
  }

  const [filterText, setFilterText] = React.useState('')
    const filteredData = useMemo(() => {
        if (!filterText) return allCategoryLists

        return allCategoryLists.filter((item: any) =>
        Object.values(item).some(
            (val) =>
            val && val.toString().toLowerCase().includes(filterText.toLowerCase())
        )
        )
    }, [filterText, allCategoryLists])

  return (
    <div className="space-y-10">
      <div className="flex justify-between items-center">
        <h1 className="text-xl font-semibold">Financial Category Lists</h1>
        <Button variant="primary" size="sm" onClick={handleCreate}>
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
                placeholder="Search Financial Categories…"
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
                  getOptionLabel={(item) => `${item.category_code}`}
                  onSingleSelectChange={(val) => {
                    handleRemove('parent_id')
                    setFormData((prev) => ({
                      ...prev,
                      parent_id: val,
                    }))
                  }}
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
                  value={category.parent_category_code}
                  disabled={true}
                />
              </div>
              <div>
                <Label>Status</Label>
                <ToggleSwitchInput
                  label="Enable Active"
                  defaultChecked={!!formData.status}
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
              Edit Category
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
                  options={categoryLists.filter((x:any) => x.id !== updateFormData.id)}
                  valueKey="id"
                  value={updateFormData.parent_id}
                  getOptionLabel={(item) => `${item.category_code}`}
                  onSingleSelectChange={(val) => {
                    handleRemove('parent_id')
                    setUpdateFormData((prev) => ({
                      ...prev,
                      parent_id: val,
                    }))
                  }}
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


