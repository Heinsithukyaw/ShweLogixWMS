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

import Tabs from '@mui/material/Tabs'
import Tab from '@mui/material/Tab'
import Box from '@mui/material/Box'

import ProductInventoryList from './ProductInventoryList'
import ProductDimensionList from './ProductDimensionList'
import ProductCommercialList from './ProductCommercialList'
import ProductOtherList from './ProductOtherList'

import ProductViewModal from './ProductModal/ViewModal'
import ProductUpdateModal from './ProductModal/UpdateProductModal'

interface TabPanelProps {
  children?: React.ReactNode
  index: number
  value: number
}

function CustomTabPanel(props: TabPanelProps) {
  const { children, value, index, ...other } = props

  return (
    <div
      role="tabpanel"
      hidden={value !== index}
      id={`simple-tabpanel-${index}`}
      aria-labelledby={`simple-tab-${index}`}
      {...other}
    >
      {value === index && <Box sx={{ p: 3 }}>{children}</Box>}
    </div>
  )
}

function a11yProps(index: number) {
  return {
    id: `simple-tab-${index}`,
    'aria-controls': `simple-tabpanel-${index}`,
  }
}

interface RowData {
  product_code: string
  product_name: string
  category_id: string
  subcategory_id:string
  brand_id:string
  part_no:string
  status: number
  [key: string]: any
}

interface Errors {
  product_code?: string
  product_name?: string
  category_id?: string
  subcategory_id?: string
  brand_id?: string
  part_no?:string
  status?: number
}

