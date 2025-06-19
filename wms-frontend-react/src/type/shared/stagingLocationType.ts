export interface StagingLocationType {
  id: any
  staging_location_code: string
  staging_location_name: string
  type:string
  [key: string]: any
}

export type StagingLocationContent = StagingLocationType[]
