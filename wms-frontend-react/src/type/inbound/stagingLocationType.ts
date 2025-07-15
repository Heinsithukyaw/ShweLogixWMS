export interface StagingLocationType {
  id: any
  staging_location_code: string
  staging_location_name: string
  warehouse_id:any
  area_id:any
  zone_id:any
  type: string
  capacity: string
  current_usage: string
  last_updated: string
  status: any
  [key: string]: any
}

export type StagingLocationContent = StagingLocationType[]
