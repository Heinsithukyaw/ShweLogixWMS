import { createSlice, PayloadAction } from '@reduxjs/toolkit'
import { WarehouseContent } from '../../type/shared/warehouseType'


interface WarehouseState {
  content: WarehouseContent | []
  loading: boolean
  error: string | null
}

const initialState: WarehouseState = {
  content: [],
  loading: false,
  error: null,
}

const warehouseSlice = createSlice({
  name: 'warehouse',
  initialState,
  reducers: {
    setContentStart: (state) => {
      state.loading = true
      state.error = null
    },
    setContentSuccess: (state, action: PayloadAction<WarehouseContent>) => {
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
  warehouseSlice.actions


export default warehouseSlice.reducer
