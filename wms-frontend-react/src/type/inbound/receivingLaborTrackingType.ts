export interface ReceivingLaborTrackingType {
  id: any
  labor_entry_code: string
  emp_id: any
  emp_code: string
  emp_name: string
  inbound_shipment_id: any
  inbound_shipment_code: string
  inbound_shipment_name: string
  task_type: any
  start_time: string
  end_time: string
  duration_min: any
  items_processed: string
  pallets_processed: string
  items_min: string
  version_control: any
  status: any
  [key: string]: any
}

export type ReceivingLaborTrackingContent = ReceivingLaborTrackingType[]
