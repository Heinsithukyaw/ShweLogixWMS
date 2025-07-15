export interface ProductInventoryType {
  product_id: number
  product_name: string
  uom:string
  warehouse_id:string,
  location:string,
  batch_no:string,
  lot_no:string,
  packing_qty:number,
  whole_qty:number,
  loose_qty:number,
  reorder_level:number,
  stock_rotation_policy:string
}

export interface ProductDimensionType {
  product_id: number
  dimension_use: string
  length: string
  width: string
  height: string
  weight: string
  volume: string
  storage_volume: string
  space_area: string
  units_per_box: number
  boxes_per_pallet: number
}

export interface ProductCommercialType {
  product_id: number
  customer_code: string
  bar_code: string
  cost_price: string
  standard_price: string
  currency: string
  discount: string
  supplier: any
  manufacturer: string
  country_code: number
}

export interface ProductOtherType {
  product_id: number
  manufacture_date: string
  expire_date: string
  abc_category_value: string
  abc_category_activity: string
  remark: string
  custom_attribute: string

}

export interface ProductListsType {
  product_id: number
  product_name: string
}

export interface UomListsType {
  uom_id: number
  uom_name: string
}
