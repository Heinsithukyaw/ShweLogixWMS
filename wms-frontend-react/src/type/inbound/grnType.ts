export interface GrnType {
  id: any
  grn_code: string
  inbound_shipment_id: any
  inbound_shipment_code: string
  supplier_id: any
  supplier_code: string
  supplier_name: string
  purchase_order_id: any
  purchase_order_name: string
  created_by: any
  created_by_code: string
  created_by_name: string
  approved_by: any
  approved_by_code: string
  approved_by_name: string
  total_items: string
  received_date: string
  status: any
  [key: string]: any
}

export type GrnContent = GrnType[]
