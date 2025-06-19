import React,{useState,useEffect} from 'react'
import Button from '../../../../../components/ui/button/Button'
import Label from '../../../../../components/form/Label'
import Input from '../../../../../components/form/input/InputField'
import SingleSelectInput from '../../../../../components/form/form-elements/SelectInputs'
import Spinner from '../../../../../components/ui/loading/spinner'
import { createGrnItem } from '../../services/grnItemApi'
import { useAppDispatch, useAppSelector } from '../../../../../store/hook'
import { RootState } from '../../../../../store/store'
import provideUtility from '../../../../../utils/toast'
import { FaTrash } from 'react-icons/fa'

interface Props {
    isOpen: true | false
    handleShowLists:() => void
}

interface Errors {
  product_id?:any
  uom_id?: any
  location_id?: any
  condition_status?: any
}

const GrnItemCreateForm:React.FC<Props> = ({isOpen,handleShowLists}) => {
   const dispatch = useAppDispatch()
   const [isLoading, setIsLoading] = useState<any>(false)
   const [isCreated, setIsCreated] = useState<any>(false)
   const [errors, setErrors] = useState<Errors[]>([])
   const productLists = useAppSelector((state: RootState) => state.product?.content)
   const uomLists = useAppSelector((state: RootState) => state.uom?.content)
   const stagingLocationLists = useAppSelector((state: RootState) => state.stagingLocation?.content)
   const [stagingLocations, setStagingLocations] = useState<any>([])

   const { showToast } = provideUtility()

   const conditionStatus = [
     {
       id: 0,
       value: 'damaged',
     },
     {
       id: 2,
       value: 'expired',
     },
     {
       id: 1,
       value: 'good',
     },
   ]
   
   const [formData, setFormData] = useState<any[]>([
     {
       grn_id: '',
       product_id: '',
       product_name: '',
       uom_id: '',
       expected_qty: '',
       received_qty: '',
       location_id: '',
       notes: '',
       condition_status: '',
     },
   ])

   useEffect(() => {
    if(stagingLocationLists){
      setStagingLocations(stagingLocationLists.filter((x:any) => x.area_type == 'Receiving' && x.zone_type == 'Receiving'))
    }
   },[stagingLocationLists])

   const handleAddItems = () => {
     setFormData((prev) => [
       ...prev,
       {
         grn_id: '',
         product_id: '',
         product_name: '',
         uom_id: '',
         expected_qty: '',
         received_qty: '',
         location_id: '',
         notes: '',
         condition_status: '',
       },
     ])
   }
  
   const handleProductChange = (index: number, val: any) => {
     const selectedProduct = productLists.find((x: any) => x.id == val)
     setFormData((prev) => {
       const updated = [...prev]
       updated[index] = {
         ...updated[index],
         product_id: val,
         product_name: selectedProduct?.product_name || '',
       }
       return updated
     })
   }

   const handleSelectChange = (index: number,field: string, val: any) => {
     console.log(val)
     const selectedProduct = productLists.find((x: any) => x.id == val)
     console.log(selectedProduct)
     setFormData((prev) => {
       const updated = [...prev]
       updated[index] = {
         ...updated[index],
         [field]: val,
       }
       return updated
     })
   }

    const handleChange =
    (index: number, field: string) =>
    (e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement>) => {
        const value = e.target.value
        setFormData((prev) => {
        const updated = [...prev]
        updated[index] = {
            ...updated[index],
            [field]: value,
        }
        return updated
        })
    }
      

    const handleRemove = (index:number,field: string) => {
        setErrors((prev) => {
          const updated = [...prev]
          updated[index] = {
            ...updated[index],
            [field]: '',
          }
          return updated
        })
    }

    const handleSubmit = async () => {
      const newErrors: Errors[] = []

      let hasError = false

      formData.forEach((item) => {
        setIsLoading(true)
        const error: Errors = {}
        console.log(item.condition_status)
        if (!item.product_id) error.product_id = 'Product is required'
        if (!item.uom_id) error.uom_id = 'UOM is required'
        if (!item.location_id) error.location_id = 'Location is required'
        if (!item.condition_status) error.condition_status = 'Condition is required'

        if (Object.keys(error).length > 0) {
          hasError = true
        }
        newErrors.push(error)
      })

      if (hasError) {
        setErrors(newErrors)
        setIsLoading(false)
        return
      }
      const complete = await dispatch(createGrnItem({ items: formData }))
      setIsLoading(false)
      console.log(complete)
      if (complete?.status) {
        showToast('', 'Create GRN Items Successfully', 'top-right', 'success')
        setIsCreated(true)
        handleShowLists()
      } else {
        showToast(complete?.error, 'Failed GRN Items Created', 'top-right', 'error')

      }
    }
    

    const removeItems = (index: number) => {
      if (formData.length === 1) return // Prevent deletion if it's the last item
      setFormData((prev) => prev.filter((_, i) => i !== index))
      setErrors((prev) => prev.filter((_, i) => i !== index))
    }
    
    
  return (
    <>
      <div className="space-y-6">
        <div className="flex justify-between items-center">
          <h2 className="text-xl font-semibold">Add Items</h2>
          <Button
            variant="primary"
            size="sm"
            onClick={handleAddItems}
            disabled={!isOpen}
          >
            Add Items
          </Button>
        </div>
        {isOpen && (
          <div className="max-w-auto rounded-2xl overflow-auto shadow-lg bg-white p-6 h-[35rem]">
            {formData.map((item, index) => (
              <>
                <div
                  key={index}
                  className="grid grid-cols-1 md:grid-cols-9 gap-6 mb-6"
                >
                  <div>
                    <Label>
                      Product<span className="text-error-500">*</span>
                    </Label>
                    <SingleSelectInput
                      options={productLists}
                      valueKey="id"
                      value={item.product_id}
                      getOptionLabel={(item) => `${item.product_code}`}
                      onSingleSelectChange={(val) => {
                        handleRemove(index,'product_id')
                        handleProductChange(index, val)
                      }}
                      error={!!errors[index]?.product_id}
                    />
                  </div>

                  <div>
                    <Label>Product Name</Label>
                    <Input
                      type="text"
                      value={item.product_name}
                      disabled={true}
                    />
                  </div>

                  <div>
                    <Label>Expected Qty</Label>
                    <Input
                      type="number"
                      value={item.expected_qty}
                      onChange={handleChange(index, 'expected_qty')}
                    />
                  </div>

                  <div>
                    <Label>Received Qty</Label>
                    <Input
                      type="number"
                      value={item.received_qty}
                      onChange={handleChange(index, 'received_qty')}
                    />
                  </div>
                  <div>
                    <Label>
                      UOM<span className="text-error-500">*</span>
                    </Label>
                    <SingleSelectInput
                      options={uomLists}
                      valueKey="id"
                      value={item.uom_id}
                      getOptionLabel={(item) => `${item.uom_code}`}
                      onSingleSelectChange={(val) => {
                        handleRemove(index,'uom_id')
                        handleSelectChange(index, 'uom_id', val)
                      }}
                      error={!!errors[index]?.uom_id}
                    />
                  </div>
                  <div>
                    <Label>
                      Location<span className="text-error-500">*</span>
                    </Label>
                    <SingleSelectInput
                      options={stagingLocations}
                      valueKey="id"
                      value={item.location_id}
                      getOptionLabel={(item) => `${item.staging_location_code}`}
                      onSingleSelectChange={(val) => {
                        handleRemove(index,'location_id')
                        handleSelectChange(index, 'location_id', val)
                      }}
                      error={!!errors[index]?.location_id}
                    />
                  </div>
                  <div>
                    <Label>
                      Condition<span className="text-error-500">*</span>
                    </Label>
                    <SingleSelectInput
                      options={conditionStatus}
                      valueKey="id"
                      value={item.condition_status}
                      getOptionLabel={(item) => `${item.value}`}
                      onSingleSelectChange={(val) => {
                        handleRemove(index,'condition_status')
                        handleSelectChange(index, 'condition_status', val)
                      }}
                      error={!!errors[index]?.condition_status}
                    />
                  </div>
                  <div>
                    <Label>Notes</Label>
                    <Input
                      type="text"
                      value={item.notes}
                      onChange={handleChange(index, 'notes')}
                    />
                  </div>
                  <div className="flex flex-col items-center">
                    <Label>Action</Label>
                    <div className="flex items-center justify-center">
                      {index > 0 && (
                        <button
                          className="text-red-600 mt-2"
                          onClick={() => removeItems(index)}
                        >
                          <FaTrash />
                        </button>
                      )}
                    </div>
                  </div>
                </div>
                <hr className="my-5 semibold border-2 border-gray-200" />
              </>
            ))}
          </div>
        )}
      </div>
      {isOpen && (
        <div className="flex justify-end items-center gap-4">
          <Button variant="secondary" onClick={handleShowLists}>
            Cancel
          </Button>
          <Button
            variant="primary"
            startIcon={isLoading && <Spinner size={4} />}
            onClick={handleSubmit}
            disabled={isCreated}
          >
            Confirm
          </Button>
        </div>
      )}
    </>
  )
}

export default GrnItemCreateForm