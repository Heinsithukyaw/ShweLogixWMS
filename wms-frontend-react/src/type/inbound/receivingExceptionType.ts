export interface ReceivingExceptionType {
  id: any
  exception_code: string
  asn_id: any
  asn_code: string
  asn_detail_id: any
  asn_detail_code: string
  item_id: any
  item_code: string
  item_name: string
  reported_by_id: any
  reported_by_code: string
  reported_by_name: string
  assigned_to_id: any
  assigned_to_code: string
  assigned_to_name: string
  exception_type: string
  reported_date: any
  resolved_date: any
  status: any
  [key: string]: any
}

export type ReceivingExceptionContent = ReceivingExceptionType[]
