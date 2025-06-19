import { createSlice, PayloadAction } from '@reduxjs/toolkit'
import { ShippingCarrierContent } from '../../type/shared/shippingCarrierType'


interface ShippingCarrierState {
  content: ShippingCarrierContent | []
  loading: boolean
  error: string | null
}

const initialState: ShippingCarrierState = {
  content: [],
  loading: false,
  error: null,
}

const shippingCarrierSlice = createSlice({
  name: 'shipping-carrier',
  initialState,
  reducers: {
    setContentStart: (state) => {
      state.loading = true
      state.error = null
    },
    setContentSuccess: (state, action: PayloadAction<ShippingCarrierContent>) => {
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
  shippingCarrierSlice.actions


export default shippingCarrierSlice.reducer
