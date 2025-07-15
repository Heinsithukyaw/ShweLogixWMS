export interface GrnItemType {
  // id: any
  // grn_id: any
  // grn_code: any
  // product_id: any
  // product_code: string
  // product_name: string
  // uom_id:any
  // uom_code: string
  // uom_name: string
  // expected_qty: any
  // received_qty: string
  // location_id: any
  // location_code: string
  // location_name: string
  // condition_status: any
  [key: string]: any
}

export type GrnItemContent = GrnItemType[]
