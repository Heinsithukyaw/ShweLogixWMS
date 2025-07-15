export interface ProductType {
  id: any
  product_code: string
  product_name: string
  [key: string]: any
}

export type ProductContent = ProductType[]
