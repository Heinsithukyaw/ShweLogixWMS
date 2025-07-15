export interface ReceivingAppointmentType {
  id: any
  appointment_code: string
  inbound_shipment_id: any
  supplier_id: any
  supplier_code: string
  supplier_name: string
  dock_id: any
  dock_code: string
  dock_name: string
  purchase_order_id: any
  scheduled_date: any
  start_time: any
  end_time: any
  carrier_name: any
  driver_name: any
  driver_phone_number: any
  trailer_number: string
  estimated_pallet: string
  check_in_time: any
  check_out_time: string
  version_control: string
  status: any
  [key: string]: any
}

export type ReceivingAppointmentContent = ReceivingAppointmentType[]
