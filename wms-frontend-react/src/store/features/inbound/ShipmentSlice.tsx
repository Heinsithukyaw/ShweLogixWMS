import { createSlice, PayloadAction } from '@reduxjs/toolkit'
import { ShipmentContent, ShipmentType } from '../../../type/inbound/shipmentType'

interface ShipmentState {
  content: ShipmentContent | []
  data: ShipmentType | null
  loading: boolean
  error: string | null
  isFetched: boolean
}

const initialState: ShipmentState = {
  content: [],
  data: null,
  loading: false,
  error: null,
  isFetched: false
}

const shipmentSlice = createSlice({
  name: 'shipment',
  initialState,
  reducers: {
    setContentStart: (state) => {
      state.loading = !state.isFetched
      state.error = null
    },
    setContentSuccess: (state, action: PayloadAction<ShipmentContent>) => {
      state.loading = false
      state.content = action.payload
      state.isFetched = true
    },
    setContentFailure: (state, action: PayloadAction<string>) => {
      state.loading = false
      state.error = action.payload
    },
    getContentData: (state, action) => {
      const id = action.payload
      state.data = state.content?.find((x: any) => x.id === id) || null
    },
    deleteContentData: (state, action) => {
      const id = action.payload
      state.content = state.content?.filter((x: any) => x.id !== id) || []
    },
    setToggleFetched: (state) => {
      state.isFetched = !state.isFetched
    },
  },
})

export const { setContentStart, setContentSuccess, setContentFailure, setToggleFetched, getContentData, deleteContentData } =
  shipmentSlice.actions


export default shipmentSlice.reducer
