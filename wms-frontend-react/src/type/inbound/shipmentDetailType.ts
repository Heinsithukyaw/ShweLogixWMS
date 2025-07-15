export interface ShipmentDetailType {
  id: any
  inbound_detail_code : string
  inbound_shipment_id : any
  product_id : any
  product_code: string
  product_name: string
  purchase_order_id: any
  purchase_order_number: string
  expected_qty : any
  received_qty: any
  damaged_qty: any
  lot_number: any
  expiration_date : any
  location_id : any
  location_code: string
  location_name: string
  received_by: string
  received_date: string
  status: any
  [key: string]: any
}

export type ShipmentDetailContent = ShipmentDetailType[] 
