export interface WarehouseType {
  id: any
  warehouse_code: string
  warehouse_name: string
  [key: string]: any
}

export type WarehouseContent = WarehouseType[]
