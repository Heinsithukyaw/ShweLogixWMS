export interface CrossDockingTaskType {
  id: any
  cross_docking_task_code: string
  asn_id: any
  asn_code: string
  asn_detail_id: any
  asn_detail_code: string
  item_id: any
  item_code: string
  item_name: string
  outbound_shipment_id: any
  source_location_id: any
  source_location_code: string
  source_location_name: string
  destination_location_id: any
  destination_location_code: string
  destination_location_name: string
  assigned_to_id: any
  assigned_to_code: string
  assigned_to_name: string
  created_date: string
  start_time: any
  complete_time: any
  qty:any
  priority:any
  status: any
  [key: string]: any
}

export type CrossDockingTaskContent = CrossDockingTaskType[]
