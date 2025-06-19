import React from 'react'
import BaseModal from '../../../../components/ui/modal'
import Label from '../../../../components/form/Label'
import Input from '../../../../components/form/input/InputField'
import TextAreaInput from '../../../../components/form/form-elements/TextAreaInput'
import ToggleSwitchInput from '../../../../components/form/form-elements/ToggleSwitch'

interface Props {
  data: any
  value: 0 | 1 | 2 | 3 | 4
  isViewOpen: true | false
  handleCloseModal: () => void
}

const ProductViewModal: React.FC<Props> = ({ value, data, isViewOpen, handleCloseModal }) => {

    return (
      <>
        <BaseModal
          isOpen={isViewOpen}
          onClose={handleCloseModal}
          isFullscreen={false}
        >
          <div className="">
            {/* Product Basic Info */}
            {value == 0 ? (
              <div className="space-y-6">
                <h2 className="text-xl font-semibold text-gray-800">Product</h2>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                  <div>
                    <Label>Product Code</Label>
                    <Input
                      type="text"
                      value={data?.product_code}
                      disabled={true}
                    />
                  </div>
                  <div>
                    <Label>Product Name</Label>
                    <Input
                      type="text"
                      value={data?.product_name}
                      disabled={true}
                    />
                  </div>

                  <div>
                    <Label>Category</Label>
                    <Input
                      type="text"
                      value={data?.category?.category_name}
                      disabled={true}
                    />
                  </div>

                  <div>
                    <Label>SubCategory</Label>
                    <Input
                      type="text"
                      value={data?.subcategory?.category_name}
                      disabled={true}
                    />
                  </div>

                  <div>
                    <Label>Brand</Label>
                    <Input
                      type="text"
                      value={data?.brand?.brand_name}
                      disabled={true}
                    />
                  </div>
                  <div>
                    <Label>Part No</Label>
                    <Input type="text" value={data?.part_no} disabled={true} />
                  </div>
                  <div className="col-span-full">
                    {/* <Label>Description</Label> */}
                    <TextAreaInput
                      value={data?.description}
                      onChange={(value) => console.log(value)}
                      disabled={true}
                    />
                  </div>
                  <div>
                    <Label>Status</Label>
                    <ToggleSwitchInput
                      label="Enable Active"
                      defaultChecked={!!data?.status}
                      onToggleChange={() => console.log('toggle')}
                      disabled={true}
                    />
                  </div>
                </div>
              </div>
            ) : (
              ''
            )}

            {/* Product  Inventory */}
            {value == 1 ? (
              <div className="">
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                  <div>
                    <Label>Product</Label>
                    <Input
                      type="text"
                      value={data?.product_name}
                      disabled={true}
                    />
                  </div>
                  <div>
                    <Label>Unit Of Measure</Label>
                    <Input type="text" value={data?.uom_name} disabled={true} />
                  </div>
                  <div>
                    <Label>Warehouse Code</Label>
                    <Input
                      type="text"
                      value={data?.warehouse_code}
                      disabled={true}
                    />
                  </div>
                  <div>
                    <Label>Location</Label>
                    <Input type="text" value={data?.location} disabled={true} />
                  </div>
                  <div>
                    <Label>Reorder Level</Label>
                    <Input
                      type="text"
                      value={data?.reorder_level}
                      disabled={true}
                    />
                  </div>
                  <div>
                    <Label>Batch No</Label>
                    <Input type="text" value={data?.batch_no} disabled={true} />
                  </div>
                  <div>
                    <Label>Lot No</Label>
                    <Input type="text" value={data?.lot_no} disabled={true} />
                  </div>
                  <div>
                    <Label>Packing Qty</Label>
                    <Input
                      type="text"
                      value={data?.packing_qty}
                      disabled={true}
                    />
                  </div>
                  <div>
                    <Label>Whole Qty</Label>
                    <Input
                      type="text"
                      value={data?.whole_qty}
                      disabled={true}
                    />
                  </div>
                  <div>
                    <Label>Loose Qty</Label>
                    <Input
                      type="text"
                      value={data?.loose_qty}
                      disabled={true}
                    />
                  </div>
                  <div>
                    <Label>Stock Rotation Policy</Label>
                    <Input
                      type="text"
                      value={data?.stock_rotation_policy}
                      disabled={true}
                    />
                  </div>
                </div>
              </div>
            ) : (
              ''
            )}

            {/* Product  Dimension */}
            {value == 2 ? (
              <div className="">
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                  <div>
                    <Label>Product</Label>
                    <Input
                      type="text"
                      value={data?.product_name}
                      disabled={true}
                    />
                  </div>
                  <div>
                    <Label>Dimension Use</Label>
                    <Input
                      type="text"
                      value={data?.dimension_use}
                      disabled={true}
                    />
                  </div>
                  <h1 className="space-y-10">Product Dimensions</h1>
                  <div className="relative">
                    <Label>Length</Label>
                    <Input type="text" value={data?.length} disabled={true} />
                    <span className="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 text-sm">
                      cm
                    </span>
                  </div>
                  <div>
                    <Label>Width</Label>
                    <Input type="text" value={data?.width} disabled={true} />
                  </div>
                  <div>
                    <Label>Height</Label>
                    <Input type="text" value={data?.height} disabled={true} />
                  </div>
                  <div>
                    <Label>Weight</Label>
                    <Input type="text" value={data?.weight} disabled={true} />
                  </div>
                  <div>
                    <Label>Volume</Label>
                    <Input type="text" value={data?.volume} disabled={true} />
                  </div>
                  <div>
                    <Label>Storage Volume</Label>
                    <Input
                      type="text"
                      value={data?.storage_volume}
                      disabled={true}
                    />
                  </div>
                  <div>
                    <Label>Space Area</Label>
                    <Input
                      type="text"
                      value={data?.space_area}
                      disabled={true}
                    />
                  </div>
                  <div>
                    <Label>Units Per Box</Label>
                    <Input
                      type="text"
                      value={data?.units_per_box}
                      disabled={true}
                    />
                  </div>
                  <div>
                    <Label>Boxes Per Pallet</Label>
                    <Input
                      type="text"
                      value={data?.boxes_per_pallet}
                      disabled={true}
                    />
                  </div>
                </div>
              </div>
            ) : (
              ''
            )}

            {/* Product  Commercial */}
            {value == 3 ? (
              <div className="">
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                  <div>
                    <Label>Product</Label>
                    <Input
                      type="text"
                      value={data?.product_name}
                      disabled={true}
                    />
                  </div>
                  <div>
                    <Label>Customer Code</Label>
                    <Input
                      type="text"
                      value={data?.customer_code}
                      disabled={true}
                    />
                  </div>
                  <div className="relative">
                    <Label>Bar Code</Label>
                    <Input type="text" value={data?.bar_code} disabled={true} />
                  </div>
                  <div>
                    <Label>Cost Price</Label>
                    <Input
                      type="text"
                      value={`${data?.cost_price}`}
                      disabled={true}
                    />
                  </div>
                  <div>
                    <Label>Standard Price</Label>
                    <Input
                      type="text"
                      value={data?.standard_price}
                      disabled={true}
                    />
                  </div>
                  <div>
                    <Label>Currency</Label>
                    <Input type="text" value={data?.currency} disabled={true} />
                  </div>
                  <div>
                    <Label>Supplier</Label>
                    <Input type="text" value={data?.supplier_code} disabled={true} />
                  </div>
                  <div>
                    <Label>Manufacturer</Label>
                    <Input
                      type="text"
                      value={data?.manufacturer}
                      disabled={true}
                    />
                  </div>
                  <div>
                    <Label>Country Code</Label>
                    <Input
                      type="text"
                      value={data?.country_code}
                      disabled={true}
                    />
                  </div>
                </div>
              </div>
            ) : (
              ''
            )}

            {/* Product  Other */}
            {value == 4 ? (
              <div className="">
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                  <div>
                    <Label>Product</Label>
                    <Input
                      type="text"
                      value={data?.product_name}
                      disabled={true}
                    />
                  </div>
                  <div>
                    <Label>Manufacture Date</Label>
                    <Input
                      type="text"
                      value={data?.manufacture_date}
                      disabled={true}
                    />
                  </div>
                  <div className="relative">
                    <Label>Expire Date</Label>
                    <Input
                      type="text"
                      value={data?.expire_date}
                      disabled={true}
                    />
                  </div>
                  <div>
                    <Label>ABC Category Value</Label>
                    <Input
                      type="text"
                      value={data?.abc_category_value}
                      disabled={true}
                    />
                  </div>
                  <div>
                    <Label>ABC Category Activity</Label>
                    <Input
                      type="text"
                      value={data?.abc_category_activity}
                      disabled={true}
                    />
                  </div>
                  <div className="col-span-full">
                    {/* <Label>Description</Label> */}
                    <TextAreaInput
                      value={data?.remark}
                      onChange={(value) => console.log(value)}
                      disabled={true}
                    />
                  </div>
                  <div className="col-span-full">
                    {/* <Label>Description</Label> */}
                    <TextAreaInput
                      value={data?.custom_attributes}
                      onChange={(value) => console.log(value)}
                      disabled={true}
                    />
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

export default ProductViewModal