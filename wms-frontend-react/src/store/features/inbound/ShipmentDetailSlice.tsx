import { createSlice, PayloadAction } from '@reduxjs/toolkit'
import { ShipmentDetailContent, ShipmentDetailType } from '../../../type/inbound/shipmentDetailType'

interface ShipmentDetailState {
  content: ShipmentDetailContent | []
  data: ShipmentDetailType | null
  loading: boolean
  error: string | null
  isFetched: boolean
}

const initialState: ShipmentDetailState = {
  content: [],
  data: null,
  loading: false,
  error: null,
  isFetched: false
}

const shipmentDetailSlice = createSlice({
  name: 'shipment-detail',
  initialState,
  reducers: {
    setContentStart: (state) => {
      state.loading = !state.isFetched
      state.error = null
    },
    setContentSuccess: (state, action: PayloadAction<ShipmentDetailContent>) => {
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
  shipmentDetailSlice.actions


export default shipmentDetailSlice.reducer