const columns: TableColumn<RowData>[] = [
  {
    name: 'No.',
    selector: (_row: any, index: any) => index + 1,
    width: '70px',
    sortable: false,
  },
  {
    name: 'Code/SKU',
    selector: (row: RowData) => row.product_code,
    sortable: true,
  },
  {
    name: 'Name',
    selector: (row: RowData) => row.product_name,
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
    name: 'Brand',
    selector: (row: RowData) => row.brand?.brand_name || '-',
    sortable: true,
  },
  {
    name: 'Part No',
    selector: (row: RowData) => row.part_no,
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


function List() {
    const [value, setValue] = React.useState(0)
    const [isCreateOpen, setIsCreateOpen] = useState(false)
    const [isViewOpen, setIsViewOpen] = useState(false)
    const [isUpdateOpen, setIsUpdateOpen] = useState(false)
    const [errors, setErrors] = useState<Errors>({})

    const [updateProductData, setUpdateProductData] = useState({})

    const [productLists, setProductLists] = useState<any>([])
    const [productInventoryLists, setProductInventoryLists] = useState<any>([])
    const [productDimensionLists, setProductDimensionLists] = useState<any>([])
    const [productCommercialLists, setProductCommercialLists] = useState<any>([])
    const [productOtherLists, setProductOtherLists] = useState<any>([])

    const [product, setProduct] = useState<any>({})
    const [allCategoryLists, setAllCategoryLists] = useState<any>([])
    const [categoryLists, setCategoryLists] = useState<any>([])
    const [subcategoryLists, setSubCategoryLists] = useState<any>([])
    const [brandLists, setBrandLists] = useState<any>([])
    const [uomLists, setUomLists] = useState<any>([])
    const [allBrandLists, setAllBrandLists] = useState<any>({})
    const [supplierLists, setSupplierLists] = useState([])
    const [isPageLoading, setIsPageLoading] = useState(false)
    const [isLoading, setIsLoading] = useState(false)
    const [subDisabled, setSubDisabled] = useState(true)
    const [brandDisabled, setBrandDisabled] = useState(true)
    const { showToast } = provideUtility()

    const [formData, setFormData] = useState({
        product_code: '',
        product_name: '',
        category_id: '',
        subcategory_id: '',
        brand_id: '',
        part_no:'',
        description:'',
        status: 1
      })

    useEffect(() => {
        setIsPageLoading(true)
        fetchProductLists()
        fetchProductInventoryLists()
        fetchProductDimensionLists()
        fetchProductCommercialLists()
        fetchProductOtherLists()
        fetchBrandLists()
        fetchCategoryLists()
        fetchUomLists()
        fetchSupplierLists()
    }, [])

    const fetchProductLists = async () => {
      try {
        setIsPageLoading(true)
        const res = await http.fetchDataWithToken('products')
        console.log("use effect product lists")
        console.log(res.data?.data)
        setProductLists(res.data?.data || [])
      } catch (err) {
        setIsPageLoading(false)
        console.error('Failed to fetch Product lists:', err)
      } finally {
        setIsPageLoading(false)
      }
    }

    const fetchProductInventoryLists = async () => {
      try {
        setIsPageLoading(true)
        const res = await http.fetchDataWithToken('product-inventories')
        console.log('use effect product inventory lists')
        console.log(res.data?.data)
        setProductInventoryLists(res.data?.data || [])
      } catch (err) {
        setIsPageLoading(false)
        console.error('Failed to fetch Product Inventory lists:', err)
      } finally {
        setIsPageLoading(false)
      }
    }

    const fetchProductDimensionLists = async () => {
      try {
        setIsPageLoading(true)
        const res = await http.fetchDataWithToken('product-dimensions')
        console.log('use effect product dimension lists')
        console.log(res.data?.data)
        setProductDimensionLists(res.data?.data || [])
      } catch (err) {
        setIsPageLoading(false)
        console.error('Failed to fetch Product Dimension lists:', err)
      } finally {
        setIsPageLoading(false)
      }
    }

    const fetchProductCommercialLists = async () => {
      try {
        setIsPageLoading(true)
        const res = await http.fetchDataWithToken('product-commercials')
        console.log('use effect product commercial lists')
        console.log(res.data?.data)
        setProductCommercialLists(res.data?.data || [])
      } catch (err) {
        setIsPageLoading(false)
        console.error('Failed to fetch Product Dimension lists:', err)
      } finally {
        setIsPageLoading(false)
      }
    }

    const fetchProductOtherLists = async () => {
      try {
        setIsPageLoading(true)
        const res = await http.fetchDataWithToken('product-others')
        console.log('use effect product commercial lists')
        console.log(res.data?.data)
        setProductOtherLists(res.data?.data || [])
      } catch (err) {
        setIsPageLoading(false)
        console.error('Failed to fetch Product Other lists:', err)
      } finally {
        setIsPageLoading(false)
      }
    }

    const fetchBrandLists = async () => {
        try {
        setIsPageLoading(true)
        const res = await http.fetchDataWithToken('brands')
        const result = res.data?.data
        setAllBrandLists(result)
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

      const fetchSupplierLists = async () => {
        try {
          const res = await http.fetchDataWithToken('business-parties')
          console.log('supplier lists')
          console.log(res.data)
          setSupplierLists(res.data?.data || [])
        } catch (err) {
          console.error('Failed to fetch Supplier lists:', err)
        }
      }

    const handleRemove = (field: string) => {
      setErrors((prev) => ({
        ...prev,
        [field]: null,
      }))
    }

    const handleGetSub = (val: any) => {
        setSubDisabled(true)
        setBrandDisabled(true)

        formData.category_id = val
        formData.subcategory_id = ''
        formData.brand_id = ''

        const subcategories = allCategoryLists?.filter(
          (x: any) => x.parent_id == formData?.category_id
        )
        setSubCategoryLists(subcategories)
      setSubDisabled(false)
    }

    const handleGetBrand = (val: any) => {
      setBrandDisabled(true)
        formData.subcategory_id = val
        formData.brand_id = ''
        console.log(brandLists);
        const brands = allBrandLists?.filter(
          (x: any) => x.subcategory_id == formData?.subcategory_id
        )
        console.log(brands)
        setBrandLists(brands)
        setBrandDisabled(false)
    }

    const handleGet = (val: any) => {
        formData.brand_id = val
    }

    const handleToggle = (checked: boolean) => {
      const is_active = checked ? 1 : 0
        setFormData((prev: any) => ({
          ...prev,
          status: is_active,
        }))
    }

    const handleCloseModal = () => {
        setIsCreateOpen(false)
        setIsViewOpen(false)
        setIsUpdateOpen(false)
    }

    const handleTabChange = (event: React.SyntheticEvent, newValue: number) => {
      setValue(newValue)
    }

      const handleView = (row: any) => {
        setProduct(productLists?.find((x: any) => x.id === row.id))
        setIsViewOpen(true)
      }
    
      const handleCreate = () => {
        setErrors({
          product_code: '',
          product_name: '',
          category_id: '',
          subcategory_id: '',
          brand_id:'',
          part_no:''
        })
        setIsCreateOpen(true)
      }
    
      const handleEdit = (row: any) => {
        setIsUpdateOpen(true)
        const product_data = productLists.find((x: any) => x.id === row.id)
        setUpdateProductData(product_data)
      }
    
      const handleDelete = async (row:any) => {
        const confirmed = await showConfirm(
          'Are you sure?',
          'This action cannot be undone.'
        )
        if (!confirmed) return
    
        try {
          const response = await http.deleteDataWithToken(`/products/${row.id}`)
          console.log(response)
          if(response.status == true){
            Swal.fire({
              title: 'Deleted!',
              text: 'Product has been deleted.',
              icon: 'success',
            })
            fetchProductLists()
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
          setFormData((prev) => ({
            ...prev,
            [field]: value,
          }))
      }

      const handleSubmit = async () => {
        setIsLoading(true)
        setErrors({})
        try {
          const response = await http.postDataWithToken(
            '/products',
            formData
          )
          if(response.status === true){
              setIsCreateOpen(false)
              showToast('', 'Create Product successful', 'top-right', 'success')
              setFormData({
                product_code: '',
                product_name: '',
                category_id: '',
                subcategory_id: '',
                brand_id:'',
                part_no:'',
                description: '',
                status: 1,
              })
              fetchProductLists()
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
            showToast('', 'Create Product failed!', 'top-right', 'error')
          }
          console.error(err)
        } finally {
          setIsLoading(false)
        }
      }

  return (
    <>
      <div className="space-y-10">
        <div className="flex justify-between items-center">
          <h1 className="text-xl font-semibold">Product Lists</h1>
          <Button variant="primary" size="sm" onClick={handleCreate}>
            Add Product
          </Button>
        </div>

        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 md:gap-6 xl:grid-cols-4">
          <div className="rounded-md shadow-lg border border-gray-200 bg-white p-2">
            <div className="rounded-2xl border border-gray-200 bg-white p-5 ">
              <p className="text-gray-500 text-theme-sm dark:text-gray-400">
                Total Products
              </p>
              <div className="flex items-end justify-between mt-3">
                <div className="">
                  <h4 className="text-2xl font-bold text-gray-800 dark:text-white/90">
                    {productLists.length}
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
                Categories
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
                Low Stock
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
                Out Of Stocks
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

        <div className="">
          <Box sx={{ width: '100%' }}>
            <Box
              sx={{
                borderBottom: 1,
                borderColor: 'divider',
                fontWeight: 'bold',
              }}
            >
              <Tabs
                value={value}
                onChange={handleTabChange}
                aria-label="basic tabs example"
              >
                <Tab
                  sx={{ fontWeight: 'bold' }}
                  label="Basic Info"
                  {...a11yProps(0)}
                />
                <Tab
                  sx={{ fontWeight: 'bold' }}
                  label="Inventory"
                  {...a11yProps(1)}
                />
                <Tab
                  sx={{ fontWeight: 'bold' }}
                  label="Dimensions"
                  {...a11yProps(2)}
                />
                <Tab
                  sx={{ fontWeight: 'bold' }}
                  label="Commercial"
                  {...a11yProps(3)}
                />
                <Tab
                  sx={{ fontWeight: 'bold' }}
                  label="Other"
                  {...a11yProps(4)}
                />
              </Tabs>
            </Box>
            <CustomTabPanel value={value} index={0}>
              <div className="">
                {isPageLoading ? (
                  <div className="flex justify-center items-center space-x-2">
                    <div className="w-5 h-5 border-2 border-t-blue-500 border-blue-200 rounded-full animate-spin"></div>
                    <span className="text-sm text-gray-500">Loading...</span>
                  </div>
                ) : (
                  <AdvancedDataTable
                    data={productLists || []}
                    columns={columns}
                    onView={handleView}
                    onEdit={handleEdit}
                    onDelete={handleDelete}
                  />
                )}
              </div>
            </CustomTabPanel>
            <CustomTabPanel value={value} index={1}>
              <ProductInventoryList
                isPageLoading={isPageLoading}
                productInventory={productInventoryLists}
                productLists={productLists}
                uomLists={uomLists}
                isCreateOpen={isCreateOpen}
                handleCloseCreateModal={() => handleCloseModal}
                openViewModal={(row: any) => handleView(row)}
                handleReFetchProInvApi={() => fetchProductInventoryLists()}
              />
            </CustomTabPanel>
            <CustomTabPanel value={value} index={2}>
              <ProductDimensionList
                isPageLoading={isPageLoading}
                productDimension={productDimensionLists}
                productLists={productLists}
                isCreateOpen={isCreateOpen}
                handleCloseCreateModal={() => handleCloseModal}
                openViewModal={(row: any) => handleView(row)}
                handleReFetchProDimApi={() => fetchProductDimensionLists()}
              />
            </CustomTabPanel>
            <CustomTabPanel value={value} index={3}>
              <ProductCommercialList
                isPageLoading={isPageLoading}
                productCommercial={productCommercialLists}
                productLists={productLists}
                supplierLists={supplierLists}
                isCreateOpen={isCreateOpen}
                handleCloseCreateModal={handleCloseModal}
                openViewModal={(row: any) => handleView(row)}
                handleReFetchProComApi={() => fetchProductCommercialLists()}
              />
            </CustomTabPanel>
            <CustomTabPanel value={value} index={4}>
              <ProductOtherList
                isPageLoading={isPageLoading}
                productOther={productOtherLists}
                productLists={productLists}
                isCreateOpen={isCreateOpen}
                handleCloseCreateModal={handleCloseModal}
                openViewModal={(row: any) => handleView(row)}
                handleReFetchProOtherApi={() => fetchProductOtherLists()}
              />
            </CustomTabPanel>
          </Box>
        </div>
      </div>

      {isCreateOpen && (
        <BaseModal
          isOpen={isCreateOpen}
          onClose={handleCloseModal}
          isFullscreen={false}
        >
          <div className="space-y-6">
            <h2 className="text-xl font-semibold text-gray-800">
              Add New Product
            </h2>
            <div className="">
              <Box sx={{ width: '100%' }}>
                <Box
                  sx={{
                    borderBottom: 1,
                    borderColor: 'divider',
                    fontWeight: 'bold',
                  }}
                >
                  <Tabs
                    value={value}
                    onChange={handleTabChange}
                    aria-label="basic tabs example"
                  >
                    <Tab
                      sx={{ fontWeight: 'bold' }}
                      label="Basic Info"
                      {...a11yProps(0)}
                    />
                    <Tab
                      sx={{ fontWeight: 'bold' }}
                      label="Inventory"
                      {...a11yProps(1)}
                    />
                    <Tab
                      sx={{ fontWeight: 'bold' }}
                      label="Dimensions"
                      {...a11yProps(2)}
                    />
                    <Tab
                      sx={{ fontWeight: 'bold' }}
                      label="Commercial"
                      {...a11yProps(3)}
                    />
                    <Tab
                      sx={{ fontWeight: 'bold' }}
                      label="Other"
                      {...a11yProps(4)}
                    />
                  </Tabs>
                </Box>
                <CustomTabPanel value={value} index={0}>
                  <div className="">
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                      <div>
                        <Label>
                          Product Code<span className="text-error-500">*</span>
                        </Label>
                        <Input
                          type="text"
                          value={formData.product_code}
                          onChange={handleChange('product_code')}
                          onKeyUp={() => handleRemove('product_code')}
                          error={!!errors.product_code}
                          hint={errors.product_code}
                        />
                      </div>
                      <div>
                        <Label>
                          Product Name<span className="text-error-500">*</span>
                        </Label>
                        <Input
                          type="text"
                          value={formData.product_name}
                          onChange={handleChange('product_name')}
                          onKeyUp={() => handleRemove('product_name')}
                          error={!!errors.product_name}
                          hint={errors.product_name}
                        />
                      </div>

                      <div>
                        <Label>Category</Label>
                        <SingleSelectInput
                          options={categoryLists}
                          valueKey="id"
                          value={formData.category_id}
                          getOptionLabel={(item) =>
                            `${item.id} - ${item.category_name}`
                          }
                          onSingleSelectChange={(val) => {
                            handleRemove('category_id')
                            handleGetSub(val)
                            console.log(
                              'selected category id ' + formData.category_id
                            )
                          }}
                          error={!!errors.category_id}
                          hint={errors.category_id}
                        />
                      </div>

                      <div>
                        <Label>SubCategory</Label>
                        {subDisabled ? (
                          <Input type="text" value={''} disabled={true} />
                        ) : (
                          <SingleSelectInput
                            options={subcategoryLists}
                            valueKey="id"
                            value={formData.subcategory_id}
                            getOptionLabel={(item) =>
                              `${item.id} - ${item.category_name}`
                            }
                            onSingleSelectChange={(val) => {
                              handleRemove('subcategory_id')
                              handleGetBrand(val)
                              console.log(
                                'selected sub category id ' +
                                  formData.subcategory_id
                              )
                            }}
                            error={!!errors.subcategory_id}
                            hint={errors.subcategory_id}
                          />
                        )}
                      </div>

                      <div>
                        <Label>Brand</Label>
                        {brandDisabled ? (
                          <Input type="text" value={''} disabled={true} />
                        ) : (
                          <SingleSelectInput
                            options={brandLists}
                            valueKey="id"
                            value={formData.brand_id}
                            getOptionLabel={(item) =>
                              `${item.id} - ${item.brand_name}`
                            }
                            onSingleSelectChange={(val) => {
                              handleRemove('brand_id')
                              handleGet(val)
                              console.log(
                                'selected brand id ' + formData.brand_id
                              )
                            }}
                            error={!!errors.brand_id}
                            hint={errors.brand_id}
                          />
                        )}
                      </div>
                      <div>
                        <Label>
                          Part No<span className="text-error-500">*</span>
                        </Label>
                        <Input
                          type="text"
                          value={formData.part_no}
                          onChange={handleChange('part_no')}
                          onKeyUp={() => handleRemove('part_no')}
                          error={!!errors.part_no}
                          hint={errors.part_no}
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
                </CustomTabPanel>
                <CustomTabPanel value={value} index={1}>
                  {/* Product  Inventory */}
                  <ProductInventoryList
                    isPageLoading={isPageLoading}
                    productInventory={productInventoryLists}
                    productLists={productLists}
                    uomLists={uomLists}
                    isCreateOpen={isCreateOpen}
                    handleCloseCreateModal={handleCloseModal}
                    openViewModal={(row: any) => handleView(row)}
                    handleReFetchProInvApi={() => fetchProductInventoryLists()}
                  />
                </CustomTabPanel>
                <CustomTabPanel value={value} index={2}>
                  {/* Product  Dimension */}
                  <ProductDimensionList
                    isPageLoading={isPageLoading}
                    productDimension={productDimensionLists}
                    productLists={productLists}
                    isCreateOpen={isCreateOpen}
                    handleCloseCreateModal={handleCloseModal}
                    openViewModal={(row: any) => handleView(row)}
                    handleReFetchProDimApi={() => fetchProductDimensionLists()}
                  />
                </CustomTabPanel>
                <CustomTabPanel value={value} index={3}>
                  <ProductCommercialList
                    isPageLoading={isPageLoading}
                    productCommercial={productCommercialLists}
                    productLists={productLists}
                    supplierLists={supplierLists}
                    isCreateOpen={isCreateOpen}
                    handleCloseCreateModal={handleCloseModal}
                    openViewModal={(row: any) => handleView(row)}
                    handleReFetchProComApi={() => fetchProductCommercialLists()}
                  />
                </CustomTabPanel>
                <CustomTabPanel value={value} index={4}>
                  <ProductOtherList
                    isPageLoading={isPageLoading}
                    productOther={productOtherLists}
                    productLists={productLists}
                    isCreateOpen={isCreateOpen}
                    handleCloseCreateModal={handleCloseModal}
                    openViewModal={(row: any) => handleView(row)}
                    handleReFetchProOtherApi={() => fetchProductOtherLists()}
                  />
                </CustomTabPanel>
              </Box>
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
          <div className="">
            <Box sx={{ width: '100%' }}>
              <Box
                sx={{
                  borderBottom: 1,
                  borderColor: 'divider',
                  fontWeight: 'bold',
                }}
              >
                <Tabs
                  value={value}
                  onChange={handleTabChange}
                  aria-label="basic tabs example"
                >
                  <Tab
                    sx={{ fontWeight: 'bold' }}
                    label="Basic Info"
                    {...a11yProps(0)}
                  />
                  <Tab
                    sx={{ fontWeight: 'bold' }}
                    label="Inventory"
                    {...a11yProps(1)}
                  />
                  <Tab
                    sx={{ fontWeight: 'bold' }}
                    label="Dimensions"
                    {...a11yProps(2)}
                  />
                  <Tab
                    sx={{ fontWeight: 'bold' }}
                    label="Commercial"
                    {...a11yProps(3)}
                  />
                  <Tab
                    sx={{ fontWeight: 'bold' }}
                    label="Other"
                    {...a11yProps(4)}
                  />
                </Tabs>
              </Box>
              <CustomTabPanel value={value} index={0}>
                <ProductViewModal
                  isViewOpen={isViewOpen}
                  value={0}
                  data={product}
                  handleCloseModal={handleCloseModal}
                />
              </CustomTabPanel>
              <CustomTabPanel value={value} index={1}>
                {/* Product  Inventory */}
                <ProductInventoryList
                  isPageLoading={isPageLoading}
                  productInventory={productInventoryLists}
                  productLists={productLists}
                  uomLists={uomLists}
                  isCreateOpen={isCreateOpen}
                  handleCloseCreateModal={handleCloseModal}
                  openViewModal={(row: any) => handleView(row)}
                  handleReFetchProInvApi={() => fetchProductInventoryLists()}
                />
              </CustomTabPanel>
              <CustomTabPanel value={value} index={2}>
                Comming soon...
              </CustomTabPanel>
              <CustomTabPanel value={value} index={3}>
                Comming soon...
              </CustomTabPanel>
              <CustomTabPanel value={value} index={4}>
                Comming soon...
              </CustomTabPanel>
            </Box>
          </div>
        </BaseModal>
      )}

      {isUpdateOpen && (
        <ProductUpdateModal
          value={0}
          data={updateProductData}
          categoryLists={categoryLists}
          allCategoryLists={allCategoryLists}
          allBrandLists={allBrandLists}
          isUpdateOpen={isUpdateOpen}
          handleCloseModal={handleCloseModal}
          handleReFetchProApi={() => fetchProductLists()}
        />
      )}
    </>
  )
}

export default List