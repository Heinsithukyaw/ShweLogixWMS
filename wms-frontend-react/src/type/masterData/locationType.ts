export interface LocationType {
  id: any
  location_code: string
  location_name: string
  location_type: string
  zone_code: string
  aisle: string
  row: string
  level: string
  bin: string
  capacity: any
  capacity_unit: string
  restrictions: string
  bar_code: string
  status: any
  [key: string]: any
}

export type LocationContent = LocationType[]
