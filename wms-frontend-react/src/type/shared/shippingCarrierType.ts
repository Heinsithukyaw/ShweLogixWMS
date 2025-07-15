export interface ShippingCarrierType {
  id: any
  carrier_code: string
  carrier_name: string
  [key: string]: any
}

export type ShippingCarrierContent = ShippingCarrierType[]
