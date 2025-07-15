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
  brand_code: string
  brand_name: string
  category_id: string
  subcategory_id: string
  status: number
  [key: string]: any
}

interface Errors {
  brand_code?: string
  brand_name?: string
  category_id?: any
  subcategory_id?: string
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
    selector: (row: RowData) => row.brand_code,
    sortable: true,
  },
  {
    name: 'Name',
    selector: (row: RowData) => row.brand_name,
    sortable: true,
  },
  {
    name: 'Category',
    selector: (row: RowData) => row.category?.category_name || '-',
    sortable: true,
  },
  {
    name: 'Sub Category',
    selector: (row: RowData) => row.subcategory?.category_name || '-',
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
  const [allCategoryLists, setAllCategoryLists] = useState<any>([])
  const [categoryLists, setCategoryLists] = useState<any>([])
  const [subcategoryLists, setSubCategoryLists] = useState<any>([])
  const [brandLists, setBrandLists] = useState<any>([])
  const [brand, setBrand] = useState<any>({})
  const [isPageLoading, setIsPageLoading] = useState(false)
  const [isLoading, setIsLoading] = useState(false)
  const [subDisabled, setSubDisabled] = useState(true)
  const { showToast } = provideUtility()

  const [formData, setFormData] = useState({
    brand_code: '',
    brand_name: '',
    category_id: '',
    subcategory_id: '',
    description: '',
    status: 1,
  })

  const [updateFormData, setUpdateFormData] = useState({
    id: '',
    brand_code: '',
    brand_name: '',
    category_id: '',
    subcategory_id: '',
    description: '',
    status: '',
  })

  useEffect(() => {
    setIsPageLoading(true)
    fetchBrandLists()
    fetchCategoryLists()
  }, [])

  const fetchBrandLists = async () => {
    try {
      setIsPageLoading(true)
      const res = await http.fetchDataWithToken('brands')
      console.log(res)
      setBrandLists(res.data?.data || [])
    } catch (err) {
      setIsPageLoading(false)
      console.error('Failed to fetch Brand lists:', err)
    } finally {
      setIsPageLoading(false)
    }
  }

  const fetchCategoryLists = async () => {
    try {
      setIsPageLoading(true)
      const res = await http.fetchDataWithToken('categories')
      console.log(res.data)
      const result = res.data?.data
      setAllCategoryLists(result)
      const categories = result?.filter((c:any) => c.parent_id == null)
      setCategoryLists(categories || [])
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

  const handleGetSub = (val:any) => {
    setSubDisabled(false)
    if(isCreateOpen){
        formData.category_id = val
        formData.subcategory_id = ''
        const subcategories = allCategoryLists?.filter(
        (x: any) => x.parent_id == formData?.category_id
        )
         setSubCategoryLists(subcategories)
    }else{
        updateFormData.category_id = val
        updateFormData.subcategory_id = ''
        const subcategories = allCategoryLists?.filter(
        (x: any) => x.parent_id == updateFormData?.category_id
        )
         setSubCategoryLists(subcategories)
    }
    setSubDisabled(false)
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
    setBrand(brandLists?.find((x:any) => x.id === row.id))
    setIsViewOpen(true)
  }

  const handleCreate = () => {
    setErrors({
      brand_code: '',
      brand_name: '',
      category_id: '',
      subcategory_id: '',
    })
    setIsCreateOpen(true)
  }

  const handleEdit = (row: any) => {
    setIsUpdateOpen(true)
        setSubDisabled(true)
    setErrors({
      brand_code: '',
      brand_name: '',
      category_id: '',
      subcategory_id: '',
    })
    const brand_data = brandLists.find((x: any) => x.id === row.id)
    
    console.log(brand_data)
  
    const subcategories = allCategoryLists?.filter(
      (x: any) => x.id == brand_data?.subcategory_id
    )
  
    setSubCategoryLists(subcategories)
    setSubDisabled(false)
    if (brand_data) {
      setUpdateFormData({
        id: brand_data.id || '',
        brand_code: brand_data.brand_code || '',
        brand_name: brand_data.brand_name || '',
        category_id: brand_data.category?.id || '',
        subcategory_id: brand_data.subcategory?.id || '',
        description: brand_data.description || '',
        status: brand_data.status || '',
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
      const response = await http.deleteDataWithToken(`/brands/${row.id}`)
      console.log(response)
      if(response.status == true){
        Swal.fire({
          title: 'Deleted!',
          text: 'Brand has been deleted.',
          icon: 'success',
        })
        fetchBrandLists()
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
        '/brands',
        formData
      )
      if(response.status === true){
          setIsCreateOpen(false)
          showToast('', 'Create Brand successful', 'top-right', 'success')
          setFormData({
            brand_code: '',
            brand_name: '',
            category_id: '',
            subcategory_id: '',
            description: '',
            status: 1,
          })
          fetchBrandLists()
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
        showToast('', 'Create Brand failed!', 'top-right', 'error')
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
        `/brands/${updateFormData.id}`,
        updateFormData
      )
      if (response.status === true) {
        setIsUpdateOpen(false)
        showToast(
          '',
          'Update Brand successful',
          'top-right',
          'success'
        )
        setUpdateFormData({
          id: '',
          brand_code: '',
          brand_name: '',
          category_id: '',
          subcategory_id: '',
          description: '',
          status: '',
        })
        fetchBrandLists()
      } else {
        showToast('', 'Something went wrong!.', 'top-right', 'error')
      }
    } catch (err: any) {
      if (err?.status === 422) {
        showToast('', err?.message, 'top-right', 'error')
        const apiErrors: Errors = err?.errors
        setErrors(apiErrors)
      } else {
        showToast('', 'Update Brand failed!', 'top-right', 'error')
      }
      console.error(err)
    } finally {
      setIsLoading(false)
    }
  }

  return (
    <div className="space-y-10">
      <div className="flex justify-between items-center">
        <h1 className="text-xl font-semibold">Brand Lists</h1>
        <Button variant="primary" size="sm" onClick={handleCreate}>
          Add Brand
        </Button>
      </div>

      {isPageLoading ? (
        <div className="flex justify-center items-center space-x-2">
          <div className="w-5 h-5 border-2 border-t-blue-500 border-blue-200 rounded-full animate-spin"></div>
          <span className="text-sm text-gray-500">Loading...</span>
        </div>
      ) : (
        <AdvancedDataTable
          data={brandLists || []}
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
              Add New Brand
            </h2>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <Label>
                  Brand Code<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={formData.brand_code}
                  onChange={handleChange('brand_code')}
                  onKeyUp={() => handleRemove('brand_code')}
                  error={!!errors.brand_code}
                  hint={errors.brand_code}
                />
              </div>
              <div>
                <Label>
                  Brand Name<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={formData.brand_name}
                  onChange={handleChange('brand_name')}
                  onKeyUp={() => handleRemove('brand_name')}
                  error={!!errors.brand_name}
                  hint={errors.brand_name}
                />
              </div>

              <div>
                <Label>Category</Label>
                <SingleSelectInput
                  options={categoryLists}
                  valueKey="id"
                  value={formData.category_id}
                  getOptionLabel={(item) => `${item.category_name}`}
                  onSingleSelectChange={(val) => {
                    console.log("value - "+val)
                    handleRemove('category_id')
                    handleGetSub(val)
                    // setFormData((prev) => ({ ...prev, category_id: val }))
                    //  console.log('form data value - ' + formData.category_id)
                  }}
                  error={!!errors.category_id}
                  hint={errors.category_id}
                />
              </div>

              <div>
                <Label>SubCategory</Label>
                {subDisabled ? (
                    <Input
                      type="text"
                      value={''}
                      disabled={true}
                    />
                ) : (
                  <SingleSelectInput
                    options={subcategoryLists}
                    valueKey="id"
                    value={formData.subcategory_id}
                    getOptionLabel={(item) => `${item.category_name}`}
                    onSingleSelectChange={(val) => {
                      handleRemove('subcategory_id')
                      setFormData((prev) => ({ ...prev, subcategory_id: val }))
                    }}
                    error={!!errors.subcategory_id}
                    hint={errors.subcategory_id}
                  />
                )}
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
              Brand
            </h2>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <Label>Brand Code</Label>
                <Input type="text" value={brand.brand_code} disabled={true} />
              </div>
              <div>
                <Label>Brand Name</Label>
                <Input type="text" value={brand.brand_name} disabled={true} />
              </div>

              <div>
                <Label>Category</Label>
                <Input
                  type="text"
                  value={brand.category?.category_name}
                  disabled={true}
                />
              </div>

              <div>
                <Label>SubCategory</Label>
                <Input
                  type="text"
                  value={brand.subcategory?.category_name}
                  disabled={true}
                />
              </div>
              <div className="col-span-full">
                {/* <Label>Description</Label> */}
                <TextAreaInput
                  value={brand.description}
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
                  defaultChecked={!!brand.status}
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
              Update Brand
            </h2>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <Label>
                  Brand Code<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={updateFormData.brand_code}
                  onChange={handleChange('brand_code')}
                  onKeyUp={() => handleRemove('brand_code')}
                  error={!!errors.brand_code}
                  hint={errors.brand_code}
                />
              </div>
              <div>
                <Label>
                  Brand Name<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={updateFormData.brand_name}
                  onChange={handleChange('brand_name')}
                  onKeyUp={() => handleRemove('brand_name')}
                  error={!!errors.brand_name}
                  hint={errors.brand_name}
                />
              </div>

              <div>
                <Label>Category</Label>
                <SingleSelectInput
                  options={categoryLists}
                  valueKey="id"
                  value={updateFormData.category_id}
                  getOptionLabel={(item) => `${item.category_name}`}
                  onSingleSelectChange={(val) => {
                    handleRemove('category_id')
                    handleGetSub(val)
                  }}
                  error={!!errors.category_id}
                  hint={errors.category_id}
                />
              </div>

              <div>
                <Label>SubCategory</Label>
                <SingleSelectInput
                  options={subcategoryLists}
                  valueKey="id"
                  value={updateFormData.subcategory_id}
                  getOptionLabel={(item) => `${item.category_name}`}
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
