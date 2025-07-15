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
  cost_code: string
  cost_name: string
  category_id: any
  subcategory_id: any
  cost_type:string
  creation_date:string
  created_by:string
  last_modified_date:string
  last_modified_by:string
  status: number
  [key: string]: any
}

interface Errors {
  cost_code?: string
  cost_name?: string
  category_id?:string
  subcategory_id?:string
  cost_type?:string
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
    selector: (row: RowData) => row.cost_code,
    sortable: true,
  },
  {
    name: 'Name',
    selector: (row: RowData) => row.cost_name,
    sortable: true,
  },
  {
    name: 'Category',
    selector: (row: RowData) => row.category_code || '-',
    sortable: true,
  },
  {
    name: 'SubCategory',
    selector: (row: RowData) => row.subcategory_code || '-',
    sortable: true,
  },
  {
    name: 'Cost Type',
    selector: (row: RowData) => row.cost_type || '-',
    sortable: true,
  },
  {
    name: 'Creation Date',
    selector: (row: RowData) => row.creation_date || '-',
    sortable: true,
  },
  {
    name: 'Created By',
    selector: (row: RowData) => row.parent_created_by || '-',
    sortable: true,
  },
  {
    name: 'Last Modified Date',
    selector: (row: RowData) => row.last_modified_date || '-',
    sortable: true,
  },
  {
    name: 'Last Modified By',
    selector: (row: RowData) => row.last_modified_by || '-',
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
  const [costTypeLists, setCostTypeLists] = useState<any>([])

  const [allCategoryLists, setAllCategoryLists] = useState<any>([])
  const [categoryLists, setCategoryLists] = useState<any>([])
  const [subcategoryLists, setSubcategoryLists] = useState<any>([])
//   const [subcategoryLists, setSubcategoryLists] = useState<any>([])

  const [costType, setCostType] = useState<any>({})
  const [isPageLoading, setIsPageLoading] = useState(false)
  const [isLoading, setIsLoading] = useState(false)
  const { showToast } = provideUtility()

  const costTypeData = [
    { id: 1, value: 'Fixed' },
    { id: 2, value: 'Variable' },
  ]

  const [formData, setFormData] = useState({
    cost_code: '',
    cost_name: '',
    category_id: '',
    subcategory_id: '',
    cost_type: '',
    created_by: '',
    last_modified_by: '',
    status: 1,
  })

  const [updateFormData, setUpdateFormData] = useState({
    id: '',
    cost_code: '',
    cost_name: '',
    category_id: '',
    subcategory_id: '',
    cost_type:'',
    created_by:'',
    last_modified_by:'',
    status: '',
  })

  useEffect(() => {
    setIsPageLoading(true)
    fetchFinancialCostTypeLists()
    fetchFinancialCategoryLists()
  }, [])

  const fetchFinancialCostTypeLists = async () => {
    try {
      setIsPageLoading(true)
      const res = await http.fetchDataWithToken('cost-types')
      console.log(res)
      setCostTypeLists(res.data?.data || [])
    } catch (err) {
      setIsPageLoading(false)
      console.error('Failed to fetch Financial Cost Type lists:', err)
    } finally {
      setIsPageLoading(false)
    }
  }

  const fetchFinancialCategoryLists = async () => {
    try {
      setIsPageLoading(true)
      const res = await http.fetchDataWithToken('financial-categories')
      console.log(res)
      setAllCategoryLists(res.data?.data)
      setCategoryLists(res.data?.data?.filter((x:any) => x.parent_id == null) || [])
    } catch (err) {
      setIsPageLoading(false)
      console.error('Failed to fetch Financial Category lists:', err)
    } finally {
      setIsPageLoading(false)
    }
  }

  const handleGetSub = (val:any) =>{
    
    const sub = allCategoryLists?.filter((x:any) => x?.parent_id === parseInt(val))
    setSubcategoryLists(sub)
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
    setCostType(costTypeLists?.find((x:any) => x.id === row.id))
    setIsViewOpen(true)
  }

  const handleCreate = () => {
    setErrors({
        cost_code: '',
        cost_name: '',
        category_id:'',
        subcategory_id:'',
        cost_type:'',
        status:''
    })
    setIsCreateOpen(true)
  }

  const handleEdit = (row: any) => {
    setIsUpdateOpen(true)
    setErrors({
      cost_code: '',
      cost_name: '',
      category_id: '',
      subcategory_id: '',
      cost_type: '',
      status: '',
    })
    const cost_type_data = costTypeLists.find((x: any) => x.id === row.id)
    if (cost_type_data) {
      handleGetSub(cost_type_data.category_id)
      setUpdateFormData({
        id: cost_type_data.id,
        cost_code: cost_type_data.cost_code,
        cost_name: cost_type_data.cost_name,
        category_id: cost_type_data.category_id,
        subcategory_id: cost_type_data.subcategory_id,
        cost_type:cost_type_data.cost_type,
        created_by:cost_type_data.created_by,
        last_modified_by:cost_type_data.last_modified_by,
        status: cost_type_data.status
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
      const response = await http.deleteDataWithToken(`/cost-types/${row.id}`)
      console.log(response)
      if(response.status == true){
        Swal.fire({
          title: 'Deleted!',
          text: 'Cost Type has been deleted.',
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
        '/cost-types',
        formData
      )
      if(response.status === true){
          setIsCreateOpen(false)
          showToast('', 'Create Cost Type successful', 'top-right', 'success')
          setFormData({
            cost_code: '',
            cost_name: '',
            category_id: '',
            subcategory_id: '',
            cost_type: '',
            created_by: '',
            last_modified_by: '',
            status: 1,
          })
          fetchFinancialCostTypeLists()
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
        showToast('', 'Create Cost Type Lists failed!', 'top-right', 'error')
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
        `/cost-types/${updateFormData.id}`,
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
          cost_code: '',
          cost_name: '',
          category_id: '',
          subcategory_id: '',
          cost_type: '',
          created_by: '',
          last_modified_by: '',
          status: '',
        })
        fetchFinancialCostTypeLists()
      } else {
        showToast('', 'Something went wrong!.', 'top-right', 'error')
      }
    } catch (err: any) {
      if (err?.status === 422) {
        showToast('', err?.message, 'top-right', 'error')
        const apiErrors: Errors = err?.errors
        setErrors(apiErrors)
      } else {
        showToast('', 'Update Cost Type failed!', 'top-right', 'error')
      }
      console.error(err)
    } finally {
      setIsLoading(false)
    }
  }

  const [filterText, setFilterText] = React.useState('')
    const filteredData = useMemo(() => {
        if (!filterText) return costTypeLists

        return costTypeLists.filter((item: any) =>
        Object.values(item).some(
            (val) =>
            val && val.toString().toLowerCase().includes(filterText.toLowerCase())
        )
        )
    }, [filterText, costTypeLists])

  return (
    <div className="space-y-10">
      <div className="flex justify-between items-center">
        <h1 className="text-xl font-semibold">Financial Cost Lists</h1>
        <Button variant="primary" size="sm" onClick={handleCreate}>
          Add Cost
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
              Add New Cost
            </h2>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <Label>
                  Cost Code<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={formData.cost_code}
                  onChange={handleChange('cost_code')}
                  onKeyUp={() => handleRemove('cost_code')}
                  error={!!errors.cost_code}
                  hint={errors.cost_code}
                />
              </div>
              <div>
                <Label>
                  Cost Name<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={formData.cost_name}
                  onChange={handleChange('cost_name')}
                  onKeyUp={() => handleRemove('cost_name')}
                  error={!!errors.cost_name}
                  hint={errors.cost_name}
                />
              </div>
              <div>
                <Label>Cost Type</Label>
                <SingleSelectInput
                  options={costTypeData}
                  valueKey="value"
                  value={formData.cost_type}
                  getOptionLabel={(item) => `${item.value}`}
                  onSingleSelectChange={(val) => {
                    handleRemove('cost_type')
                    setFormData((prev) => ({
                      ...prev,
                      cost_type: val,
                    }))
                  }}
                  error={!!errors.cost_type}
                  hint={errors.cost_type}
                />
              </div>
              <div>
                <Label>Category</Label>
                <SingleSelectInput
                  options={categoryLists}
                  valueKey="id"
                  value={formData.category_id}
                  getOptionLabel={(item) => `${item.category_code}`}
                  onSingleSelectChange={(val) => {
                    handleRemove('category_id')
                    handleGetSub(val)
                    setFormData((prev) => ({
                      ...prev,
                      category_id: val,
                    }))
                  }}
                  error={!!errors.category_id}
                  hint={errors.category_id}
                />
              </div>
              <div>
                <Label>Subcategory</Label>
                <SingleSelectInput
                  options={subcategoryLists}
                  valueKey="id"
                  value={formData.subcategory_id}
                  getOptionLabel={(item) => `${item.category_code}`}
                  onSingleSelectChange={(val) => {
                    handleRemove('subcategory_id')
                    setFormData((prev) => ({
                      ...prev,
                      subcategory_id: val,
                    }))
                  }}
                  error={!!errors.subcategory_id}
                  hint={errors.subcategory_id}
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
            <h2 className="text-xl font-semibold text-gray-800">Cost</h2>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <Label>
                  Cost Code<span className="text-error-500">*</span>
                </Label>
                <Input type="text" value={costType.cost_code} disabled={true} />
              </div>
              <div>
                <Label>
                  Cost Name<span className="text-error-500">*</span>
                </Label>
                <Input type="text" value={costType.cost_name} disabled={true} />
              </div>
              <div>
                <Label>Cost Type</Label>
                <Input type="text" value={costType.cost_type} disabled={true} />
              </div>
              <div>
                <Label>Category</Label>
                <Input
                  type="text"
                  value={costType.category_code}
                  disabled={true}
                />
              </div>
              <div>
                <Label>Subcategory</Label>
                <Input
                  type="text"
                  value={costType.subcategory_code}
                  disabled={true}
                />
              </div>
              <div>
                <Label>Status</Label>
                <ToggleSwitchInput
                  label="Enable Active"
                  defaultChecked={!!costType.status}
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
            <h2 className="text-xl font-semibold text-gray-800">Edit Cost</h2>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <Label>
                  Cost Code<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={updateFormData.cost_code}
                  onChange={handleChange('cost_code')}
                  onKeyUp={() => handleRemove('cost_code')}
                  error={!!errors.cost_code}
                  hint={errors.cost_code}
                />
              </div>
              <div>
                <Label>
                  Cost Name<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={updateFormData.cost_name}
                  onChange={handleChange('cost_name')}
                  onKeyUp={() => handleRemove('cost_name')}
                  error={!!errors.cost_name}
                  hint={errors.cost_name}
                />
              </div>
              <div>
                <Label>Cost Type</Label>
                <SingleSelectInput
                  options={costTypeData}
                  valueKey="value"
                  value={updateFormData.cost_type}
                  getOptionLabel={(item) => `${item.value}`}
                  onSingleSelectChange={(val) => {
                    handleRemove('cost_type')
                    setUpdateFormData((prev) => ({
                      ...prev,
                      cost_type: val,
                    }))
                  }}
                  error={!!errors.cost_type}
                  hint={errors.cost_type}
                />
              </div>
              <div>
                <Label>Category</Label>
                <SingleSelectInput
                  options={categoryLists}
                  valueKey="id"
                  value={updateFormData.category_id}
                  getOptionLabel={(item) => `${item.category_code}`}
                  onSingleSelectChange={(val) => {
                    handleRemove('category_id')
                    handleGetSub(val)
                    setUpdateFormData((prev) => ({
                      ...prev,
                      category_id: val,
                    }))
                  }}
                  error={!!errors.category_id}
                  hint={errors.category_id}
                />
              </div>
              <div>
                <Label>Subcategory</Label>
                <SingleSelectInput
                  options={subcategoryLists}
                  valueKey="id"
                  value={updateFormData.subcategory_id}
                  getOptionLabel={(item) => `${item.category_code}`}
                  onSingleSelectChange={(val) => {
                    handleRemove('subcategory_id')
                    setUpdateFormData((prev) => ({
                      ...prev,
                      subcategory_id: val,
                    }))
                  }}
                  error={!!errors.subcategory_id}
                  hint={errors.subcategory_id}
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


