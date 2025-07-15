export interface AsnDetailType {
  id: any
  asn_detail_code : string
  asn_id : any
  item_id : any
  item_code: string
  item_name: string
  expected_qty : any
  uom_id : any
  uom_code: string
  uom_name: string
  lot_number : any
  expiration_date : any
  received_qty : any 
  variance : any
  location_id : any
  location_code: string
  location_name: string
  pallet_id : any
  pallet_code: string
  pallet_name: string
  status: any
  [key: string]: any
}

export type AsnDetailContent = AsnDetailType[] 
