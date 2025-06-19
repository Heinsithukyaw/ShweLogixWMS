export interface ShipmentType {
  id: any
  shipment_code: string
  supplier_id: any
  supplier_code: string
  supplier_name: string
  purchase_order_name: string
  expected_arrival: string
  actual_arrival: string
  trailer_number: string
  seal_number: string
  carrier_id: any
  carrier_code: string
  carrier_name: string
  total_weight: string
  total_pallet: string
  version_control: string
  status: any
  [key: string]: any
}

export type ShipmentContent = ShipmentType[]
