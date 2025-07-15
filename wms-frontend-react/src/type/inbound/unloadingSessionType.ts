export interface UnloadingSessionType {
  id: any
  unloading_session_code: string
  inbound_shipment_id: any
  inbound_shipment_code: string
  dock_id: any
  dock_code: string
  dock_name: string
  start_time: any
  end_time: any
  supervisor_id: any
  supervisor_code: string
  supervisor_name: any
  total_pallets_unloaded: string
  total_items_unloaded: string
  equipment_used: any
  status: any
  [key: string]: any
}

export type UnloadingSessionContent = UnloadingSessionType[]
