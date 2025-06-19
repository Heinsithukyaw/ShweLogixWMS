export interface ReceivingEquipmentType {
  id: any
  receiving_equipment_code: string
  receiving_equipment_name: string
  receiving_equipment_type: string
  assigned_to_id: any
  assigned_to_code: string
  assigned_to_name: string
  last_maintenance_date: any
  days_since_maintenance: any
  version_control: string
  status: any
  [key: string]: any
}

export type ReceivingEquipmentContent = ReceivingEquipmentType[]
