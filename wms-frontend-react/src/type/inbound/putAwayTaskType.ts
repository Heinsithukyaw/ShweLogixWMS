export interface PutAwayTaskType {
  id: any
  put_away_task_code: string
  inbound_shipment_detail_id: any
  inbound_shipment_detail_code: string
  assigned_to_id: any
  assigned_to_code: string
  assigned_to_name: string
  created_date: any
  due_date: any
  start_time: any
  complete_time: any
  source_location_id: any
  source_location_code: string
  source_location_name: string
  destination_location_id: any
  destination_location_code: string
  destination_location_name: string
  qty:any
  priority:any
  status: any
  [key: string]: any
}

export type PutAwayTaskContent = PutAwayTaskType[]
