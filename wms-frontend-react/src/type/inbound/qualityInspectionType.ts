export interface QualityInspectionType {
  id: any
  quality_inspection_code: string
  inbound_shipment_detail_id: any
  inbound_shipment_detail_code: string
  inspection_date: string
  sample_size: string
  rejection_reason: string
  corrective_action: string
  image: any
  is_passed: any
  status: any
  [key: string]: any
}

export type QualityInspectionContent = QualityInspectionType[]
