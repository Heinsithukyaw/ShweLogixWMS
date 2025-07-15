import { createSlice, PayloadAction } from '@reduxjs/toolkit'
import { ReceivingDockContent, ReceivingDockType } from '../../../type/inbound/receivingDockType'

interface ReceivingDockState {
  content: ReceivingDockContent | []
  data: ReceivingDockType | null
  loading: boolean
  error: string | null
  isFetched: boolean
}

const initialState: ReceivingDockState = {
  content: [],
  data: null,
  loading: false,
  error: null,
  isFetched: false
}

const receivingDockSlice = createSlice({
  name: 'receiving-dock',
  initialState,
  reducers: {
    setContentStart: (state) => {
      state.loading = !state.isFetched
      state.error = null
    },
    setContentSuccess: (state, action: PayloadAction<ReceivingDockContent>) => {
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
  receivingDockSlice.actions


export default receivingDockSlice.reducer
