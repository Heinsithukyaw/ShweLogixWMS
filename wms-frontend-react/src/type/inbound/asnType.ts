export interface AsnType {
  id:any
  asn_code: string
  asn_name: string
  supplier_id: any
  supplier_code: string
  supplier_name: string
  purchase_order_name: string
  expected_arrival: string
  carrier_id: any
  carrier_code: string
  carrier_name: string
  tracking_number: string
  total_items: string
  total_pallets: string
  dimensions: string
  received_date: string
  status: any
  [key: string]: any
}

export type AsnContent = AsnType[] 
