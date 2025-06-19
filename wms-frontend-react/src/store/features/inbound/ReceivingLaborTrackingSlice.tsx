import { createSlice, PayloadAction } from '@reduxjs/toolkit'
import {
  ReceivingLaborTrackingContent,
  ReceivingLaborTrackingType,
} from '../../../type/inbound/receivingLaborTrackingType'

interface ReceivingLaborTrackingState {
  content: ReceivingLaborTrackingContent | []
  data: ReceivingLaborTrackingType | null
  loading: boolean
  error: string | null
  isFetched: boolean
}

const initialState: ReceivingLaborTrackingState = {
  content: [],
  data: null,
  loading: false,
  error: null,
  isFetched: false,
}

const receivingLaborTrackingSlice = createSlice({
  name: 'receiving-labor-tracking',
  initialState,
  reducers: {
    setContentStart: (state) => {
      state.loading = !state.isFetched
      state.error = null
    },
    setContentSuccess: (state, action: PayloadAction<ReceivingLaborTrackingContent>) => {
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

export const {
  setContentStart,
  setContentSuccess,
  setContentFailure,
  setToggleFetched,
  getContentData,
  deleteContentData,
} = receivingLaborTrackingSlice.actions

export default receivingLaborTrackingSlice.reducer
