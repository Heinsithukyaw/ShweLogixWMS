import { createSlice, PayloadAction } from '@reduxjs/toolkit'
import { ProductContent } from '../../type/shared/productType'


interface ProductState {
  content: ProductContent | []
  loading: boolean
  error: string | null
}

const initialState: ProductState = {
  content: [],
  loading: false,
  error: null,
}

const productSlice = createSlice({
  name: 'product',
  initialState,
  reducers: {
    setContentStart: (state) => {
      state.loading = true
      state.error = null
    },
    setContentSuccess: (state, action: PayloadAction<ProductContent>) => {
      state.loading = false
      state.content = action.payload
    },
    setContentFailure: (state, action: PayloadAction<string>) => {
      state.loading = false
      state.error = action.payload
    },
  },
})

export const { setContentStart, setContentSuccess, setContentFailure } =
  productSlice.actions


export default productSlice.reducer
