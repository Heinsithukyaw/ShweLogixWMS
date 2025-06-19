export interface PalletType {
  id: any
  pallet_code: string
  pallet_name: string
  [key: string]: any
}

export type PalletContent = PalletType[]
