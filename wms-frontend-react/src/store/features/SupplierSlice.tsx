import { createSlice, PayloadAction } from '@reduxjs/toolkit'
import { SupplierContent } from '../../type/shared/supplierType'


interface SupplierState {
  content: SupplierContent | []
  loading: boolean
  error: string | null
}

const initialState: SupplierState = {
  content: [],
  loading: false,
  error: null,
}

const supplierSlice = createSlice({
  name: 'supplier',
  initialState,
  reducers: {
    setContentStart: (state) => {
      state.loading = true
      state.error = null
    },
    setContentSuccess: (state, action: PayloadAction<SupplierContent>) => {
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
  supplierSlice.actions


export default supplierSlice.reducer
