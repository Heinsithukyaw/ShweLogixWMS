import React,{useState, useEffect} from 'react'
import http from '../../../../lib/http'
import Spinner from '../../../../components/ui/loading/spinner'
import provideUtility from '../../../../utils/toast'
import BaseModal from '../../../../components/ui/modal'
import Label from '../../../../components/form/Label'
import Input from '../../../../components/form/input/InputField'
import TextAreaInput from '../../../../components/form/form-elements/TextAreaInput'
import ToggleSwitchInput from '../../../../components/form/form-elements/ToggleSwitch'
import SingleSelectInput from '../../../../components/form/form-elements/SelectInputs'
import Button from '../../../../components/ui/button/Button'

interface Props {
  data: any
  categoryLists: any
  allCategoryLists: any
  allBrandLists: any
  value: 0 | 1 | 2 | 3 | 4
  isUpdateOpen: true | false
  handleCloseModal: () => void
  handleReFetchProApi: () => void
}

interface Errors {
  product_code?: string
  product_name?: string
  category_id?: string
  subcategory_id?: string
  brand_id?: string
  part_no?: string
  status?: number
}

const ProductUpdateModal: React.FC<Props> = ({ value, data, categoryLists, allCategoryLists, allBrandLists, isUpdateOpen, handleCloseModal, handleReFetchProApi }) => {

    const { showToast } = provideUtility()

    const [isLoading, setIsLoading] = useState(false)
    const [subDisabled, setSubDisabled] = useState(true)
    const [brandDisabled, setBrandDisabled] = useState(true)
    const [errors, setErrors] = useState<Errors>({})

    const [subcategoryLists, setSubCategoryLists] = useState<any>([])
    const [brandLists, setBrandLists] = useState<any>([])


    const [updateFormData, setUpdateFormData] = useState({
        id: '',
        product_code: '',
        product_name: '',
        category_id: '',
        subcategory_id: '',
        brand_id: '',
        part_no:'',
        description:'',
        status: '',
    })

    // function

    useEffect(() => {
        if(value == 0) {
          setErrors({
            product_code: '',
            product_name: '',
            category_id: '',
            subcategory_id: '',
            part_no: '',
          })

          if (data) {
            console.log('Status => '+data.status)
            setSubDisabled(false)
            setBrandDisabled(false)
            setSubCategoryLists(allCategoryLists.filter((x:any) => x.parent_id))
            setBrandLists(allBrandLists)
            setUpdateFormData({
              id: data.id || '',
              product_code: data.product_code || '',
              product_name: data.product_name || '',
              category_id: data.category?.id || '',
              subcategory_id: data.subcategory?.id || '',
              brand_id: data.subcategory?.id || '',
              part_no: data.part_no,
              description: data.description || '',
              status: data.status || 1,
            })
          }
        }
        
    },[data])

    const handleRemove = (field: string) => {
      setErrors((prev) => ({
        ...prev,
        [field]: null,
      }))
    }

    const handleGetSub = (val: any) => {
      setSubDisabled(true)
      setBrandDisabled(true)
        updateFormData.category_id = val
        updateFormData.subcategory_id = ''
        updateFormData.brand_id = ''
        const subcategories = allCategoryLists?.filter(
            (x: any) => x.parent_id == updateFormData?.category_id
        )
        setSubCategoryLists(subcategories)
      setSubDisabled(false)
    }

    const handleGetBrand = (val: any) => {
      console.log(val)
      setBrandDisabled(true)
        updateFormData.subcategory_id = val
        updateFormData.brand_id = ''
        const brands = allBrandLists?.filter(
          (x: any) => x.subcategory_id == updateFormData?.subcategory_id
        )
        setBrandLists(brands)
      
      setBrandDisabled(false)
    }

    const handleGet = (val: any) => {
        updateFormData.brand_id = val
    }

    const handleToggle = (checked: boolean) => {
      const is_active = checked ? 1 : 0
        setUpdateFormData((prev: any) => ({
          ...prev,
          status: is_active,
        }))
    }

    const handleChange = (field: string) => (e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement>) => {
    const value = e.target.value
        setUpdateFormData((prev) => ({
        ...prev,
        [field]: value,
        }))
    }

    const handleUpdate = async () => {
        setIsLoading(true)
        setErrors({})
        try {
            const response = await http.putDataWithToken(
            `/products/${updateFormData.id}`,
            updateFormData
            )
            if (response.status === true) {
            handleCloseModal()
            showToast(
                '',
                'Update Product successful',
                'top-right',
                'success'
            )
            setUpdateFormData({
                id: '',
                product_code: '',
                product_name: '',
                category_id: '',
                subcategory_id: '',
                brand_id:'',
                part_no: '',
                description: '',
                status: '',
            })
            handleReFetchProApi()
            } else {
            showToast('', 'Something went wrong!.', 'top-right', 'error')
            }
        } catch (err: any) {
            if (err?.status === 422) {
            showToast('', err?.message, 'top-right', 'error')
            const apiErrors: Errors = err?.errors
            setErrors(apiErrors)
            } else {
            showToast('', 'Update Product failed!', 'top-right', 'error')
            }
            console.error(err)
        } finally {
            setIsLoading(false)
        }
        }

    return (
      <>
        <BaseModal
          isOpen={isUpdateOpen}
          onClose={handleCloseModal}
          isFullscreen={false}
        >
          <div className="">
            {/* Product Basic Info */}
            {value == 0 ? (
              <div className="space-y-6">
                <h2 className="text-xl font-semibold text-gray-800">
                  Update Product
                </h2>
                <div className="">
                    <div className="">
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                        <Label>
                            Product Code
                            <span className="text-error-500">*</span>
                        </Label>
                        <Input
                            type="text"
                            value={updateFormData.product_code}
                            onChange={handleChange('product_code')}
                            onKeyUp={() => handleRemove('product_code')}
                            error={!!errors.product_code}
                            hint={errors.product_code}
                        />
                        </div>
                        <div>
                        <Label>
                            Product Name
                            <span className="text-error-500">*</span>
                        </Label>
                        <Input
                            type="text"
                            value={updateFormData.product_name}
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
                            value={updateFormData.category_id}
                            getOptionLabel={(item) =>
                            `${item.id} - ${item.category_name}`
                            }
                            onSingleSelectChange={(val) => {
                            handleRemove('category_id')
                            handleGetSub(val)
                            console.log(
                                'selected category id ' +
                                updateFormData.category_id
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
                            value={updateFormData.subcategory_id}
                            getOptionLabel={(item) =>
                                `${item.id} - ${item.category_name}`
                            }
                            onSingleSelectChange={(val) => {
                                handleRemove('subcategory_id')
                                handleGetBrand(val)
                                console.log(
                                'selected sub category id ' +
                                    updateFormData.subcategory_id
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
                            value={updateFormData.brand_id}
                            getOptionLabel={(item) =>
                                `${item.id} - ${item.brand_name}`
                            }
                            onSingleSelectChange={(val) => {
                                handleRemove('brand_id')
                                handleGet(val)
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
                            value={updateFormData.part_no}
                            onChange={handleChange('part_no')}
                            onKeyUp={() => handleRemove('part_no')}
                            error={!!errors.part_no}
                            hint={errors.part_no}
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
                        <Label>Status{updateFormData.status}</Label>
                        <ToggleSwitchInput
                            label="Enable Active"
                            defaultChecked={!!data.status}
                            onToggleChange={handleToggle}
                        />
                        </div>
                    </div>
                    <div className="flex justify-end gap-2">
                        <Button
                        variant="secondary"
                        onClick={handleCloseModal}
                        >
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
                </div>
              </div>
            ) : (
              ''
            )}
          </div>
        </BaseModal>
      </>
    )

}

export default ProductUpdateModal