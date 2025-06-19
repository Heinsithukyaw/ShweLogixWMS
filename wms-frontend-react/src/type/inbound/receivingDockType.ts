export interface ReceivingDockType {
  id: any
  dock_code: string
  dock_number: string
  dock_type: any
  zone_id: any
  zone_name:string
  zone_code:string
  features:string
  // additional_features:string
  version_control: string
  status: any
  [key: string]: any
}

export type ReceivingDockContent = ReceivingDockType[]
